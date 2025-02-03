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
    public function tr_cantidadDevuelta($asesor_id, $pro_codigo, $periodo_id) {
        return DB::table('pedidos_guias_devolucion_detalle as gd')
            ->leftJoin('pedidos_guias_devolucion as d', 'd.id', '=', 'gd.pedidos_guias_devolucion_id')
            ->where('gd.asesor_id', $asesor_id)
            ->where('gd.pro_codigo', $pro_codigo)
            ->where('gd.periodo_id', $periodo_id)
            ->where('d.estado', '1')
            ->sum('gd.cantidad_devuelta') ?? 0;
    }
    
    public function tr_cantidadDevueltaPendiente($asesor_id, $pro_codigo, $periodo_id) {
        return DB::table('pedidos_guias_devolucion_detalle as gd')
            ->leftJoin('pedidos_guias_devolucion as d', 'd.id', '=', 'gd.pedidos_guias_devolucion_id')
            ->where('gd.asesor_id', $asesor_id)
            ->where('gd.pro_codigo', $pro_codigo)
            ->where('gd.periodo_id', $periodo_id)
            ->where('d.estado', '0')
            ->sum('gd.cantidad_devuelta') ?? 0;
    }
}
