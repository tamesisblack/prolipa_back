<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteVersionadoCodigos extends Model
{
    protected $table = 'reportes_versionados_codigos';

    protected $fillable = [
        'reporte_versionado_id',
        'codigo_libro',
        'cantidad',
        'codigo'
    ];

    public function reporteVersionado()
    {
        return $this->belongsTo(ReporteVersionado::class, 'reporte_versionado_id');
    }
}
