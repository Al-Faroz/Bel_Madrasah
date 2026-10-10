# 01 — Acuan Kebutuhan

Dokumen ini menjadi acuan kebutuhan awal. Perubahan kebutuhan berikutnya harus diperbarui di dokumen ini agar implementasi dan pengujian tidak berbeda arah.

## 1. Kebutuhan platform

- Aplikasi desktop untuk Windows, dengan prioritas Windows 10/11.
- PHP sebagai bahasa utama aplikasi.
- Tidak mengharuskan operator memasang XAMPP, Apache, atau MySQL.
- Tidak mengharuskan pemutar musik eksternal seperti Winamp, VLC, atau Windows Media Player dibuka.
- Fungsi jadwal dan audio lokal tidak memerlukan internet.
- Data dan konfigurasi disimpan secara lokal.
- Paket distribusi harus menyertakan runtime/dependensi yang diperlukan atau memberi petunjuk instalasi yang jelas.

## 2. Pengelolaan paket jadwal

Operator dapat membuat, mengubah nama, mengaktifkan, dan mengelola beberapa paket jadwal, misalnya:
- Normal
- Ujian
- Ramadan
- Kegiatan Khusus

Hanya ada **satu paket jadwal aktif secara global** pada satu waktu. Semua hari menggunakan paket tersebut. Jika paket aktif diganti, aturan dari paket baru yang digunakan untuk hari-hari berikutnya. Pilihan paket aktif disimpan dan tetap berlaku setelah aplikasi dimulai ulang atau berganti tanggal, sampai operator mengubahnya.

Data paket lain tetap tersimpan dan dapat digunakan kembali.

## 3. Pengelolaan jadwal hari

Di dalam setiap paket, operator dapat mengatur Senin sampai Minggu. Untuk setiap hari dipilih salah satu pola berikut.

### 3.1 Pola Normal
- Berlaku secara umum untuk hari tersebut.
- Tidak memakai atribut pasaran Jawa atau minggu kalender.

### 3.2 Pola Khusus
Pola Khusus harus memakai **tepat satu jenis kekhususan**, bukan dua sekaligus:
- **Pasaran Jawa:** Legi, Pahing, Pon, Wage, atau Kliwon.
- **Minggu dalam bulan:** Minggu ke-1, ke-2, ke-3, ke-4, ke-5, atau Minggu terakhir.

Antarmuka yang diharapkan:
1. Pilih pola hari Normal atau Khusus.
2. Jika Khusus, tampilkan pilihan Jenis Kekhususan.
3. Jika Pasaran Jawa, tampilkan pilihan pasaran saja.
4. Jika Minggu dalam bulan, tampilkan pilihan minggu saja.
5. Jika Normal, sembunyikan seluruh atribut khusus.

## 4. Aturan minggu kalender dalam bulan

Gunakan **Model A**:
- Minggu kalender berjalan dari Senin sampai Minggu.
- Minggu pertama bulan dimulai pada tanggal 1 dan berakhir pada hari Minggu pertama.
- Minggu berikutnya berjalan Senin–Minggu.
- Minggu terakhir berakhir pada hari terakhir bulan.
- Minggu ke-5 tidak selalu ada; pilihan Minggu terakhir tetap tersedia sebagai aturan tersendiri.

Contoh Oktober 2026:
- Minggu ke-1: 1–4 Oktober
- Minggu ke-2: 5–11 Oktober
- Minggu ke-3: 12–18 Oktober
- Minggu ke-4: 19–25 Oktober
- Minggu ke-5: 26–31 Oktober

Contoh: jadwal Senin + Minggu ke-2 pada Oktober 2026 berlaku pada Senin, 5 Oktober 2026.

## 5. Prioritas pencocokan jadwal

Untuk tanggal tertentu di dalam paket aktif:
1. Cari jadwal khusus yang cocok dengan hari dan atribut tanggal.
2. Jika satu jadwal khusus cocok, gunakan jadwal khusus itu dan jangan menjalankan jadwal Normal hari tersebut bersamaan.
3. Jika tidak ada jadwal khusus yang cocok, gunakan jadwal Normal untuk hari tersebut.
4. Jangan menjalankan dua paket sekaligus.
5. Jika lebih dari satu aturan khusus berpotensi cocok pada tanggal yang sama—misalnya Kamis Legi sekaligus Kamis minggu ke-2—aplikasi harus mendeteksi dan memperingatkan operator. Kebijakan penyelesaian konflik harus jelas sebelum jadwal bentrok diizinkan aktif.

