<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignaturaDocente extends Model
{
    protected $table = "asignaturausuario";
    protected $primaryKey = 'idasiguser';
	public $timestamps = true;
}
