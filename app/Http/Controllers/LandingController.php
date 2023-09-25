<?php

namespace App\Http\Controllers;

use App\Models\MKecamatan;
use App\Models\MKelurahan;
use App\Models\MLaporanBulanan;
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
        for ($i = 1; $i <= 12; $i++) {
            $bulan_nama = Carbon::create()->day(1)->month($i)->format('F');
            $total_limbah = $total_limbah_chart_year->where('periode', $i)->first();
            $total = 0;
            if ($total_limbah) {
                // $total = intval($total_limbah->berat_limbah_total);
                try {
                    $total += floatval($total_limbah->berat_limbah_total ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $total += floatval($total_limbah->limbah_b3_noncovid ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $total += floatval($total_limbah->limbah_b3_covid ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $total += floatval($total_limbah->limbah_b3_nonmedis ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $total += floatval($total_limbah->limbah_b3_medis ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $total += floatval($total_limbah->limbah_jarum ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $total += floatval($total_limbah->limbah_sludge_ipal ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
                try {
                    $total += floatval($total_limbah->debit_limbah_cair ?? '0') ?? 0;
                } catch (Exception $ex) {
                }
            }
            array_push($laporan['total_limbah_chart_year'], round($total, 2));
            array_push($laporan['bulan_nama'], $bulan_nama);
        }

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
        $laporan_bulanan = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0);
        $laporan_periode_now = $laporan_bulanan->where(['periode' => $periode, 'tahun' => $tahun]);
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
            $cek_laporan = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where('id_user', $value->id_user)->where(['periode' => $periode, 'tahun' => $tahun])->latest()->first();
            $value->sudah_lapor = ($cek_laporan == null) ? false : true;
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
            $tmp_user_laporan_bulanan = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $i, 'tahun' => $tahun])->pluck('id_user');
            $user_puskesmas_rs_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->whereNotIn('id_user', $tmp_user_laporan_bulanan)->get()->count();
            $user_puskesmas_rs_sudah_lapor = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)->where(['periode' => $i, 'tahun' => $tahun])->get()->count();

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
}
