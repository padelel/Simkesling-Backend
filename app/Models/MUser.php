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

    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [
            'id_user' => $this->id_user,
            'username' => $this->username,
            'nama_user' => $this->nama_user,
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
            'status_user' => $this->status_user,
            'statusactive_user' => $this->statusactive_user,
            'link_manifest'  => $this->link_manifest,
            'link_logbook'  => $this->link_logbook,
            'link_lab_ipal'  => $this->link_lab_ipal,
            'link_lab_lain'  => $this->link_lab_lain,
            'link_dokumen_lingkungan_rs'  => $this->link_dokumen_lingkungan_rs,
            'link_izin_transporter'  => $this->link_izin_transporter,
            'link_mou_transporter'  => $this->link_mou_transporter,
            'link_swa_pantau'  => $this->link_swa_pantau,
            'link_lab_limbah_cair'  => $this->link_lab_limbah_cair,
            'link_izin_ipal'  => $this->link_izin_ipal,
            'link_izin_tps'  => $this->link_izin_tps,
            'link_ukl'  => $this->link_ukl,
            'link_upl'  => $this->link_upl,
            'link1'  => $this->link1,
            'link2'  => $this->link2,
            'link3'  => $this->link3,
            'kapasitas_ipal'  => $this->kapasitas_ipal,
            'uid' => $this->uid
        ];
    }
}
