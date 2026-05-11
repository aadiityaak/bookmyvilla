# PRD — Aplikasi Penyewaan Kost & Villa (Laravel Starter Kit + Vue)

**Versi:** 0.1  
**Tanggal:** 2026-05-11  
**Pemilik dokumen:** (isi)  
**Stakeholder utama:** Product, Engineering, Ops/CS, Finance

---

## 1) Ringkasan

Aplikasi ini memfasilitasi **pencarian dan penyewaan kamar kost** serta **villa** dalam satu platform. Versi pertama (MVP) berfokus pada:

1. **Listing & pencarian**,
2. **Booking & kalender ketersediaan**,
3. **Pembayaran**.

Target awal: meluncurkan produk yang cukup “usable” untuk transaksi end-to-end (lihat → booking → bayar) dengan operasi yang bisa dijalankan tim kecil.

---

## 2) Tujuan & Non-Tujuan

### 2.1 Tujuan (Goals)

- Mempercepat pengguna menemukan unit yang cocok (lokasi, harga, fasilitas).
- Mengurangi friksi proses booking (cek ketersediaan, konfirmasi, pembayaran).
- Menyediakan sumber kebenaran (single source of truth) untuk **ketersediaan** dan **status pembayaran**.
- Memberikan alat dasar bagi pemilik/host untuk mengelola listing dan kalender.

### 2.2 Non-Tujuan (Non-Goals) — untuk MVP

- Program loyalti, referral, membership.
- Dynamic pricing otomatis / revenue management.
- Integrasi channel manager villa (Airbnb/Booking.com).
- Arbitrase “co-living management” (operasional housekeeping, maintenance ticketing lengkap).

---

## 3) Asumsi & Batasan

### 3.1 Asumsi

- Platform menggunakan **Laravel Starter Kit** + **Vue** (asumsi umum: Inertia + Vue, dapat disesuaikan).
- Pembayaran dilakukan via **payment gateway** yang mendukung metode populer di Indonesia (VA, e-wallet, kartu, QRIS).
- Ketersediaan villa berbasis **kalender tanggal**; ketersediaan kost bisa berbasis **bulanan** (opsi) atau **harian** (jika ingin disatukan).

### 3.2 Batasan/Constraint

- MVP harus bisa berjalan tanpa proses manual yang terlalu banyak, tetapi masih boleh ada “admin override” untuk kasus tertentu (refund/cancel).
- Perlu kontrol akses (role-based) karena ada dua sisi: **penyewa** dan **pemilik/host** (+ admin).

---

## 4) Definisi Produk

### 4.1 Jenis properti

- **Kost**: properti dengan banyak unit kamar. Umumnya sewa bulanan, bisa juga harian/mingguan (opsional).
- **Villa**: properti sewa harian dengan kapasitas tamu, aturan check-in/out, deposit (opsional).

### 4.2 Konsep inti (entities)

- Properti (Kost/Villa)
- Unit (kamar untuk kost; untuk villa bisa 1 unit = 1 properti)
- Investor (opsional; informasi kepemilikan/pendanaan terkait properti)
- Kalender ketersediaan
- Booking/Reservation
- Pembayaran (invoice, status, webhook)
- Pengguna (penyewa, pemilik/host, admin)

---

## 5) Persona & Kebutuhan

### 5.1 Penyewa Kost

- Ingin filter lokasi dekat kampus/kantor, harga bulanan, fasilitas (AC, kamar mandi dalam, parkir).
- Ingin info jelas: aturan, jam bertamu, deposit, biaya tambahan.
- Ingin booking “aman” dan bukti pembayaran.

### 5.2 Penyewa Villa

- Ingin lihat ketersediaan tanggal, kapasitas, foto, ulasan (opsional).
- Ingin booking cepat, metode pembayaran beragam.

### 5.3 Pemilik/Host

- Ingin membuat listing mudah, mengatur harga, dan mengelola ketersediaan.
- Ingin notifikasi saat ada booking/pembayaran.
- Ingin dashboard ringkas untuk status booking dan pendapatan.

### 5.4 Admin/Operasional

- Verifikasi listing (opsional), menangani dispute/cancel/refund.
- Monitoring transaksi & integritas data.

### 5.5 Investor

- Ingin nama/brand investor tampil pada listing (transparansi kepemilikan).
- (Opsional fase lanjut) Ingin melihat ringkasan performa properti yang diinvestasikan (booking, pendapatan).

