<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormularioDocente extends Model
{
    use HasFactory;
    protected $table = "docentes_formulario";
    protected $primaryKey = 'id';
    protected $fillable = [
        'idusuario','institucion_id','estado'
    ];
}
