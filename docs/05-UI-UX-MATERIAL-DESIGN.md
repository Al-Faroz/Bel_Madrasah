# 05 — Acuan UI/UX Bel Madrasah

**Status:** ACUAN DESAIN BARU — wajib sebelum redesign modul  
**Referensi utama:** https://github.com/BootstrapDash/RoyalUI-Free-Bootstrap-Admin-Template  
**Target:** PHP Desktop Chrome 130.1 + PHP 8.3, berjalan offline

## 1. Arah desain

Gunakan RoyalUI Free Bootstrap Admin Template sebagai referensi kualitas dan struktur, bukan menyalin semua halaman atau memasang ketergantungan yang tidak diperlukan. Pelajari pola sidebar, top bar, breadcrumb, kartu ringkasan, tabel, form, alert, dan layout dashboard. Pilih komponen yang sesuai kebutuhan aplikasi bel sekolah.

Sasaran: **dashboard admin yang rapi, modern, padat-terbaca, konsisten; public dashboard yang jelas dan fokus pada informasi operasional.** Screenshot UI saat ini adalah bukti baseline/masalah, bukan acuan visual yang harus dipertahankan.

## 2. Prinsip runtime

- Fungsi inti harus tetap dapat berjalan tanpa internet.
- Jangan mengandalkan CDN/font/icon remote saat aplikasi berjalan.
- Jika aset RoyalUI/Bootstrap atau ikon digunakan, simpan aset yang diperlukan secara lokal dan tinjau lisensinya.
- Jangan menambahkan framework besar hanya untuk memperbaiki jarak, warna, atau tombol jika CSS bersama cukup.
- PHP Desktop bukan browser biasa yang dibuka lewat server eksternal; uji resize dan interaksi pada runtime desktop sebenarnya.

## 3. Arsitektur halaman

### Admin
- App shell konsisten di seluruh halaman: navigasi utama, identitas aplikasi, konten dengan lebar terarah, dan indikator halaman aktif.
- Dashboard adalah halaman kerja pertama yang merangkum paket aktif, kegiatan berikutnya, kondisi sistem yang benar-benar diketahui, dan akses cepat.
- Pemeriksaan/diagnostik menjadi menu tersendiri, bukan beranda utama.
- Halaman modul mengikuti pola: judul + deskripsi, tindakan utama, ringkasan seperlunya, konten utama, lalu status/feedback.
- Data padat menggunakan tabel dengan kolom sejajar, waktu konsisten, dan aksi per baris yang jelas.

### Public Dashboard
- Halaman terpisah dari kontrol admin.
- Menonjolkan nama sekolah, tanggal/jam, paket dan jadwal berlaku, kegiatan berikutnya, serta status audio aktual bila mesin audio tersedia.
- Informasi jadwal tersimpan harus diberi label sebagai jadwal; jangan menyebut “sedang diputar” tanpa sinyal runtime.
- Tipografi lebih besar dan kontras tinggi; minim metadata dan kontrol.

## 4. Bahasa visual

- Tipografi sans-serif sistem lokal dengan skala yang terdefinisi untuk heading, isi, label, helper, tabel, dan metadata.
- Palet warna terbatas: satu primary, permukaan, latar, teks, border, focus, success, warning, error. Identitas hijau boleh dipakai, tetapi bukan berarti setiap kartu harus hijau.
- Spacing, radius, border, shadow, dan tinggi kontrol mengikuti skala konsisten.
- Hindari kartu bertumpuk untuk setiap potongan teks, judul kapital kecil berlebihan, metadata yang terlalu kecil, tombol default yang tampak seperti tombol hitam/hijau tidak seragam, dan ruang kosong yang tidak memiliki fungsi.
- Status selalu memakai label teks, bukan warna saja.
- Gunakan ikon konsisten; jangan memakai karakter Unicode acak sebagai pengganti ikon UI.

## 5. Komponen yang harus distandarkan

- Sidebar/top navigation dan active state
- Page header, breadcrumb bila dibutuhkan, primary action
- Card/panel, summary metric, status badge
- Primary, secondary, text, destructive, disabled button
- Input, select, radio, checkbox, helper/error message
- Tabel dan aksi per baris
- Alert, confirmation dialog, toast/feedback
- Empty/loading/error state
- Focus-visible, keyboard navigation, dan target klik
- Layout responsif untuk resize jendela

## 6. Layout dan ukuran

- Admin: desktop-first, gunakan ruang horizontal secara efisien.
- Public: informasi penting dapat dibaca sekilas pada layar sekolah.
- Target pemeriksaan: 1366 px, 1024 px, dan jendela sempit sekitar 390 px.
- Tidak boleh ada horizontal overflow yang tidak disengaja, kontrol bertumpuk, label terpotong, atau form memanjang tanpa hierarki.
- Tabel lebar harus punya strategi eksplisit: overflow terkontrol atau susunan alternatif.

## 7. Perlindungan fungsi

Redesign visual tidak boleh diam-diam mengubah:
- Skema SQLite, lokasi AppData, atau mekanisme migrasi.
- Paket aktif global dan pilihan hari libur.
- Aturan Normal/Khusus dan prioritas kalender.
- Perilaku Salin Jadwal: preview, konfirmasi, sumber/tujuan yang eksplisit.
- Jalur file audio, unggahan audio, backup, atau mesin audio.
- Data yang telah tersimpan.

