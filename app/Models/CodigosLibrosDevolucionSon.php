<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigosLibrosDevolucionSon extends Model
{
    use HasFactory;
    protected $table = 'codigoslibros_devolucion_son';
    protected $primaryKey = 'id';
    protected $fillable = [
        'codigoslibros_devolucion_id',
        'id_devolucion',
        'pro_codigo',
        'combo_cantidad',
        'id_cliente',
        'id_empresa',
        'documento',
        'id_periodo',
        'id_libro',
        'precio',
        'tipo_codigo',
        'combo',
    ];
    //relacion con la tabla codigos
    public function codigos()
    {
        return $this->belongsTo(CodigosLibros::class, 'codigo');
    }
}
