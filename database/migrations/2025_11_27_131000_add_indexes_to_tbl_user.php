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
        Schema::table('tbl_user', function (Blueprint $table) {
            $table->index(['statusactive_user', 'level'], 'idx_user_status_level');
            $table->index('nama_user', 'idx_user_nama_user'); // Helps with sorting/prefix search
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_user', function (Blueprint $table) {
            $table->dropIndex('idx_user_status_level');
            $table->dropIndex('idx_user_nama_user');
        });
    }
};
