# 04 — Rencana Pengujian Bel Madrasah

## 1. Prinsip

Pengujian harus dilakukan pada runtime **PHP Desktop Chrome 130.1 + PHP 8.3 di Windows**, bukan hanya dari browser atau pemeriksaan kode. Bukti PASS terdahulu berlaku untuk skenario yang benar-benar dilaporkan, bukan otomatis untuk fitur yang baru ditambahkan atau didesain ulang.

## 2. Riwayat PoC yang dilaporkan pengguna

- [x] PHP Desktop dapat dijalankan pada Windows 11 64-bit.
- [x] Uji persistensi SQLite diagnostik: marker tetap sama setelah aplikasi ditutup dan dibuka kembali.
- [x] Database aplikasi utama dilaporkan siap setelah restart.
- [x] Audio MP3 panjang dan WAV dilaporkan berhasil pada uji awal.
- [x] Navigasi dasar, simpan konfigurasi, backup awal/kedua dilaporkan berhasil.
- [x] Pengelolaan paket jadwal dasar dilaporkan berhasil.

**Batas:** hasil tersebut adalah catatan historis berdasarkan laporan pengguna. Ini bukan bukti bahwa seluruh UI terbaru, editor jadwal, resolver kalender, scheduler otomatis, restore, atau distribusi telah lulus.

## 3. Sebelum uji setiap batch

- [ ] Jalankan `git pull` dan pastikan selesai tanpa konflik.
- [ ] Tutup lalu buka ulang aplikasi PHP Desktop.
- [ ] Pastikan paket dan jadwal lama tetap ada.
- [ ] Buat backup database sebelum menguji perubahan data.
- [ ] Catat halaman, langkah, hasil yang diharapkan, hasil aktual, dan screenshot/error.
- [ ] Jangan memakai jadwal produksi untuk uji yang menimpa data; gunakan paket percobaan.

## 4. Uji UI dan navigasi

### App shell dan dashboard admin
- [ ] Dashboard admin menjadi halaman utama yang jelas.
- [ ] Navigasi konsisten dan halaman aktif ditandai dengan benar.
- [ ] Menu Pemeriksaan membuka halaman diagnostik, bukan dashboard.
- [ ] Tidak ada tautan mati atau navigasi yang mengarah ke halaman salah.
- [ ] Tidak ada tombol dekoratif yang tidak melakukan tindakan.
- [ ] Jendela bisa di-resize tanpa elemen bertumpuk.

### Paket Jadwal
- [ ] Daftar paket lama tampil dan status aktif benar.
- [ ] Buat/edit paket percobaan; validasi nama kosong/duplikat jelas.
- [ ] Pilihan hari libur tersimpan dan tampil konsisten.
- [ ] Pola awal otomatis mengikuti hari libur paket; pesan status tidak menyebut Senin–Jumat jika paket memakai konfigurasi lain.
- [ ] Aktivasi paket meminta konfirmasi dan hanya mengaktifkan satu paket.
- [ ] Restart tidak menghilangkan perubahan.

### Editor Jadwal
- [ ] Panduan status menerangkan tiga tingkat: paket aktif, aturan hari aktif, dan kegiatan bel aktif.
- [ ] Setiap kartu aturan menampilkan status aturan serta jumlah kegiatan aktif dibanding total kegiatan.
- [ ] Kolom tabel menjelaskan dengan tegas nama kegiatan, file audio, dan status kegiatan.
- [ ] Filter hari membantu menemukan kartu aturan yang sedang diedit tanpa mencampur hari lain.
- [ ] Uji bahwa jadwal yang diaktifkan pada paket, aturan, dan kegiatan tampil sesuai pada Publik.
- [ ] Pilih paket/hari; aturan yang tampil cocok.
- [ ] Tambah/edit/nonaktifkan/hapus kegiatan sesuai dukungan fitur.
- [ ] Tombol Hapus menampilkan konfirmasi inline di dalam tabel tanpa `window.confirm()`; tombol Batal tidak mengubah data.
- [ ] Konfirmasi hapus menyebut nama kegiatan, dan setelah berhasil pesan menyebut nama kegiatan serta hari/pola yang sesuai.
- [ ] Setelah hapus satu kegiatan, hanya baris tersebut yang hilang; aturan hari dan kegiatan lain tetap tersimpan.
- [ ] Tabel kegiatan terbaca pada ukuran jendela target; input, status, tombol, dan panel konfirmasi tidak saling bertumpuk.
- [ ] Kolom waktu di Editor Jadwal menampilkan format 24 jam `HH:MM` (tanpa AM/PM) dan menolak nilai di luar `00:00–23:59`.
- [ ] Waktu dan urutan divalidasi.
- [ ] Setelah menyimpan atau menghapus kegiatan, filter tetap menampilkan hari yang sedang diedit; pola tidak tampak hilang hanya karena filter kembali ke Senin.
- [ ] Jika validasi simpan kegiatan gagal, pesan kesalahan tampil dan filter tetap pada hari yang sedang diedit.
- [ ] File audio tersimpan pada lokasi aplikasi yang benar.
- [ ] Edit tanpa memilih file audio baru mempertahankan file lama.
- [ ] Salin Jadwal menampilkan preview yang cocok dengan sumber.
- [ ] Konfirmasi menyatakan data tujuan yang akan diganti.
- [ ] Setelah salin, data tujuan cocok dengan preview; hari lain tidak berubah.
- [ ] Tidak ada perubahan jadwal yang terjadi sebelum konfirmasi.

