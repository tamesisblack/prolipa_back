<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleVentas extends Model
{
    use HasFactory;
    protected $table = "f_detalle_venta";
    protected $primaryKey = 'det_ven_codigo';
    protected $fillable = [
        'det_ven_codigo',
        'ven_codigo',
        'id_empresa',
        'pro_codigo',
        'det_ven_cantidad',
        'det_ven_valor_u',
        'det_ven_cantidad_real',
    ];
	public $timestamps = false;

}
