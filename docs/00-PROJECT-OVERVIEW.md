# 00 — Ikhtisar Proyek Bel Madrasah

## 1. Identitas dan tujuan

- **Repository:** `Al-Faroz/Bel_Madrasah`
- **Aplikasi:** Bel Madrasah
- **Target:** aplikasi desktop Windows 10/11
- **Runtime PoC yang telah dicoba:** `phpdesktop-chrome-130.1-php-8.3`
- **Bahasa:** PHP native terstruktur
- **Database:** SQLite lokal
- **Status:** fondasi runtime/database telah dibuktikan berjalan oleh pengguna; pengembangan produk dimulai kembali dengan roadmap yang bersih.

Bel Madrasah membantu operator mengelola paket jadwal sekolah dan audio bel, lalu pada tahap berikutnya menjalankan audio secara otomatis sesuai jadwal tanpa XAMPP, Apache/MySQL terpisah, atau pemutar audio eksternal.

## 2. Keputusan penting setelah PoC

Tahap 1–4 sebelumnya diperlakukan sebagai eksperimen kelayakan teknologi dan fondasi, bukan sebagai urutan implementasi produk yang harus terus ditumpuk. Pengguna melaporkan PHP Desktop berjalan baik, SQLite menyimpan data lintas restart, audio uji berjalan, konfigurasi/backup dasar, serta paket jadwal telah dicoba.

Roadmap produk disusun ulang untuk menghindari pekerjaan saling tumpang tindih. Ini bukan berarti membuang bukti PoC atau menghapus database pengguna. Implementasi yang ada menjadi bahan audit/reuse, bukan otomatis dianggap sebagai rancangan final.

## 3. Keputusan arsitektur UI yang dikunci

1. **Public area adalah halaman awal aplikasi.** Ketika aplikasi dibuka, operator melihat tampilan publik yang sederhana dan informatif.
2. **Dashboard admin diakses dari public area.** Sediakan tautan/tombol yang jelas seperti “Dashboard Admin” atau “Pengaturan Operator”.
3. **Tidak menggunakan login/Auth.** Dashboard admin dapat diakses langsung dari public area; jangan menambahkan halaman login atau sistem akun pada ruang lingkup saat ini.
4. **PHP native memakai partial views.** Gunakan layout dan partial yang di-`require` untuk sidebar/navigasi, header, breadcrumb, pesan status, footer, dan komponen berulang.
5. **Pisahkan layout admin dan public.** Keduanya boleh berbagi helper atau komponen dasar, tetapi public harus sesederhana mungkin dan tidak memuat navigasi admin yang tidak dibutuhkan.
6. **Aset public terpisah.** Gunakan CSS dan JavaScript khusus public bila kebutuhan dan ukurannya berbeda dari dashboard admin.
7. **Folder aset operator.** Sediakan satu lokasi persisten dengan subfolder audio dan logo. Operator tetap copy-paste aset ke folder tersebut, lalu memilih file melalui File Explorer di aplikasi. Nama file bebas; tidak ada pola penamaan wajib. Aplikasi menyimpan referensi file yang dipilih (path/lokasi dan nama file) ke SQLite.
8. **Data aman.*** Lokasi database AppData dan data pengguna tidak dihapus atau dipindahkan hanya demi redesign.

## 4. Prinsip produk

- Offline-first untuk fungsi inti.
- Ringan dan stabil saat menunggu jadwal.
- Data pengguna tetap aman ketika aplikasi diperbarui.
- Public-first: halaman publik adalah layar awal, dashboard admin merupakan area kerja yang dibuka melalui tautan.
- Tiap fitur punya sumber data, aturan, alur, dan kriteria uji yang jelas.
- Jangan membangun scheduler sebelum kontrak data dan resolver jadwal disepakati.
- Jangan menyatakan fitur lulus hanya karena kode tersedia di GitHub.

## 5. Batas ruang lingkup

Termasuk: pengaturan aplikasi, paket jadwal, aturan hari Normal/Khusus, pasaran Jawa dan minggu kalender, kegiatan serta audio lokal, resolver tanggal, scheduler otomatis, status/log, backup dan restore, panduan operator, dan distribusi Windows.

Belum menjadi kebutuhan awal: sinkronisasi cloud, kontrol jarak jauh internet, akun multi-operator kompleks, dan streaming audio.

