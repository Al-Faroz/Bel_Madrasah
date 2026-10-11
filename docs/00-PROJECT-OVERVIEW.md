# 00 — Ikhtisar Proyek Bel Madrasah

## 1. Identitas dan tujuan

- **Repository:** `Al-Faroz/Bel_Madrasah`
- **Aplikasi:** Bel Madrasah
- **Target:** aplikasi desktop Windows 10/11
- **Runtime PoC yang telah dicoba:** `phpdesktop-chrome-130.1-php-8.3`
- **Bahasa:** PHP
- **Database:** SQLite lokal
- **Status:** fondasi runtime/database telah dibuktikan berjalan oleh pengguna; pengembangan produk dimulai kembali dengan roadmap yang bersih.

Bel Madrasah membantu operator mengelola paket jadwal sekolah dan audio bel, lalu pada tahap berikutnya menjalankan audio secara otomatis sesuai jadwal tanpa XAMPP, Apache/MySQL terpisah, atau pemutar audio eksternal.

## 2. Keputusan penting setelah PoC

Tahap 1–4 sebelumnya diperlakukan sebagai **eksperimen kelayakan teknologi dan fondasi**, bukan sebagai urutan implementasi produk yang harus terus ditumpuk. Pengguna melaporkan PHP Desktop berjalan baik, SQLite menyimpan data lintas restart, audio uji berjalan, konfigurasi/backup dasar, serta paket jadwal telah dicoba.

Karena itu, roadmap disusun ulang dari awal untuk menghindari pekerjaan saling tumpang tindih. Ini **bukan** berarti membuang bukti PoC atau menghapus database pengguna. Implementasi yang sudah ada menjadi bahan audit/reuse, bukan otomatis dianggap sebagai rancangan final.

## 3. Prinsip produk

1. Offline-first untuk fungsi inti.
2. Ringan dan stabil saat menunggu jadwal.
3. Data pengguna tetap aman ketika aplikasi diperbarui.
4. UI admin dan tampilan publik dirancang sebagai satu produk yang konsisten.
5. Tiap fitur punya sumber data, aturan, alur, dan kriteria uji yang jelas.
6. Jangan membangun scheduler sebelum kontrak data dan resolver jadwal disepakati.
7. Jangan menyatakan fitur lulus hanya karena kode tersedia di GitHub.

## 4. Batas ruang lingkup

Termasuk: pengaturan aplikasi, paket jadwal, aturan hari Normal/Khusus, pasaran Jawa dan minggu kalender, kegiatan serta audio lokal, resolver tanggal, scheduler otomatis, status/log, backup dan restore, dan distribusi Windows.

Belum menjadi kebutuhan awal: sinkronisasi cloud, kontrol jarak jauh internet, akun multi-operator kompleks, dan streaming audio.

## 5. Prinsip desain antarmuka

- Gunakan **RoyalUI Free Bootstrap Admin Template** sebagai referensi visual/layout: https://github.com/BootstrapDash/RoyalUI-Free-Bootstrap-Admin-Template
- Referensi adalah inspirasi struktur dashboard, sidebar/navigation, card, tabel, form, dan tipografi; bukan alasan untuk menyalin seluruh template secara membabi buta.
- Sesuaikan implementasi dengan PHP Desktop, penggunaan offline, serta lisensi aset yang dipakai.
- Admin dashboard berorientasi kerja operator; public dashboard berorientasi informasi jadwal/status yang mudah dibaca.
- Hindari dekorasi kartu berlebihan, teks metadata terlalu kecil, ruang kosong tak terarah, dan tombol dengan gaya berbeda-beda.
- Detail aturan visual berada di [05-UI-UX-MATERIAL-DESIGN.md](05-UI-UX-MATERIAL-DESIGN.md).

## 6. Status yang benar

- **PoC runtime PHP Desktop:** dilaporkan berjalan baik oleh pengguna.
- **Persistensi SQLite dan uji audio awal:** dilaporkan PASS untuk skenario yang sudah dicoba.
- **Fitur paket/editor/UI yang saat ini ada:** belum boleh dianggap desain final atau lulus regresi; harus diaudit terhadap roadmap baru.
- **Scheduler otomatis, resolver kalender final, restore, dan paket distribusi:** belum boleh dianggap selesai tanpa implementasi dan pengujian khusus.

## 7. Aturan kolaborasi

1. Dokumentasi kebutuhan dan roadmap diperbarui lebih dahulu.
2. Audit kode yang ada sebelum menambah/mengubah implementasi.
3. Satu batch fokus pada satu hasil yang jelas.
4. Tiap perubahan mempunyai commit dan checklist uji.
5. Pengguna melakukan pull dan pengujian runtime Windows; screenshot/error digunakan untuk perbaikan.
6. Kode baru tidak boleh merusak penyimpanan AppData, SQLite, audio internal, atau data lama.


## Dokumen audit baseline

Audit awal kode dan risiko pengembangan dicatat di [06-BASELINE-AUDIT.md](06-BASELINE-AUDIT.md). Fase audit masih terbuka sampai pemeriksaan method database, form/action, CSS, navigasi, dan kontrak data diselesaikan.
