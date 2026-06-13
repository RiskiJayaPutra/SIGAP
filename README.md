# SIGAP WebGIS - Sistem Informasi Geografis Area Publik

![SIGAP WebGIS](public/images/logo.png)

**SIGAP WebGIS** adalah sebuah platform Sistem Informasi Geografis (WebGIS) modern berbasis web yang dikembangkan khusus untuk memetakan dan mengelola sebaran fasilitas publik di wilayah Kota Bandar Lampung. Sistem ini dibangun dengan arsitektur yang kokoh menggunakan **Laravel 11**, **Filament v3** untuk panel administrasi, dan **Leaflet.js** untuk visualisasi spasial interaktif.

## Fitur Utama

- **Peta Interaktif Real-time**: Menggunakan Leaflet.js dengan basemap OpenStreetMap untuk rendering peta yang mulus dan responsif.
- **Pencarian Rute (Routing)**: Terintegrasi dengan Leaflet Routing Machine (OSRM) untuk kalkulasi jarak dan rute perjalanan dari lokasi pengguna ke titik fasilitas yang dituju.
- **Manajemen Kategori Spasial**: Sistem *filtering* pintar untuk menampilkan atau menyembunyikan fasilitas berdasarkan kategori (Pendidikan, Kesehatan, Ibadah, Transportasi, dll) dengan dukungan ikon SVG dinamis.
- **Admin Panel Terintegrasi**: Menggunakan Filament v3 untuk pengelolaan data fasilitas, kategori, dan informasi wilayah kecamatan secara *real-time*. Dilengkapi dengan fitur *auto-detect* kecamatan berdasarkan koordinat (menggunakan PostGIS).
- **Sistem Pelacakan Lokasi Terpusat**: Kemampuan mendeteksi lokasi pengguna secara otomatis (Geolocation API) untuk kalkulasi rute yang lebih akurat.

## Tech Stack

Sistem ini didesain menggunakan teknologi terkini:
- **Backend Framework**: [Laravel 11.x](https://laravel.com)
- **Admin Panel**: [Filament v3](https://filamentphp.com)
- **Frontend & Mapping**: Vanilla JS, [Leaflet.js](https://leafletjs.com/), Leaflet Routing Machine
- **Styling**: Tailwind CSS & Vanilla CSS (untuk komponen spasial)
- **Database**: PostgreSQL dengan ekstensi **PostGIS** untuk kueri spasial tingkat lanjut (`ST_Contains`, `ST_GeomFromGeoJSON`, dll).

## Persyaratan Sistem

Sebelum melakukan instalasi, pastikan *environment* server Anda memenuhi spesifikasi berikut:
- PHP ^8.2
- Composer ^2.x
- Node.js & npm (versi terbaru)
- PostgreSQL ^14.x
- Ekstensi **PostGIS** terpasang pada PostgreSQL

## Panduan Instalasi & Konfigurasi

Ikuti langkah-langkah teknis di bawah ini untuk men-deploy SIGAP WebGIS pada mesin lokal Anda:

### 1. Kloning Repositori
Clone repositori ini menggunakan Git dan masuk ke direktori proyek.
```bash
git clone https://github.com/RiskiJayaPutra/SIGAP.git
cd SIGAP
```

### 2. Instalasi Dependensi (Backend & Frontend)
Instal seluruh *package* PHP melalui Composer dan *library* frontend melalui NPM.
```bash
composer install
npm install
npm run build
```

### 3. Konfigurasi Environment
Salin file environment default dan *generate* Application Key.
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi database Anda di file `.env`. **Penting:** Pastikan menggunakan driver `pgsql`.
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sigap
DB_USERNAME=postgres
DB_PASSWORD=password_anda
```

### 4. Ekstensi PostGIS
Buat database di PostgreSQL. Masuk ke *query tool* PostgreSQL Anda dan aktifkan ekstensi PostGIS (harus dilakukan sebelum migrasi).
```sql
CREATE DATABASE sigap;
\c sigap;
CREATE EXTENSION postgis;
```

### 5. Migrasi & Seeding Data
Jalankan migrasi skema tabel beserta *seeder* untuk mengisi data awal (Kategori, Kecamatan, GeoJSON batas wilayah, dan Akun Admin).
```bash
php artisan migrate:fresh --seed
```
*Catatan: Proses seeding batas kecamatan (`KecamatanGeomSeeder`) akan otomatis mengekstrak file `Kecamatan_Bandar_Lampung.geojson` menjadi data spasial yang kompatibel dengan database Anda.*

### 6. Storage Link & Asset Publish
Konfigurasikan akses publik ke direktori penyimpanan dan pastikan seluruh *asset* bawaan Filament diekstrak.
```bash
php artisan storage:link
php artisan filament:upgrade
php artisan icons:cache
```

### 7. Menjalankan Server
Sistem siap dijalankan. Mulai development server menggunakan Artisan.
```bash
php artisan serve
```
Akses aplikasi melalui *browser* pada alamat `http://127.0.0.1:8000`. 
Untuk mengakses dashboard Admin Filament, buka rute `/admin` dan *login* menggunakan kredensial yang telah Anda atur.

---

**Hak Cipta © 2026 SIGAP WebGIS Project.** Dikembangkan untuk mendukung tata kelola informasi tata ruang wilayah kota.
