<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _1_4TipoEmpaque extends Model
{
    use HasFactory;
    protected $table = "1_4_tipo_empaque";
    protected $primaryKey = "tip_empa_codigo";
    //quitar el autoincrement
    public $incrementing = false;
}
