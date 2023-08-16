<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTraits;

class MLaporanBulananB3Padat extends Model
{
    use HasFactory, UuidTraits;

    protected $table = 'tbl_laporan_bulanan_b3padat';
    protected $primaryKey = 'id_laporan_bulanan_b3padat';
}
