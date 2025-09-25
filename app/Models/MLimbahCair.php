<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MLimbahCair extends Model
{
    use HasFactory;

    protected $table = 'tbl_limbah_cair';
    protected $primaryKey = 'id_limbah_cair';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'id_transporter',
        'nama_transporter',
        'id_user',
        'ph',
        'bod',
        'cod',
        'tss',
        'minyak_lemak',
        'amoniak',
        'total_coliform',
        'debit_air_limbah',
        'kapasitas_ipal',
        // Link Dokumen - hanya yang diperlukan
        'link_persetujuan_teknis',
        'link_ujilab_cair',
        // Legacy fields untuk backward compatibility
        'link_lab_ipal',
        'link_manifest',
        'link_logbook',
        'periode',
        'periode_nama',
        'tahun',
        'status_limbah_cair',
        'statusactive_limbah_cair',
        'user_created',
        'user_updated',
        'uid'
    ];

    protected $casts = [
        'ph' => 'decimal:2',
        'bod' => 'decimal:2',
        'cod' => 'decimal:2',
        'tss' => 'decimal:2',
        'minyak_lemak' => 'decimal:2',
        'amoniak' => 'decimal:2',
        'debit_air_limbah' => 'decimal:2',
        'total_coliform' => 'integer',
        'periode' => 'integer',
        'tahun' => 'integer',
        'status_limbah_cair' => 'integer',
        'statusactive_limbah_cair' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = Str::uuid();
            }
        });
    }

    // Relationship dengan User
    public function user()
    {
        return $this->belongsTo(MUser::class, 'id_user', 'id_user');
    }

    // Relationship dengan Transporter
    public function transporter()
    {
        return $this->belongsTo(MTransporter::class, 'id_transporter', 'id_transporter');
    }
}