## 6. Prinsip desain antarmuka

- Gunakan **RoyalUI Free Bootstrap Admin Template** sebagai referensi visual/layout: https://github.com/BootstrapDash/RoyalUI-Free-Bootstrap-Admin-Template
- Referensi adalah inspirasi struktur dashboard, sidebar/navigation, card, tabel, form, dan tipografi; bukan alasan untuk menyalin seluruh template secara membabi buta.
- Sesuaikan implementasi dengan PHP Desktop, penggunaan offline, serta lisensi aset yang dipakai.
- Dashboard admin berorientasi kerja operator; public area berorientasi informasi jadwal/status yang mudah dibaca sekilas.
- Hindari dekorasi kartu berlebihan, teks metadata terlalu kecil, ruang kosong tak terarah, dan tombol dengan gaya berbeda-beda.
- Detail aturan visual berada di [05-UI-UX-MATERIAL-DESIGN.md](05-UI-UX-MATERIAL-DESIGN.md).

## 7. Status yang benar

- **PoC runtime PHP Desktop:** dilaporkan berjalan baik oleh pengguna.
- **Persistensi SQLite dan uji audio awal:** dilaporkan PASS untuk skenario yang sudah dicoba.
- **Fitur paket/editor/UI yang saat ini ada:** belum boleh dianggap desain final atau lulus regresi; harus diaudit terhadap roadmap baru.
- **Scheduler otomatis, resolver kalender final, restore, dan paket distribusi:** belum boleh dianggap selesai tanpa implementasi dan pengujian khusus.

## 8. Aturan kolaborasi

1. Dokumentasi kebutuhan dan roadmap diperbarui lebih dahulu.
2. Audit kode yang ada sebelum menambah/mengubah implementasi.
3. Satu batch fokus pada satu hasil yang jelas.
4. Tiap perubahan mempunyai commit dan checklist uji.
5. Pengguna melakukan pull dan pengujian runtime Windows; screenshot/error digunakan untuk perbaikan.
6. Kode baru tidak boleh merusak penyimpanan AppData, SQLite, audio internal, atau data lama.

## Dokumen audit baseline

Audit awal kode dan risiko pengembangan dicatat di [06-BASELINE-AUDIT.md](06-BASELINE-AUDIT.md). Fase audit masih terbuka sampai pemeriksaan method database, form/action, CSS, navigasi, dan kontrak data diselesaikan.


## Keputusan arsitektur UI yang dikunci

1. **Public area adalah halaman awal aplikasi.** Ketika aplikasi dibuka, operator melihat tampilan publik yang sederhana dan informatif.
2. **Dashboard admin diakses dari public area.** Sediakan tautan/tombol yang jelas seperti “Dashboard Admin”.
3. **Tidak menggunakan login/Auth.** Dashboard admin dapat diakses langsung; jangan menambahkan halaman login atau sistem akun pada ruang lingkup saat ini.
4. **PHP native memakai partial views.** Gunakan layout dan partial yang dipanggil dengan `require` untuk navigasi, header, breadcrumb, pesan status, footer, dan komponen berulang.
5. **Pisahkan layout admin dan public.** Public harus sesederhana mungkin dan tidak memuat navigasi admin yang tidak dibutuhkan.
6. **Aset public terpisah.** Gunakan CSS dan JavaScript khusus public bila diperlukan agar aset/interaksi admin tidak membebani public.
7. **Folder aset operator.** Sediakan satu lokasi mudah ditemukan untuk logo dan audio. Nama folder, subfolder, ekstensi yang didukung, cara penamaan, dan langkah mengganti file harus dijelaskan tertulis di dalam aplikasi.
8. **Data aman.** Lokasi database AppData dan data pengguna tidak dihapus atau dipindahkan hanya demi redesign.

## Prinsip produk tambahan

- Public-first: halaman publik adalah layar awal; dashboard admin merupakan area kerja yang dibuka melalui tautan.
- Offline-first tetap menjadi prinsip utama.
- Pengalaman operator harus mencakup panduan jelas untuk menaruh dan mengganti file audio/logo.
- Detail arsitektur view dan tampilan berada di [05-UI-UX-MATERIAL-DESIGN.md](05-UI-UX-MATERIAL-DESIGN.md).
