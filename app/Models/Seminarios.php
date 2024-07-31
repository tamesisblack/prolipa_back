<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seminarios extends Model
{
    use HasFactory;
    protected $table = "seminarios";
    protected $primaryKey = 'id_seminario';
    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'link_reunion',
        'id_institucion',
        'estado',
        'capacitador',
        'cant_asistentes',
        'asistencia_activa',
        'id_usuario',
        'tipo'
    ];

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class, 'id_institucion');
    }
    public function temporal(): BelongsTo
    {
        return $this->belongsTo(SeguimientoInstitucionTemporal::class, 'institucion_temporal_id','institucion_id_temporal');
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function capacitadores(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'seminarios_capacitador', 'seminario_id', 'idusuario');
    }
}
