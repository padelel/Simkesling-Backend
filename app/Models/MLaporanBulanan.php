<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTraits;

class MLaporanBulanan extends Model
{
    use HasFactory, UuidTraits;

    protected $table = 'tbl_laporan_bulanan';
    protected $primaryKey = 'id_laporan_bulanan';
    
    protected $fillable = [
        'id_transporter',
        'id_user',
        'nama_transporter',
        'nama_pemusnah',
        'metode_pemusnah',
        'berat_limbah_total',
        'limbah_b3_covid',
        'limbah_b3_noncovid',
        'debit_limbah_cair',
        'kapasitas_ipal',
        'punya_penyimpanan_tps',
        'ukuran_penyimpanan_tps',
        'punya_pemusnahan_sendiri',
        'ukuran_pemusnahan_sendiri',
        'memenuhi_syarat',
        'catatan',
        'periode',
        'periode_nama',
        'tahun',
        'status_laporan_bulanan',
        'statusactive_laporan_bulanan',
        'user_created',
        'user_updated',
        'link_input_manifest',
        'link_input_logbook',
        'link_input_lab_ipal',
        'link_input_lab_lain',
        'link_input_dokumen_lingkungan_rs',
        'link_input_swa_pantau',
        'link_input_ujilab_cair',
        'limbah_b3_nonmedis',
        'limbah_b3_medis',
        'limbah_jarum',
        'limbah_sludge_ipal',
        'limbah_padat_infeksius'
    ];

    /**
     * Relationship dengan User (Puskesmas/RS)
     */
    public function user()
    {
        return $this->belongsTo(MUser::class, 'id_user', 'id_user');
    }

    /**
     * Relationship dengan Transporter
     */
    public function transporter()
    {
        return $this->belongsTo(MTransporter::class, 'id_transporter', 'id_transporter');
    }

    /**
     * Scope untuk filter berdasarkan periode dan tahun
     */
    public function scopeByPeriode($query, $periode, $tahun)
    {
        return $query->where('periode', $periode)->where('tahun', $tahun);
    }

    /**
     * Scope untuk filter laporan aktif
     */
    public function scopeActive($query)
    {
        return $query->where('statusactive_laporan_bulanan', '<>', 0);
    }

    /**
     * Accessor untuk total limbah padat (semua jenis limbah padat)
     */
    public function getTotalLimbahPadatAttribute()
    {
        return (float)($this->limbah_b3_covid ?? 0) + 
               (float)($this->limbah_b3_noncovid ?? 0) + 
               (float)($this->limbah_b3_nonmedis ?? 0) + 
               (float)($this->limbah_b3_medis ?? 0) + 
               (float)($this->limbah_jarum ?? 0) + 
               (float)($this->limbah_sludge_ipal ?? 0) + 
               (float)($this->limbah_padat_infeksius ?? 0);
    }
}
