<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeminarioHasUsuario extends Model
{
    use HasFactory;
    protected $table = "seminario_has_usuario";
    protected $primaryKey = 'seminario_has_usuario_id';
    protected $fillable = [
        'usuario_id',
        'seminario_id',
        'institucion_nombre',
        'asistencia',
        'institucion_id',
        'certificado_cont',
        
    ];
}
