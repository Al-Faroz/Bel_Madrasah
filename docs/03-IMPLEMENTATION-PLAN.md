# 03 — Roadmap Pengerjaan Baru

## Aturan status

- **DOC READY:** kebutuhan dan urutan kerja terdokumentasi.
- **AUDIT:** kode dan dependensi diperiksa, belum berarti benar.
- **IMPLEMENTED:** kode tersedia di repository.
- **LOCAL TEST PASS:** pengguna telah menjalankan skenario pada PHP Desktop dan melaporkan hasilnya.
- PoC yang pernah PASS hanya membuktikan skenario PoC terkait; tidak otomatis meluluskan fitur produksi.

## Keputusan reset

Tahap 1–4 sebelumnya adalah PoC/fondasi untuk membuktikan PHP Desktop Chrome 130.1 + PHP 8.3, SQLite, audio internal, konfigurasi/backup dasar, dan paket jadwal bisa berjalan. Hasil itu dipertahankan sebagai bukti teknis. Roadmap produk dimulai kembali dari Fase A, tanpa mengulang eksperimen yang sudah terbukti dan tanpa menganggap seluruh UI/fitur lama sudah benar.

## Keputusan arsitektur yang dikunci

- Public area menjadi entry point aplikasi; pemeriksaan teknis bukan beranda.
- Dashboard admin diakses langsung dari public area; tidak ada login/Auth.
- Gunakan PHP native layouts/partial views melalui require, pisahkan layout public dan admin.
- Public punya CSS/JS terpisah jika dibutuhkan.
- Folder aset persisten berisi subfolder audio dan logo. Operator dapat membuka folder dari aplikasi dan copy-paste file; nama file bebas.
- Aplikasi harus menyediakan pemilihan aset yang benar-benar menunjuk file di folder aset, menyimpan referensi persisten di SQLite, dan mendeteksi file yang hilang.
- Native browser file picker tidak boleh dianggap memberi path lokal penuh. Audit bridge PHP Desktop terlebih dahulu; bila tidak tersedia, pilih dari daftar file yang ditemukan di folder aset.
- Pertahankan database dan referensi path lama. Migrasi harus aditif dan tidak menghapus aset lama.

## Roadmap eksekusi yang diperbarui

### Fase A — Audit dan kontrak teknis
1. Audit entry point/runtime config PHP Desktop.
2. Audit seluruh method Database, schema/migrasi, form POST, validasi, backup, dan referensi audio.
3. Audit CSS/JS serta navigasi; catat baseline sebelum redesign.
4. Tetapkan kontrak path aset dan kompatibilitas path audio lama.
**Kriteria selesai:** tidak ada asumsi kritis yang belum diperiksa; risiko dan keputusan tercatat di audit.

### Fase B — Fondasi arsitektur
1. Buat entry point public tanpa menghapus halaman diagnostik.
2. Susun layout public/admin dan partial PHP native.
3. Susun CSS tokens/komponen bersama; hilangkan CSS aditif yang berkonflik secara terukur.
4. Sediakan struktur folder aset persisten dan cara membuka lokasinya dari UI.
**Kriteria selesai:** navigasi, include path, lokasi data, dan kontrak aset konsisten.

### Fase C — Public area
1. Tampilan jadwal publik sederhana dan terbaca.
2. Akses jelas ke Dashboard Admin.
3. Informasi jadwal tidak disamakan dengan status audio real-time.
**Kriteria selesai:** entry point mengarah ke public, tanpa menu admin yang tidak relevan.

### Fase D — Admin shell dan modul
Urutan: D1 app shell/partial; D2 Dashboard; D3 Paket Jadwal; D4 Editor Jadwal; D5 Konfigurasi/aset; D6 Backup/Restore; D7 Diagnostik; D8 polish dan responsivitas.
Setiap modul diselesaikan, direview, dan diuji terpisah sebelum modul berikutnya.

### Fase E — Mesin operasional dan rilis
Resolver kalender Normal/Khusus → scheduler/audio dan log status → restore aman → hardening → paket distribusi dan panduan operator.

### Aturan kerja
- Satu batch memiliki satu hasil yang jelas dan commit terpisah.
- Jangan menambah patch CSS sebelum selector terkait diaudit.
- Jangan menyebut fitur “lulus” hanya karena kodenya tersedia.
- Pengguna menguji runtime PHP Desktop pada Windows; hasilnya dicatat sebagai LOCAL TEST PASS hanya setelah dilaporkan.
- Jangan mereset database, memindahkan AppData, atau menghapus data/file lama demi merapikan struktur.



## Status checkpoint — 2026-10-13

- **Fase A / audit statis:** diperluas dan dicatat di `docs/06-BASELINE-AUDIT.md`. Konfigurasi runtime PHP Desktop yang benar-benar terpasang, pengujian Windows, resolver kalender, dan desain restore masih terbuka.
- **Fase B / D1 Admin shell:** kode layout dan partial bersama sudah diimplementasikan pada commit [`448a0d2`](https://github.com/Al-Faroz/Bel_Madrasah/commit/448a0d275084f57805fefba2024db2b01329c77a).
- **Fase D / Dashboard Admin pilot:** markup dashboard dipisah menjadi view dan menggunakan layout baru. Statusnya **IMPLEMENTED**, belum **LOCAL TEST PASS**.
- Pemeriksaan PHP CLI dan smoke test memakai data simulasi lulus pada lingkungan kerja, tetapi tidak menggantikan pengujian PHP Desktop Windows dengan database SQLite asli.
- **Checkpoint wajib:** uji dashboard pada runtime target terlebih dahulu. Setelah hasil dicatat, lanjutkan migrasi satu modul per batch; modul lain belum dianggap menggunakan layout baru.

## Checkpoint lanjutan — 2026-10-13

- **Navigasi Admin → Publik:** ditemukan satu tautan dashboard yang masih memakai `target="_blank"`; atribut tersebut diubah menjadi `target="_self"` pada commit [`d14c614`](https://github.com/Al-Faroz/Bel_Madrasah/commit/d14c614e10fed10e74ffd52156257e0f3a65d98e). Pemeriksaan pencarian kode tidak menemukan lagi `target="_blank"` pada branch utama. Perilaku runtime PHP Desktop tetap perlu diverifikasi.
- **D3 Paket Jadwal:** controller `packages.php` kini menyiapkan data dan operasi lalu menggunakan layout bersama. Markup dipindahkan ke `views/admin/packages.php`; navigasi dan footer berasal dari partial/layout Admin.
- Perubahan D3 hanya refaktor struktur presentasi. Logika penyimpanan, aktivasi, pembuatan paket, pilihan hari libur, dan data SQLite dipertahankan. Tidak ada operasi penghapusan atau migrasi data.
- Commit D3: [`03f0920`](https://github.com/Al-Faroz/Bel_Madrasah/commit/03f09206c8d98010cb0757927f991350eec49b29) menambahkan view; [`99203a6`](https://github.com/Al-Faroz/Bel_Madrasah/commit/99203a6708f3b8f2e6e0752716c2edde3a612696) memigrasikan controller.
- Status: **IMPLEMENTED**, belum **LOCAL TEST PASS**. Pengguna meminta pengerjaan dilanjutkan tanpa menunggu uji dashboard; karena itu pekerjaan berlanjut, tetapi pengujian pada PHP Desktop Windows tetap tercatat sebagai belum dilakukan.
- **Batch berikutnya:** D4 — migrasi Editor Jadwal ke layout Admin bersama, dengan pemeriksaan khusus agar seluruh alur edit/penyimpanan tetap utuh.

