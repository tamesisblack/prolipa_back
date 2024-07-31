<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidosSolicitudesGerencia extends Model
{
    use HasFactory;
    protected $table = "pedidos_solicitudes_gerencia";
    protected $primaryKey = "id";
}
