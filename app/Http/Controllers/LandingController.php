<?php

namespace App\Http\Controllers;

use App\Models\MKecamatan;
use App\Models\MKelurahan;
use App\Models\MLaporanBulanan;
use App\Models\MLaporanBulananB3Padat;
use App\Models\MLaporanLab;
use App\Models\MLimbahCair;
use App\Models\MTransporter;
use App\Models\MUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\MyResponseBuilder as MyRB;
use App\MyUtils as MyUtils;
use Carbon\Carbon;
use Exception;

class LandingController extends Controller
{
    //
    public function __construct()
    {
        // $this->r = [
        //     'success' => false,
        //     'code' => 401,
        //     'message' => 'Upps..',
        //     'data' => null
        // ]
    }

    function testApi(Request $request)
    {
        // $this->r;
        // return $resp;
        // return $this->r->resp(200);
        // $token = $request->headers->get('authorization');
        // if ($token == null) {
        //     $token = $request->headers->get('Authorization');
        // }
        // $user = JWTAuth::setToken($token)->getPayload();
        $user = MyUtils::getPayloadToken($request, true) ?? '';
        dd($user);
        return 'a';
    }

    function prosesLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return
                MyRB::asError(400)
                ->withMessage('Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        $form_username = $request->username;
        $form_password = $request->password;

        $token = auth()->guard('webnext')->attempt(['username' => $form_username, 'password' => $form_password, 'statusactive_user' => 1]);
        if (!$token) {
            return
                MyRB::asError(401)
                ->withMessage('Login Gagal, Username atau Password Salah.!')
                ->withData(null)
                ->build();
        }

        $user = auth()->guard('webnext')->user();
        return
            MyRB::asSuccess(200)
            ->withMessage('Sukses Login.!')
            ->withData(['user' => $user, 'token' => $token])
            ->build();
    }

