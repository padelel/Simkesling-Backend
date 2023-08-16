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
        Schema::create('tbl_kecamatan', function (Blueprint $table) {
            $table->id('id_kecamatan');
            $table->string('nama_kecamatan', 100)->nullable();
            $table->string('kodepos_kecamatan', 10)->nullable();
            $table->string('kodewil_kecamatan', 20)->nullable();
            $table->string('status_kecamatan', 1)->default('0');
            $table->string('statusactive_kecamatan', 1)->default('1'); // (untuk data aktif(1)/delete(0))
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
        Schema::dropIfExists('tbl_kecamatan');
    }
};
