<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoAnticipoAprobadoHistorico extends Model
{
    use HasFactory;
    protected $table = "pedidos_anticipos_aprobados_historico";
    protected $primaryKey = "id";
}
