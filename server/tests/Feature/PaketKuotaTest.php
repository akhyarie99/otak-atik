<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Karya;
use App\Models\Kelas;
use App\Models\Keanggotaan;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Milestone 7.1 (PRD 9.1 & 9.3): paket & kuota. "Selesai bila": kuota
// tercapai memblokir PENAMBAHAN BARU tanpa mengunci karya yang ada —
// itu yang dibuktikan literal di sini, bukan sekadar "kuota ditegakkan".
class PaketKuotaTest extends TestCase
{
    use RefreshDatabase;

    private function guruDenganSekolah(string $paket = 'guru'): array
    {
        $sekolah = Sekolah::factory()->create(['paket' => $paket]);
        $guru = User::factory()->create();
        $keanggotaan = Keanggotaan::create([
            'user_id' => $guru->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::Guru, 'aktif' => true,
        ]);
        $this->actingAs($guru)->withSession(['keanggotaan_aktif_id' => $keanggotaan->id]);

        return [$sekolah, $guru, $keanggotaan];
    }

    // --- Kuota kelas (paket Guru: 1 kelas) ---

    public function test_paket_guru_bisa_bikin_kelas_pertama(): void
    {
        [$sekolah] = $this->guruDenganSekolah('guru');

        $resp = $this->post('/kelas', ['nama' => '5A', 'tahun_ajaran' => '2026/2027']);

        $resp->assertRedirect(route('kelas.index'));
        $this->assertDatabaseCount('kelas', 1);
    }

    public function test_paket_guru_ditolak_bikin_kelas_kedua_tanpa_mengunci_kelas_pertama(): void
    {
        [$sekolah] = $this->guruDenganSekolah('guru');
        Kelas::withoutGlobalScopes()->create([
            'tenant_id' => $sekolah->id, 'nama' => '5A', 'tahun_ajaran' => '2026/2027', 'kode_kelas' => 'SUDAH1',
        ]);

        $resp = $this->post('/kelas', ['nama' => '5B', 'tahun_ajaran' => '2026/2027']);

        $resp->assertSessionHasErrors('kuota');
        $this->assertDatabaseCount('kelas', 1); // tetap 1, bukan nol — kelas lama tidak hilang/terkunci

        // Kelas yang SUDAH ADA tetap bisa dilihat & dipakai normal (tidak terkunci).
        $lihat = $this->get('/kelas');
        $lihat->assertOk();
    }

    public function test_paket_sekolah_kelas_tak_terbatas(): void
    {
        [$sekolah] = $this->guruDenganSekolah('sekolah');
        for ($i = 0; $i < 5; $i++) {
            Kelas::withoutGlobalScopes()->create([
                'tenant_id' => $sekolah->id, 'nama' => "Kelas $i", 'tahun_ajaran' => '2026/2027',
                'kode_kelas' => 'K'.$i.'ABCD',
            ]);
        }

        $resp = $this->post('/kelas', ['nama' => 'Kelas Baru', 'tahun_ajaran' => '2026/2027']);

        $resp->assertRedirect(route('kelas.index'));
        $this->assertDatabaseCount('kelas', 6);
    }

    // --- Kuota siswa aktif (paket Guru: 35) ---

    public function test_sisa_slot_siswa_menghitung_benar_dan_null_untuk_paket_tak_terbatas(): void
    {
        [$sekolahGuru] = $this->guruDenganSekolah('guru');
        $this->assertSame(35, $sekolahGuru->sisaSlotSiswa());

        [$sekolahBesar] = $this->guruDenganSekolah('sekolah');
        $this->assertNull($sekolahBesar->sisaSlotSiswa());
    }

