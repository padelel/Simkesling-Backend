<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add performance indexes to improve query speed for dashboard and reporting pages.
     * These indexes optimize queries that filter by tahun, periode, and user_id.
     */
    public function up(): void
    {
        // Indexes for tbl_laporan_bulanan (Limbah Padat)
        Schema::table('tbl_laporan_bulanan', function (Blueprint $table) {
            // Composite index for year + period queries
            $table->index(['tahun', 'periode', 'statusactive_laporan_bulanan'], 'idx_laporan_bulanan_tahun_periode');
            
            // Index for user lookups
            $table->index(['id_user', 'tahun', 'statusactive_laporan_bulanan'], 'idx_laporan_bulanan_user');
        });

        // Indexes for tbl_limbah_cair
        Schema::table('tbl_limbah_cair', function (Blueprint $table) {
            // Composite index for year + period queries
            $table->index(['tahun', 'periode', 'statusactive_limbah_cair'], 'idx_limbah_cair_tahun_periode');
            
            // Index for user lookups
            $table->index(['id_user', 'tahun', 'statusactive_limbah_cair'], 'idx_limbah_cair_user');
        });

        // Indexes for tbl_laporan_lab
        Schema::table('tbl_laporan_lab', function (Blueprint $table) {
            // Composite index for year + period queries
            $table->index(['tahun', 'periode', 'statusactive_laporan_lab'], 'idx_laporan_lab_tahun_periode');
            
            // Index for user lookups
            $table->index(['id_user', 'tahun', 'statusactive_laporan_lab'], 'idx_laporan_lab_user');
        });

        // Indexes for tbl_user (if not exists)
        Schema::table('tbl_user', function (Blueprint $table) {
            // Index for level-based queries
            $table->index(['level', 'statusactive_user'], 'idx_user_level_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from tbl_laporan_bulanan
        Schema::table('tbl_laporan_bulanan', function (Blueprint $table) {
            $table->dropIndex('idx_laporan_bulanan_tahun_periode');
            $table->dropIndex('idx_laporan_bulanan_user');
        });

        // Drop indexes from tbl_limbah_cair
        Schema::table('tbl_limbah_cair', function (Blueprint $table) {
            $table->dropIndex('idx_limbah_cair_tahun_periode');
            $table->dropIndex('idx_limbah_cair_user');
        });

        // Drop indexes from tbl_laporan_lab
        Schema::table('tbl_laporan_lab', function (Blueprint $table) {
            $table->dropIndex('idx_laporan_lab_tahun_periode');
            $table->dropIndex('idx_laporan_lab_user');
        });

        // Drop indexes from tbl_user
        Schema::table('tbl_user', function (Blueprint $table) {
            $table->dropIndex('idx_user_level_status');
        });
    }
};
