# 05 — Standar UI/UX Material Design Bel Madrasah

**Status:** ACUAN DESAIN AKTIF  
**Cakupan:** seluruh antarmuka aplikasi desktop, termasuk Admin Dashboard, Public Dashboard/status operasional, Paket Jadwal, Editor Jadwal, Konfigurasi, Backup, dan dialog/pesan.  
**Urutan kerja wajib:** acuan → roadmap → implementasi bertahap → pengujian lokal.

## 1. Tujuan

Seluruh tampilan Bel Madrasah harus terasa seperti satu produk yang matang, bukan kumpulan halaman yang masing-masing memiliki gaya berbeda. Gunakan prinsip Material Design sebagai sistem desain yang konsisten: hierarki jelas, ruang bernapas, komponen yang dapat dikenali, status mudah dipahami, interaksi dapat diprediksi, dan responsif pada ukuran jendela berbeda.

Material Design di sini adalah acuan visual dan interaksi; bukan kewajiban memasang framework JavaScript besar atau membuat aplikasi bergantung pada internet. Prioritaskan HTML, CSS, dan JavaScript ringan yang sesuai dengan PHP Desktop.

## 2. Dua area pengalaman

### 2.1 Admin Dashboard

Ditujukan untuk operator yang mengelola aplikasi dan jadwal. Harus desktop-first, tetap nyaman saat jendela dipersempit.

- Navigasi utama konsisten pada semua halaman, dengan item aktif yang jelas.
- Header halaman memuat judul, konteks singkat, dan tindakan utama bila relevan.
- Dashboard berfokus pada status operasional: paket aktif, kegiatan berikutnya, waktu sistem, status audio, serta peringatan penting.
- Halaman pengelolaan menggunakan pola yang konsisten untuk daftar, formulir, aksi, konfirmasi, pesan sukses, dan pesan gagal.
- Data padat seperti tabel jadwal harus mudah dipindai, memiliki alignment dan format waktu yang konsisten.
- Tindakan berisiko—mengganti paket aktif, menimpa jadwal tujuan, atau memulihkan data—harus memiliki penjelasan dan konfirmasi yang memadai.

### 2.2 Public Dashboard / tampilan status operasional

Halaman ini ditujukan untuk dilihat sekilas di layar sekolah dan bukan untuk mengelola data.

- Fokus pada paket/jadwal yang berlaku, jam saat ini, kegiatan berikutnya, serta status audio yang relevan.
- Informasi penting harus terbaca dari jarak pandang wajar; minimalkan kontrol administratif dan detail teknis.
- Status berjalan, siap, peringatan, dan gagal dibedakan dengan teks serta indikator visual—jangan mengandalkan warna saja.
- Layout harus tetap terbaca di layar kecil maupun layar besar; pada layar besar, gunakan ruang secara terarah, bukan memperbesar semua elemen tanpa hierarki.
- Jika public dashboard belum dipisahkan sebagai route/halaman tersendiri, catat sebagai pekerjaan desain dan arsitektur UI; jangan berpura-pura bahwa dashboard publik sudah tersedia.

## 3. Bahasa visual

### 3.1 Tipografi

- Gunakan satu keluarga sans-serif sistem yang konsisten, dengan fallback yang aman untuk Windows.
- Tetapkan skala untuk judul halaman, judul bagian, isi, label, helper text, metadata, dan teks tabel.
- Hindari terlalu banyak teks kapital penuh, letter-spacing berlebihan, dan teks kecil untuk informasi operasional.
- Gunakan angka tabular untuk jam dan nilai yang perlu dibandingkan.

### 3.2 Warna dan status

- Gunakan token CSS terpusat untuk warna teks, permukaan, latar, border, primary, secondary, focus, success, warning, dan error.
- Pilih satu warna primer yang tenang dan identitas visual sekolah yang konsisten; warna aksen digunakan seperlunya.
- Jaga kontras teks dan komponen. Setiap status harus memiliki label teks atau ikon selain warna.
- Sediakan status hover, focus-visible, active, disabled, loading, empty, success, dan error secara konsisten.
- Jangan memakai gradient, bayangan, atau warna cerah sebagai dekorasi berlebihan. Gunakan elevasi seperlunya untuk membedakan lapisan.

### 3.3 Bentuk, ruang, dan elevasi

- Gunakan skala spacing, radius, border, dan shadow yang terbatas serta konsisten.
- Panel dan kartu memiliki fungsi informasi yang jelas; hindari menempatkan setiap potongan teks di dalam kartu terpisah.
- Terapkan grid dan lebar konten yang teratur. Hindari halaman yang terlalu renggang atau terlalu padat.
- Hindari pergeseran layout saat pesan validasi muncul atau saat isi halaman berubah.

## 4. Komponen bersama

Bangun dan gunakan pola bersama untuk:

- App shell, brand/header, navigasi, breadcrumb bila dibutuhkan, dan footer.
- Page header dengan judul, deskripsi, dan primary action.
- Card/panel, status chip, indikator status, dan ringkasan statistik.
- Tombol primary, secondary, text/tertiary, destructive, ikon, dan loading/disabled state.
- Form field, label, helper text, validasi inline, pilihan radio/checkbox, select, dan file picker.
- Tabel, empty state, pesan hasil, alert/warning, dialog konfirmasi, serta pagination jika diperlukan.
- Focus ring keyboard dan target klik yang cukup besar.

Sebelum mengubah halaman satu per satu, buat token dan komponen CSS dasar agar perbaikan global tidak perlu diduplikasi.

