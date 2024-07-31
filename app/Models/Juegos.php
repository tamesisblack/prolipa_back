<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Juegos extends Model
{
    protected $table = "juegos";
    protected $primaryKey = 'idjuegos';
    protected $fillable = [
        'nombre',
        'descripcion',
        'carpeta',
        'asignatura_idasignatura'
    ];
	public $timestamps = false;
}
