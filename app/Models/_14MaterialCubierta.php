<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _14MaterialCubierta extends Model
{
    use HasFactory;
    protected $table  = "1_4_cal_material_cubierta";

    protected $primaryKey = 'mat_cub_codigo';
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'mat_cub_codigo',
        'mat_cub_nombre',
        'mat_cub_gramaje',
        'mat_cub_estado',
    ];
}
