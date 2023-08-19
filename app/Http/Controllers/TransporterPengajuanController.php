<?php

namespace App\Http\Controllers;

use App\Models\MKecamatan;
use App\Models\MKelurahan;
use App\Models\MTransporter;
use Illuminate\Http\Request;

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
    function mouTmpProsesCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'npwp_transporter'
            => 'required',
            'nama_transporter'
            => 'required',
            'alamat_transporter'
            => 'required',
            'id_kelurahan'
            => 'required',
            'id_kecamatan'
            => 'required',
            'notlp'
            => 'required',
            'nohp'
            => 'required',
            'email'
            => 'required',
            'file_mou'
            => 'required|max:10120',
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


        // -- FILING_USER -- \\
        $dir_file = '/FILING_USER/File_' . $form_id_user . '/MOU/';
        $dir_file_move = public_path() . $dir_file;
        File::makeDirectory($dir_file, $mode = 0777, true, true);

        // -- main Model -- \\
        $kecamatan = MKecamatan::find($form_id_kecamatan);
        $kelurahan = MKelurahan::find($form_id_kelurahan);

        $transporterTmp = new MTransporterTmp();
        $transporterTmp->id_user = $form_id_user;
        $transporterTmp->npwp_transporter = $form_npwp_transporter;
        $transporterTmp->nama_transporter = $form_nama_transporter;
        $transporterTmp->alamat_transporter = $form_alamat_transporter;
        $transporterTmp->id_kelurahan = $form_id_kelurahan;
        $transporterTmp->id_kecamatan = $form_id_kecamatan;
        $transporterTmp->kelurahan = $kelurahan->nama_kelurahan ?? '';
        $transporterTmp->kecamatan = $kecamatan->nama_kecamatan ?? '';
        $transporterTmp->notlp = $form_notlp;
        $transporterTmp->nohp = $form_nohp;
        $transporterTmp->email = $form_email;
        // $transporterTmp->catatan = $form_catatan;
        $transporterTmp->status_transporter_tmp = 1;
        $transporterTmp->statusactive_transporter_tmp = 1;
        $transporterTmp->user_created = $form_username;
        // $transporterTmp->user_updated = 0;
        $transporterTmp->save();

        foreach ($form_file_mou as $key => $value) {
            $norut = $key + 1;
            $form_file = 'FILE_' . $transporterTmp->id_transporter_tmp  . '_' . $form_id_user  . '_' . $norut . '_.' . $value->extension();
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
            $transporterTmpMOU->file1 = $form_file;
            $transporterTmpMOU->tgl_mulai = $tmp_form_tgl_mulai;
            $transporterTmpMOU->tgl_akhir = $tmp_form_tgl_akhir;
            $transporterTmpMOU->status_transporter_tmp_mou = 1;
            $transporterTmpMOU->statusactive_transporter_tmp_mou = 1;

            $value->move($dir_file_move, $form_file);
            $transporterTmpMOU->save();
        }

        $resp =
            MyRB::asSuccess(200)
            ->withData(null)
            ->withMessage('Sukses Create Pengajuan Transporter.!')
            ->build();
        return $resp;
    }
    function mouTmpProsesUpdate(Request $request)
    {
        return $data['a'] = 1;
    }
    function mouTmpProsesDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transporter_id' => 'required',
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

        // -- main Model -- \\
        $form_transporter_id = $request->transporter_id;
        $form_status_transporter = $request->status_transporter;
        $form_catatan = $request->catatan ?? '';

        $transporterTmp = MTransporterTmp::find($form_transporter_id);
        if ($transporterTmp == null) {
            return
                MyRB::asError(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }
        $transporterTmp->statusactive_transporter_tmp = 0;
        $transporterTmp->save();

        return
            MyRB::asSuccess(200)
            ->withMessage('Sukses Melakukan Delete.!')
            ->withData(null)
            ->build();
    }
    function mouTmpProsesValidasi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transporter_id' => 'required',
            'status_transporter' => 'required',
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

        // -- main Model -- \\
        $form_transporter_id = $request->transporter_id;
        $form_status_transporter = $request->status_transporter;
        $form_catatan = $request->catatan ?? '';

        $transporterTmp = MTransporterTmp::find($form_transporter_id);
        $transporterTmpMOU = MTransporterTmpMOU::where('id_transporter_tmp', $form_transporter_id)->get();
        if ($transporterTmp == null) {
            return
                MyRB::asError(404)
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
            $transporter->save();

            foreach ($transporterTmpMOU as $key => $value) {
                $transporterMOU = new MTransporterMOU();
                $transporterMOU->norut = $value->norut;
                $transporterMOU->id_transporter = $transporter->id_transporter;
                $transporterMOU->id_transporter_tmp = $value->id_transporter_tmp;
                $transporterMOU->id_transporter_tmp_mou = $value->id_transporter_tmp_mou;
                $transporterMOU->id_user = $value->id_user;
                $transporterMOU->keterangan = $value->keterangan;
                $transporterMOU->file1 = $value->file1;
                $transporterMOU->tgl_mulai = $value->tgl_mulai;
                $transporterMOU->tgl_akhir = $value->tgl_akhir;
                $transporterMOU->status_transporter_mou = 1;
                $transporterMOU->statusactive_transporter_mou = 1;
                $transporterMOU->user_created = $transporter->user_created;
                // $transporterMOU->user_updated = 1;
                $transporterMOU->save();
            }
        }

        return
            MyRB::asSuccess(200)
            ->withMessage('Sukses Melakukan Validasi.!')
            ->withData(null)
            ->build();
    }
}
