<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Kelas;
use App\Models\Keanggotaan;
use App\Models\MisiPercobaan;
use App\Models\Sekolah;
use App\Models\Undangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Milestone 5.3 — PRD 6.8: undangan orang tua, akun orang tua, lihat
// progres anak sendiri, izin publikasi keluar sekolah. "Selesai bila":
// orang tua hanya bisa melihat anaknya; uji akses ke siswa lain gagal.
//
// Menulis suite ini juga membongkar celah nyata: pemeriksaan otorisasi
// lama di beberapa controller (KelasController, ProgresController,
// TugasController, ImportSiswaController, sebagian GaleriController)
// cuma memeriksa "peran !== Siswa" — sebelum milestone ini itu cukup
// karena satu-satunya peran non-siswa adalah guru. Begitu peran
// orang_tua ada, pemeriksaan itu jadi salah: orang tua ikut lolos ke
// halaman staf. Diperbaiki jadi Peran::bolehKelolaSekolah() (daftar
// positif guru/admin_sekolah), diuji di bawah.
class OrangTuaTest extends TestCase
{
    use RefreshDatabase;

    private function sekolahDenganKelas(): array
    {
        $sekolah = Sekolah::factory()->create();
        $guru = User::factory()->create();
        $keanggotaanGuru = Keanggotaan::create([
            'user_id' => $guru->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::Guru, 'aktif' => true,
        ]);
        $kelas = Kelas::withoutGlobalScopes()->create([
            'tenant_id' => $sekolah->id, 'nama' => '5A', 'tahun_ajaran' => '2026/2027',
            'kode_kelas' => strtoupper(fake()->unique()->bothify('??????')),
        ]);

        return [$sekolah, $guru, $keanggotaanGuru, $kelas];
    }

    private function siswaDiKelas(Sekolah $sekolah, Kelas $kelas, string $nama): Keanggotaan
    {
        $user = User::factory()->create(['nama_panggilan' => $nama]);
        $keanggotaan = Keanggotaan::create([
            'user_id' => $user->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::Siswa, 'aktif' => true,
        ]);
        $kelas->anggota()->attach($keanggotaan->id);

        return $keanggotaan;
    }

    private function loginSebagai(User $user, Keanggotaan $keanggotaan): void
    {
        $this->actingAs($user)->withSession(['keanggotaan_aktif_id' => $keanggotaan->id]);
    }

    // --- Undangan ---

    public function test_guru_bisa_membuat_undangan_untuk_siswa_di_sekolahnya(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->sekolahDenganKelas();
        $siswa = $this->siswaDiKelas($sekolah, $kelas, 'Ani');

        $this->loginSebagai($guru, $keanggotaanGuru);
        $resp = $this->post("/siswa/{$siswa->id}/undang-orang-tua", ['nomor_whatsapp' => '08123456789']);

        $resp->assertRedirect();
        $this->assertDatabaseCount('undangan', 1);
        $undangan = Undangan::first();
        $this->assertSame($siswa->id, $undangan->siswa_keanggotaan_id);
        $this->assertSame($keanggotaanGuru->id, $undangan->dibuat_oleh);
        $this->assertTrue($undangan->berlaku());
    }

    public function test_guru_tidak_bisa_mengundang_untuk_siswa_sekolah_lain(): void
    {
        [$sekolahA, $guruA, $keanggotaanGuruA] = $this->sekolahDenganKelas();
        [$sekolahB, , , $kelasB] = $this->sekolahDenganKelas();
        $siswaB = $this->siswaDiKelas($sekolahB, $kelasB, 'Budi');

        $this->loginSebagai($guruA, $keanggotaanGuruA);
        $resp = $this->post("/siswa/{$siswaB->id}/undang-orang-tua", []);

        $resp->assertNotFound();
        $this->assertDatabaseCount('undangan', 0);
    }

    public function test_siswa_tidak_bisa_membuat_undangan(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $siswa = $this->siswaDiKelas($sekolah, $kelas, 'Ani');

        $this->loginSebagai($siswa->user, $siswa);
        $resp = $this->post("/siswa/{$siswa->id}/undang-orang-tua", []);

        $resp->assertForbidden();
    }

