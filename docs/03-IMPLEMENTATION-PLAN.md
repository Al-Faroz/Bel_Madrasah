# 03 — Roadmap Pengerjaan Baru

## Aturan status

- **DOC READY:** kebutuhan dan urutan kerja terdokumentasi.
- **AUDIT:** kode dan dependensi diperiksa, belum berarti benar.
- **IMPLEMENTED:** kode tersedia di repository.
- **LOCAL TEST PASS:** pengguna telah menjalankan skenario pada PHP Desktop dan melaporkan hasilnya.
- PoC yang pernah PASS hanya membuktikan skenario PoC terkait; tidak otomatis meluluskan fitur produksi.

## Keputusan reset

Tahap 1–4 sebelumnya adalah PoC/fondasi untuk membuktikan PHP Desktop Chrome 130.1 + PHP 8.3, SQLite, audio internal, konfigurasi/backup dasar, dan paket jadwal bisa berjalan. Hasil itu dipertahankan sebagai bukti teknis. Roadmap produk dimulai kembali dari Fase A, tanpa mengulang eksperimen yang sudah terbukti dan tanpa menganggap seluruh UI/fitur lama sudah benar.

## Keputusan arsitektur UI (sudah disepakati)

- Public area adalah halaman pertama saat aplikasi dibuka.
- Dashboard admin dibuka melalui tautan/tombol dari public area.
- Tidak ada login/Auth; dashboard admin langsung dapat diakses.
- Gunakan PHP native terstruktur dengan layout dan partial views memakai `require`.
- Pisahkan layout admin dan public.
- Public mempunyai CSS/JS sendiri bila diperlukan; jangan membebani public dengan aset atau interaksi admin.
- Sediakan folder aset operator untuk logo dan audio serta petunjuk pemakaian di dalam aplikasi.
- Nama dan struktur folder aset final harus ditentukan setelah memeriksa bagaimana pemutar audio dan pemilih file saat ini menangani path.
- Tidak boleh mereset database atau memindahkan data AppData demi merapikan UI.

## Fase A — Audit baseline dan inventarisasi

**Status: IN PROGRESS**

- Inventarisasi semua halaman PHP, class Database, skema/migrasi SQLite, fungsi POST/GET, file audio, navigasi, CSS, dan JavaScript.
- Tandai tiap bagian: reuse, perbaiki, pindahkan, atau buang setelah konfirmasi.
- Cocokkan nama field/method yang benar-benar tersedia dengan pemanggilnya.
- Audit bagaimana path audio disimpan dan bagaimana file dipilih/diputar sebelum menetapkan folder aset.
- Catat bug yang tampak dari pengujian pengguna; jangan memperbaiki hanya dari dugaan.
- Pertahankan data AppData dan buat salinan backup sebelum perubahan yang menyentuh skema.

**Kriteria selesai:** laporan audit dan daftar pekerjaan yang tidak tumpang tindih; kontrak path aset dan entry point disepakati; tidak ada penghapusan data atau reset database otomatis.

## Fase B — Spesifikasi produk dan kontrak data

- Tetapkan entitas, relasi, validasi, status aktif/nonaktif, serta perilaku migrasi.
- Tetapkan alur operator dari membuka public area sampai mengatur paket, hari, kegiatan, audio, backup, dan pemeriksaan status.
- Definisikan perilaku jadwal Normal/Khusus, pasaran Jawa, minggu kalender, prioritas, konflik, dan jadwal terlewat sebelum membuat resolver.
- Definisikan makna status yang boleh ditampilkan. Jadwal tersimpan bukan bukti audio sedang diputar.
- Definisikan kontrak aset: lokasi folder, subfolder audio/logo, ekstensi yang didukung, perilaku file hilang, dan instruksi operator.
- Susun kriteria penerimaan tiap modul.

**Kriteria selesai:** aturan bisnis dan kontrak data terdokumentasi; semua ketidakpastian diberi keputusan atau ditandai untuk ditanyakan.

## Fase C — Arsitektur view dan design system

1. Tentukan struktur direktori PHP native untuk `layouts/`, `partials/`, `views/`, `assets/`, dan `lib/`, disesuaikan dengan file aktual.
2. Buat mekanisme render view yang memvalidasi path view yang diizinkan; hindari memakai input pengguna langsung sebagai path `require`.
3. Bangun layout public yang minimal dengan CSS/JS terpisah.
4. Bangun layout admin dengan navigasi konsisten, header, breadcrumb opsional, flash message, footer, dan slot konten.
5. Audit CSS lama dan rencanakan penggantian yang terkontrol; jangan menumpuk override kosmetik di akhir stylesheet.
6. Gunakan RoyalUI sebagai referensi dashboard, bukan dependensi remote runtime; tinjau lisensi dan siapkan aset lokal bila digunakan.
7. Tambahkan instruksi folder aset operator di antarmuka.

**Kriteria selesai:** struktur view dan app shell disetujui, public tetap ringan, dan komponen bersama punya kontrak yang jelas.

## Fase D — Implementasi per modul

1. **D1 Public Area** — halaman awal, informasi penting, tautan ke Dashboard Admin, instruksi singkat aset operator bila sesuai.
2. **D2 Admin App Shell + Dashboard** — partial views, navigasi bersama, ringkasan operasional.
3. **D3 Paket Jadwal**
4. **D4 Editor Jadwal** — termasuk salin jadwal, preview, konfirmasi, validasi.
5. **D5 Konfigurasi**
6. **D6 Backup dan Restore** — restore dengan validasi file, backup pra-pemulihan, dan konfirmasi.
7. **D7 Pemeriksaan/diagnostik** — alat bantu, bukan halaman awal.
8. **D8 Polish dan regresi UI**

Setiap modul: audit kode → implementasi satu modul → pemeriksaan statis → pengguna menguji di PHP Desktop → perbaiki temuan → tandai status. Jangan mengerjakan banyak halaman sekaligus tanpa hasil uji di antaranya.

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
- Catat status aktual: menunggu, memulai, diputar, selesai, gagal; status harus berasal dari mesin runtime.
- Uji file audio hilang, format tidak didukung, dan jalur folder aset.

## Fase G — Backup/restore dan hardening

- Backup konsisten untuk SQLite; validasi hasil dan laporkan lokasi file.
- Restore memvalidasi database, membuat backup pra-restore, lalu meminta konfirmasi.
- Periksa validasi input, escaping output, CSRF yang relevan, transaksi, dan error handling.
- Pastikan pembaruan aplikasi tidak menghapus database dan aset pengguna.

## Fase H — Distribusi dan uji penerimaan

- Uji di PHP Desktop Chrome 130.1 + PHP 8.3 pada Windows.
- Uji resolusi layar, restart aplikasi, file audio/logo, backup/restore, jadwal kalender, sleep/wake, dan kegagalan audio.
- Dokumentasikan cara memasang aplikasi, menaruh aset, mengganti logo/audio, serta memulihkan backup.
- Jangan tandai siap produksi sampai skenario penerimaan inti lulus.

## Prinsip pengerjaan

Satu batch harus punya tujuan tunggal, commit tersendiri, dan checklist uji. Jangan mengklaim lulus runtime dari pemeriksaan kode statis.