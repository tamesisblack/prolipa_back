<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Institucion extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = "institucion";
    protected $primaryKey = 'idInstitucion';
    protected $fillable = [
        'ruc',
        'nombreInstitucion',
        'telefonoInstitucion',
        'direccionInstitucion',
        'fecha_registro',
        'solicitudInstitucion',
        'vendedorInstitucion',
        'imgenInstitucion',
        'ciudad_id',
        'region_idregion',
        'estado_idEstado',
        'idcreadorinstitucion',
        'ideditor',
        'periodoescolar',
        'updated_at',
        'created_at',
        'email',
    ];
    public $timestamps = false;

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id');
    }
    public function asesor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'asesor_id','idusuario');
    }
    public function representante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'idrepresentante','idusuario');
    }
}
