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
**Pekerjaan**
- Bandingkan pilihan runtime/pembungkus desktop yang cocok untuk PHP.
- Buat prototipe terkecil untuk membuka antarmuka desktop.
- Buktikan PHP berjalan tanpa XAMPP.
- Ukur ukuran paket dan penggunaan sumber daya awal.

**Kriteria selesai**
- Prototipe bisa dijalankan di Windows sasaran tanpa XAMPP.
- Keputusan runtime ditulis beserta alasan dan keterbatasannya.

## Tahap 2 — PoC SQLite dan audio internal
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
