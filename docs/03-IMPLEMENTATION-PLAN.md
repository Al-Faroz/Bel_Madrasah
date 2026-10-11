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

