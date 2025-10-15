<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardLimbahCairController;
use App\Http\Controllers\DashboardLimbahPadatController;
use App\Http\Controllers\LimbahCairController;
use App\Http\Controllers\LimbahPadatController;
use App\Http\Controllers\LaporanBulananController;
use App\Http\Controllers\LaporanLabController;
use App\Http\Controllers\LaporanRekapitulasiController;
use App\Http\Controllers\PengajuanTransporterController;
use App\Http\Controllers\PusRsController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\TransporterPengajuanController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::group(['prefix' => 'assets'], function () {
//     Route::post
// });
Route::group(['prefix' => 'v1'], function () {
    Route::post('/login', [LandingController::class, 'prosesLogin'])->name('api.v1.landing.proses-login');

    Route::post('/coba', [LandingController::class, 'testApi'])->name('api.v1.testapi');
    
    // Test endpoints tanpa middleware untuk testing
    Route::post('/test/kecamatan', [LandingController::class, 'kecamatanProsesData'])->name('api.v1.test.kecamatan');
    Route::post('/test/kelurahan', [LandingController::class, 'kelurahanProsesData'])->name('api.v1.test.kelurahan');
    
    Route::group(['middleware' => 'ceklogin.webnext'], function () {
        Route::post('/', [LandingController::class, 'testApi'])->name('api.v1.testapi');
    });

    Route::group(['prefix' => 'user', 'middleware' => 'ceklogin.webnext'], function () {
        // -- dashboard
        Route::post('/dashboard-user/data', [LandingController::class, 'dasboardUserProsesData'])->name('api.v1.user.dashboard.user.data');
        Route::post('/dashboard-user/data-lab', [LandingController::class, 'dashboardUserLabData'])->name('api.v1.user.dashboard.user.data-lab');
        Route::post('/dashboard-admin/data', [LandingController::class, 'dasboardAdminProsesData'])->name('api.v1.user.dashboard.admin.data');
        
        // -- dashboard limbah (separated controllers)
        Route::post('/dashboard-admin/limbah-padat/data', [DashboardLimbahPadatController::class, 'dashboardLimbahPadatData'])->name('api.v1.user.dashboard.admin.limbah.padat.data');
        Route::post('/dashboard-admin/limbah-cair/data', [DashboardLimbahCairController::class, 'dashboardLimbahCairData'])->name('api.v1.user.dashboard.admin.limbah.cair.data');
        Route::post('/dashboard-admin/laporan-lab/data', [LandingController::class, 'dashboardAdminLaporanLabData'])->name('api.v1.user.dashboard.admin.laporan.lab.data');

        // -- kecamatan kelurahan
        Route::post('/kecamatan/data', [LandingController::class, 'kecamatanProsesData'])->name('api.v1.user.kecamatan.data');
        Route::post('/kelurahan/data', [LandingController::class, 'kelurahanProsesData'])->name('api.v1.user.kelurahan.data');

        // -- pengajuan transporter
        Route::post('/pengajuan-transporter/data', [TransporterPengajuanController::class, 'mouTmpProsesData'])->name('api.v1.user.pengajuan-transporter.data');
        Route::post('/pengajuan-transporter/create', [TransporterPengajuanController::class, 'mouTmpProsesCreate'])->name('api.v1.user.pengajuan-transporter.create');
        Route::post('/pengajuan-transporter/update', [TransporterPengajuanController::class, 'mouTmpProsesUpdate'])->name('api.v1.user.pengajuan-transporter.update');
        Route::post('/pengajuan-transporter/delete', [TransporterPengajuanController::class, 'mouTmpProsesDelete'])->name('api.v1.user.pengajuan-transporter.delete');
        Route::post('/pengajuan-transporter/validasi', [TransporterPengajuanController::class, 'mouTmpProsesValidasi'])->name('api.v1.user.pengajuan-transporter.validasi');

        // -- transporter
        Route::post('/transporter/data', [TransporterController::class, 'mouProsesData'])->name('api.v1.user.transporter.data');
        Route::post('/transporter/update', [TransporterController::class, 'mouProsesUpdate'])->name('api.v1.user.transporter.validasi');
        Route::post('/transporter/delete', [TransporterController::class, 'mouProsesDelete'])->name('api.v1.user.transporter.delete');

        // -- laporan bulanan
        Route::post('/laporan-rekapitulasi/data', [LaporanBulananController::class, 'laporanRekapitulasiProsesData'])->name('api.v1.user.laporan-rekapitulasi.data');
        Route::post('/laporan-bulanan/data', [LaporanBulananController::class, 'laporanProsesData'])->name('api.v1.user.laporan-bulanan.data');
        Route::post('/laporan-bulanan/create', [LaporanBulananController::class, 'laporanProsesCreate'])->name('api.v1.user.laporan-bulanan.create');
        Route::post('/laporan-bulanan/update', [LaporanBulananController::class, 'laporanProsesUpdate'])->name('api.v1.user.laporan-bulanan.update');
        Route::post('/laporan-bulanan/delete', [LaporanBulananController::class, 'laporanProsesDelete'])->name('api.v1.user.laporan-bulanan.delete');

        // -- limbah (limbah padat - using existing laporan-bulanan endpoints)
        Route::post('/limbah/data', [LaporanBulananController::class, 'laporanProsesData'])->name('api.v1.user.limbah.data');
        Route::post('/limbah/create', [LaporanBulananController::class, 'laporanProsesCreate'])->name('api.v1.user.limbah.create');
        Route::post('/limbah/update', [LaporanBulananController::class, 'laporanProsesUpdate'])->name('api.v1.user.limbah.update');
        Route::post('/limbah/delete', [LaporanBulananController::class, 'laporanProsesDelete'])->name('api.v1.user.limbah.delete');

        // -- limbah cair
        Route::post('/limbah-cair/data', [LimbahCairController::class, 'limbahCairProsesData'])->name('api.v1.user.limbah-cair.data');
        Route::post('/limbah-cair/create', [LimbahCairController::class, 'limbahCairProsesCreate'])->name('api.v1.user.limbah-cair.create');
        Route::post('/limbah-cair/update', [LimbahCairController::class, 'limbahCairProsesUpdate'])->name('api.v1.user.limbah-cair.update');
        Route::post('/limbah-cair/delete', [LimbahCairController::class, 'limbahCairProsesDelete'])->name('api.v1.user.limbah-cair.delete');
        Route::post('/limbah-cair/detail', [LimbahCairController::class, 'limbahCairProsesDetail'])->name('api.v1.user.limbah-cair.detail');

        // -- laporan lab
        Route::post('/laporan-lab/data', [LaporanLabController::class, 'laporanLabProsesData'])->name('api.v1.user.laporan-lab.data');
        Route::post('/laporan-lab/create', [LaporanLabController::class, 'laporanLabProsesCreate'])->name('api.v1.user.laporan-lab.create');
        Route::post('/laporan-lab/simple-create', [LaporanLabController::class, 'laporanLabSimpleStore'])->name('api.v1.user.laporan-lab.simple-create');
        Route::post('/laporan-lab/simple-update', [LaporanLabController::class, 'laporanLabSimpleUpdate'])->name('api.v1.user.laporan-lab.simple-update');
        Route::post('/laporan-lab/update', [LaporanLabController::class, 'laporanLabProsesUpdate'])->name('api.v1.user.laporan-lab.update');
        Route::post('/laporan-lab/delete', [LaporanLabController::class, 'laporanLabProsesDelete'])->name('api.v1.user.laporan-lab.delete');
        Route::post('/laporan-lab/show', [LaporanLabController::class, 'laporanLabProsesShow'])->name('api.v1.user.laporan-lab.show');

        // -- puskesmas rumahsakit
        Route::post('/puskesmas-rumahsakit/data', [PusRsController::class, 'pusRsProsesData'])->name('api.v1.user.puskesmas-rumahsakit.data');
        Route::post('/puskesmas-rumahsakit/data-profile', [PusRsController::class, 'pusRsProsesDataProfile'])->name('api.v1.user.puskesmas-rumahsakit.data-profile');
        Route::post('/puskesmas-rumahsakit/create', [PusRsController::class, 'pusRsProsesCreate'])->name('api.v1.user.puskesmas-rumahsakit.create');
        Route::post('/puskesmas-rumahsakit/update', [PusRsController::class, 'pusRsProsesUpdate'])->name('api.v1.user.puskesmas-rumahsakit.update');
        Route::post('/puskesmas-rumahsakit/delete', [PusRsController::class, 'pusRsProsesDelete'])->name('api.v1.user.puskesmas-rumahsakit.delete');
    });

    Route::group(['prefix' => 'admin', 'middleware' => 'ceklogin.webnext'], function () {
        // -- admin laporan management
        Route::post('/laporan-bulanan/data', [LaporanBulananController::class, 'laporanProsesData'])->name('api.v1.admin.laporan-bulanan.data');
        Route::post('/limbah-cair/data', [LimbahCairController::class, 'limbahCairProsesData'])->name('api.v1.admin.limbah-cair.data');
        Route::post('/laporan-lab/data', [LaporanLabController::class, 'laporanLabProsesData'])->name('api.v1.admin.laporan-lab.data');
    });
    // Route::post('/dokter/login', [AndroidController::class, 'loginDokter'])->name('api.android.dokter.login');
    // Route::get('/jadwal/sync', [AndroidController::class, 'syncJadwal'])->name('api.android.jadwal.sync');

    // Route::group(['middleware' => ['ceklogin.android']], function () {
    //     Route::get('/dokter/data/all', [AndroidController::class, 'getDataDokter'])->name('api.android.dokter.data.all');
    // });
});
