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
            $table->string('limbah_b3_nonmedis', 20)->nullable();
            $table->string('limbah_b3_medis', 20)->nullable();
            $table->string('limbah_jarum', 20)->nullable();
            $table->string('limbah_sludge_ipal', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('tbl_laporan_bulanan', function ($table) {
            $table->dropColumn('limbah_b3_nonmedis');
            $table->dropColumn('limbah_b3_medis');
            $table->dropColumn('limbah_jarum');
            $table->dropColumn('limbah_sludge_ipal');
        });
    }
};
