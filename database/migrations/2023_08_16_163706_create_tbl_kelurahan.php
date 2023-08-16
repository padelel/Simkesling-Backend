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
        Schema::create('tbl_kelurahan', function (Blueprint $table) {
            $table->id('id_kelurahan');
            $table->bigInteger('id_kecamatan')->nullable();
            $table->string('nama_kelurahan', 100)->nullable();
            $table->string('kodepos_kelurahan', 10)->nullable();
            $table->string('kodewil_kelurahan', 20)->nullable();
            $table->string('status_kelurahan', 1)->default('0');
            $table->string('statusactive_kelurahan', 1)->default('1'); // (untuk data aktif(1)/delete(0))
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();
            $table->uuid('uid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_kelurahan');
    }
};
