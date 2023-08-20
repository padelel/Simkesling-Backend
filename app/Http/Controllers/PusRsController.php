<?php

namespace App\Http\Controllers;

use App\Models\MKecamatan;
use App\Models\MKelurahan;
use App\Models\MLaporanBulanan;
use App\Models\MLaporanBulananB3Padat;
use App\Models\MLaporanBulananFile;
use App\Models\MTransporter;
use Illuminate\Http\Request;

use App\Models\MTransporterMOU;
use App\Models\MTransporterTmp;
use App\Models\MTransporterTmpMOU;
use App\Models\MUser;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder as RB;
use App\MyResponseBuilder as MyRB;
use App\MyUtils as MyUtils;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class PusRsController extends Controller
{
    //
    public function pusRsProsesData(Request $request)
    {
        $user = MUser::where(['statusactive_user' => 1])->whereIn('level', [2, 3])->get();
        $dataUser = $user->values()->toArray();
        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData($dataUser)
            ->build();
    }
    public function pusRsProsesCreate(Request $request)
    {
        // $table->string('username'
        // $table->string('password'
        // $table->string('nama_user'
        // $table->integer('level'
        // $table->string('noreg_tempat'
        // $table->string('tipe_tempat'
        // $table->string('nama_tempat'
        // $table->string('alamat_tempat'
        // $table->bigInteger('id_kelurahan'
        // $table->bigInteger('id_kecamatan'
        // $table->string('kelurahan'
        // $table->string('kecamatan'
        // $table->string('notlp'
        // $table->string('nohp'
        // $table->string('email'
        // $table->string('izin_ipal'
        // $table->string('izin_tps'
        // $table->integer('status_user'
        // $table->integer('statusactive_user'
        // $table->string('user_created'
        // $table->string('user_updated'

        $cek_dulu = [
            'username' => 'required',
            'password' => 'required',
            'nama_user' => 'required',
            'level' => 'required|in:2,3',
        ];

        if ($request->file_izin_ipal != null) {
            $cek_dulu['file_izin_ipal'] = 'required|max:10120';
        }
        if ($request->file_izin_tps != null) {
            $cek_dulu['file_izin_tps'] = 'required|max:10120';
        }
        $validator = Validator::make($request->all(), $cek_dulu);

        if ($validator->fails()) {
            return
                MyRB::asError(400)
                ->withMessage('Uppss.. Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        $user = MUser::where('username', $request->username)->latest()->first();
        if ($user != null) {
            return
                MyRB::asError(400)
                ->withMessage('Username Sudah Terpakai!, silahkan gunakan yang lain.!')
                ->withData(null)
                ->build();
        }

        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? 'xxx-xxxx-xxx';

        // -- form payload -- \\
        $form_input_username = $request->username;
        $form_password = Hash::make($request->password);
        $form_nama_user = $request->nama_user;
        $form_level = ($request->level == 2) ? 2 : 3;
        $form_noreg_tempat = $request->noreg_tempat;
        $form_tipe_tempat = ($request->level == 2) ? 'Rumah Sakit' : 'Puskesmas';
        $form_nama_tempat = $request->nama_tempat;
        $form_alamat_tempat = $request->alamat_tempat;
        $form_id_kelurahan = $request->id_kelurahan;
        $form_id_kecamatan = $request->id_kecamatan;
        $form_notlp = $request->notlp;
        $form_nohp = $request->nohp;
        $form_email = $request->email;

        // upload file
        $form_file_izin_ipal = $request->file_izin_ipal;
        $form_file_izin_tps = $request->file_izin_tps;

        // -- FILING_USER -- \\
        $dir_file = '/FILING_USER/File_' . $form_id_user . '_' . $form_uid . '/IZIN/';
        $dir_file_move = public_path() . $dir_file;
        File::makeDirectory($dir_file, $mode = 0777, true, true);

        $uuid = Uuid::uuid4()->toString();
        $form_file_izin_ipal_nama = null;
        $form_file_izin_tps_nama = null;

        if ($form_file_izin_ipal != null) {
            $norut = 1;
            $form_file = 'FILE_IZIN_IPAL_' . $form_id_user  . '_' . $norut . '_' . $uuid . '_.' . $form_file_izin_ipal->extension();
            $form_file_izin_ipal->move($dir_file_move, $form_file);
            $form_file_izin_ipal_nama = $form_file;
        }
        if ($form_file_izin_tps != null) {
            $norut = 1;
            $form_file = 'FILE_IZIN_TPS_' . $form_id_user  . '_' . $norut . '_' . $uuid . '_.' . $form_file_izin_tps->extension();
            $form_file_izin_tps->move($dir_file_move, $form_file);
            $form_file_izin_tps_nama = $form_file;
        }

        // -- main Model -- \\
        $kecamatan = MKecamatan::find($form_id_kecamatan);
        $kelurahan = MKelurahan::find($form_id_kelurahan);

        $tableuser = new MUser();
        $tableuser->username = $form_input_username; // string
        $tableuser->password = $form_password; // string
        $tableuser->nama_user = $form_nama_user; // string
        $tableuser->level = $form_level; // integer
        $tableuser->noreg_tempat = $form_noreg_tempat; // string
        $tableuser->tipe_tempat = $form_tipe_tempat; // string
        $tableuser->nama_tempat = $form_nama_tempat; // string
        $tableuser->alamat_tempat = $form_alamat_tempat; // string
        $tableuser->id_kelurahan = $form_id_kelurahan; // bigInteger
        $tableuser->id_kecamatan = $form_id_kecamatan; // bigInteger
        $tableuser->kelurahan = $kelurahan->nama_kelurahan ?? ''; // string
        $tableuser->kecamatan = $kecamatan->nama_kecamatan ?? ''; // string
        $tableuser->notlp = $form_notlp; // string
        $tableuser->nohp = $form_nohp; // string
        $tableuser->email = $form_email; // string
        $tableuser->izin_ipal = $form_file_izin_ipal_nama; // string
        $tableuser->izin_tps = $form_file_izin_tps_nama; // string
        $tableuser->status_user = 1; // integer
        $tableuser->statusactive_user = 1; // integer
        $tableuser->user_created = $form_username; // string
        // $tableuser->user_updated = 0; // string
        $tableuser->save();

        $resp =
            MyRB::asSuccess(200)
            ->withData(null)
            ->withMessage('Sukses Create Data ' . $form_tipe_tempat . '.!')
            ->build();
        return $resp;
    }
    public function pusRsProsesUpdate(Request $request)
    {
        $cek_dulu = [
            'username' => 'required',
            'nama_user' => 'required',
            'level' => 'required|in:2,3',
            'oldid' => 'required',
        ];

        if ($request->file_izin_ipal != null) {
            $cek_dulu['file_izin_ipal'] = 'required|max:10120';
        }
        if ($request->file_izin_tps != null) {
            $cek_dulu['file_izin_tps'] = 'required|max:10120';
        }
        $validator = Validator::make($request->all(), $cek_dulu);

        if ($validator->fails()) {
            return
                MyRB::asError(400)
                ->withMessage('Uppss.. Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        $user = MUser::where('username', $request->username)->latest()->first();
        if ($user != null && $user->id_user != $request->oldid) {
            return
                MyRB::asError(400)
                ->withMessage('Username Sudah Terpakai!, silahkan gunakan yang lain.!')
                ->withData(null)
                ->build();
        }

        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? 'xxx-xxxx-xxx';

        // -- form payload -- \\
        $form_oldid = $request->oldid;
        $form_input_username = $request->username;
        $form_password = ($request->password == null) ? null : Hash::make($request->password);
        $form_nama_user = $request->nama_user;
        $form_level = ($request->level == 2) ? 2 : 3;
        $form_noreg_tempat = $request->noreg_tempat;
        $form_tipe_tempat = ($request->level == 2) ? 'Rumah Sakit' : 'Puskesmas';
        $form_nama_tempat = $request->nama_tempat;
        $form_alamat_tempat = $request->alamat_tempat;
        $form_id_kelurahan = $request->id_kelurahan;
        $form_id_kecamatan = $request->id_kecamatan;
        $form_notlp = $request->notlp;
        $form_nohp = $request->nohp;
        $form_email = $request->email;

        // upload file
        $form_file_izin_ipal = $request->file_izin_ipal;
        $form_file_izin_tps = $request->file_izin_tps;

        // -- FILING_USER -- \\
        $dir_file = '/FILING_USER/File_' . $form_id_user . '_' . $form_uid . '/IZIN/';
        $dir_file_move = public_path() . $dir_file;
        File::makeDirectory($dir_file, $mode = 0777, true, true);

        $uuid = Uuid::uuid4()->toString();
        $form_file_izin_ipal_nama = null;
        $form_file_izin_tps_nama = null;

        // -- main Model -- \\
        $kecamatan = MKecamatan::find($form_id_kecamatan);
        $kelurahan = MKelurahan::find($form_id_kelurahan);
        $tableuser = MUser::where(['id_user' => $form_oldid, 'statusactive_user' => 1])->latest()->first();
        if ($tableuser == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }

        if ($form_file_izin_ipal != null) {
            $norut = 1;
            $form_file = 'FILE_IZIN_IPAL_' . $form_id_user  . '_' . $norut . '_' . $uuid . '_.' . $form_file_izin_ipal->extension();
            $form_file_izin_ipal->move($dir_file_move, $form_file);
            $form_file_izin_ipal_nama = $form_file;
        }
        if ($form_file_izin_tps != null) {
            $norut = 1;
            $form_file = 'FILE_IZIN_TPS_' . $form_id_user  . '_' . $norut . '_' . $uuid . '_.' . $form_file_izin_tps->extension();
            $form_file_izin_tps->move($dir_file_move, $form_file);
            $form_file_izin_tps_nama = $form_file;
        }

        $tableuser->username = $form_input_username; // string
        if ($form_password != null) {
            $tableuser->password = $form_password; // string
        }
        $tableuser->nama_user = $form_nama_user; // string
        $tableuser->level = $form_level; // integer
        $tableuser->noreg_tempat = $form_noreg_tempat; // string
        $tableuser->tipe_tempat = $form_tipe_tempat; // string
        $tableuser->nama_tempat = $form_nama_tempat; // string
        $tableuser->alamat_tempat = $form_alamat_tempat; // string
        $tableuser->id_kelurahan = $form_id_kelurahan; // bigInteger
        $tableuser->id_kecamatan = $form_id_kecamatan; // bigInteger
        $tableuser->kelurahan = $kelurahan->nama_kelurahan ?? ''; // string
        $tableuser->kecamatan = $kecamatan->nama_kecamatan ?? ''; // string
        $tableuser->notlp = $form_notlp; // string
        $tableuser->nohp = $form_nohp; // string
        $tableuser->email = $form_email; // string
        $tableuser->izin_ipal = $form_file_izin_ipal_nama; // string
        $tableuser->izin_tps = $form_file_izin_tps_nama; // string
        // $tableuser->status_user = 1; // integer
        // $tableuser->statusactive_user = 1; // integer
        // $tableuser->user_created = $form_username; // string
        $tableuser->user_updated = $form_username; // string
        $tableuser->save();

        $resp =
            MyRB::asSuccess(200)
            ->withData(null)
            ->withMessage('Sukses Update Data ' . $form_tipe_tempat . '.!')
            ->build();
        return $resp;
    }
    public function pusRsProsesDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldid' => 'required',
        ]);

        if ($validator->fails()) {
            return
                MyRB::asError(400)
                ->withHttpCode(400)
                ->withMessage('Uppss.. Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? '';

        // -- form payload -- \\
        $form_oldid = $request->oldid;

        // -- main Model -- \\
        $tableuser = MUser::where(['id_user' => $form_oldid, 'statusactive_user' => 1])->latest()->first();
        if ($tableuser == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }
        $tableuser->statusactive_user = 0;
        $tableuser->save();

        return
            MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withMessage('Sukses Melakukan Delete.!')
            ->withData(null)
            ->build();
    }
}