### Konfigurasi dan backup
- [ ] Nama madrasah/operator dan volume tersimpan setelah restart.
- [ ] Backup menghasilkan file non-kosong yang dapat ditemukan.
- [ ] Backup dapat diperiksa sebagai database SQLite yang valid.
- [x] Alur Backup & Restore utama diuji langsung oleh pengguna pada PHP Desktop Windows dan dilaporkan FIX tanpa kendala.
- [ ] Uji negatif Restore: file kosong/rusak, skema tidak cocok, folder tidak dapat ditulis, dan rollback ketika penggantian gagal (belum diklaim PASS karena hasil tiap skenario belum dilaporkan).
- [ ] Kegagalan backup memberi pesan yang bisa dipahami.

### Dashboard Publik
- [ ] Nama sekolah, tanggal, jam, paket dan jadwal yang tampil benar.
- [ ] Logo tersimpan tampil pada header Publik, header Admin, dan merek sidebar Admin; jika file hilang/tidak didukung, fallback tampil jelas.
- [ ] Pratinjau logo di Konfigurasi sesuai dengan logo yang sedang tersimpan.
- [ ] Jadwal berikutnya dihitung dari data dan waktu yang benar.
- [ ] Status audio tidak mengklaim audio diputar jika tidak ada status runtime.
- [ ] Tampilan tetap jelas saat layar/jendela diubah ukurannya.
- [ ] Public dashboard tidak menampilkan kontrol admin yang tidak diperlukan.

## 5. Uji kalender dan aturan

- [ ] Hari Senin sampai Minggu.
- [ ] Pasaran Jawa Legi, Pahing, Pon, Wage, Kliwon.
- [ ] Minggu kalender ke-1 sampai ke-5 dan minggu terakhir.
- [ ] Awal/akhir bulan ketika minggu kalender tidak penuh.
- [ ] Pergantian bulan dan tahun.
- [ ] Aturan Khusus mengesampingkan Normal sesuai spesifikasi.
- [ ] Jika tidak ada aturan khusus cocok, fallback Normal.
- [ ] Konflik aturan terdeteksi dan tidak memilih lebih dari satu aturan untuk kejadian yang sama.

## 6. Uji scheduler dan audio otomatis

- [ ] Jalankan `php tests/ScheduleResolverTest.php`; seluruh fixture kalender dan urutan kegiatan harus PASS.
- [ ] Jalankan `php tests/SchedulerPlaybackLogTest.php`; deduplikasi log dan transisi status harus PASS.
- [ ] Zona waktu Windows pada komputer target sesuai WIB/Asia/Jakarta; jam Public, waktu scheduler, dan waktu log harus selaras.
- [ ] Audio mulai pada waktu uji dan keluar melalui perangkat audio yang diharapkan.
- [ ] Dua kegiatan pada waktu sama diputar berurutan sesuai `sort_order`, tidak tumpang tindih.
- [ ] Satu kejadian tidak diputar dua kali setelah refresh atau navigasi Public/Admin.
- [ ] Audio panjang selesai tanpa dipotong.
- [ ] Kejadian lebih lambat dari toleransi 30 detik dicatat `missed` dan tidak diputar terlambat.
- [ ] Kegiatan tanpa audio dicatat `skipped`; file hilang/rusak atau autoplay yang ditolak dicatat `failed`.
- [ ] Status/log sesuai keadaan aktual: menunggu, memulai, diputar, selesai, gagal, terlewat.
- [ ] Preferensi volume 0–100 diterapkan pada elemen audio internal.
- [ ] Restart tidak memicu ulang kejadian yang sudah diproses.
- [ ] Perilaku saat aplikasi diminimalkan/ditutup didokumentasikan.
- [ ] Sleep/wake dan perubahan jam Windows diuji.
- [ ] Konfigurasi autoplay pada paket PHP Desktop yang benar-benar dipakai diperiksa; audio harus bisa berjalan tanpa dialog yang menghambat jadwal.

