<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Kelas;
use App\Models\Keanggotaan;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// "Selesai bila" milestone 4.2: impor 300 siswa dari berkas contoh
// berhasil dengan pratinjau dan pemetaan kolom.
class ImportSiswaTest extends TestCase
{
    use RefreshDatabase;

    private function loginSebagaiGuru(): array
    {
        // Paket "sekolah" (siswa tak terbatas) — 300 siswa jelas di luar
        // jangkauan paket Guru gratis (35 siswa, milestone 7.1/PRD 9.3)
        // dengan sengaja; ini simulasi sekolah sungguhan yang membeli.
        $sekolah = Sekolah::factory()->create(['paket' => 'sekolah']);
        $guru = User::factory()->create();
        $keanggotaan = Keanggotaan::create([
            'user_id' => $guru->id,
            'sekolah_id' => $sekolah->id,
            'peran' => Peran::Guru,
            'aktif' => true,
        ]);

        $this->actingAs($guru);
        $this->withSession(['keanggotaan_aktif_id' => $keanggotaan->id]);

        $kelas = Kelas::withoutGlobalScopes()->create([
            'tenant_id' => $sekolah->id,
            'nama' => '5A',
            'tahun_ajaran' => '2026/2027',
            'kode_kelas' => 'TESTAB',
        ]);

        return [$sekolah, $kelas];
    }

    private function berkasContoh300(): UploadedFile
    {
        $baris = ["No,Nama Siswa,Catatan\n"];
        for ($i = 1; $i <= 300; $i++) {
            $baris[] = "{$i},Siswa Contoh {$i},-\n";
        }

        Storage::fake('local'); // tidak dipakai untuk fixture ini, hanya isolasi disk lain
        $path = tempnam(sys_get_temp_dir(), 'siswa').'.csv';
        file_put_contents($path, implode('', $baris));

        return new UploadedFile($path, 'daftar-siswa.csv', 'text/csv', null, true);
    }

    public function test_pratinjau_menampilkan_judul_kolom_dan_beberapa_baris_sebelum_impor(): void
    {
        [, $kelas] = $this->loginSebagaiGuru();
        $berkas = $this->berkasContoh300();

        $respons = $this->post("/kelas/{$kelas->id}/impor/pratinjau", ['berkas' => $berkas]);

        $respons->assertOk();
        $respons->assertJsonStructure(['token', 'header', 'pratinjau', 'total_baris']);
        $this->assertSame(['No', 'Nama Siswa', 'Catatan'], $respons->json('header'));
        $this->assertSame(300, $respons->json('total_baris'));
        $this->assertLessThanOrEqual(10, count($respons->json('pratinjau')));

        // Belum ada satu pun akun siswa dibuat pada tahap pratinjau.
        $this->assertDatabaseCount('users', 1); // cuma guru
    }

    public function test_impor_300_siswa_berhasil_dengan_pemetaan_kolom_yang_benar(): void
    {
        [$sekolah, $kelas] = $this->loginSebagaiGuru();
        $berkas = $this->berkasContoh300();

        $pratinjau = $this->post("/kelas/{$kelas->id}/impor/pratinjau", ['berkas' => $berkas])->json();

        // Kolom "Nama Siswa" ada di indeks 1 — buktikan pemetaan kolom
        // dipakai sungguhan, bukan asal ambil kolom pertama.
        $indeksNama = array_search('Nama Siswa', $pratinjau['header']);
        $this->assertSame(1, $indeksNama);

        $respons = $this->post("/kelas/{$kelas->id}/impor/proses", [
            'token' => $pratinjau['token'],
            'kolom_nama' => $indeksNama,
        ]);

        $respons->assertOk();
        $respons->assertJson(['jumlah_dibuat' => 300, 'kode_kelas' => 'TESTAB']);

        $this->assertDatabaseCount('users', 301); // 300 siswa + 1 guru
        $this->assertDatabaseCount('keanggotaan', 301);
        $this->assertDatabaseCount('kelas_anggota', 300);

        // Semua siswa milik SEKOLAH yang benar (tenant_id konsisten).
        $jumlahDiSekolah = Keanggotaan::where('sekolah_id', $sekolah->id)
            ->where('peran', Peran::Siswa->value)
            ->count();
        $this->assertSame(300, $jumlahDiSekolah);

        // Nama panggilan terisi benar dari kolom yang dipetakan (bukan kolom "No").
        $siswaPertama = User::where('nama_panggilan', 'Siswa Contoh 1')->first();
        $this->assertNotNull($siswaPertama);
        $this->assertNotNull($siswaPertama->pin_hash);

        // Setiap siswa dapat PIN 4 digit unik dari respons (dipakai kartu cetak).
        $pinList = array_column($respons->json('siswa'), 'pin');
        $this->assertCount(300, $pinList);
        foreach ($pinList as $pin) {
            $this->assertMatchesRegularExpression('/^\d{4}$/', $pin);
        }
    }

    public function test_siswa_hasil_impor_bisa_langsung_masuk_pakai_kode_kelas_nama_dan_pin(): void
    {
        [, $kelas] = $this->loginSebagaiGuru();
        $berkas = $this->berkasContoh300();
        $pratinjau = $this->post("/kelas/{$kelas->id}/impor/pratinjau", ['berkas' => $berkas])->json();
        $hasil = $this->post("/kelas/{$kelas->id}/impor/proses", [
            'token' => $pratinjau['token'],
            'kolom_nama' => 1,
        ])->json();

        $siswa = $hasil['siswa'][0];

        // Sesi guru dilepas dulu supaya ini benar-benar pengujian login siswa dari nol.
        $this->app['auth']->guard('web')->logout();
        $this->flushSession();

        $respons = $this->post('/masuk-siswa', [
            'kode_kelas' => 'TESTAB',
            'nama_panggilan' => $siswa['nama_panggilan'],
            'pin' => $siswa['pin'],
        ]);

        $respons->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }
}
