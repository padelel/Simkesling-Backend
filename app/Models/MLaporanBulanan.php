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
}
