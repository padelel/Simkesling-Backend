<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tbl_laporan_bulanan', function (Blueprint $table) {
            $table->string('limbah_cair_b3')->nullable()->after('limbah_padat_infeksius');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tbl_laporan_bulanan', function ($table) {
            $table->dropColumn('limbah_cair_b3');
        });
    }
};