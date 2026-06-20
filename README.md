# 📦 LogistikG

**Sistem Manajemen Pengajuan & Pengadaan Logistik Kepanitiaan**

> Aplikasi web berbasis PHP Native + MySQL untuk menggantikan sistem Excel manual dalam pengelolaan pengadaan barang dan inventaris antar divisi dalam kepanitiaan multi-event.

---

## 📖 Studi Kasus & Penyelesaian Masalah Riil

**Latar Belakang Masalah:**
Dalam sebuah penyelenggaraan acara (seperti Seminar Nasional), koordinasi pengadaan barang antara berbagai Divisi (Acara, Humas, Konsumsi) dengan Divisi Perlengkapan (Perkap) sering kali kacau karena masih menggunakan grup _chat_ atau pendataan _spreadsheet/Excel_ manual. Masalah yang sering timbul antara lain:

1. Kesalahan spesifikasi barang karena tidak ada acuan visual yang jelas.
2. Status pengajuan yang tidak transparan (apakah barang sedang dibeli, dipinjam, atau sudah ada?).
3. Data yang tumpang tindih antar acara (jika panitia mengelola lebih dari satu event sekaligus).

**Solusi dari Aplikasi LogistikG:**
Aplikasi ini menyelesaikan masalah tersebut dengan menyediakan platform satu pintu. Divisi pengaju wajib menyertakan kuantitas dan foto referensi barang. Di sisi lain, Koor Perkap memiliki dashboard utama untuk mengubah status pengadaan (Beli/Pinjam), melakukan _Quality Control_ (QC) kondisi barang, serta mengunggah bukti fisik barang yang sudah siap. Sistem juga dirancang secara dinamis agar mampu menampung banyak event (Multi-Event) tanpa membuat data saling tumpang tindih.

## 🚀 Fitur Utama

| Fitur                                                 | Koor Divisi (User) | Koor Perkap (Admin) |
| ----------------------------------------------------- | :----------------: | :-----------------: |
| Login & Logout terenkapsulasi session                 |         ✅         |         ✅          |
| Pengajuan Barang + Upload Foto Referensi              |         ✅         |          —          |
| Halaman Dashboard Khusus Divisi                       |         ✅         |          —          |
| Edit / Hapus Pengajuan (hanya saat status 'Diajukan') |         ✅         |          —          |
| Dashboard Terintegrasi (Filter Dinamis & Multi-Tabel) |         —          |         ✅          |
| Manajemen Multi-Event (Modal UI)                      |         —          |         ✅          |
| Cetak Rekapitulasi Laporan (PDF Format Ringan)        |         —          |         ✅          |
| Update Status & Metode (Beli/Pinjam)                  |         —          |         ✅          |
| Quality Control + Upload Foto Bukti Fisik             |         —          |         ✅          |
| Registrasi Divisi & Akun Koor Cepat                   |         —          |         ✅          |

---

## 🗂️ Struktur Database (7 Tabel)

Database menggunakan nama `db_logistikG` dengan total **7 Tabel** yang direlasikan secara valid menggunakan _Primary Key_ (PK) dan _Foreign Key_ (FK). Berikut adalah strukturnya:

    db_logistikG
    ├── tabel_role          ← Hak akses (Koor Perkap, Koor Divisi)
    ├── tabel_user          ← Kredensial login + relasi role & divisi
    ├── tabel_event         ← Data acara (nama, tanggal, lokasi)
    ├── tabel_divisi        ← Divisi kepanitiaan (Composite Unique Key ke Event)
    ├── tabel_master_barang ← Katalog logistik referensi baku
    ├── tabel_pengajuan     ← Request barang dari divisi + foto referensi
    └── tabel_pengadaan     ← Proses pengadaan + catatan QC + foto bukti

---

## 🛠️ Tech Stack

| Layer        | Teknologi                               |
| ------------ | --------------------------------------- |
| Backend      | PHP 8.x Native (tanpa framework)        |
| Database     | MySQL 8.x (menggunakan driver `mysqli`) |
| Frontend     | HTML5, CSS3, JavaScript                 |
| Framework UI | Bootstrap 5.3 + Bootstrap Icons         |
| Server Lokal | XAMPP (Apache + MySQL)                  |

---

## ⚙️ Cara Instalasi & Menjalankan (XAMPP)

### 1. Prasyarat

- **XAMPP** versi 8.x ke atas.
- Pastikan Port 3306 (MySQL) dan Port 80 (Apache) tidak bentrok.

