# Sistem Manajemen Karyawan - Absensi & Cuti

Sistem manajemen karyawan berbasis web dengan fitur absensi real-time, pengajuan cuti dengan bukti wajib, dan reporting. Dibangun dengan Laravel 12, Firebase Realtime Database dan Cloudinary.

## Spesifikasi Sistem

### Environment Requirements:

```
PHP ^8.3
Composer 2.4.1
Node.js v22.19.0
NPM 10.9.3
Git 2.51.0
Laravel Framework ^12.0
Firebase Realtime Database
Cloudinary (Untuk penyimpanan bukti)
```

## Fitur Utama

### Manajemen Karyawan

-   CRUD data karyawan dengan Firebase
-   Multi-role: Admin, Manager, Employee
-   Akun otomatis dengan Firebase Authentication
-   Reset password oleh admin
-   Live status kehadiran

### Sistem Absensi Real-time

-   Check-in/Check-out dengan timestamp
-   Perhitungan otomatis:
    -   Jam kerja normal (08:00-16:00)
    -   Overtime (setelah 18:00)
    -   Keterlambatan (late minutes)
-   Live dashboard dengan status karyawan
-   Riwayat absensi per bulan

### Manajemen Cuti dengan Bukti Wajib

-   Pengajuan cuti dengan bukti pendukung wajib
-   Format file: JPG, JPEG, PNG (maks. 2MB)
-   Cloudinary integration - penyimpanan cloud aman
-   Preview bukti - modal preview untuk admin
-   Salin link - fitur khusus admin untuk audit
-   Approval workflow multi-level
-   Perhitungan hari kerja otomatis (exclude weekend)

### 📊 Reporting & Analytics

-   Dashboard statistik real-time
-   Laporan absensi dengan filter
-   Export data ke Excel/PDF

## 🚀 Teknologi Stack

### Backend:

-   Laravel ^12.0 - PHP Framework modern
-   Firebase Realtime Database - NoSQL real-time database
-   Firebase Authentication - Secure user management
-   Cloudinary API - Cloud storage untuk bukti cuti
-   Carbon 3.x - Date & time manipulation

### Frontend:

-   Tailwind CSS 4.0 - Utility-first CSS framework
-   Font Awesome 6 - Icon library
-   Vanilla JavaScript ES6 - Client-side operations
-   Sweetalert2 - Beautiful notifications

### Development & Deployment:

-   Composer 2.4.1 - PHP dependency manager
-   NPM 10.9.3 - Node package manager
-   Git 2.51.0 - Version control
-   Vite 7.0.7 - Fast build tool
-   Visual Studio Code - Recommended IDE

## 📁 Struktur Proyek

