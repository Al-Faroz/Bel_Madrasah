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


## 11. Public-first dan partial views (keputusan terbaru)

### Public area
- Menjadi halaman awal aplikasi.
- Fokus pada informasi jadwal dan status yang benar-benar diketahui.
- Menyediakan satu tombol/tautan yang jelas menuju Dashboard Admin.
- Tidak memuat navigasi admin, formulir pengaturan, atau dekorasi berlebihan.
- CSS/JS public boleh dipisah agar ringan dan mudah dipelihara.

### Admin area
- Tidak menggunakan login/Auth pada ruang lingkup saat ini.
- Diakses langsung dari tautan pada public area.
- Semua halaman admin menggunakan layout dan partial views PHP native yang sama.

### Pola PHP native
Target struktur, menyesuaikan hasil audit:
- `layouts/public.php` dan `layouts/admin.php`
- `partials/admin-sidebar.php`, `partials/admin-header.php`, `partials/flash-message.php`, `partials/footer.php`
- `views/<modul>/<halaman>.php`
- `assets/css/public.css`, `assets/js/public.js`, dan aset admin yang terorganisasi
- `lib/Database.php` serta logika bisnis terpisah dari view

Gunakan path `require` yang ditentukan internal aplikasi, bukan input pengguna. Selesaikan aksi POST dan validasi sebelum merender view. Escape semua data yang ditampilkan ke HTML.

### Folder aset operator
Sediakan satu folder akar yang mudah ditemukan dengan subfolder audio dan logo. Nama/path final dipilih setelah audit path pemutar audio yang sekarang. Instruksi di dalam aplikasi harus menjelaskan cara copy-paste file, format yang didukung, penamaan, cara mengganti file yang sedang dirujuk jadwal, serta tindakan saat file tidak ditemukan. Aset harus disimpan di lokasi yang tidak terhapus saat aplikasi diperbarui.

Jangan memindahkan referensi path lama tanpa migrasi aman dan jangan mengklaim format audio didukung sebelum diverifikasi.
