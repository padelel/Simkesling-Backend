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
        Schema::table('tbl_laporan_lab', function (Blueprint $table) {
            $table->dropColumn([
                'nama_lab',
                'jenis_pemeriksaan',
                'total_pemeriksaan',
                'parameter_uji',
                'hasil_uji',
                'metode_analisis',
                'link_sertifikat_lab',
                'link_hasil_uji',
                'link_dokumen_pendukung'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_laporan_lab', function (Blueprint $table) {
            // Re-add the dropped columns in reverse order
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
            $table->text('parameter_uji')->nullable();
            $table->text('hasil_uji')->nullable();
            $table->string('metode_analisis', 100)->nullable();
            $table->text('link_sertifikat_lab')->nullable();
            $table->text('link_hasil_uji')->nullable();
            $table->text('link_dokumen_pendukung')->nullable();
        });
    }
};
