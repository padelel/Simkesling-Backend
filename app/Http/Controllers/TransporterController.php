<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MTransporterMOU;
use App\Models\MTransporterTmp;
use App\Models\MTransporterTmpMOU;
use App\Models\MKecamatan;
use App\Models\MKelurahan;
use App\Models\MTransporter;
use App\Models\MUser;
use App\MyResponseBuilder as MyRB;
use App\MyUtils as MyUtils;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TransporterController extends Controller
{
    function mouProsesData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? '';

        $transporter = MTransporter::where('statusactive_transporter', '<>', 0);
        if ($form_level == '1') {
        } else {
            $transporter = $transporter->where('id_user', $form_id_user);
        }
        $transporter = $transporter->get();
        foreach ($transporter as $key => $v) {
            $user = MUser::where(['id_user' => $v->id_user])->latest()->first();
            $transporterMOU = MTransporterMOU::where('id_transporter', $v->id_transporter)->get();
            $dateMOU = MTransporterMOU::where('id_transporter', $v->id_transporter)->orderBy('tgl_akhir', 'DESC')->latest()->first();
            $tgl_now = Carbon::now();
            $tgl_akhir = Carbon::now()->format('Y-m-d H:m:s');
            $masa_berlaku_berakhir = true;
            if ($dateMOU != null) {
                $tgl_akhir = $dateMOU->tgl_akhir;
            }
            $masa_berlaku_berakhir = $tgl_now->gte(Carbon::parse($tgl_akhir));
            $v->masa_berlaku_sudah_berakhir = $masa_berlaku_berakhir;
            $v->masa_berlaku_terakhir = $tgl_akhir;
            $v->files = $transporterMOU->values()->toArray();
            $v->user = $user;
        }
        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData($transporter->values()->toArray())
            ->build();
    }
    function mouProsesDelete(Request $request)
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
        $transporter = MTransporter::find($form_oldid);
        if ($transporter == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }
        $transporter->statusactive_transporter = 0;
        $transporter->save();

        return
            MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withMessage('Sukses Melakukan Delete.!')
            ->withData(null)
            ->build();
    }
    function mouProsesUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldid' => 'required',
            'file_mou' => 'required|max:10120',
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


        // -- FILING_USER -- \\
        $dir_file = '/FILING_USER/File_' . $form_id_user . '_' . $form_uid . '/MOU/';
        $dir_file_move = public_path() . $dir_file;
        File::makeDirectory($dir_file, $mode = 0777, true, true);

        // -- main Model -- \\
        $kecamatan = MKecamatan::find($form_id_kecamatan);
        $kelurahan = MKelurahan::find($form_id_kelurahan);

        // -- main Model -- \\
        $transporter = MTransporter::find($form_oldid);
        $transporterMOU = MTransporterMOU::where('id_transporter', $form_oldid)->get();
        if ($transporter == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }
        // -- buang file yang berkaitan dulu takutnya input kebanyakan filenya -- \\
        foreach ($transporterMOU as $key => $value) {
            $row = MTransporterMOU::find($value->id_transporter_mou);
            $row->delete();
            // try {
            //     $filenya = public_path() . $value->file1;
            //     unlink($filenya);
            // } catch (Exception $ex) {
            // }
        }

        $transporter->id_user = $form_id_user;
        $transporter->npwp_transporter = $form_npwp_transporter;
        $transporter->nama_transporter = $form_nama_transporter;
        $transporter->alamat_transporter = $form_alamat_transporter;
        $transporter->id_kelurahan = $form_id_kelurahan;
        $transporter->id_kecamatan = $form_id_kecamatan;
        $transporter->kelurahan = $kelurahan->nama_kelurahan ?? '';
        $transporter->kecamatan = $kecamatan->nama_kecamatan ?? '';
        $transporter->notlp = $form_notlp;
        $transporter->nohp = $form_nohp;
        $transporter->email = $form_email;
        // $transporter->catatan = $form_catatan;
        // $transporter->status_transporter_tmp = 1;
        // $transporter->statusactive_transporter_tmp = 1;
        $transporter->user_created = $form_username;
        $transporter->user_updated = $form_username;
        $transporter->save();

        foreach ($form_file_mou as $key => $value) {
            $norut = $key + 1;
            $form_file = 'FILE_' . $transporter->id_transporter  . '_' . $form_id_user  . '_' . $norut . '_.' . $value->extension();
            $form_dir_file = $dir_file . $form_file;
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

            $transporterMOU = new MTransporterMOU();
            $transporterMOU->norut = $norut;
            $transporterMOU->id_transporter = $transporter->id_transporter;
            $transporterMOU->id_transporter_tmp = $transporter->id_transporter_tmp;
            $transporterMOU->id_user = $form_id_user;
            $transporterMOU->keterangan = '-';
            $transporterMOU->file1 = $form_dir_file;
            $transporterMOU->tgl_mulai = $tmp_form_tgl_mulai;
            $transporterMOU->tgl_akhir = $tmp_form_tgl_akhir;
            $transporterMOU->status_transporter_mou = 1;
            $transporterMOU->statusactive_transporter_mou = 1;
            // $transporterMOU->user_created = $form_username;
            $transporterMOU->user_updated = $form_username;

            $value->move($dir_file_move, $form_file);
            $transporterMOU->save();
        }

        $resp =
            MyRB::asSuccess(200)
            ->withData(null)
            ->withMessage('Sukses Update Transporter.!')
            ->build();
        return $resp;
    }
}
