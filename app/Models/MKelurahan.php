<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTraits;

class MKelurahan extends Model
{
    use HasFactory, UuidTraits;

    protected $table = 'tbl_kelurahan';
    protected $primaryKey = 'id_kelurahan';
}
