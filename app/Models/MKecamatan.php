<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTraits;

class MKecamatan extends Model
{
    use HasFactory, UuidTraits;

    protected $table = 'tbl_kecamatan';
    protected $primaryKey = 'id_kecamatan';
}
