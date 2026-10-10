# 02 — Fondasi Teknis dan Keputusan Arsitektur

## 1. Tujuan dokumen

Mencatat batasan teknis yang sudah disepakati serta hal-hal yang masih harus dibuktikan sebelum implementasi utama. Dokumen ini mencegah kita mengasumsikan bahwa aplikasi PHP desktop otomatis setara dengan aplikasi web PHP biasa.

## 2. Batasan wajib

- PHP adalah bahasa utama logika aplikasi.
- Target akhir adalah aplikasi desktop Windows yang dapat dijalankan pengguna biasa.
- Tidak boleh mewajibkan instalasi XAMPP.
- Tidak boleh mewajibkan operator membuka pemutar musik eksternal.
- Database lokal tidak memerlukan server database terpisah.
- Aplikasi tetap ringan saat tidak sedang memutar audio.
- Jadwal dan audio lokal dapat bekerja tanpa internet.

## 3. Arsitektur konseptual

Komponen logis yang direncanakan:

1. **Antarmuka desktop** — dashboard, pengaturan, editor jadwal, status dan log.
2. **Layanan konfigurasi** — mengelola paket, jadwal, kegiatan, pengaturan, dan file audio.
3. **Penyimpanan lokal** — SQLite sebagai kandidat database.
4. **Mesin penjadwalan** — menentukan jadwal yang berlaku pada tanggal/hari ini, memantau waktu, mencegah pemicu ganda.
5. **Mesin audio internal** — memutar file audio tanpa pemutar eksternal.
6. **Pencatatan kejadian** — menyimpan hasil pemicu dan status pemutaran.
7. **Lapisan distribusi Windows** — menyediakan runtime dan dependensi agar pengguna tidak perlu memasang XAMPP.

Diagram alur konseptual:

`Jam Windows → Mesin Penjadwalan → Resolusi Paket/Hari → Mesin Audio → Perangkat Suara Windows`

Status dari proses tersebut dikirim ke dashboard dan log.

## 4. Hal yang belum diputuskan

Keputusan final harus dibuat setelah proof of concept (PoC):
- Pembungkus/runtime desktop untuk aplikasi PHP.
- Cara menjalankan PHP tanpa instalasi pengguna.
- Library atau komponen audio internal.
- Dukungan MP3 dan WAV serta perilaku audio panjang.
- Integrasi system tray dan auto-start jika dibutuhkan.
- Metode distribusi: portable, installer, atau keduanya.
- Perilaku pemulihan ketika Windows bangun dari sleep.
- Ukuran paket, konsumsi RAM/CPU, dan persyaratan versi Windows.

Jangan mengunci teknologi hanya karena mudah dibuat sebagai halaman web. Uji apakah teknologi tersebut benar-benar memenuhi target desktop mandiri dan audio otomatis.

## 5. Kriteria PoC teknis

PoC awal harus membuktikan:
1. Aplikasi PHP dapat dibuka sebagai aplikasi desktop tanpa XAMPP.
2. Runtime yang diperlukan bisa disertakan atau dipasang secara jelas.
3. SQLite dapat dibaca dan ditulis secara lokal.
4. File audio lokal dapat diputar oleh komponen internal tanpa membuka aplikasi pemutar eksternal.
5. Audio berdurasi panjang tidak terpotong.
6. Aplikasi dapat menunggu jadwal dengan konsumsi CPU yang rendah.
7. Pengulangan pemeriksaan waktu tidak menyebabkan audio diputar ganda.
8. Perilaku minimize/tray tidak menghentikan penjadwalan.
9. Paket dapat dijalankan di komputer Windows sasaran yang tidak memiliki lingkungan pengembangan.

## 6. Prinsip penyimpanan

Data konfigurasi dan database sebaiknya ditempatkan di direktori data pengguna Windows yang dapat ditulis, bukan di dalam direktori aplikasi yang mungkin dilindungi izin Windows. Folder audio harus memiliki aturan yang jelas: apakah file disalin ke folder data aplikasi atau tetap dirujuk dari lokasi pilihan operator. Jika file dipindah atau dihapus, aplikasi harus menampilkan peringatan yang bisa dipahami.

Database dan file audio pengguna tidak boleh terhapus saat aplikasi diperbarui atau dipasang ulang.

## 7. Keandalan audio

- Jadwal dan mesin audio harus dipisahkan secara logis.
- Notifikasi visual Windows tidak menjadi ketergantungan pemutaran audio.
- Kegagalan audio tidak boleh membuat aplikasi diam-diam menganggap kegiatan berhasil.
- Setiap kejadian perlu memiliki status yang bisa dicatat.
- Audio panjang harus dibiarkan selesai kecuali operator secara eksplisit menghentikannya.
- Benturan waktu harus ditangani secara eksplisit, bukan memotong audio tanpa aturan.

## 8. Zona waktu

Jadwal mengikuti jam lokal komputer Windows. Untuk konteks pengguna di Indonesia, konfigurasi komputer harus menggunakan zona waktu yang sesuai, misalnya WIB bila sekolah berada di wilayah WIB. Aplikasi perlu menampilkan jam sistem yang dipakai sehingga perbedaan jam komputer mudah diketahui.

## 9. Keamanan dan pemeliharaan

- Jangan menyimpan rahasia atau kredensial yang tidak dibutuhkan.
- Validasi jalur file dan masukan operator.
- Backup database harus dapat dilakukan sebelum perubahan besar.
- Log harus memiliki batas atau mekanisme pembersihan agar tidak tumbuh tanpa kendali.
- Dependensi dan lisensinya perlu dicatat sebelum distribusi.
