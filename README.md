# SGB Sales Command Center - Tahap 2

Fondasi inti aplikasi penjualan internal berbasis Laravel 13 + MySQL.

## Cakupan Tahap 1 (Fondasi)
- Otentikasi (login/logout)
- Akses berbasis peran
- Skema basis data inti (unit, team, user, prospect, prospect_logs)
- Manajemen prospek + input harian
- Dashboard ringkas

## Cakupan Tahap 2 (Pemantauan Operasional)
- Pipeline board (kanban) berdasarkan status:
  - Baru
  - Dihubungi
  - Dibalas
  - Sedang Berjalan
  - Tindak Lanjut
  - Penutupan
  - Hilang
- Halaman kinerja penjualan per rentang tanggal
- Filter & pencarian cepat (status, owner, due follow-up, kata kunci)
- Quick update status prospek langsung dari board
- Pengingat tindak lanjut (hari ini, <= 3 hari)
- Deteksi prospek terlambat follow-up

## Cakupan Tahap 3 (Tinjauan & Pembelajaran)
- Pusat Tinjauan Obrolan (`chat-reviews`)
- Antrian Pembaruan Pengetahuan (`knowledge-queue`)
- Catatan tinjauan manajer untuk tiap kasus obrolan
- Alur persetujuan Super Admin (queued -> in_review -> approved/rejected)
- Fokus menangkap pola percakapan berhasil/gagal untuk bahan pembaruan knowledge GPT

## Batasan Operasional
- Prioritas utama: kontrol operasional dan input penjualan harian
- Tidak menambah kompleksitas enterprise yang tidak perlu
- Kategori akun `mini` dan `reguler` tersedia dan dapat difilter pada modul operasional

## Peran
- `super_admin`: akses semua data
- `kepala`: melihat semua prospek di unit yang sama
- `manager`: melihat prospek tim yang sama
- `penjualan`: hanya melihat & mengedit prospek miliknya sendiri

## Setup
1. Pastikan MySQL aktif.
2. Buat database: `sgb_sales_command_center`
3. Jalankan:
```bash
composer install
npm install
php artisan migrate:fresh --seed
php artisan serve
npm run dev
```

## Akun Demo
Password semua akun: `password123`

- Super Admin: `admin@sgbcc.test`
- Kepala Unit: `kepala.barat@sgbcc.test`
- Manajer Tim: `manager.alpha@sgbcc.test`
- Penjualan: `andi@sgbcc.test`
- Penjualan: `rina@sgbcc.test`
- Penjualan: `bayu@sgbcc.test`
# sales-command-center
