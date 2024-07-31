<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $table = "video";
    protected $primaryKey = 'idvideo';
    protected $fillable = [
        'nombrevideo',
        'descripcionvideo',
        'webvideo',
        'asignatura_idasignatura',
        'Estado_idEstado',
    ];
	public $timestamps = false;
}
