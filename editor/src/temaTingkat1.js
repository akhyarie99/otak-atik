// Tema Blockly tingkat 1 — milestone 6.1 (rencana-build.md: "blok >= 56
// px, tema cerah"). Font jauh lebih besar daripada tema bawaan: renderer
// Zelos menghitung ukuran blok dari metrik font + padding, jadi menaikkan
// fontStyle.size di sini yang membuat setiap blok >= 56px tinggi tanpa
// perlu mengubah setiap definisi blok satu-satu (diverifikasi langsung di
// browser lewat Playwright — lihat catatan verifikasi milestone 6.1).
import * as Blockly from 'blockly/core'

export const TEMA_TINGKAT_1 = Blockly.Theme.defineTheme('tingkat1', {
  base: Blockly.Themes.Zelos,
  name: 'tingkat1',
  fontStyle: {
    family: "'Baloo 2', system-ui, sans-serif",
    weight: '700',
    size: 20,
  },
  componentStyles: {
    workspaceBackgroundColour: '#FFF8E7',
    toolboxBackgroundColour: '#FFEFC2',
    toolboxForegroundColour: '#232B4D',
    flyoutBackgroundColour: '#FFFBF0',
    flyoutForegroundColour: '#232B4D',
    flyoutOpacity: 1,
    scrollbarColour: '#F5B32E',
    insertionMarkerColour: '#F5B32E',
    insertionMarkerOpacity: 0.4,
    cursorColour: '#F5B32E',
  },
})
