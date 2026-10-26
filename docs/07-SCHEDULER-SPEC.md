# 07 — Spesifikasi Scheduler dan Audio Otomatis

## 1. Tujuan dan batas operasi

Scheduler menjalankan kegiatan bel yang aktif pada paket jadwal aktif, berdasarkan tanggal dan waktu lokal komputer Windows. Pemutaran menggunakan audio internal PHP Desktop/Chromium; tidak menggunakan Winamp atau pemutar eksternal.

Scheduler hanya bekerja selama aplikasi Bel Madrasah terbuka dan runtime PHP Desktop berjalan. Menutup aplikasi berarti scheduler berhenti. Layanan Windows, startup otomatis Windows, system tray, dan pemutaran saat aplikasi ditutup belum termasuk dalam kontrak ini.

## 2. Sumber kebenaran

- Zona waktu aplikasi saat ini ditetapkan eksplisit ke **Asia/Jakarta (WIB)** agar jam server-rendered, resolver, dan log konsisten walau `php.ini` runtime tidak mengatur `date.timezone`. Komputer Windows yang menjalankan aplikasi harus menggunakan zona waktu WIB untuk deployment ini; jika target sekolah berbeda zona, timezone harus menjadi konfigurasi eksplisit sebelum dipakai.
- Paket aktif global dipilih dari `schedule_packages`.
- Tanggal dan hari diselesaikan oleh `ScheduleResolver`, termasuk pasaran Jawa dan Model A (minggu kalender).
- Hanya aturan dan kegiatan berstatus aktif yang boleh dijalankan.
- Aturan Khusus yang cocok tunggal menggantikan Normal. Jika beberapa aturan Khusus cocok, resolver menggunakan Normal sebagai fallback dan status konflik harus ditampilkan; scheduler tidak boleh menggabungkan aturan yang konflik.
- `bell_events.event_time` memakai format 24 jam `HH:MM`. Dalam satu aturan hari (`day_rule_id`), setiap waktu harus unik; validasi Database menolak waktu yang sudah digunakan kegiatan lain pada aturan yang sama. `sort_order` tetap disimpan untuk pengaturan tampilan dan data lama.
- `audio_path` adalah referensi file lokal. Path lama tetap didukung. Scheduler tidak memindah, mengganti nama, atau menghapus file.

## 3. Kontrak kejadian

Setiap kejadian memiliki kunci logis `event_id + scheduled_for`, dengan `scheduled_for` berupa tanggal dan waktu jadwal yang sebenarnya, bukan waktu saat polling.

1. Scheduler memeriksa jadwal secara berkala dengan interval pendek dan menghitung tanggal/waktu aktual setiap pemeriksaan; jangan hanya mengandalkan penghitung interval.
2. Kejadian hanya boleh diklaim satu kali untuk satu `event_id + scheduled_for`, termasuk setelah halaman di-refresh atau navigasi Public/Admin.
3. Konfigurasi normal tidak boleh memiliki dua kegiatan pada menit yang sama dalam satu aturan hari. Validasi saat tambah/edit menolak duplikasi; waktu kegiatan lain pada pola Normal/Khusus yang berbeda tidak dicampur karena resolver hanya memilih pola yang cocok.
4. Toleransi untuk memulai setiap kejadian adalah **maksimal 30 detik**. Jika aplikasi kembali aktif setelah batas itu, jangan memutar bel lama secara terlambat; catat sebagai `missed`. Jika database lama masih berisi duplikasi waktu dalam satu pola, jangan memutar kegiatan pada waktu tersebut: catat setiap kegiatan duplikat sebagai `failed`, beri alasan yang bisa ditindaklanjuti, dan tampilkan peringatan di Editor. Operator memperbaiki pola secara manual; data tidak dihapus otomatis.
5. Kejadian dari tanggal sebelumnya tidak boleh diputar ketika aplikasi dibuka kembali.
6. Kegiatan aktif tanpa path audio ditandai `skipped` dengan alasan yang jelas. Referensi audio yang tidak ditemukan atau gagal dibaca ditandai `failed`; jangan menampilkan status seolah audio berhasil diputar.
7. Setelah media benar-benar selesai, catat `completed`. Jika pemutaran gagal, ditolak oleh kebijakan autoplay, atau dihentikan sebelum selesai, catat `failed` dengan pesan yang relevan.
8. Volume player memakai preferensi volume yang tersimpan pada konfigurasi (0–100). Volume 0 tidak boleh mengubah log sukses menjadi klaim bahwa suara terdengar; log tetap merekam status pemutaran media, bukan pengukuran suara fisik.

