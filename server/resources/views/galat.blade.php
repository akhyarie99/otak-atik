<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pemantauan galat — Otak-atik</title>
<style>
  body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: #232B4D; max-width: 900px; margin: 40px auto; padding: 0 20px; }
  h1 { font-family: 'Baloo 2', sans-serif; font-size: 22px; }
  .kosong { color: #3A4470; padding: 24px; background: #EDF0F7; border-radius: 12px; }
  .entri { border: 1px solid #EDF0F7; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; }
  .kepala { display: flex; gap: 10px; align-items: center; margin-bottom: 8px; }
  .level { font-weight: 700; font-size: 12px; padding: 3px 10px; border-radius: 999px; }
  .level-ERROR { background: #FEE2E2; color: #991B1B; }
  .level-CRITICAL, .level-ALERT, .level-EMERGENCY { background: #7A1F1F; color: #fff; }
  .waktu { font-size: 13px; color: #3A4470; }
  pre { white-space: pre-wrap; word-break: break-word; font-family: 'JetBrains Mono', monospace; font-size: 12.5px; margin: 0; max-height: 260px; overflow-y: auto; }
</style>
</head>
<body>
  <h1>Pemantauan galat</h1>
  <p style="color:#3A4470;font-size:14px;">
    {{ count($entri) }} entri ERROR/CRITICAL terbaru dari storage/logs/laravel.log.
    Halaman ini hanya untuk admin platform.
  </p>

  @if(empty($entri))
    <div class="kosong">Tidak ada entri galat tercatat.</div>
  @else
    @foreach($entri as $e)
      <div class="entri">
        <div class="kepala">
          <span class="level level-{{ $e['level'] }}">{{ $e['level'] }}</span>
          <span class="waktu">{{ $e['waktu'] }}</span>
        </div>
        <pre>{{ $e['pesan'] }}</pre>
      </div>
    @endforeach
  @endif
</body>
</html>
