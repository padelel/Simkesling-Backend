<?php

namespace App\Services;

use App\Models\MLimbahCair;
use App\Models\MUser;
use Illuminate\Support\Facades\DB;

class LimbahCairService
{
    public function getData($params)
    {
        $year = $params['year'] ?? null;
        $period = $params['period'] ?? null;
        $userId = $params['user_id'] ?? null;
        $userLevel = $params['user_level'] ?? '3';
        $searchName = $params['search_name'] ?? null;
        $includeFacilities = $params['include_facilities'] ?? true;

        // Build Query
        $query = MLimbahCair::query()
            ->select([
                'id_limbah_cair', 'id_user', 'id_transporter', 'nama_transporter',
                'periode', 'periode_nama', 'tahun',
                'ph', 'bod', 'cod', 'tss', 'minyak_lemak', 'amoniak',
                'total_coliform', 'debit_air_limbah', 'kapasitas_ipal',
                'link_persetujuan_teknis', 'link_ujilab_cair',
                'statusactive_limbah_cair', 'status_limbah_cair',
                'created_at', 'updated_at', 'user_created', 'user_updated'
            ])
            ->with([
                'user:id_user,nama_user,username,tipe_tempat,kecamatan,kelurahan',
                'transporter:id_transporter,nama_transporter'
            ])
            ->where('statusactive_limbah_cair', '<>', 0);

        // Apply filters
        if ($userLevel != '1' && $userId) {
            $query->where('id_user', $userId);
        }

        if ($period) {
            $query->where('periode', $period);
        }

        if ($year) {
            $query->where('tahun', $year);
        }

        if ($searchName) {
            $query->whereHas('user', function($q) use ($searchName) {
                $q->where('nama_user', 'LIKE', '%' . $searchName . '%');
            });
        }

        // Optimization: Indexing should be on (tahun, periode, id_user)
        // Sort by latest
        $reports = $query->orderBy('tahun', 'DESC')
            ->orderBy('periode', 'DESC')
            ->limit(2000) // Safety limit to prevent OOM
            ->get();

        // For regular users (level 2 & 3), return array directly for frontend compatibility
        // For admin, return structured data with additional info
        if ($userLevel == '1') {
            $data = [
                'reports' => $reports,
                'data' => $reports->toArray(), // For backward compatibility
            ];

            // Fetch facilities only if needed (Admin view)
            if ($includeFacilities) {
                $facilitiesQuery = MUser::query()
                    ->select(['id_user', 'nama_user', 'username', 'tipe_tempat'])
                    ->where('statusactive_user', 1)
                    ->whereIn('level', ['2', '3']);

                if ($searchName) {
                    $facilitiesQuery->where('nama_user', 'LIKE', '%' . $searchName . '%');
                }

                $data['all_facilities'] = $facilitiesQuery->get();
            }

            return $data;
        } else {
            // For regular users, return array directly
            return $reports->toArray();
        }
    }
}
