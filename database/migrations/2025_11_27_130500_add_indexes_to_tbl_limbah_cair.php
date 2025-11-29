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
            // Adding indexes for performance optimization
            $table->index(['statusactive_limbah_cair', 'tahun', 'periode'], 'idx_limbah_cair_status_tahun_periode');
            $table->index('id_user', 'idx_limbah_cair_id_user');
            $table->index('id_transporter', 'idx_limbah_cair_id_transporter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_limbah_cair', function (Blueprint $table) {
            $table->dropIndex('idx_limbah_cair_status_tahun_periode');
            $table->dropIndex('idx_limbah_cair_id_user');
            $table->dropIndex('idx_limbah_cair_id_transporter');
        });
    }
};
