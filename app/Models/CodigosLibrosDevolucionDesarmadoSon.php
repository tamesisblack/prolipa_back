<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigosLibrosDevolucionDesarmadoSon extends Model
{
    use HasFactory;
    protected $table = 'codigoslibros_devolucion_desarmados_son';
    protected $primaryKey = 'id';

    protected $fillable = [
        'codigoslibros_devolucion_desarmados_header_id',
        'libro_id',
        'codigo',
        'estado_liquidacion',
        'liquidado_regalado',
        'precio',
        'estado',
    ];

    public function devolucionHeader()
    {
        return $this->belongsTo(CodigosLibrosDevolucionDesarmadoHeader::class, 'codigoslibros_devolucion_desarmados_header_id');
    }
}
