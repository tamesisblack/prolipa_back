<?php

namespace App\Models\Models\Pagos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificacionPago extends Model
{
    use HasFactory;
    protected $table = "verificaciones_pagos";
    protected $primaryKey ="verificacion_pago_id";
}
