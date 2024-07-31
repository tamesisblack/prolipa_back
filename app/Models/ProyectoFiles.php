<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProyectoFiles extends Model
{
    use HasFactory;
    protected $table = "proyecto_files";
    protected $primaryKey = 'id';
    protected $fillable = [
        'archivo',
        'url',
        'tipo',
        'descripcion',
        'proyecto_id',
        'ext',
        'respuesta',
    ];
	public $timestamps = false;
}