```
management-karyawan/
├── 📁 app/
│   ├── 📁 Http/
│   │   ├── 📁 Controllers/
│   │   │   ├── AdminController.php           #  Kelola semua fungsi admin
│   │   │   ├── AttendanceController.php      #  Handle absensi, checkin/out
│   │   │   ├── AuthController.php            #  Login/logout, autentikasi
│   │   │   ├── Controller.php                #  Controller base class
│   │   │   ├── DashboardController.php       #  Tampilkan dashboard semua role
│   │   │   ├── EmployeeController.php        #  CRUD data karyawan
│   │   │   ├── LeaveController.php           #  Kelola pengajuan cuti + bukti
│   │   │   ├── ProfileController.php         #  Update profil user
│   │   │   ├── ReportController.php          #  Generate laporan PDF/Excel
│   │   │   └── SettingsController.php        #  Pengaturan sistem
│   │   ├── 📁 Middleware/
│   │   └── 📁 Models/
│   ├── 📁 Providers/
│   └── 📁 Services/
│       └── FirebaseService.php               #  Service untuk Firebase API
├── 📁 bootstrap/                             #  Bootstrap aplikasi
│   └── app.php
├── 📁 config/                                #  Konfigurasi
├── 📁 database/                              #  Database migrations
├── 📁 public/                                #  File publik
├── 📁 resources/
│   ├── 📁 css/
│   ├── 📁 js/
│   └── 📁 views/                             #  VIEW FILES - Template Blade
│       ├── 📁 admin/                         #  VIEWS ADMIN
│       │   ├── admin-barcode-scanner.blade.php     #  Halaman scanner barcode admin
│       │   ├── barcode-verification-history.blade.php #  Riwayat scan barcode
│       │   ├── dashboard.blade.php           #  Dashboard admin (statistik)
│       │   ├── scanner.blade.php             #  Interface scanner sederhana
│       │   └── users.blade.php               #  Daftar semua user (admin view)
│       ├── 📁 attendance/                    #  VIEWS ABSENSI
│       │   ├── dashboard.blade.php           #  Overview absensi (status live)
│       │   └── employee-barcode.blade.php    #  Tampilkan barcode karyawan untuk discan
│       ├── 📁 auth/                          #  VIEWS AUTH
│       │   └── login.blade.php               #  Halaman login
│       ├── 📁 employees/                     #  VIEWS KARYAWAN
│       │   ├── create.blade.php              #  Form tambah karyawan baru
│       │   ├── dashboard.blade.php           #  Dashboard pribadi karyawan
│       │   ├── edit.blade.php                #  Form edit data karyawan
│       │   ├── index.blade.php               #  Daftar semua karyawan
│       │   └── show.blade.php                #  Detail satu karyawan
│       ├── 📁 layouts/                       #  LAYOUT TEMPLATES
│       │   └── app.blade.php                 #  Layout utama (header, sidebar, footer)
│       ├── 📁 leaves/                        #  VIEWS CUTI
│       │   ├── create.blade.php              #  Form pengajuan cuti + upload bukti
│       │   ├── index.blade.php               #  Daftar semua pengajuan cuti (admin)
│       │   ├── my-leaves.blade.php           #  Daftar cuti saya (karyawan)
│       │   └── show.blade.php                #  Detail cuti + preview bukti
│       ├── 📁 manager/                       #  VIEWS MANAGER
│       │   └── dashboard.blade.php           #  Dashboard manager (tim stats)
│       ├── 📁 profile/                       #  VIEWS PROFILE
│       │   └── show.blade.php                #  Halaman profil user
│       ├── 📁 reports/                       #  VIEWS LAPORAN
│       │   ├── attendance-pdf.blade.php      #  Template PDF untuk laporan absensi
│       │   └── attendance.blade.php          #  Filter & hasil laporan absensi
│       ├── 📁 settings/                      #  VIEWS SETTINGS
│       │   └── index.blade.php               #  Halaman pengaturan sistem
│       ├── dashboard.blade.php               #  Dashboard default (redirect based on role)
│       └── welcome.blade.php                 #  Halaman landing/home
├── 📁 routes/                                #  ROUTING
│   ├── api.php                               #  API routes (JSON responses)
│   ├── console.php                           #  Artisan command routes
│   └── web.php                               #  Web routes (GET/POST requests)
├── 📁 storage/                               #  FILE STORAGE
│   ├── 📁 app/
│   │   ├── 📁 firebase/
│   │   │   └── credentials.json             #  Firebase service account key (JSON)
│   │   ├── private/                         #  Private files
│   │   └── public/                          #  Public files (bisa diakses via URL)
│   └── logs/                                #  Log files aplikasi
├── 📁 tests/                                 #  Testing
├── .editorconfig
├── .env.example                              #  Contoh file env
├── .gitattributes
├── .gitignore                               #  File-file yang diignore git
├── artisan                                   #  CLI Laravel
├── composer.json                            #  Konfigurasi dependencies PHP
├── composer.lock                            #  Versi locked dependencies
├── package-lock.json                        #  Versi locked npm
├── package.json                             #  Konfigurasi dependencies JS
├── phpunit.xml                              #  Konfigurasi testing
├── README.md                                #  Dokumentasi proyek
├── vite.config.js                           #  Konfigurasi build Vite
```

## ⚙️ Instalasi & Setup

### 1. Clone & Setup Awal

```bash
# Clone repository
git clone https://github.com/Sigmaku/manajemen-karyawan.git
cd manajemen-karyawan

# Install PHP dependencies
composer install

# Install Node.js dependencies (jika ada)
npm install

# Setup environment
cp .env.example .env
```

### 2. Konfigurasi Firebase

