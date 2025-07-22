<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoPagosDetalle extends Model
{
    use HasFactory;
    protected $table = 'pedidos_pagos_detalles';
    protected $primaryKey = 'id_pedido_pago_detalle';
}
