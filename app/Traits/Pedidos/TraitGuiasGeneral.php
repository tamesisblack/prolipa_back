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
    public function tr_pedidoxLibro($request){
        $query = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro
        FROM libros_series ls
        LEFT JOIN libro l ON ls.idLibro = l.idlibro
        LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
        WHERE ls.id_serie = '$request->id_serie'
        AND a.area_idarea  = '$request->id_area'
        AND l.Estado_idEstado = '1'
        AND a.estado = '1'
        AND ls.year = '$request->libro'
        LIMIT 1
       ");
       return $query;
    }
    public function tr_pedidoxLibroPlanLector($request){
        $query = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro
        FROM libros_series ls
        LEFT JOIN libro l ON ls.idLibro = l.idlibro
        LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
        WHERE ls.id_serie = '6'
        AND l.Estado_idEstado = '1'
        AND a.estado = '1'
        AND l.idlibro = '$request->plan_lector'
        LIMIT 1
        ");
        return $query;
    }
}
