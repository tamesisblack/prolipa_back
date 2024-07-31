<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguimientoMuestra extends Model
{
    use HasFactory;
    protected $table = "seguimiento_muestra";
    protected $primaryKey = 'muestra_id';
    protected $fillable = [
        'num_muestra',
        'institucion_id',
        'asesor_id',
        'usuario_editor',
        'fecha_entrega',
        'observacion',
        'periodo_id',
        'persona_solicita',
        'foto_evidencia',
        'foto_devolucion',
        'fecha_devolucion',
        'comentario_devolucion',
        'estado',
        
    ];
	public $timestamps = false;

}
