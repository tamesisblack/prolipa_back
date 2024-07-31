<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialApoyo extends Model
{
    protected $table = "material";
    protected $primaryKey = 'idmaterial';
    protected $fillable = [
        'nombrematerial',
        'descripcionmaterial',
        'zipmaterial',
        'webmaterial',
        'exematerial',
        'Estado_idEstado',
    ];
	public $timestamps = false;
}
