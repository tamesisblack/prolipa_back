<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoConvenio extends Model
{
    use HasFactory;
    protected $table        = "pedidos_convenios";
    protected $primarykey   = "id";

}
