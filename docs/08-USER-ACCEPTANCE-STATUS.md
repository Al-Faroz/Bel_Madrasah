# 08 — Status Penerimaan Berdasarkan Checklist Pengguna

Tanggal pembaruan: 2026-10-27

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

## 2. Tambahan yang sudah lulus setelah catatan awal

- [x] Validasi waktu kegiatan menolak duplikasi saat tambah/edit dan menjaga data lama; `ScheduleEventValidationTest.php` dilaporkan PASS pada PHP CLI.
- [x] Validasi referensi audio dan penanganan file hilang/kosong/tidak didukung; tes otomatis dan skenario runtime PHP Desktop dilaporkan PASS pada checklist `docs/04-TEST-PLAN.md`.
- [x] Pratinjau MP3/WAV dari tabel kegiatan dan formulir Tambah Kegiatan Bel; seluruh checklist dilaporkan PASS pada 2026-10-27.
- [x] Redesain ringkasan aturan hari, modal tambah/edit, reset pesan validasi, serta status aktif/nonaktif aturan; pengguna melaporkan PASS pada 2026-10-27.
- [x] Tes `DayRuleStatusTest.php` dilaporkan PASS; perubahan status tidak mengubah pola/kegiatan dan aksi lintas paket ditolak.

## 3. Tindak lanjut yang masih terbuka

- [ ] Uji PHP Desktop Windows untuk pemulihan scheduler setelah jendela kembali fokus/terlihat, termasuk minimize/restore dan sleep/wake. Jadwal yang melewati toleransi 30 detik harus tercatat `missed`, bukan diputar terlambat. Checklist rinci ada di `docs/04-TEST-PLAN.md`.
- [ ] Uji negatif Restore pada PHP Desktop Windows menggunakan database/backup uji: file kosong/rusak, skema tidak cocok, folder tidak dapat ditulis, dan rollback saat penggantian gagal.
- [ ] Selesaikan hanya checklist UI/runtime lain yang masih terbuka di `docs/04-TEST-PLAN.md`; jangan menyimpulkan seluruh aplikasi lulus dari satu batch PASS.
- [ ] Pengemasan/installer Windows masih ditunda sesuai keputusan pengguna; jangan memulai kembali tanpa instruksi.

## 4. Batas validasi

- Status di Bagian 1 adalah PASS berdasarkan laporan pengujian pengguna.
- Penambahan file tes ke repository tidak berarti tes itu sudah dijalankan atau lulus.
- Tes otomatis perlu dijalankan pada lingkungan yang mendukung PHP dan PDO SQLite.
- Pemutaran audio berurutan, status runtime, dan perilaku saat file hilang tetap perlu dikonfirmasi pada PHP Desktop Windows.
