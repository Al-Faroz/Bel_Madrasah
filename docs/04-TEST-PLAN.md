# 04 — Rencana Pengujian

## 1. Prinsip

Pengujian dilakukan pada komputer Windows lokal, bukan hanya berdasarkan pemeriksaan kode. Uji audio harus benar-benar mendengarkan keluaran speaker/amplifier dan memastikan tidak ada pemutar musik eksternal yang perlu dibuka.

## 2. Lingkungan uji yang perlu dicatat

- Edisi dan versi Windows.
- Arsitektur Windows (x64/ARM64 bila relevan).
- Perangkat output suara yang dipilih.
- Jalur koneksi komputer ke speaker/amplifier.
- Versi paket aplikasi.
- Ukuran file dan format audio yang diuji.
- Pengaturan daya Windows, termasuk sleep.
- Zona waktu dan ketepatan jam sistem.

## 3. Uji fondasi desktop

- [ ] Aplikasi berjalan tanpa XAMPP.
- [ ] Aplikasi berjalan tanpa instalasi Apache/MySQL.
- [ ] Aplikasi berjalan pada komputer uji yang tidak memiliki lingkungan pengembangan.
- [x] Data uji dapat disimpan dan dibaca setelah aplikasi dimulai ulang (hasil pengguna: PASS).
- [x] Tes SQLite Tahap 1A: marker dan waktu simpan tetap sama setelah aplikasi ditutup sepenuhnya dan dibuka kembali (hasil pengguna: PASS).
- [ ] Tes gagal SQLite: bila status database bermasalah, pesan error dapat dibaca dan dicatat; aplikasi tidak diam-diam menyimpan database di folder program.
- [x] Lokasi database diagnostik dirancang berada di `%APPDATA%\\BelMadrasah\\diagnostics\\storage-test.sqlite` (atau `LOCALAPPDATA` bila `APPDATA` tidak tersedia); lokasi aktual belum dicatat dari laporan.
- [ ] Aplikasi tidak menghapus data pengguna saat pembaruan.
- [ ] Penggunaan CPU/RAM saat idle diukur.
- [ ] Ukuran paket distribusi dicatat.

### Catatan hasil uji Tahap 1A
- **Lingkungan:** Windows 11 64-bit; PHP 8.3.13; PDO SQLite terdeteksi.
- **Database siap:** Ya.
- **Simpan data uji:** Berhasil.
- **Setelah restart aplikasi:** Marker dan waktu sama.
- **Error:** Tidak ada.
- **Status:** PASS untuk uji persistensi diagnostik yang dilaporkan pengguna.
- **Batas cakupan:** Belum menguji kegagalan tulis, pemadaman paksa, backup/restore, maupun skema database jadwal produksi.

### Uji tambahan Tahap 2 — database aplikasi utama
- [x] Panel menampilkan `SKEMA SIAP` (hasil pengguna: PASS).
- [ ] Lokasi database berada di `%APPDATA%\\BelMadrasah\\data\\bel-madrasah.sqlite` atau fallback `%LOCALAPPDATA%`, bukan di folder runtime (lokasi tepat belum disalin dalam laporan).
- [x] Versi skema tampil `1` dan tabel inti terdeteksi (tercakup dalam laporan database PASS; detail tampilan tidak disalin).
- [x] Tutup dan buka kembali aplikasi; skema tetap siap (hasil pengguna: PASS).
- [ ] Bila folder data tidak bisa ditulis, aplikasi menampilkan pesan error yang terbaca.
- [x] Uji tulis/baca sementara tidak menambah record permanen (uji otomatis menggunakan TEMP table dan rollback; hasil panel database PASS).

### Catatan hasil uji Tahap 2 — laporan pengguna
- **Database aplikasi:** PASS.
- **Restart aplikasi:** PASS.
- **MP3 panjang:** PASS.
- **WAV:** PASS.
- **Pesan error/catatan:** Tidak ada.
- **Kesimpulan:** Tahap 2 dinyatakan PASS untuk skenario yang dilaporkan pengguna. Tidak ada perbaikan kode yang diindikasikan oleh laporan ini.
- **Belum tercakup:** uji file hilang/rusak, durasi/ukuran file yang tepat, lokasi path aktual yang dikonfirmasi, perangkat audio terputus, pemadaman paksa, dan scheduler otomatis.

