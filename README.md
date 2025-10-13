# Aplikasi PKL Digital Lengkap

Aplikasi Jurnal dan Observasi Praktik Kerja Lapangan (PKL) Digital adalah sebuah platform berbasis web yang dirancang untuk memodernisasi dan menyederhanakan proses manajemen PKL. Aplikasi ini menghubungkan empat peran pengguna utama—**Siswa**, **Instruktur Industri**, **Guru Pembimbing**, dan **Admin Sekolah**—dalam satu ekosistem yang terintegrasi.

Dibangun dengan **PHP Native** dan **Bootstrap 5**, aplikasi ini mengutamakan keamanan, kemudahan penggunaan, dan desain yang elegan serta responsif di semua perangkat.

---

## ✨ Fitur Utama

### 👤 Peran & Fungsionalitas

1.  **Admin Sekolah / Koordinator PKL**
    *   **Dashboard Global:** Melihat ringkasan total siswa, DUDIKA, dan guru.
    *   **Manajemen Data Master:** CRUD (Create, Read, Update, Delete) untuk data Siswa, Guru, DUDIKA, dan Instruktur melalui form modal yang intuitif.
    *   **Mapping PKL:** Menghubungkan Siswa dengan Guru Pembimbing dan Instruktur DUDIKA.
    *   **Rekapitulasi & Laporan:** Menghasilkan rekap absensi bulanan dan daftar nilai akhir seluruh sekolah, dengan fitur ekspor ke CSV/Excel.

2.  **Siswa (Peserta Didik)**
    *   **Dashboard Progres:** Memantau persentase kehadiran dan status jurnal.
    *   **Jurnal Harian:** Fitur Check-in/Check-out, pengisian deskripsi kegiatan, dan melihat riwayat jurnal beserta status verifikasi (Pending, Approved, Rejected).
    *   **Lihat Penilaian:** Menampilkan hasil penilaian dari instruktur dalam bentuk diagram radar yang elegan.
    *   **Konsultasi Laporan:** Mengunggah draf laporan untuk ditinjau oleh guru pembimbing dan melihat feedback.

3.  **Instruktur Industri (DUDIKA)**
    *   **Dashboard Aksi:** Notifikasi untuk jurnal yang menunggu verifikasi dan siswa yang belum dinilai.
    *   **Verifikasi Jurnal:** Menyetujui (Approve) atau menolak (Reject) jurnal harian siswa bimbingan.
    *   **Input Penilaian:** Memberikan penilaian observasi kepada siswa menggunakan slider skor (1-100) dan feedback deskriptif.

4.  **Guru Pembimbing**
    *   **Dashboard Monitoring:** Memantau daftar siswa bimbingan dan persentase kelancaran jurnal mereka.
    *   **Monitoring Detail:** Melihat riwayat jurnal dan absensi siswa bimbingan dengan fitur filter.
    *   **Konsultasi Laporan:** Mengunduh draf laporan yang diunggah siswa dan memberikan catatan feedback langsung di aplikasi.

---

## 🛠️ Teknologi yang Digunakan

*   **Backend:** **PHP Native (8.x)**
*   **Frontend:** HTML5, CSS, JavaScript (Vanilla)
*   **Framework UI:** **Bootstrap 5.3**
*   **Database:** MySQL / MariaDB
*   **Visualisasi Data:** Chart.js (untuk diagram radar)

---

## 🚀 Panduan Instalasi dan Konfigurasi

1.  **Clone Repositori**
    ```bash
    git clone https://github.com/username/repo-name.git
    cd repo-name
    ```

2.  **Setup Database**
    *   Buat sebuah database baru di server MySQL/MariaDB Anda (misalnya, `pkl_digital_app`).
    *   Impor skema dan data awal dari file `db/database.sql` ke dalam database yang baru Anda buat.
        ```bash
        mysql -u username -p pkl_digital_app < db/database.sql
        ```

3.  **Konfigurasi Aplikasi**
    *   Buka file `config/config.php`.
    *   Sesuaikan kredensial database (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) dengan konfigurasi server Anda.
        ```php
        define('DB_HOST', '127.0.0.1');
        define('DB_USER', 'root');
        define('DB_PASS', ''); // Sesuaikan dengan password Anda
        define('DB_NAME', 'pkl_digital_app');
        ```
    *   Sesuaikan `BASE_URL` dengan URL root proyek Anda di server lokal (misalnya, `http://localhost/pkl-digital-app`).

4.  **Jalankan Aplikasi**
    *   Letakkan direktori proyek di dalam `htdocs` (untuk XAMPP) atau `www` (untuk WAMP).
    *   Akses `BASE_URL` yang telah Anda atur di browser.

---

## 🔑 Informasi Login Default

Berikut adalah kredensial default untuk setiap peran. **Password sama dengan Username**.

| Peran | Username | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin Sekolah** | `admin` | `admin` | Kredensial statis. |
| **Siswa** | NISN Siswa | NISN Siswa | Dibuat oleh Admin. |
| **Guru Pembimbing** | NIP Guru | NIP Guru | Dibuat oleh Admin. |
| **Instruktur DUDIKA**| No. Seri | No. Seri | Dibuat otomatis oleh sistem saat Admin menambahkan data. |

**Catatan Keamanan:** Semua password disimpan dalam bentuk *hash* di database untuk keamanan.