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
}
