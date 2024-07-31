<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidosAnticiposSolicitados extends Model
{
    use HasFactory;
    protected $table = "pedidos_anticipos_solicitados";
    protected $primaryKey = "id";
}
