<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoCodigos extends Model
{
    use HasFactory;
    protected $table = 'hist_codlibros';
    protected $primaryKey = 'id_codlibros';
    protected $fillable = [
        'id_usuario',
        'codigo_libro', 
        'idInstitucion',
        'usuario_editor',
        'observacion', 
        'id_periodo',
        'contrato_anterior',
        'contrato_actual',
        'verificacion_id',
        'verificacion_columna',
        'b_estado',


    ];
   
}
