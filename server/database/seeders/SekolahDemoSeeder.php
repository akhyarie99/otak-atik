<?php

namespace Database\Seeders;

use App\Enums\Peran;
use App\Models\Karya;
use App\Models\Kelas;
use App\Models\Keanggotaan;
use App\Models\MisiPercobaan;
use App\Models\Sekolah;
use App\Models\Tugas;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\KodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Sekolah contoh untuk pengembangan lokal — BUKAN sekolah sungguhan
// (belum ada yang berlangganan, lihat rencana-build.md Fase 7). Tujuannya
// supaya seluruh alur (fase 1-7) bisa dicoba langsung: masuk sebagai
// guru/admin sekolah/orang tua/siswa, lihat kelas, tugas, papan progres,
// dan galeri berisi karya sungguhan — tanpa harus mengisi semuanya
// manual lewat antarmuka satu per satu.
//
// Jalankan lewat: php artisan migrate:fresh --seed
class SekolahDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sekolah = Sekolah::create([
            'nama' => 'SD Contoh Otak-atik',
            'kode_sekolah' => 'DEMO001',
            'paket' => 'sekolah', // supaya kelas/siswa tak terbatas & tak pernah hanya-baca (belum ada Langganan)
        ]);

        $guru = $this->buatAkunSurel($sekolah->id, Peran::Guru, 'Bu Guru Demo', 'guru@demo.test');
        $this->buatAkunSurel($sekolah->id, Peran::AdminSekolah, 'Admin Sekolah Demo', 'admin.sekolah@demo.test');
        $this->buatAkunSurel(null, Peran::AdminPlatform, 'Admin Platform Demo', 'admin.platform@demo.test');

        app(TenantContext::class)->aktifkan($sekolah->id);

        $kelas4A = Kelas::create(['nama' => '4A', 'tahun_ajaran' => '2026/2027', 'kode_kelas' => 'DEMO4A']);
        $kelas5B = Kelas::create(['nama' => '5B', 'tahun_ajaran' => '2026/2027', 'kode_kelas' => 'DEMO5B']);

        $siswa4A = $this->buatSiswaKelas($kelas4A, ['Adit', 'Bunga', 'Citra', 'Dewa', 'Eka']);
        $siswa5B = $this->buatSiswaKelas($kelas5B, ['Fajar', 'Gita', 'Hana', 'Indra', 'Joko']);

        Tugas::create([
            'kelas_id' => $kelas4A->id, 'diberikan_oleh' => $guru->id,
            'misi_id' => 'tk2-01-maju', 'tingkat' => 2, 'tenggat' => now()->addWeek(),
        ]);
        Tugas::create([
            'kelas_id' => $kelas5B->id, 'diberikan_oleh' => $guru->id,
            'misi_id' => 'tk2-03-pena', 'tingkat' => 2, 'tenggat' => now()->addWeek(),
        ]);

        foreach ($siswa4A as $s) {
            MisiPercobaan::create(['keanggotaan_id' => $s->id, 'misi_id' => 'tk2-01-maju', 'lulus' => true]);
        }
        foreach (array_slice($siswa4A, 0, 2) as $s) {
            MisiPercobaan::create(['keanggotaan_id' => $s->id, 'misi_id' => 'tk2-02-putar', 'lulus' => false]);
        }

        $programKotak = ['program' => [
            ['t' => 'ulangi', 'n' => 4, 'id' => 'u1', 'isi' => [
                ['t' => 'maju', 'n' => 50, 'id' => 'm1'],
                ['t' => 'putar', 'n' => 90, 'id' => 'p1'],
            ]],
            ['t' => 'katakan', 'teks' => 'Kotak selesai!', 'n' => 1, 'id' => 'k1'],
        ]];

        $karyaAsal = Karya::create([
            'keanggotaan_id' => $siswa4A[0]->id,
            'judul' => 'Kotak ajaib',
            'project_json' => $programKotak,
            'client_updated_at' => now(),
            'status_publikasi' => 'sekolah',
            'dipublikasikan_pada' => now(),
        ]);

        Karya::create([
            'keanggotaan_id' => $siswa4A[1]->id,
            'judul' => 'Kotak ajaib (remix)',
            'project_json' => $programKotak,
            'client_updated_at' => now(),
            'status_publikasi' => 'kelas',
            'dipublikasikan_pada' => now(),
            'remix_dari_karya_id' => $karyaAsal->id,
        ]);

        Karya::create([
            'keanggotaan_id' => $siswa4A[2]->id,
            'judul' => 'Percobaan pertama',
            'project_json' => ['program' => [['t' => 'maju', 'n' => 20, 'id' => 'm1']]],
            'client_updated_at' => now(),
            'status_publikasi' => 'privat',
        ]);

        // Orang tua ditautkan langsung ke siswa pertama — mirip hasil
        // akhir alur undangan (UndanganController::terima), tapi tanpa
        // perlu membuka tautan token secara manual.
        $userOrangTua = User::create([
            'name' => 'Orang Tua Demo', 'email' => 'ortu@demo.test',
            'email_verified_at' => now(), 'password' => Hash::make('password'),
        ]);
        Keanggotaan::create([
            'user_id' => $userOrangTua->id, 'sekolah_id' => $sekolah->id,
            'peran' => Peran::OrangTua, 'aktif' => true,
            'anak_keanggotaan_id' => $siswa4A[0]->id,
        ]);

        app(TenantContext::class)->bersihkan();

        $this->cetakRingkasan($siswa4A, $siswa5B);
    }

    private function buatAkunSurel(?int $sekolahId, Peran $peran, string $nama, string $email): Keanggotaan
    {
        $user = User::create([
            'name' => $nama, 'email' => $email,
            'email_verified_at' => now(), 'password' => Hash::make('password'),
        ]);

        return Keanggotaan::create([
            'user_id' => $user->id, 'sekolah_id' => $sekolahId, 'peran' => $peran, 'aktif' => true,
        ]);
    }

    /** @return array<int, Keanggotaan> berisi PIN di properti sementara pin_polos untuk dicetak */
    private function buatSiswaKelas(Kelas $kelas, array $namaNama): array
    {
        $hasil = [];
        foreach ($namaNama as $nama) {
            $pin = KodeGenerator::pin();
            $user = User::create([
                'name' => $nama, 'nama_panggilan' => $nama,
                'pin_hash' => Hash::make($pin, ['rounds' => 4]),
            ]);
            $keanggotaan = Keanggotaan::create([
                'user_id' => $user->id, 'sekolah_id' => $kelas->tenant_id,
                'peran' => Peran::Siswa, 'aktif' => true,
            ]);
            $kelas->anggota()->attach($keanggotaan->id);

            $keanggotaan->pin_polos = $pin; // dipakai cetakRingkasan() saja, bukan kolom sungguhan
            $hasil[] = $keanggotaan;
        }

        return $hasil;
    }

    private function cetakRingkasan(array $siswa4A, array $siswa5B): void
    {
        $garis = str_repeat('-', 60);
        $this->command?->info($garis);
        $this->command?->info('SEKOLAH CONTOH DIBUAT — kata sandi semua akun surel: password');
        $this->command?->info($garis);
        $this->command?->info('Guru           : guru@demo.test');
        $this->command?->info('Admin sekolah  : admin.sekolah@demo.test');
        $this->command?->info('Admin platform : admin.platform@demo.test');
        $this->command?->info('Orang tua      : ortu@demo.test (anak: '.$siswa4A[0]->user->nama_panggilan.')');
        $this->command?->info($garis);
        $this->command?->info('Masuk siswa lewat /masuk-siswa — kode kelas + nama panggilan + PIN:');
        foreach (['4A (kode DEMO4A)' => $siswa4A, '5B (kode DEMO5B)' => $siswa5B] as $label => $daftar) {
            $this->command?->info("  Kelas {$label}:");
            foreach ($daftar as $s) {
                $this->command?->info('    '.str_pad($s->user->nama_panggilan, 8).' PIN '.$s->pin_polos);
            }
        }
        $this->command?->info($garis);
    }
}