## 4. Uji audio

- [ ] Audio uji dapat diputar tanpa membuka pemutar eksternal.
- [ ] Suara keluar melalui perangkat output yang diharapkan.
- [ ] File MP3 pendek dapat diputar sampai selesai.
- [x] File WAV dapat diputar pada runtime uji (hasil pengguna: PASS; format WAV spesifik belum dicatat).
- [x] Audio MP3 panjang diputar sampai selesai tanpa terpotong (hasil pengguna: PASS; durasi/ukuran file tidak dicatat).
- [ ] Durasi audio tampil setelah metadata berhasil dibaca (belum dilaporkan secara terpisah).
- [ ] File dengan format tidak didukung menampilkan pesan error dan aplikasi tetap responsif.
- [ ] File yang tidak ditemukan menghasilkan pesan yang jelas.
- [ ] File rusak/tidak didukung tidak membuat aplikasi macet.
- [ ] Perilaku ketika audio lain sedang berjalan mengikuti kebijakan benturan.
- [ ] Pemutaran manual tidak merusak status scheduler.

### Uji Tahap 3 — kerangka, konfigurasi, dan backup
- [x] Navigasi Dashboard, Konfigurasi, dan Backup data terbuka (hasil pengguna: PASS).
- [x] Simpan konfigurasi berhasil (hasil pengguna: PASS).
- [x] Persistensi konfigurasi setelah restart (hasil pengguna: PASS).
- [ ] Volume menerima nilai 0 dan 100; menolak nilai di luar rentang (belum dilaporkan terpisah).
- [x] Tidak ada error pada skenario yang dilaporkan pengguna.
- [x] Backup pertama berhasil dibuat (hasil pengguna: PASS; lokasi dan ukuran file belum dicatat).
- [ ] Database aktif tetap ada dan tidak berubah lokasi setelah backup (belum dikonfirmasi terpisah).
- [ ] Backup dapat diperiksa sebagai database SQLite yang valid pada salinan terpisah; jangan mengganti database aktif.
- [x] Backup kedua berhasil dibuat (hasil pengguna: PASS).
- [ ] Nama file berbeda dan backup pertama tidak tertimpa (belum dikonfirmasi terpisah).
- [ ] Gagal membuat folder atau file backup menghasilkan pesan error yang terbaca.
- [ ] Data konfigurasi dan database tetap ada setelah menyalin pembaruan aplikasi.
- [ ] Volume saat ini hanya preferensi tersimpan; jangan menganggapnya sudah mengubah output audio.

**Catatan hasil Tahap 3 — laporan pengguna:** navigasi PASS; simpan konfigurasi PASS; persistensi setelah restart PASS; backup pertama PASS; backup kedua PASS; tidak ada error. Tahap 3 dinyatakan PASS untuk skenario yang dilaporkan. Integritas backup dan restore masih perlu diuji.

## 5. Uji paket dan persistensi

### Tahap 4 — pengelolaan paket
- [x] Halaman Paket jadwal dapat dibuka dari navigasi dashboard, konfigurasi, dan backup.
- [x] Pada database tanpa paket, halaman menyiapkan satu paket Normal sebagai aktif.
- [x] Buat paket Ujian; paket baru tampil nonaktif dan Normal tetap aktif.
- [x] Buat paket Ramadan; kedua paket tersimpan tanpa mengubah paket aktif.
- [x] Nama paket kosong atau terlalu panjang ditolak dengan pesan yang jelas.
- [x] Nama paket duplikat (termasuk beda kapitalisasi) ditolak.
- [x] Edit nama/keterangan menyimpan perubahan tanpa mengubah status aktif.
- [x] Aktifkan Ujian; Ujian menjadi aktif dan Normal menjadi nonaktif.
- [x] Aktifkan kembali Normal; hanya Normal yang aktif.
- [x] Tutup dan buka aplikasi; paket dan status aktif tetap sama.
- [x] Paket tidak aktif tidak dihapus dari daftar.
- [x] Kesalahan penyimpanan menampilkan pesan yang dapat dipahami.
- [x] Pemeriksaan database memastikan jumlah paket dengan `is_active = 1` tidak pernah lebih dari satu.

