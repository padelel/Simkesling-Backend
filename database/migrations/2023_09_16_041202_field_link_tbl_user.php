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
        Schema::table('tbl_user', function ($table) {
            $table->string('link_manifest', 255)->nullable();
            $table->string('link_logbook', 255)->nullable();
            $table->string('link_lab_ipal', 255)->nullable();
            $table->string('link_lab_lain', 255)->nullable();
            $table->string('link_dokumen_lingkungan_rs', 255)->nullable();
            $table->string('link_izin_transporter', 255)->nullable();
            $table->string('link_mou_transporter', 255)->nullable();
            $table->string('link_swa_pantau', 255)->nullable();
            $table->string('link_lab_limbah_cair', 255)->nullable();
            $table->string('link_izin_ipal', 255)->nullable();
            $table->string('link_izin_tps', 255)->nullable();
            $table->string('link_ukl', 255)->nullable();
            $table->string('link_upl', 255)->nullable();
            $table->string('link1', 255)->nullable();
            $table->string('link2', 255)->nullable();
            $table->string('link3', 255)->nullable();
            $table->string('kapasitas_ipal', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('tbl_user', function ($table) {
            $table->dropColumn('link_manifest');
            $table->dropColumn('link_logbook');
            $table->dropColumn('link_lab_ipal');
            $table->dropColumn('link_lab_lain');
            $table->dropColumn('link_dokumen_lingkungan_rs');
            $table->dropColumn('link_izin_transporter');
            $table->dropColumn('link_mou_transporter');
            $table->dropColumn('link_swa_pantau');
            $table->dropColumn('link_lab_limbah_cair');
            $table->dropColumn('link_izin_ipal');
            $table->dropColumn('link_izin_tps');
            $table->dropColumn('link_ukl');
            $table->dropColumn('link_upl');
            $table->dropColumn('link1');
            $table->dropColumn('link2');
            $table->dropColumn('link3');
            $table->dropColumn('kapasitas_ipal');
        });
    }
};
