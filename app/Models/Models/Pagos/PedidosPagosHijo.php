<?php

namespace App\Models\Models\Pagos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidosPagosHijo extends Model
{
    use HasFactory;
    protected $table     = "pedidos_pagos_hijos";
    protected $guarded   = [];
}
