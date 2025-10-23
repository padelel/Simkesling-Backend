<?php

namespace App\Http\Controllers;

use App\Models\MLimbahCair;
use App\Models\MTransporter;
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

class LimbahCairController extends Controller
{
    /**
     * Get data limbah cair
     */
    function limbahCairProsesData(Request $request)
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

        $limbahCair = MLimbahCair::where('statusactive_limbah_cair', '<>', 0);
        
        if ($form_level == '1') {
            // Admin dapat melihat semua data
        } else {
            // User hanya dapat melihat data miliknya
            $limbahCair = $limbahCair->where('id_user', $form_id_user);
        }
        
        if ($periode) {
            $limbahCair = $limbahCair->where('periode', $periode);
        }
        if ($tahun) {
            $limbahCair = $limbahCair->where('tahun', $tahun);
        }

        $limbahCair = $limbahCair->orderBy('id_limbah_cair', 'DESC')->get();
        
        foreach ($limbahCair as $key => $v) {
            $user = MUser::where(['id_user' => $v->id_user])->latest()->first();
            $v->user = $user;
            
            $transporter = MTransporter::where(['id_transporter' => $v->id_transporter])->latest()->first();
            $v->transporter = $transporter;
        }

        return MyRB::asSuccess(200)
            ->withMessage('Success get data limbah cair.!')
            ->withData($limbahCair->values()->toArray())
            ->build();
    }

    /**
     * Create new limbah cair report
     */
    function limbahCairProsesCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'id_transporter' => 'required|integer|min:1',
            'ph' => 'required|numeric|min:0|max:14',
            'bod' => 'required|numeric|min:0',
            'cod' => 'required|numeric|min:0',
            'tss' => 'required|numeric|min:0',
            'minyak_lemak' => 'required|numeric|min:0',
            'amoniak' => 'required|numeric|min:0',
            'total_coliform' => 'required|integer|min:0',
            'debit_air_limbah' => 'required|numeric|min:0',
            'kapasitas_ipal' => 'required|string|max:50',
            'link_lab_ipal' => 'nullable|string',
            'link_ujilab_cair' => 'required|string',
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
        $form_id_transporter = $request->id_transporter;
        $form_nama_transporter = $request->nama_transporter ?? null;
        $form_ph = $request->ph;
        $form_bod = $request->bod;
        $form_cod = $request->cod;
        $form_tss = $request->tss;
        $form_minyak_lemak = $request->minyak_lemak;
        $form_amoniak = $request->amoniak;
        $form_total_coliform = $request->total_coliform;
        $form_debit_air_limbah = $request->debit_air_limbah;
        $form_kapasitas_ipal = $request->kapasitas_ipal;
        $form_periode = $request->periode;
        $form_periode_nama = Carbon::create()->day(1)->month($form_periode)->locale('id')->monthName;
        $form_tahun = $request->tahun;

        // Links - hanya yang diperlukan sesuai database
        $form_link_persetujuan_teknis = $request->link_persetujuan_teknis ?? $request->link_lab_ipal ?? '';
        $form_link_ujilab_cair = $request->link_ujilab_cair ?? '';
        // Legacy fields untuk backward compatibility
        $form_link_lab_ipal = $request->link_lab_ipal ?? '';
        $form_link_manifest = $request->link_manifest ?? '';
        $form_link_logbook = $request->link_logbook ?? '';

        // Check if report already exists for this period
        $existingReport = MLimbahCair::where([
            'id_user' => $form_id_user,
            'periode' => $form_periode,
            'tahun' => $form_tahun,
            'statusactive_limbah_cair' => 1
        ])->first();

        if ($existingReport) {
            return MyRB::asError(400)
                ->withHttpCode(400)
                ->withMessage('Laporan limbah cair untuk periode `' . $form_periode_nama . ' ' . $form_tahun . '` sudah ada.!')
                ->withData(null)
                ->build();
        }

        // Create new limbah cair report
        $limbahCair = new MLimbahCair();
        $limbahCair->id_user = $form_id_user;
        $limbahCair->id_transporter = $form_id_transporter;
        $limbahCair->nama_transporter = $form_nama_transporter;
        $limbahCair->ph = $form_ph;
        $limbahCair->bod = $form_bod;
        $limbahCair->cod = $form_cod;
        $limbahCair->tss = $form_tss;
        $limbahCair->minyak_lemak = $form_minyak_lemak;
        $limbahCair->amoniak = $form_amoniak;
        $limbahCair->total_coliform = $form_total_coliform;
        $limbahCair->debit_air_limbah = $form_debit_air_limbah;
        $limbahCair->kapasitas_ipal = $form_kapasitas_ipal;
        $limbahCair->periode = $form_periode;
        $limbahCair->periode_nama = $form_periode_nama;
        $limbahCair->tahun = $form_tahun;
        // Link Dokumen - hanya yang ada di database
        $limbahCair->link_persetujuan_teknis = $form_link_persetujuan_teknis;
        $limbahCair->link_ujilab_cair = $form_link_ujilab_cair;
        // Legacy fields untuk backward compatibility
        $limbahCair->link_lab_ipal = $form_link_lab_ipal;
        $limbahCair->link_manifest = $form_link_manifest;
        $limbahCair->link_logbook = $form_link_logbook;
        $limbahCair->status_limbah_cair = 1;
        $limbahCair->statusactive_limbah_cair = 1;
        $limbahCair->user_created = $form_username;
        $limbahCair->save();

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage("Sukses membuat laporan limbah cair untuk periode '" . $form_periode_nama . " " . $form_tahun . "' .!")
            ->build();
    }

    /**
     * Update limbah cair report
     */
    function limbahCairProsesUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldid' => 'required|integer',
            // 'id_transporter' => 'required|integer|min:1',
            'ph' => 'required|numeric|min:0|max:14',
            'bod' => 'required|numeric|min:0',
            'cod' => 'required|numeric|min:0',
            'tss' => 'required|numeric|min:0',
            'minyak_lemak' => 'required|numeric|min:0',
            'amoniak' => 'required|numeric|min:0',
            'total_coliform' => 'required|integer|min:0',
            'debit_air_limbah' => 'required|numeric|min:0',
            'kapasitas_ipal' => 'required|string|max:50',
            'link_lab_ipal' => 'nullable|string',
            'link_ujilab_cair' => 'required|string',
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
        $limbahCair = MLimbahCair::find($request->oldid);
        if (!$limbahCair) {
            return MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data limbah cair tidak ditemukan.!')
                ->withData(null)
                ->build();
        }

        // Check ownership (non-admin users can only edit their own reports)
        if ($user->level != '1' && $limbahCair->id_user != $form_id_user) {
            return MyRB::asError(403)
                ->withHttpCode(403)
                ->withMessage('Anda tidak memiliki akses untuk mengubah data ini.!')
                ->withData(null)
                ->build();
        }

        // -- form payload -- \\
        $form_id_transporter = $request->id_transporter;
        $form_nama_transporter = $request->nama_transporter ?? null;
        $form_ph = $request->ph;
        $form_bod = $request->bod;
        $form_cod = $request->cod;
        $form_tss = $request->tss;
        $form_minyak_lemak = $request->minyak_lemak;
        $form_amoniak = $request->amoniak;
        $form_total_coliform = $request->total_coliform;
        $form_debit_air_limbah = $request->debit_air_limbah;
        $form_kapasitas_ipal = $request->kapasitas_ipal;
        $form_periode = $request->periode;
        $form_periode_nama = Carbon::create()->day(1)->month($form_periode)->locale('id')->monthName;
        $form_tahun = $request->tahun;

        // Links - hanya yang diperlukan sesuai database
        $form_link_persetujuan_teknis = $request->link_persetujuan_teknis ?? $request->link_lab_ipal ?? '';
        $form_link_ujilab_cair = $request->link_ujilab_cair ?? '';
        // Legacy fields untuk backward compatibility
        $form_link_lab_ipal = $request->link_lab_ipal ?? '';
        $form_link_manifest = $request->link_manifest ?? '';
        $form_link_logbook = $request->link_logbook ?? '';

        // Update the report
        $limbahCair->id_transporter = $form_id_transporter;
        $limbahCair->nama_transporter = $form_nama_transporter;
        $limbahCair->ph = $form_ph;
        $limbahCair->bod = $form_bod;
        $limbahCair->cod = $form_cod;
        $limbahCair->tss = $form_tss;
        $limbahCair->minyak_lemak = $form_minyak_lemak;
        $limbahCair->amoniak = $form_amoniak;
        $limbahCair->total_coliform = $form_total_coliform;
        $limbahCair->debit_air_limbah = $form_debit_air_limbah;
        $limbahCair->kapasitas_ipal = $form_kapasitas_ipal;
        $limbahCair->periode = $form_periode;
        $limbahCair->periode_nama = $form_periode_nama;
        $limbahCair->tahun = $form_tahun;
        // Link Dokumen - hanya yang ada di database
        $limbahCair->link_persetujuan_teknis = $form_link_persetujuan_teknis;
        $limbahCair->link_ujilab_cair = $form_link_ujilab_cair;
        // Legacy fields untuk backward compatibility
        $limbahCair->link_lab_ipal = $form_link_lab_ipal;
        $limbahCair->link_manifest = $form_link_manifest;
        $limbahCair->link_logbook = $form_link_logbook;
        $limbahCair->user_updated = $form_username;
        $limbahCair->save();

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage("Sukses mengupdate laporan limbah cair untuk periode '" . $form_periode_nama . " " . $form_tahun . "' .!")
            ->build();
    }

    /**
     * Delete limbah cair report
     */
    function limbahCairProsesDelete(Request $request)
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
        $limbahCair = MLimbahCair::find($request->id);
        if (!$limbahCair) {
            return MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data limbah cair tidak ditemukan.!')
                ->withData(null)
                ->build();
        }

        // Check ownership (non-admin users can only delete their own reports)
        if ($user->level != '1' && $limbahCair->id_user != $form_id_user) {
            return MyRB::asError(403)
                ->withHttpCode(403)
                ->withMessage('Anda tidak memiliki akses untuk menghapus data ini.!')
                ->withData(null)
                ->build();
        }

        // Soft delete by setting statusactive to 0
        $limbahCair->statusactive_limbah_cair = 0;
        $limbahCair->user_updated = $form_username;
        $limbahCair->save();

        return MyRB::asSuccess(200)
            ->withHttpCode(200)
            ->withData(null)
            ->withMessage('Sukses menghapus laporan limbah cair.!')
            ->build();
    }

    /**
     * Get single limbah cair report by ID
     */
    function limbahCairProsesDetail(Request $request)
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

        $limbahCair = MLimbahCair::where('statusactive_limbah_cair', '<>', 0)
            ->where('id_limbah_cair', $request->id);

        if ($form_level != '1') {
            // Non-admin users can only view their own reports
            $limbahCair = $limbahCair->where('id_user', $form_id_user);
        }

        $limbahCair = $limbahCair->first();

        if (!$limbahCair) {
            return MyRB::asError(404)
                ->withHttpCode(404)
                ->withMessage('Data limbah cair tidak ditemukan.!')
                ->withData(null)
                ->build();
        }

        // Load relationships
        $user = MUser::where(['id_user' => $limbahCair->id_user])->latest()->first();
        $limbahCair->user = $user;

        if ($limbahCair->id_transporter) {
            $transporter = MTransporter::where(['id_transporter' => $limbahCair->id_transporter])->latest()->first();
            $limbahCair->transporter = $transporter;
        }

        return MyRB::asSuccess(200)
            ->withMessage('Success get detail limbah cair.!')
            ->withData($limbahCair)
            ->build();
    }
}