## 7. Backup, restore, dan stabilitas

- [ ] Backup tetap valid ketika aplikasi berjalan.
- [ ] Dari daftar backup lokal, tombol “Periksa & pratinjau” menampilkan hasil pemeriksaan SQLite sebelum restore.
- [ ] Restore menolak konfirmasi jika checkbox persetujuan belum dicentang.
- [ ] Restore backup valid mengganti data aktif dengan isi backup yang dipilih.
- [ ] Setelah restore berhasil, data paket, aturan, kegiatan, dan konfigurasi cocok dengan backup yang dipulihkan.
- [ ] Backup pra-restore otomatis terbentuk dan tetap muncul di daftar backup.
- [ ] File kosong, bukan SQLite, tidak utuh, atau bukan database Bel Madrasah ditolak tanpa mengubah database aktif.
- [ ] Jika proses penggantian gagal, aplikasi mencoba mengembalikan backup pra-restore dan memberi pesan hasil rollback.
- [ ] Folder data/backup yang tidak dapat ditulis menghasilkan pesan yang jelas.
- [ ] File audio hilang/rusak ditangani tanpa crash.
- [ ] CPU/RAM idle dan selama audio diukur pada komputer target.

## 8. Penerimaan visual

- [ ] UI dibandingkan dengan arah desain RoyalUI dan dokumen [05-UI-UX-MATERIAL-DESIGN.md](05-UI-UX-MATERIAL-DESIGN.md).
- [ ] Uji ukuran 1366 px, 1024 px, dan jendela sempit sekitar 390 px.
- [ ] Judul, body, label, metadata terbaca dan hierarkinya jelas.
- [ ] Spacing, tombol, field, tabel, alert, dan status konsisten.
- [ ] Tidak ada scroll horizontal tak sengaja, tombol tumpang tindih, atau label terpotong.
- [ ] Focus keyboard terlihat dan semua kontrol punya label.
- [ ] Screenshot hasil uji disimpan sebagai bukti sebelum status UI ditandai PASS.

## 9. Catatan temuan

Untuk setiap bug, catat:
1. Nama halaman/fitur.
2. Langkah reproduksi.
3. Hasil yang diharapkan.
4. Hasil aktual.
5. Screenshot atau pesan error.
6. Apakah data berubah atau rusak.

Jangan menyimpulkan penyebab hanya dari screenshot; reproduksi dan audit kode diperlukan sebelum mengubah backend.
## Audit Editor Jadwal → Publik (2026-10-24)

- [ ] Panduan Editor Jadwal menyatakan dengan benar bahwa scheduler memeriksa jadwal aktif secara berkala dan memakai pemutar audio internal.
- [ ] Jika tidak ada paket aktif, Publik menampilkan `Belum ada paket jadwal aktif`.
- [ ] Jika paket aktif tetapi tidak ada kegiatan aktif untuk tanggal tersebut, Publik menampilkan `Tidak ada kegiatan aktif untuk tanggal ini`.
- [ ] Jika kegiatan hari ini sudah habis, Publik menampilkan `Tidak ada kegiatan tersisa hari ini` dan tidak menyebut pola Normal secara keliru.
- [ ] Setelah mengubah nama, jam, audio, atau status satu kegiatan di Editor dan menyimpan, Publik mencerminkan perubahan setelah dimuat ulang.
- [ ] Periksa paket aktif dan pemilihan Normal/Khusus pada tanggal yang sesuai; jangan mengubah atau menghapus data jadwal lain saat pengujian.

Catatan: butir di atas adalah kriteria uji; belum dianggap lulus hanya karena perubahan kode sudah dibuat. Validasi akhir dilakukan di PHP Desktop Windows.