    function kecamatanProsesData(Request $request)
    {
        $kecamatan = MKecamatan::all();
        $dataKecamatan = $kecamatan->values()->toArray();
        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData($dataKecamatan)
            ->build();
    }
    function kelurahanProsesData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_kecamatan' => 'required',
        ]);

        if ($validator->fails()) {
            return
                MyRB::asError(400)
                ->withMessage('Uppss.. Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        $form_id_kecamatan = $request->id_kecamatan;
        if ($form_id_kecamatan == null) {
        }
        $kelurahan = MKelurahan::where('id_kecamatan', $form_id_kecamatan)->get();
        $dataKelurahan = $kelurahan->values()->toArray();
        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData($dataKelurahan)
            ->build();
    }

    function dasboardUserProsesData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? '';

        // -- form input -- \\
        $form_tahun = (($request->tahun == null) ? null : intval($request->tahun)) ?? intval(date('Y'));
        $form_periode = (($request->periode == null) ? null : intval($request->periode)) ?? intval(date('m'));
        $form_jenis_limbah = $request->jenis_limbah ?? 'bulanan'; // 'padat', 'cair', or 'bulanan' (default)

        $tahun = ($form_tahun == 0 || $form_tahun == '0') ? intval(date('Y')) : $form_tahun;
        $periode = ($form_periode == 0 || $form_periode == '0') ? intval(date('m')) : $form_periode;
        $periode_nama = Carbon::create()->day(1)->month($periode)->format('F');
        $laporan = [
            'laporan_periode' => $periode,
            'laporan_periode_nama' => $periode_nama,
            'laporan_periode_tahun' => $tahun,
            'sudah_lapor' => false,
            'total_limbah_chart_year' => [],
            'bulan_nama' => []
        ];
        $laporan_bulanan = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where('id_user', $form_id_user);
        $laporan_bulanan_periode_now = $laporan_bulanan->where(['periode' => $periode, 'tahun' => $tahun])->get();
        if (count($laporan_bulanan_periode_now) > 0) {
            $laporan['sudah_lapor'] = true;
        }

        $total_limbah_chart_year = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where('id_user', $form_id_user)->where('tahun', $tahun)->get();

        $arr_berat_limbah_total = [];
        $arr_limbah_padat_infeksius = [];
        $arr_limbah_b3_covid = [];
        $arr_limbah_b3_nonmedis = [];
        $arr_limbah_jarum = [];
        $arr_limbah_sludge_ipal = [];
        $arr_debit_limbah_cair = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulan_nama = Carbon::create()->day(1)->month($i)->format('F');
            $total_limbah = $total_limbah_chart_year->where('periode', $i)->first();
            $total = 0;

            // {
            //     name: 'PRODUCT A',
            //     data: [44, 55, 41, 67, 22, 43]
            //   }
            $val_berat_limbah_total = 0;
            $val_limbah_padat_infeksius = 0;
            $val_limbah_b3_covid = 0;
            $val_limbah_b3_nonmedis = 0;
            $val_limbah_jarum = 0;
            $val_limbah_sludge_ipal = 0;
            $val_debit_limbah_cair = 0;
            if ($total_limbah) {
                try {
                    $val_berat_limbah_total = floatval($total_limbah->berat_limbah_total ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $val_limbah_padat_infeksius = floatval($total_limbah->limbah_padat_infeksius ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $val_limbah_b3_covid = floatval($total_limbah->limbah_b3_covid ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $val_limbah_b3_nonmedis = floatval($total_limbah->limbah_b3_nonmedis ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $val_limbah_jarum = floatval($total_limbah->limbah_jarum ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $val_limbah_sludge_ipal = floatval($total_limbah->limbah_sludge_ipal ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $val_debit_limbah_cair = floatval($total_limbah->debit_limbah_cair ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
            }
            array_push($arr_berat_limbah_total, $val_berat_limbah_total);
            array_push($arr_limbah_padat_infeksius, $val_limbah_padat_infeksius);
            array_push($arr_limbah_b3_covid, $val_limbah_b3_covid);
            array_push($arr_limbah_b3_nonmedis, $val_limbah_b3_nonmedis);
            array_push($arr_limbah_jarum, $val_limbah_jarum);
            array_push($arr_limbah_sludge_ipal, $val_limbah_sludge_ipal);
            array_push($arr_debit_limbah_cair, $val_debit_limbah_cair);

            // array_push($laporan['total_limbah_chart_year'], round($total, 2));
            array_push($laporan['bulan_nama'], $bulan_nama);
        }
        $arr_jsn = [
            ['name' => 'Total Limbah Padat Infeksius', 'data' => $arr_limbah_padat_infeksius],
            ['name' => 'Total Limbah Covid', 'data' => $arr_limbah_b3_covid],
            ['name' => 'Total Limbat Padat Non Infeksius', 'data' => $arr_limbah_b3_nonmedis],
            ['name' => 'Total Limbah Jarum', 'data' => $arr_limbah_jarum],
            ['name' => 'Total Limbah Sludge IPAL', 'data' => $arr_limbah_sludge_ipal],
            ['name' => 'Total Limbah Cair', 'data' => $arr_debit_limbah_cair],
        ];
        $laporan['total_limbah_chart_year'] = $arr_jsn;
        // dd($laporan);

        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData(['values' => $laporan])
            ->build();
    }
    function dasboardAdminProsesData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? '';

        // -- form input -- \\
        $form_tahun = (($request->tahun == null) ? null : intval($request->tahun)) ?? intval(date('Y'));
        $form_periode = (($request->periode == null) ? null : intval($request->periode)) ?? intval(date('m'));

        $tahun = ($form_tahun == 0 || $form_tahun == '0') ? intval(date('Y')) : $form_tahun;
        $periode = ($form_periode == 0 || $form_periode == '0') ? intval(date('m')) : $form_periode;
        $periode_nama = Carbon::create()->day(1)->month($periode)->format('F');
        // dd($periode);
        // dd($tahun);

        $laporan = [
            'laporan_periode' => $periode,
            'laporan_periode_nama' => $periode_nama,
            'laporan_periode_tahun' => $tahun,
        ];
        // Determine which table to use based on jenis_limbah
        if ($form_jenis_limbah == 'padat') {
            // Use laporan bulanan table for limbah padat (simpler approach)
            $laporan_query = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0);
            $laporan_periode_now = $laporan_query->where(['periode' => $periode, 'tahun' => $tahun]);
        } elseif ($form_jenis_limbah == 'cair') {
            $laporan_query = MLimbahCair::where('statusactive_limbah_cair', 1);
            $laporan_periode_now = $laporan_query->where(['periode' => $periode, 'tahun' => $tahun]);
        } else {
            // Default to laporan bulanan for backward compatibility
            $laporan_query = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0);
            $laporan_periode_now = $laporan_query->where(['periode' => $periode, 'tahun' => $tahun]);
        }
        
        $total_laporan_perperiode = $laporan_periode_now->count();
        $user_laporan = $laporan_periode_now->pluck('id_user');
        $user_puskesmas_rs = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->whereNotIn('id_user', $user_laporan)->get();
        $total_puskesmas_rs_belum_lapor = $user_puskesmas_rs->count();
        $total_puskesmas_rs_sudah_lapor = $laporan_periode_now->get()->count();

        $tmp_user_rs = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->pluck('id_user');
        $tmp_user_puskesmas = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->pluck('id_user');

        $user_rs_sudah_lapor = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $periode, 'tahun' => $tahun])->whereIn('id_user', $tmp_user_rs);
        $user_rs_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->whereNotIn('id_user', $user_rs_sudah_lapor->pluck('id_user'))->get()->count();
        $user_rs_sudah_lapor = $user_rs_sudah_lapor->get()->count();

        $user_puskesmas_sudah_lapor = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $periode, 'tahun' => $tahun])->whereIn('id_user', $tmp_user_puskesmas);
        $user_puskesmas_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->whereNotIn('id_user', $user_puskesmas_sudah_lapor->pluck('id_user'))->get()->count();
        $user_puskesmas_sudah_lapor = $user_puskesmas_sudah_lapor->get()->count();

        $total_transporter = MTransporter::where('statusactive_transporter', 1)->get()->count();
        $total_puskesmas_rs = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->get()->count();
        $total_rs = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->get()->count();
        $total_puskesmas = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->get()->count();

        $tmp_user = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->get();
        foreach ($tmp_user as $data => $value) {
            if ($form_jenis_limbah == 'padat') {
                // Get all reported months for this user (limbah padat)
                $reported_months = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)
                    ->where('id_user', $value->id_user)
                    ->where('tahun', $tahun)
                    ->pluck('periode')->toArray();
                
                // Find missing months (1-12)
                $all_months = range(1, 12);
                $missing_months = array_diff($all_months, $reported_months);
                
                $value->sudah_lapor_limbah_padat = (count($missing_months) == 0) ? true : false;
                $value->missing_months_padat = array_values($missing_months);
                $value->reported_months_padat = $reported_months;
                $value->sudah_lapor = $value->sudah_lapor_limbah_padat; // For backward compatibility
                
            } elseif ($form_jenis_limbah == 'cair') {
                // Get all reported months for this user (limbah cair)
                $reported_months = MLimbahCair::where('statusactive_limbah_cair', 1)
                    ->where('id_user', $value->id_user)
                    ->where('tahun', $tahun)
                    ->pluck('periode')->toArray();
                
                // Find missing months (1-12)
                $all_months = range(1, 12);
                $missing_months = array_diff($all_months, $reported_months);
                
                $value->sudah_lapor_limbah_cair = (count($missing_months) == 0) ? true : false;
                $value->missing_months_cair = array_values($missing_months);
                $value->reported_months_cair = $reported_months;
                $value->sudah_lapor = $value->sudah_lapor_limbah_cair; // For backward compatibility
                
            } else {
                // Default behavior for backward compatibility
                $cek_laporan = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where('id_user', $value->id_user)->where(['periode' => $periode, 'tahun' => $tahun])->latest()->first();
                $value->sudah_lapor = ($cek_laporan == null) ? false : true;
            }
        }

        $total_chart_puskesmas_rs = [];
        $total_chart_puskesmas_rs_belum_lapor = [];
        $total_chart_puskesmas_rs_sudah_lapor = [];

        $total_chart_rs = [];
        $total_chart_rs_sudah_lapor = [];
        $total_chart_rs_belum_lapor = [];

        $total_chart_puskesmas = [];
        $total_chart_puskesmas_sudah_lapor = [];
        $total_chart_puskesmas_belum_lapor = [];

        $total_chart_pie_rs = [$total_rs, $user_rs_belum_lapor, $user_rs_sudah_lapor];
        $total_chart_pie_puskesmas = [$total_puskesmas, $user_puskesmas_belum_lapor, $user_puskesmas_sudah_lapor];
        $total_chart_pie_label_rs = ['Total Rumah Sakit', 'Total Rumah Sakit Belum Lapor', 'Total Rumah Sakit Sudah Lapor'];
        $total_chart_pie_label_puskesmas = ['Total Puskesmas', 'Total Puskesmas Belum Lapor', 'Total Puskesmas Sudah Lapor'];


        for ($i = 1; $i <= 12; $i++) {
            // Use appropriate table based on jenis_limbah
            if ($form_jenis_limbah == 'padat') {
                // Use laporan bulanan for limbah padat
                $tmp_user_laporan = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $i, 'tahun' => $tahun])->pluck('id_user');
                $user_puskesmas_rs_sudah_lapor = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $i, 'tahun' => $tahun])->get()->count();
            } elseif ($form_jenis_limbah == 'cair') {
                $tmp_user_laporan = MLimbahCair::where('statusactive_limbah_cair', 1)->where(['periode' => $i, 'tahun' => $tahun])->pluck('id_user');
                $user_puskesmas_rs_sudah_lapor = MLimbahCair::where('statusactive_limbah_cair', 1)->where(['periode' => $i, 'tahun' => $tahun])->get()->count();
            } else {
                $tmp_user_laporan = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $i, 'tahun' => $tahun])->pluck('id_user');
                $user_puskesmas_rs_sudah_lapor = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $i, 'tahun' => $tahun])->get()->count();
            }
            
            $user_puskesmas_rs_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->whereNotIn('id_user', $tmp_user_laporan)->get()->count();

            $tmp_user_rs = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->pluck('id_user');
            $tmp_user_puskesmas = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->pluck('id_user');

            $user_rs_sudah_lapor = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $i, 'tahun' => $tahun])->whereIn('id_user', $tmp_user_rs);
            $user_rs_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->whereNotIn('id_user', $user_rs_sudah_lapor->pluck('id_user'))->get()->count();
            $user_rs_sudah_lapor = $user_rs_sudah_lapor->get()->count();

            $user_puskesmas_sudah_lapor = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $i, 'tahun' => $tahun])->whereIn('id_user', $tmp_user_puskesmas);
            $user_puskesmas_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->whereNotIn('id_user', $user_puskesmas_sudah_lapor->pluck('id_user'))->get()->count();
            $user_puskesmas_sudah_lapor = $user_puskesmas_sudah_lapor->get()->count();

            array_push($total_chart_puskesmas_rs, $tmp_user->count());
            array_push($total_chart_puskesmas_rs_belum_lapor, $user_puskesmas_rs_belum_lapor);
            array_push($total_chart_puskesmas_rs_sudah_lapor, $user_puskesmas_rs_sudah_lapor);

            array_push($total_chart_rs, $tmp_user_rs->count());
            array_push($total_chart_rs_sudah_lapor, $user_rs_sudah_lapor);
            array_push($total_chart_rs_belum_lapor, $user_rs_belum_lapor);

            array_push($total_chart_puskesmas, $tmp_user_puskesmas->count());
            array_push($total_chart_puskesmas_sudah_lapor, $user_puskesmas_sudah_lapor);
            array_push($total_chart_puskesmas_belum_lapor, $user_puskesmas_belum_lapor);
        }



        $laporan['total_laporan_perperiode'] = $total_laporan_perperiode;
        $laporan['total_puskesmas_rs_belum_lapor'] = $total_puskesmas_rs_belum_lapor;
        $laporan['total_puskesmas_rs_sudah_lapor'] = $total_puskesmas_rs_sudah_lapor;
        $laporan['total_transporter'] = $total_transporter;
        $laporan['total_puskesmas_rs'] = $total_puskesmas_rs;
        $laporan['total_rs'] = $total_rs;
        $laporan['total_puskesmas'] = $total_puskesmas;
        $laporan['total_chart_puskesmas_rs'] = $total_chart_puskesmas_rs;
        $laporan['total_chart_puskesmas_rs_belum_lapor'] = $total_chart_puskesmas_rs_belum_lapor;
        $laporan['total_chart_puskesmas_rs_sudah_lapor'] = $total_chart_puskesmas_rs_sudah_lapor;

        $laporan['total_chart_rs'] = $total_chart_rs;
        $laporan['total_chart_rs_sudah_lapor'] = $total_chart_rs_sudah_lapor;
        $laporan['total_chart_rs_belum_lapor'] = $total_chart_rs_belum_lapor;

        $laporan['total_chart_puskesmas'] = $total_chart_puskesmas;
        $laporan['total_chart_puskesmas_sudah_lapor'] = $total_chart_puskesmas_sudah_lapor;
        $laporan['total_chart_puskesmas_belum_lapor'] = $total_chart_puskesmas_belum_lapor;

        $laporan['total_chart_pie_rs'] = $total_chart_pie_rs;
        $laporan['total_chart_pie_puskesmas'] = $total_chart_pie_puskesmas;
        $laporan['total_chart_pie_label_rs'] = $total_chart_pie_label_rs;
        $laporan['total_chart_pie_label_puskesmas'] = $total_chart_pie_label_puskesmas;


        $laporan['notif_user_laporan_bulanan'] = $tmp_user;


        return MyRB::asSuccess(200)
            ->withMessage('Success get data.!')
            ->withData(['values' => $laporan])
            ->build();


        // dd($user_puskesmas_rs->count());
        // dd($laporan_periode_now->get());
        // dd($user_puskesmas_rs);
        // $belum_lapor = $laporan_periode_now->whereNotIn('id_user', $user_puskesmas_rs)->get();
        // $total = $laporan_bulanan->where('')
        // dd($laporan_periode_now->get());
        // dd($user_puskesmas_rs);
        // dd($belum_lapor);
    }

    function dashboardUserLabData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->nama_user ?? '';
        $form_uid = $user->uid ?? '';

        // -- form input -- \\
        $form_tahun = (($request->tahun == null) ? null : intval($request->tahun)) ?? intval(date('Y'));
        $form_periode = (($request->periode == null) ? null : intval($request->periode)) ?? intval(date('m'));

        $tahun = ($form_tahun == 0 || $form_tahun == '0') ? intval(date('Y')) : $form_tahun;
        $periode = ($form_periode == 0 || $form_periode == '0') ? intval(date('m')) : $form_periode;
        $periode_nama = Carbon::create()->day(1)->month($periode)->format('F');
        
        $laporan = [
            'laporan_periode' => $periode,
            'laporan_periode_nama' => $periode_nama,
            'laporan_periode_tahun' => $tahun,
            'sudah_lapor' => false,
            'total_lab_chart_year' => [],
            'bulan_nama' => [],
            'bulan_sudah_lapor' => [] // Array untuk menandai bulan mana saja yang sudah ada laporan
        ];

        // Check if user has reported for current period
        $laporan_lab = MLaporanLab::where('statusactive_laporan_lab', '<>', 0)->where('id_user', $form_id_user);
        $laporan_lab_periode_now = $laporan_lab->where(['periode' => $periode, 'tahun' => $tahun])->get();
        if (count($laporan_lab_periode_now) > 0) {
            $laporan['sudah_lapor'] = true;
        }

        // Get all lab reports for the year to build chart data
        $total_lab_chart_year = MLaporanLab::where('statusactive_laporan_lab', '<>', 0)
            ->where('id_user', $form_id_user)
            ->where('tahun', $tahun)
            ->get();

        $arr_total_pemeriksaan = [];
        $arr_bulan_sudah_lapor = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $bulan_nama = Carbon::create()->day(1)->month($i)->format('F');
            $laporan_bulan = $total_lab_chart_year->where('periode', $i);
            
            $val_total_pemeriksaan = 0;
            $sudah_lapor_bulan = false;
            
            if ($laporan_bulan->count() > 0) {
                $sudah_lapor_bulan = true;
                // Sum all examinations for this month
                foreach ($laporan_bulan as $lab_report) {
                    try {
                        $val_total_pemeriksaan += intval($lab_report->total_pemeriksaan ?? 0);
                    } catch (Exception $ex) {
                        // Handle exception if needed
                    }
                }
            }
            
            array_push($arr_total_pemeriksaan, $val_total_pemeriksaan);
            array_push($arr_bulan_sudah_lapor, $sudah_lapor_bulan);
            array_push($laporan['bulan_nama'], $bulan_nama);
        }

        // Format data for ApexCharts
        $arr_jsn = [
            ['name' => 'Total Pemeriksaan Lab', 'data' => $arr_total_pemeriksaan]
        ];
        
        $laporan['total_lab_chart_year'] = $arr_jsn;
        $laporan['bulan_sudah_lapor'] = $arr_bulan_sudah_lapor;

        return MyRB::asSuccess(200)
            ->withMessage('Success get lab dashboard data!')
            ->withData(['values' => $laporan])
            ->build();
    }

    function dashboardAdminLaporanLabData(Request $request)
    {
        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';
        $form_username = $user->username ?? '';
        $form_nama_user = $user->nama_user ?? '';
        $form_uid = $user->uid ?? '';

        // -- form input -- \\
        $form_tahun = (($request->tahun == null) ? null : intval($request->tahun)) ?? intval(date('Y'));

        $tahun = ($form_tahun == 0 || $form_tahun == '0') ? intval(date('Y')) : $form_tahun;
        
        $laporan = [
            'laporan_periode_tahun' => $tahun,
        ];

        // Get all lab reports for the year
        $laporan_lab_query = MLaporanLab::where('statusactive_laporan_lab', '<>', 0)->where('tahun', $tahun);
        $total_laporan_perperiode = $laporan_lab_query->count();
        
        // Get users who have reported
        $user_laporan = $laporan_lab_query->pluck('id_user')->unique();
        
        // Get all users (excluding admin level 1)
        $user_puskesmas_rs = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->get();
        $total_puskesmas_rs = $user_puskesmas_rs->count();
        
        // Calculate who has and hasn't reported
        $user_sudah_lapor = $user_puskesmas_rs->whereIn('id_user', $user_laporan);
        $user_belum_lapor = $user_puskesmas_rs->whereNotIn('id_user', $user_laporan);
        
        $total_puskesmas_rs_sudah_lapor = $user_sudah_lapor->count();
        $total_puskesmas_rs_belum_lapor = $user_belum_lapor->count();

        // Separate by user level
        $tmp_user_rs = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->pluck('id_user');
        $tmp_user_puskesmas = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->pluck('id_user');

        $user_rs_sudah_lapor = $user_sudah_lapor->whereIn('id_user', $tmp_user_rs)->count();
        $user_rs_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->whereNotIn('id_user', $user_laporan)->count();

        $user_puskesmas_sudah_lapor = $user_sudah_lapor->whereIn('id_user', $tmp_user_puskesmas)->count();
        $user_puskesmas_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->whereNotIn('id_user', $user_laporan)->count();

        $total_rs = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->count();
        $total_puskesmas = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->count();

        // Add reporting status to ALL users (both reported and not reported)
        $tmp_all_users = [];
        foreach ($user_puskesmas_rs as $value) {
            // Get months that have been reported for this user
            $reported_months = MLaporanLab::where('statusactive_laporan_lab', '<>', 0)
                ->where('id_user', $value->id_user)
                ->where('tahun', $tahun)
                ->pluck('periode')
                ->toArray();
            
            $all_months = range(1, 12);
            $unreported_months = array_diff($all_months, $reported_months);
            
            // Set reporting status
            $value->sudah_lapor = in_array($value->id_user, $user_laporan->toArray());
            $value->reported_months_lab = $reported_months;
            $value->missing_months_lab = array_values($unreported_months);
            
            // Add month names for display
            $reported_month_names = [];
            foreach ($reported_months as $month) {
                $reported_month_names[] = Carbon::create()->day(1)->month($month)->format('F');
            }
            $value->bulan_sudah_lapor = implode(', ', $reported_month_names);
            
            $unreported_month_names = [];
            foreach ($unreported_months as $month) {
                $unreported_month_names[] = Carbon::create()->day(1)->month($month)->format('F');
            }
            $value->bulan_belum_lapor = implode(', ', $unreported_month_names);
            
            // Add user type based on level
            $value->tipe_tempat = ($value->level == '2') ? 'Rumah Sakit' : 'Puskesmas';
            $value->nama_lab = $value->nama_user;
            $value->jenis_lab = $value->tipe_tempat;
            
            $tmp_all_users[] = $value;
        }

        // Chart data for 12 months
        $total_chart_puskesmas_rs = [];
        $total_chart_puskesmas_rs_belum_lapor = [];
        $total_chart_puskesmas_rs_sudah_lapor = [];
        $total_chart_rs = [];
        $total_chart_rs_sudah_lapor = [];
        $total_chart_rs_belum_lapor = [];
        $total_chart_puskesmas = [];
        $total_chart_puskesmas_sudah_lapor = [];
        $total_chart_puskesmas_belum_lapor = [];

        for ($i = 1; $i <= 12; $i++) {
            $tmp_user_laporan = MLaporanLab::where('statusactive_laporan_lab', '<>', 0)
                ->where(['periode' => $i, 'tahun' => $tahun])
                ->pluck('id_user')
                ->unique();
            
            $user_puskesmas_rs_sudah_lapor_bulan = $tmp_user_laporan->count();
            $user_puskesmas_rs_belum_lapor_bulan = $total_puskesmas_rs - $user_puskesmas_rs_sudah_lapor_bulan;

            $user_rs_sudah_lapor_bulan = MLaporanLab::where('statusactive_laporan_lab', '<>', 0)
                ->where(['periode' => $i, 'tahun' => $tahun])
                ->whereIn('id_user', $tmp_user_rs)
                ->pluck('id_user')
                ->unique()
                ->count();
            $user_rs_belum_lapor_bulan = $total_rs - $user_rs_sudah_lapor_bulan;

            $user_puskesmas_sudah_lapor_bulan = MLaporanLab::where('statusactive_laporan_lab', '<>', 0)
                ->where(['periode' => $i, 'tahun' => $tahun])
                ->whereIn('id_user', $tmp_user_puskesmas)
                ->pluck('id_user')
                ->unique()
                ->count();
            $user_puskesmas_belum_lapor_bulan = $total_puskesmas - $user_puskesmas_sudah_lapor_bulan;

            array_push($total_chart_puskesmas_rs, $total_puskesmas_rs);
            array_push($total_chart_puskesmas_rs_belum_lapor, $user_puskesmas_rs_belum_lapor_bulan);
            array_push($total_chart_puskesmas_rs_sudah_lapor, $user_puskesmas_rs_sudah_lapor_bulan);

            array_push($total_chart_rs, $total_rs);
            array_push($total_chart_rs_sudah_lapor, $user_rs_sudah_lapor_bulan);
            array_push($total_chart_rs_belum_lapor, $user_rs_belum_lapor_bulan);

            array_push($total_chart_puskesmas, $total_puskesmas);
            array_push($total_chart_puskesmas_sudah_lapor, $user_puskesmas_sudah_lapor_bulan);
            array_push($total_chart_puskesmas_belum_lapor, $user_puskesmas_belum_lapor_bulan);
        }

        // Pie chart data
        $total_chart_pie_rs = [$total_rs, $user_rs_belum_lapor, $user_rs_sudah_lapor];
        $total_chart_pie_puskesmas = [$total_puskesmas, $user_puskesmas_belum_lapor, $user_puskesmas_sudah_lapor];
        $total_chart_pie_label_rs = ['Total Rumah Sakit', 'Total Rumah Sakit Belum Lapor', 'Total Rumah Sakit Sudah Lapor'];
        $total_chart_pie_label_puskesmas = ['Total Puskesmas', 'Total Puskesmas Belum Lapor', 'Total Puskesmas Sudah Lapor'];

        // Build response data
        $laporan['total_laporan_perperiode'] = $total_laporan_perperiode;
        $laporan['total_puskesmas_rs_belum_lapor'] = $total_puskesmas_rs_belum_lapor;
        $laporan['total_puskesmas_rs_sudah_lapor'] = $total_puskesmas_rs_sudah_lapor;
        $laporan['total_puskesmas_rs'] = $total_puskesmas_rs;
        $laporan['total_rs'] = $total_rs;
        $laporan['total_puskesmas'] = $total_puskesmas;
        $laporan['total_chart_puskesmas_rs'] = $total_chart_puskesmas_rs;
        $laporan['total_chart_puskesmas_rs_belum_lapor'] = $total_chart_puskesmas_rs_belum_lapor;
        $laporan['total_chart_puskesmas_rs_sudah_lapor'] = $total_chart_puskesmas_rs_sudah_lapor;
        $laporan['total_chart_rs'] = $total_chart_rs;
        $laporan['total_chart_rs_sudah_lapor'] = $total_chart_rs_sudah_lapor;
        $laporan['total_chart_rs_belum_lapor'] = $total_chart_rs_belum_lapor;
        $laporan['total_chart_puskesmas'] = $total_chart_puskesmas;
        $laporan['total_chart_puskesmas_sudah_lapor'] = $total_chart_puskesmas_sudah_lapor;
        $laporan['total_chart_puskesmas_belum_lapor'] = $total_chart_puskesmas_belum_lapor;
        $laporan['total_chart_pie_rs'] = $total_chart_pie_rs;
        $laporan['total_chart_pie_puskesmas'] = $total_chart_pie_puskesmas;
        $laporan['total_chart_pie_label_rs'] = $total_chart_pie_label_rs;
        $laporan['total_chart_pie_label_puskesmas'] = $total_chart_pie_label_puskesmas;
        $laporan['notif_user_laporan_bulanan'] = $tmp_all_users;

        return MyRB::asSuccess(200)
            ->withMessage('Success get laporan lab dashboard data!')
            ->withData(['values' => $laporan])
            ->build();
    }
}
