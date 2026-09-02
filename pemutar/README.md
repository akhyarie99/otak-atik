# pemutar

Template game mandiri hasil ekspor (< 15 KB). Memakai mesin dari `paket/runtime` — dilarang menyalin logika runtime ke sini (lihat aturan tetap #6 di `CLAUDE.md`).

`motor.min.js` dibundel otomatis dari `paket/runtime/index.js` lewat `build.mjs` (esbuild) — bukti teknis bahwa editor dan pemutar memakai satu mesin yang sama, bukan salinan tangan. Jalankan `npm run build:pemutar` di akar repo setelah mengubah apa pun di `paket/runtime`; `npm run dev` melakukannya otomatis lewat hook `predev`.

Editor menggabungkan `motor.min.js` + AST program + pembungkus HTML minimal jadi satu berkas lewat `editor/src/ekspor.js` saat tombol "Ekspor jadi game" ditekan.
