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
        Schema::table('tbl_limbah_cair', function (Blueprint $table) {
            $table->string('nama_transporter', 255)->nullable()->after('id_transporter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_limbah_cair', function (Blueprint $table) {
            $table->dropColumn('nama_transporter');
        });
    }
};
