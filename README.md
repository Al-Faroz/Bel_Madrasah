# Bel Madrasah

Aplikasi bel otomatis untuk kebutuhan operasional madrasah/sekolah di Windows.

**Status proyek:** PoC runtime `phpdesktop-chrome-130.1-php-8.3`, SQLite lokal, dan audio internal telah dilaporkan berjalan untuk skenario uji awal. Alur utama Backup & Restore juga dilaporkan FIX oleh pengguna pada Windows. Fondasi scheduler dan stream audio internal kini tersedia di repository, tetapi **belum diuji di PHP Desktop Windows**; autoplay, ketepatan waktu, dan perilaku saat minimize/sleep-wake masih harus diverifikasi.

## Tujuan

- Mengelola paket jadwal dan kegiatan bel sekolah.
- Memutar audio lokal secara otomatis sesuai jadwal; fondasi scheduler tersedia, tetapi belum dinyatakan lulus sampai uji PHP Desktop Windows.
- Berjalan lokal tanpa XAMPP, Apache/MySQL terpisah, atau pemutar audio eksternal.
- Menjaga data pengguna tetap ada saat aplikasi diperbarui.
- Menyediakan dashboard admin untuk operator dan dashboard publik untuk status operasional.

## Keputusan teknis PoC

- Runtime uji: PHP Desktop Chrome 130.1 + PHP 8.3.
- Database lokal: SQLite.
- Lokasi database pengguna berada di AppData, terpisah dari folder runtime.
- Hasil uji awal persistensi SQLite, audio MP3 panjang/WAV, konfigurasi/backup dasar, dan paket jadwal dilaporkan PASS untuk skenario yang sudah dicoba. Hasil tersebut tidak otomatis membuktikan fitur produksi lain telah lulus.

## Arah desain UI

Acuan visual: [RoyalUI Free Bootstrap Admin Template](https://github.com/BootstrapDash/RoyalUI-Free-Bootstrap-Admin-Template). Template digunakan sebagai referensi layout, dashboard, navigasi, kartu, form, dan tabel. Aplikasi harus tetap dapat digunakan secara offline; aset yang dibutuhkan saat runtime harus tersedia lokal dan lisensinya ditinjau.

Dokumen desain: [docs/05-UI-UX-MATERIAL-DESIGN.md](docs/05-UI-UX-MATERIAL-DESIGN.md).

## Roadmap pengembangan baru

1. Audit baseline kode, halaman, database, navigasi, dan fungsi yang sudah ada.
2. Tetapkan spesifikasi produk dan kontrak data.
3. Rancang app shell serta sistem UI yang konsisten.
4. Implementasikan dan uji modul satu per satu: dashboard, paket jadwal, editor, konfigurasi, backup/restore, diagnostik, public dashboard.
5. Implementasikan resolver kalender dan prioritas jadwal.
6. Implementasikan scheduler dan pemutaran audio otomatis (**fondasi kode tersedia; uji runtime Windows masih terbuka**).
7. Uji backup/restore, stabilitas, kinerja, dan distribusi Windows.
8. Jalankan uji penerimaan dan dokumentasikan keterbatasan.

**Penting:** Tahap 1–4 sebelumnya merupakan eksperimen kelayakan teknologi/fondasi. Roadmap produk disusun ulang agar pekerjaan tidak tumpang tindih. Data dan fondasi yang sudah berjalan tidak boleh dihapus atau direset hanya demi memulai urutan kerja baru.

## Dokumen acuan

- [Ikhtisar proyek](docs/00-PROJECT-OVERVIEW.md)
- [Roadmap pengerjaan baru](docs/03-IMPLEMENTATION-PLAN.md)
- [Rencana pengujian](docs/04-TEST-PLAN.md)
- [Acuan UI/UX](docs/05-UI-UX-MATERIAL-DESIGN.md)
- [Spesifikasi scheduler dan audio otomatis](docs/07-SCHEDULER-SPEC.md)
- [Pengemasan Windows dan installer](docs/08-WINDOWS-PACKAGING.md)

## Pola kolaborasi

Setiap batch harus fokus, memiliki commit terpisah, dan dilengkapi langkah uji. Label IMPLEMENTED berarti kode tersedia; LOCAL TEST PASS hanya diberikan setelah aplikasi dijalankan pada PHP Desktop di Windows dan hasilnya dilaporkan. Jangan menyatakan rilis siap digunakan di sekolah sebelum fitur inti, sleep/wake, audio, data, dan distribusi diuji langsung.

## Lisensi

Lisensi proyek belum ditentukan.