### Cakupan fitur tahap berikutnya
- [ ] Paket aktif benar-benar menjadi sumber resolver kalender dan scheduler (belum diimplementasikan).
- [ ] Paket yang tidak aktif tidak memicu audio (perlu diuji setelah scheduler tersedia).

### Tahap 5 — editor jadwal harian
- [ ] Editor jadwal dapat dibuka dari dashboard, paket jadwal, konfigurasi, dan backup.
- [ ] Pilih paket berbeda; daftar aturan yang tampil sesuai paket yang dipilih.
- [ ] Tambah pola Normal Senin; tampil tanpa atribut khusus.
- [ ] Tambah pola Khusus Selasa dengan Pasaran Jawa; nilai yang tersedia hanya Legi, Pahing, Pon, Wage, Kliwon.
- [ ] Ubah jenis kekhususan menjadi minggu kalender; nilai yang tersedia hanya minggu ke-1 s.d. ke-5 dan minggu terakhir.
- [ ] Pilih Normal; bidang jenis/nilai kekhususan tersembunyi dan tidak disimpan.
- [ ] Coba tambah pola Normal kedua pada hari/paket yang sama; aplikasi menolak duplikasi.
- [ ] Coba tambah aturan Khusus dengan jenis/nilai identik pada hari/paket yang sama; aplikasi menolak duplikasi.
- [ ] Aturan khusus yang berbeda pada hari yang sama dapat disimpan dan menampilkan peringatan potensi benturan.
- [ ] Status aktif/nonaktif aturan tersimpan setelah halaman dibuka kembali.
- [ ] Tambah kegiatan dengan nama, waktu valid, urutan, status aktif, dan file MP3.
- [ ] Tambah kegiatan WAV; pastikan jalur file tersimpan dan file ada di folder audio lokal aplikasi.
- [ ] Edit nama/waktu/urutan/status kegiatan dan ganti audio; pastikan perubahan tersimpan.
- [ ] Simpan edit kegiatan tanpa memilih audio baru; jalur audio lama harus tetap dipertahankan dari database.
- [ ] Coba unggahan gagal/terlalu besar; pesan harus menjelaskan penyebab dan tidak boleh meninggalkan file audio yatim saat validasi data gagal.
- [ ] Coba waktu tidak valid melalui request yang dimodifikasi (contoh 25:90); server harus menolak.
- [ ] Coba file dengan ekstensi yang tidak diizinkan dan file di atas batas ukuran; aplikasi menolak dengan pesan jelas.
- [ ] Hapus satu kegiatan; hanya kegiatan itu yang hilang.
- [ ] Tutup dan buka aplikasi; aturan dan kegiatan tetap tersimpan.
- [ ] Pastikan belum ada audio otomatis yang diputar hanya karena data jadwal disimpan (scheduler ada pada tahap berikutnya).

## 6. Uji kalender

- [ ] Jadwal Normal Senin–Minggu.
- [ ] Pasaran Legi, Pahing, Pon, Wage, Kliwon.
- [ ] Minggu kalender ke-1 sampai ke-5 jika tersedia.
- [ ] Minggu terakhir.
- [ ] Awal bulan ketika tanggal 1 bukan Senin.
- [ ] Akhir bulan ketika minggu terakhir tidak penuh.
- [ ] Pergantian bulan dan tahun.
- [ ] Tanggal yang cocok dengan aturan pasaran dan minggu kalender sekaligus.
- [ ] Aturan khusus mengesampingkan Normal.
- [ ] Jika tidak ada aturan khusus yang cocok, Normal digunakan.
- [ ] Konflik aturan khusus memberi peringatan dan tidak menghasilkan pemutaran ganda.

## 7. Uji scheduler

