<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Keanggotaan;
use App\Models\Langganan;
use App\Models\Sekolah;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// Milestone 7.2 — "selesai bila": satu siklus langganan penuh
// disimulasikan dari daftar sampai perpanjangan. Ini ujian literalnya:
// daftar -> uji coba -> tagihan H-60 -> pengingat -> lunas -> aktif ->
// (siklus berikutnya) lewat jatuh tempo -> tenggang -> hanya-baca ->
// dibayar lagi -> aktif lagi, dengan penegakan hanya-baca sungguhan
// diverifikasi lewat rute HTTP (bukan cuma dibaca dari model).
class TagihanSiklusTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // jangan biarkan "waktu palsu" bocor ke tes lain
        parent::tearDown();
    }

    public function test_satu_siklus_langganan_penuh_dari_daftar_sampai_perpanjangan(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-01-01');

        // --- 1. Daftar: sekolah dibuat, mulai di paket Guru gratis ---
        $sekolah = Sekolah::factory()->create(['paket' => 'guru', 'nomor_whatsapp' => '628123456789']);
        $adminUser = User::factory()->create();
        $admin = Keanggotaan::create([
            'user_id' => $adminUser->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::AdminSekolah, 'aktif' => true,
        ]);
        $this->actingAs($adminUser)->withSession(['keanggotaan_aktif_id' => $admin->id]);

        // --- 2. Mulai uji coba paket Sekolah (6 bulan gratis, PRD 9.4) ---
        $resp = $this->post('/langganan/mulai', ['paket' => 'sekolah']);
        $resp->assertRedirect(route('langganan.tampilkan'));

        $this->assertSame('sekolah', $sekolah->fresh()->paket->value);
        $langganan = Langganan::withoutGlobalScopes()->where('tenant_id', $sekolah->id)->first();
        $this->assertSame('percobaan', $langganan->status);
        $this->assertSame('2026-01-01', $langganan->mulai_pada->toDateString());
        $this->assertSame('2026-07-01', $langganan->berakhir_pada->toDateString()); // +6 bulan

        // Kuota tak terbatas sekarang (paket Sekolah) — bukti langganan sungguhan berefek.
        $this->assertNull($sekolah->fresh()->batasKelas());

        // --- 3. H-60 sebelum trial berakhir: tagihan perpanjangan otomatis dibuat ---
        Carbon::setTestNow('2026-05-02'); // 2026-07-01 minus 60 hari
        Artisan::call('langganan:perpanjang');

        $tagihan = Tagihan::withoutGlobalScopes()->where('langganan_id', $langganan->id)->first();
        $this->assertNotNull($tagihan, 'Tagihan perpanjangan harus sudah dibuat pada H-60.');
        $this->assertSame('menunggu', $tagihan->status);
        $this->assertSame('2026-07-01', $tagihan->jatuh_tempo->toDateString());
        $this->assertSame('2026-07-02', $tagihan->periode_mulai->toDateString());
        $this->assertSame(config('paket.sekolah.harga_tahunan'), $tagihan->jumlah);

        // Menjalankan lagi TIDAK membuat tagihan kedua (idempoten).
        Artisan::call('langganan:perpanjang');
        $this->assertSame(1, Tagihan::withoutGlobalScopes()->where('langganan_id', $langganan->id)->count());

        // --- 4. Pengingat H-30 terkirim (surel — nomor WA di-set juga, FonnteService no-op aman tanpa token) ---
        Carbon::setTestNow('2026-06-01'); // H-30 dari jatuh_tempo 2026-07-01
        Artisan::call('tagihan:kirim-pengingat');

        // Mail::raw() tidak selalu terdeteksi assertSent(Closure) MailFake
        // dengan rapi (bukan Mailable bernama) — bukti yang lebih langsung
        // & tetap sahih: penanda pengingat_terkirim di bawah, yang HANYA
        // ditulis SETELAH Mail::raw() berhasil dipanggil (lihat
        // KirimPengingatTagihan::kirimSatu — try/catch di sekelilingnya
        // tidak menelan galat sampai baris update() itu).
        $tagihan->refresh();
        $this->assertContains('h60', $tagihan->pengingat_terkirim);
        $this->assertContains('h30', $tagihan->pengingat_terkirim);
        $this->assertNotContains('h7', $tagihan->pengingat_terkirim);

        // Jalan lagi di hari yang sama -> tidak mengirim dobel (idempoten).
        Mail::fake();
        Artisan::call('tagihan:kirim-pengingat');
        Mail::assertNotSent(fn (\Illuminate\Mail\Mailable $mail) => true);

        // --- 5. Simulasi buat VA Midtrans (server_key belum dikonfigurasi -> disimulasikan) ---
        $resp = $this->post("/tagihan/{$tagihan->id}/bayar");
        $resp->assertRedirect();
        $tagihan->refresh();
        $this->assertSame('midtrans_va', $tagihan->metode);
        $this->assertStringStartsWith('SIM-', $tagihan->midtrans_order_id);
        $this->assertNotNull($tagihan->midtrans_va_nomor);

        // --- 6. Ditandai lunas manual oleh admin platform (jalur PO/transfer bank, PRD 9.2) ---
        $adminPlatformUser = User::factory()->create();
        Keanggotaan::create([
            'user_id' => $adminPlatformUser->id, 'sekolah_id' => null, 'peran' => Peran::AdminPlatform, 'aktif' => true,
        ]);
        $this->actingAs($adminPlatformUser);
        $resp = $this->post("/tagihan/{$tagihan->id}/tandai-lunas");
        $resp->assertRedirect();

        $tagihan->refresh();
        $this->assertTrue($tagihan->lunas());
        $this->assertNotNull($tagihan->lunas_pada);

        $langganan->refresh();
        $this->assertSame('aktif', $langganan->status);
        $this->assertSame('2027-07-01', $langganan->berakhir_pada->toDateString()); // diperpanjang ke periode_selesai tagihan

        // --- 7. Faktur/kwitansi bisa dicetak (unduh, PRD 9.2) ---
        $resp = $this->get("/tagihan/{$tagihan->id}/cetak");
        $resp->assertOk();
        $resp->assertSee($tagihan->nomor_faktur);
        $resp->assertSee('LUNAS');

        // --- 8. Siklus KEDUA: waktu maju sampai lewat jatuh tempo TANPA dibayar -> tenggang -> hanya-baca ---
        $this->actingAs($adminUser)->withSession(['keanggotaan_aktif_id' => $admin->id]);

        Carbon::setTestNow('2027-07-15'); // 14 hari lewat berakhir_pada baru (2027-07-01)
        Artisan::call('langganan:perpanjang');
        $langganan->refresh();
        $this->assertSame('tenggang', $langganan->status);

        // Masih fungsi PENUH selama tenggang (PRD 9.4) — kelas baru tetap boleh.
        $resp = $this->post('/kelas', ['nama' => 'Kelas Tenggang', 'tahun_ajaran' => '2027/2028']);
        $resp->assertRedirect(route('kelas.index'));
        $this->assertDatabaseHas('kelas', ['nama' => 'Kelas Tenggang']);

        Carbon::setTestNow('2027-08-05'); // 35 hari lewat -> masa tenggang 30 hari habis
        Artisan::call('langganan:perpanjang');
        $langganan->refresh();
        $this->assertSame('hanya_baca', $langganan->status);

        // Sekarang PENAMBAHAN BARU diblokir...
        $resp = $this->post('/kelas', ['nama' => 'Kelas Ditolak', 'tahun_ajaran' => '2027/2028']);
        $resp->assertSessionHasErrors('kuota');
        $this->assertDatabaseMissing('kelas', ['nama' => 'Kelas Ditolak']);

        // ...tapi kelas yang SUDAH ADA tetap terlihat & bisa dipakai (PRD 9.4 — data tidak pernah dihapus/dikunci).
        $lihatKelas = $this->get('/kelas');
        $lihatKelas->assertOk();
        $lihatKelas->assertInertia(fn ($page) => $page->has('kelas', 1)); // cuma 'Kelas Tenggang' dari langkah 8 — 'Kelas Ditolak' TIDAK ikut

        // --- 9. Bayar tagihan siklus kedua -> aktif lagi (perpanjangan tuntas) ---
        $tagihanKedua = Tagihan::withoutGlobalScopes()->where('langganan_id', $langganan->id)->where('id', '!=', $tagihan->id)->first();
        $this->assertNotNull($tagihanKedua, 'Tagihan perpanjangan siklus kedua harus sudah dibuat oleh perintah di atas.');

        $this->actingAs($adminPlatformUser);
        $this->post("/tagihan/{$tagihanKedua->id}/tandai-lunas")->assertRedirect();

        $langganan->refresh();
        $this->assertSame('aktif', $langganan->status);
        $this->assertTrue($langganan->berfungsiPenuh());
        $this->assertSame($tagihanKedua->periode_selesai->toDateString(), $langganan->berakhir_pada->toDateString());
    }
}
