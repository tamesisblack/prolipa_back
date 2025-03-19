<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class CodigosLibros extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = "codigoslibros";
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $fillable=[
        'serie','libro','anio','contrato','idusuario','idusuario_creador_codigo','libro_idlibro','estado','fecha_create','id_periodo','created_at','updated_at',
        'estado_liquidacion',
        'verif1',
        'verif2',
        'verif3',
        'verif4',
        'verif5',
        'verif6',
        'verif7',
        'verif8',
        'verif9',
        'verif10',
        'bc_estado',
        'bc_fecha_ingreso',
        'bc_periodo',
        'bc_institucion'
    ];
    const CODIGO_LIQUIDADO          = 'El código se encuentra liquidado';
    const CODIGO_REGALADO           = 'El código se encuentra regalado';
    const CODIGO_GUIA               = 'El código se encuentra como GUIA';
    const CODIGO_DEVUELTO           = 'El código se encuentra como DEVUELTO';
    const CODIGO_LIQUIDADO_REGALADO = 'El código se encuentra liquidado y regalado';
    const CODIGO_BLOQUEADO          = 'El código se encuentra bloqueado';

    const CODIGO_ACTIVACION         = 'Problema con el código de activación';
    const CODIGO_DIAGNOSTICA        = 'Problema con el código de diagnóstico';
    const CODIGO_AMBOS              = 'Problema con el código de ambos';

    CONST CODIGO_CON_INSTITUCION    = 'El código se encuentra con la institución';
    public function libroSeries()
    {
        return $this->hasMany(LibroSerie::class, 'idLibro', 'libro_idlibro');
    }
}
