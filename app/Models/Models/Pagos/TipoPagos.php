<?php

namespace App\Models\Models\Pagos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPagos extends Model
{
    use HasFactory;
    protected $table = "pedidos_tipo_pagos";
    protected $primaryKey = "id";
}
