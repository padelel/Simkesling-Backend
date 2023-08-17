<?php

namespace App\Http\Controllers;

use App\Models\MTransporterMOU;
use App\Models\MTransporterTmp;
use App\Models\MTransporterTmpMOU;
use Illuminate\Http\Request;
// use MilanTarami\ApiResponseBuilder\Facades\ResponseBuilder;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder as RB;
use App\MyResponseBuilder as MyRB;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PusRsController extends Controller
{
    //

    function mouTmpProsesCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transporter_id'
            => 'required',
            'file_mou'
            => 'required|max:10120',
            // 'tgl_mulai'
            // => 'required',
            // 'tgl_akhir'
            // => 'required',
        ]);

        if ($validator->fails()) {
            return
                MyRB::asError(400)
                ->withMessage('Uppss.. Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        $form_transporter_id = $request->transporter_id;
        $form_file_mou = $request->file_mou;
        $form_tgl_mulai = $request->tgl_mulai;
        $form_tgl_akhir = $request->tgl_akhir;

        $form_id_user = 1;

        // -- FILING_USER -- \\
        $dir_file = '/FILING_USER/File_' . $form_id_user . '/MOU/';
        $dir_file_move = public_path() . $dir_file;
        File::makeDirectory($dir_file, $mode = 0777, true, true);

        $transporterTmp = new MTransporterTmp();
        $transporterTmp->id_user = $form_id_user;
        $transporterTmp->nama_transporter = '-- test --';
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
            $transporterTmpMOU->file1 = '-- test --';
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
        return $data['a'] = 1;
    }
}
