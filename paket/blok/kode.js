// Generator kode JavaScript dari AST — milestone 1.4.
// Ditampilkan di panel "Kode JavaScript" untuk dibaca anak (baca-saja
// sampai tingkat 3; dua arah baru di tingkat 4). Sengaja memakai sintaks
// JS asli (for/while/if) supaya jadi jembatan nyata ke tingkat berikutnya
// — bukan kode yang benar-benar dieksekusi (eksekusi selalu lewat
// interpreter AST, aturan tetap #2).

function kodeKondisi(n) {
  if (!n) return 'false'
  switch (n.t) {
    case 'menyentuh_warna':
      return `panggung.menyentuhWarna("${n.w}")`
    case 'menyentuh_sprite':
      return 'panggung.menyentuhSprite()'
    case 'tombol_ditekan':
      return `panggung.apakahTombolDitekan(${JSON.stringify(n.tombol)})`
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
