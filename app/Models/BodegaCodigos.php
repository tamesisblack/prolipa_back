<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BodegaCodigos extends Model
{
    use HasFactory;
    protected $table = "bodega_codigos";
    protected $primaryKey = 'id';
    protected $fillable=[
        'codigo','libro','anio','contrato','idusuario','idusuario_creador_codigo','libro_idlibro','estado','fecha_create','id_periodo','created_at','updated_at',
        'bc_estado',
        'bc_fecha_ingreso',
        'bc_periodo',
        'bc_institucion'
    ];
}
