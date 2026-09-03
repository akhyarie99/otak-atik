<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Langganan;
use App\Models\Tagihan;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Tagihan — milestone 7.2 (PRD 9.2). Faktur/kwitansi diunduh sebagai
// halaman cetak (pola sama seperti LkpdController), bukan PDF — tidak
// menambah dependensi besar untuk pembuatan PDF (CLAUDE.md).
class TagihanController extends Controller
{
    // Bayar lewat VA Midtrans — membuat transaksi (atau simulasi kalau
    // Midtrans belum dikonfigurasi, lihat MidtransService) dan menyimpan
    // nomor VA-nya ke tagihan supaya bisa ditampilkan berulang tanpa
    // membuat transaksi baru setiap kali halaman dibuka.
    public function bayar(Request $request, Tagihan $tagihan, MidtransService $midtrans): \Illuminate\Http\RedirectResponse
    {
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');
        abort_unless($keanggotaan->peran->bolehKelolaSekolah(), 403);
        abort_if($tagihan->lunas(), 422, 'Tagihan ini sudah lunas.');

        if (! $tagihan->midtrans_order_id) {
            $hasil = $midtrans->buatTransaksiVa($tagihan);
            $tagihan->update([
                'metode' => 'midtrans_va',
                'midtrans_order_id' => $hasil['order_id'],
                'midtrans_va_nomor' => $hasil['va_nomor'],
                'midtrans_bank' => $hasil['bank'],
            ]);
        }

        return back()->with('status', "Nomor VA {$tagihan->fresh()->midtrans_bank}: {$tagihan->fresh()->midtrans_va_nomor}");
    }

    // Notifikasi webhook Midtrans — TIDAK melewati auth/tenant middleware
    // sama sekali (Midtrans yang memanggil, bukan pengguna berlogin), jadi
    // Tagihan dicari lewat withoutGlobalScopes() dengan sengaja (satu-
    // satunya cara sah keluar dari TenantScope, bukan celah — lihat pola
    // yang sama di KaryaController::pulihkan untuk kasus serupa).
    public function webhook(Request $request, MidtransService $midtrans): \Illuminate\Http\JsonResponse
    {
        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signature = (string) $request->input('signature_key');
        $transactionStatus = (string) $request->input('transaction_status');

        // Order "SIM-..." dari simulasi lokal TIDAK PERNAH datang dari
        // Midtrans sungguhan — tolak eksplisit, bukan cuma gagal verifikasi.
        abort_if(str_starts_with($orderId, 'SIM-'), 422, 'Order simulasi tidak menerima webhook sungguhan.');
        abort_unless($midtrans->verifikasiSignature($orderId, $statusCode, $grossAmount, $signature), 403, 'Tanda tangan tidak valid.');

        if (in_array($transactionStatus, ['settlement', 'capture'], true)) {
            $tagihan = Tagihan::withoutGlobalScopes()->where('midtrans_order_id', $orderId)->first();
            if ($tagihan && ! $tagihan->lunas()) {
                $this->tandaiLunasInternal($tagihan);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    // Penandaan lunas manual (PO/transfer bank, PRD 9.2) — admin platform
    // saja. Keanggotaan admin platform sekolah_id-nya NULL (tidak terikat
    // satu sekolah, lihat migrasi keanggotaan), jadi dicek langsung dari
    // user, bukan lewat keanggotaan_aktif/TenantContext seperti biasa.
    public function tandaiLunas(Request $request, int $tagihanId): \Illuminate\Http\RedirectResponse
    {
        $keanggotaanAdmin = $request->user()->keanggotaan()->where('peran', Peran::AdminPlatform)->where('aktif', true)->first();
        abort_unless($keanggotaanAdmin, 403);

        $tagihan = Tagihan::withoutGlobalScopes()->findOrFail($tagihanId);
        abort_if($tagihan->lunas(), 422, 'Tagihan ini sudah lunas.');

        $tagihan->update(['metode' => $tagihan->metode ?? 'transfer_manual']);
        $this->tandaiLunasInternal($tagihan, $keanggotaanAdmin->id);

        return back()->with('status', "Tagihan {$tagihan->nomor_faktur} ditandai lunas.");
    }

    private function tandaiLunasInternal(Tagihan $tagihan, ?int $ditandaiOleh = null): void
    {
        $tagihan->update([
            'status' => 'lunas',
            'lunas_pada' => now(),
            'ditandai_lunas_oleh' => $ditandaiOleh,
        ]);

        $langganan = Langganan::withoutGlobalScopes()->find($tagihan->langganan_id);
        if (! $langganan) return;

        // Perpanjang sampai periode_selesai tagihan ini — kalau ada
        // beberapa tagihan lunas berturut-turut (jarang, tapi mungkin),
        // ambil yang PALING JAUH supaya tidak mundur.
        if ($tagihan->periode_selesai->gt($langganan->berakhir_pada)) {
            $langganan->update(['berakhir_pada' => $tagihan->periode_selesai]);
        }
        $langganan->update(['status' => 'aktif']);
    }

    public function cetak(Request $request, int $tagihanId): View
    {
        // Faktur perlu bisa diunduh admin sekolah (pemilik tagihan) MAUPUN
        // admin platform (bukan pemilik tenant tapi berhak lihat semua) —
        // dicari lewat ID langsung tanpa TenantScope, verifikasi hak akses
        // manual di sini (pola sama seperti KaryaController::pulihkan).
        $tagihan = Tagihan::withoutGlobalScopes()->findOrFail($tagihanId);
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');
        $adminPlatform = $request->user()->keanggotaan()->where('peran', Peran::AdminPlatform)->where('aktif', true)->exists();
        abort_unless($adminPlatform || ($keanggotaan && $keanggotaan->sekolah_id === $tagihan->tenant_id), 404);

        // langganan() ikut lewat Langganan::query() BIASA (TenantScope
        // aktif) — tanpa tenant aktif (mis. dilihat admin platform) itu
        // fail-closed jadi null. Diisi tangan lewat withoutGlobalScopes(),
        // konsisten dengan Tagihan itu sendiri di atas.
        $tagihan->setRelation('langganan', Langganan::withoutGlobalScopes()->find($tagihan->langganan_id));

        return view('faktur', ['tagihan' => $tagihan, 'sekolah' => $tagihan->sekolah]);
    }
}
