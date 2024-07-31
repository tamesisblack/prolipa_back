<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class J_opcionesContenidos extends Model
{
    use HasFactory;
    protected $table = "j_opciones_contenidos";
    protected $primaryKey = 'id_opcion_contenido';
}
