<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportHostedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:hosted-data {file?} {--fresh} {--backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from hosted Simkesling database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file') ?? database_path('imports/hosted_data.sql');
        
        if (!File::exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            $this->info("Letakkan file SQL di: database/imports/hosted_data.sql");
            return 1;
        }

        // Backup data lokal jika diminta
        if ($this->option('backup')) {
            $this->info('Membuat backup data lokal...');
            $backupFile = database_path('backups/local_backup_' . date('Y-m-d_H-i-s') . '.sql');
            
            if (!File::exists(dirname($backupFile))) {
                File::makeDirectory(dirname($backupFile), 0755, true);
            }
            
            $dbName = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            
            $command = "mysqldump -u {$username}";
            if ($password) {
                $command .= " -p{$password}";
            }
            $command .= " {$dbName} > {$backupFile}";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->info("Backup berhasil: {$backupFile}");
            } else {
                $this->warn("Backup gagal, melanjutkan import...");
            }
        }

        // Fresh migrate jika diminta
        if ($this->option('fresh')) {
            $this->info('Menjalankan fresh migration...');
            $this->call('migrate:fresh');
        }

        $this->info("Mengimpor data dari: {$file}");
        
        // Baca file SQL
        $sql = File::get($file);
        
        // Pisahkan query berdasarkan semicolon
        $queries = array_filter(
            array_map('trim', explode(';', $sql)),
            function($query) {
                return !empty($query) && 
                       !str_starts_with($query, '--') && 
                       !str_starts_with($query, '/*');
            }
        );

        $this->info("Ditemukan " . count($queries) . " query untuk dieksekusi");
        
        // Progress bar
        $bar = $this->output->createProgressBar(count($queries));
        $bar->start();

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('SET sql_mode = "";');
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Jalankan setiap query
        foreach ($queries as $index => $query) {
            if (!empty($query)) {
                try {
                    DB::statement($query);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'query_index' => $index + 1,
                        'error' => $e->getMessage(),
                        'query_preview' => substr($query, 0, 100) . '...'
                    ];
                }
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Tampilkan hasil
        $this->info("Import selesai!");
        $this->info("Berhasil: {$successCount} query");
        
        if ($errorCount > 0) {
            $this->warn("Error: {$errorCount} query");
            
            if ($this->confirm('Tampilkan detail error?')) {
                foreach ($errors as $error) {
                    $this->error("Query #{$error['query_index']}: {$error['error']}");
                    $this->line("Preview: {$error['query_preview']}");
                    $this->newLine();
                }
            }
        }

        // Verifikasi data
        $this->info('Verifikasi data:');
        
        try {
            $userCount = DB::table('tbl_user')->count();
            $this->info("- Users: {$userCount}");
        } catch (\Exception $e) {
            $this->warn("- Users: Error - " . $e->getMessage());
        }

        try {
            $kecCount = DB::table('m_kecamatan')->count();
            $this->info("- Kecamatan: {$kecCount}");
        } catch (\Exception $e) {
            $this->warn("- Kecamatan: Error - " . $e->getMessage());
        }

        try {
            $kelCount = DB::table('m_kelurahan')->count();
            $this->info("- Kelurahan: {$kelCount}");
        } catch (\Exception $e) {
            $this->warn("- Kelurahan: Error - " . $e->getMessage());
        }

        return 0;
    }
}