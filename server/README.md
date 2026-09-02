# server

Laravel 12 + Inertia (Vue) — mulai diisi di milestone 4.1 (fondasi tenant).

## Model tenant (PRD 6.8, aturan tetap #5)

- **Satu basis data**, kolom `tenant_id` — bukan satu basis data per sekolah (`sekolah` = tabel tenant).
- **Peran melekat pada keanggotaan** (`app/Models/Keanggotaan.php`), bukan pada akun (`User`). Satu akun bisa punya banyak keanggotaan di sekolah berbeda dengan peran berbeda.
- **`App\Services\TenantContext`** menyimpan sekolah aktif untuk request saat ini (diisi middleware dari keanggotaan yang dipilih user — menyusul di milestone 4.2).
- **`App\Models\Concerns\BelongsToTenant`**: tempel di setiap model data sekolah (kelas, siswa, karya, tugas, dst). Otomatis menambahkan `TenantScope` dan mengisi `tenant_id` saat baris dibuat.
- **Gagal tertutup**: kalau tidak ada tenant aktif sama sekali, kueri menghasilkan kosong — bukan menampilkan semua sekolah. Lihat `app/Models/Scopes/TenantScope.php`.
- Uji pembuktiannya ada di `tests/Feature/Tenant/TenantScopeTest.php` — termasuk kasus akses lewat ID langsung, sesuai aturan tetap #5.

## Menjalankan lokal

Basis data: MariaDB lewat XAMPP (`C:\xampp\mysql\bin\mysqld.exe`), basis data `otak_atik` — **jangan** basis data lain di instance XAMPP yang sama (dipakai proyek lain juga).

```
composer install
npm install
php artisan migrate
php artisan serve       # http://127.0.0.1:8000
npm run dev              # Vite untuk aset Inertia/Vue
php artisan test          # 31 uji, termasuk TenantScopeTest
```
