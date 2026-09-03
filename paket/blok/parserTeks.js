// Parser teks -> AST — milestone 6.3 (tingkat 4, SMA).
//
// rencana-build.md menandai sinkronisasi DUA ARAH blok<->teks sebagai
// "masalah paling berat di seluruh proyek", dan SECARA EKSPLISIT mengizinkan
// versi pertama SATU ARAH saja: "blok -> teks, lalu teks mengambil alih
// permanen untuk karya itu." Itu yang dibangun di sini — bukan penyimpangan
// diam-diam, ditulis di komentar ini dan di deskripsi PR.
//
// Kenapa tetap butuh PARSER sungguhan (bukan sekadar simpan teksnya apa
// adanya): aturan tetap #2 melarang eval/new Function terhadap kode hasil —
// eksekusi SELALU lewat interpreter AST. Begitu anak mengedit teks dan
// karyanya "mode teks", teks itu WAJIB diterjemahkan balik ke AST supaya
// bisa dijalankan sama seperti karya mode blok — parser inilah jembatannya.
//
// Tata bahasa yang dikenali adalah PERSIS subset yang dihasilkan
// kode.js:kodeProgram() (lihat file itu) — parser ini pasangan terbaliknya.
// Bukan JavaScript umum: cuma pemanggilan panggung.*, for/while/if/else,
// penugasan variabel, daftar (list), dan pemanggilan fungsi buatan sendiri.
// Baris di luar tata bahasa ini menghasilkan galat dengan nomor baris,
// bukan diam-diam diabaikan atau (yang paling penting) di-eval.

class GalatParser extends Error {
  constructor(pesan, baris) {
    super(`Baris ${baris}: ${pesan}`)
    this.baris = baris
  }
}

// --- Lexer ---

const KATA_KUNCI = new Set(['for', 'while', 'if', 'else', 'let', 'function', 'await', 'true', 'false', 'async'])

function tokenisasi(teks) {
  const token = []
  let i = 0
  let baris = 1
  const n = teks.length
  while (i < n) {
    const c = teks[i]
    if (c === '\n') {
      baris++
      i++
      continue
    }
    if (/\s/.test(c)) {
      i++
      continue
    }
    if (c === '/' && teks[i + 1] === '/') {
      while (i < n && teks[i] !== '\n') i++
      continue
    }
    if (/[0-9]/.test(c) || (c === '-' && /[0-9]/.test(teks[i + 1] || ''))) {
      let j = i + 1
      while (j < n && /[0-9.]/.test(teks[j])) j++
      token.push({ jenis: 'angka', nilai: parseFloat(teks.slice(i, j)), baris })
      i = j
      continue
    }
    if (c === '"' || c === "'") {
      let j = i + 1
      let s = ''
      while (j < n && teks[j] !== c) {
        if (teks[j] === '\\' && j + 1 < n) {
          s += teks[j + 1]
          j += 2
        } else {
          s += teks[j]
          j++
        }
      }
      token.push({ jenis: 'teks', nilai: s, baris })
      i = j + 1
      continue
    }
    if (/[A-Za-z_$]/.test(c)) {
      let j = i + 1
      while (j < n && /[A-Za-z0-9_$]/.test(teks[j])) j++
      const kata = teks.slice(i, j)
      token.push({ jenis: KATA_KUNCI.has(kata) ? kata : 'ident', nilai: kata, baris })
      i = j
      continue
    }
    const dua = teks.slice(i, i + 2)
    if (teks.slice(i, i + 3) === '===') {
      token.push({ jenis: 'op', nilai: '===', baris })
      i += 3
      continue
    }
    if (['&&', '||', '+='].includes(dua)) {
      token.push({ jenis: 'op', nilai: dua, baris })
      i += 2
      continue
    }
    if ('(){}[];,.=<>!+-*/'.includes(c)) {
      token.push({ jenis: 'op', nilai: c, baris })
      i++
      continue
    }
    throw new GalatParser(`karakter tidak dikenali "${c}"`, baris)
  }
  token.push({ jenis: 'eof', nilai: null, baris })
  return token
}

