<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>{{ $judul }}</title>
<style>
  *{margin:0;box-sizing:border-box}
  html,body{height:100%}
  body{display:flex;align-items:center;justify-content:center;background:#EDF0F7}
  canvas{background:#fff;border-radius:12px;box-shadow:0 2px 14px rgba(35,43,77,.15);max-width:96vw;max-height:96vh}
</style>
</head>
<body>
<canvas id="p" width="480" height="360" aria-label="{{ $judul }}"></canvas>
<script>{!! $motorJs !!}</script>
<script>
  var pg = new OtakAtik.Panggung(document.getElementById('p'));
  var it = new OtakAtik.Interpreter(pg);
  it.mulai({!! $programJson !!});
  addEventListener('pointerdown', function u() {
    removeEventListener('pointerdown', u);
    try { if (innerWidth <= 768 && document.documentElement.requestFullscreen) document.documentElement.requestFullscreen().catch(function(){}); } catch (e) {}
    try { screen.orientation && screen.orientation.lock && screen.orientation.lock('landscape').catch(function(){}); } catch (e) {}
  });
</script>
</body>
</html>
