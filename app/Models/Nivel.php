<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    protected $table = "nivel";
    protected $primaryKey = 'idnivel';
    protected $fillable = [
        'nombrenivel',
        'ofertaacademica_idofertaAcademica',
        'estado'
    ];
	public $timestamps = false;
}
