# 08 — Pengemasan Windows dan Installer

## Tujuan

Menyediakan installer Windows `BelMadrasah-Setup.exe` yang memasang aplikasi bersama seluruh runtime PHP Desktop. Pengguna tidak perlu memasang XAMPP, Apache/MySQL, atau PHP secara terpisah.

**Status:** rancangan packaging. Installer final belum dikompilasi atau diuji pada Windows.

## Keputusan distribusi

- Target runtime: PHP Desktop Chrome 130.1 + PHP 8.3 untuk Windows 64-bit, sesuai runtime yang saat ini diuji.
- Gunakan Inno Setup untuk membuat installer.
- Runtime upstream tidak disimpan di Git repository karena berisi binari berukuran besar.
- Installer memasang aplikasi untuk pengguna Windows saat ini, dengan shortcut Start Menu dan opsi shortcut Desktop.
- Folder program hanya berisi runtime dan source aplikasi. Database, konfigurasi, aset audio/logo, serta backup pengguna tetap berada di `%APPDATA%\BelMadrasah` atau fallback `%LOCALAPPDATA%\BelMadrasah`.
- Update atau uninstall tidak boleh menghapus folder data pengguna.
- Salinan `settings.json` yang dikemas harus memakai `debugging.show_console = false` dan kebijakan autoplay `no-user-gesture-required`, tanpa menghapus pengaturan runtime lain.

## Menyiapkan runtime sumber

1. Unduh paket PHP Desktop Chrome 130.1 for Windows dari halaman rilis resmi: https://github.com/cztomczak/phpdesktop/releases
2. Ekstrak ke folder lokal yang bukan folder source aplikasi, misalnya `D:\BelMadrasah-phpdesktop-runtime`.
3. Pastikan folder tersebut berisi `phpdesktop-chrome.exe` dan `settings.json`.
4. Jangan menyalin database produksi, folder data pengguna, atau file audio pribadi ke folder runtime sumber.
5. Jalankan skrip persiapan packaging dari PowerShell pada repository. Skrip akan menyalin runtime ke `build\installer-stage`, mengganti folder `www` dengan source aplikasi terbaru, dan memperbarui hanya pengaturan console/autoplay yang diperlukan pada salinan staging.

Contoh pemanggilan setelah skrip tersedia:

```powershell
powershell -ExecutionPolicy Bypass -File .\installer\Prepare-Installer.ps1 -RuntimeSource "D:\BelMadrasah-phpdesktop-runtime"
```

## Membuat installer

1. Pasang Inno Setup 6 dari situs resminya: https://jrsoftware.org/isinfo.php
2. Jalankan `installer\BelMadrasah.iss` di Inno Setup Compiler atau kompilasi melalui `ISCC.exe`.
3. Output setup akan ditempatkan di `build\installer-output\BelMadrasah-Setup-*.exe`.
4. Jangan menyebut installer siap rilis sebelum tes instalasi bersih, upgrade, audio, dan preservasi data selesai.

## Uji penerimaan installer

### Instalasi bersih

- [ ] Persiapan staging berhasil dan mencakup runtime PHP Desktop lengkap serta source `www` terbaru.
- [ ] `settings.json` staging valid; `show_console` dan `subprocess_show_console` bernilai `false), dan switch autoplay tersedia.
- [ ] Inno Setup berhasil menghasilkan `BelMadrasah-Setup-*.exe`.
- [ ] Installer dapat dijalankan pada Windows 10/11 64-bit dengan akun pengguna standar.
- [ ] Aplikasi terbuka dari shortcut Start Menu tanpa jendela console.
- [ ] Shortcut Desktop bekerja bila opsi itu dipilih.
- [ ] Dashboard Publik dan Admin terbuka; SQLite dapat ditulis; aplikasi tidak memerlukan XAMPP atau koneksi internet.
- [ ] Audio MP3/WAV terjadwal terdengar dan log pemutaran sesuai.
- [ ] Installer tidak mengemas data atau aset pribadi dari mesin pembuat.

### Update dan preservasi data

- [ ] Buat data uji yang mudah dikenali, satu jadwal percobaan, dan satu backup; tutup aplikasi.
- [ ] Jalankan installer versi baru di lokasi instalasi yang sama.
- [ ] Setelah update, data jadwal, konfigurasi, aset audio/logo, dan backup tetap tersedia.
- [ ] Jalankan aplikasi setelah update; pastikan scheduler, audio, Publik, dan Admin tetap bekerja.

### Uninstall dan instal ulang

- [ ] Uninstall aplikasi dari Windows Apps/Programs.
- [ ] Pastikan folder program dan shortcut aplikasi dihapus.
- [ ] Pastikan `%APPDATA%\BelMadrasah` tidak ikut terhapus.
- [ ] Instal ulang aplikasi dan pastikan data pengguna yang dipertahankan dapat dibaca.
- [ ] Jangan menguji uninstall menggunakan satu-satunya salinan data penting; buat backup terpisah lebih dahulu.

## Batasan

- Kode installer dan skrip staging adalah alat packaging; keberhasilan compile tidak membuktikan aplikasi lulus runtime.
- Distribusi publik/produksi memerlukan peninjauan lisensi PHP Desktop, PHP, Chromium, dan seluruh aset yang dikemas.
- Penandatanganan kode dan reputasi SmartScreen belum termasuk tahap ini.
- Installer final tidak dapat dihasilkan dari source repository saja; runtime resmi PHP Desktop harus tersedia secara lokal.