---

## 6) User Journey (Alur Utama)

### 6.1 Penyewa (MVP)

1. Browse listing → gunakan search & filter
2. Buka detail properti/unit → cek fasilitas, harga, aturan, ketersediaan
3. Pilih tanggal (villa) / periode sewa (kost) → buat booking
4. Lanjut ke pembayaran → bayar via gateway
5. Terima status: pending/paid/failed/expired → booking terkonfirmasi jika paid

### 6.2 Pemilik/Host (MVP)

1. Login → buat properti dan unit (jika kost)
2. Isi detail listing + foto
3. Atur ketersediaan (kalender) + harga
4. Terima notifikasi booking → pantau status pembayaran

---

## 7) Ruang Lingkup MVP

### 7.1 Modul 1 — Auth & Roles (fondasi)

- Registrasi & login.
- Role: `tenant` (penyewa), `host` (pemilik), `investor`, `admin`.
- Profil pengguna (nama, nomor HP, email).

**Manajemen user (MVP):**

- **Tenant/Host**
    - Melihat & mengubah profil sendiri (nama, nomor HP, foto opsional).
    - Mengubah password.
    - Verifikasi email/nomor HP bersifat opsional; jika belum terverifikasi, aplikasi menampilkan notice + CTA untuk verifikasi.
- **Admin**
    - Melihat daftar user (pagination) + pencarian (nama/email/HP) + filter role + filter status aktif/nonaktif.
    - Membuat user (untuk kebutuhan operasional CS/ops).
    - Mengubah role user (`tenant` ↔ `host`), dengan batasan audit (lihat catatan audit).
    - Menonaktifkan / mengaktifkan user (soft disable, tidak menghapus data historis).
    - Reset password (generate link reset / set ulang via email) tanpa mengetahui password lama.
    - Melihat ringkasan aktivitas: jumlah booking, jumlah pembayaran (read-only).

### 7.2 Modul 2 — Listing & Pencarian (wajib)

**Fitur pengguna:**

- Halaman home/eksplorasi listing.
- Pencarian: keyword + lokasi/area.
- Filter minimum:
    - Tipe: kost / villa
    - Harga (range)
    - Lokasi/area (kota/kecamatan/kelurahan atau “nearby” opsional)
    - Fasilitas (checkbox)
- Sorting: relevansi / termurah / termahal / rating (rating bisa ditunda) / terbaru.

**Fitur host:**

- CRUD properti.
- CRUD unit kamar untuk kost.
- Upload & kelola foto (minimal: cover + galeri).
- Draft vs published (opsional tapi direkomendasikan).

**Definisi data minimal:**

- Properti: nama, tipe, alamat, lat/lng (opsional), deskripsi, fasilitas, aturan, jam check-in/out (villa), kontak, investor (opsional: nama investor / relasi investor).
- Unit (kost): nama/nomor kamar, harga, fasilitas unit, status aktif.

### 7.3 Modul 3 — Booking & Kalender (wajib)

**Prinsip umum:**

- Sistem harus mencegah double booking dengan aturan konsistensi data.

**Villa (harian):**

- Pilih tanggal check-in/check-out.
- Hitung total harga berdasarkan:
    - nightly rate \* jumlah malam
    - biaya tambahan opsional (cleaning fee, pajak, deposit) — bisa phase berikutnya

**Kost (saran untuk MVP):**
Pilih salah satu pendekatan:

- **Opsi A (paling sederhana):** sewa bulanan dengan “tanggal mulai” dan minimal 1 bulan.
- **Opsi B (lebih seragam):** harian/mingguan/bulanan (lebih kompleks, berisiko lebih lama).

Untuk MVP, rekomendasi: **Opsi A**.

**Fitur booking:**

- Buat booking dengan status:
    - `draft` (opsional)
    - `pending_payment`
    - `confirmed`
    - `cancelled`
    - `expired`
- Expiration untuk pending payment (mis. 30–60 menit untuk metode tertentu).
- Notifikasi email/WhatsApp (WhatsApp bisa phase berikutnya; minimal email/in-app).

**Kalender ketersediaan:**

- Host dapat “block” tanggal (villa) atau menonaktifkan unit (kost).
- Kalender menampilkan:
    - booked (confirmed)
    - pending (opsional, bisa dianggap hold dengan TTL)
    - blocked

