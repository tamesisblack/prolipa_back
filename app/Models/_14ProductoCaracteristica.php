<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _14ProductoCaracteristica extends Model
{
    use HasFactory;
    protected $table = '1_4_cal_producto_caracteristica';

    protected $primaryKey = 'pro_car_codigo';
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'pro_car_codigo',
        'pro_tamaño',
        'pro_int_pagina',
        'mat_in_codigo',
        'pro_int_tinta',
        'mat_cub_codigo',
        'pro_cub_recubrimiento',
        'pro_cub_tintas',
        'pro_cub_codigos',
        'pro_guia',
        'updated_at',
        'user_created'
    ];
}
