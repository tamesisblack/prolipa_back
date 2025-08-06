<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Temas extends Model
{
    protected $table = 'temas';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'id_asignatura');
    }


}
