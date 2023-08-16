<?php

namespace Database\Seeders;

use App\Models\MKecamatan;
use App\Models\MKelurahan;
use App\Models\MUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $table = new MUser();
        $table->username = 'admin'; // $table->string('username')
        $table->password = Hash::make('adminsimkesling1'); // $table->string('password')
        $table->nama_user = 'Admin Simkesling'; // $table->string('nama_user')
        $table->level = 1; // $table->integer('level')
        $table->noreg_tempat = ''; // $table->string('noreg_tempat')
        $table->tipe_tempat = ''; // $table->string('tipe_tempat')
        $table->nama_tempat = 'Dinkes Kota Depok'; // $table->string('nama_tempat')
        $table->alamat_tempat = 'Jl Margonda Raya'; // $table->string('alamat_tempat')
        $table->id_kelurahan = 0; // $table->bigInteger('id_kelurahan')
        $table->id_kecamatan = 0; // $table->bigInteger('id_kecamatan')
        $table->kelurahan = ''; // $table->string('kelurahan')
        $table->kecamatan = ''; // $table->string('kecamatan')
        $table->notlp = ''; // $table->string('notlp')
        $table->nohp = ''; // $table->string('nohp')
        $table->email = ''; // $table->string('email')
        $table->izin_ipal = ''; // $table->string('izin_ipal')
        $table->izin_tps = ''; // $table->string('izin_tps')
        $table->status_user = 0; // $table->integer('status_user')
        $table->statusactive_user = 0; // $table->integer('statusactive_user')
        $table->user_created = '-- system --'; // $table->string('user_created')
        $table->user_updated = '-- system --'; // $table->string('user_updated')
        $table->uid = 'zzzz System zzzz'; // $table->uuid('uid')
        $table->save();
    }
}
