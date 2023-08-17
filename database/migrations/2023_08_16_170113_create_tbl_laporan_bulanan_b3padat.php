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
        Schema::create('tbl_laporan_bulanan_b3padat', function (Blueprint $table) {
            $table->id('id_laporan_bulanan_b3padat');
            $table->bigInteger('id_laporan_bulanan')->length(11)->nullable();
            $table->bigInteger('id_user')->length(11)->nullable();
            $table->integer('norut')->length(3)->nullable();
            $table->string('kategori', 100)->nullable();
            $table->string('catatan', 255)->nullable();
            $table->string('total', 100)->nullable();

            $table->integer('status_laporan_bulanan_b3padat')->length(1)->nullable();
            $table->integer('statusactive_laporan_bulanan_b3padat')->length(1)->nullable();
            $table->string('user_created', 100)->nullable();
            $table->string('user_updated', 100)->nullable();
            $table->uuid('uid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_laporan_bulanan_b3padat');
    }
};
