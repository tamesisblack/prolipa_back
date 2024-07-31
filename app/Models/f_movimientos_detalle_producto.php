<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class f_movimientos_detalle_producto extends Model
{
    use HasFactory;
    protected $table = "f_movimientos_detalle_producto";
    protected $primaryKey = 'fmdp_id';
    public $timestamps = false;
    protected $fillable = [
        'fmp_id',
        'pro_codigo',
        'emp_id',
        'fmdp_cantidad',
        'fmdp_tipo_bodega',
    ];
}
