# 03 — Rencana Pengerjaan Bertahap

## Cara membaca status

Semua tahap di bawah berstatus **BELUM DIMULAI** sampai benar-benar dikerjakan dan diuji. Menulis kode saja tidak otomatis berarti tahap lulus.

## Tahap 0 — Acuan proyek
**Pekerjaan**
- README dan dokumen acuan.
- Menetapkan kebutuhan dan alur kerja kolaborasi.

**Kriteria selesai**
- Kebutuhan utama, aturan jadwal, dan rencana tahap terdokumentasi.
- Tidak ada fitur inti yang diasumsikan selesai sebelum diuji.

## Tahap 1 — Pemeriksaan dan PoC desktop Windows
**Status saat ini: SEDANG BERJALAN — PoC awal menggunakan PHP Desktop Chrome 130.1 sebagai kandidat, belum final.**

**Pekerjaan**
- Bandingkan pilihan runtime/pembungkus desktop yang cocok untuk PHP.
- Buat prototipe terkecil untuk membuka antarmuka desktop.
- Buktikan PHP berjalan tanpa XAMPP.
- Ukur ukuran paket dan penggunaan sumber daya awal.

**Kriteria selesai**
- Prototipe bisa dijalankan di Windows sasaran tanpa XAMPP.
- Keputusan runtime ditulis beserta alasan dan keterbatasannya.

## Tahap 1A — Validasi persistensi SQLite
**Status: PASS — diuji pengguna pada Windows 11 64-bit.**

**Hasil aktual yang dilaporkan**
- Status database: siap.
- Simpan data uji: berhasil.
- Setelah aplikasi ditutup dan dibuka kembali: marker dan waktu simpan sama.
- Pesan error: tidak ada.

Catatan: status PASS berlaku untuk tes persistensi diagnostik yang dilaporkan pengguna. Ini belum membuktikan database jadwal produksi, ketahanan terhadap pemadaman paksa, atau keamanan backup.

**Pekerjaan**
- Tambahkan tes diagnostik yang membuat database SQLite di AppData pengguna Windows.
- Tulis marker uji dan tampilkan kembali marker/waktu simpan.
- Sediakan langkah uji setelah aplikasi ditutup dan dibuka kembali.
- Tampilkan error yang dapat dilaporkan jika direktori atau PDO SQLite gagal.

**Kriteria selesai**
- Marker yang disimpan sebelum aplikasi ditutup terbaca identik setelah aplikasi dibuka kembali.
- Lokasi database bukan di dalam folder program/runtime.
- Hasil uji aktual dicatat sebelum status tahap dinyatakan lulus.

## Tahap 2 — PoC SQLite dan audio internal
**Status: PASS — diuji pengguna pada Windows 11 64-bit berdasarkan laporan Tahap 2.**

**Sudah dibuat di repository**
- Database aplikasi utama terpisah dari database diagnostik Tahap 1A, disimpan di `%APPDATA%\\BelMadrasah\\data\\bel-madrasah.sqlite` (fallback `%LOCALAPPDATA%`).
- Inisialisasi skema versi 1: metadata, pengaturan, paket jadwal, aturan hari, kegiatan bel, dan log pemutaran.
- Relasi foreign key, validasi nilai dasar, serta unique partial index untuk mencegah lebih dari satu paket aktif di tingkat database.
- Uji tulis/baca sementara memakai TEMP table dan rollback agar tidak meninggalkan data uji permanen.
- Panel status menampilkan lokasi database, versi skema, dan tabel yang terdeteksi.
- Panel audio menampilkan durasi metadata dan tetap menggunakan elemen audio internal.

**Hasil uji lokal yang dilaporkan:** database aplikasi PASS; setelah restart tetap PASS; MP3 panjang PASS; WAV PASS; tidak ada catatan/error.

**Batas cakupan:** hasil ini berlaku untuk skenario yang dilaporkan. Durasi/ukuran file yang tepat, format WAV spesifik, kegagalan file hilang/rusak, ketahanan pemadaman paksa, dan perilaku perangkat audio yang terputus belum dikonfirmasi.

**Pekerjaan**
- Buat/baca/tulis database SQLite.
- Putar audio lokal melalui komponen internal.
- Uji MP3/WAV dan file audio panjang.
- Uji kesalahan file hilang dan perangkat audio tidak tersedia.

**Kriteria selesai**
- Database dan audio bekerja tanpa server terpisah atau pemutar eksternal.
- Audio panjang tidak dipotong oleh implementasi.
- Keputusan teknologi audio dicatat.

## Tahap 3 — Kerangka aplikasi dan konfigurasi
**Status: IMPLEMENTASI AWAL TERSEDIA — menunggu uji lokal pengguna.**

**Implementasi yang tersedia**
- Navigasi dasar Dashboard diagnostik, Konfigurasi, dan Backup data.
- Halaman konfigurasi menyimpan nama madrasah, nama operator, dan preferensi volume 0–100 ke tabel app_settings.
- Validasi panjang nama dan rentang volume; pesan berhasil/gagal ditampilkan.
- Halaman backup membuat salinan database SQLite melalui VACUUM INTO di folder %APPDATA%\\BelMadrasah\\backups (fallback %LOCALAPPDATA%), tanpa memindahkan atau menghapus database aktif.
- Backup diberi nama unik dan diperiksa keberadaan/ukuran filenya.

