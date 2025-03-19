<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpacadoRempacado extends Model
{
    use HasFactory;
    protected $table = "empacado_rempacado";
    protected $fillable = [
        'empa_codigo',
        'idempresa',
        'empa_fecha',
        'empa_libros',
        'empa_facturas',
        'empa_cartones',
        'usu_codigo',
        'remi_codigo',
        'user_created',
        'remision_id',
    ];
}
