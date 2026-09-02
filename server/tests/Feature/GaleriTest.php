<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Karya;
use App\Models\Kelas;
use App\Models\Keanggotaan;
use App\Models\Reaksi;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Milestone 5.1 — PRD 6.6: galeri kelas & sekolah, mainkan karya teman,
// reaksi terpilih (tanpa komentar bebas), sembunyikan oleh guru.
// "Selesai bila": karya bisa dimainkan teman sekelas dalam satu ketukan
// dari daftar — dibuktikan langsung di sini lewat rute /galeri/{karya}/mainkan.
class GaleriTest extends TestCase
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

    private function karyaMilik(Sekolah $sekolah, Keanggotaan $pemilik, string $judul, string $status = 'privat'): Karya
    {
        return Karya::create([
            'tenant_id' => $sekolah->id,
            'keanggotaan_id' => $pemilik->id,
            'judul' => $judul,
            'project_json' => ['program' => []],
            'client_updated_at' => now(),
            'status_publikasi' => $status,
            'dipublikasikan_pada' => $status !== 'privat' ? now() : null,
        ]);
    }

    private function loginSebagai(User $user, Keanggotaan $keanggotaan): void
    {
        $this->actingAs($user)->withSession(['keanggotaan_aktif_id' => $keanggotaan->id]);
    }

    public function test_karya_yang_diterbitkan_bisa_dimainkan_teman_sekelas_satu_ketukan(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $teman = $this->siswaDiKelas($sekolah, $kelas, 'Budi');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Bola Memantul', 'kelas');

        $this->loginSebagai($teman->user, $teman);
        $resp = $this->get("/galeri/{$karya->id}/mainkan");

        $resp->assertOk();
        $resp->assertSee('OtakAtik', false);
        $resp->assertSee('Bola Memantul');
    }

    public function test_karya_privat_tidak_bisa_dimainkan_orang_lain(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $teman = $this->siswaDiKelas($sekolah, $kelas, 'Budi');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Rahasia', 'privat');

        $this->loginSebagai($teman->user, $teman);
        $resp = $this->get("/galeri/{$karya->id}/mainkan");

        $resp->assertNotFound();
    }

    public function test_pemilik_selalu_bisa_memainkan_karyanya_sendiri_walau_privat(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Rahasia', 'privat');

        $this->loginSebagai($penulis->user, $penulis);
        $resp = $this->get("/galeri/{$karya->id}/mainkan");

        $resp->assertOk();
    }

    public function test_siswa_sekolah_lain_tidak_bisa_memainkan_karya_lewat_id_langsung(): void
    {
        [$sekolahA, , , $kelasA] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolahA, $kelasA, 'Ani');
        $karya = $this->karyaMilik($sekolahA, $penulis, 'Punya Sekolah A', 'sekolah');

        [$sekolahB, , , $kelasB] = $this->sekolahDenganKelas();
        $siswaB = $this->siswaDiKelas($sekolahB, $kelasB, 'Citra');

        $this->loginSebagai($siswaB->user, $siswaB);
        $resp = $this->get("/galeri/{$karya->id}/mainkan");

        // TenantScope pada route model binding Karya membuat karya
        // sekolah lain 404 lewat ID langsung sekalipun (aturan tetap #5).
        $resp->assertNotFound();
    }

    // --- Milestone 5.2 — remix (PRD 6.6): "Anak bisa remix: menyalin
    // karya teman untuk dimodifikasi, dengan atribusi otomatis ke
    // pembuat asli." "Selesai bila": rantai remix terlacak sampai
    // karya asal, sekalipun sudah remix-dari-remix beberapa lapis.

    public function test_remix_membuat_karya_baru_milik_peremix_dengan_atribusi_ke_pembuat_asli(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulisAsli = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $asal = $this->karyaMilik($sekolah, $penulisAsli, 'Kereta Warna-Warni', 'kelas');

        $peremix = $this->siswaDiKelas($sekolah, $kelas, 'Budi');
        $this->loginSebagai($peremix->user, $peremix);
        $resp = $this->post("/galeri/{$asal->id}/remix");

        $resp->assertRedirect(route('editor'));
        $this->assertDatabaseCount('karya', 2);

        $remix = Karya::where('keanggotaan_id', $peremix->id)->first();
        $this->assertNotNull($remix);
        $this->assertSame($asal->id, $remix->remix_dari_karya_id);
        $this->assertSame($asal->project_json, $remix->project_json);
        $this->assertSame('privat', $remix->status_publikasi);

        // Karya asal Ani tidak berubah sama sekali oleh aksi remix Budi.
        $this->assertSame('kelas', $asal->fresh()->status_publikasi);
        $this->assertSame($penulisAsli->id, $asal->fresh()->keanggotaan_id);
    }

    public function test_rantai_remix_terlacak_sampai_karya_asal_walau_berlapis(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $ani = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $asal = $this->karyaMilik($sekolah, $ani, 'Karya Asal', 'kelas');

        $budi = $this->siswaDiKelas($sekolah, $kelas, 'Budi');
        $this->loginSebagai($budi->user, $budi);
        $this->post("/galeri/{$asal->id}/remix")->assertRedirect();
        $remixBudi = Karya::where('keanggotaan_id', $budi->id)->first();
        $remixBudi->update(['status_publikasi' => 'kelas']); // Budi menerbitkan remix-nya juga

        $citra = $this->siswaDiKelas($sekolah, $kelas, 'Citra');
        $this->loginSebagai($citra->user, $citra);
        $this->post("/galeri/{$remixBudi->id}/remix")->assertRedirect();
        $remixCitra = Karya::where('keanggotaan_id', $citra->id)->first();

        // Remix-dari-remix: induk langsung Citra adalah karya Budi,
        // tapi rantainya tetap terlacak sampai karya asal milik Ani.
        $rantai = $remixCitra->rantaiRemix();
        $this->assertSame([$asal->id, $remixBudi->id, $remixCitra->id], $rantai->pluck('id')->all());
        $this->assertSame($asal->id, $rantai->first()->id);
        $this->assertSame('Ani', $rantai->first()->keanggotaan->user->nama_panggilan);
    }

    public function test_remix_karya_privat_ditolak(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Rahasia', 'privat');

        $peremix = $this->siswaDiKelas($sekolah, $kelas, 'Budi');
        $this->loginSebagai($peremix->user, $peremix);
        $resp = $this->post("/galeri/{$karya->id}/remix");

        $resp->assertNotFound();
        $this->assertDatabaseCount('karya', 1);
    }

    public function test_remix_lewat_id_karya_sekolah_lain_ditolak(): void
    {
        [$sekolahA, , , $kelasA] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolahA, $kelasA, 'Ani');
        $karya = $this->karyaMilik($sekolahA, $penulis, 'Punya Sekolah A', 'sekolah');

        [$sekolahB, , , $kelasB] = $this->sekolahDenganKelas();
        $siswaB = $this->siswaDiKelas($sekolahB, $kelasB, 'Citra');
        $this->loginSebagai($siswaB->user, $siswaB);

        $resp = $this->post("/galeri/{$karya->id}/remix");

        $resp->assertNotFound();
    }

    public function test_galeri_kelas_hanya_berisi_karya_teman_sekelas_yang_terbit(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelasA] = $this->sekolahDenganKelas();
        $kelasB = Kelas::withoutGlobalScopes()->create([
            'tenant_id' => $sekolah->id, 'nama' => '5B', 'tahun_ajaran' => '2026/2027',
            'kode_kelas' => strtoupper(fake()->unique()->bothify('??????')),
        ]);

        $temanSekelas = $this->siswaDiKelas($sekolah, $kelasA, 'Ani');
        $karyaTeman = $this->karyaMilik($sekolah, $temanSekelas, 'Karya Ani', 'kelas');

        $anakKelasLain = $this->siswaDiKelas($sekolah, $kelasB, 'Dedi');
        $this->karyaMilik($sekolah, $anakKelasLain, 'Karya Dedi', 'kelas');

        $penonton = $this->siswaDiKelas($sekolah, $kelasA, 'Budi');
        $this->loginSebagai($penonton->user, $penonton);

        $resp = $this->get('/galeri');
        $resp->assertOk();
        $judulTampil = collect($resp->viewData('page')['props']['galeriKelas'])->pluck('judul');

        $this->assertTrue($judulTampil->contains('Karya Ani'));
        $this->assertFalse($judulTampil->contains('Karya Dedi'));
    }

    public function test_guru_melihat_karya_terbit_kelas_manapun_di_tenantnya_walau_bukan_anggota_kelas(): void
    {
        // Guru tidak terdaftar di pivot kelas_anggota (bukan "anggota
        // kelas" seperti siswa) — celah nyata yang ketahuan lewat uji
        // Playwright: tanpa penanganan ini, tombol "Sembunyikan" guru
        // tidak pernah muncul karena daftar galerinya selalu kosong.
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $this->karyaMilik($sekolah, $penulis, 'Karya Ani', 'kelas');

        $this->loginSebagai($guru, $keanggotaanGuru);
        $resp = $this->get('/galeri');

        $resp->assertOk();
        $judulTampil = collect($resp->viewData('page')['props']['galeriKelas'])->pluck('judul');
        $this->assertTrue($judulTampil->contains('Karya Ani'));
    }

    public function test_galeri_sekolah_hanya_menampilkan_status_sekolah(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $this->karyaMilik($sekolah, $penulis, 'Baru Kelas', 'kelas');
        $this->karyaMilik($sekolah, $penulis, 'Sudah Sekolah', 'sekolah');

        $penonton = $this->siswaDiKelas($sekolah, $kelas, 'Budi');
        $this->loginSebagai($penonton->user, $penonton);

        $resp = $this->get('/galeri');
        $judulTampil = collect($resp->viewData('page')['props']['galeriSekolah'])->pluck('judul');

        $this->assertFalse($judulTampil->contains('Baru Kelas'));
        $this->assertTrue($judulTampil->contains('Sudah Sekolah'));
    }

    public function test_siswa_bisa_menerbitkan_karyanya_sendiri_ke_kelas(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Karyaku', 'privat');

        $this->loginSebagai($penulis->user, $penulis);
        $resp = $this->post("/karya/{$karya->id}/terbitkan", ['status' => 'kelas']);

        $resp->assertRedirect();
        $this->assertSame('kelas', $karya->fresh()->status_publikasi);
        $this->assertNotNull($karya->fresh()->dipublikasikan_pada);
    }

    public function test_siswa_tidak_bisa_menerbitkan_karya_orang_lain(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Karyaku', 'privat');

        $lainnya = $this->siswaDiKelas($sekolah, $kelas, 'Budi');
        $this->loginSebagai($lainnya->user, $lainnya);
        $resp = $this->post("/karya/{$karya->id}/terbitkan", ['status' => 'kelas']);

        $resp->assertForbidden();
        $this->assertSame('privat', $karya->fresh()->status_publikasi);
    }

    public function test_hanya_guru_bisa_mempromosikan_ke_galeri_sekolah(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Karyaku', 'kelas');

        // Siswa sendiri tidak boleh mempromosikan langsung ke sekolah.
        $this->loginSebagai($penulis->user, $penulis);
        $this->post("/karya/{$karya->id}/promosikan-sekolah")->assertForbidden();
        $this->assertSame('kelas', $karya->fresh()->status_publikasi);

        // Guru boleh.
        $this->app['auth']->forgetGuards();
        $this->loginSebagai($guru, $keanggotaanGuru);
        $this->post("/karya/{$karya->id}/promosikan-sekolah")->assertRedirect();
        $this->assertSame('sekolah', $karya->fresh()->status_publikasi);
    }

    public function test_guru_bisa_menyembunyikan_karya_dan_karya_hilang_dari_galeri(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Karyaku', 'kelas');

        $this->loginSebagai($guru, $keanggotaanGuru);
        $this->post("/karya/{$karya->id}/sembunyikan")->assertRedirect();

        $this->assertTrue($karya->fresh()->disembunyikan_oleh_guru);
        $this->assertFalse($karya->fresh()->terlihat());

        // Menyembunyikan TIDAK mengubah isi karya (privasi anak: guru
        // cuma bisa menyembunyikan, tidak bisa mengubah).
        $this->assertSame('Karyaku', $karya->fresh()->judul);
        $this->assertSame(['program' => []], $karya->fresh()->project_json);
    }

    public function test_siswa_tidak_bisa_menyembunyikan_karya(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Karyaku', 'kelas');

        $this->loginSebagai($penulis->user, $penulis);
        $this->post("/karya/{$karya->id}/sembunyikan")->assertForbidden();
    }

    public function test_reaksi_terpilih_tersimpan_dan_reaksi_kedua_menggantikan_bukan_menumpuk(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Karyaku', 'kelas');
        $pemberi = $this->siswaDiKelas($sekolah, $kelas, 'Budi');

        $this->loginSebagai($pemberi->user, $pemberi);
        $this->post("/karya/{$karya->id}/reaksi", ['jenis' => 'suka'])->assertRedirect();
        $this->post("/karya/{$karya->id}/reaksi", ['jenis' => 'keren'])->assertRedirect();

        $this->assertDatabaseCount('reaksi', 1);
        $this->assertSame('keren', Reaksi::first()->jenis);
    }

    public function test_reaksi_dengan_jenis_bebas_ditolak(): void
    {
        [$sekolah, , , $kelas] = $this->sekolahDenganKelas();
        $penulis = $this->siswaDiKelas($sekolah, $kelas, 'Ani');
        $karya = $this->karyaMilik($sekolah, $penulis, 'Karyaku', 'kelas');
        $pemberi = $this->siswaDiKelas($sekolah, $kelas, 'Budi');

        $this->loginSebagai($pemberi->user, $pemberi);
        $resp = $this->post("/karya/{$karya->id}/reaksi", ['jenis' => 'ini komentar bebas yang panjang']);

        $resp->assertSessionHasErrors('jenis');
        $this->assertDatabaseCount('reaksi', 0);
    }
}
