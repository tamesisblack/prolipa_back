<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenTrabajo extends Model
{
    use HasFactory;
    protected $table = '1_1_orden_trabajo';
	

    protected $primaryKey = 'or_codigo';
    public $timestamps = false;

    protected $fillable = [
        'or_codigo',
        'usu_codigo',
        'or_fecha', 
        'prov_codigo',
        'or_estado',
        'or_empresa',        
        'or_observacion',
        'or_aprobacion',
        'or_solicitado',
        'or_elaborado',      
    ];
}
