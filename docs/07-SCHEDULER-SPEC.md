# 07 — Spesifikasi Scheduler dan Audio Otomatis

## 1. Tujuan dan batas operasi

Scheduler menjalankan kegiatan bel yang aktif pada paket jadwal aktif, berdasarkan tanggal dan waktu lokal komputer Windows. Pemutaran menggunakan audio internal PHP Desktop/Chromium; tidak menggunakan Winamp atau pemutar eksternal.

Scheduler hanya bekerja selama aplikasi Bel Madrasah terbuka dan runtime PHP Desktop berjalan. Menutup aplikasi berarti scheduler berhenti. Layanan Windows, startup otomatis Windows, system tray, dan pemutaran saat aplikasi ditutup belum termasuk dalam kontrak ini.

## 2. Sumber kebenaran

- Waktu yang dipakai adalah waktu lokal sistem Windows, konsisten dengan jam yang ditampilkan aplikasi. Jangan menetapkan timezone berbeda diam-diam.
- Paket aktif global dipilih dari `schedule_packages`.
- Tanggal dan hari diselesaikan oleh `ScheduleResolver`, termasuk pasaran Jawa dan Model A (minggu kalender).
- Hanya aturan dan kegiatan berstatus aktif yang boleh dijalankan.
- Aturan Khusus yang cocok tunggal menggantikan Normal. Jika beberapa aturan Khusus cocok, resolver menggunakan Normal sebagai fallback dan status konflik harus ditampilkan; scheduler tidak boleh menggabungkan aturan yang konflik.
- `bell_events.event_time` memakai format 24 jam `HH:MM`. Urutan kegiatan pada waktu yang sama mengikuti `sort_order`, lalu ID kegiatan.
- `audio_path` adalah referensi file lokal. Path lama tetap didukung. Scheduler tidak memindah, mengganti nama, atau menghapus file.

## 3. Kontrak kejadian

Setiap kejadian memiliki kunci logis `event_id + scheduled_for`, dengan `scheduled_for` berupa tanggal dan waktu jadwal yang sebenarnya, bukan waktu saat polling.

1. Scheduler memeriksa jadwal secara berkala dengan interval pendek dan menghitung tanggal/waktu aktual setiap pemeriksaan; jangan hanya mengandalkan penghitung interval.
2. Kejadian hanya boleh diklaim satu kali untuk satu `event_id + scheduled_for`, termasuk setelah halaman di-refresh atau navigasi Public/Admin.
3. Kegiatan yang jatuh pada menit yang sama diputar satu per satu sesuai urutan, bukan ditumpuk secara bersamaan.
4. Toleransi keterlambatan awal yang disarankan adalah **maksimal 30 detik**. Jika aplikasi kembali aktif setelah batas itu, jangan memutar bel lama secara terlambat; catat sebagai `missed`. Batas ini perlu diuji ketika aplikasi diminimalkan dan setelah sleep/wake.
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
- Setelah event selesai/gagal, lanjutkan antrean kejadian yang masih jatuh dalam toleransi waktu.
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
- [ ] Kegiatan dengan waktu sama diputar berurutan sesuai urutan yang tersimpan.
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
