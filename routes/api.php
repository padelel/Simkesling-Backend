<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\PusRsController;
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
        Route::post('/pengajuan-transporter/data', [TransporterPengajuanController::class, 'mouTmpProsesData'])->name('api.v1.user.mou.data');
        Route::post('/pengajuan-transporter/create', [TransporterPengajuanController::class, 'mouTmpProsesCreate'])->name('api.v1.user.mou.create');
        Route::post('/pengajuan-transporter/update', [TransporterPengajuanController::class, 'mouTmpProsesUpdate'])->name('api.v1.user.mou.update');
        Route::post('/pengajuan-transporter/delete', [TransporterPengajuanController::class, 'mouTmpProsesDelete'])->name('api.v1.user.mou.delete');
        Route::post('/pengajuan-transporter/validasi', [TransporterPengajuanController::class, 'mouTmpProsesValidasi'])->name('api.v1.user.mou.validasi');
    });
    // Route::post('/dokter/login', [AndroidController::class, 'loginDokter'])->name('api.android.dokter.login');
    // Route::get('/jadwal/sync', [AndroidController::class, 'syncJadwal'])->name('api.android.jadwal.sync');

    // Route::group(['middleware' => ['ceklogin.android']], function () {
    //     Route::get('/dokter/data/all', [AndroidController::class, 'getDataDokter'])->name('api.android.dokter.data.all');
    // });
});
