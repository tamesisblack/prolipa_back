<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;


    protected $table = "institucion_cargos";
    protected $primaryKey = 'id';
    protected $fillable=[
        'cargo',
        'usuario_editor',
        'estado',
     
    ];
}