1. Buat project di [Firebase Console](https://console.firebase.google.com)
2. Download service account (JSON) dari Project Settings > Service Accounts
3. Simpan sebagai `storage/app/firebase/credentials.json`
4. Update `.env`:

```env
FIREBASE_CREDENTIALS=storage/app/firebase/credentials.json
FIREBASE_DATABASE_URL=https://[PROJECT-ID].firebasedatabase.app
FIREBASE_COMPANY_ID=[YOUR_COMPANY_ID]
```

### 3. Konfigurasi Cloudinary (Untuk Bukti Cuti)

1. Daftar di [Cloudinary](https://cloudinary.com)
2. Dapatkan credentials dari Dashboard
3. Update `.env`:

```env
# Format 1: Single URL (Recommended)
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME

# Format 2: Separate values
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

### 4. Jalankan Development Server

```bash
# Start local server
php artisan serve

# Atau dengan port custom
php artisan serve --port=8000
```

## 📖 Panduan Penggunaan

## 🔐 Authentication System

Sistem menggunakan Firebase Authentication dengan multi-role:

-   Admin: Full system access
-   Manager: Employee management & leave approval
-   Employee: Personal attendance & leave requests

### Login Flow:

1. User memasukkan email dan password
2. System melakukan authentication via Firebase
3. Session dibuat berdasarkan role user
4. User di-redirect ke dashboard yang sesuai

### Untuk Karyawan:

1. Login dengan akun karyawan
2. Check-in saat datang kerja (setelah 08:00 = terlambat)
3. Check-out saat pulang (otomatis hitung overtime)
4. Ajukan cuti dengan:
    - Pilih jenis cuti
    - Isi tanggal dan alasan
    - Upload bukti wajib (max 2MB)
    - Submit dan tunggu approval
5. Pantau status di "Pengajuan Cuti Saya"

### Untuk Admin/Manager:

1. Kelola karyawan - tambah/edit/hapus
2. Review cuti - dengan preview bukti
3. Approve/Reject - dengan alasan jika reject
4. Salin link bukti - untuk keperluan audit/report
5. Generate laporan - absensi & cuti
6. Live monitoring - status kehadiran real-time

## 🗃️ Struktur Data Firebase

**Catatan Keamanan:** Struktur data Firebase tidak ditampilkan secara detail di sini untuk alasan keamanan. Sistem menggunakan Firebase Realtime Database dengan struktur yang dinamis dan terenkripsi. Jika Anda developer, lihat kode sumber di `app/Services/FirebaseService.php` untuk implementasi internal.

### Contoh Penggunaan (Tanpa Detail Struktur):

-   **LeaveRequests**: Menyimpan data pengajuan cuti dengan bukti, status approval, dan metadata.
-   **Attendance**: Menyimpan data absensi harian dengan perhitungan overtime dan keterlambatan.

## 🐛 Troubleshooting & Debug

### Common Issues:

1. Firebase Connection Failed

```bash
# Cek credentials
ls -la storage/app/firebase/

# Test connection
php artisan tinker
>>> app('App\Services\FirebaseService')->getDatabase()->getReference('test')->set(['status' => 'ok']);
```

2. Cloudinary Upload Error

```bash
# Cek .env variables
echo $CLOUDINARY_URL

# Test upload manual
php -r "
require 'vendor/autoload.php';
\$cloudinary = new \Cloudinary\Cloudinary();
echo '✅ Cloudinary Connected';
"
```

3. File Upload Size Limit

```bash
# Cek PHP upload limits
php -r "echo 'Upload: ' . ini_get('upload_max_filesize') . PHP_EOL;"
php -r "echo 'Post: ' . ini_get('post_max_size') . PHP_EOL;"
```

### Debug Mode:

```env
# .env configuration
APP_DEBUG=true
APP_ENV=local
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

## ❓ FAQ (Pertanyaan Umum)

### Q: Bagaimana cara menjalankan proyek ini di lokal?

A: Ikuti langkah-langkah di bagian "Instalasi & Setup". Pastikan semua dependencies terinstall dan konfigurasi Firebase/Cloudinary sudah benar.

### Q: Mengapa absensi tidak tersimpan?

A: Periksa koneksi Firebase. Jalankan `php artisan tinker` dan test koneksi seperti di bagian Troubleshooting.

### Q: Bukti cuti tidak bisa diupload?

A: Pastikan file JPG/PNG maksimal 2MB. Cek konfigurasi Cloudinary di .env dan koneksi internet.

### Q: Bagaimana reset password karyawan?

A: Admin dapat reset password melalui menu manajemen karyawan.

### Q: Apa perbedaan role Admin, Manager, dan Employee?

A: Admin: Full access. Manager: Kelola karyawan & approve cuti. Employee: Absensi & ajukan cuti pribadi.

### Q: Mengapa overtime tidak terhitung?

A: Overtime dihitung setelah jam 18:00. Pastikan check-out dilakukan setelah waktu tersebut.

### Q: Bagaimana export laporan?

A: Gunakan menu Reports. Pilih filter tanggal dan klik Export ke Excel/PDF.

## 📄 Lisensi & Hak Cipta

Proyek ini dilisensikan di bawah MIT License. Lihat file [LICENSE](LICENSE) untuk detail lengkap.

Hak Cipta © 2026 Tim Pengembangan Sistem Manajemen Karyawan

## 🙏 Credits & Acknowledgments

-   Laravel Community - Amazing PHP framework
-   Firebase Google - Real-time database solution
-   Cloudinary - Cloud media management
-   Tailwind CSS Team - Utility-first CSS framework
-   All Contributors - Terima kasih atas kontribusi

---