## 4. Status dan riwayat

Tabel `playback_log` yang sudah ada menyediakan status `started`, `completed`, `failed`, `missed`, dan `skipped`. Gunakan status tersebut secara konsisten:

- `started`: kejadian sudah diklaim dan media mulai diproses.
- `completed`: elemen media melaporkan selesai.
- `failed`: media tidak dapat dimulai/dibaca atau dihentikan sebelum selesai.
- `missed`: aplikasi tidak memproses jadwal dalam toleransi keterlambatan.
- `skipped`: kegiatan tidak memiliki audio atau tidak dapat dijalankan karena alasan operasional yang memang dilewati.

Riwayat tidak boleh dipakai sebagai satu-satunya bukti bahwa speaker Windows mengeluarkan suara. UI harus membedakan status media dari status suara fisik yang tidak dapat diukur aplikasi.

## 5. Pemutaran internal dan navigasi

- File audio harus disalurkan melalui endpoint lokal yang memeriksa ID kegiatan terhadap database, lalu membaca hanya path yang berasal dari database. Endpoint tidak boleh menerima path file arbitrer dari query string.
- Script scheduler dijalankan pada Public dan Admin agar pergantian halaman tidak mematikan scheduler secara permanen.
- Saat halaman berpindah atau dimuat ulang ketika media sedang berjalan, kejadian aktif harus direkonsiliasi sebagai terhenti/gagal; jangan memutar kejadian yang sama dua kali.
- Kegagalan satu kejadian tidak boleh menghentikan polling jadwal pada waktu berikutnya. Data lama yang memiliki waktu duplikat perlu ditandai dan diperbaiki; jangan menganggap antrean beberapa kegiatan pada menit yang sama sebagai konfigurasi yang didukung.
- Tidak boleh memakai CDN, service internet, atau pemutar eksternal.

## 6. Prasyarat runtime dan risiko

Konfigurasi runtime PHP Desktop yang dipakai pengguna tidak tersimpan di repository. Karena itu, kebijakan autoplay Chromium, izin PHP untuk membaca file, dan perilaku timer saat jendela diminimalkan belum terverifikasi. Sebelum menyatakan scheduler lulus:

1. Periksa konfigurasi runtime PHP Desktop yang benar-benar dipakai; jangan mengasumsikan file konfigurasi dari paket lain identik.
2. Pastikan autoplay audio lokal bekerja tanpa dialog yang menghentikan jadwal pada waktu bel.
3. Pastikan audio selesai sebelum kegiatan berikutnya dimulai.
4. Pastikan perpindahan Public/Admin, minimize/restore, dan restart tidak menghasilkan pemutaran ganda.
5. Pastikan error file hilang/rusak dicatat dan tidak membuat aplikasi macet.

## 7. Kriteria penerimaan Windows

- [ ] Bel satu menit ke depan terdengar pada waktu yang benar.
- [ ] Waktu duplikat dalam satu aturan hari ditolak saat tambah/edit; data lama yang sudah duplikat ditandai di Editor dan tidak dihapus otomatis.
- [ ] Kegiatan nonaktif tidak diputar.
- [ ] Paket aktif dan aturan Normal/Khusus sesuai tanggal yang diuji.
- [ ] Tidak ada pemutaran ganda setelah refresh/navigasi.
- [ ] Waktu terlewat di luar toleransi dicatat `missed`, tidak diputar terlambat.
- [ ] File audio kosong/tidak ditemukan/format tidak didukung menghasilkan status yang tepat dan aplikasi tetap hidup.
- [ ] Volume 0 dan 100 berperilaku sesuai preferensi.
- [ ] Minimize/restore, sleep/wake, perubahan jam Windows, dan restart diuji.
- [ ] Status pada UI cocok dengan riwayat database dan tidak mengklaim suara fisik terukur.

## 8. Batas klaim

Kode resolver kalender yang ada hanya menentukan pola dan daftar kegiatan. Sampai scheduler, dispatch audio, log, serta pengujian Windows di atas tersedia, status fitur tetap **BELUM LOCAL TEST PASS**.


