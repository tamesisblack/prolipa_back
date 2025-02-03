<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoHistoricoActas extends Model
{
    use HasFactory;
    protected $table = "pedidos_historico_actas";
    //fillable
    protected $fillable = [
        'cantidad',
        'ven_codigo',
        'pro_codigo',
        'stock_anterior',
        'nuevo_stock',
        'stock_anterior_empresa',
        'nuevo_stock_empresa',
        'id_pedido',
    ];
}
