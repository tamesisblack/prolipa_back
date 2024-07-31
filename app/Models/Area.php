<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = "area";
    protected $primaryKey = 'idarea';
    protected $fillable = [
        'nombrearea',
        'tipoareas_idtipoarea',
        'estado'
    ];
	public $timestamps = false;
}
