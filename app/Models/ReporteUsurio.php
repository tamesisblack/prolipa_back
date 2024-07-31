<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteUsurio extends Model
{
   protected $table = "registro_usuario";
   protected $fillable = ['id_registro','ip','navegador','hora_ingreso_usuario', 'usuario_idusuario'];
}
