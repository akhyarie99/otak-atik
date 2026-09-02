// Konversi Blockly <-> "kartu" — milestone 3.1.
// Mode kartu menulis ke struktur yang SAMA PERSIS dengan mode kanvas (PRD
// 6.2): fungsi di sini murni mengubah bentuk data serialisasi Blockly
// (next/inputs bertingkat) menjadi pohon Kartu yang gampang dirender
// sebagai daftar vertikal, dan sebaliknya. Tidak ada informasi yang
// dibuang di kedua arah — field apa pun diteruskan apa adanya (opaque),
// termasuk field_variable yang berbentuk {id} di Blockly.
//
import { BLOK_PER_TIPE, daftarFieldBlok, punyaSoket } from './definisi.js'

// Bentuk Kartu:
//   { id, type, fields: {...}, do?: Kartu[], lain?: Kartu[], kondisi?: Kartu|null }
// `do`/`lain` ada kalau bloknya punya input_statement (ulangi/selamanya/
// jika/jika_lain). `kondisi` ada kalau bloknya punya input_value (jika/
// jika_lain) — nilainya satu Kartu (dari blok Kondisi) atau null.

function satuKeKartu(b) {
  const kartu = { id: b.id, type: b.type, fields: { ...(b.fields || {}) } }
  if (b.inputs?.DO) kartu.do = rantaiKeKartu(b.inputs.DO.block)
  if (b.inputs?.LAIN) kartu.lain = rantaiKeKartu(b.inputs.LAIN.block)
  if (b.inputs?.KONDISI) kartu.kondisi = b.inputs.KONDISI.block ? satuKeKartu(b.inputs.KONDISI.block) : null
  return kartu
}

function rantaiKeKartu(b) {
  const out = []
  let cur = b
  while (cur) {
    out.push(satuKeKartu(cur))
    cur = cur.next?.block || null
  }
  return out
}

// Blockly-serialized top block (mis. `blocks.blocks[0]`, blok bendera) ->
// larik Kartu (bendera + seluruh badan program lewat rantai .next).
export function blocklyKeKartu(topBlockJson) {
  return topBlockJson ? rantaiKeKartu(topBlockJson) : []
}

function kartuKeSatu(k) {
  const b = { type: k.type, id: k.id }
  // Sama seperti Blockly.serialization.workspaces.save(): field kosong
  // tidak ditulis sama sekali, bukan {} — supaya hasil pulang-pergi
  // byte-identik dengan yang dihasilkan Blockly sendiri.
  if (k.fields && Object.keys(k.fields).length > 0) b.fields = { ...k.fields }
  if (k.do) {
    const rantai = kartuArrayKeRantai(k.do)
    if (rantai) b.inputs = { ...(b.inputs || {}), DO: { block: rantai } }
  }
  if (k.lain) {
    const rantai = kartuArrayKeRantai(k.lain)
    if (rantai) b.inputs = { ...(b.inputs || {}), LAIN: { block: rantai } }
  }
  if (k.kondisi) {
    b.inputs = { ...(b.inputs || {}), KONDISI: { block: kartuKeSatu(k.kondisi) } }
  }
  return b
}

function kartuArrayKeRantai(list) {
  let hasil = null
  for (let i = list.length - 1; i >= 0; i--) {
    const b = kartuKeSatu(list[i])
    if (hasil) b.next = { block: hasil }
    hasil = b
  }
  return hasil
}

// Larik Kartu -> satu top block Blockly-serialized (atau null kalau
// kosong), siap dipakai sebagai `blocks.blocks[0]`.
export function kartuKeBlockly(kartuArray) {
  return kartuArrayKeRantai(kartuArray || [])
}

export function programKeWorkspaceJson(kartuArray, variables) {
  const top = kartuKeBlockly(kartuArray)
  return {
    ...(variables ? { variables } : {}),
    blocks: { languageVersion: 0, blocks: top ? [top] : [] },
  }
}

let penghitungId = 0
export function idKartuBaru() {
  penghitungId += 1
  return `kartu-${Date.now()}-${penghitungId}`
}

// Membuat Kartu baru dari drawer "+ tambah blok" dengan nilai bawaan
// sesuai definisi bloknya. `variabelAwal` dipakai untuk field_variable
// (mode kartu tidak membuat variabel baru sendiri — pilih dari yang
// sudah ada di workspace, dibuat lewat mode kanvas seperti biasa).
export function kartuBaru(type, { variabelAwal } = {}) {
  const def = BLOK_PER_TIPE[type]
  const fields = {}
  for (const f of daftarFieldBlok(type)) {
    if (f.type === 'field_number') fields[f.name] = f.value ?? 0
    else if (f.type === 'field_input') fields[f.name] = f.text ?? ''
    else if (f.type === 'field_dropdown') fields[f.name] = f.options[0][1]
    else if (f.type === 'field_variable') fields[f.name] = variabelAwal || { id: null }
  }
  const kartu = { id: idKartuBaru(), type, fields }
  if (punyaSoket(type, 'DO')) kartu.do = []
  if (punyaSoket(type, 'LAIN')) kartu.lain = []
  if (punyaSoket(type, 'KONDISI')) kartu.kondisi = null
  return kartu
}
