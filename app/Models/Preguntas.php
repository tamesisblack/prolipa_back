<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preguntas extends Model
{

    protected $table = 'preguntas';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function tema()
    {
        return $this->belongsTo(Temas::class, 'id_tema','id');
    }
}