    public function test_undangan_yang_valid_bisa_diterima_dan_membuat_keanggotaan_orang_tua(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->sekolahDenganKelas();
        $siswa = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $undangan = Undangan::create([
            'tenant_id' => $sekolah->id, 'siswa_keanggotaan_id' => $siswa->id, 'dibuat_oleh' => $keanggotaanGuru->id,
            'token' => 'token-uji-123', 'kadaluarsa_pada' => now()->addDays(7),
        ]);

        $orangTua = User::factory()->create();
        $this->actingAs($orangTua);
        $resp = $this->get('/undangan/token-uji-123/terima');

        $resp->assertRedirect(route('dashboard'));
        $keanggotaanBaru = Keanggotaan::where('user_id', $orangTua->id)->first();
        $this->assertNotNull($keanggotaanBaru);
        $this->assertSame(Peran::OrangTua, $keanggotaanBaru->peran);
        $this->assertSame($siswa->id, $keanggotaanBaru->anak_keanggotaan_id);
        $this->assertNotNull($undangan->fresh()->dipakai_pada);
    }

    public function test_pengunjung_belum_login_diarahkan_masuk_lalu_kembali_ke_undangan(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->sekolahDenganKelas();
        $siswa = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        Undangan::create([
            'tenant_id' => $sekolah->id, 'siswa_keanggotaan_id' => $siswa->id, 'dibuat_oleh' => $keanggotaanGuru->id,
            'token' => 'token-tanpa-login', 'kadaluarsa_pada' => now()->addDays(7),
        ]);

        $resp = $this->get('/undangan/token-tanpa-login/terima');

        $resp->assertRedirect(route('login'));
        $this->assertSame(route('undangan.terima', 'token-tanpa-login'), session('url.intended'));
    }

    public function test_undangan_yang_sudah_dipakai_ditolak(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->sekolahDenganKelas();
        $siswa = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        Undangan::create([
            'tenant_id' => $sekolah->id, 'siswa_keanggotaan_id' => $siswa->id, 'dibuat_oleh' => $keanggotaanGuru->id,
            'token' => 'token-terpakai', 'kadaluarsa_pada' => now()->addDays(7), 'dipakai_pada' => now(),
        ]);

        $this->actingAs(User::factory()->create());
        $resp = $this->get('/undangan/token-terpakai/terima');

        $resp->assertStatus(410);
        $this->assertDatabaseCount('keanggotaan', 2); // cuma guru + siswa, tidak nambah
    }

    public function test_undangan_kedaluwarsa_ditolak(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->sekolahDenganKelas();
        $siswa = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        Undangan::create([
            'tenant_id' => $sekolah->id, 'siswa_keanggotaan_id' => $siswa->id, 'dibuat_oleh' => $keanggotaanGuru->id,
            'token' => 'token-lawas', 'kadaluarsa_pada' => now()->subDay(),
        ]);

        $this->actingAs(User::factory()->create());
        $resp = $this->get('/undangan/token-lawas/terima');

        $resp->assertStatus(410);
    }

    // --- Progres orang tua: hanya anaknya sendiri ---

    private function orangTuaDenganAnak(Sekolah $sekolah, Keanggotaan $anak): array
    {
        $userOrangTua = User::factory()->create();
        $keanggotaanOrangTua = Keanggotaan::create([
            'user_id' => $userOrangTua->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::OrangTua,
            'aktif' => true, 'anak_keanggotaan_id' => $anak->id,
        ]);

        return [$userOrangTua, $keanggotaanOrangTua];
    }

