<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $tagihan->lunas() ? 'Kwitansi' : 'Faktur' }} — {{ $tagihan->nomor_faktur }}</title>
<style>
  body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: #232B4D; max-width: 640px; margin: 40px auto; padding: 0 20px; }
  h1 { font-family: 'Baloo 2', sans-serif; font-size: 22px; margin-bottom: 4px; }
  .sub { color: #3A4470; font-size: 13px; margin-bottom: 24px; }
  table { width: 100%; border-collapse: collapse; margin-top: 16px; }
  td, th { padding: 8px 4px; border-bottom: 1px solid #EDF0F7; text-align: left; font-size: 14px; }
  .jumlah { font-size: 20px; font-weight: 700; }
  .status { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
  .status.lunas { background: #DCFCE7; color: #166534; }
  .status.menunggu { background: #FEF9C3; color: #854D0E; }
  .tombol-cetak { margin: 20px 0; }
  @media print { .tombol-cetak { display: none; } body { margin: 0; } }
</style>
</head>
<body>
  <button class="tombol-cetak" onclick="window.print()">🖨 Cetak halaman ini</button>

  <h1>Otak-atik</h1>
  <p class="sub">{{ $tagihan->lunas() ? 'Kwitansi pembayaran' : 'Faktur' }} · {{ $tagihan->nomor_faktur }}</p>

  <table>
    <tr><th>Sekolah</th><td>{{ $sekolah->nama }} ({{ $sekolah->kode_sekolah }})</td></tr>
    <tr><th>Paket</th><td>{{ ucfirst($tagihan->langganan->paket) }}</td></tr>
    <tr><th>Periode</th><td>{{ $tagihan->periode_mulai->translatedFormat('d F Y') }} — {{ $tagihan->periode_selesai->translatedFormat('d F Y') }}</td></tr>
    <tr><th>Jatuh tempo</th><td>{{ $tagihan->jatuh_tempo->translatedFormat('d F Y') }}</td></tr>
    <tr><th>Status</th><td><span class="status {{ $tagihan->status }}">{{ strtoupper($tagihan->status) }}</span></td></tr>
    @if($tagihan->lunas())
      <tr><th>Lunas pada</th><td>{{ $tagihan->lunas_pada->translatedFormat('d F Y, H:i') }} WIB</td></tr>
    @endif
    @if($tagihan->midtrans_va_nomor)
      <tr><th>Virtual account</th><td>{{ strtoupper($tagihan->midtrans_bank) }} — {{ $tagihan->midtrans_va_nomor }}</td></tr>
    @endif
  </table>

  <p class="jumlah" style="margin-top: 20px;">{{ $tagihan->jumlahFormat() }}</p>

  <p class="sub" style="margin-top: 40px;">
    Dokumen ini dibuat otomatis oleh sistem Otak-atik. Untuk pertanyaan penagihan, hubungi admin platform.
  </p>
</body>
</html>
