<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificacionhasLiquidacionBc extends Model
{
    use HasFactory;
    protected $table = "verificaciones_has_liquidacion_bc";
    protected $primaryKey = 'id_verificacion_inst';
    protected $fillable = [
        'num_verificacion',
        'contrato',
        'codigo',
        'cantidad',
        'desface',
        'nombre_libro',
        'estado'
        
    ];
	public $timestamps = false;
}
