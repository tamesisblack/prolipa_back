<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Agenda extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = "agenda_usuario";
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_usuario',
        'title',
        'label',
        'classes',
        'startDate',
        'endDate',
        'hora_inicio',
        'hora_fin',
        'url',
        'institucion_id_temporal',
        'institucion_id',
        'opciones',
        'nombre_institucion_temporal'
        
    ];
}
