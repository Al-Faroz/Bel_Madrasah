# 03 — Roadmap Pengerjaan Baru

## Aturan status

- **DOC READY:** kebutuhan dan urutan kerja terdokumentasi.
- **AUDIT:** kode dan dependensi diperiksa, belum berarti benar.
- **IMPLEMENTED:** kode tersedia di repository.
- **LOCAL TEST PASS:** pengguna telah menjalankan skenario pada PHP Desktop dan melaporkan hasilnya.
- PoC yang pernah PASS hanya membuktikan skenario PoC terkait; tidak otomatis meluluskan fitur produksi.

## Keputusan reset

Tahap 1–4 sebelumnya adalah PoC/fondasi untuk membuktikan PHP Desktop Chrome 130.1 + PHP 8.3, SQLite, audio internal, konfigurasi/backup dasar, dan paket jadwal bisa berjalan. Hasil itu dipertahankan sebagai bukti teknis. Roadmap produk di bawah dimulai kembali dari **Fase A**, tanpa mengulang eksperimen yang sudah terbukti dan tanpa menganggap seluruh UI/fitur lama sudah benar.

**Strategi:** audit → kontrak kebutuhan/data → design system dan app shell → halaman per modul → aturan kalender → scheduler/audio → backup/restore dan hardening → distribusi.

## Fase A — Audit baseline dan inventarisasi

**Status: NEXT**

- Inventarisasi semua halaman PHP, class Database, skema/migrasi SQLite, fungsi POST/GET, file audio, navigasi, CSS, dan JavaScript.
- Tandai tiap bagian: reuse, perbaiki, pindahkan, atau buang setelah konfirmasi.
- Cocokkan nama field/method yang benar-benar tersedia dengan pemanggilnya.
- Catat bug yang tampak dari pengujian pengguna; jangan memperbaiki hanya dari dugaan.
- Pertahankan data AppData dan buat salinan backup sebelum perubahan yang menyentuh skema.

**Kriteria selesai:** laporan audit dan daftar pekerjaan yang tidak tumpang tindih; tidak ada penghapusan data atau reset database otomatis.

## Fase B — Spesifikasi produk dan kontrak data

- Tetapkan entitas, relasi, validasi, status aktif/nonaktif, serta perilaku migrasi.
- Tetapkan alur operator dari membuka aplikasi sampai mengatur paket, hari, kegiatan, audio, backup, dan pemeriksaan status.
- Definisikan perilaku jadwal Normal/Khusus, pasaran Jawa, minggu kalender, prioritas, konflik, dan jadwal terlewat sebelum membuat resolver.
- Definisikan makna status yang boleh ditampilkan. Jadwal tersimpan bukan bukti audio sedang diputar.
- Susun kriteria penerimaan tiap modul.

**Kriteria selesai:** aturan bisnis dan kontrak data terdokumentasi; semua ketidakpastian diberi keputusan atau ditandai untuk ditanyakan.

## Fase C — UI/UX dan app shell (menggunakan RoyalUI sebagai referensi)

- Audit screenshot dan struktur halaman yang sekarang; jangan menganggap tampilan sekarang sebagai acuan final.
- Tetapkan layout admin, navigasi, header, lebar konten, grid, typography, warna, spacing, form, tabel, dialog, notifikasi, dan empty/error state.
- Buat satu app shell dan komponen CSS bersama sebelum redesign per halaman.
- Buat konsep dashboard admin yang berorientasi operasional, bukan halaman diagnostik.
- Buat public dashboard terpisah, minim kontrol admin, dengan informasi yang bisa dibaca dari kejauhan.
- Hindari ketergantungan CDN saat runtime; aset yang diperlukan untuk offline harus tersedia lokal.
- Pastikan lisensi template/aset ditinjau sebelum disalin.

**Kriteria selesai:** satu bahasa visual konsisten dan mockup/struktur halaman disetujui sebelum seluruh halaman ditata ulang.

## Fase D — Implementasi UI modul secara terurut

Urutan ini sengaja satu jalur agar tidak tumpang tindih:

1. **D1 App shell + Dashboard Admin**
2. **D2 Paket Jadwal**
3. **D3 Editor Jadwal** — termasuk salin jadwal, preview, konfirmasi, dan validasi
4. **D4 Konfigurasi**
5. **D5 Backup dan Restore** — restore baru boleh dilakukan setelah validasi file dan konfirmasi
6. **D6 Pemeriksaan/diagnostik** — tetap tersedia sebagai alat bantu, bukan beranda utama
7. **D7 Public Dashboard**
8. **D8 Polish dan regresi UI**

Setiap modul: audit kode → implementasi satu modul → pemeriksaan statis → pengguna menguji → perbaiki temuan → tandai status. Jangan mengerjakan banyak halaman sekaligus tanpa hasil uji di antaranya.

**Kriteria selesai:** alur UI konsisten, setiap kontrol menjalankan aksi nyata, data lama tetap utuh, dan pengguna melaporkan skenario uji lulus.

## Fase E — Resolver kalender dan prioritas

- Implementasikan perhitungan hari, pasaran Jawa, minggu kalender, dan minggu terakhir.
- Terapkan aturan prioritas Khusus terhadap Normal sesuai spesifikasi.
- Deteksi konflik; untuk satu tanggal dan paket aktif harus terpilih paling banyak satu aturan.
- Sediakan pratinjau “jadwal yang berlaku pada tanggal ini” untuk operator.

**Kriteria selesai:** pengujian unit/fixture untuk pergantian bulan/tahun, awal/akhir bulan, pasaran, minggu ke-1–5/minggu terakhir, fallback Normal, dan konflik.

## Fase F — Scheduler dan pemutaran audio otomatis

- Hubungkan resolver dengan jam sistem dan pemutar audio internal.
- Cegah pemutaran ganda untuk satu kejadian jadwal.
- Tentukan kebijakan aplikasi dibuka terlambat, Windows sleep/wake, perubahan jam, dan aplikasi ditutup.
- Catat status aktual: menunggu, memulai, diputar, selesai, gagal, atau terlewat.
- Pastikan status dashboard bersumber dari status runtime, bukan prediksi tampilan.

**Kriteria selesai:** uji nyata dengan speaker/output target, beberapa jadwal berurutan, audio panjang, restart, sleep/wake, dan tidak ada pemutaran ganda.

## Fase G — Backup, restore, stabilitas, dan distribusi

- Verifikasi integritas backup dengan membuka/memeriksa salinan database.
- Implementasikan restore dengan validasi, konfirmasi, dan perlindungan salinan sebelum pemulihan.
- Uji database terkunci, disk penuh, folder tidak dapat ditulis, file audio hilang/rusak, serta shutdown paksa.
- Audit CPU/RAM idle dan selama audio.
- Dokumentasikan distribusi PHP Desktop, lokasi data, pembaruan aplikasi, dan prosedur pemulihan.

**Kriteria selesai:** backup dapat diverifikasi, restore dapat dipulihkan, data pengguna aman, dan paket dapat diuji di Windows tanpa XAMPP.

## Fase H — Uji penerimaan dan rilis

- Jalankan checklist end-to-end sesuai [04-TEST-PLAN.md](04-TEST-PLAN.md).
- Catat versi runtime, Windows, perangkat audio, dan hasil.
- Perbaiki temuan kritis sebelum rilis.
- Dokumentasikan keterbatasan yang masih tersisa.

**Kriteria selesai:** semua fungsi inti memiliki bukti uji lokal; status yang belum diuji tetap terbuka.

## Aturan implementasi

- Jangan merombak fondasi runtime/SQLite yang telah terbukti tanpa alasan dan tes regresi.
- Jangan melakukan migrasi destruktif atau menghapus data.
- Jangan menambahkan tombol yang hanya dekoratif.
- Jangan menyebut halaman “beres” sebelum pengguna memeriksa tampilannya di runtime.
- Commit kecil, fokus, dan terpisah; dokumen diperbarui bila urutan atau keputusan berubah.
