<?php

namespace App\Http\Controllers;

use App\Models\MLaporanLab;
use App\Models\MUser;
use Illuminate\Http\Request;
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

class LaporanLabController extends Controller
{
    /**
     * Get data laporan lab
     */
    function laporanLabProsesData(Request $request)
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
        $periode_nama = $periode ? Carbon::create()->day(1)->month($periode)->format('F') : null;

        $laporanLab = MLaporanLab::where('statusactive_laporan_lab', '<>', 0);
        
        if ($form_level == '1') {
            // Admin dapat melihat semua data
        } else {
            // User hanya dapat melihat data miliknya
            $laporanLab = $laporanLab->where('id_user', $form_id_user);
        }
        
        if ($periode) {
            $laporanLab = $laporanLab->where('periode', $periode);
        }
        if ($tahun) {
            $laporanLab = $laporanLab->where('tahun', $tahun);
        }

        $laporanLab = $laporanLab->orderBy('id_laporan_lab', 'DESC')->get();
        
        foreach ($laporanLab as $key => $v) {
            $user = MUser::where(['id_user' => $v->id_user])->latest()->first();
            $v->user = $user;
        }

        return MyRB::asSuccess(200)
            ->withMessage('Success get data laporan lab.!')
            ->withData([
                'data' => $laporanLab->values()->toArray()
            ])
            ->build();
    }

    /**
     * Update laporan lab - Simple Form
     */
    function laporanLabSimpleUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldid' => 'required|integer',
            'kualitas_udara' => 'required|string',
            'kualitas_air' => 'required|string',
            'kualitas_makanan' => 'required|string',
            'usap_alat_medis' => 'required|string',
            'limbah_cair' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return MyRB::asError(400)
                ->withMessage('Form tidak sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? 'xxx-xxxx-xxx';

        // Find existing report
        $laporanLab = MLaporanLab::find($request->oldid);
        if (!$laporanLab) {
            return MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data laporan lab tidak ditemukan.!')
                ->withData(null)
                ->build();
        }

        // Check ownership (non-admin users can only edit their own reports)
        if ($user->level != '1' && $laporanLab->id_user != $form_id_user) {
            return MyRB::asError(403)
                ->withHttpCode(403)
                ->withMessage('Anda tidak memiliki akses untuk mengubah data ini.!')
                ->withData(null)
                ->build();
        }

        // -- form payload -- \\
        $form_kualitas_udara = $request->kualitas_udara;
        $form_kualitas_air = $request->kualitas_air;
        $form_kualitas_makanan = $request->kualitas_makanan;
        $form_usap_alat_medis = $request->usap_alat_medis;
        $form_limbah_cair = $request->limbah_cair;
        $form_catatan = $request->catatan;
        $form_catatan = $request->catatan;
        
        // Get period and year from user input instead of current time
        $form_periode_nama = $request->periode; // User selected period name (e.g., "Januari", "Triwulan 1")
        $form_tahun = $request->tahun; // User selected year
        
        // Convert period name to month number for database storage
        $monthMapping = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
            'Triwulan 1' => 3, 'Triwulan 2' => 6, 'Triwulan 3' => 9, 'Triwulan 4' => 12,
            'Semester 1' => 6, 'Semester 2' => 12, 'Tahunan' => 12
        ];
        $form_periode = $monthMapping[$form_periode_nama] ?? 1;

        // Update the report with simplified data
        $laporanLab->kualitas_udara = $form_kualitas_udara;
        $laporanLab->kualitas_air = $form_kualitas_air;
        $laporanLab->kualitas_makanan = $form_kualitas_makanan;
        $laporanLab->usap_alat_medis = $form_usap_alat_medis;
        $laporanLab->limbah_cair = $form_limbah_cair;
        $laporanLab->catatan = $form_catatan;
        
        $laporanLab->periode = $form_periode;
        $laporanLab->periode_nama = $form_periode_nama;
        $laporanLab->tahun = $form_tahun;
        $laporanLab->user_updated = $form_username;
        $laporanLab->save();

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage("Sukses mengupdate laporan lab untuk periode '" . $form_periode_nama . " " . $form_tahun . "' .!")
            ->build();
    }

    /**
     * Create new laporan lab - Simple Form
     */
    function laporanLabSimpleStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kualitas_udara' => 'required|string',
            'kualitas_air' => 'required|string',
            'kualitas_makanan' => 'required|string',
            'usap_alat_medis' => 'required|string',
            'limbah_cair' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return MyRB::asError(400)
                ->withMessage('Form tidak sesuai.!')
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
        $form_kualitas_udara = $request->kualitas_udara;
        $form_kualitas_air = $request->kualitas_air;
        $form_kualitas_makanan = $request->kualitas_makanan;
        $form_usap_alat_medis = $request->usap_alat_medis;
        $form_limbah_cair = $request->limbah_cair;
        $form_catatan = $request->catatan;
        
        // Get period and year from user input instead of current time
        $form_periode_nama = $request->periode; // User selected period name (e.g., "Januari", "Triwulan 1")
        $form_tahun = $request->tahun; // User selected year
        
        // Convert period name to month number for database storage
        $monthMapping = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
            'Triwulan 1' => 3, 'Triwulan 2' => 6, 'Triwulan 3' => 9, 'Triwulan 4' => 12,
            'Semester 1' => 6, 'Semester 2' => 12, 'Tahunan' => 12
        ];
        $form_periode = $monthMapping[$form_periode_nama] ?? 1;

        // Check if report already exists for this period
        $existingReport = MLaporanLab::where([
            'id_user' => $form_id_user,
            'periode' => $form_periode,
            'tahun' => $form_tahun,
            'statusactive_laporan_lab' => 1
        ])->first();

        if ($existingReport) {
            return MyRB::asError(400)
                ->withHttpCode(400)
                ->withMessage('Laporan lab untuk periode `' . $form_periode_nama . ' ' . $form_tahun . '` sudah ada.!')
                ->withData(null)
                ->build();
        }

        // Create new laporan lab with simplified data
        $laporanLab = new MLaporanLab();
        $laporanLab->id_user = $form_id_user;
        
        // Save data to individual columns only
        $laporanLab->kualitas_udara = $form_kualitas_udara;
        $laporanLab->kualitas_air = $form_kualitas_air;
        $laporanLab->kualitas_makanan = $form_kualitas_makanan;
        $laporanLab->usap_alat_medis = $form_usap_alat_medis;
        $laporanLab->limbah_cair = $form_limbah_cair;
        $laporanLab->catatan = $form_catatan;
        
        $laporanLab->periode = $form_periode;
        $laporanLab->periode_nama = $form_periode_nama;
        $laporanLab->tahun = $form_tahun;
        $laporanLab->user_created = $form_username;
        $laporanLab->save();

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage("Sukses membuat laporan lab untuk periode '" . $form_periode_nama . " " . $form_tahun . "' .!")
            ->build();
    }

    /**
     * Create new laporan lab
     */
    function laporanLabProsesStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'nama_lab' => 'required|string|max:100',
            // 'jenis_pemeriksaan' => 'required|in:kualitas_udara,kualitas_air,kualitas_makanan,usap_alat_medis,limbah_cair',
            // 'total_pemeriksaan' => 'required|integer|min:0',
            // 'parameter_uji' => 'required|string',
            // 'hasil_uji' => 'required|string',
            // 'metode_analisis' => 'required|string|max:100',
            'catatan' => 'nullable|string',
            // 'link_sertifikat_lab' => 'nullable|string',
            // 'link_hasil_uji' => 'nullable|string',
            // 'link_dokumen_pendukung' => 'nullable|string',
            'periode' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2050',
        ]);

        if ($validator->fails()) {
            return MyRB::asError(400)
                ->withMessage('Form tidak sesuai.!')
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
        // $form_nama_lab = $request->nama_lab;
        // $form_jenis_pemeriksaan = $request->jenis_pemeriksaan;
        // $form_total_pemeriksaan = $request->total_pemeriksaan;
        // $form_parameter_uji = $request->parameter_uji;
        // $form_hasil_uji = $request->hasil_uji;
        // $form_metode_analisis = $request->metode_analisis;
        $form_catatan = $request->catatan;
        // $form_link_sertifikat_lab = $request->link_sertifikat_lab;
        // $form_link_hasil_uji = $request->link_hasil_uji;
        // $form_link_dokumen_pendukung = $request->link_dokumen_pendukung;
        $form_periode = $request->periode;
        $form_periode_nama = Carbon::create()->day(1)->month($form_periode)->locale('id')->monthName;
        $form_tahun = $request->tahun;

        // Validate parameter_uji and hasil_uji based on examination type
        $this->validateExaminationData($form_jenis_pemeriksaan, $form_parameter_uji, $form_hasil_uji);

        // Check if report already exists for this period and examination type
        $existingReport = MLaporanLab::where([
            'id_user' => $form_id_user,
            'jenis_pemeriksaan' => $form_jenis_pemeriksaan,
            'periode' => $form_periode,
            'tahun' => $form_tahun,
            'statusactive_laporan_lab' => 1
        ])->first();

        if ($existingReport) {
            return MyRB::asError(400)
                ->withHttpCode(400)
                ->withMessage('Laporan lab untuk jenis pemeriksaan `' . $form_jenis_pemeriksaan . '` periode `' . $form_periode_nama . ' ' . $form_tahun . '` sudah ada.!')
                ->withData(null)
                ->build();
        }

        // Create new laporan lab
        $laporanLab = new MLaporanLab();
        $laporanLab->id_user = $form_id_user;
        // $laporanLab->nama_lab = $form_nama_lab;
        // $laporanLab->jenis_pemeriksaan = $form_jenis_pemeriksaan;
        // $laporanLab->total_pemeriksaan = $form_total_pemeriksaan;
        // $laporanLab->parameter_uji = $form_parameter_uji;
        // $laporanLab->hasil_uji = $form_hasil_uji;
        // $laporanLab->metode_analisis = $form_metode_analisis;
        $laporanLab->catatan = $form_catatan;
        // $laporanLab->link_sertifikat_lab = $form_link_sertifikat_lab;
        // $laporanLab->link_hasil_uji = $form_link_hasil_uji;
        // $laporanLab->link_dokumen_pendukung = $form_link_dokumen_pendukung;
        $laporanLab->periode = $form_periode;
        $laporanLab->periode_nama = $form_periode_nama;
        $laporanLab->tahun = $form_tahun;
        $laporanLab->status_laporan_lab = 1;
        $laporanLab->statusactive_laporan_lab = 1;
        $laporanLab->user_created = $form_username;
        $laporanLab->save();

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage("Sukses membuat laporan lab untuk periode '" . $form_periode_nama . " " . $form_tahun . "' .!")
            ->build();
    }

    /**
     * Update laporan lab
     */
    function laporanLabProsesUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldid' => 'required|integer',
            // 'nama_lab' => 'required|string|max:100',
            // 'jenis_pemeriksaan' => 'required|string|max:100',
            // 'total_pemeriksaan' => 'required|integer|min:0',
            // 'parameter_uji' => 'required|string',
            // 'hasil_uji' => 'required|string',
            // 'metode_analisis' => 'required|string|max:100',
            'catatan' => 'nullable|string',
            // 'link_sertifikat_lab' => 'nullable|string',
            // 'link_hasil_uji' => 'nullable|string',
            // 'link_dokumen_pendukung' => 'nullable|string',
            'periode' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2050',
        ]);

        if ($validator->fails()) {
            return MyRB::asError(400)
                ->withMessage('Form tidak sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_username = $user->username ?? '';
        $form_nama_user = $user->username ?? '';
        $form_uid = $user->uid ?? 'xxx-xxxx-xxx';

        // Find existing report
        $laporanLab = MLaporanLab::find($request->oldid);
        if (!$laporanLab) {
            return MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data laporan lab tidak ditemukan.!')
                ->withData(null)
                ->build();
        }

        // Check ownership (non-admin users can only edit their own reports)
        if ($user->level != '1' && $laporanLab->id_user != $form_id_user) {
            return MyRB::asError(403)
                ->withHttpCode(403)
                ->withMessage('Anda tidak memiliki akses untuk mengubah data ini.!')
                ->withData(null)
                ->build();
        }

        // -- form payload -- \\
        // $form_nama_lab = $request->nama_lab;
        // $form_jenis_pemeriksaan = $request->jenis_pemeriksaan;
        // $form_total_pemeriksaan = $request->total_pemeriksaan;
        // $form_parameter_uji = $request->parameter_uji;
        // $form_hasil_uji = $request->hasil_uji;
        // $form_metode_analisis = $request->metode_analisis;
        $form_catatan = $request->catatan;
        // $form_link_sertifikat_lab = $request->link_sertifikat_lab;
        // $form_link_hasil_uji = $request->link_hasil_uji;
        // $form_link_dokumen_pendukung = $request->link_dokumen_pendukung;
        $form_periode = $request->periode;
        $form_periode_nama = Carbon::create()->day(1)->month($form_periode)->locale('id')->monthName;
        $form_tahun = $request->tahun;

        // Update the report
        // $laporanLab->nama_lab = $form_nama_lab;
        // $laporanLab->jenis_pemeriksaan = $form_jenis_pemeriksaan;
        // $laporanLab->total_pemeriksaan = $form_total_pemeriksaan;
        // $laporanLab->parameter_uji = $form_parameter_uji;
        // $laporanLab->hasil_uji = $form_hasil_uji;
        // $laporanLab->metode_analisis = $form_metode_analisis;
        $laporanLab->catatan = $form_catatan;
        // $laporanLab->link_sertifikat_lab = $form_link_sertifikat_lab;
        // $laporanLab->link_hasil_uji = $form_link_hasil_uji;
        // $laporanLab->link_dokumen_pendukung = $form_link_dokumen_pendukung;
        $laporanLab->periode = $form_periode;
        $laporanLab->periode_nama = $form_periode_nama;
        $laporanLab->tahun = $form_tahun;
        $laporanLab->user_updated = $form_username;
        $laporanLab->save();

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage("Sukses mengupdate laporan lab untuk periode '" . $form_periode_nama . " " . $form_tahun . "' .!")
            ->build();
    }

    /**
     * Delete laporan lab
     */
    function laporanLabProsesDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return MyRB::asError(400)
                ->withMessage('Form tidak sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_username = $user->username ?? '';

        // Find existing report
        $laporanLab = MLaporanLab::find($request->id);
        if (!$laporanLab) {
            return MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data laporan lab tidak ditemukan.!')
                ->withData(null)
                ->build();
        }

        // Check ownership (non-admin users can only delete their own reports)
        if ($user->level != '1' && $laporanLab->id_user != $form_id_user) {
            return MyRB::asError(403)
                ->withHttpCode(403)
                ->withMessage('Anda tidak memiliki akses untuk menghapus data ini.!')
                ->withData(null)
                ->build();
        }

        // Soft delete by setting statusactive to 0
        $laporanLab->statusactive_laporan_lab = 0;
        $laporanLab->user_updated = $form_username;
        $laporanLab->save();

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage('Sukses menghapus laporan lab.!')
            ->build();
    }

    /**
     * Get single laporan lab by ID
     */
    function laporanLabProsesShow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return MyRB::asError(400)
                ->withMessage('Form tidak sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        // -- user payload -- \\
        $user = MyUtils::getPayloadToken($request, true);
        $form_id_user = $user->id_user ?? 0;
        $form_level = $user->level ?? '3';

        // Find the report
        $laporanLab = MLaporanLab::where('statusactive_laporan_lab', '<>', 0)
            ->find($request->id);

        if (!$laporanLab) {
            return MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data laporan lab tidak ditemukan.!')
                ->withData(null)
                ->build();
        }

        // Check ownership (non-admin users can only view their own reports)
        if ($form_level != '1' && $laporanLab->id_user != $form_id_user) {
            return MyRB::asError(403)
                ->withHttpCode(403)
                ->withMessage('Anda tidak memiliki akses untuk melihat data ini.!')
                ->withData(null)
                ->build();
        }

        // Load user relationship
        $user = MUser::where(['id_user' => $laporanLab->id_user])->latest()->first();
        $laporanLab->user = $user;

        return MyRB::asSuccess(200)
            ->withMessage('Success get data laporan lab.!')
            ->withData($laporanLab)
            ->build();
    }

    /**
     * Validate examination data based on examination type
     */
    private function validateExaminationData($jenis_pemeriksaan, $parameter_uji, $hasil_uji)
    {
        try {
            $parameter_data = json_decode($parameter_uji, true);
            $hasil_data = json_decode($hasil_uji, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON format for parameter_uji or hasil_uji');
            }

            // Get expected parameters for this examination type
            $tempModel = new MLaporanLab();
            $tempModel->jenis_pemeriksaan = $jenis_pemeriksaan;
            $expectedParams = $tempModel->getParameterTemplate();
            
            // Validate that all required parameters are present
            foreach ($expectedParams as $param => $config) {
                if (!isset($parameter_data[$param]) || !isset($hasil_data[$param])) {
                    throw new Exception("Missing parameter: {$param} for examination type: {$jenis_pemeriksaan}");
                }
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Validation failed: " . $e->getMessage());
        }
    }
}