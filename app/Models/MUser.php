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
            'uid' => $this->uid
        ];
    }
}
