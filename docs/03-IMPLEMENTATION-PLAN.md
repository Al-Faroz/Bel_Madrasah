# 03 — Roadmap Pengerjaan Baru

## Aturan status

- **DOC READY:** kebutuhan dan urutan kerja terdokumentasi.
- **AUDIT:** kode dan dependensi diperiksa, belum berarti benar.
- **IMPLEMENTED:** kode tersedia di repository.
- **LOCAL TEST PASS:** pengguna telah menjalankan skenario pada PHP Desktop dan melaporkan hasilnya.
- PoC yang pernah PASS hanya membuktikan skenario PoC terkait; tidak otomatis meluluskan fitur produksi.

## Keputusan reset

Tahap 1–4 sebelumnya adalah PoC/fondasi untuk membuktikan PHP Desktop Chrome 130.1 + PHP 8.3, SQLite, audio internal, konfigurasi/backup dasar, dan paket jadwal bisa berjalan. Hasil itu dipertahankan sebagai bukti teknis. Roadmap produk dimulai kembali dari Fase A, tanpa mengulang eksperimen yang sudah terbukti dan tanpa menganggap seluruh UI/fitur lama sudah benar.

## Keputusan arsitektur yang dikunci

- Public area menjadi entry point aplikasi; pemeriksaan teknis bukan beranda.
- Dashboard admin diakses langsung dari public area; tidak ada login/Auth.
- Gunakan PHP native layouts/partial views melalui require, pisahkan layout public dan admin.
- Public punya CSS/JS terpisah jika dibutuhkan.
- Folder aset persisten berisi subfolder audio dan logo. Operator dapat membuka folder dari aplikasi dan copy-paste file; nama file bebas.
- Aplikasi harus menyediakan pemilihan aset yang benar-benar menunjuk file di folder aset, menyimpan referensi persisten di SQLite, dan mendeteksi file yang hilang.
- Native browser file picker tidak boleh dianggap memberi path lokal penuh. Audit bridge PHP Desktop terlebih dahulu; bila tidak tersedia, pilih dari daftar file yang ditemukan di folder aset.
- Pertahankan database dan referensi path lama. Migrasi harus aditif dan tidak menghapus aset lama.

## Roadmap eksekusi yang diperbarui

### Fase A — Audit dan kontrak teknis
1. Audit entry point/runtime config PHP Desktop.
2. Audit seluruh method Database, schema/migrasi, form POST, validasi, backup, dan referensi audio.
3. Audit CSS/JS serta navigasi; catat baseline sebelum redesign.
4. Tetapkan kontrak path aset dan kompatibilitas path audio lama.
**Kriteria selesai:** tidak ada asumsi kritis yang belum diperiksa; risiko dan keputusan tercatat di audit.

### Fase B — Fondasi arsitektur
1. Buat entry point public tanpa menghapus halaman diagnostik.
2. Susun layout public/admin dan partial PHP native.
3. Susun CSS tokens/komponen bersama; hilangkan CSS aditif yang berkonflik secara terukur.
4. Sediakan struktur folder aset persisten dan cara membuka lokasinya dari UI.
**Kriteria selesai:** navigasi, include path, lokasi data, dan kontrak aset konsisten.

### Fase C — Public area
1. Tampilan jadwal publik sederhana dan terbaca.
2. Akses jelas ke Dashboard Admin.
3. Informasi jadwal tidak disamakan dengan status audio real-time.
**Kriteria selesai:** entry point mengarah ke public, tanpa menu admin yang tidak relevan.

### Fase D — Admin shell dan modul
Urutan: D1 app shell/partial; D2 Dashboard; D3 Paket Jadwal; D4 Editor Jadwal; D5 Konfigurasi/aset; D6 Backup/Restore; D7 Diagnostik; D8 polish dan responsivitas.
Setiap modul diselesaikan, direview, dan diuji terpisah sebelum modul berikutnya.

### Fase E — Mesin operasional dan rilis
Resolver kalender Normal/Khusus → scheduler/audio dan log status → restore aman → hardening → paket distribusi dan panduan operator.

### Aturan kerja
- Satu batch memiliki satu hasil yang jelas dan commit terpisah.
- Jangan menambah patch CSS sebelum selector terkait diaudit.
- Jangan menyebut fitur “lulus” hanya karena kodenya tersedia.
- Pengguna menguji runtime PHP Desktop pada Windows; hasilnya dicatat sebagai LOCAL TEST PASS hanya setelah dilaporkan.
- Jangan mereset database, memindahkan AppData, atau menghapus data/file lama demi merapikan struktur.



