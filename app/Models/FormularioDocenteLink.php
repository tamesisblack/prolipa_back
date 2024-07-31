<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormularioDocenteLink extends Model
{
    use HasFactory;
    protected $table = "docentes_formulario_links";
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_created','link','institucion_id','periodo_id','observacion',
        'fecha_expiracion'
    ];
}
