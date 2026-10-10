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

## 5. Uji paket dan persistensi

- [ ] Paket Normal dapat dibuat dan diubah.
- [ ] Paket Ujian dapat dibuat tanpa mengubah isi paket Normal.
- [ ] Paket Ramadan/kegiatan khusus dapat dibuat.
- [ ] Hanya satu paket aktif pada satu waktu.
- [ ] Paket aktif tetap sama setelah aplikasi dimulai ulang.
- [ ] Mengubah paket aktif langsung mengubah sumber jadwal yang berlaku.
- [ ] Paket yang tidak aktif tidak memicu audio.

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

## 10. Catatan operasional

Aplikasi tidak dapat menjamin pemutaran tepat waktu jika komputer mati, Windows tidur, aplikasi dihentikan, atau perangkat audio terputus. Rekomendasi pengaturan daya dan langkah pemulihan harus ditulis dalam panduan pengguna setelah perilaku final diuji.
