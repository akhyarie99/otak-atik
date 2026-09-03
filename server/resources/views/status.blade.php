<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Status layanan — Otak-atik</title>
<style>
  body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: #232B4D; max-width: 560px; margin: 60px auto; padding: 0 20px; }
  h1 { font-family: 'Baloo 2', sans-serif; font-size: 24px; }
  .lencana { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 999px; font-weight: 700; font-size: 15px; margin: 16px 0 28px; }
  .lencana.baik { background: #DCFCE7; color: #166534; }
  .lencana.buruk { background: #FEE2E2; color: #991B1B; }
  .titik { width: 10px; height: 10px; border-radius: 50%; background: currentColor; }
  table { width: 100%; border-collapse: collapse; }
  td, th { padding: 10px 4px; border-bottom: 1px solid #EDF0F7; text-align: left; font-size: 14px; }
</style>
</head>
<body>
  <h1>Status layanan Otak-atik</h1>

  <span class="lencana {{ $operasional ? 'baik' : 'buruk' }}">
    <span class="titik"></span>
    {{ $operasional ? 'Semua sistem berjalan normal' : 'Ada gangguan' }}
  </span>

  <table>
    <tr>
      <th>Basis data</th>
      <td>
        @if($database['baik'])
          Terhubung ({{ $database['ms'] }} ms)
        @else
          {{ $database['pesan'] }}
        @endif
      </td>
    </tr>
    <tr>
      <th>Cadangan terakhir</th>
      <td>
        @if($cadanganTerakhir)
          {{ \Illuminate\Support\Carbon::createFromTimestamp($cadanganTerakhir['waktu'])->translatedFormat('d F Y, H:i') }} WIB
        @else
          Belum ada cadangan tercatat
        @endif
      </td>
    </tr>
  </table>

  <p style="margin-top:32px;font-size:13px;color:#3A4470;">
    Halaman ini diperiksa langsung setiap kali dimuat — bukan angka tetap.
  </p>
</body>
</html>
