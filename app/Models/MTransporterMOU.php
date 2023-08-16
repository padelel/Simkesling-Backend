<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTraits;

class MTransporterMOU extends Model
{
    use HasFactory, UuidTraits;

    protected $table = 'tbl_transporter_mou';
    protected $primaryKey = 'id_transporter_mou';
}
