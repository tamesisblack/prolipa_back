<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificacionComboLiquidadoDetalle extends Model
{
    use HasFactory;

   protected $table = 'verificaciones_combos_liquidados_detalle';
   protected $fillable = [
       'verificaciones_combos_liquidados_id',
       'codigo',
       'combo_etiqueta',
       'combo',
       'contrato_liquidada',
       'institucion_liquidada',
       'periodo_liquidada',
       'contrato_relacion',
       'columna_verificacion',
       'verificacion_id'
   ];
}
