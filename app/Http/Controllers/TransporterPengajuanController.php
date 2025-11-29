<?php

namespace App\Http\Controllers;

use App\Models\MKecamatan;
use App\Models\MKelurahan;
use App\Models\MTransporter;
use Illuminate\Http\Request;

use App\Models\MUser;
use App\Models\MTransporterMOU;
use App\Models\MTransporterTmp;
use App\Models\MTransporterTmpMOU;
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

class TransporterPengajuanController extends Controller
{
    function mouTmpProsesData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? '';

        $transporterTmp = MTransporterTmp::where('statusactive_transporter_tmp', '<>', 0)->where('status_transporter_tmp', '<>', 2);
        if ($form_level == '1') {
        } else {
            $transporterTmp = $transporterTmp->where('id_user', $form_id_user); // jika sudah di acc
        }
        $transporterTmp = $transporterTmp->get();
        foreach ($transporterTmp as $key => $v) {
            $user = MUser::where(['id_user' => $v->id_user])->latest()->first();
            $transporterTmpMOU = MTransporterTmpMOU::where('id_transporter_tmp', $v->id_transporter_tmp)->get();
            // $v->files = $transporterTmpMOU->values()->toArray();
            $v->izin = $transporterTmpMOU->filter(function ($val) {
                return $val->tipe == 'IZIN';
            })->values()->toArray();
            $v->files = $transporterTmpMOU->filter(function ($val) {
                return $val->tipe == 'MOU';
            })->values()->toArray();
            $v->user = $user;
        }
        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData($transporterTmp->values()->toArray())
            ->build();
    }
    function mouTmpProsesCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'npwp_transporter'
            // => 'required',
            'nama_transporter'
            => 'required',
            'alamat_transporter'
            => 'required',
            'nama_pemusnah'
            => 'required',
            'metode_pemusnah'
            => 'required',
            // 'id_kelurahan'
            // => 'required',
            // 'id_kecamatan'
            // => 'required',
            'notlp'
            => 'required',
            'nohp'
            => 'required',
            'email'
            => 'required',
            // 'link_input_izin_transporter'
            // => 'required',
            'link_input_izin'
            => 'required',
            'link_input_mou_transporter'
            => 'required',

            // 'file_mou'
            // => 'required|max:10120',
        ]);

        if ($validator->fails()) {
            return
                MyRB::asError(400)
                ->withMessage('Uppss.. Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? 'xxx-xxxx-xxx';

        // -- form payload -- \\
        $form_npwp_transporter = $request->npwp_transporter;
        $form_nama_transporter = $request->nama_transporter;
        $form_alamat_transporter = $request->alamat_transporter;
        $form_id_kelurahan = $request->id_kelurahan;
        $form_id_kecamatan = $request->id_kecamatan;
        $form_notlp = $request->notlp;
        $form_nohp = $request->nohp;
        $form_email = $request->email;
        // $form_catatan = $request->catatan;

        // upload file
        $form_file_mou = $request->file_mou;
        $form_tgl_mulai = $request->tgl_mulai;
        $form_tgl_akhir = $request->tgl_akhir;

        $form_noizin = $request->noizin;
        $form_nama_pemusnah = $request->nama_pemusnah;
        $form_metode_pemusnah = $request->metode_pemusnah;
        $form_link_input_izin = $request->link_input_izin;
        // link
        // $form_link_input_izin_transporter = $request->link_input_izin_transporter;
        $form_link_input_mou_transporter = $request->link_input_mou_transporter;


        // -- FILING_USER -- \\
        $dir_file = '/FILING_USER/File_' . $form_id_user . '_' . $form_uid . '/MOU/';
        $dir_file_move = public_path() . $dir_file;
        File::makeDirectory($dir_file, $mode = 0777, true, true);

        // -- main Model -- \\
        // $kecamatan = MKecamatan::find($form_id_kecamatan);
        // $kelurahan = MKelurahan::find($form_id_kelurahan);

        $transporterTmp = new MTransporterTmp();
        $transporterTmp->id_user = $form_id_user;
        $transporterTmp->npwp_transporter = $form_npwp_transporter;
        $transporterTmp->nama_transporter = $form_nama_transporter;
        $transporterTmp->alamat_transporter = $form_alamat_transporter;
        // $transporterTmp->id_kelurahan = $form_id_kelurahan;
        // $transporterTmp->id_kecamatan = $form_id_kecamatan;
        // $transporterTmp->kelurahan = $kelurahan->nama_kelurahan ?? '';
        // $transporterTmp->kecamatan = $kecamatan->nama_kecamatan ?? '';
        $transporterTmp->notlp = $form_notlp;
        $transporterTmp->nohp = $form_nohp;
        $transporterTmp->email = $form_email;
        // $transporterTmp->catatan = $form_catatan;
        $transporterTmp->status_transporter_tmp = 1;
        $transporterTmp->statusactive_transporter_tmp = 1;
        $transporterTmp->user_created = $form_username;
        // $transporterTmp->user_updated = 0;

        $transporterTmp->noizin = $form_noizin;
        $transporterTmp->link_input_izin = $form_link_input_izin;
        $transporterTmp->nama_pemusnah = $form_nama_pemusnah;
        $transporterTmp->metode_pemusnah = $form_metode_pemusnah;
        $transporterTmp->save();

        // foreach ($form_link_input_izin_transporter as $key => $value) {
        //     $norut = $key + 1;
        //     $tmp_form_tgl_mulai = null;
        //     $tmp_form_tgl_akhir = null;
        //     try {
        //         $tmp_form_tgl_mulai =
        //             DateTime::createFromFormat("Y-m-d", $form_tgl_mulai[$key]);;
        //     } catch (Exception $ex) {
        //         // dd($ex);
        //         $tmp_form_tgl_mulai = null;
        //     }
        //     try {
        //         $tmp_form_tgl_akhir =
        //             DateTime::createFromFormat("Y-m-d", $form_tgl_akhir[$key]);;
        //     } catch (Exception $ex) {
        //         // dd($ex);
        //         $tmp_form_tgl_akhir = null;
        //     }

        //     $transporterTmpMOU = new MTransporterTmpMOU();
        //     $transporterTmpMOU->norut = $norut;
        //     $transporterTmpMOU->id_transporter_tmp = $transporterTmp->id_transporter_tmp;
        //     $transporterTmpMOU->id_user = $form_id_user;
        //     $transporterTmpMOU->keterangan = '-';
        //     $transporterTmpMOU->tipe = 'IZIN';
        //     $transporterTmpMOU->link_input = $value;
        //     // $transporterTmpMOU->file1 = $form_dir_file;
        //     $transporterTmpMOU->tgl_mulai = $tmp_form_tgl_mulai;
        //     $transporterTmpMOU->tgl_akhir = $tmp_form_tgl_akhir;
        //     $transporterTmpMOU->status_transporter_tmp_mou = 1;
        //     $transporterTmpMOU->statusactive_transporter_tmp_mou = 1;
        //     $transporterTmpMOU->user_created = $form_username;
        //     $transporterTmpMOU->save();
        // }
        foreach ($form_link_input_mou_transporter as $key => $value) {
            $norut = $key + 1;
            $tmp_form_tgl_mulai = null;
            $tmp_form_tgl_akhir = null;
            try {
                $tmp_form_tgl_mulai =
                    DateTime::createFromFormat("Y-m-d", $form_tgl_mulai[$key]);;
            } catch (Exception $ex) {
                // dd($ex);
                $tmp_form_tgl_mulai = null;
            }
            try {
                $tmp_form_tgl_akhir =
                    DateTime::createFromFormat("Y-m-d", $form_tgl_akhir[$key]);;
            } catch (Exception $ex) {
                // dd($ex);
                $tmp_form_tgl_akhir = null;
            }

            $transporterTmpMOU = new MTransporterTmpMOU();
            $transporterTmpMOU->norut = $norut;
            $transporterTmpMOU->id_transporter_tmp = $transporterTmp->id_transporter_tmp;
            $transporterTmpMOU->id_user = $form_id_user;
            $transporterTmpMOU->keterangan = '-';
            $transporterTmpMOU->tipe = 'MOU';
            $transporterTmpMOU->link_input = $value;
            // $transporterTmpMOU->file1 = $form_dir_file;
            $transporterTmpMOU->tgl_mulai = $tmp_form_tgl_mulai;
            $transporterTmpMOU->tgl_akhir = $tmp_form_tgl_akhir;
            $transporterTmpMOU->status_transporter_tmp_mou = 1;
            $transporterTmpMOU->statusactive_transporter_tmp_mou = 1;
            $transporterTmpMOU->user_created = $form_username;
            $transporterTmpMOU->save();
        }
        // foreach ($form_file_mou as $key => $value) {
        //     $norut = $key + 1;
        //     $form_file = 'FILE_' . $transporterTmp->id_transporter_tmp  . '_' . $form_id_user  . '_' . $norut . '_.' . $value->extension();
        //     $form_dir_file = $dir_file . $form_file;
        //     $tmp_form_tgl_mulai = null;
        //     $tmp_form_tgl_akhir = null;
        //     try {
        //         $tmp_form_tgl_mulai =
        //             DateTime::createFromFormat("Y-m-d", $form_tgl_mulai[$key]);;
        //     } catch (Exception $ex) {
        //         // dd($ex);
        //         $tmp_form_tgl_mulai = null;
        //     }
        //     try {
        //         $tmp_form_tgl_akhir =
        //             DateTime::createFromFormat("Y-m-d", $form_tgl_akhir[$key]);;
        //     } catch (Exception $ex) {
        //         // dd($ex);
        //         $tmp_form_tgl_akhir = null;
        //     }

        //     $transporterTmpMOU = new MTransporterTmpMOU();
        //     $transporterTmpMOU->norut = $norut;
        //     $transporterTmpMOU->id_transporter_tmp = $transporterTmp->id_transporter_tmp;
        //     $transporterTmpMOU->id_user = $form_id_user;
        //     $transporterTmpMOU->keterangan = '-';
        //     $transporterTmpMOU->file1 = $form_dir_file;
        //     $transporterTmpMOU->tgl_mulai = $tmp_form_tgl_mulai;
        //     $transporterTmpMOU->tgl_akhir = $tmp_form_tgl_akhir;
        //     $transporterTmpMOU->status_transporter_tmp_mou = 1;
        //     $transporterTmpMOU->statusactive_transporter_tmp_mou = 1;
        //     $transporterTmpMOU->user_created = $form_username;
        //     // $transporterTmpMOU->user_updated = 0;

        //     $value->move($dir_file_move, $form_file);
        //     $transporterTmpMOU->save();
        // }

        $resp =
            MyRB::asSuccess(200)
            ->withData(null)
            ->withMessage('Sukses Create Pengajuan Transporter.!')
            ->build();
        return $resp;
    }
    function mouTmpProsesUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldid' => 'required',
            // 'npwp_transporter'
            // => 'required',
            'nama_transporter'
            => 'required',
            'alamat_transporter'
            => 'required',
            'nama_pemusnah'
            => 'required',
            'metode_pemusnah'
            => 'required',
            // 'id_kelurahan'
            // => 'required',
            // 'id_kecamatan'
            // => 'required',
            'notlp'
            => 'required',
            'nohp'
            => 'required',
            'email'
            => 'required',
            // 'link_input_izin_transporter'
            // => 'required',
            'link_input_izin'
            => 'required',
            'link_input_mou_transporter'
            => 'required',
        ]);

        if ($validator->fails()) {
            return
                MyRB::asError(400)
                ->withMessage('Uppss.. Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
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
        $form_npwp_transporter = $request->npwp_transporter;
        $form_nama_transporter = $request->nama_transporter;
        $form_alamat_transporter = $request->alamat_transporter;
        $form_id_kelurahan = $request->id_kelurahan;
        $form_id_kecamatan = $request->id_kecamatan;
        $form_notlp = $request->notlp;
        $form_nohp = $request->nohp;
        $form_email = $request->email;
        // $form_catatan = $request->catatan;

        // upload file
        $form_file_mou = $request->file_mou;
        $form_tgl_mulai = $request->tgl_mulai;
        $form_tgl_akhir = $request->tgl_akhir;

        $form_noizin = $request->noizin;
        $form_nama_pemusnah = $request->nama_pemusnah;
        $form_metode_pemusnah = $request->metode_pemusnah;
        $form_link_input_izin = $request->link_input_izin;
        // link
        // $form_link_input_izin_transporter = $request->link_input_izin_transporter;
        $form_link_input_mou_transporter = $request->link_input_mou_transporter;

        // -- FILING_USER -- \\
        $dir_file = '/FILING_USER/File_' . $form_id_user . '_' . $form_uid . '/MOU/';
        $dir_file_move = public_path() . $dir_file;
        File::makeDirectory($dir_file, $mode = 0777, true, true);

        // -- main Model -- \\
        // $kecamatan = MKecamatan::find($form_id_kecamatan);
        // $kelurahan = MKelurahan::find($form_id_kelurahan);

        $transporterTmp = MTransporterTmp::find($form_oldid);
        $transporterTmpMOU = MTransporterTmpMOU::where('id_transporter_tmp', $form_oldid)->get();
        if ($transporterTmp == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }
        // -- buang file yang berkaitan dulu takutnya input kebanyakan filenya -- \\
        foreach ($transporterTmpMOU as $key => $value) {
            $row = MTransporterTmpMOU::find($value->id_transporter_tmp_mou);
            $row->delete();
            // try {
            //     $filenya = public_path() . $value->file1;
            //     unlink($filenya);
            // } catch (Exception $ex) {
            // }
        }

        $transporterTmp->id_user = $form_id_user;
        $transporterTmp->npwp_transporter = $form_npwp_transporter;
        $transporterTmp->nama_transporter = $form_nama_transporter;
        $transporterTmp->alamat_transporter = $form_alamat_transporter;
        // $transporterTmp->id_kelurahan = $form_id_kelurahan;
        // $transporterTmp->id_kecamatan = $form_id_kecamatan;
        // $transporterTmp->kelurahan = $kelurahan->nama_kelurahan ?? '';
        // $transporterTmp->kecamatan = $kecamatan->nama_kecamatan ?? '';
        $transporterTmp->notlp = $form_notlp;
        $transporterTmp->nohp = $form_nohp;
        $transporterTmp->email = $form_email;
        // $transporterTmp->catatan = $form_catatan;
        $transporterTmp->status_transporter_tmp = 1;
        $transporterTmp->statusactive_transporter_tmp = 1;
        $transporterTmp->user_created = $form_username;
        // $transporterTmp->user_updated = 0;
        $transporterTmp->noizin = $form_noizin;
        $transporterTmp->nama_pemusnah = $form_nama_pemusnah;
        $transporterTmp->metode_pemusnah = $form_metode_pemusnah;
        $transporterTmp->link_input_izin = $form_link_input_izin;
        $transporterTmp->save();

        // foreach ($form_link_input_izin_transporter as $key => $value) {
        //     $norut = $key + 1;
        //     $tmp_form_tgl_mulai = null;
        //     $tmp_form_tgl_akhir = null;
        //     try {
        //         $tmp_form_tgl_mulai =
        //             DateTime::createFromFormat("Y-m-d", $form_tgl_mulai[$key]);;
        //     } catch (Exception $ex) {
        //         // dd($ex);
        //         $tmp_form_tgl_mulai = null;
        //     }
        //     try {
        //         $tmp_form_tgl_akhir =
        //             DateTime::createFromFormat("Y-m-d", $form_tgl_akhir[$key]);;
        //     } catch (Exception $ex) {
        //         // dd($ex);
        //         $tmp_form_tgl_akhir = null;
        //     }

        //     $transporterTmpMOU = new MTransporterTmpMOU();
        //     $transporterTmpMOU->norut = $norut;
        //     $transporterTmpMOU->id_transporter_tmp = $transporterTmp->id_transporter_tmp;
        //     $transporterTmpMOU->id_user = $form_id_user;
        //     $transporterTmpMOU->keterangan = '-';
        //     $transporterTmpMOU->tipe = 'IZIN';
        //     $transporterTmpMOU->link_input = $value;
        //     // $transporterTmpMOU->file1 = $form_dir_file;
        //     $transporterTmpMOU->tgl_mulai = $tmp_form_tgl_mulai;
        //     $transporterTmpMOU->tgl_akhir = $tmp_form_tgl_akhir;
        //     $transporterTmpMOU->status_transporter_tmp_mou = 1;
        //     $transporterTmpMOU->statusactive_transporter_tmp_mou = 1;
        //     $transporterTmpMOU->user_created = $form_username;
        //     $transporterTmpMOU->save();
        // }
        foreach ($form_link_input_mou_transporter as $key => $value) {
            $norut = $key + 1;
            $tmp_form_tgl_mulai = null;
            $tmp_form_tgl_akhir = null;
            try {
                $tmp_form_tgl_mulai =
                    DateTime::createFromFormat("Y-m-d", $form_tgl_mulai[$key]);;
            } catch (Exception $ex) {
                // dd($ex);
                $tmp_form_tgl_mulai = null;
            }
            try {
                $tmp_form_tgl_akhir =
                    DateTime::createFromFormat("Y-m-d", $form_tgl_akhir[$key]);;
            } catch (Exception $ex) {
                // dd($ex);
                $tmp_form_tgl_akhir = null;
            }

            $transporterTmpMOU = new MTransporterTmpMOU();
            $transporterTmpMOU->norut = $norut;
            $transporterTmpMOU->id_transporter_tmp = $transporterTmp->id_transporter_tmp;
            $transporterTmpMOU->id_user = $form_id_user;
            $transporterTmpMOU->keterangan = '-';
            $transporterTmpMOU->tipe = 'MOU';
            $transporterTmpMOU->link_input = $value;
            // $transporterTmpMOU->file1 = $form_dir_file;
            $transporterTmpMOU->tgl_mulai = $tmp_form_tgl_mulai;
            $transporterTmpMOU->tgl_akhir = $tmp_form_tgl_akhir;
            $transporterTmpMOU->status_transporter_tmp_mou = 1;
            $transporterTmpMOU->statusactive_transporter_tmp_mou = 1;
            $transporterTmpMOU->user_created = $form_username;
            $transporterTmpMOU->save();
        }

        // foreach ($form_file_mou as $key => $value) {
        //     $norut = $key + 1;
        //     $form_file = 'FILE_' . $transporterTmp->id_transporter_tmp  . '_' . $form_id_user  . '_' . $norut . '_.' . $value->extension();
        //     $form_dir_file = $dir_file . $form_file;
        //     $tmp_form_tgl_mulai = null;
        //     $tmp_form_tgl_akhir = null;
        //     try {
        //         $tmp_form_tgl_mulai =
        //             DateTime::createFromFormat("Y-m-d", $form_tgl_mulai[$key]);;
        //     } catch (Exception $ex) {
        //         // dd($ex);
        //         $tmp_form_tgl_mulai = null;
        //     }
        //     try {
        //         $tmp_form_tgl_akhir =
        //             DateTime::createFromFormat("Y-m-d", $form_tgl_akhir[$key]);;
        //     } catch (Exception $ex) {
        //         // dd($ex);
        //         $tmp_form_tgl_akhir = null;
        //     }

        //     $transporterTmpMOU = new MTransporterTmpMOU();
        //     $transporterTmpMOU->norut = $norut;
        //     $transporterTmpMOU->id_transporter_tmp = $transporterTmp->id_transporter_tmp;
        //     $transporterTmpMOU->id_user = $form_id_user;
        //     $transporterTmpMOU->keterangan = '-';
        //     $transporterTmpMOU->file1 = $form_dir_file;
        //     $transporterTmpMOU->tgl_mulai = $tmp_form_tgl_mulai;
        //     $transporterTmpMOU->tgl_akhir = $tmp_form_tgl_akhir;
        //     $transporterTmpMOU->status_transporter_tmp_mou = 1;
        //     $transporterTmpMOU->statusactive_transporter_tmp_mou = 1;
        //     $transporterTmpMOU->user_created = $form_username;
        //     // $transporterTmpMOU->user_updated = 0;

        //     $value->move($dir_file_move, $form_file);
        //     $transporterTmpMOU->save();
        // }

        $resp =
            MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage('Sukses Update Pengajuan Transporter.!')
            ->build();
        return $resp;
    }
    function mouTmpProsesDelete(Request $request)
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
        $transporterTmp = MTransporterTmp::find($form_oldid);
        if ($transporterTmp == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }
        $transporterTmp->statusactive_transporter_tmp = 0;
        $transporterTmp->save();

        return
            MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withMessage('Sukses Melakukan Delete.!')
            ->withData(null)
            ->build();
    }
    function mouTmpProsesValidasi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldid' => 'required', // id_transporter_tmp
            'status_transporter' => 'required',
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

        // -- main Model -- \\
        $form_oldid = $request->oldid;
        $form_status_transporter = $request->status_transporter;
        $form_catatan = $request->catatan ?? '';

        $transporterTmp = MTransporterTmp::find($form_oldid);
        $transporterTmpMOU = MTransporterTmpMOU::where('id_transporter_tmp', $form_oldid)->get();
        if ($transporterTmp == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }
        $transporterTmp->status_transporter_tmp = $form_status_transporter;
        $transporterTmp->catatan = $form_catatan;
        $transporterTmp->user_updated = $form_username;
        $transporterTmp->save();

        if ($form_status_transporter == 2) { // di acc si admin
            $transporter = new MTransporter();
            $transporter->id_transporter_tmp = $transporterTmp->id_transporter_tmp;
            $transporter->id_user = $transporterTmp->id_user;
            $transporter->npwp_transporter = $transporterTmp->npwp_transporter;
            $transporter->nama_transporter = $transporterTmp->nama_transporter;
            $transporter->alamat_transporter = $transporterTmp->alamat_transporter;
            $transporter->id_kelurahan = $transporterTmp->id_kelurahan;
            $transporter->id_kecamatan = $transporterTmp->id_kecamatan;
            $transporter->kelurahan = $transporterTmp->kelurahan;
            $transporter->kecamatan = $transporterTmp->kecamatan;
            $transporter->notlp = $transporterTmp->notlp;
            $transporter->nohp = $transporterTmp->nohp;
            $transporter->email = $transporterTmp->email;
            $transporter->catatan = $transporterTmp->catatan;
            $transporter->status_transporter = $transporterTmp->status_transporter_tmp;
            $transporter->statusactive_transporter = $transporterTmp->statusactive_transporter_tmp;
            $transporter->user_created = $transporterTmp->user_created;
            // $transporter->user_updated = $transporterTmp->user_updated;
            $transporter->noizin = $transporterTmp->noizin;
            $transporter->link_input_izin = $transporterTmp->link_input_izin;
            $transporter->nama_pemusnah = $transporterTmp->nama_pemusnah;
            $transporter->metode_pemusnah = $transporterTmp->metode_pemusnah;
            $transporter->save();

            // $link_izin = $transporterTmpMOU->filter(function ($val) {
            //     return $val->tipe == 'IZIN';
            // });
            $link_mou =  $transporterTmpMOU->filter(function ($val) {
                return $val->tipe == 'MOU';
            });

            // foreach ($link_izin as $key => $value) {
            //     $transporterMOU = new MTransporterMOU();
            //     $transporterMOU->norut = $value->norut;
            //     $transporterMOU->id_transporter = $transporter->id_transporter;
            //     $transporterMOU->id_transporter_tmp = $value->id_transporter_tmp;
            //     $transporterMOU->id_transporter_tmp_mou = $value->id_transporter_tmp_mou;
            //     $transporterMOU->id_user = $value->id_user;
            //     $transporterMOU->keterangan = $value->keterangan;
            //     $transporterMOU->link_input = $value->link_input;
            //     $transporterMOU->tipe = $value->tipe;
            //     // $transporterMOU->file1 = $value->file1;
            //     $transporterMOU->tgl_mulai = $value->tgl_mulai;
            //     $transporterMOU->tgl_akhir = $value->tgl_akhir;
            //     $transporterMOU->status_transporter_mou = 1;
            //     $transporterMOU->statusactive_transporter_mou = 1;
            //     $transporterMOU->user_created = $transporter->user_created;
            //     // $transporterMOU->user_updated = 1;
            //     $transporterMOU->save();
            // }
            foreach ($link_mou as $key => $value) {
                $transporterMOU = new MTransporterMOU();
                $transporterMOU->norut = $value->norut;
                $transporterMOU->id_transporter = $transporter->id_transporter;
                $transporterMOU->id_transporter_tmp = $value->id_transporter_tmp;
                $transporterMOU->id_transporter_tmp_mou = $value->id_transporter_tmp_mou;
                $transporterMOU->id_user = $value->id_user;
                $transporterMOU->keterangan = $value->keterangan;
                $transporterMOU->link_input = $value->link_input;
                $transporterMOU->tipe = $value->tipe;
                // $transporterMOU->file1 = $value->file1;
                $transporterMOU->tgl_mulai = $value->tgl_mulai;
                $transporterMOU->tgl_akhir = $value->tgl_akhir;
                $transporterMOU->status_transporter_mou = 1;
                $transporterMOU->statusactive_transporter_mou = 1;
                $transporterMOU->user_created = $transporter->user_created;
                // $transporterMOU->user_updated = 1;
                $transporterMOU->save();
            }

            // foreach ($transporterTmpMOU as $key => $value) {
            //     $transporterMOU = new MTransporterMOU();
            //     $transporterMOU->norut = $value->norut;
            //     $transporterMOU->id_transporter = $transporter->id_transporter;
            //     $transporterMOU->id_transporter_tmp = $value->id_transporter_tmp;
            //     $transporterMOU->id_transporter_tmp_mou = $value->id_transporter_tmp_mou;
            //     $transporterMOU->id_user = $value->id_user;
            //     $transporterMOU->keterangan = $value->keterangan;
            //     $transporterMOU->file1 = $value->file1;
            //     $transporterMOU->tgl_mulai = $value->tgl_mulai;
            //     $transporterMOU->tgl_akhir = $value->tgl_akhir;
            //     $transporterMOU->status_transporter_mou = 1;
            //     $transporterMOU->statusactive_transporter_mou = 1;
            //     $transporterMOU->user_created = $transporter->user_created;
            //     // $transporterMOU->user_updated = 1;
            //     $transporterMOU->save();
            // }
        }

        return
            MyRB::asSuccess(200)
            ->withMessage('Sukses Melakukan Validasi.!')
            ->withData(null)
            ->build();
    }

    function mouTmpProsesApprove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldid' => 'required',
        ]);

        if ($validator->fails()) {
            return MyRB::asError(400)
                ->withHttpCode(400)
                ->withMessage('Uppss.. Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_username = $user->username ?? '';
        $form_oldid = $request->oldid;

        $transporterTmp = MTransporterTmp::find($form_oldid);
        if ($transporterTmp == null) {
            return MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }

        $transporterTmpMOU = MTransporterTmpMOU::where('id_transporter_tmp', $form_oldid)->get();

        $transporterTmp->status_transporter_tmp = 2;
        $transporterTmp->user_updated = $form_username;
        $transporterTmp->save();

        $transporter = new MTransporter();
        $transporter->id_transporter_tmp = $transporterTmp->id_transporter_tmp;
        $transporter->id_user = $transporterTmp->id_user;
        $transporter->npwp_transporter = $transporterTmp->npwp_transporter;
        $transporter->nama_transporter = $transporterTmp->nama_transporter;
        $transporter->alamat_transporter = $transporterTmp->alamat_transporter;
        $transporter->id_kelurahan = $transporterTmp->id_kelurahan;
        $transporter->id_kecamatan = $transporterTmp->id_kecamatan;
        $transporter->kelurahan = $transporterTmp->kelurahan;
        $transporter->kecamatan = $transporterTmp->kecamatan;
        $transporter->notlp = $transporterTmp->notlp;
        $transporter->nohp = $transporterTmp->nohp;
        $transporter->email = $transporterTmp->email;
        $transporter->catatan = $transporterTmp->catatan;
        $transporter->status_transporter = 2;
        $transporter->statusactive_transporter = $transporterTmp->statusactive_transporter_tmp;
        $transporter->user_created = $transporterTmp->user_created;
        $transporter->noizin = $transporterTmp->noizin;
        $transporter->link_input_izin = $transporterTmp->link_input_izin;
        $transporter->nama_pemusnah = $transporterTmp->nama_pemusnah;
        $transporter->metode_pemusnah = $transporterTmp->metode_pemusnah;
        $transporter->save();

        $link_mou = $transporterTmpMOU->filter(function ($val) {
            return $val->tipe == 'MOU';
        });

        foreach ($link_mou as $value) {
            $transporterMOU = new MTransporterMOU();
            $transporterMOU->norut = $value->norut;
            $transporterMOU->id_transporter = $transporter->id_transporter;
            $transporterMOU->id_transporter_tmp = $value->id_transporter_tmp;
            $transporterMOU->id_transporter_tmp_mou = $value->id_transporter_tmp_mou;
            $transporterMOU->id_user = $value->id_user;
            $transporterMOU->keterangan = $value->keterangan;
            $transporterMOU->link_input = $value->link_input;
            $transporterMOU->tipe = $value->tipe;
            $transporterMOU->tgl_mulai = $value->tgl_mulai;
            $transporterMOU->tgl_akhir = $value->tgl_akhir;
            $transporterMOU->status_transporter_mou = 1;
            $transporterMOU->statusactive_transporter_mou = 1;
            $transporterMOU->user_created = $transporter->user_created;
            $transporterMOU->save();
        }

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withMessage('Sukses Menyetujui Pengajuan Transporter.!')
            ->withData(null)
            ->build();
    }

    function mouTmpProsesReject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldid' => 'required',
            'catatan' => 'required',
        ]);

        if ($validator->fails()) {
            return MyRB::asError(400)
                ->withHttpCode(400)
                ->withMessage('Uppss.. Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        $user = MyUtils::getPayloadToken($request, true);
        $form_username = $user->username ?? '';
        $form_oldid = $request->oldid;
        $form_catatan = $request->catatan;

        $transporterTmp = MTransporterTmp::find($form_oldid);
        if ($transporterTmp == null) {
            return MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }

        $transporterTmp->status_transporter_tmp = 0;
        $transporterTmp->catatan = $form_catatan;
        $transporterTmp->user_updated = $form_username;
        $transporterTmp->save();

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withMessage('Sukses Menolak Pengajuan Transporter.!')
            ->withData(null)
            ->build();
    }
}