    public function test_orang_tua_melihat_progres_anaknya_sendiri(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $anak = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        MisiPercobaan::create(['tenant_id' => $sekolah->id, 'keanggotaan_id' => $anak->id, 'misi_id' => 'tk2-01-maju', 'lulus' => true]);

        [$userOrangTua, $keanggotaanOrangTua] = $this->orangTuaDenganAnak($sekolah, $anak);
        $this->loginSebagai($userOrangTua, $keanggotaanOrangTua);

        $resp = $this->get('/orang-tua/progres');

        $resp->assertOk();
        $props = $resp->viewData('page')['props'];
        $this->assertSame('Ani', $props['anak']['nama_panggilan']);
        $misi = collect($props['misi'])->firstWhere('misi_id', 'tk2-01-maju');
        $this->assertTrue($misi['lulus']);
    }

    public function test_orang_tua_tidak_bisa_melihat_progres_siswa_lain_lewat_halaman_kelas(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $anak = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $this->siswaDiKelas($sekolah, $kelas, 'Budi'); // teman sekelas anaknya

        [$userOrangTua, $keanggotaanOrangTua] = $this->orangTuaDenganAnak($sekolah, $anak);
        $this->loginSebagai($userOrangTua, $keanggotaanOrangTua);

        // Halaman progres SATU KELAS (semua siswa) — bukan cuma anaknya
        // — harus tertutup buat orang tua, walau satu sekolah/kelas.
        $resp = $this->get("/kelas/{$kelas->id}/progres");
        $resp->assertForbidden();
    }

    public function test_orang_tua_tidak_bisa_membuka_kelola_kelas_tugas_atau_galeri(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $anak = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        [$userOrangTua, $keanggotaanOrangTua] = $this->orangTuaDenganAnak($sekolah, $anak);
        $this->loginSebagai($userOrangTua, $keanggotaanOrangTua);

        $this->get('/kelas')->assertForbidden();
        $this->get('/tugas')->assertForbidden();
        $this->get('/galeri')->assertForbidden();
    }

    public function test_orang_tua_dengan_data_anak_tidak_konsisten_tetap_ditolak_404(): void
    {
        // Skenario pertahanan berlapis: seandainya anak_keanggotaan_id
        // sampai menunjuk ke siswa sekolah LAIN (harusnya tidak pernah
        // terjadi lewat alur undangan normal), pengecekan tangan di
        // OrangTuaController tetap menutupnya — bukan cuma dipercaya
        // dari alur pembuatannya saja (pola sama seperti aturan tetap #5).
        [$sekolahA, , , $kelasA] = $this->sekolahDenganKelas();
        $anakSekolahA = $this->siswaDiKelas($sekolahA, $kelasA, 'Ani');

        [$sekolahB] = $this->sekolahDenganKelas();
        $userOrangTua = User::factory()->create();
        $keanggotaanOrangTua = Keanggotaan::create([
            'user_id' => $userOrangTua->id, 'sekolah_id' => $sekolahB->id, 'peran' => Peran::OrangTua,
            'aktif' => true, 'anak_keanggotaan_id' => $anakSekolahA->id,
        ]);
        $this->loginSebagai($userOrangTua, $keanggotaanOrangTua);

        $resp = $this->get('/orang-tua/progres');
        $resp->assertNotFound();
    }

    // --- Izin publikasi keluar sekolah ---

    public function test_orang_tua_bisa_mengubah_izin_publikasi_anaknya(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $anak = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        [$userOrangTua, $keanggotaanOrangTua] = $this->orangTuaDenganAnak($sekolah, $anak);
        $this->loginSebagai($userOrangTua, $keanggotaanOrangTua);

        $this->assertFalse($anak->fresh()->izin_publikasi_luar_sekolah);
        $resp = $this->post('/orang-tua/izin-publikasi', ['izin' => true]);

        $resp->assertRedirect();
        $this->assertTrue($anak->fresh()->izin_publikasi_luar_sekolah);
    }

    public function test_siswa_tidak_bisa_mengubah_izin_publikasinya_sendiri(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $anak = $this->siswaDiKelas($sekolah, $kelas, 'Ani');

        $this->loginSebagai($anak->user, $anak);
        $resp = $this->post('/orang-tua/izin-publikasi', ['izin' => true]);

        $resp->assertForbidden();
    }
}
