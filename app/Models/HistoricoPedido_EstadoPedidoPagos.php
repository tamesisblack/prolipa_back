<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoPedido_EstadoPedidoPagos extends Model
{
    use HasFactory;
    protected $table  ='historico_pedido_estadopedidopagos';

    protected $primaryKey = 'hes_id';
    public $timestamps = false;

    protected $fillable = [
        'hes_id',
        'hes_contrato',
        'user_created',
        'created_at',
        'updated_at'
    ];
}
