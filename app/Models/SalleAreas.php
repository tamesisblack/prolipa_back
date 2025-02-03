<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalleAreas extends Model
{
    protected $table = "salle_areas";
    protected $primaryKey = 'id_area';

    public function asignaturas()
    {
        return $this->hasMany(SalleAsignaturas::class, 'id_area', 'id_area');
    }
}
