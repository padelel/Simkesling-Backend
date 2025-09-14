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
        Schema::create('tbl_laporan_bulanan', function (Blueprint $table) {
            // Primary Key
            $table->id('id_laporan_bulanan');
            
            // Foreign Keys
            $table->bigInteger('id_transporter')->length(11)->nullable();
            $table->bigInteger('id_user')->length(11)->nullable();
            
            // Transporter & Pemusnah Info
            $table->string('nama_transporter', 100)->nullable();
            $table->string('nama_pemusnah', 100)->nullable();
            $table->string('metode_pemusnah', 100)->nullable();
            
            // Data Limbah Padat
            $table->string('berat_limbah_total', 20)->nullable();
            $table->string('limbah_b3_covid', 20)->nullable();
            $table->string('limbah_b3_noncovid', 20)->nullable();
            
            // Data Limbah Cair
            $table->string('debit_limbah_cair', 20)->nullable();
            $table->string('kapasitas_ipal', 20)->nullable();
            
            // Fasilitas TPS
            $table->integer('punya_penyimpanan_tps')->length(1)->default(0)->nullable(); // yes no
            $table->string('ukuran_penyimpanan_tps', 20)->nullable();
            
            // Fasilitas Pemusnahan
            $table->integer('punya_pemusnahan_sendiri')->length(1)->default(0)->nullable();
            $table->string('ukuran_pemusnahan_sendiri', 20)->nullable();
            
            // Compliance & Notes
            $table->integer('memenuhi_syarat')->length(1)->default(0)->nullable();
            $table->string('catatan', 255)->nullable();
            
            // Periode Laporan
            $table->integer('periode')->length(2)->nullable();
            $table->string('periode_nama', 15)->nullable();
            $table->integer('tahun')->length(4)->nullable();
            
            // Status Fields
            $table->integer('status_laporan_bulanan')->length(1)->nullable();
            $table->integer('statusactive_laporan_bulanan')->length(1)->nullable();
            
            // Audit Fields
            $table->string('user_created', 100)->nullable();
            $table->string('user_updated', 100)->nullable();
            $table->uuid('uid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_laporan_bulanan');
    }
};
