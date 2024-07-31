<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proforma extends Model
{
    use HasFactory;
    protected $table = "f_proforma";
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'prof_id',
        'usu_codigo',
        'pedido_id',
        'emp_id',
        'idPuntoventa',
        'prof_observacion',
        'prof_observacion_libreria',
        'prof_com',
        'prof_descuento',
        'pro_des_por',
        'prof_iva',
        'prof_iva_por',
        'prof_total',
        'prof_estado',
        'user_editor',
        'prof_tipo_proforma',
        'created_at',
        'updated_at',
    ];
}
