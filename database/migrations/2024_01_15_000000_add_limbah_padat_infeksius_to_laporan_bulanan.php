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
        Schema::table('tbl_laporan_bulanan', function (Blueprint $table) {
            $table->string('limbah_padat_infeksius', 20)->nullable()->after('limbah_sludge_ipal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_laporan_bulanan', function ($table) {
            $table->dropColumn('limbah_padat_infeksius');
        });
    }
};