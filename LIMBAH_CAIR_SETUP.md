# Setup Database dan Controller Limbah Cair

Dokumentasi ini menjelaskan cara setup database dan controller untuk fitur limbah cair yang baru dibuat.

## File yang Dibuat

### 1. Migration
- **File**: `database/migrations/2024_01_16_000000_create_tbl_limbah_cair.php`
- **Deskripsi**: Migration untuk membuat tabel `tbl_limbah_cair` dengan struktur yang mirip dengan tabel laporan bulanan namun khusus untuk data limbah cair.

### 2. Model
- **File**: `app/Models/MLimbahCair.php`
- **Deskripsi**: Model Eloquent untuk tabel limbah cair dengan relationships ke User dan Transporter.

### 3. Controller
- **File**: `app/Http/Controllers/LimbahCairController.php`
- **Deskripsi**: Controller dengan method CRUD lengkap untuk mengelola data limbah cair.

### 4. Routes
- **File**: `routes/api.php` (diupdate)
- **Deskripsi**: Menambahkan routes API untuk limbah cair.

### 5. Seeder
- **File**: `database/seeders/LimbahCairSeeder.php`
- **Deskripsi**: Seeder untuk data contoh limbah cair.

## Struktur Tabel Limbah Cair

Tabel `tbl_limbah_cair` memiliki field-field berikut:

### Primary Key
- `id_limbah_cair` (Primary Key)

### Foreign Keys
- `id_transporter` (BigInteger, nullable)
- `id_user` (BigInteger, nullable)

### Parameter Limbah Cair
- `ph` (Decimal 4,2, nullable) - pH 0-14
- `bod` (Decimal 8,2, nullable) - BOD dalam mg/l
- `cod` (Decimal 8,2, nullable) - COD dalam mg/l
- `tss` (Decimal 8,2, nullable) - TSS dalam mg/l
- `minyak_lemak` (Decimal 8,2, nullable) - Minyak & Lemak dalam mg/l
- `amoniak` (Decimal 8,2, nullable) - Amoniak dalam mg/l
- `total_coliform` (Integer, nullable) - Total Coliform dalam MPN/100ml
- `debit_air_limbah` (Decimal 10,2, nullable) - Debit dalam M³/hari

### Kapasitas IPAL
- `kapasitas_ipal` (String 50, nullable)

### Link Dokumen (Text fields)
- `link_manifest`
- `link_logbook`
- `link_lab_ipal`
- `link_lab_lain`
- `link_dokumen_lingkungan_rs`
- `link_swa_pantau`
- `link_ujilab_cair`
- `link_izin_transporter`
- `link_mou_transporter`
- `link_lab_limbah_cair`
- `link_izin_ipal`
- `link_izin_tps`
- `link_ukl`
- `link_upl`

### Periode Laporan
- `periode` (Integer 2, nullable)
- `periode_nama` (String 15, nullable)
- `tahun` (Integer 4, nullable)

### Status Fields
- `status_limbah_cair` (Integer 1, default 1)
- `statusactive_limbah_cair` (Integer 1, default 1)

### Audit Fields
- `user_created` (String 100, nullable)
- `user_updated` (String 100, nullable)
- `uid` (UUID)
- `created_at`, `updated_at` (Timestamps)

## API Endpoints

Semua endpoints berada di bawah prefix `/api/v1/user/limbah-cair/` dan memerlukan middleware `ceklogin.webnext`:

### 1. Get Data Limbah Cair
- **Method**: POST
- **URL**: `/api/v1/user/limbah-cair/data`
- **Parameters**: 
  - `tahun` (optional): Filter berdasarkan tahun
  - `periode` (optional): Filter berdasarkan periode (1-12)

### 2. Create Limbah Cair
- **Method**: POST
- **URL**: `/api/v1/user/limbah-cair/create`
- **Required Parameters**:
  - `id_transporter` (integer, min:1)
  - `ph` (numeric, 0-14)
  - `bod` (numeric, min:0)
  - `cod` (numeric, min:0)
  - `tss` (numeric, min:0)
  - `minyak_lemak` (numeric, min:0)
  - `amoniak` (numeric, min:0)
  - `total_coliform` (integer, min:0)
  - `debit_air_limbah` (numeric, min:0)
  - `kapasitas_ipal` (string, max:50)
  - `link_manifest` (string)
  - `link_logbook` (string)
  - `link_lab_ipal` (string)
  - `link_ujilab_cair` (string)
  - `periode` (integer, 1-12)
  - `tahun` (integer, 2020-2050)

### 3. Update Limbah Cair
- **Method**: POST
- **URL**: `/api/v1/user/limbah-cair/update`
- **Parameters**: Same as create + `oldid` (required)
- **Required Parameters**: Same as create plus:
  - `oldid` (integer, required)

**Note**: Link fields tidak memerlukan format URL yang valid, dapat berupa string apapun.

### 4. Delete Limbah Cair
- **Method**: POST
- **URL**: `/api/v1/user/limbah-cair/delete`
- **Parameters**: `id` (required)

### 5. Get Detail Limbah Cair
- **Method**: POST
- **URL**: `/api/v1/user/limbah-cair/detail`
- **Parameters**: `id` (required)

## Cara Menjalankan Setup

### 1. Jalankan Migration
```bash
php artisan migrate
```

### 2. Jalankan Seeder (Optional)
```bash
php artisan db:seed --class=LimbahCairSeeder
```

Atau jalankan semua seeder:
```bash
php artisan db:seed
```

### 3. Clear Cache (Optional)
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Validasi Parameter Limbah Cair

Berdasarkan standar baku mutu limbah cair rumah sakit:

- **pH**: 6-9
- **BOD**: Maksimum 30 mg/l
- **COD**: Maksimum 100 mg/l
- **TSS**: Maksimum 30 mg/l
- **Minyak & Lemak**: Maksimum 5 mg/l
- **Amoniak**: Maksimum 10 mg/l
- **Total Coliform**: Maksimum 3000 MPN/100ml

## Fitur Keamanan

1. **Authorization**: User hanya bisa melihat/edit data miliknya sendiri (kecuali admin)
2. **Validation**: Semua input divalidasi sesuai dengan aturan yang ditetapkan
3. **Soft Delete**: Data tidak dihapus permanen, hanya diubah status `statusactive_limbah_cair` menjadi 0
4. **Audit Trail**: Setiap perubahan dicatat dengan `user_created` dan `user_updated`

## Integrasi dengan Frontend

Frontend sudah memiliki form `FormPengajuanLimbahCair.tsx` yang siap terintegrasi dengan API endpoints ini. Form tersebut sudah menggunakan endpoint yang benar:

- Create: `/user/limbah-cair/create`
- Update: `/user/limbah-cair/update`

### Fitur Dropdown Transporter

Form limbah cair sudah dilengkapi dengan:

1. **Dropdown Transporter**: User dapat memilih transporter dari daftar yang tersedia
2. **Validasi**: User harus memilih transporter sebelum dapat menyimpan laporan
3. **Data Dinamis**: Daftar transporter diambil dari database secara real-time

### API Endpoints untuk Dropdown

- **Transporter**: `POST /api/v1/user/transporter/data`

Pastikan frontend menggunakan parameter yang sesuai dengan validasi di controller.