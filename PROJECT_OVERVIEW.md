# SGB Sales Command Center

## Ringkasan
SGB Sales Command Center adalah aplikasi internal untuk operasional penjualan yang menggabungkan:

- input prospek harian
- monitoring follow up
- pipeline penjualan
- evaluasi performa sales
- review chat penting
- knowledge update queue untuk insight GPT/internal knowledge

Project ini dibangun di atas Laravel sebagai backend utama, lalu sekarang sudah memiliki frontend React preview yang dipasang langsung di route utama untuk halaman operasional inti.

## Tujuan Project
Project ini dibuat untuk membantu tim sales dan leader menjawab kebutuhan operasional harian secara cepat:

- sales bisa input dan update prospek tanpa proses yang ribet
- manager dan head bisa memantau tim secara real-time
- follow up overdue bisa langsung terlihat
- bottleneck pipeline bisa dibaca cepat
- chat penting bisa dikumpulkan untuk review dan pembelajaran

## Tech Stack

### Backend
- PHP `8.3+`
- Laravel `13`
- MySQL
- Eloquent ORM
- Session-based authentication

### Frontend
- React `19`
- Vite `8`
- TypeScript `6`
- Wouter
- TanStack React Query
- Tailwind CSS `4`
- Radix UI primitives
- Lucide React
- React Hook Form + Zod
- Sonner

### Tooling
- Laravel Vite Plugin
- Composer
- npm
- Laravel Pint
- PHPUnit

## Arsitektur Singkat

### Backend Laravel
Laravel menangani:

- authentication
- role-based access control
- routing
- query data dashboard/prospek/pipeline/performance
- validasi request
- mutation data prospek dan quick update
- halaman legacy Blade

### Frontend React
Frontend React menangani:

- shell dashboard modern
- render halaman `/dashboard`
- render halaman `/prospects`
- render halaman `/pipeline`
- render halaman `/kinerja-penjualan`
- fetch data via endpoint JSON `/react-api/*`

### Legacy Layer
Versi Blade lama tetap tersedia sebagai fallback/comparison di route:

- `/legacy/dashboard`
- `/legacy/prospects`
- `/legacy/pipeline`
- `/legacy/kinerja-penjualan`

## Role dan Hak Akses

### Super Admin
- akses semua data
- bisa lihat dan edit semua prospek
- bisa approve knowledge update

### Kepala
- bisa lihat seluruh data dalam unit yang sama
- fokus monitoring unit

### Manager
- bisa lihat data tim yang sama
- fokus monitoring tim

### Penjualan
- hanya bisa lihat prospek milik sendiri
- bisa create prospek
- bisa edit prospek milik sendiri
- bisa quick update dari pipeline

## Modul Utama

### 1. Dashboard
Route:
- `/dashboard`

Fungsi:
- menampilkan total prospek
- menampilkan prospek aktif
- menampilkan jumlah closing
- menampilkan input hari ini
- menampilkan overdue follow up
- menampilkan due today
- menampilkan snapshot status prospek
- menampilkan follow up terdekat

Sumber data:
- `ReactApiController@dashboard`
- query utama berasal dari model `Prospect` dan `ProspectLog`

### 2. Manajemen Prospek
Route:
- `/prospects`

Fungsi:
- list prospek
- filter berdasarkan status
- filter owner
- filter kategori akun
- filter follow up
- pencarian nama/perusahaan/kode/nomor HP
- akses ke detail dan edit prospek

Kemampuan utama:
- create prospek baru
- update prospek
- delete prospek sesuai permission

### 3. Pipeline Board
Route:
- `/pipeline`

Fungsi:
- melihat prospek per status pipeline
- memantau overdue, due today, dan due soon
- quick update status prospek
- quick update next follow up date
- quick note update

Status pipeline:
- `baru`
- `dihubungi`
- `dibalas`
- `sedang_berjalan`
- `tindak_lanjut`
- `penutupan`
- `hilang`

### 4. Kinerja Penjualan
Route:
- `/kinerja-penjualan`

