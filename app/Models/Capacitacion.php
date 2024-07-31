<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capacitacion extends Model
{
    use HasFactory;
    protected $table = "capacitacion_agenda";
    protected $primaryKey = 'id';
    protected $fillable=[
        'tema_id',
        'label',
        'title',
        'classes',
        'endDate',
        'startDate',
        'hora_inicio',
        'hora_fin',
        'institucion_id',
        'periodo_id',
        'id_usuario',
        'estado',
        'tipo',
        
    ];
    

}

