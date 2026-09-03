<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Karya;
use App\Models\Kelas;
use App\Models\Keanggotaan;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Milestone 7.3 — "selesai bila": pemulihan dari cadangan diuji NYATA,
// bukan diasumsikan. Di sini artinya benar-benar: isi database
// sungguhan (lewat model/Eloquent, bukan data buatan) -> jalankan
// cadangan:jalankan -> HAPUS datanya (simulasi bencana) -> jalankan
// cadangan:pulihkan -> buktikan data yang tadinya hilang KEMBALI PERSIS
// sama, baris demi baris, bukan cuma "tidak error".
class CadanganPemulihanTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_yang_dihapus_kembali_utuh_setelah_pulih_dari_cadangan(): void
    {
        Storage::fake('local');

        // --- Isi database dengan data nyata lewat alur aplikasi biasa ---
        $sekolah = Sekolah::factory()->create(['nama' => 'SD Sebelum Bencana']);
        $guru = User::factory()->create(['name' => 'Guru Asli']);
        $keanggotaanGuru = Keanggotaan::create([
            'user_id' => $guru->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::Guru, 'aktif' => true,
        ]);
        $kelas = Kelas::withoutGlobalScopes()->create([
            'tenant_id' => $sekolah->id, 'nama' => '5A Asli', 'tahun_ajaran' => '2026/2027', 'kode_kelas' => 'ASLI01',
        ]);
        $siswaUser = User::factory()->create(['nama_panggilan' => 'Budi Asli']);
        $siswa = Keanggotaan::create([
            'user_id' => $siswaUser->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::Siswa, 'aktif' => true,
        ]);
        $kelas->anggota()->attach($siswa->id);
        $karya = Karya::create([
            'tenant_id' => $sekolah->id, 'keanggotaan_id' => $siswa->id, 'judul' => 'Karya Sebelum Bencana',
            'project_json' => ['program' => [['t' => 'maju', 'n' => 42]]], 'client_updated_at' => now(),
        ]);

        // --- Cadangan diambil SEBELUM bencana ---
        Artisan::call('cadangan:jalankan');
        $daftarBerkas = Storage::disk('local')->files('backups');
        $this->assertCount(1, $daftarBerkas, 'Satu berkas cadangan harus dibuat.');
        $berkasCadangan = $daftarBerkas[0];

        $isiCadangan = json_decode(Storage::disk('local')->get($berkasCadangan), true);
        $this->assertArrayHasKey('sekolah', $isiCadangan);
        $this->assertArrayHasKey('karya', $isiCadangan);
        $this->assertNotEmpty($isiCadangan['karya']);

        // --- BENCANA: semua data dihapus (simulasi kerusakan/kesalahan operator) ---
        Karya::withoutGlobalScopes()->truncate();
        Keanggotaan::truncate();
        Kelas::withoutGlobalScopes()->truncate();
        Sekolah::truncate();
        User::truncate();

        $this->assertDatabaseCount('sekolah', 0);
        $this->assertDatabaseCount('karya', 0);
        $this->assertDatabaseCount('users', 0);

        // --- PEMULIHAN dari cadangan ---
        $kode = Artisan::call('cadangan:pulihkan', ['berkas' => $berkasCadangan, '--konfirmasi' => true]);
        $this->assertSame(0, $kode, 'Perintah pemulihan harus sukses (kode keluar 0).');

        // --- Data yang tadinya hilang HARUS kembali, persis sama ---
        $this->assertDatabaseCount('sekolah', 1);
        $this->assertDatabaseCount('users', 2); // guru + siswa
        $this->assertDatabaseCount('keanggotaan', 2);
        $this->assertDatabaseCount('kelas', 1);
        $this->assertDatabaseCount('karya', 1);

        $sekolahPulih = Sekolah::first();
        $this->assertSame('SD Sebelum Bencana', $sekolahPulih->nama);
        $this->assertSame($sekolah->id, $sekolahPulih->id); // ID juga persis sama, bukan re-generate baru

        $karyaPulih = Karya::withoutGlobalScopes()->first();
        $this->assertSame('Karya Sebelum Bencana', $karyaPulih->judul);
        $this->assertSame([['t' => 'maju', 'n' => 42]], $karyaPulih->project_json['program']);

        $kelasPulih = Kelas::withoutGlobalScopes()->first();
        $this->assertSame('ASLI01', $kelasPulih->kode_kelas);
        $this->assertSame(1, $kelasPulih->anggota()->count()); // relasi pivot ikut pulih juga
    }

    public function test_pemulihan_ditolak_kalau_nama_database_cadangan_beda_dari_yang_aktif(): void
    {
        Storage::fake('local');

        $isiPalsu = [
            '_meta' => ['database' => 'database-proyek-lain-sama-sekali', 'dibuat_pada' => now()->toIso8601String()],
            'sekolah' => [['id' => 1, 'nama' => 'Bukan Punya Kita', 'kode_sekolah' => 'X', 'created_at' => now(), 'updated_at' => now()]],
        ];
        Storage::disk('local')->put('backups/cadangan-asing.json', json_encode($isiPalsu));

        $kode = Artisan::call('cadangan:pulihkan', ['berkas' => 'backups/cadangan-asing.json', '--konfirmasi' => true]);

        $this->assertNotSame(0, $kode, 'Pemulihan dari cadangan database lain harus DITOLAK, bukan dijalankan.');
        $this->assertDatabaseCount('sekolah', 0); // tidak ada apa pun yang tertulis
    }

    public function test_pemulihan_tanpa_flag_konfirmasi_ditolak(): void
    {
        Storage::fake('local');
        Artisan::call('cadangan:jalankan');
        $berkas = Storage::disk('local')->files('backups')[0];

        $kode = Artisan::call('cadangan:pulihkan', ['berkas' => $berkas]); // tanpa --konfirmasi

        $this->assertNotSame(0, $kode);
    }
}
