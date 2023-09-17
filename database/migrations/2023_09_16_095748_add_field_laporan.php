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
        //
        Schema::table('tbl_laporan_bulanan', function (Blueprint $table) {
            $table->string('link_input_manifest', 255)->nullable();
            $table->string('link_input_logbook', 255)->nullable();
            $table->string('link_input_lab_ipal', 255)->nullable();
            $table->string('link_input_lab_lain', 255)->nullable();
            $table->string('link_input_dokumen_lingkungan_rs', 255)->nullable();
            $table->string('link_input_swa_pantau', 255)->nullable();
            $table->string('link_input_ujilab_cair', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('tbl_laporan_bulanan', function ($table) {
            $table->dropColumn('link_input_manifest');
            $table->dropColumn('link_input_logbook');
            $table->dropColumn('link_input_lab_ipal');
            $table->dropColumn('link_input_lab_lain');
            $table->dropColumn('link_input_dokumen_lingkungan_rs');
            $table->dropColumn('link_input_swa_pantau');
            $table->dropColumn('link_input_ujilab_cair');
        });
    }
};
