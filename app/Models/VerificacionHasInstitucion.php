<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificacionHasInstitucion extends Model
{
    use HasFactory;
    protected $table = "verificaciones_has_temporadas";
    protected $primaryKey = 'id_verificacion_inst';
    protected $fillable = [
        'verificacion_id',
        'contrato',
        'codigo',
        'cantidad',
        'desface',
        'nombre_libro',
        'estado',
        'nuevo',
        
    ];
	public $timestamps = false;
}