// --- Parser rekursif-turun ---

class Parser {
  constructor(token) {
    this.token = token
    this.pos = 0
    this._penghitungId = 0
  }

  intip(offset = 0) {
    return this.token[this.pos + offset]
  }

  ambil(jenis, nilai) {
    const t = this.intip()
    if (t.jenis !== jenis || (nilai !== undefined && t.nilai !== nilai)) {
      throw new GalatParser(`diharapkan "${nilai ?? jenis}", ketemu "${t.nilai ?? t.jenis}"`, t.baris)
    }
    this.pos++
    return t
  }

  cek(jenis, nilai) {
    const t = this.intip()
    return t.jenis === jenis && (nilai === undefined || t.nilai === nilai)
  }

  idBaru() {
    this._penghitungId++
    return `teks-${this._penghitungId}`
  }

  // Program := (statement | fungsiDef)*
  program() {
    const daftarFungsi = []
    const utama = []
    while (!this.cek('eof')) {
      if (this.cek('async') || this.cek('function')) {
        daftarFungsi.push(this.fungsiDef())
      } else {
        utama.push(this.statement())
      }
    }
    if (daftarFungsi.length === 0) return utama
    return [{ t: 'deklarasi_fungsi', daftar: daftarFungsi, id: 'deklarasi-fungsi' }, ...utama]
  }

  fungsiDef() {
    // "async" tidak seharusnya pernah muncul di sini — pembungkus terluar
    // "async function ketikaBenderaDiklik() {...}" sudah dilepas duluan
    // (lihat teksKeAst) sebelum badannya ditokenisasi. Diterima dengan
    // aman kalau toh muncul (mis. anak menyalin-tempel), diabaikan saja.
    if (this.cek('async')) this.ambil('async')
    this.ambil('function')
    const nama = this.ambil('ident').nilai
    this.ambil('op', '(')
    this.ambil('op', ')')
    this.ambil('op', '{')
    const isi = []
    while (!this.cek('op', '}')) isi.push(this.statement())
    this.ambil('op', '}')
    return { nama, isi }
  }

  blokKurung() {
    this.ambil('op', '{')
    const isi = []
    while (!this.cek('op', '}')) isi.push(this.statement())
    this.ambil('op', '}')
    return isi
  }

  statement() {
    const t = this.intip()
    if (t.jenis === 'for') return this.stFor()
    if (t.jenis === 'while') return this.stWhile()
    if (t.jenis === 'if') return this.stIf()
    if (t.jenis === 'let') return this.stLet()
    if (t.jenis === 'await') return this.stAwait()
    if (t.jenis === 'ident') return this.stIdent()
    throw new GalatParser(`baris ini tidak dikenali ("${t.nilai ?? t.jenis}")`, t.baris)
  }

  stFor() {
    this.ambil('for')
    this.ambil('op', '(')
    this.ambil('let')
    this.ambil('ident', 'i')
    this.ambil('op', '=')
    this.ambil('angka', 0)
    this.ambil('op', ';')
    this.ambil('ident', 'i')
    this.ambil('op', '<')
    const n = this.ambil('angka').nilai
    this.ambil('op', ';')
    this.ambil('ident', 'i')
    this.ambil('op', '+')
    this.ambil('op', '+')
    this.ambil('op', ')')
    const isi = this.blokKurung()
    return { t: 'ulangi', n: Math.floor(n), isi, id: this.idBaru() }
  }

  stWhile() {
    this.ambil('while')
    this.ambil('op', '(')
    if (this.cek('true')) {
      this.ambil('true')
      this.ambil('op', ')')
      const isi = this.blokKurung()
      return { t: 'selamanya', isi, id: this.idBaru() }
    }
    this.ambil('op', '!')
    this.ambil('op', '(')
    const kondisi = this.ekspresiKondisi()
    this.ambil('op', ')')
    this.ambil('op', ')')
    const isi = this.blokKurung()
    return { t: 'ulangi_sampai', kondisi, isi, id: this.idBaru() }
  }

