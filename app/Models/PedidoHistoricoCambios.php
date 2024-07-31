<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoHistoricoCambios extends Model
{
    use HasFactory;
    protected $table = "pedidos_historico_cambios";
    protected $primaryKey = "id";
}
