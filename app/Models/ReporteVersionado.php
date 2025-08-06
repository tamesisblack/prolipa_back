<?php
// app/Models/ReporteVersionado.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteVersionado extends Model
{
    protected $table = 'reportes_versionados';
    public $timestamps = false;

    protected $fillable = [
        'nombre_reporte', 'periodo_id', 'serie_id', 'total_codigos', 'version', 'user_created'
    ];

    public function codigos()
    {
        return $this->hasMany(ReporteVersionadoCodigos::class, 'reporte_versionado_id');
    }
}
