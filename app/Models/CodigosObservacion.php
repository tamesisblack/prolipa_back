<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigosObservacion extends Model
{
    use HasFactory;

    protected $table = "codigos_observacion";
    protected $primaryKey = 'id';
    protected $fillable = [
        'codigo',
        'observacion',
        'id_usuario',
        'idInstitucion',
        'usuario_editor',
        
    ];
}
