// Simpan, buka projek — milestone 2.3.
// project.json menyimpan DUA bagian (PRD 6.5): "blockly" (susunan blok,
// untuk dibuka lagi di editor) dan "program" (AST, untuk dijalankan).
// Pemisahan ini yang membuat game lama tetap bisa diputar walau
// editornya nanti berubah (aturan tetap #3).
import * as Blockly from 'blockly/core'
import { programAst } from '@otak-atik/blok'

export const FORMAT_PROJEK = 'otak-atik'
export const VERSI_PROJEK = 1

export function berkasProjek(workspace) {
  return {
    format: FORMAT_PROJEK,
    versi: VERSI_PROJEK,
    dibuat: new Date().toISOString(),
    program: programAst(workspace),
    blockly: Blockly.serialization.workspaces.save(workspace),
  }
}

export function unduhBerkas(nama, isi, tipe = 'application/json') {
  const blob = new Blob([isi], { type: tipe })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = nama
  a.click()
  URL.revokeObjectURL(url)
  return blob.size
}

export function simpanProjek(workspace, nama = 'karyaku.json') {
  const data = berkasProjek(workspace)
  return unduhBerkas(nama, JSON.stringify(data, null, 2))
}

export async function bacaBerkasProjek(file) {
  const teks = await file.text()
  let data
  try {
    data = JSON.parse(teks)
  } catch {
    throw new Error('Berkas ini bukan JSON yang sah.')
  }
  if (data.format !== FORMAT_PROJEK || !data.blockly) {
    throw new Error('Berkas ini bukan projek Otak-atik yang dikenali.')
  }
  return data
}

export function muatProjek(data, workspace) {
  Blockly.serialization.workspaces.load(data.blockly, workspace)
}
