# 03 — Roadmap Pengerjaan Baru

## Aturan status

- **DOC READY:** kebutuhan dan urutan kerja terdokumentasi.
- **AUDIT:** kode dan dependensi diperiksa, belum berarti benar.
- **IMPLEMENTED:** kode tersedia di repository.
- **LOCAL TEST PASS:** pengguna telah menjalankan skenario pada PHP Desktop dan melaporkan hasilnya.
- PoC yang pernah PASS hanya membuktikan skenario PoC terkait; tidak otomatis meluluskan fitur produksi.

## Keputusan reset

Tahap 1–4 sebelumnya adalah PoC/fondasi untuk membuktikan PHP Desktop Chrome 130.1 + PHP 8.3, SQLite, audio internal, konfigurasi/backup dasar, dan paket jadwal bisa berjalan. Hasil itu dipertahankan sebagai bukti teknis. Roadmap produk dimulai kembali dari Fase A, tanpa mengulang eksperimen yang sudah terbukti dan tanpa menganggap seluruh UI/fitur lama sudah benar.

## Keputusan arsitektur UI (sudah disepakati)

- Public area adalah halaman pertama saat aplikasi dibuka.
- Dashboard admin dibuka melalui tautan/tombol dari public area.
- Tidak ada login/Auth; dashboard admin langsung dapat diakses.
- Gunakan PHP native terstruktur dengan layout dan partial views memakai `require`.
- Pisahkan layout admin dan public.
- Public mempunyai CSS/JS sendiri bila diperlukan; jangan membebani public dengan aset atau interaksi admin.
- Sediakan folder aset operator untuk audio dan logo beserta petunjuk tertulis di dalam aplikasi. Operator copy-paste file ke subfolder yang sesuai, lalu memilih file yang digunakan melalui File Explorer; database menyimpan referensi file terpilih.
- Nama file audio/logo bebas dan tidak perlu mengikuti pola tertentu.
- Nama/path folder aset final ditentukan setelah memeriksa bagaimana pemutar audio dan pemilih file menangani path saat ini.
- Jangan mereset database atau memindahkan data AppData demi merapikan UI.

## Penyesuaian urutan fase

1. Audit kode dan kontrak path audio/logo.
2. Tetapkan struktur partial views serta design system.
3. Bangun public area minimal sebagai entry point.
4. Bangun admin app shell dengan partial views.
5. Terapkan pada Dashboard Admin, lalu modul lain satu per satu.
6. Lanjutkan resolver kalender, scheduler/audio, backup/restore, hardening, dan distribusi.