  stIf() {
    this.ambil('if')
    this.ambil('op', '(')
    const kondisi = this.ekspresiKondisi()
    this.ambil('op', ')')
    const isi = this.blokKurung()
    if (this.cek('else')) {
      this.ambil('else')
      const isiLain = this.blokKurung()
      return { t: 'jika_lain', kondisi, isi, isiLain, id: this.idBaru() }
    }
    return { t: 'jika', kondisi, isi, id: this.idBaru() }
  }

  stLet() {
    this.ambil('let')
    const nama = this.ambil('ident').nilai
    this.ambil('op', '=')
    this.ambil('op', '[')
    this.ambil('op', ']')
    this.ambil('op', ';')
    return { t: 'daftar_buat', nama, id: this.idBaru() }
  }

  stAwait() {
    this.ambil('await')
    // await panggung.katakan("x", n) | await panggung.ucapkan("x") | await tunggu(n)
    if (this.cek('ident', 'tunggu')) {
      this.ambil('ident')
      this.ambil('op', '(')
      const n = this.ambil('angka').nilai
      this.ambil('op', ')')
      this.ambil('op', ';')
      return { t: 'tunggu', n, id: this.idBaru() }
    }
    this.ambil('ident', 'panggung')
    this.ambil('op', '.')
    const metode = this.ambil('ident').nilai
    this.ambil('op', '(')
    if (metode === 'katakan') {
      const teks = this.ambil('teks').nilai
      this.ambil('op', ',')
      const n = this.ambil('angka').nilai
      this.ambil('op', ')')
      this.ambil('op', ';')
      return { t: 'katakan', teks, n, id: this.idBaru() }
    }
    if (metode === 'ucapkan') {
      const teks = this.ambil('teks').nilai
      this.ambil('op', ')')
      this.ambil('op', ';')
      return { t: 'ucapkan', teks, id: this.idBaru() }
    }
    throw new GalatParser(`panggung.${metode}() dengan "await" tidak dikenali`, this.intip().baris)
  }

  // Baris yang diawali identifier: panggung.X(...);  NAMA = n;  NAMA += n;
  // NAMA.push(v);  NAMA();
  stIdent() {
    const idBaris = this.intip().baris
    const nama1 = this.ambil('ident').nilai

    if (nama1 === 'panggung') {
      this.ambil('op', '.')
      const metode = this.ambil('ident').nilai
      this.ambil('op', '(')
      const hasil = this.panggilPanggung(metode)
      this.ambil('op', ')')
      this.ambil('op', ';')
      return hasil
    }

    if (this.cek('op', '(')) {
      this.ambil('op', '(')
      this.ambil('op', ')')
      this.ambil('op', ';')
      return { t: 'fungsi_panggil', nama: nama1, id: this.idBaru() }
    }

    if (this.cek('op', '.')) {
      this.ambil('op', '.')
      const metode = this.ambil('ident').nilai
      if (metode !== 'push') throw new GalatParser(`hanya ".push(...)" yang dikenali untuk daftar`, idBaris)
      this.ambil('op', '(')
      const nilai = this.ekspresiNilai()
      this.ambil('op', ')')
      this.ambil('op', ';')
      return { t: 'daftar_tambah', nama: nama1, nilai, id: this.idBaru() }
    }

    if (this.cek('op', '+=')) {
      this.ambil('op', '+=')
      const n = this.ambil('angka').nilai
      this.ambil('op', ';')
      return { t: 'var_ubah', nama: nama1, n, id: this.idBaru() }
    }

    this.ambil('op', '=')
    const n = this.ambil('angka').nilai
    this.ambil('op', ';')
    return { t: 'var_atur', nama: nama1, n, id: this.idBaru() }
  }

