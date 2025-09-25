<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTraits;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MLaporanLab extends Model
{
    use HasFactory, UuidTraits;

    // Constants for examination types
    const JENIS_KUALITAS_UDARA = 'kualitas_udara';
    const JENIS_KUALITAS_AIR = 'kualitas_air';
    const JENIS_KUALITAS_MAKANAN = 'kualitas_makanan';
    const JENIS_USAP_ALAT_MEDIS_LINEN = 'usap_alat_medis_linen';
    const JENIS_LIMBAH_CAIR = 'limbah_cair';
    const JENIS_GABUNGAN = 'gabungan';

    protected $table = 'tbl_laporan_lab';
    protected $primaryKey = 'id_laporan_lab';
    
    protected $fillable = [
        'id_user',
        'nama_lab',
        'jenis_pemeriksaan',
        'total_pemeriksaan',
        'parameter_uji',
        'hasil_uji',
        'metode_analisis',
        'catatan',
        'link_sertifikat_lab',
        'link_hasil_uji',
        'link_dokumen_pendukung',
        'periode',
        'periode_nama',
        'tahun',
        'status_laporan_lab',
        'statusactive_laporan_lab',
        'user_created',
        'user_updated',
        'uid'
    ];

    protected $casts = [
        'total_pemeriksaan' => 'integer',
        'periode' => 'integer',
        'tahun' => 'integer',
        'status_laporan_lab' => 'integer',
        'statusactive_laporan_lab' => 'integer',
        'parameter_uji' => 'json',
        'hasil_uji' => 'json'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = Str::uuid();
            }
            
            // Set periode_nama berdasarkan periode
            if ($model->periode && !$model->periode_nama) {
                $model->periode_nama = Carbon::create()->day(1)->month($model->periode)->format('F');
            }
        });

        static::updating(function ($model) {
            // Update periode_nama jika periode berubah
            if ($model->isDirty('periode') && $model->periode) {
                $model->periode_nama = Carbon::create()->day(1)->month($model->periode)->format('F');
            }
        });
    }

    /**
     * Relationship dengan User (Puskesmas/RS)
     */
    public function user()
    {
        return $this->belongsTo(MUser::class, 'id_user', 'id_user');
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
        return $query->where('statusactive_laporan_lab', '<>', 0);
    }

    /**
     * Scope untuk filter berdasarkan user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('id_user', $userId);
    }

    /**
     * Scope untuk filter berdasarkan jenis pemeriksaan
     */
    public function scopeByJenisPemeriksaan($query, $jenis)
    {
        return $query->where('jenis_pemeriksaan', $jenis);
    }

    /**
     * Get parameter template based on examination type
     */
    public function getParameterTemplate()
    {
        switch ($this->jenis_pemeriksaan) {
            case self::JENIS_KUALITAS_UDARA:
                return [
                    'pencahayaan' => ['satuan' => 'lux', 'nilai' => null],
                    'kebisingan' => ['satuan' => 'dB', 'nilai' => null],
                    'udara_ambien' => ['satuan' => 'µg/m³', 'nilai' => null],
                    'emisi' => ['satuan' => 'mg/m³', 'nilai' => null],
                    'kelembapan' => ['satuan' => '%', 'nilai' => null]
                ];
            case self::JENIS_KUALITAS_AIR:
                return [
                    'air_minum' => ['satuan' => 'mg/L', 'nilai' => null],
                    'air_hemodialisa' => ['satuan' => 'mg/L', 'nilai' => null],
                    'air_hygiene_sanitasi' => ['satuan' => 'mg/L', 'nilai' => null]
                ];
            case self::JENIS_KUALITAS_MAKANAN:
                return [
                    'makanan' => ['satuan' => 'CFU/g', 'nilai' => null],
                    'usap_alat_makan_masak' => ['satuan' => 'CFU/cm²', 'nilai' => null],
                    'usap_dubur' => ['satuan' => 'CFU/swab', 'nilai' => null]
                ];
            case self::JENIS_USAP_ALAT_MEDIS_LINEN:
                return [
                    'usap_alat_medis' => ['satuan' => 'CFU/cm²', 'nilai' => null],
                    'usap_linen' => ['satuan' => 'CFU/cm²', 'nilai' => null]
                ];
            case self::JENIS_LIMBAH_CAIR:
                return [
                    'ph' => ['satuan' => 'pH', 'nilai' => null],
                    'bod' => ['satuan' => 'mg/L', 'nilai' => null],
                    'cod' => ['satuan' => 'mg/L', 'nilai' => null],
                    'tss' => ['satuan' => 'mg/L', 'nilai' => null],
                    'minyak_lemak' => ['satuan' => 'mg/L', 'nilai' => null],
                    'amoniak' => ['satuan' => 'mg/L', 'nilai' => null],
                    'total_coliform' => ['satuan' => 'MPN/100ml', 'nilai' => null]
                ];
            default:
                return [];
        }
    }

    /**
     * Accessor untuk status laporan dalam bentuk text
     */
    public function getStatusTextAttribute()
    {
        switch ($this->status_laporan_lab) {
            case 0:
                return 'Draft';
            case 1:
                return 'Submitted';
            case 2:
                return 'Approved';
            case 3:
                return 'Rejected';
            default:
                return 'Unknown';
        }
    }

    /**
     * Accessor untuk nama periode dalam bahasa Indonesia
     */
    public function getPeriodeNamaIdAttribute()
    {
        $bulanIndonesia = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        return $bulanIndonesia[$this->periode] ?? '';
    }
}