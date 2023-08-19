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
        Schema::create('tbl_transporter', function (Blueprint $table) {
            $table->id('id_transporter');
            $table->bigInteger('id_transporter_tmp')->length(11)->nullable();
            $table->bigInteger('id_user')->length(11)->nullable();
            $table->string('npwp_transporter', 50)->nullable();
            $table->string('nama_transporter', 100)->nullable();
            $table->string('alamat_transporter', 100)->nullable();
            $table->bigInteger('id_kelurahan')->length(11)->nullable();
            $table->bigInteger('id_kecamatan')->length(11)->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('notlp', 15)->nullable();
            $table->string('nohp', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('catatan', 255)->nullable();

            $table->integer('status_transporter')->length(1)->nullable();
            $table->integer('statusactive_transporter')->length(1)->nullable();
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
        Schema::dropIfExists('tbl_transporter');
    }
};