## 9. Penanganan kegagalan audio — implementasi lanjutan (2026-10-24)

- Sebelum kejadian diklaim `started`, scheduler memeriksa ekstensi, keberadaan file, izin baca, ukuran nonnol, dan apakah file dapat dibuka oleh PHP/Windows.
- Ekstensi yang diizinkan endpoint saat ini: MP3, WAV, OGG, M4A, AAC. MP3/WAV tetap format rekomendasi untuk uji operasional; ekstensi yang valid tidak menjamin isi file/codec dapat didekode.
- Editor Jadwal menampilkan indikator untuk referensi kosong, file hilang/tidak dapat dibaca, file kosong, ekstensi tidak didukung, dan file yang tersedia. Status tersedia bukan jaminan codec valid; uji putar tetap diperlukan.
- Jika Chromium gagal memuat atau mendekode audio, status scheduler membedakan pembatalan, kegagalan penyaluran, kegagalan decode, dan format/codec yang tidak didukung.
- Status backend yang sudah ditutup dengan alasan spesifik tidak boleh ditimpa diam-diam oleh pesan UI yang lebih umum.
- Status: **KODE DIIMPLEMENTASIKAN; BELUM LOCAL TEST PASS** sampai skenario negatif dijalankan di PHP Desktop Windows.


## 10. Konsistensi log saat penyaluran audio (2026-10-24)

- Jika endpoint penyaluran mengalami exception setelah ID run diketahui, endpoint mencoba menutup log tersebut sebagai `failed`; kegagalan pencatatan tidak boleh menutupi pesan kegagalan utama.
- Jika pembacaan file berhenti sebelum seluruh byte terkirim dan bukan karena klien memutus koneksi, log ditutup sebagai `failed` dengan alasan penyaluran terhenti.
- Jika klien memutus koneksi, lifecycle halaman/player bertanggung jawab menyelesaikan status run; saat halaman dimuat kembali, run `started` yang tersisa direkonsiliasi menjadi `failed`.
- Jika player mencoba menutup run yang sudah ditutup endpoint stream, respons scheduler menyertakan status dan pesan yang sudah tersimpan agar UI dapat menampilkan alasan asli, bukan hanya pesan umum “log sudah ditutup”.
- Kriteria ini mencegah riwayat pemutaran menggantung pada status `started`. Tetap perlu uji Windows untuk memastikan jalur exception dan pemutusan koneksi benar-benar terjadi seperti yang diharapkan.

- Pembacaan stream juga memeriksa kondisi setelah loop: jika EOF tercapai sementara masih ada byte yang seharusnya terkirim, run ditutup sebagai `failed` dengan jumlah byte tersisa. Pemutusan koneksi oleh klien tetap ditangani sebagai lifecycle player, bukan otomatis dianggap kerusakan file.

## 11. Waktu tampilan dan pergantian tanggal (2026-10-24)

- Jam pada Dashboard Publik diformat secara eksplisit memakai zona waktu `Asia/Jakarta`, bukan zona waktu lokal Chromium. Ini menyamakan tampilan dengan timezone backend yang ditetapkan di `Database.php`.
- Tanggal publik yang dirender PHP disimpan sebagai tanggal acuan. Ketika tanggal WIB pada browser berbeda dari tanggal acuan, halaman dimuat ulang agar tanggal, pasaran, aturan khusus, dan daftar kegiatan tidak tertinggal setelah tengah malam atau perubahan jam/tanggal.
- Mengubah jam sistem secara manual dan perilaku timer saat sleep/wake tetap harus diuji di PHP Desktop Windows; sinkronisasi tampilan tidak menjamin Windows memiliki waktu yang benar.

## 12. Klarifikasi aturan kalender (2026-10-24)

