# SGB Sales Command Center — App Spec

## Tujuan
Membangun dashboard command center yang sekaligus menjadi workspace input kerja sales untuk dua kategori akun:
- Mini Account
- Regular Account

App harus sederhana, cepat dipakai, mudah dipantau, dan fokus pada kontrol operasional.

Prinsip utama:
- satu sistem untuk input + monitoring
- sales input data harian langsung di sistem
- leader memantau performa real-time
- data chat bisa dipakai untuk evaluasi dan update GPT
- mini dan regular dipisah dalam segmentasi, tapi tetap ada overview gabungan

---

## User Roles

### 1. Super Admin
Hak akses:
- full access semua data
- create / edit / delete semua data
- manage users
- assign role
- edit master data
- edit pipeline status
- lihat semua tim, semua cabang, semua kategori
- export data
- approve knowledge update

### 2. Head / Kepala Kantor
Hak akses:
- lihat seluruh data unit / kantor
- lihat performa semua manager dan sales di unitnya
- lihat chat review
- input catatan evaluasi
- tidak bisa ubah struktur global sistem
- tidak bisa ubah role user

### 3. Manager
Hak akses:
- lihat tim sendiri
- lihat performa sales di bawahnya
- review input harian sales
- beri catatan coaching
- tandai case penting untuk review
- tidak bisa lihat tim lain
- tidak bisa edit pengaturan global

### 4. Sales
Hak akses:
- input lead dan update status lead sendiri
- input ringkasan chat
- input objection
- input hasil follow up
- lihat data sendiri
- tidak bisa lihat data sales lain
- tidak bisa edit master system

---

## Menu Utama App

### 1. Dashboard Overview
Tujuan:
memberi gambaran cepat kondisi operasional harian dan mingguan.

Komponen:
- total leads masuk
- total leads mini
- total leads regular
- total chat aktif
- total closing mini
- total closing regular
- conversion rate mini
- conversion rate regular
- total lost leads
- response time rata-rata
- top performer hari ini
- top performer minggu ini
- pipeline summary

Filter:
- tanggal
- kategori (mini / regular / all)
- kantor
- manager
- sales

---

### 2. Lead Management / Input Harian
Tujuan:
menjadi tempat kerja utama sales.

Field utama:
- Lead ID (auto generate)
- Tanggal input
- Nama lead
- Nomor HP
- Sumber lead
  - TikTok
  - Instagram
  - Facebook
  - Website
  - Referral
  - Ads
  - DM manual
  - Other
- Kategori akun
  - Mini
  - Regular
- Status pipeline
  - New
  - Contacted
  - Replied
  - Ongoing
  - Follow Up
  - Closing
  - Lost
- Nama sales
- Nama manager
- Ringkasan chat terakhir
- Tipe user
  - Cold
  - Warm
  - Hot
- Emosi dominan
  - Takut
  - Ragu
  - Kritis
  - Marah
  - Tertarik
  - Siap
- Objection utama
- Next action
- Jadwal follow up berikutnya
- Hasil akhir
  - Deposit
  - Tidak deposit
  - Masih proses
- Nominal potensi
- Nominal actual deposit
- Catatan internal

Behavior:
- sales wajib bisa create lead baru
- sales wajib bisa update lead lama
- semua perubahan tercatat timestamp
- status pipeline harus mudah diubah
- follow up date harus bisa dimonitor

---

### 3. Pipeline Board
Tujuan:
melihat kebocoran flow secara visual.

Format:
kanban / board view

Kolom:
- New
- Contacted
- Replied
- Ongoing
- Follow Up
- Closing
- Lost

Fitur:
- drag and drop status (jika memungkinkan)
- filter by kategori
- filter by sales
- filter by manager
- filter by date
- klik card untuk lihat detail lead

---

### 4. Sales Performance
Tujuan:
monitor performa individu dan tim.

Metrics per sales:
- jumlah lead masuk
- jumlah lead dihubungi
- jumlah reply
- jumlah ongoing
- jumlah closing
- jumlah lost
- conversion rate
- response time rata-rata
- follow up overdue
- regular closing count
- mini closing count
- total nominal deposit

Metrics per manager:
- total tim
- total lead tim
- total closing tim
- conversion rate tim
- top sales di tim
- overdue follow up tim

---

### 5. Chat Review Center
Tujuan:
mengumpulkan chat penting untuk training, evaluasi, dan update GPT.

Field:
- Lead ID
- Sales
- Kategori akun
- Ringkasan chat
- Cuplikan chat penting
- Status hasil
- Tipe case
  - Closing berhasil
  - Gagal closing
  - Objection baru
  - Case sulit
  - Case unik
- Analisa manager
- Rekomendasi update knowledge
- Status review
  - Pending
  - Reviewed
  - Approved for knowledge
  - Rejected

Fungsi:
- jadi bahan training mingguan
- jadi bahan update GPT knowledge
- jadi sumber evaluasi script

---

### 6. Knowledge Update Queue
Tujuan:
tempat shortlist insight yang layak masuk ke knowledge GPT.

Field:
- Tanggal
- Sumber case
- Kategori akun
- Problem / objection baru
- Jawaban lama
- Jawaban usulan baru
- Status approval
  - Pending
  - Approved
  - Rejected
- Approved by
- Catatan

Hanya Super Admin atau pihak yang ditunjuk yang boleh final approve.

---

### 7. User & Access Management
Tujuan:
mengelola role dan akses.

