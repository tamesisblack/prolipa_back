<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalleAsignaturas extends Model
{
    protected $table = "salle_asignaturas";
    protected $primaryKey = 'id_asignatura';
    public function area()
    {
        return $this->belongsTo(SalleAreas::class, 'id_area', 'id_area');
    }

    public function preguntas()
    {
        return $this->hasMany(SallePreguntas::class, 'id_asignatura', 'id_asignatura');
    }
}