### 2. Persiapan Folder

Salin seluruh folder proyek `LogistikG` ke direktori `htdocs` XAMPP Anda:

- **Windows:** `C:\xampp\htdocs\LogistikG`

### 3. Import Database

1. Buka browser dan akses `http://localhost/phpmyadmin`
2. Buat database baru bernama `db_logistikG`
3. Pilih database tersebut, klik tab **Import**.
4. Masukkan file `database/schema.sql` lalu klik **Go**.

### 4. Konfigurasi Koneksi (Opsional)

Jika _username_ dan _password_ MySQL Anda berbeda dari bawaan XAMPP, sesuaikan pada file `config/database.php`:

    $host = 'localhost';
    $user = 'root';
    $pass = ''; // Sesuaikan jika MySQL Anda memiliki password
    $db   = 'db_logistikG';

### 5. Akses Aplikasi

Buka browser dan jalankan URL berikut:

    http://localhost/LogistikG/

---

## 👤 Akun Login Default (Sampel)

| Role Akses         | Divisi / Tanggung Jawab     | Username           | Password    |
| ------------------ | --------------------------- | ------------------ | ----------- |
| 🔑 **Koor Perkap** | Administrator Utama         | `perkap`           | `admin123`  |
| 👤 **Koor Divisi** | Divisi Acara (Seminar)      | `acara_seminar`    | `divisi123` |
| 👤 **Koor Divisi** | Divisi Humas (Seminar)      | `humas_seminar`    | `divisi123` |
| 👤 **Koor Divisi** | Divisi Konsumsi (Seminar)   | `konsumsi_seminar` | `divisi123` |
| 👤 **Koor Divisi** | Divisi Konsumsi (Funtastic) | `konsumsi_fun`     | `divisi123` |

_(Catatan: Akun Koor Divisi baru dapat dibuat secara dinamis oleh Admin melalui halaman Detail Event)._

---

## 📁 Struktur Direktori Utama

    LogistikG/
    ├── admin/
    │   ├── dashboard.php         ← Dashboard filter data terintegrasi
    │   ├── event.php             ← Manajemen multi-event
    │   ├── detail_event.php      ← Ruang kontrol spesifik per event + tambah divisi
    │   └── proses_pengajuan.php  ← Form QC, update status, dan metode
    ├── assets/
    │   └── uploads/
    │       ├── referensi/        ← Foto acuan dari Koor Divisi
    │       └── bukti/            ← Foto fisik hasil pengadaan Perkap
    ├── config/
    │   └── database.php          ← Koneksi mysqli & fungsi utilitas
    ├── database/
    │   └── schema.sql            ← Skema tabel, trigger, & sample data
    ├── user/
    │   ├── dashboard.php         ← Rangkuman pengajuan divisi terkait
    │   ├── buat_pengajuan.php    ← Form request & upload acuan
    │   └── (file CRUD lainnya)
    ├── auth_check.php            ← Middleware validasi session RBAC
    ├── index.php                 ← Router login redirection
    ├── login.php                 ← Autentikasi utama
    └── logout.php                ← Session destroy

---

## 🧩 Alur Penggunaan Dasar

1. **Setup Awal (Koor Perkap):** Login, masuk ke menu _Event_, tambahkan event baru. Buka detail event tersebut, lalu daftarkan divisi-divisi beserta akunnya.
2. **Pengajuan (Koor Divisi):** Login dengan akun yang baru dibuat, ajukan barang, masukkan kuantitas, dan unggah foto referensi agar tidak salah beli.
3. **Eksekusi (Koor Perkap):** Pantau halaman _Dashboard_. Ubah status menjadi "Diproses" dan tentukan metode pengadaan (Beli/Pinjam). Setelah barang ada, ubah ke "Selesai", masukkan catatan kondisi (QC), dan unggah foto bukti fisiknya.
4. **Pelaporan:** Di akhir acara, tekan tombol "Cetak Rekapitulasi" untuk Export ke PDF.

---

## 👨‍💻 Hak Cipta & Pengembangan

Dikembangkan khusus untuk memenuhi kebutuhan digitalisasi manajemen logistik kepanitiaan.

**Pengembang:** Azka Nazalla (202431133)  
**Institusi:** Institut Teknologi PLN (ITPLN) - Teknik Informatika / Asisten Laboratorium

_Selesai dikembangkan pada: Juni 2026_
