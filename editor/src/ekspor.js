// Ekspor game mandiri — milestone 2.3.
// motor.min.js dibundel dari paket/runtime lewat pemutar/build.mjs — SATU
// mesin yang sama dipakai editor (aturan tetap #6), bukan salinan tangan.
// Jalankan `npm run build:pemutar` di akar repo setelah mengubah apa pun
// di paket/runtime supaya berkas ini ikut ter-update.
import motorJs from '../../pemutar/motor.min.js?raw'
import { unduhBerkas } from './berkas'

function escapeHtml(s) {
  return String(s).replace(/[<>&]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]))
}

export function buatHtmlEkspor(programAst, judul = 'Karyaku') {
  const judulAman = escapeHtml(judul)
  const programJson = JSON.stringify(programAst)
  return (
    '<!doctype html><html lang="id"><head><meta charset="utf-8">' +
    '<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">' +
    `<title>${judulAman}</title>` +
    '<style>*{margin:0;box-sizing:border-box}html,body{height:100%}' +
    'body{display:flex;align-items:center;justify-content:center;background:#EDF0F7}' +
    'canvas{background:#fff;border-radius:12px;box-shadow:0 2px 14px rgba(35,43,77,.15);max-width:96vw;max-height:96vh}</style>' +
    `</head><body><canvas id="p" width="480" height="360" aria-label="${judulAman}"></canvas>` +
    `<script>${motorJs}</script>` +
    `<script>var pg=new OtakAtik.Panggung(document.getElementById("p"));var it=new OtakAtik.Interpreter(pg);it.mulai(${programJson});` +
    // Kunci lanskap saat bermain di HP (PRD 6.2) — butuh gestur pengguna
    // di kebanyakan browser, jadi dicoba sekali saat sentuhan pertama;
    // gagal secara diam-diam kalau tidak didukung (mis. Safari iOS).
    'addEventListener("pointerdown",function u(){removeEventListener("pointerdown",u);' +
    'try{if(innerWidth<=768&&document.documentElement.requestFullscreen)' +
    'document.documentElement.requestFullscreen().catch(function(){});}catch(e){}' +
    'try{screen.orientation&&screen.orientation.lock&&screen.orientation.lock("landscape").catch(function(){});}catch(e){}});</script>' +
    '</body></html>'
  )
}

export function namaBerkasAman(judul) {
  return (
    String(judul || 'karyaku')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'karyaku'
  )
}

export function unduhEksporHtml(programAst, judul) {
  const html = buatHtmlEkspor(programAst, judul)
  const ukuran = unduhBerkas(`${namaBerkasAman(judul)}.html`, html, 'text/html')
  return { html, ukuran }
}
