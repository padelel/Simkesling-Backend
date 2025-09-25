<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class HostedDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Path ke file SQL yang diekspor dari hosting
        $sqlFile = database_path('imports/hosted_data.sql');
        
        if (File::exists($sqlFile)) {
            // Baca file SQL
            $sql = File::get($sqlFile);
            
            // Pisahkan query berdasarkan semicolon
            $queries = array_filter(
                array_map('trim', explode(';', $sql)),
                function($query) {
                    return !empty($query) && !str_starts_with($query, '--');
                }
            );
            
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Jalankan setiap query
            foreach ($queries as $query) {
                if (!empty($query)) {
                    try {
                        DB::statement($query);
                        $this->command->info("Executed: " . substr($query, 0, 50) . "...");
                    } catch (\Exception $e) {
                        $this->command->error("Error executing query: " . $e->getMessage());
                        $this->command->error("Query: " . substr($query, 0, 100) . "...");
                    }
                }
            }
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->command->info('Hosted data imported successfully!');
        } else {
            $this->command->error("SQL file not found at: {$sqlFile}");
            $this->command->info("Please place your exported SQL file at: database/imports/hosted_data.sql");
        }
    }
}