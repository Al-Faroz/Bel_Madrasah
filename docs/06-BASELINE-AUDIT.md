# Fase A — Audit Baseline

**Status:** audit statis awal selesai; audit runtime dan seluruh dependency masih terbuka.  
**Tanggal:** 2026-10-11  
**Metode:** pembacaan file pada default branch repository. Tidak menjalankan PHP Desktop/Windows dari lingkungan ini.

## 1. Hasil audit awal

### Fondasi runtime dan database

- `prototype/phpdesktop/www/lib/Database.php` adalah pusat akses SQLite; database utama ditempatkan di `%APPDATA%/BelMadrasah/data/bel-madrasah.sqlite`, dengan fallback ke `%LOCALAPPDATA%`.
- Kelas database membuat skema dan menjalankan migrasi saat inisialisasi. Perubahan skema harus ditinjau hati-hati karena constructor dipakai oleh banyak halaman.
- `index.php` masih berisi pemeriksaan runtime dan tes persistensi/diagnostik, sementara dashboard operasional ada di `dashboard.php`. Perlu diputuskan apakah halaman pemeriksaan tetap menjadi entry point teknis atau dipisah jelas dari dashboard produk.
- Tidak boleh menghapus database diagnostik maupun database utama hanya untuk merapikan alur.

### Halaman yang teridentifikasi

- `dashboard.php`: dashboard admin dengan ringkasan paket, kegiatan, dan tautan ke modul.
- `packages.php`: daftar, buat/edit, aktivasi paket dan pilihan hari libur.
- `schedule-editor.php`: aturan hari, kegiatan bel, serta alur preview/konfirmasi salin Senin ke Selasa.
- `settings.php`: nama sekolah, operator, dan preferensi volume.
- `backup.php`: pembuatan backup database ke folder AppData.
- `public.php`: dashboard baca-saja yang menyusun jadwal berdasarkan aturan Normal; memberi peringatan jika aturan Khusus ada.
- `assets/app.css`: satu stylesheet sekitar 65 KB dengan beberapa blok gaya yang ditambahkan bertahap. Perlu audit selector duplikat, aturan yang saling menimpa, dan konsistensi komponen sebelum redesign.

## 2. Temuan dan risiko yang perlu ditangani

1. **Entry point belum jelas untuk produk.** `index.php` berorientasi pemeriksaan teknis, sedangkan `dashboard.php` adalah dashboard admin. Tentukan alur pembukaan aplikasi agar pengguna tidak menganggap halaman diagnostik sebagai beranda produk.
2. **CSS tumbuh secara aditif.** Gaya dari beberapa batch ditambahkan di bagian bawah stylesheet; hal ini berisiko menghasilkan override dan perbedaan visual antarlaman. Jangan menambah patch kosmetik lagi sebelum inventarisasi selector dan token.
3. **Navigasi tidak seragam.** Urutan item menu berbeda antara beberapa halaman (contoh settings/backup menempatkan Pemeriksaan lebih awal daripada dashboard/packages). Perlu satu partial/template navigasi bersama atau strategi render bersama.
4. **Aturan kalender belum menjadi sumber jadwal final.** `dashboard.php` dan `public.php` membaca aturan Normal hari ini secara langsung dan melewatkan aturan Khusus. Public dashboard sudah memberi peringatan, tetapi informasi berikutnya belum mencerminkan resolver kalender final.
5. **Status scheduler/audio belum nyata.** Label yang menjelaskan jadwal tersimpan bukan status pemutaran aktual harus dipertahankan sampai mesin scheduler mengirim status runtime.
6. **Backup belum sama dengan backup-and-restore.** Halaman saat ini membuat file backup; restore harus menjadi fase terpisah dengan validasi, backup pra-pemulihan, dan konfirmasi.
7. **Aktivasi paket dan edit paket memerlukan regresi data.** Pastikan constraint satu paket aktif dan perilaku hari awal tetap sesuai, tanpa perubahan data diam-diam.
8. **Salin jadwal berisiko overwrite.** Alur preview/konfirmasi ada, namun harus diuji dengan data dummy untuk memastikan source/target benar, tidak ada perubahan sebelum konfirmasi, dan audio/path dipertahankan.
9. **Validasi keamanan/ketahanan belum diaudit penuh.** Periksa CSRF untuk form POST, otorisasi lokal yang relevan untuk aplikasi desktop, validasi ID milik paket yang dipilih, penanganan error, dan escaping output pada seluruh halaman.
10. **Tidak ada klaim lulus runtime untuk UI terbaru.** Screenshot pengguna menunjukkan layout tidak sesuai ekspektasi. Semua redesign berikutnya perlu uji screenshot pada PHP Desktop yang sebenarnya.

