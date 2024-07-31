<?php

namespace App\Models\Models\Pagos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormasPagos extends Model
{
    use HasFactory;
    protected $table        = "pedidos_formas_pago";
    protected $primaryKey   = "tip_pag_codigo";
}