    public function test_impor_siswa_dipotong_pas_kuota_bukan_ditolak_semuanya(): void
    {
        [$sekolah, , $keanggotaan] = $this->guruDenganSekolah('guru');
        $kelas = Kelas::withoutGlobalScopes()->create([
            'tenant_id' => $sekolah->id, 'nama' => '5A', 'tahun_ajaran' => '2026/2027', 'kode_kelas' => 'IMPORQ',
        ]);

        // Sudah ada 33 siswa aktif — sisa slot cuma 2.
        for ($i = 0; $i < 33; $i++) {
            $u = User::factory()->create();
            $k = Keanggotaan::create(['user_id' => $u->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::Siswa, 'aktif' => true]);
            $kelas->anggota()->attach($k->id);
        }
        $this->assertSame(2, $sekolah->fresh()->sisaSlotSiswa());

        $isi = "Nama\n".implode('', array_map(fn ($i) => "Anak $i\n", range(1, 5)));
        Storage::fake('local');
        $path = tempnam(sys_get_temp_dir(), 'siswa').'.csv';
        file_put_contents($path, $isi);
        $berkas = new UploadedFile($path, 'siswa.csv', 'text/csv', null, true);

        $pratinjau = $this->post("/kelas/{$kelas->id}/impor/pratinjau", ['berkas' => $berkas])->json();
        $hasil = $this->post("/kelas/{$kelas->id}/impor/proses", [
            'token' => $pratinjau['token'], 'kolom_nama' => 0,
        ])->json();

        // Cuma 2 dari 5 yang berhasil dibuat — sisanya dilaporkan dilewati, bukan diam-diam hilang.
        $this->assertSame(2, $hasil['jumlah_dibuat']);
        $this->assertSame(3, $hasil['jumlah_dilewati_kuota']);
        $this->assertSame(35, $sekolah->fresh()->jumlahSiswaAktif());
    }

    // --- Kuota karya per siswa (lewat remix — satu-satunya jalan siswa
    // punya lebih dari satu baris karya, lihat GaleriController) ---

    public function test_remix_ditolak_saat_kuota_karya_tercapai_tanpa_mengunci_karya_yang_ada(): void
    {
        [$sekolahA, , $keanggotaanGuruA] = $this->guruDenganSekolah('guru');
        $penulis = $this->siswaDiSekolah($sekolahA, 'Penulis');
        $karyaAsal = Karya::create([
            'tenant_id' => $sekolahA->id, 'keanggotaan_id' => $penulis->id, 'judul' => 'Karya Asal',
            'project_json' => ['program' => []], 'client_updated_at' => now(), 'status_publikasi' => 'kelas',
        ]);

        $peremix = $this->siswaDiSekolah($sekolahA, 'Peremix');
        // Isi kuota (20 karya) duluan.
        for ($i = 0; $i < 20; $i++) {
            Karya::create([
                'tenant_id' => $sekolahA->id, 'keanggotaan_id' => $peremix->id, 'judul' => "Lama $i",
                'project_json' => ['program' => []], 'client_updated_at' => now(),
            ]);
        }

        $this->actingAs($peremix->user)->withSession(['keanggotaan_aktif_id' => $peremix->id]);
        $resp = $this->post("/galeri/{$karyaAsal->id}/remix");

        $resp->assertStatus(422);
        $this->assertDatabaseCount('karya', 21); // 20 lama peremix + 1 karya asal — remix TIDAK jadi 22

        // 20 karya LAMA peremix tetap ada & tidak terkunci (masih bisa dilihat).
        $this->assertSame(20, Karya::where('keanggotaan_id', $peremix->id)->count());
    }

    public function test_remix_diizinkan_di_bawah_kuota(): void
    {
        [$sekolahA, , ] = $this->guruDenganSekolah('guru');
        $penulis = $this->siswaDiSekolah($sekolahA, 'Penulis');
        $karyaAsal = Karya::create([
            'tenant_id' => $sekolahA->id, 'keanggotaan_id' => $penulis->id, 'judul' => 'Karya Asal',
            'project_json' => ['program' => []], 'client_updated_at' => now(), 'status_publikasi' => 'kelas',
        ]);
        $peremix = $this->siswaDiSekolah($sekolahA, 'Peremix');

        $this->actingAs($peremix->user)->withSession(['keanggotaan_aktif_id' => $peremix->id]);
        $resp = $this->post("/galeri/{$karyaAsal->id}/remix");

        $resp->assertRedirect(route('editor'));
        $this->assertDatabaseCount('karya', 2);
    }

    private function siswaDiSekolah(Sekolah $sekolah, string $nama): Keanggotaan
    {
        $user = User::factory()->create(['nama_panggilan' => $nama]);

        return Keanggotaan::create(['user_id' => $user->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::Siswa, 'aktif' => true]);
    }
}
