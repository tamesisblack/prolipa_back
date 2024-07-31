<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraOrdenTrabajo extends Model
{
    use HasFactory;
    protected $table = "1_4_cal_compra";

    protected $primaryKey = 'com_codigo';
    public $timestamps = false;
    
    protected $fillable = [
        'com_codigo',
        'prov_codigo',
        'com_factura',
        'com_fecha',
        'com_valor',
        'com_responsable',
        'com_observacion',
        'com_iva',
        'com_empresa',
        'com_descuento',
        'orden_trabajo',
        'user_created',
        'updated_at',
       
    ];
}
