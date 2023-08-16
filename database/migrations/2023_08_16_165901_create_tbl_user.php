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
        Schema::create('tbl_user', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('username', 100)->nullable();
            $table->string('password')->nullable();
            $table->string('nama_user', 100)->nullable();
            $table->integer('level')->length(1)->default(4); # (1admin, 2rs, 3puskesmas, 4zonk)
            $table->string('noreg_tempat', 100)->nullable();
            $table->string('tipe_tempat', 15)->nullable(); # Rumah Sakit|Puskesmas
            $table->string('nama_tempat', 100)->nullable();
            $table->string('alamat_tempat', 100)->nullable();

            $table->bigInteger('id_kelurahan')->length(11)->nullable();
            $table->bigInteger('id_kecamatan')->length(11)->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('notlp', 15)->nullable();
            $table->string('nohp', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('izin_ipal', 255)->nullable();
            $table->string('izin_tps', 255)->nullable();

            $table->integer('status_user')->length(1)->nullable();
            $table->integer('statusactive_user')->length(1)->nullable();
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
        Schema::dropIfExists('tbl_login');
    }
};