## 3. Keputusan yang dikunci

- PoC PHP Desktop Chrome 130.1 + PHP 8.3 dan SQLite tidak diulang tanpa alasan teknis.
- Lokasi data AppData, file database, dan data pengguna dipertahankan.
- Fitur lama menjadi kandidat reuse setelah audit, bukan dianggap otomatis final.
- RoyalUI adalah referensi visual, bukan dependensi remote runtime.
- Satu halaman/modul dikerjakan dalam satu batch dan diuji sebelum lanjut.
- Jangan memperbaiki tampilan dengan menambahkan lapisan CSS tanpa menghapus/merapikan aturan lama yang berkonflik.

## 4. Temuan audit: audio dan aset

- `bell_events.audio_path` menyimpan path absolut.
- `Database::getAudioStorageDirectory()` membuat `%APPDATA%/BelMadrasah/audio` dengan fallback ke LocalAppData.
- `Database::storeUploadedAudio()` memakai unggahan HTML, membatasi ukuran 50 MB dan ekstensi MP3/WAV/OGG/M4A/AAC, lalu mengganti nama file menjadi token acak. Jadi alur sekarang adalah unggah/salin ke folder internal, bukan memilih referensi file yang sudah disalin operator.
- `schedule-editor.php` mengandalkan `$_FILES['audio_file']`. Jika simpan jadwal gagal sesudah unggahan, file baru yang tidak terpakai dihapus.
- Salin jadwal menyalin referensi `audio_path`, tidak menggandakan file.
- Belum ditemukan workflow atau setting logo pada halaman yang diperiksa.

### Kontrak target aset
- Target: folder persisten `BelMadrasah/assets/audio` dan `BelMadrasah/assets/logo`, dengan copy-paste dan nama file bebas.
- Sebelum implementasi, periksa konfigurasi PHP Desktop dan native bridge. HTML file picker standar tidak mengungkap path lokal penuh; jangan menyimpan fakepath.
- Bila tidak ada bridge native, pilih file dari daftar file yang ditemukan di folder aset.
- Pertahankan referensi audio lama. Migrasi path harus aditif dan tidak menghapus file sumber atau mengubah data massal tanpa backup.

### Audit lanjutan
- [ ] Periksa konfigurasi runtime untuk URL awal dan kemungkinan native file dialog/bridge.
- [ ] Audit seluruh POST action untuk CSRF, validasi ID, escaping, transaksi, dan error handling.
- [ ] Inventarisasi CSS selector duplikat dan navigasi.
- [ ] Audit backup dan rancang restore dengan backup pra-pemulihan.
- [ ] Tetapkan spesifikasi resolver kalender, pasaran Jawa, minggu kalender, scheduler, dan status audio.
- [ ] Siapkan matriks uji runtime Windows dan screenshot baseline.

## 5. Status fase

**Fase A belum dinyatakan selesai sepenuhnya.** Audit awal menemukan area risiko di atas, tetapi pemeriksaan penuh semua method, selector, alur error, dan uji runtime masih harus dilakukan. Langkah berikutnya adalah menuntaskan audit kontrak database dan form/action, lalu mengunci keputusan spesifikasi sebelum membuat ulang app shell.
