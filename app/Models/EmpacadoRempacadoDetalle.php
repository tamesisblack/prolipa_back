<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpacadoRempacadoDetalle extends Model
{
    use HasFactory;
    protected $table = "empacado_rempacado_detalle";
    protected $fillable = [
        'det_empa_codigo',
        'empacado_rempacado_id',
        'idempresa',
        'tip_empa_codigo',
        'dete_estado',
        'user_created',
        'cantidad',
    ];
}
