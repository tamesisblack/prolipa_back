<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoAlcanceHistorico extends Model
{
    use HasFactory;
    protected $table        = "pedidos_alcance_historico";
    protected $primaryKey   = "id";
}
