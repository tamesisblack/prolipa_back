<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermisoSuper extends Model
{
    use HasFactory;
    protected $table = "permisos_super";
    protected $primaryKey = 'id';
    protected $fillable = [
        'usuario_id',
        'id_group',
    ];
	 
}
