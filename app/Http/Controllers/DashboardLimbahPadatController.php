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

class DashboardLimbahPadatController extends Controller
{
    function dashboardLimbahPadatData(Request $request)
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
        
        // OPTIMIZATION: Preload all data once for the entire year to reduce queries from 48+ to 4
        // 1. Get all users and group by level - SINGLE QUERY
        $all_users = MUser::where(['statusactive_user' => 1])
            ->where('level', '<>', '1')
            ->get();
        
        $user_rs_ids = $all_users->where('level', '=', '2')->pluck('id_user');
        $user_puskesmas_ids = $all_users->where('level', '=', '3')->pluck('id_user');
        
        // 2. Preload all reports for the year - SINGLE QUERY
        $all_reports_year = MLaporanBulanan::where('statusactive_laporan_bulanan', '<>', 0)
            ->where('tahun', $tahun)
            ->get()
            ->groupBy('periode');
        
        // 3. Get transporter count - SINGLE QUERY
        $total_transporter = MTransporter::where('statusactive_transporter', 1)->count();
        
        // Calculate static totals (no DB queries)
        $total_puskesmas_rs = $all_users->count();
        $total_rs = $user_rs_ids->count();
        $total_puskesmas = $user_puskesmas_ids->count();
        
        // Calculate current period stats using preloaded data
        $laporan_periode_now = $all_reports_year[$periode] ?? collect();
        $total_laporan_perperiode = $laporan_periode_now->count();
        
        $user_laporan_ids = $laporan_periode_now->pluck('id_user');
        $total_puskesmas_rs_sudah_lapor = $user_laporan_ids->count();
        $total_puskesmas_rs_belum_lapor = $total_puskesmas_rs - $total_puskesmas_rs_sudah_lapor;
        
        $user_rs_sudah_lapor = $user_laporan_ids->intersect($user_rs_ids)->count();
        $user_rs_belum_lapor = $total_rs - $user_rs_sudah_lapor;
        
        $user_puskesmas_sudah_lapor = $user_laporan_ids->intersect($user_puskesmas_ids)->count();
        $user_puskesmas_belum_lapor = $total_puskesmas - $user_puskesmas_sudah_lapor;

        // OPTIMIZATION: Process user notifications using preloaded data (no queries in loop)
        foreach ($all_users as $value) {
            // Get all reported months for this user from preloaded data
            $reported_months = [];
            foreach ($all_reports_year as $month => $reports) {
                if ($reports->contains('id_user', $value->id_user)) {
                    $reported_months[] = $month;
                }
            }
            
            // Find missing months (1-12)
            $all_months = range(1, 12);
            $missing_months = array_diff($all_months, $reported_months);
            
            $value->sudah_lapor_limbah_padat = (count($missing_months) == 0) ? true : false;
            $value->missing_months_padat = array_values($missing_months);
            $value->reported_months_padat = $reported_months;
            $value->sudah_lapor = $value->sudah_lapor_limbah_padat;
        }

        // OPTIMIZATION: Generate chart data using preloaded data (no queries in loop)
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
            // Get reports for this month from preloaded data
            $month_reports = $all_reports_year[$i] ?? collect();
            $month_user_ids = $month_reports->pluck('id_user');
            
            // Calculate counts using collection operations (no DB queries)
            $puskesmas_rs_sudah_lapor = $month_user_ids->count();
            $puskesmas_rs_belum_lapor = $total_puskesmas_rs - $puskesmas_rs_sudah_lapor;
            
            $rs_sudah_lapor = $month_user_ids->intersect($user_rs_ids)->count();
            $rs_belum_lapor = $total_rs - $rs_sudah_lapor;
            
            $puskesmas_sudah_lapor = $month_user_ids->intersect($user_puskesmas_ids)->count();
            $puskesmas_belum_lapor = $total_puskesmas - $puskesmas_sudah_lapor;
            
            // Push to chart arrays
            array_push($total_chart_puskesmas_rs, $total_puskesmas_rs);
            array_push($total_chart_puskesmas_rs_belum_lapor, $puskesmas_rs_belum_lapor);
            array_push($total_chart_puskesmas_rs_sudah_lapor, $puskesmas_rs_sudah_lapor);
            
            array_push($total_chart_rs, $total_rs);
            array_push($total_chart_rs_sudah_lapor, $rs_sudah_lapor);
            array_push($total_chart_rs_belum_lapor, $rs_belum_lapor);
            
            array_push($total_chart_puskesmas, $total_puskesmas);
            array_push($total_chart_puskesmas_sudah_lapor, $puskesmas_sudah_lapor);
            array_push($total_chart_puskesmas_belum_lapor, $puskesmas_belum_lapor);
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
        $laporan['notif_user_laporan_bulanan'] = $all_users;

        return MyRB::asSuccess(200)
            ->withMessage('Success get limbah padat data.!')
            ->withData(['values' => $laporan])
            ->build();
    }
}