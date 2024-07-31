<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    protected $table = "libro";
    protected $primaryKey = 'idlibro';
    protected $fillable = [
        'nombrelibro',
        'descripcionlibro',
        'serie',
        'ziplibro',
        'weblibro',
        'exelibro',
        'pdfconguia',
        'pdfsinguia',
        'guiadidactica',
        'asignatura_idasignatura',
        'Estado_idEstado',
    ];
	public $timestamps = false;
}
