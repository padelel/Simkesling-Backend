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
        Schema::table('tbl_transporter_tmp', function ($table) {
            $table->string('nama_pemusnah', 255)->nullable();
            $table->string('metode_pemusnah', 255)->nullable();
            $table->string('noizin', 255)->nullable();
            $table->string('link_input_mou', 255)->nullable();
            $table->string('link_input_izin', 255)->nullable();
        });
        Schema::table('tbl_transporter_tmp_mou', function ($table) {
            $table->string('link_input', 255)->nullable();
            $table->string('tipe', 10)->nullable(); // IZIN | MOU
        });

        Schema::table('tbl_transporter', function ($table) {
            $table->string('nama_pemusnah', 255)->nullable();
            $table->string('metode_pemusnah', 255)->nullable();
            $table->string('noizin', 255)->nullable();
            $table->string('link_input_mou', 255)->nullable();
            $table->string('link_input_izin', 255)->nullable();
        });
        Schema::table('tbl_transporter_mou', function ($table) {
            $table->string('link_input', 255)->nullable();
            $table->string('tipe', 10)->nullable(); // IZIN | MOU
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('tbl_transporter_tmp', function ($table) {
            $table->dropColumn('nama_pemusnah');
            $table->dropColumn('metode_pemusnah');
            $table->dropColumn('link_input_mou');
            $table->dropColumn('link_input_izin');
        });
        Schema::table('tbl_transporter_tmp_mou', function ($table) {
            $table->dropColumn('link_input');
            $table->dropColumn('tipe'); // IZIN | MOU
        });

        Schema::table('tbl_transporter', function ($table) {
            $table->dropColumn('nama_pemusnah');
            $table->dropColumn('metode_pemusnah');
            $table->dropColumn('link_input_mou');
            $table->dropColumn('link_input_izin');
        });
        Schema::table('tbl_transporter_mou', function ($table) {
            $table->dropColumn('link_input');
            $table->dropColumn('tipe'); // IZIN | MOU
        });
    }
};