Aturan di atas harus diuji dengan tanggal nyata, termasuk pergantian bulan dan tahun.

## 6. Pengelolaan kegiatan bel

Setiap kegiatan bel sekurang-kurangnya memiliki:
- Nama kegiatan yang dapat diubah.
- Waktu pemicu.
- Referensi file audio lokal.
- Status aktif/nonaktif.
- Urutan tampilan yang mudah dipahami.

Contoh kegiatan: Bel Masuk, Pergantian Jam, Istirahat, Indonesia Raya, dan Bel Pulang. Daftar ini hanya contoh, bukan nama yang wajib dikunci di kode.

Kebutuhan lanjutan yang perlu ditetapkan saat UI dibuat:
- Apakah volume dapat diatur per kegiatan atau hanya global.
- Format audio yang didukung secara final.
- Perilaku jika dua audio dijadwalkan pada waktu yang sama.
- Perilaku jika operator menekan uji audio saat audio lain sedang berjalan.

## 7. Mesin audio

- Audio diputar oleh komponen internal yang menjadi bagian dari aplikasi.
- Tidak mengandalkan pemutar musik eksternal yang harus dijalankan operator.
- Audio keluar melalui perangkat output suara Windows yang dipilih/default.
- Audio harus dapat diputar sampai selesai, termasuk audio panjang seperti lagu Indonesia Raya.
- Aplikasi menampilkan status pemutaran.
- Kesalahan file hilang, file tidak valid, atau perangkat audio gagal harus dilaporkan dan dicatat.
- Audio tidak boleh dipotong secara tidak sengaja ketika jadwal berikutnya diproses.

Dukungan format dan implementasi mesin audio harus dibuktikan lewat prototipe teknis sebelum diputuskan final.

## 8. Penjadwalan dan keandalan

- Aplikasi memeriksa waktu berdasarkan jam sistem Windows dan zona waktu lokal komputer.
- Tidak boleh memutar kejadian jadwal yang sama berulang-ulang hanya karena pemeriksaan waktu berlangsung setiap detik.
- Simpan identitas kejadian yang telah diproses, minimal berdasarkan tanggal, paket, jadwal, dan waktu kejadian.
- Jika aplikasi baru dibuka setelah waktu jadwal lewat, jangan otomatis memutar semua bel yang terlewat. Perilaku default yang diusulkan: tandai sebagai terlewat/log, lalu lanjutkan ke jadwal berikutnya.
- Jika komputer tidur atau aplikasi berhenti, audio tidak dapat dijamin berjalan pada waktu tersebut. Perilaku setelah komputer bangun harus diuji dan didokumentasikan.
- Perlu ada cara menguji audio secara manual tanpa mengubah jadwal produksi.

## 9. Status dan log

Dashboard setidaknya menampilkan:
- Paket jadwal aktif.
- Tanggal dan jam saat ini.
- Kegiatan berikutnya dan waktu pemicunya.
- Status audio: menunggu, sedang diputar, selesai, gagal, atau terlewat.
- Riwayat kejadian terakhir.

Log harus membantu operator mengetahui waktu kejadian, nama kegiatan, paket aktif, hasil pemutaran, dan keterangan kesalahan bila ada.

## 10. Notifikasi dan tampilan

- Popup notifikasi Windows bukan kebutuhan wajib.
- Fokus utama adalah otomatisasi audio dan dashboard status.
- System tray diinginkan jika layak secara teknis, sehingga aplikasi dapat diminimalkan tanpa menghentikan penjadwalan.

## 11. Kinerja dan distribusi

- Ringan saat idle; konsumsi CPU/RAM akan diukur saat prototipe tersedia.
- Tidak membutuhkan internet untuk jadwal dan audio lokal.
- Pengaturan harus tetap tersimpan setelah aplikasi ditutup.
- Distribusi harus dapat dijalankan pada komputer Windows sasaran tanpa XAMPP.
- Metode portable atau installer ditentukan setelah uji fondasi teknis.