Setiap tombol wajib terhubung ke tindakan backend yang benar. Konfirmasi tidak menggantikan validasi server. Jangan tampilkan sukses sebelum operasi selesai.

## 8. Urutan redesign wajib

1. **Audit baseline:** inventaris halaman, screenshot, CSS, struktur DOM, form/action, serta pola yang tidak konsisten.
2. **Setujui app shell dan bahasa visual:** layout admin, navigasi, lebar konten, tipografi, palet, spacing, tombol, form, tabel, feedback.
3. **Dashboard Admin:** beranda operasional yang jelas.
4. **Paket Jadwal.**
5. **Editor Jadwal dan Salin Jadwal.**
6. **Konfigurasi.**
7. **Backup/Restore.**
8. **Pemeriksaan/diagnostik.**
9. **Public Dashboard.**
10. **Polish dan regresi.**

Selesaikan dan uji satu modul sebelum memperluas redesign ke modul berikutnya. Hindari patch CSS bertumpuk tanpa audit selector yang sudah ada.

## 9. Kriteria penerimaan

- [ ] Struktur dan hierarki halaman terinspirasi pola RoyalUI namun tetap cocok untuk aplikasi bel.
- [ ] Semua halaman menggunakan app shell dan komponen yang konsisten.
- [ ] Dashboard admin benar-benar halaman operasional, bukan diagnostik.
- [ ] Public dashboard terpisah dari pengelolaan.
- [ ] Form/tabel/tombol/feedback mudah dipahami dan tidak saling menimpa.
- [ ] Aplikasi tetap bisa digunakan tanpa internet.
- [ ] Uji resize pada 1366, 1024, dan sekitar 390 px.
- [ ] Navigasi keyboard/focus dan label kontrol memadai.
- [ ] Tidak ada regresi pada paket, editor, audio, atau database.
- [ ] Pengguna memeriksa tampilan pada PHP Desktop sebelum dinyatakan lulus.

## 10. Status implementasi

Dokumen ini menetapkan **arah baru**. Kode UI yang ada perlu diaudit; jangan menganggap implementasi sebelumnya sebagai final. Label “IMPLEMENTED” hanya berarti kode tersedia. Label “LOCAL TEST PASS” hanya digunakan setelah hasil runtime dilaporkan.


## 11. Public-first dan partial views

### Public area
- Halaman awal aplikasi dengan nama sekolah, tanggal/jam, jadwal yang relevan, kegiatan berikutnya, dan tombol Dashboard Admin.
- Tidak memuat navigasi/form admin.
- CSS/JS public terpisah bila dibutuhkan.

### Admin area
- Tidak menggunakan login/Auth.
- Halaman admin menggunakan layout dan partial PHP native yang konsisten.
- Pemeriksaan runtime menjadi halaman diagnostik, bukan beranda.

### Struktur target bertahap
```text
www/
  index.php                 # entry point ke public area
  public.php                # halaman publik
  diagnostics.php           # pemeriksaan runtime
  layouts/                  # layout public/admin
  partials/                 # navigasi, header, pesan, footer
  views/                    # view per modul
  assets/css/               # public.css dan admin.css
  assets/js/                # public.js dan admin.js
  lib/Database.php
```
Ini target migrasi, bukan perintah memindahkan semua halaman sekaligus. Pertahankan URL lama sampai tiap tahap teruji.

### Folder aset operator
- Target folder persisten: `BelMadrasah/assets/audio` dan `BelMadrasah/assets/logo` di area data pengguna Windows, bukan folder program.
- Sediakan tombol Buka Folder Aset dan instruksi copy-paste.
- Nama file bebas dan dipertahankan.
- Database menyimpan path kanonis aset yang dipilih; UI menampilkan nama dan status ditemukan/hilang.
- HTML file picker standar menyembunyikan path lokal penuh. Audit bridge PHP Desktop terlebih dahulu; jika tidak tersedia, pilih file dari daftar yang dipindai dalam folder aset.
- Audio lama saat ini berada di `%APPDATA%/BelMadrasah/audio` dan direferensikan lewat `bell_events.audio_path`. Jangan ubah skema atau memindahkan file secara massal sebelum ada migrasi aditif yang aman.
- Format audio/gambar hanya dinyatakan didukung setelah diverifikasi pada runtime.

### Perlindungan data
- Database utama tetap di `%APPDATA%/BelMadrasah/data/bel-madrasah.sqlite` dengan fallback `%LOCALAPPDATA%`.
- Jangan menghapus data/file lama saat redesign.
- Uji restart, file hilang, nama file dengan spasi/Unicode, path lama, dan update aplikasi.


## 12. Pratinjau audio langsung dari Editor Jadwal

- Pada kolom File audio di tabel kegiatan, operator dapat menekan **Dengarkan** untuk memeriksa audio yang sedang dipilih sebelum menyimpan perubahan.
- Pemilihan dropdown berubah → pratinjau memakai pilihan terbaru tanpa harus menyimpan baris.
- Pratinjau memakai satu pemutar aktif; memulai pratinjau lain menghentikan pratinjau sebelumnya.
- Status pratinjau memberi umpan balik memuat, sedang diputar, selesai, atau gagal.
- Pratinjau tersedia pula pada formulir Tambah Kegiatan Bel.
- Endpoint pratinjau tidak mencatat kejadian sebagai pemutaran bel terjadwal dan tidak boleh mengubah data jadwal.
- Status: kode diimplementasikan; **belum local test PASS** sampai operator menguji pada PHP Desktop Windows.
