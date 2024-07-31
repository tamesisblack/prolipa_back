<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuaderno extends Model
{
    protected $table = "cuaderno";
    protected $primaryKey = 'idcuaderno';
    protected $fillable = [
        'nombrecuaderno',
        'descripcioncuaderno',
        'zipcuaderno',
        'webcuaderno',
        'execuaderno',
        'pdfconguia',
        'pdfsinguia',
        'guiadidactica',
        'asignatura_idasignatura',
        'Estado_idEstado',
    ];
	public $timestamps = false;
}
