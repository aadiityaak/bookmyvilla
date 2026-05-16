# Rencana Pengerjaan (Kost per Kamar Bulanan + Villa Harian)

Dokumen ini merangkum langkah implementasi untuk:
- Kost: sewa per kamar, billing bulanan, status penghuni aktif.
- Villa: booking harian, kalender ketersediaan, pembayaran per booking.

## Prinsip Desain Data
- Kost “aktif” ditentukan oleh kontrak sewa kamar (tenancy/lease), bukan oleh pembayaran.
- Villa “aktif” ditentukan oleh booking/reservation.
- Pembayaran dipisah dari kontrak/booking agar fleksibel (DP, cicilan, telat bayar, refund).
- Transaksi bisa disatukan dengan `orders` bila butuh satu mekanisme checkout untuk semua tipe produk (opsional).

## Tahap 0 — Kebutuhan & Aturan Bisnis (wajib diputuskan)
- Kost
  - Apakah kontrak selalu per bulan kalender atau bisa start di tanggal berapa saja?
  - Aturan overdue: tenant masih dianggap aktif jika telat bayar?
  - Apakah boleh 1 kamar punya lebih dari 1 penghuni (roommates)?
- Villa
  - Aturan check-in/check-out (jam), minimum stay, blokir tanggal.
  - Status booking: pending/confirmed/cancelled/completed.
- Pembayaran
  - Gateway yang dipakai, status pembayaran, dan kebijakan refund.

## Tahap 1 — Data Model & Migration

### 1.1 Property & Unit (Kamar)
- `properties`
  - Tetap dipakai untuk kost dan villa (kolom `type` sudah ada).
- Tambah tabel `rooms` (kamar/unit) untuk `properties.type = kost`
  - Kolom minimal: `id`, `property_id`, `code/name`, `floor`, `price_monthly`, `capacity`, `status`
  - Indeks: `property_id`, `(property_id, status)`

### 1.2 Kost Bulanan — Tenancy/Lease
- Tambah tabel `tenancies`
  - Kolom minimal:
    - `id`, `room_id`, `tenant_user_id`
    - `start_date`, `end_date` (nullable jika open-ended)
    - `billing_day` (1–28) atau aturan billing lain
    - `status` (active/ended/cancelled)
    - `monthly_price`, `deposit_amount` (opsional)
  - Constraint: 1 kamar tidak boleh overlap tenancy aktif pada periode yang sama (enforced via aplikasi + index/query).

### 1.3 Villa Harian — Booking/Reservation
- Tambah tabel `bookings`
  - Kolom minimal:
    - `id`, `property_id`, `guest_user_id`
    - `check_in_date`, `check_out_date`
    - `guests_count`, `status` (pending/confirmed/cancelled/completed)
    - `price_per_night`, `total_amount`
  - Validasi: tanggal tidak boleh overlap pada booking yang confirmed.

### 1.4 Billing & Payment (disarankan)
- `invoices` (untuk kost bulanan)
  - `id`, `tenancy_id`, `period_start`, `period_end`, `amount`, `status` (unpaid/paid/void), `due_date`
- `payments`
  - `id`, `payable_type`, `payable_id` (polymorphic: invoice/booking/order)
  - `provider`, `provider_ref`, `amount`, `status` (pending/paid/failed/refunded), `paid_at`

### 1.5 Orders (opsional, jika mau 1 checkout flow)
- `orders`
  - `id`, `user_id`, `status`, `total_amount`, `currency`, `metadata`
- `order_items`
  - `id`, `order_id`, `item_type` (tenancy_first_month / villa_nights / addon), `ref_id`, `amount`

## Tahap 2 — Seeder & Data Dummy
- Seeder user (admin/host/investor/tenant) sudah ada.
- Tambah seeder:
  - `RoomSeeder` (buat beberapa kamar untuk kost).
  - `TenancySeeder` (buat tenancy aktif + beberapa sudah ended).
  - `BookingSeeder` (buat booking villa confirmed untuk uji overlap).
  - `InvoiceSeeder` (buat invoice bulan berjalan + bulan lalu).

## Tahap 3 — API/Controller & Validasi
- Kost
  - CRUD kamar (rooms) untuk host/admin.
  - CRUD tenancy untuk host/admin:
    - Create tenancy memeriksa overlap.
    - End tenancy (set end_date + status ended).
  - Generate invoice bulanan (manual + scheduler).
- Villa
  - CRUD booking untuk guest/tenant (buat), host/admin (manage).
  - Validasi overlap kalender saat confirm booking.
- Payment
  - Endpoint callback gateway + update status.
  - Hubungkan payment ke invoice/booking.

## Tahap 4 — UI (Inertia + Vue)
- Kost (Host/Admin)
  - Property (kost) → daftar kamar → detail kamar → tenancy list.
  - Form tenancy: pilih tenant user, tanggal mulai, billing day, harga.
  - Dashboard: “kamar terisi”, “kamar kosong”, “tenant aktif”.
- Villa (Guest/Tenant)
  - Listing villa + kalender ketersediaan.
  - Booking flow: pilih tanggal, isi data tamu, checkout.
- Shared
  - Halaman invoice + status pembayaran.
  - Komponen kalender (untuk villa) dan tabel periodik (untuk invoice).

## Tahap 5 — Rules & Reporting
- Definisi “aktif”
  - Kost aktif: tenancy status active + tanggal berlaku.
  - Villa aktif: booking status confirmed + tanggal range.
- Laporan
  - Occupancy rate kost per properti/per kamar.
  - Revenue: invoice paid (kost) + booking paid (villa).

## Tahap 6 — Non-Functional
- Authorization/Role
  - Tenant/guest hanya bisa lihat/booking.
  - Host hanya untuk properti miliknya.
  - Admin full access.
- Audit log (opsional)
  - Log perubahan status tenancy/booking/payment.
- Storage
  - Featured image + gallery sudah memakai disk `public`; pastikan `storage:link` dan policy upload.

## Deliverables Minimum (MVP)
- Kost
  - rooms + tenancies + invoice generation manual
  - daftar tenant aktif per kamar
- Villa
  - bookings + overlap check
- Payments (MVP)
  - pencatatan status pembayaran manual (tanpa gateway) atau integrasi 1 gateway.

