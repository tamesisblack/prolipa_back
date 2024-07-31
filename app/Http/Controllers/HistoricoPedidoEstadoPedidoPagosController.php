<?php

namespace App\Http\Controllers;

use App\Models\HistoricoPedido_EstadoPedidoPagos;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HistoricoPedidoEstadoPedidoPagosController extends Controller
{
    public function GetHistoricoEstadoPedido_todo(){
        $query = DB::SELECT("SELECT * FROM historico_pedido_estadopedidopagos ORDER BY hes_id ASC");
        return $query;
    }
}
