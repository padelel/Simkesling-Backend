<?php

namespace App\Http\Controllers;

use App\Models\MKecamatan;
use App\Models\MKelurahan;
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

class DashboardLimbahCairController extends Controller
{
    function dashboardLimbahCairData(Request $request)
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
        ];

        // Check if current user has reported for this period
        $current_user_report = MLimbahCair::where('statusactive_limbah_cair', 1)
            ->where('id_user', $form_id_user)
            ->where('periode', $periode)
            ->where('tahun', $tahun)
            ->first();
        
        $laporan['sudah_lapor'] = $current_user_report ? true : false;
        
        // Use limbah cair table
        $laporan_query = MLimbahCair::where('statusactive_limbah_cair', 1);
        $laporan_periode_now = $laporan_query->where(['periode' => $periode, 'tahun' => $tahun]);
        $total_laporan_perperiode = $laporan_periode_now->count();

        $user_laporan = $laporan_periode_now->pluck('id_user');
        $user_puskesmas_rs = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->whereNotIn('id_user', $user_laporan)->get();
        $total_puskesmas_rs_belum_lapor = $user_puskesmas_rs->count();
        $total_puskesmas_rs_sudah_lapor = $laporan_periode_now->get()->count();

        $tmp_user_rs = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->pluck('id_user');
        $tmp_user_puskesmas = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->pluck('id_user');

        $user_rs_sudah_lapor = MLimbahCair::where('statusactive_limbah_cair', 1)->where(['periode' => $periode, 'tahun' => $tahun])->whereIn('id_user', $tmp_user_rs);
        $user_rs_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->whereNotIn('id_user', $user_rs_sudah_lapor->pluck('id_user'))->get()->count();
        $user_rs_sudah_lapor = $user_rs_sudah_lapor->get()->count();

        $user_puskesmas_sudah_lapor = MLimbahCair::where('statusactive_limbah_cair', 1)->where(['periode' => $periode, 'tahun' => $tahun])->whereIn('id_user', $tmp_user_puskesmas);
        $user_puskesmas_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->whereNotIn('id_user', $user_puskesmas_sudah_lapor->pluck('id_user'))->get()->count();
        $user_puskesmas_sudah_lapor = $user_puskesmas_sudah_lapor->get()->count();

        $total_transporter = MTransporter::where('statusactive_transporter', 1)->get()->count();
        $total_puskesmas_rs = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->get()->count();
        $total_rs = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->get()->count();
        $total_puskesmas = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->get()->count();

        // Process user notifications with detailed month tracking
        $tmp_user = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->get();
        foreach ($tmp_user as $data => $value) {
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
        }

        // Generate chart data for 12 months
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
            // Use limbah cair table
            $tmp_user_laporan = MLimbahCair::where('statusactive_limbah_cair', 1)->where(['periode' => $i, 'tahun' => $tahun])->pluck('id_user');
            $user_puskesmas_rs_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '<>', '1')->whereNotIn('id_user', $tmp_user_laporan)->get()->count();
            $user_puskesmas_rs_sudah_lapor = MLimbahCair::where('statusactive_limbah_cair', 1)->where(['periode' => $i, 'tahun' => $tahun])->get()->count();

            $tmp_user_rs = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->pluck('id_user');
            $tmp_user_puskesmas = MUser::where(['statusactive_user' => 1])->where('level', '=', '3')->pluck('id_user');

            $user_rs_sudah_lapor = MLimbahCair::where('statusactive_limbah_cair', 1)->where(['periode' => $i, 'tahun' => $tahun])->whereIn('id_user', $tmp_user_rs);
            $user_rs_belum_lapor = MUser::where(['statusactive_user' => 1])->where('level', '=', '2')->whereNotIn('id_user', $user_rs_sudah_lapor->pluck('id_user'))->get()->count();
            $user_rs_sudah_lapor = $user_rs_sudah_lapor->get()->count();

            $user_puskesmas_sudah_lapor = MLimbahCair::where('statusactive_limbah_cair', 1)->where(['periode' => $i, 'tahun' => $tahun])->whereIn('id_user', $tmp_user_puskesmas);
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

        // Build response data
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
            ->withMessage('Success get limbah cair data.!')
            ->withData(['values' => $laporan])
            ->build();
    }
}