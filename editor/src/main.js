import { createApp } from 'vue'
import App from './App.vue'
import './style.css'

createApp(App).mount('#app')

// PWA offline penuh (milestone 7.3) — daftarkan setelah halaman selesai
// dimuat agar tidak menunda tampilnya editor itu sendiri.
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
  })
}
