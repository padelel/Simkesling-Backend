<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTraits;

class MTransporter extends Model
{
    use HasFactory, UuidTraits;

    protected $table = 'tbl_transporter';
    protected $primaryKey = 'id_transporter';
}
