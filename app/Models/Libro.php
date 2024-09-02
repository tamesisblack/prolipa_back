<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    protected $table = "libro";
    protected $primaryKey = 'idlibro';
    protected $fillable = [
        'nombrelibro',
        'nombre_imprimir',
        'descripcionlibro',
        'serie',
        'titulo',
        'portada',
        'weblibro',
        'exelibro',
        'pdfsinguia',
        'pdfconguia',
        'guiadidactica',
        'Estado_idEstado',
        'asignatura_idasignatura',
        'ziplibro',
        'grupo',
        'puerto',
        's_weblibro',
        's_pdfsinguia',
        's_pdfconguia',
        's_guiadidactica',
        's_portada',
        'c_weblibro',
        'c_pdfsinguia',
        'c_pdfconguia',
        'c_guiadidactica',
        'c_portada',
        'demo',
        'creado_at',
        'actualizado_at',
    ];
	public $timestamps = false;
}
