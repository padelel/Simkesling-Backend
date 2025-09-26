-- Script untuk membersihkan data transporter yang tidak valid
-- Jalankan script ini dengan hati-hati dan pastikan backup database terlebih dahulu

-- 1. Cek data transporter yang memiliki id_user NULL atau tidak valid
SELECT 
    t.id_transporter,
    t.nama_transporter,
    t.id_user,
    u.nama_user,
    u.tipe_tempat,
    u.nama_tempat
FROM tbl_transporter t
LEFT JOIN tbl_user u ON t.id_user = u.id_user
WHERE t.statusactive_transporter <> 0
AND (t.id_user IS NULL OR u.id_user IS NULL);

-- 2. Cek data transporter_tmp yang memiliki id_user NULL atau tidak valid
SELECT 
    tt.id_transporter_tmp,
    tt.nama_transporter,
    tt.id_user,
    u.nama_user,
    u.tipe_tempat,
    u.nama_tempat
FROM tbl_transporter_tmp tt
LEFT JOIN tbl_user u ON tt.id_user = u.id_user
WHERE tt.statusactive_transporter_tmp <> 0
AND (tt.id_user IS NULL OR u.id_user IS NULL);

-- 3. Update status transporter yang tidak memiliki user valid menjadi tidak aktif
-- UNCOMMENT BARIS BERIKUT SETELAH MEMVERIFIKASI DATA DI ATAS
/*
UPDATE tbl_transporter 
SET statusactive_transporter = 0, 
    user_updated = 'system_cleanup',
    updated_at = NOW()
WHERE id_user IS NULL 
   OR id_user NOT IN (SELECT id_user FROM tbl_user WHERE statusactive_user <> 0);
*/

-- 4. Update status transporter_tmp yang tidak memiliki user valid menjadi tidak aktif
-- UNCOMMENT BARIS BERIKUT SETELAH MEMVERIFIKASI DATA DI ATAS
/*
UPDATE tbl_transporter_tmp 
SET statusactive_transporter_tmp = 0, 
    user_updated = 'system_cleanup',
    updated_at = NOW()
WHERE id_user IS NULL 
   OR id_user NOT IN (SELECT id_user FROM tbl_user WHERE statusactive_user <> 0);
*/

-- 5. Cek hasil setelah cleanup
/*
SELECT 
    'Active Transporter' as table_name,
    COUNT(*) as total_records
FROM tbl_transporter 
WHERE statusactive_transporter <> 0

UNION ALL

SELECT 
    'Active Transporter Tmp' as table_name,
    COUNT(*) as total_records
FROM tbl_transporter_tmp 
WHERE statusactive_transporter_tmp <> 0;
*/