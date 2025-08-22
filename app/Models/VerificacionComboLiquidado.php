<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificacionComboLiquidado extends Model
{

    protected $table = 'verificaciones_combos_liquidados';
    protected $fillable = [
        'periodo_id',
        'contrato',
        'combo_etiqueta',
        'user_oculta',
    ];

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function verificacion()
    {
        return $this->belongsTo(Verificacion::class, 'verificacion_id');
    }
}
