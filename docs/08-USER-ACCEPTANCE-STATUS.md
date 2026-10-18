# 08 — Status Penerimaan Berdasarkan Checklist Pengguna

Tanggal pencatatan: 2026-10-18

Dokumen ini mencatat hasil yang dilaporkan langsung oleh pengguna. Status ini bukan hasil klaim pengujian otomatis oleh pengembang.

## 1. Lulus berdasarkan pengujian pengguna

### Lingkungan aplikasi
- [x] Aplikasi PHP Desktop berjalan di Windows tanpa XAMPP.
- [x] Aplikasi dapat ditutup dan dibuka kembali.
- [x] SQLite dapat dibaca dan ditulis tanpa error.
- [x] Aplikasi berjalan tanpa internet.

### Fitur yang sudah dikonfirmasi
- [x] Salin Jadwal berjalan dengan benar.
- [x] Backup berhasil dibuat.
- [x] Restore backup berhasil diuji.
- [x] Logo tampil tanpa latar belakang kotak yang mengganggu.

### Scheduler, audio, dan kalender
- [x] Halaman Public dapat tetap terbuka saat menunggu bel.
- [x] Bel berbunyi otomatis pada waktu jadwal.
- [x] Audio MP3 diputar oleh scheduler.
- [x] Audio WAV diputar oleh scheduler.
- [x] Status pemutaran berubah sesuai kondisi yang diamati.
- [x] Bel yang sama tidak berbunyi dua kali pada satu jadwal.
- [x] Scheduler tetap bekerja setelah aplikasi diminimalkan dan dibuka kembali.
- [x] Paket jadwal aktif memengaruhi jadwal yang digunakan.
- [x] Perubahan jadwal bertahan setelah aplikasi dimulai ulang.
- [x] Kegiatan yang dinonaktifkan tidak ikut berbunyi.
- [x] Tanggal dan waktu WIB berjalan benar.

## 2. Belum diuji oleh pengguna

- [ ] Dua atau lebih kegiatan dengan waktu sama diputar berurutan sesuai urutan yang ditentukan.
- [ ] Penanganan file audio yang referensinya hilang atau tidak dapat dibaca.

## 3. Tindak lanjut Batch 1

1. Audit sumber scheduler, audio-stream, JavaScript player, database playback log, dan resolver.
2. Tambahkan tes otomatis khusus kelanjutan antrean waktu-sama dan deduplikasi.
3. Periksa perilaku ketika audio pertama gagal, agar kegiatan berikutnya pada menit yang sama tetap diproses.
4. Rencanakan uji file hilang/tidak terbaca tanpa merusak jadwal produksi.
5. Minta pengguna menguji hanya skenario yang belum terkonfirmasi di PHP Desktop Windows.

## 4. Batas validasi

- Status di Bagian 1 adalah PASS berdasarkan laporan pengujian pengguna.
- Penambahan file tes ke repository tidak berarti tes itu sudah dijalankan atau lulus.
- Tes otomatis perlu dijalankan pada lingkungan yang mendukung PHP dan PDO SQLite.
- Pemutaran audio berurutan, status runtime, dan perilaku saat file hilang tetap perlu dikonfirmasi pada PHP Desktop Windows.
