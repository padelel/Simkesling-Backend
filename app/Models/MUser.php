<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\UuidTraits;
use Tymon\JWTAuth\Contracts\JWTSubject;

class MUser extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    use UuidTraits;

    protected $table = 'tbl_user';
    protected $primaryKey = 'id_user';

    protected $fillable = [
        'username', 'password', 'nama_user', 'level', 'noreg_tempat', 'tipe_tempat', 
        'nama_tempat', 'alamat_tempat', 'id_kelurahan', 'id_kecamatan', 'kelurahan', 
        'kecamatan', 'notlp', 'nohp', 'email', 'izin_ipal', 'izin_tps', 'status_user', 
        'statusactive_user', 'user_created', 'user_updated', 'uid', 'link_manifest', 
        'link_logbook', 'link_lab_ipal', 'link_lab_lain', 'link_dokumen_lingkungan_rs', 
        'link_izin_transporter', 'link_mou_transporter', 'link_swa_pantau', 
        'link_lab_limbah_cair', 'link1', 'link2', 'link3', 'link_izin_ipal', 
        'link_izin_tps', 'link_input_izin_ipal', 'link_input_izin_tps', 'link_ukl', 
        'link_upl', 'kapasitas_ipal', 'kapasitas_ipal_option', 'link_input_dokumen_lingkungan_rs'
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'id_user' => $this->id_user,
            'username' => $this->username,
            'nama_user' => $this->nama_user,
            'level' => $this->level,
            'noreg_tempat' => $this->noreg_tempat,
            'tipe_tempat' => $this->tipe_tempat,
            'nama_tempat' => $this->nama_tempat,
            'alamat_tempat' => $this->alamat_tempat,
            'kelurahan' => $this->kelurahan,
            'kecamatan' => $this->kecamatan,
            'notlp' => $this->notlp,
            'nohp' => $this->nohp,
            'email' => $this->email,
            'link' => $this->link,
            'uid' => $this->uid,
        ];
    }

    /**
     * Relationship dengan Laporan Bulanan
     */
    public function laporanBulanan()
    {
        return $this->hasMany(MLaporanBulanan::class, 'id_user', 'id_user');
    }

    /**
     * Relationship dengan Limbah Cair
     */
    public function limbahCair()
    {
        return $this->hasMany(MLimbahCair::class, 'id_user', 'id_user');
    }

    /**
     * Scope untuk filter user aktif
     */
    public function scopeActive($query)
    {
        return $query->where('statusactive_user', 1);
    }

    /**
     * Scope untuk filter berdasarkan level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Accessor untuk mendapatkan nama level
     */
    public function getLevelNameAttribute()
    {
        $levels = [
            1 => 'Admin',
            2 => 'Rumah Sakit',
            3 => 'Puskesmas',
            4 => 'Tidak Aktif'
        ];
        
        return $levels[$this->level] ?? 'Unknown';
    }
}
