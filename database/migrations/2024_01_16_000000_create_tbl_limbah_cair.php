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
        Schema::create('tbl_limbah_cair', function (Blueprint $table) {
            // Primary Key
            $table->id('id_limbah_cair');
            
            // Foreign Keys
            $table->bigInteger('id_transporter')->length(11)->nullable();
            $table->bigInteger('id_user')->length(11)->nullable();
            
            // Parameter Limbah Cair
            $table->decimal('ph', 4, 2)->nullable(); // pH 0-14 dengan 2 desimal
            $table->decimal('bod', 8, 2)->nullable(); // BOD dalam mg/l
            $table->decimal('cod', 8, 2)->nullable(); // COD dalam mg/l
            $table->decimal('tss', 8, 2)->nullable(); // TSS dalam mg/l
            $table->decimal('minyak_lemak', 8, 2)->nullable(); // Minyak & Lemak dalam mg/l
            $table->decimal('amoniak', 8, 2)->nullable(); // Amoniak dalam mg/l
            $table->integer('total_coliform')->nullable(); // Total Coliform dalam MPN/100ml
            $table->decimal('debit_air_limbah', 10, 2)->nullable(); // Debit dalam M³/hari
            
            // Kapasitas IPAL
            $table->string('kapasitas_ipal', 50)->nullable();
            
            // Link Dokumen - hanya yang diperlukan
            $table->text('link_persetujuan_teknis')->nullable(); // Link Persetujuan Teknis
            $table->text('link_ujilab_cair')->nullable(); // Link Uji Lab Cair
            
            // Kolom legacy untuk backward compatibility (optional)
            $table->text('link_lab_ipal')->nullable(); // Mapping ke link_persetujuan_teknis
            $table->text('link_manifest')->nullable(); // Legacy field
            $table->text('link_logbook')->nullable(); // Legacy field
            
            // Periode Laporan
            $table->integer('periode')->length(2)->nullable();
            $table->string('periode_nama', 15)->nullable();
            $table->integer('tahun')->length(4)->nullable();
            
            // Status Fields
            $table->integer('status_limbah_cair')->length(1)->default(1)->nullable();
            $table->integer('statusactive_limbah_cair')->length(1)->default(1)->nullable();
            
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
        Schema::dropIfExists('tbl_limbah_cair');
    }
};