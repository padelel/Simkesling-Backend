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
        Schema::create('tbl_transporter_tmp_mou', function (Blueprint $table) {
            $table->id('id_transporter_tmp_mou');
            $table->bigInteger('id_transporter_tmp')->length(11)->nullable();
            $table->bigInteger('id_user')->length(11)->nullable();
            $table->integer('norut')->length(3)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->string('file1', 255)->nullable();
            $table->string('file2', 255)->nullable();
            $table->string('file3', 255)->nullable();
            $table->dateTime('tgl_mulai')->nullable();
            $table->dateTime('tgl_akhir')->nullable();

            $table->integer('status_transporter_tmp_mou')->length(1)->nullable();
            $table->integer('statusactive_transporter_tmp_mou')->length(1)->nullable();
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
        Schema::dropIfExists('tbl_transporter_tmp_mou');
    }
};