  panggilPanggung(metode) {
    const id = this.idBaru()
    switch (metode) {
      case 'maju':
        return { t: 'maju', n: this.ambilAngkaTunggal(), id }
      case 'putar':
        return { t: 'putar', n: this.ambilAngkaTunggal(), id }
      case 'arahkanKe':
        return { t: 'arahkan', n: this.ambilAngkaTunggal(), id }
      case 'pergiKe': {
        const x = this.ambil('angka').nilai
        this.ambil('op', ',')
        const y = this.ambil('angka').nilai
        return { t: 'pergi', x, y, id }
      }
      case 'pantulTepi':
        return { t: 'pantul', id }
      case 'penaTurun':
        return { t: 'pena', turun: this.ambilBoolean(), id }
      case 'warnaPena':
        return { t: 'warna', w: this.ambil('teks').nilai, id }
      case 'hapusGambar':
        return { t: 'hapus', id }
      case 'aturTampil':
        return { t: 'atur_tampil', tampak: this.ambilBoolean(), id }
      case 'gantiUkuran':
        return { t: 'ukuran', n: this.ambilAngkaTunggal(), id }
      case 'gantiKostum':
        return { t: 'kostum', nama: this.ambil('teks').nilai, id }
      case 'mainkanBunyi':
        return { t: 'bunyi', nama: this.ambil('teks').nilai, id }
      case 'tampilkanSkor': {
        const namaLabel = this.ambil('teks').nilai
        this.ambil('op', ',')
        // Bisa "panggung.tampilkanSkor("skor", skor)" (var_tampil) atau
        // "panggung.tampilkanSkor("buah", buah.join(", "))" (daftar_tampil).
        const namaVar = this.ambil('ident').nilai
        if (this.cek('op', '.')) {
          this.ambil('op', '.')
          this.ambil('ident', 'join')
          this.ambil('op', '(')
          this.ambil('teks')
          this.ambil('op', ')')
          return { t: 'daftar_tampil', nama: namaVar, id }
        }
        return { t: 'var_tampil', nama: namaVar, id }
      }
      default:
        throw new GalatParser(`panggung.${metode}() tidak dikenali`, this.intip().baris)
    }
  }

  ambilAngkaTunggal() {
    const n = this.ambil('angka').nilai
    return n
  }

  ambilBoolean() {
    if (this.cek('true')) {
      this.ambil('true')
      return true
    }
    this.ambil('false')
    return false
  }

  // --- Ekspresi NILAI (angka) & KONDISI (boolean) — tingkat 3/4 ---

  ekspresiNilaiDasar() {
    if (this.cek('angka')) return this.ambil('angka').nilai
    if (this.cek('op', '(')) {
      this.ambil('op', '(')
      const kiri = this.ekspresiNilai()
      const t = this.intip()
      if (t.jenis === 'op' && ['+', '-', '*', '/'].includes(t.nilai)) {
        this.ambil('op')
        const kanan = this.ekspresiNilai()
        this.ambil('op', ')')
        return { t: 'op_arit', op: t.nilai, kiri, kanan }
      }
      this.ambil('op', ')')
      return kiri
    }
    if (this.cek('ident', 'acak')) {
      this.ambil('ident')
      this.ambil('op', '(')
      const min = this.ekspresiNilai()
      this.ambil('op', ',')
      const maks = this.ekspresiNilai()
      this.ambil('op', ')')
      return { t: 'acak', min, maks }
    }
    if (this.cek('ident', 'panggung')) {
      this.ambil('ident')
      this.ambil('op', '.')
      this.ambil('ident', 'sprite')
      this.ambil('op', '.')
      const sumbu = this.ambil('ident').nilai
      return sumbu === 'x' ? { t: 'posisi_x' } : { t: 'posisi_y' }
    }
    const nama = this.ambil('ident').nilai
    if (this.cek('op', '.')) {
      this.ambil('op', '.')
      this.ambil('ident', 'length')
      return { t: 'daftar_panjang', nama }
    }
    return { t: 'var_nilai', nama }
  }