Fungsi:
- melihat leaderboard sales
- melihat total prospek per sales
- melihat closing count
- melihat lost count
- melihat activity count
- melihat overdue count
- melihat estimasi nilai
- melihat rasio closing
- melihat distribusi status
- melihat daftar prospek yang terlambat follow up

### 5. Chat Review Center
Route utama backend:
- resource `chat-reviews`

Fungsi:
- menyimpan review chat penting
- menyimpan catatan manager
- menjadi sumber insight untuk knowledge update

### 6. Knowledge Update Queue
Route utama backend:
- `/knowledge-queue`

Fungsi:
- menampung usulan update knowledge
- alur review
- approve/reject oleh role yang berwenang

## Data dan Entitas Utama

### User
Merepresentasikan pengguna aplikasi.

Field penting:
- nama
- email
- role
- unit
- team

### Prospect
Entitas utama penjualan.

Field penting:
- `prospect_code`
- `name`
- `company`
- `phone`
- `email`
- `source`
- `account_category`
- `status`
- `priority`
- `estimation_value`
- `next_follow_up_date`
- `notes`
- `owner_id`
- `team_id`
- `unit_id`

### ProspectLog
Riwayat input harian / aktivitas prospek.

Field penting:
- `log_date`
- `activity_type`
- `summary`
- `result`
- `next_follow_up_date`
- `prospect_id`
- `user_id`

## Kategori Akun
Project saat ini memakai dua kategori akun:

- `mini`
- `reguler`

Kategori ini dapat dipakai untuk filter dashboard, prospek, pipeline, dan performance.

## Endpoint JSON React
Frontend React menggunakan endpoint berikut:

- `GET /react-api/meta`
- `GET /react-api/dashboard`
- `GET /react-api/prospects`
- `GET /react-api/pipeline`
- `GET /react-api/performance`

Fungsi endpoint ini adalah menyuplai data ke UI React tanpa mengubah business logic utama Laravel.

## Quick Update Pipeline
Quick update di pipeline memakai endpoint backend yang sama dengan logic lama:

- `PATCH /prospects/{prospect}/quick-update`

Payload utama:
- `status`
- `next_follow_up_date`
- `quick_note`

Hasil:
- update status prospek
- update tanggal follow up
- membuat `ProspectLog` baru

## Route Utama Saat Ini

### Frontend React
- `/dashboard`
- `/prospects`
- `/pipeline`
- `/kinerja-penjualan`

### Legacy Blade
- `/legacy/dashboard`
- `/legacy/prospects`
- `/legacy/pipeline`
- `/legacy/kinerja-penjualan`

## Struktur Folder Penting

### Backend
- `app/Http/Controllers`
- `app/Models`
- `routes/web.php`
- `database/migrations`
- `database/seeders`

### Frontend React
- `resources/react/App.tsx`
- `resources/react/main.tsx`
- `resources/react/pages`
- `resources/react/components`
- `resources/react/lib`

### View Blade
- `resources/views`
- `resources/views/react/app.blade.php`

## Cara Menjalankan Project

### Setup awal
```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
```

### Mode development
```bash
composer run dev
```

Atau jalankan manual:
```bash
php artisan serve
npm run dev
```

### Production-style asset build
```bash
npm run build
```

## Akun Demo
Password demo:

```text
password123
```

Contoh akun:
- `admin@sgbcc.test`
- `kepala.barat@sgbcc.test`
- `manager.alpha@sgbcc.test`
- `andi@sgbcc.test`
- `rina@sgbcc.test`
- `bayu@sgbcc.test`

## Kondisi Implementasi Saat Ini
- backend Laravel aktif dan dipakai penuh
- React sudah dipasang untuk halaman operasional inti
- route utama sudah diarahkan ke React untuk dashboard/prospects/pipeline/performance
- legacy Blade masih tersedia untuk fallback
- modul chat review dan knowledge queue masih memakai tampilan backend lama

## Catatan Penting
- React layer saat ini fokus pada UI/UX dan konsumsi data
- logic bisnis utama tetap berada di Laravel controller/model
- jika ingin full migration ke React untuk semua modul, langkah berikutnya adalah memindahkan `chat-reviews` dan `knowledge-queue` ke UI React juga
