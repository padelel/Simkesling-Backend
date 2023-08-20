<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\LaporanBulananController;
use App\Http\Controllers\PusRsController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\TransporterPengajuanController;
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
    Route::group(['middleware' => 'ceklogin.webnext'], function () {
        Route::post('/', [LandingController::class, 'testApi'])->name('api.v1.testapi');
    });

    Route::group(['prefix' => 'user'], function () {
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
        Route::post('/laporan-bulanan/data', [LaporanBulananController::class, 'laporanProsesData'])->name('api.v1.user.laporan-bulanan.data');
        Route::post('/laporan-bulanan/create', [LaporanBulananController::class, 'laporanProsesCreate'])->name('api.v1.user.laporan-bulanan.create');
        Route::post('/laporan-bulanan/update', [LaporanBulananController::class, 'laporanProsesUpdate'])->name('api.v1.user.laporan-bulanan.update');
        // Route::post('/laporan-bulanan/delete', [LaporanBulananController::class, 'laporanProsesCreate'])->name('api.v1.user.laporan-bulanan.delete');

        // -- puskesmas rumahsakit
        Route::post('/puskesmas-rumahsakit/data', [PusRsController::class, 'pusRsProsesData'])->name('api.v1.user.puskesmas-rumahsakit.data');
        Route::post('/puskesmas-rumahsakit/create', [PusRsController::class, 'pusRsProsesCreate'])->name('api.v1.user.puskesmas-rumahsakit.create');
        Route::post('/puskesmas-rumahsakit/update', [PusRsController::class, 'pusRsProsesUpdate'])->name('api.v1.user.puskesmas-rumahsakit.update');
        Route::post('/puskesmas-rumahsakit/delete', [PusRsController::class, 'pusRsProsesDelete'])->name('api.v1.user.puskesmas-rumahsakit.delete');
    });
    // Route::post('/dokter/login', [AndroidController::class, 'loginDokter'])->name('api.android.dokter.login');
    // Route::get('/jadwal/sync', [AndroidController::class, 'syncJadwal'])->name('api.android.jadwal.sync');

    // Route::group(['middleware' => ['ceklogin.android']], function () {
    //     Route::get('/dokter/data/all', [AndroidController::class, 'getDataDokter'])->name('api.android.dokter.data.all');
    // });
});