- `Minggu ke-1` sampai `Minggu ke-5` dihitung sebagai blok tanggal dalam bulan: tanggal 1–7, 8–14, 15–21, 22–28, dan 29 sampai akhir bulan. Ini bukan nomor minggu ISO.
- Opsi `Kemunculan terakhir dalam bulan` berarti kemunculan terakhir dari hari yang dipilih dalam bulan tersebut (misalnya Jumat terakhir), bukan seluruh blok kalender terakhir. Label ini dibuat eksplisit di Editor Jadwal, resolver, dan Dashboard Publik agar tidak tertukar dengan `Minggu ke-4/5`.
- Kedua jenis aturan dapat bertumpang tindih untuk tanggal tertentu. Jika lebih dari satu aturan Khusus aktif cocok pada hari yang sama, resolver tidak menggabungkannya: pola Normal dipakai sebagai fallback dan konflik ditampilkan. Operator perlu menghindari kombinasi aturan yang bertumpang tindih pada hari yang sama.
- Dashboard Publik menampilkan label aturan Khusus yang cocok; saat konflik, pesan menyebut aturan yang terdeteksi dan menjelaskan fallback ke pola Normal.
- Status kode: label dan pesan diperbarui di repository; pemeriksaan runtime dan tanggal batas tetap perlu dilakukan di PHP Desktop Windows.
- Matriks uji kalender lokal: periksa batas tanggal 7/8, 14/15, 21/22, 28/29; Februari tahun kabisat/nonkabisat; bulan 30 dan 31 hari; pasaran yang berlanjut lintas bulan/tahun; satu aturan Khusus yang menggantikan Normal; serta kombinasi `Minggu ke-4/5` dengan `Kemunculan terakhir dalam bulan` yang harus memunculkan konflik dan memakai Normal sebagai fallback.

## 13. Hari libur mingguan sebagai aturan operasional (2026-10-24)

- `off_days` pada paket aktif bukan sekadar label atau template saat paket dibuat. Resolver bersama memeriksa hari dalam minggu dan menghentikan daftar kegiatan jika tanggal tersebut termasuk hari libur paket.
- Pada hari libur mingguan, Dashboard Publik menampilkan pemberitahuan, Dashboard Admin menjelaskan bahwa bel tidak dijalankan, dan scheduler mengirim status `off_day` tanpa memulai audio.
- Aturan dan kegiatan yang sudah tersimpan pada hari libur tidak dihapus. Jika operator mengubah pilihan hari libur pada paket lama, aturan hari itu tetap tersimpan tetapi tidak dijalankan sampai hari tersebut tidak lagi ditetapkan sebagai hari libur.
- Perubahan ini berlaku juga untuk aturan Khusus: hari libur mingguan menjadi prioritas lebih tinggi daripada aturan Normal maupun Khusus.
- Uji lokal: untuk tiap pilihan Jumat, Minggu, dan Sabtu–Minggu, pastikan scheduler tidak memutar audio pada hari libur walaupun aturan aktif masih tersimpan; pastikan hari sekolah tetap memakai resolver Normal/Khusus seperti biasa; ubah pilihan libur pada paket yang sudah memiliki aturan dan pastikan aturan tidak hilang.
- Status: implementasi kode di repository; **belum local test pass** sampai diuji di PHP Desktop Windows.


## 14. Validator audio bersama dan pesan riwayat otoritatif — 2026-10-26

- `lib/AudioAsset.php` menjadi satu pemeriksa referensi audio untuk scheduler dan endpoint stream. Pemeriksaan mencakup referensi kosong, ekstensi, keberadaan file, tipe file biasa, keterbacaan, ukuran nonnol, dan kemampuan membuka file.
- Scheduler memeriksa aset sebelum mencatat status `started`. Referensi kosong menjadi `skipped`; file hilang, kosong, tidak dapat dibaca/dibuka, atau format tak didukung menjadi `failed` dengan alasan yang dapat ditindaklanjuti.
- Endpoint stream menjalankan pemeriksaan yang sama sekali lagi karena file dapat berubah setelah scheduler mengklaim kejadian.
- Respons ketika log sudah ditutup menyediakan `status` dan `stored_message` terstruktur. UI memakai status/pesan tersimpan sebagai sumber kebenaran agar callback media yang datang belakangan tidak mengganti kegagalan spesifik dengan pesan generik.
- `tests/AudioAssetValidationTest.php` menguji cabang validasi file tanpa membutuhkan database SQLite. Tes ini tidak membuktikan codec MP3/WAV dapat didekode; itu tetap harus diuji pada PHP Desktop Windows.
- Status batch: **KODE DIIMPLEMENTASIKAN; BELUM DIVERIFIKASI PADA CHECKOUT PENGGUNA ATAU RUNTIME WINDOWS**. Jalankan checklist baru di `docs/04-TEST-PLAN.md` sebelum menyatakan batch ini PASS.
