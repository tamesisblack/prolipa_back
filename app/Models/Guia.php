<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guia extends Model
{
    protected $table = "guia";
    protected $fillable = [
        'nombreguia',
        'descripcionguia',
        'zipguia',
        'webguia',
        'exeguia',
        'pdfconguia',
        'pdfsinguia',
        'guiadidactica',
        'asignatura_idasignatura',
        'Estado_idEstado',
    ];
	public $timestamps = false;
}