### 7.4 Modul 4 — Pembayaran (wajib)

**Kemampuan minimal:**

- Generate invoice dari booking.
- Integrasi payment gateway (VA, e-wallet, QRIS).
- Terima webhook untuk update status pembayaran.
- Rekonsiliasi: booking menjadi `confirmed` ketika pembayaran `paid`.

**Status pembayaran:**

- `unpaid`, `pending`, `paid`, `failed`, `expired`, `refunded` (refund bisa phase berikutnya tapi status disiapkan).

**Syarat penting:**

- Webhook harus idempotent (aman dipanggil ulang).
- Semua perubahan status penting dicatat (audit trail).

---

## 8) Di Luar Scope (Out of Scope) — MVP

- Chat penyewa-host.
- Verifikasi identitas (KYC).
- Review & rating.
- Promo/kupon.
- Split payment untuk multi-host / komisi marketplace (kalau model bisnis butuh komisi, kita bisa tambah di fase berikut).
- Refund otomatis (MVP bisa manual oleh admin).
- Multi-language & multi-currency.

---

## 9) Kebutuhan Fungsional (Detail Requirements)

### 9.0 Auth & Manajemen User

**FR-AU-01** Pengguna dapat registrasi, login, dan logout.  
**FR-AU-02** Pengguna dapat melihat & mengubah profil sendiri (nama, email/HP, foto opsional).  
**FR-AU-03** Pengguna dapat mengubah password (dengan verifikasi password lama).  
**FR-AU-04** Admin dapat melihat daftar user dengan pagination, pencarian, dan filter (role, status aktif).  
**FR-AU-05** Admin dapat membuat user dan menetapkan role awal.  
**FR-AU-06** Admin dapat mengubah role user dan perubahan tersebut tercatat di audit log.  
**FR-AU-07** Admin dapat menonaktifkan user; user nonaktif tidak dapat login dan tidak dapat membuat booking/aksi host baru.  
**FR-AU-08** Admin dapat memicu reset password via mekanisme resmi Laravel (email reset link).
**FR-AU-09** Jika email/nomor HP pengguna belum terverifikasi, sistem menampilkan notice + CTA untuk melakukan verifikasi tanpa memblokir alur utama MVP.

### 9.1 Listing & Pencarian

**FR-LS-01** Pengguna dapat melihat daftar listing dengan pagination.  
**FR-LS-02** Pengguna dapat memfilter berdasarkan tipe (kost/villa), harga, fasilitas, lokasi.  
**FR-LS-03** Pengguna dapat membuka halaman detail listing + unit (jika kost).  
**FR-LS-04** Host dapat membuat/mengubah/mengarsip listing.  
**FR-LS-06** Sistem dapat menampilkan informasi investor pada listing/detail properti (jika diisi).

### 9.2 Booking

**FR-BK-01** Pengguna dapat memilih tanggal/periode dan membuat booking.  
**FR-BK-02** Sistem memvalidasi ketersediaan sebelum membuat booking (server-side).  
**FR-BK-03** Sistem menyimpan ringkasan harga pada booking (untuk menghindari perubahan harga setelah booking).  
**FR-BK-04** Booking pending payment akan expired setelah TTL (background job).

### 9.3 Kalender Ketersediaan

**FR-CA-01** Host dapat memblok tanggal (villa) / menonaktifkan unit (kost).  
**FR-CA-02** Sistem menandai tanggal booked untuk booking confirmed.  
**FR-CA-03** (Opsional MVP) Pending booking melakukan “soft hold” dengan TTL agar tidak terjadi race.

### 9.4 Pembayaran

**FR-PY-01** Sistem membuat invoice dan mengarahkan pengguna ke metode pembayaran (redirect/QR).  
**FR-PY-02** Sistem menerima webhook pembayaran dan memperbarui status payment & booking.  
**FR-PY-03** Sistem menampilkan riwayat pembayaran & status kepada pengguna dan host.  
**FR-PY-04** Sistem mencegah booking confirmed tanpa status paid.

---

## 10) Kebutuhan Non-Fungsional

### 10.1 Keamanan

- Password hashing standar Laravel.
- Rate limit login & endpoint sensitif.
- Validasi otorisasi ketat: host hanya bisa mengakses properti miliknya.
- Tanda tangan/verifikasi webhook pembayaran.
- Logging aman (hindari menyimpan data kartu; gunakan token dari gateway).

