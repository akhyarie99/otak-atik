// Generator kode JavaScript dari AST — milestone 1.4.
// Ditampilkan di panel "Kode JavaScript" untuk dibaca anak (baca-saja
// sampai tingkat 3; dua arah baru di tingkat 4). Sengaja memakai sintaks
// JS asli (for/while/if) supaya jadi jembatan nyata ke tingkat berikutnya
// — bukan kode yang benar-benar dieksekusi (eksekusi selalu lewat
// interpreter AST, aturan tetap #2).

// Simpul NILAI (angka) tingkat 3 — milestone 6.2. Angka polos dirender
// apa adanya (kompatibel dengan seluruh kode tingkat 2 yang sudah ada).
function kodeNilai(n) {
  if (typeof n === 'number') return String(n)
  if (!n) return '0'
  switch (n.t) {
    case 'var_nilai':
      return n.nama
    case 'posisi_x':
      return 'panggung.sprite.x'
    case 'posisi_y':
      return 'panggung.sprite.y'
    case 'daftar_panjang':
      return `${n.nama}.length`
    case 'acak':
      return `acak(${kodeNilai(n.min)}, ${kodeNilai(n.maks)})`
    case 'op_arit':
      return `(${kodeNilai(n.kiri)} ${n.op} ${kodeNilai(n.kanan)})`
    default:
      return '0'
  }
}

function kodeKondisi(n) {
  if (!n) return 'false'
  switch (n.t) {
    case 'menyentuh_warna':
      return `panggung.menyentuhWarna("${n.w}")`
    case 'menyentuh_sprite':
      return 'panggung.menyentuhSprite()'
    case 'tombol_ditekan':
      return `panggung.apakahTombolDitekan(${JSON.stringify(n.tombol)})`
    case 'op_banding':
      return `(${kodeNilai(n.kiri)} ${{ sama: '===', kurang: '<', lebih: '>' }[n.op] || '=='} ${kodeNilai(n.kanan)})`
    case 'op_logika':
      return `(${kodeKondisi(n.kiri)} ${n.op === 'dan' ? '&&' : '||'} ${kodeKondisi(n.kanan)})`
    case 'op_bukan':
      return `!(${kodeKondisi(n.nilai)})`
    default:
      return 'false'
  }
}

export function kodeUrutan(list, tab) {
  return (list || []).map((n) => kodeSatu(n, tab)).join('')
}

function kodeSatu(n, tab) {
  const t = '  '.repeat(tab)
  switch (n.t) {
    case 'maju':
      return `${t}panggung.maju(${n.n});\n`
    case 'putar':
      return `${t}panggung.putar(${n.n});\n`
    case 'arahkan':
      return `${t}panggung.arahkanKe(${n.n});\n`
    case 'pergi':
      return `${t}panggung.pergiKe(${n.x}, ${n.y});\n`
    case 'pantul':
      return `${t}panggung.pantulTepi();\n`
    case 'pena':
      return `${t}panggung.penaTurun(${n.turun});\n`
    case 'warna':
      return `${t}panggung.warnaPena("${n.w}");\n`
    case 'hapus':
      return `${t}panggung.hapusGambar();\n`
    case 'katakan':
      return `${t}await panggung.katakan("${n.teks}", ${n.n});\n`
    case 'ucapkan':
      return `${t}await panggung.ucapkan("${n.teks}");\n`
    case 'tunggu':
      return `${t}await tunggu(${n.n});\n`
    case 'ulangi':
      return `${t}for (let i = 0; i < ${n.n}; i++) {\n${kodeUrutan(n.isi, tab + 1)}${t}}\n`
    case 'selamanya':
      return `${t}while (true) {\n${kodeUrutan(n.isi, tab + 1)}${t}}\n`
    case 'jika':
      return `${t}if (${kodeKondisi(n.kondisi)}) {\n${kodeUrutan(n.isi, tab + 1)}${t}}\n`
    case 'jika_lain':
      return (
        `${t}if (${kodeKondisi(n.kondisi)}) {\n${kodeUrutan(n.isi, tab + 1)}${t}} else {\n` +
        `${kodeUrutan(n.isiLain, tab + 1)}${t}}\n`
      )
    case 'atur_tampil':
      return `${t}panggung.aturTampil(${n.tampak});\n`
    case 'ukuran':
      return `${t}panggung.gantiUkuran(${n.n});\n`
    case 'kostum':
      return `${t}panggung.gantiKostum("${n.nama}");\n`
    case 'bunyi':
      return `${t}panggung.mainkanBunyi("${n.nama}");\n`
    case 'var_atur':
      return `${t}${n.nama} = ${n.n};\n`
    case 'var_ubah':
      return `${t}${n.nama} += ${n.n};\n`
    case 'var_tampil':
      return `${t}panggung.tampilkanSkor("${n.nama}", ${n.nama});\n`
    case 'ulangi_sampai':
      return `${t}while (!(${kodeKondisi(n.kondisi)})) {\n${kodeUrutan(n.isi, tab + 1)}${t}}\n`
    case 'daftar_buat':
      return `${t}let ${n.nama} = [];\n`
    case 'daftar_tambah':
      return `${t}${n.nama}.push(${kodeNilai(n.nilai)});\n`
    case 'daftar_tampil':
      return `${t}panggung.tampilkanSkor("${n.nama}", ${n.nama}.join(", "));\n`
    case 'deklarasi_fungsi':
      return (n.daftar || []).map((f) => `${t}function ${f.nama}() {\n${kodeUrutan(f.isi, tab + 1)}${t}}\n`).join('')
    case 'fungsi_panggil':
      return `${t}${n.nama}();\n`
    default:
      return ''
  }
}

export function kodeProgram(ast) {
  const isi = kodeUrutan(ast, 1)
  return (
    '// Kode ini dibuat otomatis dari blok yang kamu susun.\n' +
    '// Nanti di tingkat SMP, panel ini bisa kamu baca sambil menyusun blok.\n\n' +
    `async function ketikaBenderaDiklik() {\n${isi || '  // belum ada apa-apa di sini\n'}}\n`
  )
}