## Status checkpoint — 2026-10-13

- **Fase A / audit statis:** diperluas dan dicatat di `docs/06-BASELINE-AUDIT.md`. Konfigurasi runtime PHP Desktop yang benar-benar terpasang, pengujian Windows, resolver kalender, dan desain restore masih terbuka.
- **Fase B / D1 Admin shell:** kode layout dan partial bersama sudah diimplementasikan pada commit [`448a0d2`](https://github.com/Al-Faroz/Bel_Madrasah/commit/448a0d275084f57805fefba2024db2b01329c77a).
- **Fase D / Dashboard Admin pilot:** markup dashboard dipisah menjadi view dan menggunakan layout baru. Statusnya **IMPLEMENTED**, belum **LOCAL TEST PASS**.
- Pemeriksaan PHP CLI dan smoke test memakai data simulasi lulus pada lingkungan kerja, tetapi tidak menggantikan pengujian PHP Desktop Windows dengan database SQLite asli.
- **Checkpoint wajib:** uji dashboard pada runtime target terlebih dahulu. Setelah hasil dicatat, lanjutkan migrasi satu modul per batch; modul lain belum dianggap menggunakan layout baru.

## Checkpoint lanjutan — 2026-10-13

- **Navigasi Admin → Publik:** ditemukan satu tautan dashboard yang masih memakai `target="_blank"`; atribut tersebut diubah menjadi `target="_self"` pada commit [`d14c614`](https://github.com/Al-Faroz/Bel_Madrasah/commit/d14c614e10fed10e74ffd52156257e0f3a65d98e). Pemeriksaan pencarian kode tidak menemukan lagi `target="_blank"` pada branch utama. Perilaku runtime PHP Desktop tetap perlu diverifikasi.
- **D3 Paket Jadwal:** controller `packages.php` kini menyiapkan data dan operasi lalu menggunakan layout bersama. Markup dipindahkan ke `views/admin/packages.php`; navigasi dan footer berasal dari partial/layout Admin.
- Perubahan D3 hanya refaktor struktur presentasi. Logika penyimpanan, aktivasi, pembuatan paket, pilihan hari libur, dan data SQLite dipertahankan. Tidak ada operasi penghapusan atau migrasi data.
- Commit D3: [`03f0920`](https://github.com/Al-Faroz/Bel_Madrasah/commit/03f09206c8d98010cb0757927f991350eec49b29) menambahkan view; [`99203a6`](https://github.com/Al-Faroz/Bel_Madrasah/commit/99203a6708f3b8f2e6e0752716c2edde3a612696) memigrasikan controller.
- Status: **IMPLEMENTED**, belum **LOCAL TEST PASS**. Pengguna meminta pengerjaan dilanjutkan tanpa menunggu uji dashboard; karena itu pekerjaan berlanjut, tetapi pengujian pada PHP Desktop Windows tetap tercatat sebagai belum dilakukan.
- **Batch berikutnya:** D4 — migrasi Editor Jadwal ke layout Admin bersama, dengan pemeriksaan khusus agar seluruh alur edit/penyimpanan tetap utuh.

## Checkpoint D4 — Editor Jadwal memakai layout bersama (2026-10-13)

- Controller `schedule-editor.php` sekarang memproses data dan aksi lalu memanggil `layouts/admin.php`.
- Markup halaman dipisahkan ke `views/admin/schedule-editor.php`; header, navigasi, dan footer berasal dari partial Admin bersama.
- Commit view: [`b148b44`](https://github.com/Al-Faroz/Bel_Madrasah/commit/b148b446830ddebcf8449968f836406f6e643147).
- Commit controller/layout: [`fda7c51`](https://github.com/Al-Faroz/Bel_Madrasah/commit/fda7c51a7d46a19c569c81f26e113888dc3c9d6e).
- Pemeriksaan struktur statis memastikan controller Editor Jadwal tidak lagi merender dokumen HTML sendiri dan view tidak lagi membawa header/nav/footer duplikat.
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Belum ada pengujian PHP Desktop Windows atau verifikasi interaksi formulir setelah refaktor.
- **Catatan pekerjaan berikutnya:** Editor Jadwal masih memiliki input upload audio lama. Itu belum diubah dalam refaktor ini; harus diselaraskan dengan keputusan aset persisten (operator copy-paste file ke folder aset lalu memilih file dari daftar, nama file bebas) pada batch Konfigurasi/Aset. Jangan menganggap alur audio baru sudah selesai.
- **Batch berikutnya:** D5 — audit/migrasi Konfigurasi dan Aset, termasuk kontrak folder persisten dan cara memilih audio/logo tanpa menghapus atau mengganti referensi path lama.

## Checkpoint D5 — Konfigurasi dan aset persisten (2026-10-13)

- Halaman Konfigurasi kini menggunakan layout Admin bersama; preferensi sekolah/operator/volume tetap disimpan melalui `app_settings`.
- Folder persisten dibuat di `%APPDATA%/BelMadrasah/assets/audio` dan `%APPDATA%/BelMadrasah/assets/logo` (fallback `%LOCALAPPDATA%`). UI menampilkan lokasi folder, jumlah file yang dikenali, petunjuk copy-paste ke File Explorer, dan tombol salin lokasi.
- Editor Jadwal kini memilih audio dari daftar file di folder aset. Tidak ada lagi input upload HTML pada formulir kegiatan. File audio tidak digandakan; database menyimpan path absolut. Referensi audio lama dipertahankan kecuali operator memilih file aset baru atau menghapus referensinya secara eksplisit.
- Pengaturan logo disimpan sebagai key `school_logo_path` di tabel key-value `app_settings`; tidak diperlukan perubahan skema tabel. Logo yang dipilih ditampilkan di header Admin dan Publik melalui data URI lokal yang dibatasi pada tipe gambar yang dikenal dan ukuran maksimal 5 MB.
- Commit terkait: [`4cbb1b9`](https://github.com/Al-Faroz/Bel_Madrasah/commit/4cbb1b930578793df0a37f159a61bddf3616e0df), [`3bf871d`](https://github.com/Al-Faroz/Bel_Madrasah/commit/3bf871d0def751e589bfa4ba72175e5b2b518827), [`23c61c0`](https://github.com/Al-Faroz/Bel_Madrasah/commit/23c61c0fff18b1f1325e482ca15ba2fc4aa1c0d4), [`bfa3e38`](https://github.com/Al-Faroz/Bel_Madrasah/commit/bfa3e38d998facea9c0f779b654bbd45131bbe1a), [`f74cad0`](https://github.com/Al-Faroz/Bel_Madrasah/commit/f74cad0ef82055a85e878be612a291e62f64eb47), [`389d3a3`](https://github.com/Al-Faroz/Bel_Madrasah/commit/389d3a3b3ca1c97a0cfc9d9a8d76002e767a052f), [`fc8d4ed`](https://github.com/Al-Faroz/Bel_Madrasah/commit/fc8d4ed03e5a83178375fce0819ecbba08eed658), [`24be67c`](https://github.com/Al-Faroz/Bel_Madrasah/commit/24be67cae16a4b987b859d0e0e124cfe0b2e8039), [`f685df1`](https://github.com/Al-Faroz/Bel_Madrasah/commit/f685df138b0c44f35fa7859ff426f3aaadb3143f), [`75ab969`](https://github.com/Al-Faroz/Bel_Madrasah/commit/75ab9693f6270c7a0ca89d4e7cc0f993723988b0), [`29684c8`](https://github.com/Al-Faroz/Bel_Madrasah/commit/29684c8c98fbd8f61ae501c0740c21702c72281b), [`da617c1`](https://github.com/Al-Faroz/Bel_Madrasah/commit/da617c1d54aac07bc75631756e70d6595db54a02), [`1180de9`](https://github.com/Al-Faroz/Bel_Madrasah/commit/1180de9cdc4140e5fd2bb1ab3fcfe8154cb6e62f), [`216e86f`](https://github.com/Al-Faroz/Bel_Madrasah/commit/216e86f8f112cb70833f9d4a2101f2fe019037e9).
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Belum diuji pada PHP Desktop Windows; belum ada klaim bahwa clipboard, rendering logo, atau pemilihan audio sudah lulus runtime.
- Pada checkpoint 2026-10-13, folder dibuka manual melalui salin path karena bridge/native shell belum diverifikasi. Perubahan 2026-10-14 menambahkan tombol pembuka File Explorer berbasis fungsi exec Windows dengan fallback salin path; hasil runtime masih belum diverifikasi.
- **Batch berikutnya:** D6 — audit dan migrasi Backup/Restore. Sebelum restore diimplementasikan, rancang validasi integritas SQLite dan backup pra-pemulihan; jangan menimpa database aktif tanpa pratinjau/konfirmasi.

## Checkpoint D6 — Backup dan Restore (2026-10-13)

- Halaman Backup kini memakai layout Admin bersama dan menampilkan database aktif, lokasi backup, daftar file SQLite, ukuran, serta waktu modifikasi.
- Restore menggunakan dua langkah: **Periksa & pratinjau** (validasi integritas `PRAGMA integrity_check`, tabel wajib, dan versi skema), lalu konfirmasi eksplisit sebelum pemulihan.
- Sebelum mengganti database aktif, `Database::restoreFromBackup()` membuat backup pra-pemulihan. Jika penggantian atau inisialisasi gagal, metode mencoba mengembalikan file pra-pemulihan dan membuka ulang koneksi.
- Daftar restore hanya memuat file `.sqlite` yang berada langsung di folder backup aplikasi. File dari luar dapat disalin ke folder tersebut; file yang bukan database Bel Madrasah akan ditolak pada pemeriksaan.
- Commit: [`10877bc`](https://github.com/Al-Faroz/Bel_Madrasah/commit/10877bc772ef9d2117055f4851a396798662fdf5), [`2be0719`](https://github.com/Al-Faroz/Bel_Madrasah/commit/2be07196c7c4ac99237b09092f9958396e0f815d), [`296f776`](https://github.com/Al-Faroz/Bel_Madrasah/commit/296f7766fd2f75dd90f4b5f5e6057b5cc09ead70), [`4c98edf`](https://github.com/Al-Faroz/Bel_Madrasah/commit/4c98edf2bdec3367872f0c8d271077715762bc6e).
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Pemulihan database adalah operasi berisiko dan belum diuji dengan SQLite asli di PHP Desktop Windows. Sebelum penggunaan produksi, uji backup valid, file rusak, skema tidak cocok, kegagalan copy, dan pemulihan dari backup pra-pemulihan.
- **Batch berikutnya:** D7 — migrasi Diagnostik ke layout bersama, mempertahankan seluruh pengujian teknis yang sudah ada.

## Checkpoint D7 — Diagnostik memakai layout bersama (2026-10-13)

- Controller `diagnostics.php` tetap menjalankan pemeriksaan runtime, SQLite/persistensi, dan uji audio yang sudah ada; dokumen HTML dan navigasi dipindahkan ke `layouts/admin.php` serta `views/admin/diagnostics.php`.
- Layout memakai koneksi `$appDatabase` yang sudah dibuat halaman diagnostik bila tersedia, agar tidak membuka koneksi aplikasi kedua hanya untuk membaca logo.
- Commit: [`611fb9e`](https://github.com/Al-Faroz/Bel_Madrasah/commit/611fb9e9229feacd6051be38858c2623556b298c), [`b6037bd`](https://github.com/Al-Faroz/Bel_Madrasah/commit/b6037bd08b108492eac880d163801763581a533d), [`cdd8612`](https://github.com/Al-Faroz/Bel_Madrasah/commit/cdd8612b59dd57d6af552c252e52148c52540e42).
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Uji diagnostik dan pemutaran audio harus dijalankan kembali pada PHP Desktop Windows setelah salinan `www/` diperbarui.
- **Batch berikutnya:** D8 — audit CSS yang sudah ada dan perapian responsivitas terukur; hindari menambah override tanpa menghapus/menyatukan aturan yang bertumpuk.

## Checkpoint D8 — Audit CSS dan polish awal (2026-10-13)

- Inventarisasi statis `assets/app.css`: 65,279 karakter / 912 baris, 683 blok aturan sederhana yang terdeteksi, 450 selector unik, dan 155 selector yang muncul lebih dari sekali. Angka ini bukan bukti semua duplikasi salah; beberapa aturan berada di media query atau memang override responsif.
- Karena belum ada screenshot regresi dari runtime Windows, selector lama tidak digabung/dihapus secara otomatis. Pendekatan pertama hanya menambahkan gaya untuk selector baru yang sebelumnya tidak ada: panel lokasi aset, daftar backup, pratinjau restore, dan penyesuaian grid untuk layar sempit.
- Commit: [`ba7df73`](https://github.com/Al-Faroz/Bel_Madrasah/commit/ba7df73497227eda98641203bf648a62ce5d8a82).
- Status **IMPLEMENTED — POLISH AWAL**; D8 belum tuntas. Konsolidasi CSS lama dan verifikasi visual masih menunggu screenshot/runtime test agar aturan responsif yang valid tidak terhapus.
- Setelah baseline screenshot tersedia, lanjutkan audit selector per konteks media query, hilangkan override yang benar-benar redundan, lalu lanjut ke Fase E: resolver kalender → scheduler/audio → hardening dan paket distribusi.

## Checkpoint E1 — Resolver kalender Normal/Khusus (2026-10-13)

- Ditambahkan `lib/ScheduleResolver.php` sebagai resolver bersama untuk tanggal lokal: menghitung pasaran Jawa dari anchor Jumat Legi 17 Agustus 1945, menentukan ordinal kemunculan hari dalam bulan, memilih aturan Normal/Khusus, dan menyusun kegiatan aktif berdasarkan waktu.
- Aturan Khusus menggantikan Normal jika tepat satu aturan cocok. Jika lebih dari satu aturan Khusus cocok pada tanggal yang sama, resolver memilih Normal sebagai fallback dan menandai konflik agar UI dapat memperingatkan operator; resolver tidak menggabungkan dua jadwal khusus secara diam-diam.
- Dashboard Admin dan Tampilan Publik kini menggunakan resolver yang sama serta menampilkan pasaran/pola kalender. Ini baru pemilihan jadwal untuk informasi layar; belum ada scheduler atau pemutaran audio otomatis.
- Anchor pasaran diverifikasi pada kalender daring: 17 Agustus 1945 = Jumat Legi ([ALMNK](https://almnk.com/kalender/masehi/tanggal/17-agustus-1945), [Ki-Demang](https://ki-demang.com/almanak_jawa/konversi/masehi/1945/agustus/17)).
- Commit: [`6216207`](https://github.com/Al-Faroz/Bel_Madrasah/commit/62162075ef0c62068c2c268693a31f85410b0ef8), [`6a271c6`](https://github.com/Al-Faroz/Bel_Madrasah/commit/6a271c63b58d6cd0b2f4c33c2f5fe6429a6f2b7a), [`0703c4e`](https://github.com/Al-Faroz/Bel_Madrasah/commit/0703c4e23ca3ed2e68f773f2188ea050bdf8ef8f), [`067278d`](https://github.com/Al-Faroz/Bel_Madrasah/commit/067278d512b82613be32ae1daf3eb6bebd46ef3f), [`a418914`](https://github.com/Al-Faroz/Bel_Madrasah/commit/a41891435a7038d7473e5be7901674628f5254be), [`e78c021`](https://github.com/Al-Faroz/Bel_Madrasah/commit/e78c021ebd35106f9cee8ce76666abd8d1f68210), [`712649e`](https://github.com/Al-Faroz/Bel_Madrasah/commit/712649ebf7a2fb7cfc7a2072f775062bcd23e4ad), [`4093987`](https://github.com/Al-Faroz/Bel_Madrasah/commit/40939878e66d3b2bd9760348dcfc1d4c89b0b71f), [`7eb478f`](https://github.com/Al-Faroz/Bel_Madrasah/commit/7eb478fba9943c029c980c12f2349025f5959f2b).
- Fixture regresi tersedia di `tests/ScheduleResolverTest.php` untuk anchor pasaran, siklus harian, aturan khusus tunggal, konflik fallback Normal, dan kecocokan Minggu ke-5. Fixture ini belum dijalankan terhadap repo/runtime target.
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Perhitungan tanggal dan konflik harus diuji dengan `php tests/ScheduleResolverTest.php` pada PHP CLI yang memiliki PDO SQLite, lalu di PHP Desktop sebelum scheduler memakai hasil resolver.
- **Batch berikutnya:** E2 — scheduler/audio otomatis dan status operasional, dengan penanganan audio yang hilang, keterlambatan tick, dan pemulihan setelah aplikasi minimisasi/restart.

## Checkpoint UI Admin — 2026-10-14

- Referensi RoyalUI resmi diperiksa langsung pada partial sidebar dan navbar.
- **D1 shell baru: IMPLEMENTED** — sidebar navigasi, topbar workspace, indikator menu aktif, SVG lokal, serta stylesheet Admin terpisah dari public.
- **Status visual/runtime: BELUM DIVERIFIKASI.** Perubahan belum diuji di PHP Desktop Chrome 130.1 pada Windows dan belum ada screenshot pascaperubahan.
- **Tidak boleh menganggap D2–D8 selesai.** Langkah berikutnya tetap memeriksa dashboard hasil shell baru dan menguji seluruh link/layout di runtime target sebelum migrasi visual modul berikutnya.
- Detail temuan dan commit ada di docs/06-BASELINE-AUDIT.md.

## Checkpoint UI Admin lanjutan — 2026-10-14

### Perubahan kode yang sudah masuk repository

- **D1 / shell:** sidebar dan topbar bersama; judul topbar kini menunjukkan halaman yang sedang dibuka, bukan mengulang nama aplikasi di semua halaman.
- **D2 / Dashboard:** hierarki ringkasan, metadata, panel, dan ikon lokal dirapikan.
- **D3 / Paket Jadwal:** intro tidak lagi menjadi kartu besar terpisah; ringkasan paket aktif, badge, dan tombol aksi dibuat lebih konsisten.
- **D4 / Editor Jadwal:** panel aturan, tombol simpan/salin, status konfirmasi, dan tabel kegiatan diberi gaya Admin yang lebih padat. Struktur POST dan operasi database tidak sengaja diubah pada batch styling ini.
- **D5 / Konfigurasi aset:** tombol **Buka Folder Audio** dan **Buka Folder Logo** ditambahkan. Helper hanya menerima path folder aset yang diperoleh dari Database, membuka Explorer di Windows jika fungsi exec tersedia, dan mengembalikan fallback salin path jika tidak dapat menjalankannya. Tombol folder memakai aksi POST tersendiri agar tidak ikut menyimpan/mengubah preferensi konfigurasi.
- **D6 / Backup-restore** dan **D7 / Diagnostik:** panel, badge, path panjang, tabel, dan pesan status dirapikan.
- **Public dashboard:** ikon simbol diganti dengan SVG inline lokal. Tidak ada CDN atau font ikon baru.
- **Pembersihan navigasi CSS:** aturan `.main-nav` lama yang membentuk navigasi horizontal di `app.css` dihapus setelah shell bersama dipastikan menjadi satu-satunya sumber markup navigasi. Gaya sidebar kini dikelola oleh `admin.css`; tidak ada aturan `.main-nav` tersisa di `app.css`.
- **Responsivitas:** menu Admin pada lebar sempit tetap dua kolom; padding judul halaman tidak kembali membesar pada breakpoint 480 px.

### Commit penting

- Shell/navigasi: [02a1f26](https://github.com/Al-Faroz/Bel_Madrasah/commit/02a1f2676af8db15adad8e2819ea606af9309a26)
- Judul halaman dan Dashboard: [dec39f3](https://github.com/Al-Faroz/Bel_Madrasah/commit/dec39f356bdffb3eb940893c2b2cb031f9dad48c)
- Paket Jadwal: [d713d36](https://github.com/Al-Faroz/Bel_Madrasah/commit/d713d368e53c08e4ec7aa2ee326c2cf3ee70cfd6)
- Editor Jadwal: [2c5c11b](https://github.com/Al-Faroz/Bel_Madrasah/commit/2c5c11b76ba12d03853d5ded19cf1dd997fb7333)
- Pembuka folder aset: [0d6b771](https://github.com/Al-Faroz/Bel_Madrasah/commit/0d6b771f06d2f587e3978430db9cb4eefb98b9c1)
- Backup/Diagnostik: [54a39f3](https://github.com/Al-Faroz/Bel_Madrasah/commit/54a39f3d87497686b89299d46676ff2e54c4801f)
- Public icons dan responsivitas: [65b411f](https://github.com/Al-Faroz/Bel_Madrasah/commit/65b411f38abb70399f0353cb7cca857e9db63ca6)
- Hapus CSS navigasi horizontal lama: [821e3b3](https://github.com/Al-Faroz/Bel_Madrasah/commit/821e3b38ca5909ab2d346a988da4ad979b6a543f)

### Status dan batas verifikasi

- **IMPLEMENTED:** perubahan kode di atas tersedia di branch main.
- **Pemeriksaan statis terbatas:** kurung blok CSS seimbang pada app.css dan admin.css; file terkait dibaca ulang dari branch setelah perubahan; selector aksi folder dan fallback salin path ada.
- **BELUM DIVERIFIKASI:** sintaks PHP melalui PHP CLI, pemanggilan File Explorer pada PHP Desktop Windows, perubahan tampilan aktual, navigasi klik, perilaku tombol pada semua modul, dan resize 1366/1024/390 px.
- **Belum LOCAL TEST PASS.** Belum ada screenshot pascaperubahan dari pengguna. D8 belum selesai karena CSS lama masih memiliki banyak selector berulang dan konsolidasi harus dilakukan hati-hati dengan baseline visual, bukan penghapusan otomatis.
- Tidak ada perubahan skema SQLite, migrasi database, path database utama, atau data pengguna dalam batch UI ini.
