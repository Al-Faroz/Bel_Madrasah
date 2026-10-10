# Tahap 1 — PoC PHP Desktop untuk Windows

Folder ini berisi prototipe awal untuk memeriksa apakah antarmuka PHP dapat berjalan di jendela desktop Windows tanpa XAMPP.

**Status:** PoC desktop dan uji persistensi SQLite Tahap 1A dilaporkan PASS oleh pengguna pada Windows 11 64-bit. Pengujian lanjutan tetap diperlukan sebelum runtime ditetapkan final.

## Kandidat runtime

PoC ini menggunakan **PHP Desktop Chrome 130.1 untuk Windows** sebagai kandidat awal. Rilis upstream menyebut Windows 10+ dan PHP 8.3. Runtime belum disalin ke repository karena paket binari desktop cukup besar dan perlu diambil dari rilis upstream resmi.

- Repositori: https://github.com/cztomczak/phpdesktop
- Rilis: https://github.com/cztomczak/phpdesktop/releases

## Cara mencoba di Windows

1. Unduh paket **PHP Desktop Chrome 130.1 for Windows** dari halaman Releases resmi.
2. Ekstrak paket ke folder lokal yang dapat ditulis, misalnya `C:\BelMadrasah-PoC\runtime\`.
3. Dari repository ini, buka folder `prototype/phpdesktop/www/`.
4. Salin isi folder `www/` tersebut ke folder `www/` pada paket PHP Desktop yang sudah diekstrak. Untuk menghindari kehilangan file bawaan, buat salinan cadangan folder `www` terlebih dahulu.
5. Jalankan executable PHP Desktop yang disertakan dalam paket (umumnya `phpdesktop-chrome.exe`).
6. Halaman **Bel Madrasah — Pemeriksaan Fondasi** seharusnya terbuka sebagai jendela desktop.
7. Periksa versi PHP, SAPI, dukungan SQLite, dan tes audio dengan memilih file MP3/WAV lokal yang memang tersedia di komputer.
8. Catat hasil uji menggunakan formulir di `docs/04-TEST-PLAN.md`.

> Nama executable dan isi paket dapat berbeda antarversi. Jangan menghapus file runtime bawaan; cukup ganti isi folder aplikasi `www/`.

## Tahap 1A — Tes persistensi SQLite

Prototipe sekarang menyediakan panel **Uji simpan dan baca database**. Panel membuat database diagnostik terpisah di folder data pengguna Windows:

`%APPDATA%\\BelMadrasah\\diagnostics\\storage-test.sqlite`

Jika `APPDATA` tidak tersedia, aplikasi mencoba `LOCALAPPDATA`. Aplikasi tidak sengaja menaruh database di folder runtime/program.

1. Jalankan aplikasi dan pastikan status **DATABASE SIAP**.
2. Tekan **Simpan data uji**. Catat nilai **Marker tersimpan** dan **Waktu simpan**. Pesan harus menyatakan `PASS`.
3. Tutup jendela aplikasi sepenuhnya.
4. Jalankan kembali executable PHP Desktop.
5. Tekan **Baca data tersimpan**.
6. Pastikan marker dan waktu simpan sama dengan yang dicatat sebelum aplikasi ditutup. Jangan tekan Simpan lagi sebelum nilai lama dicocokkan, karena tombol itu membuat marker baru.
7. Bila muncul `FAIL`, catat seluruh pesan error dan lokasi database yang ditampilkan.

Tes ini memeriksa pembuatan database, operasi tulis/baca, dan persistensi setelah aplikasi ditutup. Ini belum merupakan database jadwal produksi.

**Hasil uji lokal yang dilaporkan:** DATABASE SIAP; Simpan data uji berhasil; setelah restart marker dan waktu sama; tidak ada error. Status tes persistensi diagnostik: **PASS**.

## Tahap 2 — Database aplikasi utama dan audio internal

Tahap 2 menambahkan `www/lib/Database.php` dan panel **Database aplikasi utama**. Database utama berada di `%APPDATA%\\BelMadrasah\\data\\bel-madrasah.sqlite` (fallback `%LOCALAPPDATA%`), terpisah dari database diagnostik `diagnostics/storage-test.sqlite`.

Skema versi 1 menyiapkan tabel `app_meta`, `app_settings`, `schedule_packages`, `day_rules`, `bell_events`, dan `playback_log`. Inisialisasi bersifat idempoten (`CREATE TABLE IF NOT EXISTS`) dan dilakukan dalam transaksi. Uji tulis/baca menggunakan TEMP table dan rollback, sehingga tidak menambahkan data uji permanen. Ini baru fondasi skema, bukan editor jadwal yang sudah berfungsi.

### Cara uji Tahap 2
1. Ambil perubahan terbaru: `git pull origin main`.
2. Salin kembali isi `prototype/phpdesktop/www/` ke folder `www/` runtime PHP Desktop seperti saat Tahap 1. Jangan menimpa folder data `%APPDATA%\\BelMadrasah`.
3. Jalankan ulang aplikasi. Panel **Database aplikasi utama** harus menunjukkan `SKEMA SIAP`, versi `1`, dan enam tabel inti.
4. Tutup aplikasi sepenuhnya, buka kembali. Pastikan status tetap `SKEMA SIAP` dan lokasi database tetap sama.
5. Pilih file MP3 panjang (idealnya 3–5 menit) atau audio kegiatan yang boleh dipakai untuk tes. Putar dari awal sampai selesai; jangan hanya melihat kontrol berjalan—pastikan audio terdengar dan tidak terpotong.
6. Ulangi dengan WAV. Jika ada file yang gagal, catat format, durasi, ukuran, pesan aplikasi, dan apakah aplikasi tetap responsif.
7. Jangan menguji dengan file penting yang tidak boleh terputus. Scheduler otomatis, tray, sleep/wake, serta pemilihan format audio final belum menjadi bagian yang sudah lulus.

**Status Tahap 2:** implementasi awal tersedia; menunggu hasil uji lokal. Jangan menandai PASS hanya berdasarkan kode.

## Hasil pengujian Tahap 2

**Status: PASS berdasarkan laporan pengguna.** Hasil yang dilaporkan: database aplikasi PASS; setelah restart tetap PASS; MP3 panjang PASS; WAV PASS; tidak ada error/catatan tambahan. Belum ada detail durasi/ukuran file yang dicatat. Pengujian file hilang/rusak, audio device terputus, pemadaman paksa, scheduler, tray, dan sleep/wake masih diperlukan.

## Apa yang diperiksa prototipe

- Halaman PHP dirender di jendela desktop.
- Versi PHP dan jenis SAPI dapat ditampilkan.
- Ekstensi PDO SQLite / SQLite3 terdeteksi.
- Database SQLite diagnostik dapat dibuat, ditulis, dan dibaca ulang setelah restart.
- File audio lokal dapat dipilih dan dicoba diputar melalui elemen audio yang berada di dalam aplikasi, bukan melalui pemutar eksternal.
- Jam komputer ditampilkan sebagai informasi diagnostik.

## Batasan penting

- Tes audio ini hanya pemeriksaan awal kemampuan browser tertanam. Ini belum membuktikan penjadwalan otomatis, pemutaran tepat waktu, audio panjang, system tray, atau konsumsi sumber daya idle.
- Jika MP3 gagal tetapi WAV berhasil, simpan pesan/hasil tersebut; jangan mengonversi file atau menganggap semua codec didukung.
- Pengujian harus dilakukan dengan speaker/headset yang benar-benar terhubung.
- Jangan menggunakan audio penting sebagai tes pertama. Gunakan file pendek yang tidak sensitif.
- Runtime PHP Desktop tidak di-commit ke repository. Tahap pengemasan aplikasi akan menentukan cara distribusi final.

## Laporan hasil yang dibutuhkan

Kirimkan:
- Versi Windows dan arsitektur (misalnya Windows 11 64-bit).
- Apakah jendela aplikasi berhasil terbuka.
- Versi PHP dan status PDO SQLite yang terlihat.
- Hasil tes MP3 dan WAV (jika keduanya tersedia).
- Perkiraan penggunaan RAM/CPU dari Task Manager saat jendela diam selama beberapa menit.
- Screenshot jika ada tampilan kosong atau pesan error.