### 10.2 Performa & Skalabilitas

- Pencarian listing: minimal indexing DB; opsi advanced: Elasticsearch/Meilisearch (fase berikut).
- Caching untuk halaman listing populer (opsional).

### 10.3 Keandalan

- Background jobs untuk:
    - expire booking
    - sinkronisasi status pembayaran (fallback polling jika webhook gagal)
- Backup DB terjadwal.

### 10.4 Auditability

- Audit log perubahan status booking & payment (siapa, kapan, dari status apa ke apa).

---

## 11) Model Data (High-Level)

> Nama tabel/field final mengikuti konvensi tim, di bawah ini konsepnya.

- `users` (role: tenant/host/investor/admin)
- `users` (role: tenant/host/admin)
- `users.is_active` (boolean) atau `users.disabled_at` (timestamp) untuk disable user
- `investors` (nama/brand, kontak opsional) atau `properties.investor_name` (opsi sederhana MVP)
- `users.email_verified_at` / `users.phone_verified_at` (opsional sesuai keputusan verifikasi)
- `properties` (type: kost/villa, owner_id)
- `units` (property_id, untuk kost)
- `amenities` + pivot (property_amenities, unit_amenities opsional)
- `bookings` (user_id, property_id, unit_id nullable, start_date, end_date, status, price_snapshot_json)
- `payments` (booking_id, provider, external_id, status, amount, raw_payload_json)
- `booking_status_logs` / `payment_status_logs` (audit)
- `photos` (entity_type, entity_id, url/path, is_cover, sort_order)

---

## 12) API / Halaman (Rangkuman)

### 12.1 Halaman (Vue)

- Home / Explore listing
- Search results + filter
- Property detail (kost: daftar unit; villa: kalender)
- Checkout / Payment page
- Booking status page (success/pending/failed)
- Host dashboard: properti, unit, kalender, booking
- Admin dashboard (minimal): monitor booking & payment
- Admin: manajemen user (list, detail, edit role, aktif/nonaktif)

### 12.2 Endpoint (contoh)

- `GET /properties` (search/filter)
- `GET /properties/{id}`
- `POST /bookings`
- `GET /bookings/{id}`
- `POST /payments/initiate`
- `POST /payments/webhook/{provider}`
- `POST /host/properties` + CRUD
- `POST /host/availability/block`
- `GET /admin/users` (list + filter)
- `POST /admin/users` (create)
- `GET /admin/users/{id}` (detail)
- `PATCH /admin/users/{id}` (update role, status, profile tertentu)

---

## 13) Metrik Keberhasilan (Success Metrics)

- Conversion:
    - View listing → detail
    - Detail → mulai booking
    - Booking pending → paid
- Waktu rata-rata dari mulai booking → paid
- Cancel/expire rate
- Jumlah listing aktif (host activation)
- Retensi: pengguna kembali mencari/booking (30 hari)

---

## 14) Risiko & Mitigasi

- **Double booking / race condition** → gunakan transaksi DB + locking/unique constraint + TTL hold.
- **Webhook tidak masuk** → fallback polling / reconcile job + dashboard admin untuk cek anomali.
- **Fraud/chargeback** → kebijakan cancel/refund jelas; simpan audit log; gunakan gateway yang support fraud checks (fase lanjut).
- **Konten listing buruk** → moderasi/admin approval (fase lanjut).

---

## 15) Rencana Rilis (Usulan)

### Phase 0 — Fondasi (1–2 minggu)

- Setup Laravel starter kit + Vue, auth, role, layout dasar.

### Phase 1 — Listing & Host Panel (2–4 minggu)

- CRUD properti/unit, upload foto, listing publik + filter dasar.

### Phase 2 — Booking & Kalender (2–4 minggu)

- Ketersediaan, booking creation, TTL expiration job, status page.

### Phase 3 — Pembayaran (2–4 minggu)

- Integrasi gateway, webhook, invoice, konfirmasi booking otomatis.

---

## 16) Pertanyaan Terbuka (untuk diputuskan)

1. Model harga & periode untuk kost: **bulanan saja (rekomendasi MVP)** atau multi-periode?
2. Apakah platform mengambil komisi? Jika ya:
    - tipe komisi (persentase/fixed)
    - kapan komisi diakui (paid/settled)
3. Ketersediaan pending booking: apakah perlu “hold” slot (TTL) atau cukup first paid wins?
