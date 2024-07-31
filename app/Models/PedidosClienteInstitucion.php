<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidosClienteInstitucion extends Model
{
    use HasFactory;
    protected $table = "pedidos_asesor_institucion_docente";
    protected $primaryKey = "id";
}