**Batasan:** preferensi volume baru disimpan, belum mengatur volume pemutar. Restore belum dibuat. Backup belum diuji di runtime lokal, jadi kompatibilitas VACUUM INTO dan validitas hasil file harus diverifikasi.

**Pekerjaan**
- Struktur aplikasi dan navigasi dasar.
- Pengaturan lokal dan lokasi data pengguna.
- Inisialisasi database yang aman.
- Backup awal/ekspor database jika diperlukan.

**Kriteria selesai**
- Aplikasi dapat dibuka berulang kali dan konfigurasi tersimpan.
- Memperbarui aplikasi tidak menghapus data pengguna.

## Tahap 4 — Pengelolaan paket jadwal
**Pekerjaan**
- CRUD paket jadwal.
- Contoh paket awal: Normal, Ujian, Ramadan.
- Penetapan satu paket aktif secara global.
- Persistensi paket aktif setelah restart.

**Kriteria selesai**
- Hanya satu paket aktif.
- Mengganti paket memengaruhi semua hari tanpa menghapus paket lainnya.

## Tahap 5 — Editor jadwal harian
**Pekerjaan**
- Jadwal Senin–Minggu per paket.
- Pola Normal dan Khusus.
- Jenis kekhususan: pasaran Jawa atau minggu kalender.
- Input waktu, nama kegiatan, file audio, status aktif, dan urutan.
- Validasi data dan penanganan konflik.

**Kriteria selesai**
- Normal tidak menampilkan atribut khusus.
- Khusus meminta tepat satu atribut.
- Konflik yang berpotensi membuat dua jadwal berlaku pada tanggal sama terdeteksi dan dijelaskan kepada operator.

## Tahap 6 — Resolver kalender dan prioritas
**Pekerjaan**
- Hitung hari dalam minggu.
- Hitung pasaran Jawa.
- Terapkan Model A minggu kalender.
- Pilih jadwal khusus atau fallback Normal.
- Tampilkan jadwal yang akan berlaku untuk tanggal terpilih agar dapat diperiksa.

**Kriteria selesai**
- Pengujian mencakup tanggal di awal/akhir bulan, pergantian tahun, bulan dengan empat/lima minggu kalender, dan konflik pasaran/minggu.
- Hanya satu pola hari dipilih untuk satu tanggal dalam satu paket aktif.

## Tahap 7 — Mesin penjadwalan otomatis
**Pekerjaan**
- Pantau jam sistem dengan cara yang hemat sumber daya.
- Memicu audio pada waktu jadwal.
- Cegah pemutaran ganda.
- Catat status berhasil, gagal, dan terlewat.
- Tetapkan perilaku ketika aplikasi dibuka terlambat atau Windows bangun dari sleep.

**Kriteria selesai**
- Pemicu jadwal tidak berulang.
- Audio dimulai sesuai waktu dalam batas toleransi yang disepakati saat uji.
- Kejadian terlewat tidak diputar massal secara otomatis.
- Benturan waktu ditangani sesuai kebijakan yang terdokumentasi.

## Tahap 8 — Dashboard dan operasi harian
**Pekerjaan**
- Tampilkan paket aktif, jam, kegiatan berikutnya, status audio, dan log.
- Uji audio manual.
- Pengaturan volume bila didukung.
- Minimalkan ke system tray jika layak.
- Berikan pesan error yang dapat dipahami operator.

**Kriteria selesai**
- Operator dapat memahami status aplikasi tanpa membaca log teknis.
- Menutup/minimalkan jendela memiliki perilaku yang jelas dan tidak menghentikan scheduler secara tak sengaja.

## Tahap 9 — Pengemasan Windows
**Pekerjaan**
- Buat paket portable atau installer berdasarkan hasil PoC.
- Sertakan runtime dan dependensi yang dibutuhkan.
- Pastikan data pengguna tetap terpisah dari file program.
- Buat panduan instalasi, menjalankan, backup, dan pembaruan.

**Kriteria selesai**
- Paket dapat dipasang/dijalankan pada komputer uji tanpa XAMPP.
- Penghapusan atau pembaruan program tidak menghapus jadwal dan audio pengguna secara diam-diam.

## Tahap 10 — Uji penerimaan dan optimasi
**Pekerjaan**
- Uji jadwal harian dan pola khusus.
- Uji audio pendek dan panjang.
- Uji restart aplikasi/Windows.
- Uji perubahan paket aktif.
- Uji kinerja idle dan saat audio berjalan.
- Perbaiki temuan dan perbarui dokumentasi.

**Kriteria selesai**
- Seluruh skenario prioritas lulus atau memiliki batasan yang disetujui.
- Versi dan catatan rilis terdokumentasi.

## Siklus kerja setiap tahap

1. Implementasi kecil dan terfokus.
2. Commit dengan pesan jelas.
3. Instruksi menjalankan dan menguji.
4. Pengguna melakukan pull dan pengujian lokal.
5. Catat hasil PASS/FAIL dan bukti bila gagal.
6. Perbaiki temuan.
7. Baru lanjut ke tahap berikutnya.

## Format laporan uji

- **Tahap/versi:**
- **Lingkungan Windows:**
- **Langkah pengujian:**
- **Hasil yang diharapkan:**
- **Hasil aktual:**
- **Status:** PASS / FAIL / BLOCKED
- **Bukti/log:**
- **Tindak lanjut:**
