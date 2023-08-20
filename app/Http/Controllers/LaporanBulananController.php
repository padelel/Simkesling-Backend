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

class LaporanBulananController extends Controller
{
    //
    function laporanProsesData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? '';

        $laporanBulanan = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where('id_user', $form_id_user)->get();
        foreach ($laporanBulanan as $key => $v) {
            $laporanBulananB3Padat = MLaporanBulananB3Padat::where('id_laporan_bulanan', $v->id_laporan_bulanan)->get();
            $v->b3padat = $laporanBulananB3Padat->toArray();

            $laporanBulananFile = MLaporanBulananFile::where('id_laporan_bulanan', $v->id_laporan_bulanan)->get();
            $v->file_manifest = $laporanBulananFile->where('tipe_file', 'manifest')->values()->toArray();
            $v->file_logbook = $laporanBulananFile->where('tipe_file', 'logbook')->values()->toArray();
        };
        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData($laporanBulanan->values()->toArray())
            ->build();
    }
    function laporanProsesCreate(Request $request)
    {
        // $table->id('id_laporan_bulanan');
        // $table->bigInteger('id_transporter')->length(11)->nullable();
        // $table->bigInteger('id_user')->length(11)->nullable();
        // $table->string('nama_transporter', 100)->nullable();
        // $table->string('nama_pemusnah', 100)->nullable();
        // $table->string('metode_pemusnah', 100)->nullable();
        // $table->string('berat_limbah_total', 20)->nullable();
        // $table->integer('punya_penyimpanan_tps')->length(1)->default(0)->nullable(); // yes no
        // $table->string('ukuran_penyimpanan_tps', 20)->nullable();
        // $table->integer('punya_pemusnahan_sendiri')->length(1)->default(0)->nullable();
        // $table->string('ukuran_pemusnahan_sendiri', 20)->nullable();
        // $table->string('limbah_b3_covid', 20)->nullable();
        // $table->string('limbah_b3_noncovid', 20)->nullable();
        // $table->string('debit_limbah_cair', 20)->nullable();
        // $table->string('kapasitas_ipal', 20)->nullable();
        // $table->integer('memenuhi_syarat')->length(1)->default(0)->nullable();
        // $table->string('catatan', 255)->nullable();
        // $table->integer('periode')->length(2)->nullable();
        // $table->string('periode_nama', 15)->nullable();
        // $table->integer('tahun')->length(4)->nullable();
        // $table->integer('status_laporan_bulanan')->length(1)->nullable();
        // $table->integer('statusactive_laporan_bulanan')->length(1)->nullable();
        // $table->string('user_created', 100)->nullable();
        // $table->string('user_updated', 100)->nullable();

        // $table->bigInteger('id_laporan_bulanan')->length(11)->nullable();
        // $table->bigInteger('id_user')->length(11)->nullable();
        // $table->integer('norut')->length(3)->nullable();
        // $table->string('kategori', 100)->nullable();
        // $table->string('catatan', 255)->nullable();
        // $table->string('total', 100)->nullable();
        // $table->integer('status_laporan_bulanan_b3padat')->length(1)->nullable();
        // $table->integer('statusactive_laporan_bulanan_b3padat')->length(1)->nullable();
        // $table->string('user_created', 100)->nullable();
        // $table->string('user_updated', 100)->nullable();
        // $table->uuid('uid');

        // $table->bigInteger('id_laporan_bulanan
        // $table->bigInteger('id_user
        // $table->integer('norut
        // $table->string('tipe_file
        // $table->string('file1
        // $table->string('file2
        // $table->string('file3
        // $table->integer('status_laporan_bulanan_file
        // $table->integer('statusactive_laporan_bulanan_file
        // $table->string('user_created
        // $table->string('user_updated

        $validator = Validator::make($request->all(), [
            'id_transporter' => 'required',
            'nama_pemusnah' => 'required',
            'metode_pemusnah' => 'required',
            'berat_limbah_total' =>  'required',
            'punya_penyimpanan_tps' =>  'required',
            'ukuran_penyimpanan_tps' =>  'required',
            'punya_pemusnahan_sendiri' =>  'required',
            'ukuran_pemusnahan_sendiri' =>  'required',
            'limbah_b3_covid' =>  'required',
            'limbah_b3_noncovid' =>  'required',
            'debit_limbah_cair' =>  'required',
            'kapasitas_ipal' =>  'required',
            'memenuhi_syarat' =>  'required',
            'catatan' => 'required',
            'periode' => 'required',
            'tahun' => 'required',

            'limbah_padat_kategori' => 'required',
            'limbah_padat_catatan' => 'required',
            'limbah_padat_berat' => 'required',

            'file_manifest' => 'required|max:10120',
            'file_logbook' => 'required|max:10120',
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
        $form_id_transporter = $request->id_transporter;
        $form_nama_pemusnah = $request->nama_pemusnah;
        $form_metode_pemusnah = $request->metode_pemusnah;
        $form_berat_limbah_total = $request->berat_limbah_total;
        $form_punya_penyimpanan_tps = $request->punya_penyimpanan_tps;
        $form_ukuran_penyimpanan_tps = $request->ukuran_penyimpanan_tps;
        $form_punya_pemusnahan_sendiri = $request->punya_pemusnahan_sendiri;
        $form_ukuran_pemusnahan_sendiri = $request->ukuran_pemusnahan_sendiri;
        $form_limbah_b3_covid = $request->limbah_b3_covid;
        $form_limbah_b3_noncovid = $request->limbah_b3_noncovid;
        $form_debit_limbah_cair = $request->debit_limbah_cair;
        $form_kapasitas_ipal = $request->kapasitas_ipal;
        $form_memenuhi_syarat = $request->memenuhi_syarat;
        $form_catatan = $request->catatan;
        $form_periode = $request->periode;
        $form_periode_nama = Carbon::create()->day(1)->month($form_periode)->locale('id')->monthName;
        $form_tahun = $request->tahun;

        // detail limbat padat
        $form_limbah_padat_kategori = $request->limbah_padat_kategori;
        $form_limbah_padat_catatan = $request->limbah_padat_catatan;
        $form_limbah_padat_berat = $request->limbah_padat_berat;

        // file
        $form_file_manifest = $request->file_manifest;
        $form_file_logbook = $request->file_logbook;

        // -- FILING_USER -- \\
        $dir_file_manifest = '/FILING_USER/File_' . $form_id_user . '_' . $form_uid . '/MANIFEST/';
        $dir_file_manifest_move = public_path() . $dir_file_manifest;
        File::makeDirectory($dir_file_manifest, $mode = 0777, true, true);

        $dir_file_logbook = '/FILING_USER/File_' . $form_id_user . '_' . $form_uid . '/LOGBOOK/';
        $dir_file_logbook_move = public_path() . $dir_file_logbook;
        File::makeDirectory($dir_file_logbook, $mode = 0777, true, true);

        // -- main Model -- \\
        $transporter = MTransporter::find($form_id_transporter);
        if ($transporter == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Transporter Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }

        $laporan_bulanan = new MLaporanBulanan();
        $laporan_bulanan->id_transporter = $transporter->id_transporter; // bigInteger
        $laporan_bulanan->id_user = $form_id_user; // bigInteger
        $laporan_bulanan->nama_transporter = $transporter->nama_transporter; // string
        $laporan_bulanan->nama_pemusnah = $form_nama_pemusnah; // string
        $laporan_bulanan->metode_pemusnah = $form_metode_pemusnah; // string
        $laporan_bulanan->berat_limbah_total = $form_berat_limbah_total; // string
        $laporan_bulanan->punya_penyimpanan_tps = $form_punya_penyimpanan_tps; // integer
        $laporan_bulanan->ukuran_penyimpanan_tps = $form_ukuran_penyimpanan_tps; // string
        $laporan_bulanan->punya_pemusnahan_sendiri = $form_punya_pemusnahan_sendiri; // integer
        $laporan_bulanan->ukuran_pemusnahan_sendiri = $form_ukuran_pemusnahan_sendiri; // string
        $laporan_bulanan->limbah_b3_covid = $form_limbah_b3_covid; // string
        $laporan_bulanan->limbah_b3_noncovid = $form_limbah_b3_noncovid; // string
        $laporan_bulanan->debit_limbah_cair = $form_debit_limbah_cair; // string
        $laporan_bulanan->kapasitas_ipal = $form_kapasitas_ipal; // string
        $laporan_bulanan->memenuhi_syarat = $form_memenuhi_syarat; // integer
        $laporan_bulanan->catatan = $form_catatan; // string
        $laporan_bulanan->periode = $form_periode; // integer
        $laporan_bulanan->periode_nama = $form_periode_nama; // string
        $laporan_bulanan->tahun = $form_tahun; // integer
        $laporan_bulanan->status_laporan_bulanan = 1; // integer
        $laporan_bulanan->statusactive_laporan_bulanan = 1; // integer
        $laporan_bulanan->user_created = $form_username; // string
        // $laporan_bulanan->user_updated = 0; // string
        $laporan_bulanan->save();

        foreach ($form_limbah_padat_kategori as $key => $v) {
            $tmp_limbah_padat_kategori = $form_limbah_padat_kategori[$key] ?? '';
            $tmp_limbah_padat_catatan = $form_limbah_padat_catatan[$key] ?? '';
            $tmp_limbah_padat_berat = $form_limbah_padat_berat[$key] ?? '';

            $laporan_bulanan_b3padat = new MLaporanBulananB3Padat();
            $laporan_bulanan_b3padat->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
            $laporan_bulanan_b3padat->id_user = $form_id_user;
            $laporan_bulanan_b3padat->norut = $key + 1;
            $laporan_bulanan_b3padat->kategori = $tmp_limbah_padat_kategori;
            $laporan_bulanan_b3padat->catatan = $tmp_limbah_padat_catatan;
            $laporan_bulanan_b3padat->total = $tmp_limbah_padat_berat;
            $laporan_bulanan_b3padat->status_laporan_bulanan_b3padat = 1;
            $laporan_bulanan_b3padat->statusactive_laporan_bulanan_b3padat = 1;
            $laporan_bulanan_b3padat->user_created = $form_username;
            // $laporan_bulanan_b3padat->user_updated = 0;
            $laporan_bulanan_b3padat->save();
        }

        // -- MANIFEST
        foreach ($form_file_manifest as $key => $v) {
            $norut = $key + 1;
            $form_file = 'FILE_MANIFEST_' . $laporan_bulanan->id_laporan_bulanan  . '_' . $form_id_user  . '_' . $norut . '_.' . $v->extension();
            $form_dir_file = $dir_file_manifest . $form_file;

            $laporan_bulanan_file = new MLaporanBulananFile();
            $laporan_bulanan_file->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
            $laporan_bulanan_file->id_user = $form_id_user;
            $laporan_bulanan_file->norut = $norut;
            $laporan_bulanan_file->tipe_file = 'manifest';
            $laporan_bulanan_file->file1 = $form_dir_file;
            // $laporan_bulanan_file->file2 = 0;
            // $laporan_bulanan_file->file3 = 0;
            $laporan_bulanan_file->status_laporan_bulanan_file = 1;
            $laporan_bulanan_file->statusactive_laporan_bulanan_file = 1;
            $laporan_bulanan_file->user_created = $form_username;
            // $laporan_bulanan_file->user_updated = 0;

            $v->move($dir_file_manifest_move, $form_file);
            $laporan_bulanan_file->save();
        }

        //-- LOGBOOK
        foreach ($form_file_logbook as $key => $v) {
            $norut = $key + 1;
            $form_file = 'FILE_LOGBOOK_' . $laporan_bulanan->id_laporan_bulanan  . '_' . $form_id_user  . '_' . $norut . '_.' . $v->extension();
            $form_dir_file = $dir_file_logbook . $form_file;

            $laporan_bulanan_file = new MLaporanBulananFile();
            $laporan_bulanan_file->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
            $laporan_bulanan_file->id_user = $form_id_user;
            $laporan_bulanan_file->norut = $norut;
            $laporan_bulanan_file->tipe_file = 'logbook';
            $laporan_bulanan_file->file1 = $form_dir_file;
            // $laporan_bulanan_file->file2 = 0;
            // $laporan_bulanan_file->file3 = 0;
            $laporan_bulanan_file->status_laporan_bulanan_file = 1;
            $laporan_bulanan_file->statusactive_laporan_bulanan_file = 1;
            $laporan_bulanan_file->user_created = $form_username;
            // $laporan_bulanan_file->user_updated = 0;

            $v->move($dir_file_logbook_move, $form_file);
            $laporan_bulanan_file->save();
        }

        $resp =
            MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage("Sukses Create Laporan Bulan '" . $form_periode_nama . " " . $form_tahun . "' .!")
            ->build();
        return $resp;
    }

    function laporanProsesUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_transporter' => 'required',
            'nama_pemusnah' => 'required',
            'metode_pemusnah' => 'required',
            'berat_limbah_total' =>  'required',
            'punya_penyimpanan_tps' =>  'required',
            'ukuran_penyimpanan_tps' =>  'required',
            'punya_pemusnahan_sendiri' =>  'required',
            'ukuran_pemusnahan_sendiri' =>  'required',
            'limbah_b3_covid' =>  'required',
            'limbah_b3_noncovid' =>  'required',
            'debit_limbah_cair' =>  'required',
            'kapasitas_ipal' =>  'required',
            'memenuhi_syarat' =>  'required',
            'catatan' => 'required',
            'periode' => 'required',
            'tahun' => 'required',

            'limbah_padat_kategori' => 'required',
            'limbah_padat_catatan' => 'required',
            'limbah_padat_berat' => 'required',

            'file_manifest' => 'required|max:10120',
            'file_logbook' => 'required|max:10120',
            'oldid' => 'required', // id_laporan_bulanan
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
        $form_oldid = $request->oldid; // id_laporan_bulanan

        $form_npwp_transporter = $request->npwp_transporter;
        $form_id_transporter = $request->id_transporter;
        $form_nama_pemusnah = $request->nama_pemusnah;
        $form_metode_pemusnah = $request->metode_pemusnah;
        $form_berat_limbah_total = $request->berat_limbah_total;
        $form_punya_penyimpanan_tps = $request->punya_penyimpanan_tps;
        $form_ukuran_penyimpanan_tps = $request->ukuran_penyimpanan_tps;
        $form_punya_pemusnahan_sendiri = $request->punya_pemusnahan_sendiri;
        $form_ukuran_pemusnahan_sendiri = $request->ukuran_pemusnahan_sendiri;
        $form_limbah_b3_covid = $request->limbah_b3_covid;
        $form_limbah_b3_noncovid = $request->limbah_b3_noncovid;
        $form_debit_limbah_cair = $request->debit_limbah_cair;
        $form_kapasitas_ipal = $request->kapasitas_ipal;
        $form_memenuhi_syarat = $request->memenuhi_syarat;
        $form_catatan = $request->catatan;
        $form_periode = $request->periode;
        $form_periode_nama = Carbon::create()->day(1)->month($form_periode)->locale('id')->monthName;
        $form_tahun = $request->tahun;

        // detail limbat padat
        $form_limbah_padat_kategori = $request->limbah_padat_kategori;
        $form_limbah_padat_catatan = $request->limbah_padat_catatan;
        $form_limbah_padat_berat = $request->limbah_padat_berat;

        // file
        $form_file_manifest = $request->file_manifest;
        $form_file_logbook = $request->file_logbook;

        // -- FILING_USER -- \\
        $dir_file_manifest = '/FILING_USER/File_' . $form_id_user . '_' . $form_uid . '/MANIFEST/';
        $dir_file_manifest_move = public_path() . $dir_file_manifest;
        File::makeDirectory($dir_file_manifest, $mode = 0777, true, true);

        $dir_file_logbook = '/FILING_USER/File_' . $form_id_user . '_' . $form_uid . '/LOGBOOK/';
        $dir_file_logbook_move = public_path() . $dir_file_logbook;
        File::makeDirectory($dir_file_logbook, $mode = 0777, true, true);

        // -- main Model -- \\
        $transporter = MTransporter::find($form_id_transporter);
        $laporan_bulanan = MLaporanBulanan::find($form_oldid);
        if ($transporter == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Transporter Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }
        if ($laporan_bulanan == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Laporan Bulanan Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }

        $laporan_bulanan_b3padat = MLaporanBulananB3Padat::whereIn('id_laporan_bulanan', [$form_oldid, intval($form_oldid)])->get();
        $laporan_bulanan_file = MLaporanBulananFile::whereIn('id_laporan_bulanan', [$form_oldid, intval($form_oldid)])->get();
        // dd($form_oldid);
        foreach ($laporan_bulanan_b3padat as $key => $v) {
            try {
                $row = MLaporanBulananB3Padat::find($v->id_laporan_bulanan_b3padat);
                $row->delete();
            } catch (Exception $ex) {
            }
        }
        foreach ($laporan_bulanan_file as $key => $v) {
            try {
                $row = MLaporanBulananFile::find($v->id_laporan_bulanan_file);
                $row->delete();
            } catch (Exception $ex) {
            }
        }

        $laporan_bulanan->id_transporter = $transporter->id_transporter; // bigInteger
        $laporan_bulanan->id_user = $form_id_user; // bigInteger
        $laporan_bulanan->nama_transporter = $transporter->nama_transporter; // string
        $laporan_bulanan->nama_pemusnah = $form_nama_pemusnah; // string
        $laporan_bulanan->metode_pemusnah = $form_metode_pemusnah; // string
        $laporan_bulanan->berat_limbah_total = $form_berat_limbah_total; // string
        $laporan_bulanan->punya_penyimpanan_tps = $form_punya_penyimpanan_tps; // integer
        $laporan_bulanan->ukuran_penyimpanan_tps = $form_ukuran_penyimpanan_tps; // string
        $laporan_bulanan->punya_pemusnahan_sendiri = $form_punya_pemusnahan_sendiri; // integer
        $laporan_bulanan->ukuran_pemusnahan_sendiri = $form_ukuran_pemusnahan_sendiri; // string
        $laporan_bulanan->limbah_b3_covid = $form_limbah_b3_covid; // string
        $laporan_bulanan->limbah_b3_noncovid = $form_limbah_b3_noncovid; // string
        $laporan_bulanan->debit_limbah_cair = $form_debit_limbah_cair; // string
        $laporan_bulanan->kapasitas_ipal = $form_kapasitas_ipal; // string
        $laporan_bulanan->memenuhi_syarat = $form_memenuhi_syarat; // integer
        $laporan_bulanan->catatan = $form_catatan; // string
        $laporan_bulanan->periode = $form_periode; // integer
        $laporan_bulanan->periode_nama = $form_periode_nama; // string
        $laporan_bulanan->tahun = $form_tahun; // integer
        $laporan_bulanan->status_laporan_bulanan = 1; // integer
        $laporan_bulanan->statusactive_laporan_bulanan = 1; // integer
        // $laporan_bulanan->user_created = $form_username; // string
        $laporan_bulanan->user_updated = $form_username; // string
        $laporan_bulanan->save();

        foreach ($form_limbah_padat_kategori as $key => $v) {
            $tmp_limbah_padat_kategori = $form_limbah_padat_kategori[$key] ?? '';
            $tmp_limbah_padat_catatan = $form_limbah_padat_catatan[$key] ?? '';
            $tmp_limbah_padat_berat = $form_limbah_padat_berat[$key] ?? '';

            $laporan_bulanan_b3padat = new MLaporanBulananB3Padat();
            $laporan_bulanan_b3padat->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
            $laporan_bulanan_b3padat->id_user = $form_id_user;
            $laporan_bulanan_b3padat->norut = $key + 1;
            $laporan_bulanan_b3padat->kategori = $tmp_limbah_padat_kategori;
            $laporan_bulanan_b3padat->catatan = $tmp_limbah_padat_catatan;
            $laporan_bulanan_b3padat->total = $tmp_limbah_padat_berat;
            $laporan_bulanan_b3padat->status_laporan_bulanan_b3padat = 1;
            $laporan_bulanan_b3padat->statusactive_laporan_bulanan_b3padat = 1;
            $laporan_bulanan_b3padat->user_created = $form_username;
            // $laporan_bulanan_b3padat->user_updated = 0;
            $laporan_bulanan_b3padat->save();
        }

        // -- MANIFEST
        foreach ($form_file_manifest as $key => $v) {
            $norut = $key + 1;
            $form_file = 'FILE_MANIFEST_' . $laporan_bulanan->id_laporan_bulanan  . '_' . $form_id_user  . '_' . $norut . '_.' . $v->extension();
            $form_dir_file = $dir_file_manifest . $form_file;

            $laporan_bulanan_file = new MLaporanBulananFile();
            $laporan_bulanan_file->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
            $laporan_bulanan_file->id_user = $form_id_user;
            $laporan_bulanan_file->norut = $norut;
            $laporan_bulanan_file->tipe_file = 'manifest';
            $laporan_bulanan_file->file1 = $form_dir_file;
            // $laporan_bulanan_file->file2 = 0;
            // $laporan_bulanan_file->file3 = 0;
            $laporan_bulanan_file->status_laporan_bulanan_file = 1;
            $laporan_bulanan_file->statusactive_laporan_bulanan_file = 1;
            $laporan_bulanan_file->user_created = $form_username;
            // $laporan_bulanan_file->user_updated = 0;

            $v->move($dir_file_manifest_move, $form_file);
            $laporan_bulanan_file->save();
        }

        //-- LOGBOOK
        foreach ($form_file_logbook as $key => $v) {
            $norut = $key + 1;
            $form_file = 'FILE_LOGBOOK_' . $laporan_bulanan->id_laporan_bulanan  . '_' . $form_id_user  . '_' . $norut . '_.' . $v->extension();
            $form_dir_file = $dir_file_logbook . $form_file;

            $laporan_bulanan_file = new MLaporanBulananFile();
            $laporan_bulanan_file->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
            $laporan_bulanan_file->id_user = $form_id_user;
            $laporan_bulanan_file->norut = $norut;
            $laporan_bulanan_file->tipe_file = 'logbook';
            $laporan_bulanan_file->file1 = $form_dir_file;
            // $laporan_bulanan_file->file2 = 0;
            // $laporan_bulanan_file->file3 = 0;
            $laporan_bulanan_file->status_laporan_bulanan_file = 1;
            $laporan_bulanan_file->statusactive_laporan_bulanan_file = 1;
            $laporan_bulanan_file->user_created = $form_username;
            // $laporan_bulanan_file->user_updated = 0;

            $v->move($dir_file_logbook_move, $form_file);
            $laporan_bulanan_file->save();
        }

        $resp =
            MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage("Sukses Update Laporan Bulan '" . $form_periode_nama . " " . $form_tahun . "' .!")
            ->build();
        return $resp;
    }
}
