<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigosDevolucion extends Model
{
    use HasFactory;
    protected $table = "codigos_devolucion";
    protected $primaryKey = 'id';
    protected $fillable=[
        'codigo','cliente','institucion_id','periodo_id','fecha_devolucion','observacion','usuario_editor',
    ];
}
