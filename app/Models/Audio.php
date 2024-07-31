<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audio extends Model
{
    protected $table = "audio";
    protected $primaryKey = 'idaudio';
    protected $fillable = [
        'nombreaudio',
        'descripcionaudio',
        'webaudio',
        'asignatura_idasignatura',
        'Estado_idEstado',
    ];
	public $timestamps = false;
}
