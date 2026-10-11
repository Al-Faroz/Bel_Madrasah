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

## 3. Keputusan arsitektur produk yang dikunci

1. **Public area menjadi halaman awal aplikasi.** Pemeriksaan teknis bukan beranda produk.
2. **Dashboard Admin diakses dari Public.** Tombol “Dashboard Admin” terlihat jelas.
3. **Tanpa login/Auth.** Admin dapat diakses langsung.
4. **PHP native memakai layout dan partial views.** Include menggunakan path internal yang ditentukan kode aplikasi.
5. **Layout Public dan Admin dipisah.** Public ringkas; admin memiliki navigasi dan komponen kerja yang konsisten.
6. **CSS/JS Public dipisahkan** jika berguna untuk menjaga halaman awal tetap ringan.
7. **Folder aset persisten** memiliki subfolder audio dan logo; operator dapat membukanya dari aplikasi lalu copy-paste file ke sana.
8. **Nama file bebas.** Tidak ada pola penamaan wajib.
9. **Database menyimpan referensi aset yang dipilih.** UI harus mendeteksi file hilang dan menyediakan pemilihan ulang.
10. **Data aman.** Database AppData, data pengguna, dan hasil PoC tidak dihapus atau dipindahkan demi redesign.

### Kontrak teknis pemilihan aset

HTML file picker standar tidak memberikan path lokal penuh yang dapat dipercaya. Audit konfigurasi/bridge PHP Desktop sebelum menggunakan pemilih native Windows. Jika bridge tidak tersedia, operator copy-paste file ke folder aset lalu memilih dari daftar file yang ditemukan aplikasi. Jangan menyimpan nilai fakepath sebagai referensi. Path audio lama harus tetap didukung sampai ada migrasi aditif yang aman.

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


## Keputusan arsitektur produk yang dikunci

1. Public area adalah halaman awal aplikasi; pemeriksaan teknis bukan beranda.
2. Dashboard admin diakses dari public area melalui tautan/tombol yang jelas.
3. Tidak menggunakan login/Auth; admin dapat diakses langsung.
4. Gunakan layout dan partial PHP native melalui require dengan path include yang ditentukan aplikasi.
5. Pisahkan layout public dan admin. Public ringkas dan tidak memuat navigasi pengelolaan.
6. CSS/JS public dipisahkan bila bermanfaat.
7. Sediakan folder aset operator persisten dengan subfolder audio dan logo, terpisah dari folder program.
8. Nama file bebas, tanpa pola penamaan wajib.
9. Database menyimpan referensi aset yang dipilih; file yang hilang harus terdeteksi dan dapat dipilih ulang.
10. Database AppData, data pengguna, dan hasil PoC tidak dihapus atau dipindahkan demi redesign.

## Kontrak pengelolaan aset

- Operator membuka folder aset dari aplikasi, copy-paste file ke subfolder audio/logo, lalu memilih aset yang digunakan.
- Audit konfigurasi PHP Desktop sebelum memutuskan apakah path asli dapat diperoleh melalui native bridge. HTML file picker biasa tidak memberikan path lokal penuh yang dapat dipercaya.
- Jika tidak ada bridge native, UI menampilkan daftar file valid yang ditemukan di folder aset untuk dipilih.
- Path audio lama harus tetap didukung selama transisi; jangan memindahkan atau menghapus file lama tanpa backup dan migrasi yang tervalidasi.
- Ekstensi yang ditampilkan sebagai didukung harus diverifikasi terhadap validasi dan kemampuan runtime.

## Prinsip produk tambahan

- Public-first: halaman publik adalah layar awal; dashboard admin merupakan area kerja yang dibuka melalui tautan.
- Offline-first tetap menjadi prinsip utama.
- Pengalaman operator harus mencakup panduan jelas untuk menaruh dan mengganti file audio/logo.
- Detail arsitektur view dan tampilan berada di [05-UI-UX-MATERIAL-DESIGN.md](05-UI-UX-MATERIAL-DESIGN.md).
