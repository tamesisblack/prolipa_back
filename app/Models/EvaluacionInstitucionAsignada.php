<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionInstitucionAsignada extends Model
{
    use HasFactory;
    protected $table        = 'institucion_evaluacion_asignada';
    protected $primaryKey   = 'id';
    //relacion
    public function editor(){
        return $this->belongsTo('App\Models\Usuario', 'user_created', 'idusuario');
    }
}
