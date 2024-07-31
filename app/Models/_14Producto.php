<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _14Producto extends Model
{
    use HasFactory;
    protected $table  ="1_4_cal_producto";

    protected $primaryKey = 'pro_codigo';
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'pro_codigo',
        'gru_pro_codigo',
        'pro_nombre',
        'pro_descripcion',
        'pro_iva',
        'pro_valor',
        'pro_descuento',
        'pro_stock',
        'pro_stockCalmed',
        'pro_reservar',
        'pro_deposito',
        'pro_depositoCalmed',
        'pro_costo',
        'pro_peso',
        'pro_estado',
        'user_created'
        // Agrega cualquier otro campo que sea fillable
    ];
}
