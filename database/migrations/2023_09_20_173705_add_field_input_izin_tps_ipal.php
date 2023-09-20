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
        Schema::table('tbl_user', function (Blueprint $table) {
            $table->string('link_input_izin_ipal', 255)->nullable();
            $table->string('link_input_izin_tps', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('tbl_user', function ($table) {
            $table->dropColumn('link_input_izin_ipal');
            $table->dropColumn('link_input_izin_tps');
        });
    }
};