  ekspresiNilai() {
    return this.ekspresiNilaiDasar()
  }

  ekspresiKondisiDasar() {
    if (this.cek('op', '!')) {
      this.ambil('op', '!')
      this.ambil('op', '(')
      const nilai = this.ekspresiKondisi()
      this.ambil('op', ')')
      return { t: 'op_bukan', nilai }
    }
    if (this.cek('op', '(')) {
      this.ambil('op', '(')
      // Coba sebagai perbandingan/logika dulu: (NILAI OP NILAI) atau (KONDISI && / || KONDISI)
      const awal = this.pos
      try {
        const kiri = this.ekspresiKondisi()
        if (this.cek('op', '&&') || this.cek('op', '||')) {
          const op = this.ambil('op').nilai === '&&' ? 'dan' : 'atau'
          const kanan = this.ekspresiKondisi()
          this.ambil('op', ')')
          return { t: 'op_logika', op, kiri, kanan }
        }
        this.ambil('op', ')')
        return kiri
      } catch {
        this.pos = awal
      }
      const kiri = this.ekspresiNilai()
      const simbol = this.ambil('op').nilai
      const opMap = { '===': 'sama', '<': 'kurang', '>': 'lebih' }
      if (!opMap[simbol]) throw new GalatParser(`operator perbandingan "${simbol}" tidak dikenali`, this.intip().baris)
      const kanan = this.ekspresiNilai()
      this.ambil('op', ')')
      return { t: 'op_banding', op: opMap[simbol], kiri, kanan }
    }
    if (this.cek('ident', 'panggung')) {
      this.ambil('ident')
      this.ambil('op', '.')
      const metode = this.ambil('ident').nilai
      this.ambil('op', '(')
      if (metode === 'menyentuhWarna') {
        const w = this.ambil('teks').nilai
        this.ambil('op', ')')
        return { t: 'menyentuh_warna', w }
      }
      if (metode === 'menyentuhSprite') {
        this.ambil('op', ')')
        return { t: 'menyentuh_sprite' }
      }
      if (metode === 'apakahTombolDitekan') {
        const tombol = this.ambil('teks').nilai
        this.ambil('op', ')')
        return { t: 'tombol_ditekan', tombol }
      }
      throw new GalatParser(`sensor panggung.${metode}() tidak dikenali`, this.intip().baris)
    }
    throw new GalatParser('kondisi tidak dikenali', this.intip().baris)
  }

  ekspresiKondisi() {
    return this.ekspresiKondisiDasar()
  }
}

// Mengubah teks kode (persis tata bahasa kodeProgram()) menjadi AST program
// — dipakai App.vue saat anak mengedit teks di tingkat 4 lalu menjalankannya
// (bukan eval, lihat catatan di atas). Melempar GalatParser (pesan Bahasa
// Indonesia + nomor baris) kalau teksnya tidak dikenali, supaya editor bisa
// menunjuk baris yang salah — bukan membekukan atau menjalankan hal lain
// dari yang ditulis anak.
export function teksKeAst(teks) {
  // Bungkus "async function ketikaBenderaDiklik() { ... }" dari kodeProgram()
  // dilepas dulu: badan fungsi itulah program utamanya, bukan fungsi
  // buatan anak — lihat kode.js:kodeProgram().
  const dibungkus = /async\s+function\s+ketikaBenderaDiklik\s*\(\s*\)\s*\{([\s\S]*)\}\s*$/m.exec(teks.trim())
  const isiUtama = dibungkus ? dibungkus[1] : teks

  const token = tokenisasi(isiUtama)
  const parser = new Parser(token)
  return parser.program()
}

export { GalatParser }
