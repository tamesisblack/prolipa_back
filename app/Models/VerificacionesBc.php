<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificacionesBc extends Model
{
    use HasFactory;
    protected $table = "verificaciones_bc";
    protected $primaryKey = 'id';
    protected $fillable = [
        'num_verificacion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        
    ];
	public $timestamps = false;
}
