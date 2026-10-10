# Bel Madrasah

Aplikasi bel otomatis untuk kebutuhan operasional madrasah/sekolah di Windows.

**Status proyek:** Tahap 1 — PoC fondasi desktop Windows sedang berjalan.

Bel Madrasah dirancang agar operator dapat mengatur paket jadwal, waktu kegiatan, dan file audio. Saat waktu yang ditentukan tiba, aplikasi menjalankan audio secara otomatis melalui perangkat suara Windows tanpa operator perlu membuka Winamp, VLC, Windows Media Player, atau pemutar musik eksternal lainnya.

## Tujuan utama

- Pemutaran bel dan audio kegiatan sekolah secara otomatis berdasarkan jadwal.
- Ringan ketika menunggu jadwal dan tidak menjalankan pemutar musik eksternal.
- Dapat digunakan secara lokal tanpa koneksi internet untuk fungsi inti.
- Tidak membutuhkan instalasi XAMPP, Apache, atau server database terpisah.
- Pengaturan sederhana agar mudah digunakan operator sekolah.
- Jadwal tersimpan secara lokal dan tetap tersedia setelah aplikasi ditutup atau komputer dimulai ulang.

## Rancangan fitur

- Paket jadwal seperti **Normal**, **Ujian**, **Ramadan**, dan kegiatan khusus lainnya.
- Satu paket jadwal aktif secara global; paket aktif tetap digunakan sampai operator menggantinya.
- Jadwal Senin–Minggu pada setiap paket.
- Pola hari **Normal** atau **Khusus**.
- Hari khusus berdasarkan **pasaran Jawa** atau **minggu kalender dalam bulan**.
- Daftar kegiatan bel yang dapat diubah nama, waktunya, file audionya, dan status aktifnya.
- Pemutar audio internal aplikasi.
- Dashboard status jadwal dan pemutaran.
- Log pemutaran, kegagalan, dan kejadian terlewat.
- Aplikasi dapat diminimalkan ke system tray bila pendekatan desktop yang dipilih mendukungnya.
- Pengaturan serta data disimpan secara lokal.

## Prinsip teknis

PHP adalah bahasa utama aplikasi. Karena targetnya desktop Windows mandiri, bukan aplikasi web yang memerlukan XAMPP, mekanisme runtime desktop, distribusi PHP, dan mesin audio internal akan ditetapkan dan diuji pada tahap fondasi teknis sebelum fitur besar dibangun.

SQLite menjadi kandidat basis data lokal karena tidak membutuhkan server database terpisah. Pemilihan teknologi final harus mempertimbangkan ukuran paket, konsumsi RAM/CPU, kompatibilitas Windows, dukungan audio, serta kemudahan pembaruan.

## Rencana pengerjaan

1. Menetapkan kebutuhan dan keputusan arsitektur.
2. Membuat fondasi aplikasi desktop Windows.
3. Menyiapkan penyimpanan lokal dan pengaturan.
4. Membuat mesin audio internal dan menguji format/durasi audio.
5. Membuat pengelolaan paket jadwal.
6. Membuat editor jadwal harian dan pola hari khusus.
7. Membuat mesin penjadwalan otomatis serta pencegahan pemutaran ganda.
8. Membuat dashboard operator, status, dan log.
9. Menyiapkan paket aplikasi mandiri untuk Windows.
10. Melakukan pengujian, perbaikan, dan optimasi.

Rincian acuan kebutuhan, aturan kalender, arsitektur, tahapan, dan pengujian ada di folder [docs](docs/).

## Pola kerja pengembangan

Pengembangan dilakukan bertahap melalui repository GitHub. Setiap tahap harus memiliki perubahan yang jelas, cara menjalankan, dan kriteria pengujian. Setelah perubahan tersedia di repository, pengembang lokal melakukan pull, menjalankan aplikasi, dan melaporkan hasil pengujian sebelum pekerjaan dilanjutkan.

## Status

Belum dianggap siap digunakan di lingkungan sekolah sampai fungsi inti, perilaku saat Windows aktif/tidur, pemutaran audio, dan paket distribusi Windows diuji secara langsung.

## Lisensi

Belum ditentukan.
