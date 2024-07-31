<?php

namespace App\Traits\Pedidos;

use App\Models\Models\Pagos\FormasPagos;
use App\Models\Models\Pagos\TipoPagos;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Repositories\pedidos\VerificacionRepository;
use App\Repositories\PedidosPagosRepository;
use DB;
use Illuminate\Support\Facades\Http;
trait TraitPagosGeneral
{
    private $pagoRepository;
    private $verificacionRepository;
    public function __construct(PedidosPagosRepository $repositorio,VerificacionRepository $verificacionRepository)
    {
     $this->pagoRepository          = $repositorio;
     $this->verificacionRepository  = $verificacionRepository;
    }
    public function obtenerTiposPagos(){
        $query = TipoPagos::all();
        return $query;
    }
    public function obtenerFormasPagos(){
        $query = FormasPagos::all();
        return $query;
    }
    public function aprobarAnticipoPedidoPago($id_pedido,$valor){
        $query = $this->pagoRepository->getPagosInstitucion(0,0,5,'id_pedido',$id_pedido,'ifAntAprobado',1,1)
        ->update([
            // "estado"                         => 1,
            "doc_valor"                      => $valor
        ]);
    }
}
