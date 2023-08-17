<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTraits;

class MTransporterTmpMOU extends Model
{
    use HasFactory, UuidTraits;

    protected $table = 'tbl_transporter_tmp_mou';
    protected $primaryKey = 'id_transporter_tmp_mou';
}
