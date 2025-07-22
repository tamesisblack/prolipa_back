<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoGuiaDevolucion extends Model
{
    use HasFactory;
    protected $table = "pedidos_guias_devolucion";
    // relacion
    public function pedidos_guias_devolucion_detalle()
    {
        return $this->hasMany(PedidoGuiaDevolucionDetalle::class,'pedidos_guias_devolucion_id','id');
    }
}
