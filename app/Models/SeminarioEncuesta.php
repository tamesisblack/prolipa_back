<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeminarioEncuesta extends Model
{
    use HasFactory;
    protected $table = "seminario_conteo_certificados";
    protected $primaryKey = 'id';
    protected $fillable = [
        'seminario_id',
        'usuario_id',
        'contador',
   
        
    ];
}
