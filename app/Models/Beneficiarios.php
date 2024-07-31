<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiarios extends Model
{
    protected $table = "pedidos_beneficiarios";
    protected $primaryKey = 'id_beneficiario_pedido';
}
