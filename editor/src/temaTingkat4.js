// Tema Blockly tingkat 4 — milestone 6.3 (rencana-build.md: "tema gelap,
// tata letak menyerupai editor kode sungguhan"). Dipakai untuk kanvas blok
// yang masih tersisa (blok tetap ada, cuma jadi OPSIONAL di tingkat 4 —
// PRD §4) — palet gelap yang sama nuansanya dengan panel kode/textarea
// mode teks (lihat .editor-teks di App.vue), supaya keduanya terasa satu
// aplikasi, bukan dua tema yang ditempel.
import * as Blockly from 'blockly/core'

export const TEMA_TINGKAT_4 = Blockly.Theme.defineTheme('tingkat4', {
  base: Blockly.Themes.Zelos,
  name: 'tingkat4',
  componentStyles: {
    workspaceBackgroundColour: '#1E2130',
    toolboxBackgroundColour: '#171923',
    toolboxForegroundColour: '#D7DDEC',
    flyoutBackgroundColour: '#252A3D',
    flyoutForegroundColour: '#D7DDEC',
    flyoutOpacity: 1,
    scrollbarColour: '#4A5170',
    insertionMarkerColour: '#5B8DEF',
    insertionMarkerOpacity: 0.5,
    cursorColour: '#5B8DEF',
  },
})
