<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>LKPD — {{ $misi['judul'] }}</title>
<style>
  body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: #232B4D; max-width: 720px; margin: 40px auto; padding: 0 20px; }
  h1 { font-family: 'Baloo 2', sans-serif; font-size: 22px; margin-bottom: 4px; }
  .sub { color: #3A4470; font-size: 13px; margin-bottom: 24px; }
  h2 { font-size: 15px; margin-top: 28px; border-bottom: 2px solid #EDF0F7; padding-bottom: 6px; }
  ol, ul { padding-left: 20px; }
  li { margin-bottom: 6px; }
  .lkpd-siswa { background: #F5F7FC; border: 1.5px dashed #D7DDEC; border-radius: 10px; padding: 16px; margin-top: 10px; }
  .lkpd-siswa .nama { border-bottom: 1px solid #232B4D; display: inline-block; min-width: 200px; }
  .kotak-jawab { border: 1px solid #D7DDEC; border-radius: 8px; height: 100px; margin-top: 10px; }
  .tombol-cetak { margin: 20px 0; }
  @media print { .tombol-cetak { display: none; } body { margin: 0; } }
</style>
</head>
<body>
  <button class="tombol-cetak" onclick="window.print()">🖨 Cetak halaman ini</button>

  <h1>{{ $misi['judul'] }}</h1>
  <p class="sub">Otak-atik · Tingkat 2 (SD kelas 4–6) · Bahan ajar guru</p>

  <h2>Tujuan pembelajaran</h2>
  <p>{{ $misi['tujuan'] }}</p>

  <h2>Langkah mengajar</h2>
  <ol>
    @foreach ($misi['langkah_mengajar'] as $langkah)
      <li>{{ $langkah }}</li>
    @endforeach
  </ol>

  <h2>Lembar Kerja Peserta Didik</h2>
  <div class="lkpd-siswa">
    <p>Nama: <span class="nama">&nbsp;</span> &nbsp; Kelas: <span class="nama">&nbsp;</span></p>
    <p>{{ $misi['lkpd'] }}</p>
    <div class="kotak-jawab"></div>
  </div>
</body>
</html>
