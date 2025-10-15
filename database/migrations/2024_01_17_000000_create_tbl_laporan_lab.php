<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_laporan_lab', function (Blueprint $table) {
            // Primary Key
            $table->id('id_laporan_lab');
            
            // Foreign Keys
            $table->bigInteger('id_user')->length(11)->nullable();
            
            // Lab Information
            $table->string('nama_lab', 100)->nullable();
            $table->enum('jenis_pemeriksaan', [
                'kualitas_udara',
                'kualitas_air', 
                'kualitas_makanan',
                'usap_alat_medis_linen',
                'limbah_cair',
                'gabungan'
            ])->nullable();
            $table->integer('total_pemeriksaan')->nullable();
            
            // Parameter Uji - JSON structure for different examination types
            $table->text('parameter_uji')->nullable(); // JSON untuk parameter sesuai jenis pemeriksaan
            $table->text('hasil_uji')->nullable(); // JSON untuk hasil sesuai jenis pemeriksaan
            $table->string('metode_analisis', 100)->nullable();
            
            // Specific columns for each examination type
            $table->text('kualitas_udara')->nullable(); // JSON data untuk pemeriksaan kualitas udara
            $table->text('kualitas_air')->nullable(); // JSON data untuk pemeriksaan kualitas air
            $table->text('kualitas_makanan')->nullable(); // JSON data untuk pemeriksaan kualitas makanan
            $table->text('usap_alat_medis')->nullable(); // JSON data untuk pemeriksaan usap alat medis
            $table->text('limbah_cair')->nullable(); // JSON data untuk pemeriksaan limbah cair
            
            // Additional Info
            $table->text('catatan')->nullable();
            
            // Link Dokumen
            $table->text('link_sertifikat_lab')->nullable();
            $table->text('link_hasil_uji')->nullable();
            $table->text('link_dokumen_pendukung')->nullable();
            
            // Periode Laporan
            $table->integer('periode')->length(2)->nullable();
            $table->string('periode_nama', 15)->nullable();
            $table->integer('tahun')->length(4)->nullable();
            
            // Status Fields
            $table->integer('status_laporan_lab')->length(1)->default(1)->nullable();
            $table->integer('statusactive_laporan_lab')->length(1)->default(1)->nullable();
            
            // Audit Fields
            $table->string('user_created', 100)->nullable();
            $table->string('user_updated', 100)->nullable();
            $table->uuid('uid');
            $table->timestamps();
            
            // Note: Following existing pattern - no explicit foreign key constraints defined
            // The relationship is handled at the application level through Eloquent models
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_laporan_lab');
    }
};