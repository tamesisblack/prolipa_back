<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProyectoAsignatura extends Model
{
    use HasFactory;
    protected $table = "proyecto_asignatura";
    protected $primaryKey = 'pasignatura_id';
    protected $fillable = [
        'asignatura_id',
        'proyecto_id',
    ];
	public $timestamps = false;
}