- [ ] Audio dimulai pada waktu uji yang dijadwalkan.
- [ ] Pemeriksaan waktu berulang tidak memicu audio berkali-kali.
- [ ] Kejadian yang sama tidak diputar ulang setelah dashboard diperbarui.
- [ ] Kejadian terlewat dicatat, tidak diputar massal otomatis.
- [ ] Aplikasi tetap menjadwalkan ketika diminimalkan jika fitur tersebut tersedia.
- [ ] Menutup aplikasi memiliki perilaku yang jelas.
- [ ] Restart aplikasi tidak mengulang kejadian yang sudah diproses.
- [ ] Perilaku setelah Windows bangun dari sleep didokumentasikan dan diuji.
- [ ] Jam sistem yang salah dapat dikenali operator.

## 8. Uji operasional

- [ ] Operator dapat mengubah nama kegiatan.
- [ ] Operator dapat mengganti waktu kegiatan.
- [ ] Operator dapat memilih file audio.
- [ ] Operator dapat menonaktifkan kegiatan tanpa menghapus datanya.
- [ ] Dashboard menampilkan kegiatan berikutnya.
- [ ] Status sedang diputar/selesai/gagal sesuai kondisi nyata.
- [ ] Log membantu menjelaskan hasil pemutaran.
- [ ] Popup Windows tidak dibutuhkan agar audio tetap berjalan.

## 9. Kinerja dan stabilitas

Lakukan pengukuran pada komputer sasaran:
- Idle selama minimal satu jam.
- Pemutaran audio pendek berulang.
- Pemutaran satu audio panjang.
- Aplikasi berjalan selama satu hari sekolah simulasi.
- Restart aplikasi dan Windows.
- Kondisi file audio hilang atau perangkat output berubah.

Target numerik CPU/RAM akan ditentukan setelah PoC dan disesuaikan dengan spesifikasi komputer sekolah; jangan mengklaim aplikasi ringan sebelum ada pengukuran.

## 10. Uji penerimaan UI/UX Material Design

Acuan: [05-UI-UX-MATERIAL-DESIGN.md](05-UI-UX-MATERIAL-DESIGN.md).

### Konsistensi visual dan interaksi
- [ ] Semua halaman menggunakan token warna, tipografi, spacing, radius, border, elevation, dan komponen bersama.
- [ ] Navigasi konsisten dan item halaman aktif terlihat jelas.
- [ ] Hierarki judul, deskripsi, tindakan utama, isi, dan metadata mudah dibedakan.
- [ ] Tombol primary/secondary/destructive punya prioritas visual yang konsisten.
- [ ] Semua kontrol punya label; focus keyboard terlihat; disabled/loading state dapat dibedakan.
- [ ] Pesan validasi, sukses, gagal, warning, dan empty state jelas serta tidak menggeser layout secara merusak.
- [ ] Warna status tidak menjadi satu-satunya cara menyampaikan arti.

### Dashboard
- [ ] Admin Dashboard menunjukkan ringkasan operasional yang relevan, bukan hanya informasi diagnostik mentah.
- [ ] Jam, paket aktif, kegiatan berikutnya, dan status audio hanya menyatakan kondisi yang benar-benar diketahui aplikasi.
- [ ] Public Dashboard/status terpisah dari kontrol admin, atau keterbatasan route/fitur dicatat dengan jelas.
- [ ] Informasi penting public/status dapat dibaca sekilas tanpa detail teknis yang tidak dibutuhkan.

### Responsivitas
- [ ] Tampilan pada lebar 1366 px.
- [ ] Tampilan pada lebar 1024 px.
- [ ] Tampilan pada lebar 390 px.
- [ ] Resize jendela PHP Desktop tidak membuat tombol tumpang tindih, label terpotong, atau form melebar keluar.
- [ ] Tabel jadwal tetap dapat dibaca melalui layout/overflow yang disengaja.

### Regresi fungsi setelah redesign
- [ ] Buat dan edit paket jadwal; paket aktif tetap mengikuti aturan satu paket aktif.
- [ ] Pilihan hari libur paket baru tetap tersimpan dan hari aktif terbentuk sesuai pilihan.
- [ ] Paket lama tidak diam-diam berubah aturan hari liburnya.
- [ ] Tambah/edit/hapus kegiatan tetap menggunakan aturan backend yang sama.
- [ ] Pilihan file audio dan path tersimpan tidak berubah akibat redesign.
- [ ] Salin Jadwal menampilkan pratinjau dan konfirmasi, menyalin Senin ke Selasa, mengganti seluruh kegiatan target, serta tidak mengubah paket/hari lain.
- [ ] Konfigurasi dan backup tetap berfungsi.
- [ ] Hasil visual diperiksa di runtime Windows lokal; code review saja bukan PASS.

