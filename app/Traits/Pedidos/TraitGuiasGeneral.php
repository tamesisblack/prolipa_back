<?php

namespace App\Traits\Pedidos;

use App\Models\Pedidos;
use DB;
use Illuminate\Support\Facades\Http;
trait TraitGuiasGeneral
{
    public function tr_obtenerSecuenciaGuia($id){
        $secuencia = DB::SELECT("SELECT  * FROM f_tipo_documento d
        WHERE d.tdo_id = ?",[$id]);
        return $secuencia;
    }
    public function tr_guiasXEstado($estado_entrega){
        $query = DB::SELECT("SELECT p.id_pedido,p.ven_codigo,p.fecha_entrega_bodega
        FROM pedidos p
        WHERE p.tipo = '1'
        AND p.estado= '1'
        AND p.estado_entrega = ?
        ",[$estado_entrega]);
        return $query;
    }
}
