# 00 — Ikhtisar Proyek

## 1. Identitas

- **Nama repository:** Bel_Madrasah
- **Nama aplikasi kerja:** Bel Madrasah
- **Platform sasaran:** Windows desktop, prioritas Windows 10/11
- **Bahasa utama:** PHP
- **Basis data lokal kandidat:** SQLite
- **Status:** perencanaan dan fondasi teknis belum divalidasi

## 2. Masalah yang ingin diselesaikan

Sekolah memerlukan aplikasi bel yang berjalan otomatis sesuai waktu kegiatan. Operator tidak seharusnya perlu membuka pemutar musik terpisah atau menekan tombol Play setiap kali bel berbunyi. Aplikasi harus sederhana, ringan ketika idle, dan bisa digunakan tanpa internet untuk fungsi inti.

## 3. Prinsip produk

1. **Otomatis:** waktu jadwal memicu audio tanpa tindakan manual.
2. **Mandiri:** tidak membutuhkan XAMPP, Apache, MySQL, atau pemutar musik eksternal yang harus dibuka operator.
3. **Ringan:** menghindari proses berat saat menunggu jadwal.
4. **Lokal-first:** jadwal, pengaturan, log, dan audio dapat dikelola secara lokal.
5. **Mudah dioperasikan:** operator mengubah paket, hari, waktu, nama kegiatan, dan file audio melalui antarmuka aplikasi.
6. **Dapat diprediksi:** aturan prioritas jadwal khusus dan penanganan benturan harus jelas.
7. **Teruji:** setiap tahap mempunyai kriteria uji yang dapat dijalankan di komputer lokal.

## 4. Batasan ruang lingkup awal

Termasuk:
- Paket jadwal yang bisa dibuat dan diubah.
- Paket aktif global.
- Jadwal per hari dan pola hari.
- Pencocokan pasaran Jawa dan minggu kalender.
- Pemutaran audio lokal otomatis.
- Status pemutaran dan log.
- Pengemasan untuk Windows.

Belum menjadi kebutuhan awal:
- Sinkronisasi cloud atau server pusat.
- Kontrol jarak jauh melalui internet.
- Notifikasi popup Windows sebagai fitur wajib.
- Akun multi-operator atau sistem hak akses kompleks.
- Pemutaran audio melalui layanan streaming.

## 5. Definisi berhasil

Aplikasi dinyatakan memenuhi tujuan inti ketika:
- Dapat dijalankan pada komputer Windows yang tidak memasang XAMPP.
- Jadwal tersimpan setelah aplikasi ditutup dan dibuka kembali.
- Paket aktif tetap sama sampai operator menggantinya.
- Jadwal yang cocok untuk tanggal/hari berjalan dipilih sesuai aturan.
- Audio lokal mulai diputar sesuai jadwal dan dapat selesai tanpa dipotong oleh aplikasi.
- Pemutaran tidak berganda untuk satu kejadian jadwal.
- Kegagalan audio atau jadwal terlewat dapat ditelusuri melalui status/log.
- Konsumsi sumber daya saat menunggu jadwal terukur dan dianggap layak pada komputer target.

## 6. Status UI/UX terbaru

- Standar Material Design telah ditetapkan di [05-UI-UX-MATERIAL-DESIGN.md](05-UI-UX-MATERIAL-DESIGN.md).
- Design tokens dan komponen dasar awal sudah ditambahkan ke `prototype/phpdesktop/www/assets/app.css`.
- Dashboard Admin awal tersedia di `prototype/phpdesktop/www/dashboard.php`; halaman diagnostik tetap tersedia terpisah melalui menu Pemeriksaan. Tampilan baca-saja publik tersedia di `prototype/phpdesktop/www/public.php`.
- UI-3 Paket Jadwal, UI-4 Editor Jadwal, UI-5 Konfigurasi/Backup, dan UI-6 Public Dashboard sudah memiliki implementasi kode awal serta link navigasi bersama; UI-7 polish/regresi masih tersisa.
- Tampilan belum dinyatakan lulus karena belum diuji pada runtime Windows lokal.

## 7. Acuan UI/UX

Seluruh halaman Admin Dashboard dan Public Dashboard/status mengikuti [Standar UI/UX Material Design](05-UI-UX-MATERIAL-DESIGN.md). Roadmap di [03-IMPLEMENTATION-PLAN.md](03-IMPLEMENTATION-PLAN.md) menempatkan standarisasi visual setelah editor jadwal dan sebelum implementasi resolver kalender/scheduler berikutnya. Redesign wajib menjaga aturan bisnis dan diuji di runtime Windows lokal.

## 8. Pola kolaborasi

1. Perubahan dikerjakan secara bertahap di repository.
2. Setiap commit memiliki pesan yang menjelaskan perubahan.
3. Tahap yang dapat diuji diserahkan dengan instruksi uji.
4. Pengguna melakukan pull dan menguji di komputer lokal.
5. Masalah diperbaiki berdasarkan hasil nyata, termasuk screenshot, log, atau pesan error.
6. Tahap berikutnya tidak dianggap selesai hanya karena kode berhasil ditulis; hasil perlu diuji sesuai kemampuan lingkungan.