## 10. Catatan operasional

Aplikasi tidak dapat menjamin pemutaran tepat waktu jika komputer mati, Windows tidur, aplikasi dihentikan, atau perangkat audio terputus. Rekomendasi pengaturan daya dan langkah pemulihan harus ditulis dalam panduan pengguna setelah perilaku final diuji.


## UI-3 — Paket Jadwal (Material Design)

Acuan visual: [05-UI-UX-MATERIAL-DESIGN.md](05-UI-UX-MATERIAL-DESIGN.md).

- [ ] Halaman daftar dan formulir paket konsisten dengan design tokens dan app shell.
- [ ] Paket aktif tampak berbeda dengan label teks dan indikator visual.
- [ ] Nama paket dan keterangan mempunyai label aksesibel yang terkait langsung dengan input.
- [ ] Ringkasan hari libur pada setiap paket sesuai data yang tersimpan.
- [ ] Pilihan hari libur pada paket baru tetap sama: Jumat, Minggu, atau Sabtu–Minggu.
- [ ] Mengedit paket lama tidak mengubah aturan hari libur yang sudah ada.
- [ ] Aktivasi paket meminta konfirmasi sebelum mengganti sumber jadwal global.
- [ ] Membatalkan dialog konfirmasi tidak mengirim perubahan.
- [ ] Konfirmasi aktivasi yang disetujui tetap mengaktifkan paket melalui handler backend yang ada.
- [ ] Layout diuji pada lebar 1366 px, 1024 px, dan 390 px.
- [ ] Pemeriksaan runtime dilakukan pada Windows lokal; pemeriksaan repository bukan pengganti uji runtime.


## UI-4 — Editor Jadwal (Material Design)

- [ ] Pemilihan paket dan navigasi aktif terlihat jelas.
- [ ] Form aturan Normal/Khusus, jenis/nilai atribut, dan status aktif tetap berfungsi.
- [ ] Tabel kegiatan tetap dapat digulir horizontal saat dibutuhkan tanpa merusak layout.
- [ ] Form edit kegiatan, upload audio, tambah kegiatan, hapus kegiatan, dan validasi server tetap berfungsi.
- [ ] Salin Jadwal masih menampilkan pratinjau dan konfirmasi; pembatalan tidak mengubah data.
- [ ] Tampilan diuji pada 1366 px, 1024 px, dan 390 px.

## UI-5 — Konfigurasi dan Backup (Material Design)

- [ ] Label form terhubung ke input dan nilai konfigurasi tetap tersimpan setelah restart.
- [ ] Jalur database dan jalur backup dapat dibaca pada jendela sempit.
- [ ] Tombol backup membuat file pada lokasi yang diharapkan; database aktif tidak diubah.
- [ ] Pesan sukses/gagal tampil jelas tanpa menyembunyikan detail yang diperlukan untuk diagnosis.
- [ ] Tampilan diuji pada 1366 px, 1024 px, dan 390 px.

## UI-6 — Public Dashboard (Material Design)

- [ ] Halaman `public.php` dapat dibuka terpisah dari kontrol admin.
- [ ] Tidak ada aksi pengelolaan paket/jadwal pada tampilan publik.
- [ ] Nama madrasah, tanggal/jam, paket aktif, kegiatan tersimpan, dan kegiatan berikutnya ditampilkan sesuai data yang benar-benar diketahui.
- [ ] Jika ada aturan Khusus, halaman menjelaskan bahwa resolver kalender belum tersedia dan tidak mengklaim jadwal final.
- [ ] Halaman tidak mengklaim audio sedang diputar; statusnya menjelaskan bahwa ini ringkasan konfigurasi.
- [ ] Jam diperbarui di sisi tampilan dan layout diuji pada layar besar serta 1366 px, 1024 px, dan 390 px.
- [ ] Link dari Admin Dashboard membuka tampilan publik.
- [ ] Pemeriksaan runtime dilakukan pada Windows lokal.
