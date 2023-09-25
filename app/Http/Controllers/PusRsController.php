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
    public function pusRsProsesDataProfile(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? 'xxx-xxxx-xxx';

        $user = MUser::where('id_user', $form_id_user)->latest()->first();
        // $dataUser = $user->values()->toArray();
        // $resp['data'] = $user;
        return MyRB::asSuccess(200)
            ->withMessage('Success get data File.!')
            ->withData(['data' => $user])
            ->build();
    }
    public function pusRsProsesData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? 'xxx-xxxx-xxx';


        $user = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1');

        if ($form_level == '1') {
        } else {
            $user = $user->whereIn('level', ['2', '3']);
        }
        $user = $user->get();
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
            'link_manifest' => 'required',
            'link_logbook' => 'required',
            'link_lab_ipal' => 'required',
            'link_lab_lain' => 'required',
            'link_dokumen_lingkungan_rs' => 'required',
            'link_izin_transporter' => 'required',
            'link_mou_transporter' => 'required',
            // 'link_swa_pantau' => 'required',
            // 'link_lab_limbah_cair' => 'required',
            // 'link_izin_ipal' => 'required',
            // 'link_izin_tps' => 'required',
            // 'link_ukl' => 'required',
            // 'link_upl' => 'required',
            // 'link1' => 'required',
            // 'link2' => 'required',
            // 'link3' => 'required',
            'kapasitas_ipal' => 'required',
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
        $form_link_manifest = $request->link_manifest;
        $form_link_logbook = $request->link_logbook;
        $form_link_lab_ipal = $request->link_lab_ipal;
        $form_link_lab_lain = $request->link_lab_lain;
        $form_link_dokumen_lingkungan_rs = $request->link_dokumen_lingkungan_rs;
        $form_link_izin_transporter = $request->link_izin_transporter;
        $form_link_mou_transporter = $request->link_mou_transporter;
        $form_link_swa_pantau = $request->link_swa_pantau;
        $form_link_lab_limbah_cair = $request->link_lab_limbah_cair;
        $form_link1 = $request->link1;
        $form_link2 = $request->link2;
        $form_link3 = $request->link3;
        $form_link_izin_ipal = $request->link_izin_ipal;
        $form_link_izin_tps = $request->link_izin_tps;
        $form_link_ukl = $request->link_ukl;
        $form_link_upl = $request->link_upl;
        $form_kapasitas_ipal = $request->kapasitas_ipal;
        $form_link_input_dokumen_lingkungan_rs = $request->link_input_dokumen_lingkungan_rs;

        // upload file
        $form_file_izin_ipal = $request->file_izin_ipal;
        $form_file_izin_tps = $request->file_izin_tps;

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
        $tableuser->link_manifest = $form_link_manifest; // string
        $tableuser->link_logbook = $form_link_logbook; // string
        $tableuser->link_lab_ipal = $form_link_lab_ipal; // string
        $tableuser->link_lab_lain = $form_link_lab_lain; // string
        $tableuser->link_dokumen_lingkungan_rs = $form_link_dokumen_lingkungan_rs; // string
        $tableuser->link_izin_transporter = $form_link_izin_transporter; // string
        $tableuser->link_mou_transporter = $form_link_mou_transporter; // string
        $tableuser->link_swa_pantau = $form_link_swa_pantau; // string
        $tableuser->link_lab_limbah_cair = $form_link_lab_limbah_cair; // string
        $tableuser->link1 = $form_link1; // string
        $tableuser->link2 = $form_link2; // string
        $tableuser->link3 = $form_link3; // string
        $tableuser->link_izin_ipal = $form_link_izin_ipal; // string
        $tableuser->link_izin_tps = $form_link_izin_tps; // string
        $tableuser->link_ukl = $form_link_ukl; // string
        $tableuser->link_upl = $form_link_upl; // string
        $tableuser->kapasitas_ipal = $form_kapasitas_ipal; // string
        $tableuser->link_input_dokumen_lingkungan_rs = $form_link_input_dokumen_lingkungan_rs; // string
        // $tableuser->izin_ipal = $form_file_izin_ipal_nama; // string
        // $tableuser->izin_tps = $form_file_izin_tps_nama; // string
        $tableuser->status_user = 1; // integer
        $tableuser->statusactive_user = 1; // integer
        $tableuser->user_created = $form_username; // string
        // $tableuser->user_updated = 0; // string
        $tableuser->save();

        // -- FILING_USER -- \\
        $dir_file = '/FILING_USER/File_' . $tableuser->id_user . '_' . $tableuser->uid . '/IZIN/';
        $dir_file_move = public_path() . $dir_file;
        File::makeDirectory($dir_file, $mode = 0777, true, true);

        // $last_id =
        $uuid = Uuid::uuid4()->toString();
        $form_file_izin_ipal_nama = null;
        $form_file_izin_tps_nama = null;

        if ($form_file_izin_ipal != null) {
            $norut = 1;
            $form_file = 'FILE_IZIN_IPAL_' . $tableuser->id_user  . '_' . $norut . '_' . $tableuser->uid . '_.' . $form_file_izin_ipal->extension();
            $form_file_izin_ipal->move($dir_file_move, $form_file);
            $form_file_izin_ipal_nama = $form_file;
        }
        if ($form_file_izin_tps != null) {
            $norut = 1;
            $form_file = 'FILE_IZIN_TPS_' . $tableuser->id_user  . '_' . $norut . '_' . $tableuser->uid . '_.' . $form_file_izin_tps->extension();
            $form_file_izin_tps->move($dir_file_move, $form_file);
            $form_file_izin_tps_nama = $form_file;
        }
        // $tableuser = MUser::find($tableuser->id_user);
        $tableuser->izin_ipal = $form_file_izin_ipal_nama;
        $tableuser->izin_tps = $form_file_izin_tps_nama;
        $tableuser->updated_at = null;
        // $tableuser->updated_at = null;
        $tableuser->save();
        // $tableuser->save(['timestamps' => false]);

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
            'link_manifest' => 'required',
            'link_logbook' => 'required',
            'link_lab_ipal' => 'required',
            'link_lab_lain' => 'required',
            'link_dokumen_lingkungan_rs' => 'required',
            'link_izin_transporter' => 'required',
            'link_mou_transporter' => 'required',
            // 'link_swa_pantau' => 'required',
            // 'link_lab_limbah_cair' => 'required',
            // 'link_izin_ipal' => 'required',
            // 'link_izin_tps' => 'required',
            // 'link_ukl' => 'required',
            // 'link_upl' => 'required',
            'kapasitas_ipal' => 'required',
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
        $form_link_manifest = $request->link_manifest;
        $form_link_logbook = $request->link_logbook;
        $form_link_lab_ipal = $request->link_lab_ipal;
        $form_link_lab_lain = $request->link_lab_lain;
        $form_link_dokumen_lingkungan_rs = $request->link_dokumen_lingkungan_rs;
        $form_link_izin_transporter = $request->link_izin_transporter;
        $form_link_mou_transporter = $request->link_mou_transporter;
        $form_link_swa_pantau = $request->link_swa_pantau;
        $form_link_lab_limbah_cair = $request->link_lab_limbah_cair;
        $form_link1 = $request->link1;
        $form_link2 = $request->link2;
        $form_link3 = $request->link3;
        $form_link_izin_ipal = $request->link_izin_ipal;
        $form_link_izin_tps = $request->link_izin_tps;
        $form_link_ukl = $request->link_ukl;
        $form_link_upl = $request->link_upl;
        $form_link_input_izin_ipal = ($request->link_input_izin_ipal == null) ? null : $request->link_input_izin_ipal;
        $form_link_input_izin_tps = ($request->link_input_izin_tps == null) ? null : $request->link_input_izin_tps;
        $form_kapasitas_ipal = $request->kapasitas_ipal;
        $form_link_input_dokumen_lingkungan_rs = $request->link_input_dokumen_lingkungan_rs;

        // upload file
        $form_file_izin_ipal = $request->file_izin_ipal;
        $form_file_izin_tps = $request->file_izin_tps;

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
        $tableuser->link_manifest = $form_link_manifest; // string
        $tableuser->link_logbook = $form_link_logbook; // string
        $tableuser->link_lab_ipal = $form_link_lab_ipal; // string
        $tableuser->link_lab_lain = $form_link_lab_lain; // string
        $tableuser->link_dokumen_lingkungan_rs = $form_link_dokumen_lingkungan_rs; // string
        $tableuser->link_izin_transporter = $form_link_izin_transporter; // string
        $tableuser->link_mou_transporter = $form_link_mou_transporter; // string
        $tableuser->link_swa_pantau = $form_link_swa_pantau; // string
        $tableuser->link_lab_limbah_cair = $form_link_lab_limbah_cair; // string
        $tableuser->link1 = $form_link1; // string
        $tableuser->link2 = $form_link2; // string
        $tableuser->link3 = $form_link3; // string
        $tableuser->link_izin_ipal = $form_link_izin_ipal; // string
        $tableuser->link_izin_tps = $form_link_izin_tps; // string
        if ($form_link_input_izin_ipal != null) {
            $tableuser->link_input_izin_ipal = $form_link_input_izin_ipal; // string
        }
        if ($form_link_input_izin_tps != null) {
            $tableuser->link_input_izin_tps = $form_link_input_izin_tps; // string
        }
        $tableuser->link_ukl = $form_link_ukl; // string
        $tableuser->link_upl = $form_link_upl; // string
        $tableuser->kapasitas_ipal = $form_kapasitas_ipal; // string
        $tableuser->link_input_dokumen_lingkungan_rs = $form_link_input_dokumen_lingkungan_rs; // string
        // $tableuser->izin_ipal = $form_file_izin_ipal_nama; // string
        // $tableuser->izin_tps = $form_file_izin_tps_nama; // string
        // $tableuser->status_user = 1; // integer
        // $tableuser->statusactive_user = 1; // integer
        // $tableuser->user_created = $form_username; // string
        $tableuser->user_updated = $form_username; // string
        $tableuser->save();

        // -- FILING_USER -- \\
        $dir_file = '/FILING_USER/File_' . $tableuser->id_user . '_' . $tableuser->uid . '/IZIN/';
        $dir_file_move = public_path() . $dir_file;
        File::makeDirectory($dir_file, $mode = 0777, true, true);

        // $last_id =
        $uuid = Uuid::uuid4()->toString();
        $form_file_izin_ipal_nama = null;
        $form_file_izin_tps_nama = null;

        if ($form_file_izin_ipal != null) {
            $norut = 1;
            $form_file = 'FILE_IZIN_IPAL_' . $tableuser->id_user  . '_' . $norut . '_' . $tableuser->uid . '_.' . $form_file_izin_ipal->extension();
            $form_file_izin_ipal->move($dir_file_move, $form_file);
            $form_file_izin_ipal_nama = $dir_file . $form_file;
        }
        if ($form_file_izin_tps != null) {
            $norut = 1;
            $form_file = 'FILE_IZIN_TPS_' . $tableuser->id_user  . '_' . $norut . '_' . $tableuser->uid . '_.' . $form_file_izin_tps->extension();
            $form_file_izin_tps->move($dir_file_move, $form_file);
            $form_file_izin_tps_nama = $dir_file . $form_file;
        }
        // $tableuser = MUser::find($tableuser->id_user);
        $tableuser->izin_ipal = $form_file_izin_ipal_nama;
        $tableuser->izin_tps = $form_file_izin_tps_nama;
        // $tableuser->updated_at = null;
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
