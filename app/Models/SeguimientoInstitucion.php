<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguimientoInstitucion extends Model
{
    use HasFactory;
    protected $table = "seguimiento_cliente";
    protected $primaryKey = 'id';
    protected $fillable = [
        'num_visita',
        'institucion_id',
        'asesor_id',
        'usuario_editor',
        'tipo_seguimiento',
        'fecha_genera_visita',
        'fecha_que_visita',
        'observacion',
        'periodo_id',
        'estado',
    ];
	public $timestamps = false;
}
