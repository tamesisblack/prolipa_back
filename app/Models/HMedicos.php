<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HMedicos extends Model
{
    // use HasFactory;
    protected $table = "hsp_usuarios";
    protected $primaryKey = 'idusuario';
}
