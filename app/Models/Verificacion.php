<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Verificacion extends Model
{
    use HasFactory;
    protected $table = "verificaciones";
    protected $primaryKey = 'id';
    protected $fillable = [
        'num_verificacion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        
    ];
	public $timestamps = false;

 
}
