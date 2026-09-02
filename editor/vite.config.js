import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    // Ekspor game mandiri membaca ../pemutar/motor.min.js?raw (di luar
    // akar editor/) — lihat editor/src/ekspor.js.
    fs: { allow: ['..'] },
  },
})
