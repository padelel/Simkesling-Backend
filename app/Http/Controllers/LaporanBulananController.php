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

class LaporanBulananController extends Controller
{
    //
    function laporanProsesData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? '';

        // -- form input -- \\
        $form_tahun = (($request->tahun == null) ? null : intval($request->tahun)) ?? null;
        $form_periode = (($request->periode == null) ? null : intval($request->periode)) ?? null;

        $tahun = $form_tahun;
        $periode = $form_periode;
        $periode_nama = Carbon::create()->day(1)->month($periode)->format('F');

        $laporanBulanan = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0);
        if ($form_level == '1') {
        } else {
            $laporanBulanan = $laporanBulanan->where('id_user', $form_id_user);
        }
        // $laporanBulanan = $laporanBulanan->where(['periode' => $periode, 'tahun' => $tahun]);
        if ($periode) {
            $laporanBulanan = $laporanBulanan->where('periode', $periode);
        }
        if ($tahun) {
            $laporanBulanan = $laporanBulanan->where('tahun', $tahun);
        }

        $laporanBulanan = $laporanBulanan->orderBy('id_laporan_bulanan', 'DESC')->get();
        foreach ($laporanBulanan as $key => $v) {
            $user = MUser::where(['id_user' => $v->id_user])->latest()->first();
            $v->user = $user;
            $laporanBulananB3Padat = MLaporanBulananB3Padat::where('id_laporan_bulanan', $v->id_laporan_bulanan)->get();
            $v->b3padat = $laporanBulananB3Padat->toArray();

            $laporanBulananFile = MLaporanBulananFile::where('id_laporan_bulanan', $v->id_laporan_bulanan)->get();
            $v->file_manifest = $laporanBulananFile->where('tipe_file', 'manifest')->values()->toArray();
            $v->file_logbook = $laporanBulananFile->where('tipe_file', 'logbook')->values()->toArray();

            $berat_limbah_total = 0;
            $limbah_b3_covid = 0;
            $limbah_b3_noncovid = 0;
            $limbah_jarum = 0;
            $limbah_sludge_ipal = 0;
            $limbah_cair_b3 = 0;
            try {
                $berat_limbah_total = floatval($v->berat_limbah_total ?? '0') ?? 0;
            } catch (Exception $ex) {
            }
            try {
                $limbah_b3_covid = floatval($v->limbah_b3_covid ?? '0') ?? 0;
            } catch (Exception $ex) {
            }
            try {
                $limbah_b3_noncovid = floatval($v->limbah_b3_noncovid ?? '0') ?? 0;
            } catch (Exception $ex) {
            }
            try {
                $limbah_jarum = floatval($v->limbah_jarum ?? '0') ?? 0;
            } catch (Exception $ex) {
            }
            try {
                $limbah_sludge_ipal = floatval($v->limbah_sludge_ipal ?? '0') ?? 0;
            } catch (Exception $ex) {
            }
            try {
                $limbah_cair_b3 = floatval($v->limbah_cair_b3 ?? '0') ?? 0;
            } catch (Exception $ex) {
            }

            // VALIDASI: Total keseluruhan limbah menggunakan berat_limbah_total yang sudah dihitung otomatis
            // berat_limbah_total sudah merupakan hasil penjumlahan dari 6 komponen:
            // - limbah_b3_covid (limbah B3 COVID-19)
            // - limbah_b3_noncovid (limbah B3 non-COVID) 
            // - limbah_jarum (limbah jarum suntik)
            // - limbah_sludge_ipal (limbah sludge IPAL)
            // - limbah_cair_b3 (limbah cair B3)
            // - limbah_b3_nonmedis (limbah B3 non-medis)
            // Jadi tidak perlu dijumlahkan lagi untuk menghindari double counting
            $v->total_keseluruhan_limbah = $berat_limbah_total;
            
            // Pertahankan nilai asli dari database
            $v->berat_limbah_total = $berat_limbah_total;
            $v->limbah_b3_covid = $limbah_b3_covid;
            $v->limbah_b3_noncovid = $limbah_b3_noncovid;
            $v->limbah_jarum = $limbah_jarum;
            $v->limbah_sludge_ipal = $limbah_sludge_ipal;
            $v->limbah_cair_b3 = $limbah_cair_b3;
        };

        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData($laporanBulanan->values()->toArray())
            ->build();
    }
    function laporanRekapitulasiProsesData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? '';

        // -- form input -- \\
        $form_tahun = (($request->tahun == null) ? date("Y") : intval($request->tahun)) ?? null;
        $form_periode = (($request->periode == null) ? null : intval($request->periode)) ?? null;
        $form_search_tempat = $request->search_tempat;

        $tahun = $form_tahun;
        $periode = $form_periode;
        $periode_nama = Carbon::create()->day(1)->month($periode)->format('F');

        // OPTIMIZATION: Use eager loading to reduce N+1 queries
        $users = MUser::where(['statusactive_user' => 1])
            ->where('level', '<>', '1');
        
        if ($form_search_tempat != null && strlen($form_search_tempat) > 0) {
            $users->where('nama_user', 'like', '%' . $form_search_tempat . '%');
        }
        $users = $users->get();

        // OPTIMIZATION: Preload all laporan bulanan data for the year to reduce database queries
        $i_awal = ($periode == null) ? 1 : $periode;
        $i_akhir = ($periode == null) ? 12 : $periode;
        
        $userIds = $users->pluck('id_user')->toArray();
        $laporanBulananData = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)
            ->whereIn('id_user', $userIds)
            ->where('tahun', $tahun)
            ->whereBetween('periode', [$i_awal, $i_akhir])
            ->get()
            ->groupBy(['id_user', 'periode']);

        $laporan_rekapitulasi['users'] = $users;
        $laporan_rekapitulasi['total_seluruh_limbah'] = 0;
        $laporan_rekapitulasi['total_seluruh_limbah_b3'] = 0;
        $laporan_rekapitulasi['total_seluruh_limbah_covid'] = 0;
        $laporan_rekapitulasi['total_seluruh_limbah_noncovid'] = 0;
        $laporan_rekapitulasi['total_seluruh_limbah_b3_noncovid'] = 0;
        $laporan_rekapitulasi['total_seluruh_limbah_b3_medis'] = 0;
        $laporan_rekapitulasi['total_seluruh_limbah_jarum'] = 0;
        $laporan_rekapitulasi['total_seluruh_limbah_sludge_ipal'] = 0;
        $laporan_rekapitulasi['total_seluruh_limbah_padat_infeksius'] = 0;
        $laporan_rekapitulasi['tahun'] = $tahun;
        $laporan_rekapitulasi['laporan'] = [];

        for ($i = $i_awal; $i <= $i_akhir; $i++) {
            $periode_nama = Carbon::create()->day(1)->month($i)->format('F');
            $tmpData['periode'] = $i;
            $tmpData['periode_nama'] = $periode_nama;
            $tmpData['total_limbah'] = 0;
            $tmpData['total_limbah_b3'] = 0;
            $tmpData['total_limbah_covid'] = 0;
            $tmpData['total_limbah_noncovid'] = 0;
            $tmpData['total_limbah_b3_noncovid'] = 0;
            $tmpData['total_limbah_b3_medis'] = 0;
            $tmpData['total_limbah_jarum'] = 0;
            $tmpData['total_limbah_sludge_ipal'] = 0;
            $tmpData['total_limbah_padat_infeksius'] = 0;
            $tmpData['users'] = [];
            
            foreach ($users as $key => $user) {
                $dataUser = clone $user; // Use clone to avoid modifying original user object
                
                // OPTIMIZATION: Get laporan from preloaded data instead of database query
                $laporanBulanan = $laporanBulananData[$user->id_user][$i] ?? collect();
                $laporanBulanan = $laporanBulanan->first();
                
                // OPTIMIZATION: Use helper method to safely extract float values
                $limbahB3Padat = $this->safeFloatValue($laporanBulanan, 'berat_limbah_total');
                $limbahNonCovid = $this->safeFloatValue($laporanBulanan, 'limbah_b3_noncovid');
                $limbahCovid = $this->safeFloatValue($laporanBulanan, 'limbah_b3_covid');
                $limbah_b3_noncovid = $this->safeFloatValue($laporanBulanan, 'limbah_b3_noncovid');
                $limbah_b3_medis = $this->safeFloatValue($laporanBulanan, 'limbah_b3_medis');
                $limbah_jarum = $this->safeFloatValue($laporanBulanan, 'limbah_jarum');
                $limbah_sludge_ipal = $this->safeFloatValue($laporanBulanan, 'limbah_sludge_ipal');
                $limbah_padat_infeksius = $this->safeFloatValue($laporanBulanan, 'limbah_padat_infeksius');

                $dataUser->limbah = $laporanBulanan;
                $dataUser->limbah_b3 = $limbahB3Padat;
                $dataUser->limbah_noncovid = $limbahNonCovid;
                $dataUser->limbah_covid = $limbahCovid;
                $dataUser->limbah_b3_noncovid = $limbah_b3_noncovid;
                $dataUser->limbah_b3_medis = $limbah_b3_medis;
                $dataUser->limbah_jarum = $limbah_jarum;
                $dataUser->limbah_sludge_ipal = $limbah_sludge_ipal;
                $dataUser->limbah_padat_infeksius = $limbah_padat_infeksius;

                // VALIDASI: Total limbah menggunakan berat_limbah_total dari database
                $tmpData['total_limbah'] += $limbahB3Padat;
                $tmpData['total_limbah_b3'] += $limbahB3Padat;
                $tmpData['total_limbah_noncovid'] += $limbahNonCovid;
                $tmpData['total_limbah_covid'] += $limbahCovid;
                $tmpData['total_limbah_b3_noncovid'] += $limbah_b3_noncovid;
                $tmpData['total_limbah_b3_medis'] += $limbah_b3_medis;
                $tmpData['total_limbah_jarum'] += $limbah_jarum;
                $tmpData['total_limbah_sludge_ipal'] += $limbah_sludge_ipal;
                $tmpData['total_limbah_padat_infeksius'] += $limbah_padat_infeksius;

                array_push($tmpData['users'], $dataUser);
            }
            
            // total masing masing limbah pertahun
            $laporan_rekapitulasi['total_seluruh_limbah_b3'] += $tmpData['total_limbah_b3'];
            $laporan_rekapitulasi['total_seluruh_limbah_covid'] += $tmpData['total_limbah_covid'];
            $laporan_rekapitulasi['total_seluruh_limbah_noncovid'] += $tmpData['total_limbah_noncovid'];
            $laporan_rekapitulasi['total_seluruh_limbah_b3_noncovid'] += $tmpData['total_limbah_b3_noncovid'];
            $laporan_rekapitulasi['total_seluruh_limbah_b3_medis'] += $tmpData['total_limbah_b3_medis'];
            $laporan_rekapitulasi['total_seluruh_limbah_jarum'] += $tmpData['total_limbah_jarum'];
            $laporan_rekapitulasi['total_seluruh_limbah_sludge_ipal'] += $tmpData['total_limbah_sludge_ipal'];
            $laporan_rekapitulasi['total_seluruh_limbah_padat_infeksius'] += $tmpData['total_limbah_padat_infeksius'];

            // total seluruh limbah pertahun
            $laporan_rekapitulasi['total_seluruh_limbah'] += $tmpData['total_limbah'];
            array_push($laporan_rekapitulasi['laporan'], $tmpData);
        }

        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData($laporan_rekapitulasi)
            ->build();
    }

    /**
     * OPTIMIZATION: Helper method to safely extract float values from model
     */
    private function safeFloatValue($model, $field)
    {
        if (!$model) return 0;
        
        try {
            return floatval($model->$field ?? '0') ?? 0;
        } catch (Exception $ex) {
            return 0;
        }
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
            // 'nama_pemusnah' => 'required',
            // 'metode_pemusnah' => 'required',
            // 'berat_limbah_total' =>  'required', // Dihitung otomatis dari komponen limbah
            // 'punya_penyimpanan_tps' =>  'required',
            // 'ukuran_penyimpanan_tps' =>  'required',
            // 'punya_pemusnahan_sendiri' =>  'required',
            // 'ukuran_pemusnahan_sendiri' =>  'required',
            // 'limbah_b3_covid' =>  'required',
            // 'limbah_b3_noncovid' =>  'required',
            // 'debit_limbah_cair' =>  'required',
            // 'kapasitas_ipal' =>  'required',
            // 'memenuhi_syarat' =>  'required',
            'catatan' => 'required',
            'periode' => 'required',
            'tahun' => 'required',

            // 'limbah_padat_kategori' => 'required',
            // 'limbah_padat_catatan' => 'required',
            // 'limbah_padat_berat' => 'required',

            // 'file_manifest' => 'required|max:10120',
            // 'file_logbook' => 'required|max:10120',
            'link_input_manifest' => 'required',
            'link_input_logbook' => 'required',
            // 'link_input_lab_ipal' => 'required',
            'link_input_lab_lain' => 'required',
            // 'link_input_dokumen_lingkungan_rs' => 'required',
            // 'link_input_swa_pantau' => 'required',
            // 'link_input_ujilab_cair' => 'required',
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
        $form_limbah_b3_covid = $request->limbah_b3_covid ?? 0;
        $form_limbah_b3_noncovid = $request->limbah_b3_noncovid ?? 0;
        $form_debit_limbah_cair = $request->limbah_cair_b3 ?? 0;
        $form_kapasitas_ipal = $request->kapasitas_ipal;
        $form_memenuhi_syarat = $request->memenuhi_syarat;
        $form_catatan = $request->catatan;
        $form_periode = $request->periode;
        $form_periode_nama = Carbon::create()->day(1)->month($form_periode)->locale('id')->monthName;
        $form_tahun = $request->tahun;

        // detail limbat padat
        $form_limbah_padat_kategori = $request->limbah_padat_kategori ?? [];
        $form_limbah_padat_catatan = $request->limbah_padat_catatan ?? [];
        $form_limbah_padat_berat = $request->limbah_padat_berat ?? [];

        // file
        $form_file_manifest = $request->file_manifest;
        $form_file_logbook = $request->file_logbook;

        $form_link_input_manifest = $request->link_input_manifest;
        $form_link_input_logbook = $request->link_input_logbook;
        $form_link_input_lab_ipal = $request->link_input_lab_ipal;
        $form_link_input_lab_lain = $request->link_input_lab_lain;
        $form_link_input_dokumen_lingkungan_rs = $request->link_input_dokumen_lingkungan_rs;
        $form_link_input_swa_pantau = $request->link_input_swa_pantau ?? '';
        $form_link_input_ujilab_cair = $request->link_input_ujilab_cair ?? '';

        $form_limbah_b3_nonmedis = $request->limbah_b3_nonmedis ?? 0;
        $form_limbah_b3_medis = $request->limbah_b3_medis ?? 0;
        $form_limbah_jarum = $request->limbah_jarum ?? 0;
        $form_limbah_sludge_ipal = $request->limbah_sludge_ipal ?? 0;
        $form_limbah_padat_infeksius = $request->limbah_padat_infeksius ?? 0;
        $form_debit_limbah_cair = $request->limbah_cair_b3 ?? 0;

        $laporanBulanan = MLaporanBulanan::where(['id_user' => $form_id_user, 'periode' => $form_periode, 'tahun' => $form_tahun, 'statusactive_laporan_bulanan' => 1])->get();

        if (count($laporanBulanan) > 0) {
            return
                MyRB::asError(400)
                ->withHttpCode(400)
                ->withMessage('Laporan untuk period `' . $form_periode_nama . ' ' . $form_tahun . '` sudah ada.!')
                ->withData(null)
                ->build();
        }

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

        // Hitung berat_limbah_total otomatis dari 7 komponen limbah
        // Komponen: limbah_b3_covid + limbah_b3_nonmedis + limbah_b3_medis + limbah_jarum + limbah_sludge_ipal + limbah_padat_infeksius + debit_limbah_cair
        // Note: debit_limbah_cair ikut dijumlahkan ke total limbah padat sesuai kebutuhan sistem
        $calculated_berat_limbah_total = floatval($form_limbah_b3_covid) + floatval($form_limbah_b3_nonmedis) + 
                                        floatval($form_limbah_b3_medis) + floatval($form_limbah_jarum) + 
                                        floatval($form_limbah_sludge_ipal) + floatval($form_limbah_padat_infeksius) + floatval($form_debit_limbah_cair);

        $laporan_bulanan = new MLaporanBulanan();
        $laporan_bulanan->id_transporter = $transporter->id_transporter; // bigInteger
        $laporan_bulanan->id_user = $form_id_user; // bigInteger
        $laporan_bulanan->nama_transporter = $transporter->nama_transporter; // string
        $laporan_bulanan->nama_pemusnah = $form_nama_pemusnah; // string
        $laporan_bulanan->metode_pemusnah = $form_metode_pemusnah; // string
        $laporan_bulanan->berat_limbah_total = $calculated_berat_limbah_total; // Dihitung otomatis
        $laporan_bulanan->punya_penyimpanan_tps = $form_punya_penyimpanan_tps; // integer
        $laporan_bulanan->ukuran_penyimpanan_tps = $form_ukuran_penyimpanan_tps; // string
        $laporan_bulanan->punya_pemusnahan_sendiri = $form_punya_pemusnahan_sendiri; // integer
        $laporan_bulanan->ukuran_pemusnahan_sendiri = $form_ukuran_pemusnahan_sendiri; // string
        $laporan_bulanan->limbah_b3_covid = $form_limbah_b3_covid; // string
        $laporan_bulanan->limbah_b3_noncovid = $form_limbah_b3_noncovid; // string
        $laporan_bulanan->limbah_cair_b3 = $form_debit_limbah_cair; // string - menyimpan data limbah cair B3
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
        $laporan_bulanan->link_input_manifest = $form_link_input_manifest;
        $laporan_bulanan->link_input_logbook = $form_link_input_logbook;
        $laporan_bulanan->link_input_lab_ipal = $form_link_input_lab_ipal;
        $laporan_bulanan->link_input_lab_lain = $form_link_input_lab_lain;
        $laporan_bulanan->link_input_dokumen_lingkungan_rs = $form_link_input_dokumen_lingkungan_rs;
        $laporan_bulanan->link_input_swa_pantau = $form_link_input_swa_pantau;
        $laporan_bulanan->link_input_ujilab_cair = $form_link_input_ujilab_cair;

        $laporan_bulanan->limbah_b3_nonmedis = $form_limbah_b3_nonmedis;
        $laporan_bulanan->limbah_b3_medis = $form_limbah_b3_medis;
        $laporan_bulanan->limbah_jarum = $form_limbah_jarum;
        $laporan_bulanan->limbah_sludge_ipal = $form_limbah_sludge_ipal;
        $laporan_bulanan->limbah_padat_infeksius = $form_limbah_padat_infeksius;
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
        // foreach ($form_file_manifest as $key => $v) {
        //     $norut = $key + 1;
        //     $form_file = 'FILE_MANIFEST_' . $laporan_bulanan->id_laporan_bulanan  . '_' . $form_id_user  . '_' . $norut . '_.' . $v->extension();
        //     $form_dir_file = $dir_file_manifest . $form_file;

        //     $laporan_bulanan_file = new MLaporanBulananFile();
        //     $laporan_bulanan_file->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
        //     $laporan_bulanan_file->id_user = $form_id_user;
        //     $laporan_bulanan_file->norut = $norut;
        //     $laporan_bulanan_file->tipe_file = 'manifest';
        //     $laporan_bulanan_file->file1 = $form_dir_file;
        //     // $laporan_bulanan_file->file2 = 0;
        //     // $laporan_bulanan_file->file3 = 0;
        //     $laporan_bulanan_file->status_laporan_bulanan_file = 1;
        //     $laporan_bulanan_file->statusactive_laporan_bulanan_file = 1;
        //     $laporan_bulanan_file->user_created = $form_username;
        //     // $laporan_bulanan_file->user_updated = 0;

        //     $v->move($dir_file_manifest_move, $form_file);
        //     $laporan_bulanan_file->save();
        // }

        // //-- LOGBOOK
        // foreach ($form_file_logbook as $key => $v) {
        //     $norut = $key + 1;
        //     $form_file = 'FILE_LOGBOOK_' . $laporan_bulanan->id_laporan_bulanan  . '_' . $form_id_user  . '_' . $norut . '_.' . $v->extension();
        //     $form_dir_file = $dir_file_logbook . $form_file;

        //     $laporan_bulanan_file = new MLaporanBulananFile();
        //     $laporan_bulanan_file->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
        //     $laporan_bulanan_file->id_user = $form_id_user;
        //     $laporan_bulanan_file->norut = $norut;
        //     $laporan_bulanan_file->tipe_file = 'logbook';
        //     $laporan_bulanan_file->file1 = $form_dir_file;
        //     // $laporan_bulanan_file->file2 = 0;
        //     // $laporan_bulanan_file->file3 = 0;
        //     $laporan_bulanan_file->status_laporan_bulanan_file = 1;
        //     $laporan_bulanan_file->statusactive_laporan_bulanan_file = 1;
        //     $laporan_bulanan_file->user_created = $form_username;
        //     // $laporan_bulanan_file->user_updated = 0;

        //     $v->move($dir_file_logbook_move, $form_file);
        //     $laporan_bulanan_file->save();
        // }

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
            // 'nama_pemusnah' => 'required',
            // 'metode_pemusnah' => 'required',
            // 'berat_limbah_total' =>  'required', // Dihitung otomatis dari komponen limbah
            // 'punya_penyimpanan_tps' =>  'required',
            // 'ukuran_penyimpanan_tps' =>  'required',
            // 'punya_pemusnahan_sendiri' =>  'required',
            // 'ukuran_pemusnahan_sendiri' =>  'required',
            // 'limbah_b3_covid' =>  'required',
            // 'limbah_b3_noncovid' =>  'required',
            // 'debit_limbah_cair' =>  'required',
            // 'kapasitas_ipal' =>  'required',
            // 'memenuhi_syarat' =>  'required',
            'catatan' => 'required',
            'periode' => 'required',
            'tahun' => 'required',

            // 'limbah_padat_kategori' => 'required',
            // 'limbah_padat_catatan' => 'required',
            // 'limbah_padat_berat' => 'required',

            // 'file_manifest' => 'required|max:10120',
            // 'file_logbook' => 'required|max:10120',
            'link_input_manifest' => 'required',
            'link_input_logbook' => 'required',
            // 'link_input_lab_ipal' => 'required',
            'link_input_lab_lain' => 'required',
            // 'link_input_dokumen_lingkungan_rs' => 'required',
            // 'link_input_swa_pantau' => 'required',
            // 'link_input_ujilab_cair' => 'required',
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
        $form_limbah_b3_covid = $request->limbah_b3_covid ?? 0;
        $form_limbah_b3_noncovid = $request->limbah_b3_noncovid ?? 0;
        $form_debit_limbah_cair = $request->limbah_cair_b3 ?? 0;
        $form_kapasitas_ipal = $request->kapasitas_ipal;
        $form_memenuhi_syarat = $request->memenuhi_syarat;
        $form_catatan = $request->catatan;
        $form_periode = $request->periode;
        $form_periode_nama = Carbon::create()->day(1)->month($form_periode)->locale('id')->monthName;
        $form_tahun = $request->tahun;

        // detail limbat padat
        $form_limbah_padat_kategori = $request->limbah_padat_kategori ?? [];
        $form_limbah_padat_catatan = $request->limbah_padat_catatan ?? [];
        $form_limbah_padat_berat = $request->limbah_padat_berat ?? [];

        // file
        $form_file_manifest = $request->file_manifest;
        $form_file_logbook = $request->file_logbook;

        $form_link_input_manifest = $request->link_input_manifest;
        $form_link_input_logbook = $request->link_input_logbook;
        $form_link_input_lab_ipal = $request->link_input_lab_ipal;
        $form_link_input_lab_lain = $request->link_input_lab_lain;
        $form_link_input_dokumen_lingkungan_rs = $request->link_input_dokumen_lingkungan_rs;
        $form_link_input_swa_pantau = $request->link_input_swa_pantau ?? '';
        $form_link_input_ujilab_cair = $request->link_input_ujilab_cair ?? '';

        $form_limbah_b3_nonmedis = $request->limbah_b3_nonmedis ?? 0;
        $form_limbah_b3_medis = $request->limbah_b3_medis ?? 0;
        $form_limbah_jarum = $request->limbah_jarum ?? 0;
        $form_limbah_sludge_ipal = $request->limbah_sludge_ipal ?? 0;
        $form_limbah_padat_infeksius = $request->limbah_padat_infeksius ?? 0;


        // $laporanBulanan = MLaporanBulanan::where(['id_user' => $form_id_user, 'periode' => $form_periode, 'tahun' => $form_tahun, 'statusactive_laporan_bulanan' => 1])->get();

        // if (count($laporanBulanan) > 0) {
        //     return
        //         MyRB::asError(400)
        //         ->withHttpCode(400)
        //         ->withMessage('Laporan untuk period `' . $form_periode_nama . ' ' . $form_tahun . '` sudah ada.!')
        //         ->withData(null)
        //         ->build();
        // }

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
        if ($laporan_bulanan->periode != $form_periode && $laporan_bulanan->tahun != $form_tahun) {
            $cek_laporan_bulanan = MLaporanBulanan::where(['id_user' => $form_id_user, 'periode' => $form_periode, 'tahun' => $form_tahun, 'statusactive_laporan_bulanan' => 1])->get();
            if (count($cek_laporan_bulanan) > 0) {
                return
                    MyRB::asError(400)
                    ->withHttpCode(400)
                    ->withMessage('Laporan untuk period `' . $form_periode_nama . ' ' . $form_tahun . '` sudah ada.!')
                    ->withData(null)
                    ->build();
            }
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

        // Hitung berat_limbah_total otomatis dari 7 komponen limbah
        // Komponen: limbah_b3_covid + limbah_b3_nonmedis + limbah_b3_medis + limbah_jarum + limbah_sludge_ipal + limbah_padat_infeksius + debit_limbah_cair
        // Note: debit_limbah_cair ikut dijumlahkan ke total limbah padat sesuai kebutuhan sistem
        $calculated_berat_limbah_total = floatval($form_limbah_b3_covid) + floatval($form_limbah_b3_nonmedis) + 
                                        floatval($form_limbah_b3_medis) + floatval($form_limbah_jarum) + 
                                        floatval($form_limbah_sludge_ipal) + floatval($form_limbah_padat_infeksius) + floatval($form_debit_limbah_cair);

        $laporan_bulanan->id_transporter = $transporter->id_transporter; // bigInteger
        $laporan_bulanan->id_user = $form_id_user; // bigInteger
        $laporan_bulanan->nama_transporter = $transporter->nama_transporter; // string
        $laporan_bulanan->nama_pemusnah = $form_nama_pemusnah; // string
        $laporan_bulanan->metode_pemusnah = $form_metode_pemusnah; // string
        $laporan_bulanan->berat_limbah_total = $calculated_berat_limbah_total; // Dihitung otomatis
        $laporan_bulanan->punya_penyimpanan_tps = $form_punya_penyimpanan_tps; // integer
        $laporan_bulanan->ukuran_penyimpanan_tps = $form_ukuran_penyimpanan_tps; // string
        $laporan_bulanan->punya_pemusnahan_sendiri = $form_punya_pemusnahan_sendiri; // integer
        $laporan_bulanan->ukuran_pemusnahan_sendiri = $form_ukuran_pemusnahan_sendiri; // string
        $laporan_bulanan->limbah_b3_covid = $form_limbah_b3_covid; // string
        $laporan_bulanan->limbah_b3_noncovid = $form_limbah_b3_noncovid; // string
        $laporan_bulanan->limbah_cair_b3 = $form_debit_limbah_cair; // string - menyimpan data limbah cair B3
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
        $laporan_bulanan->link_input_manifest = $form_link_input_manifest;
        $laporan_bulanan->link_input_logbook = $form_link_input_logbook;
        $laporan_bulanan->link_input_lab_ipal = $form_link_input_lab_ipal;
        $laporan_bulanan->link_input_lab_lain = $form_link_input_lab_lain;
        $laporan_bulanan->link_input_dokumen_lingkungan_rs = $form_link_input_dokumen_lingkungan_rs;
        $laporan_bulanan->link_input_swa_pantau = $form_link_input_swa_pantau;
        $laporan_bulanan->link_input_ujilab_cair = $form_link_input_ujilab_cair;

        $laporan_bulanan->limbah_b3_nonmedis = $form_limbah_b3_nonmedis;
        $laporan_bulanan->limbah_b3_medis = $form_limbah_b3_medis;
        $laporan_bulanan->limbah_jarum = $form_limbah_jarum;
        $laporan_bulanan->limbah_sludge_ipal = $form_limbah_sludge_ipal;
        $laporan_bulanan->limbah_padat_infeksius = $form_limbah_padat_infeksius;
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
        // foreach ($form_file_manifest as $key => $v) {
        //     $norut = $key + 1;
        //     $form_file = 'FILE_MANIFEST_' . $laporan_bulanan->id_laporan_bulanan  . '_' . $form_id_user  . '_' . $norut . '_.' . $v->extension();
        //     $form_dir_file = $dir_file_manifest . $form_file;

        //     $laporan_bulanan_file = new MLaporanBulananFile();
        //     $laporan_bulanan_file->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
        //     $laporan_bulanan_file->id_user = $form_id_user;
        //     $laporan_bulanan_file->norut = $norut;
        //     $laporan_bulanan_file->tipe_file = 'manifest';
        //     $laporan_bulanan_file->file1 = $form_dir_file;
        //     // $laporan_bulanan_file->file2 = 0;
        //     // $laporan_bulanan_file->file3 = 0;
        //     $laporan_bulanan_file->status_laporan_bulanan_file = 1;
        //     $laporan_bulanan_file->statusactive_laporan_bulanan_file = 1;
        //     $laporan_bulanan_file->user_created = $form_username;
        //     // $laporan_bulanan_file->user_updated = 0;

        //     $v->move($dir_file_manifest_move, $form_file);
        //     $laporan_bulanan_file->save();
        // }

        // //-- LOGBOOK
        // foreach ($form_file_logbook as $key => $v) {
        //     $norut = $key + 1;
        //     $form_file = 'FILE_LOGBOOK_' . $laporan_bulanan->id_laporan_bulanan  . '_' . $form_id_user  . '_' . $norut . '_.' . $v->extension();
        //     $form_dir_file = $dir_file_logbook . $form_file;

        //     $laporan_bulanan_file = new MLaporanBulananFile();
        //     $laporan_bulanan_file->id_laporan_bulanan = $laporan_bulanan->id_laporan_bulanan;
        //     $laporan_bulanan_file->id_user = $form_id_user;
        //     $laporan_bulanan_file->norut = $norut;
        //     $laporan_bulanan_file->tipe_file = 'logbook';
        //     $laporan_bulanan_file->file1 = $form_dir_file;
        //     // $laporan_bulanan_file->file2 = 0;
        //     // $laporan_bulanan_file->file3 = 0;
        //     $laporan_bulanan_file->status_laporan_bulanan_file = 1;
        //     $laporan_bulanan_file->statusactive_laporan_bulanan_file = 1;
        //     $laporan_bulanan_file->user_created = $form_username;
        //     // $laporan_bulanan_file->user_updated = 0;

        //     $v->move($dir_file_logbook_move, $form_file);
        //     $laporan_bulanan_file->save();
        // }

        $resp =
            MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage("Sukses Update Laporan Bulan '" . $form_periode_nama . " " . $form_tahun . "' .!")
            ->build();
        return $resp;
    }

    public function laporanProsesDelete(Request $request)
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
        $tableuser = MLaporanBulanan::where(['id_laporan_bulanan' => $form_oldid, 'statusactive_laporan_bulanan' => 1])->latest()->first();
        if ($tableuser == null) {
            return
                MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data Referensi Tidak Ditemukan.!')
                ->withData(null)
                ->build();
        }
        $tableuser->statusactive_laporan_bulanan = 0;
        $tableuser->save();

        return
            MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withMessage('Sukses Melakukan Delete.!')
            ->withData(null)
            ->build();
    }
}
