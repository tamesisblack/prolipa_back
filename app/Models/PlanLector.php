<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanLector extends Model
{
    protected $table = "planlector";
    protected $fillable = [
        'nombreplanlector',
        'descripcionplanlector',
        'zipplanlector',
        'webplanlector',
        'exeplanlector',
        'pdfconguia',
        'pdfsinguia',
        'guiadidactica',
        'asignatura_idasignatura',
        'Estado_idEstado',
    ];
	public $timestamps = false;
}
