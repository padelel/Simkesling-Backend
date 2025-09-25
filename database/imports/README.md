# Import Data dari Hosting

## Langkah-langkah untuk memindahkan data dari Simkesling yang sudah di hosting:

### 1. Export Data dari Hosting

#### Menggunakan phpMyAdmin:
1. Login ke phpMyAdmin di hosting
2. Pilih database Simkesling
3. Klik tab "Export"
4. Pilih format "SQL"
5. Pilih opsi "Custom" untuk pengaturan lanjutan
6. Pastikan centang:
   - Structure and data
   - Add DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER statement
   - Add CREATE DATABASE / USE statement (opsional)
7. Download file SQL

#### Menggunakan mysqldump (via SSH/Terminal):
```bash
mysqldump -u username -p database_name > hosted_data.sql
```

### 2. Persiapan File Import

1. Rename file yang didownload menjadi `hosted_data.sql`
2. Letakkan file tersebut di folder `database/imports/hosted_data.sql`
3. Pastikan file dapat dibaca dan tidak corrupt

### 3. Import Data ke Database Lokal

#### Metode 1: Menggunakan Seeder (Direkomendasikan)
```bash
php artisan db:seed --class=HostedDataSeeder
```

#### Metode 2: Import Manual via MySQL
```bash
mysql -u root -p lalapand_simkesling < database/imports/hosted_data.sql
```

#### Metode 3: Menggunakan Laravel Artisan Command
```bash
php artisan migrate:fresh
php artisan db:seed --class=HostedDataSeeder
```

### 4. Verifikasi Data

Setelah import, verifikasi data dengan:
```bash
php artisan tinker
```

Kemudian jalankan:
```php
// Cek jumlah user
App\Models\User::count();

// Cek data kecamatan
App\Models\MKecamatan::count();

// Cek data kelurahan  
App\Models\MKelurahan::count();

// Cek laporan bulanan
App\Models\LaporanBulanan::count();
```

### 5. Troubleshooting

#### Jika ada error foreign key:
- Pastikan urutan import sesuai dengan dependency
- Disable foreign key checks sementara

#### Jika ada error duplicate entry:
- Truncate tabel yang bermasalah terlebih dahulu
- Atau gunakan `INSERT IGNORE` dalam SQL

#### Jika ada error encoding:
- Pastikan file SQL menggunakan UTF-8 encoding
- Atau ubah charset di MySQL: `SET NAMES utf8mb4;`

### 6. Backup Data Lokal

Sebelum import, backup data lokal yang ada:
```bash
php artisan migrate:fresh --seed
```

Atau backup manual:
```bash
mysqldump -u root -p lalapand_simkesling > backup_local_$(date +%Y%m%d).sql
```