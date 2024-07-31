<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallePedidoLibroObsequio extends Model
{
    use HasFactory;
    protected $table = "p_detalle_libros_obsequios";


    protected $primaryKey = 'id_detalle';
    // public $timestamps = false;

    protected $fillable = [
        'id_detalle',
        'p_libros_obsequios_id',
        'pro_codigo',
        'p_libros_cantidad',
        'p_libros_valor_u',
        'p_libros_cantidad_pendiente',
        'created_at',
        'updated_at',
        'user_created',
    ];
}