Fitur:
- create user
- assign role
- assign manager ke sales
- assign head ke kantor
- activate / deactivate account
- reset password

---

## Segmentasi Mini vs Regular
Mini dan Regular wajib dipisah secara data view dan reporting.

Kebutuhan:
- field kategori akun wajib di semua lead
- semua dashboard harus bisa difilter Mini / Regular / All
- leaderboard bisa dipisah Mini dan Regular
- conversion rate harus dibedakan
- nominal actual deposit lebih relevan untuk Regular
- volume chat lebih relevan untuk Mini

---

## KPI Utama

### KPI Mini
- lead masuk
- reply rate
- ongoing count
- closing count
- conversion rate
- volume activity

### KPI Regular
- lead berkualitas
- chat depth / ongoing serious
- closing count
- conversion rate
- nominal deposit
- follow up quality

---

## Workflow Operasional

### Workflow Sales
1. Sales input lead baru
2. Sales hubungi lead via WA
3. Jika lead balas, sales update status menjadi Replied / Ongoing
4. Sales gunakan GPT sesuai kategori akun
5. Sales isi ringkasan chat
6. Sales isi objection jika ada
7. Sales isi next action dan follow up date
8. Jika closing, update hasil dan nominal
9. Jika lost, pilih alasan lost

### Workflow Manager
1. Buka dashboard tim
2. Cek overdue follow up
3. Cek sales dengan conversion rendah
4. Review chat penting
5. Beri catatan coaching
6. Naikkan case penting ke Chat Review Center

### Workflow Head
1. Pantau seluruh unit
2. Lihat trend closing dan conversion
3. Lihat manager performance
4. Lihat bottleneck cabang / tim
5. Monitor kualitas follow up dan disiplin input

### Workflow Super Admin
1. Pantau semua data
2. Review insight dari Chat Review Center
3. Putuskan insight yang masuk Knowledge Update Queue
4. Approve knowledge update
5. Monitor kualitas sistem dan role access

---

## Data yang Harus Tercatat di Setiap Lead
Minimal wajib:
- nama lead
- nomor HP
- kategori akun
- sales owner
- status pipeline
- ringkasan chat terakhir
- follow up date
- hasil akhir

Tanpa field ini, sistem monitoring akan lemah.

---

## Lost Reason (Wajib Ada)
Pilihan alasan lost:
- tidak respon
- tidak tertarik
- takut risiko
- tidak percaya
- tidak siap modal
- membandingkan dengan tempat lain
- follow up gagal
- salah target
- alasan lain

Tujuan:
agar kebocoran bisa dibaca secara objektif.

---

## Notification / Reminder yang Disarankan
Sederhana saja:
- reminder follow up hari ini
- overdue follow up
- lead belum diupdate > 2 hari
- sales belum input harian

Tidak perlu notif yang terlalu banyak.

---

## Hak Edit vs Hak Lihat

### Super Admin
- edit semua
- lihat semua

### Head
- lihat semua dalam unitnya
- tambah catatan evaluasi
- tidak edit global setting

### Manager
- lihat dan review tim sendiri
- edit coaching note
- tidak edit role dan system config

### Sales
- create dan update lead sendiri
- lihat lead sendiri
- tidak edit lead orang lain
- tidak ubah sistem

---

## Struktur Role Final
Role yang cukup untuk versi awal:
- Super Admin
- Head
- Manager
- Sales

Tidak perlu tambah role lain dulu.

---

## Dashboard Metrics yang Harus Muncul
Di halaman utama minimal tampil:
- total leads
- active leads
- closing leads
- lost leads
- conversion rate
- overdue follow up
- top 5 sales
- mini vs regular summary

---

## Search & Filter
App harus punya filter dan search yang simple:
- cari nama lead
- cari nomor HP
- filter status
- filter sales
- filter manager
- filter kategori akun
- filter tanggal

---

## Audit Trail
Setiap perubahan penting harus tersimpan:
- siapa yang edit
- kapan diedit
- field apa yang berubah

Minimal untuk:
- status pipeline
- hasil closing
- nominal
- follow up date

---

## Prinsip UX
- sederhana
- cepat dipakai
- sales tidak perlu input terlalu banyak
- 1 lead idealnya bisa diupdate dalam 15–30 detik
- mobile friendly jika memungkinkan
- table view + card view lebih bagus

---

## Rekomendasi Teknologi / Logika Replit Agent
Boleh dibuat sebagai web app sederhana dengan:
- login page
- role-based dashboard
- database lead
- performance report
- chat review module

Fokus bukan desain mewah, tapi fungsional dan ringan.

---

## Output yang Diharapkan dari App
Dengan app ini, owner harus bisa menjawab pertanyaan berikut kapan saja:
- siapa sales terbaik hari ini?
- siapa conversion paling rendah?
- lead banyak drop di tahap mana?
- objection apa yang paling sering muncul?
- mini dan regular performanya bagaimana?
- siapa yang overdue follow up?
- chat mana yang layak dijadikan update knowledge GPT?

Jika app bisa menjawab itu, maka sistem sudah hidup.

---

## Final Notes
Ini adalah versi awal yang sengaja dibuat simple tapi kuat.

Prioritas utama:
1. input harian jalan
2. dashboard monitoring hidup
3. role access aman
4. chat review bisa dipakai untuk update GPT

Jangan tambah fitur lain dulu sebelum versi ini berjalan stabil.