## 5. Layout dan responsivitas

- Admin: desktop-first dengan navigasi dan tabel yang tetap berguna pada jendela lebih sempit.
- Public/status: content-first, menonjolkan informasi operasional dan dapat diproyeksikan atau dilihat sekilas.
- Pada lebar kecil, grid turun secara teratur, tombol tidak saling bertumpuk, label tidak terpotong, dan tabel menggunakan strategi overflow/stacking yang disengaja.
- Uji setidaknya lebar desktop 1366 px, laptop 1024 px, dan jendela sempit 390 px. Karena ini desktop app, pastikan juga resize jendela PHP Desktop tidak membuat elemen saling menimpa.
- Hormati preferensi reduced motion bila animasi ditambahkan. Jangan menggunakan animasi yang mengganggu pemantauan jam atau status.

## 6. Aksesibilitas dan interaksi

- Semua kontrol mempunyai label yang jelas dan dapat digunakan dengan keyboard.
- Focus-visible harus terlihat.
- Gunakan elemen HTML semantik dan urutan heading yang benar.
- Pesan sukses/gagal menjelaskan apa yang terjadi dan tindakan berikutnya; hindari pesan teknis mentah jika dapat diterjemahkan.
- Validasi dilakukan di sisi server untuk aturan data; validasi UI hanya membantu pengguna, bukan pengganti.
- Konfirmasi diberikan sebelum operasi destruktif atau penggantian data.
- Jam, status audio, dan informasi operasional penting tidak boleh bergantung pada animasi saja.

## 7. Perlindungan fungsi

Redesign UI tidak boleh mengubah tanpa keputusan terpisah:
- Skema database dan lokasi penyimpanan.
- Aturan paket aktif global.
- Aturan pola hari Normal/Khusus dan prioritas kalender.
- Pemilihan hari libur pada paket baru serta perilaku data paket lama.
- Perilaku Salin Jadwal: Senin sebagai sumber, Selasa sebagai tujuan, mode mengganti seluruh kegiatan Selasa, dengan pratinjau dan konfirmasi.
- Path file audio yang tersimpan, mesin audio, scheduler, dan aturan backup.
- Perilaku yang sudah dilaporkan PASS oleh pengguna.

Setiap perubahan tampilan harus tetap terhubung ke aksi backend yang benar. Jangan membuat tombol dekoratif yang tidak berfungsi atau menampilkan status sukses sebelum operasi benar-benar berhasil.

## 8. Urutan implementasi UI yang wajib

1. **Audit baseline:** inventarisasi halaman, layout, komponen, navigasi, form, tabel, dan fungsi POST/GET; catat bagian yang belum menjadi dashboard publik tersendiri.
2. **Design tokens & app shell:** rapikan token CSS, tipografi, spacing, surface, tombol, form, status, navigasi, dan responsivitas bersama.
3. **Admin Dashboard:** prioritaskan dashboard utama sebagai ringkasan operasional, bukan halaman diagnostik yang membingungkan sebagai UI final.
4. **Paket Jadwal:** rapikan daftar, form paket, status aktif, ringkasan hari libur, dan konfirmasi aktivasi.
5. **Editor Jadwal:** rapikan pemilih paket/hari, aturan hari, tabel kegiatan, audio, validasi, pratinjau, dan Salin Jadwal tanpa mengubah logika.
6. **Konfigurasi & Backup:** konsistensi form, status database, backup, dan penjelasan risiko.
7. **Public Dashboard/status:** definisikan tujuan dan route nyata, pisahkan tampilan status dari kontrol admin, lalu implementasikan sesuai arsitektur yang ada.
8. **Polish & regression:** responsivitas, keyboard/focus, empty/error/success states, konsistensi antarhalaman, dan pemeriksaan fungsi.

## 9. Kriteria penerimaan visual

- [ ] Semua halaman memakai token dan komponen dasar yang sama.
- [ ] Navigasi aktif dan hierarki halaman mudah dikenali.
- [ ] Admin Dashboard menampilkan status operasional yang relevan dan tidak didominasi informasi diagnostik mentah.
- [ ] Public Dashboard/status jelas terpisah dari pengelolaan admin, atau status keterpisahannya didokumentasikan jika belum diimplementasikan.
- [ ] Semua tombol terlihat sesuai prioritas dan menjalankan aksi yang dijanjikan.
- [ ] Form memiliki label, helper/error state, dan status simpan yang jelas.
- [ ] Tabel jadwal mudah dibaca dan tidak menyebabkan layout rusak saat jendela diubah ukurannya.
- [ ] Tampilan diuji pada 1366 px, 1024 px, dan 390 px.
- [ ] Focus keyboard dan kontras dasar dapat digunakan.
- [ ] Fitur paket, hari libur, editor, audio, dan Salin Jadwal tetap berfungsi.
- [ ] Hasil visual diperiksa pada runtime Windows lokal; pemeriksaan kode saja tidak cukup untuk menyatakan lulus.

## 10. Aturan status pekerjaan

- **DOC READY:** acuan dan roadmap sudah diperbarui.
- **IMPLEMENTED:** perubahan kode tersedia di repository.
- **CODE REVIEWED:** kode diperiksa, termasuk perubahan yang berpotensi regresi.
- **LOCAL TEST PASS:** pengguna telah menjalankan aplikasi dan melaporkan hasil lulus.
- Jangan menyamakan IMPLEMENTED atau CODE REVIEWED dengan LOCAL TEST PASS.
