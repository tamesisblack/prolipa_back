<?php

namespace App\Http\Controllers;
use App\Models\DetalleOrdenTrabajo;

use DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DetalleOrdenTrabajoController extends Controller
{
    //
     public function Get_Ordendetalle(Request $request){
        $query = DB::SELECT("
            SELECT *
            FROM 1_1_detalle_orden_trabajo AS dot
            INNER JOIN 1_4_cal_producto AS p ON dot.pro_codigo = p.pro_codigo
            WHERE dot.or_codigo = ?
            ORDER BY
                -- Extraer base del código (quita G si lo tiene)
                CASE
                    WHEN p.pro_codigo LIKE 'G%' THEN SUBSTRING(p.pro_codigo, 2)
                    ELSE p.pro_codigo
                END ASC,
                -- Mostrar primero grupo 1, luego grupo 2
                p.gru_pro_codigo ASC
        ", [$request->or_codigo]);

        return $query;
    }
    public function Get_empresaOrden(Request $request){
        $query = DB::SELECT("SELECT e.* FROM 1_1_orden_trabajo ot
        INNER JOIN empresas e ON e.id = ot.or_empresa
        where ot.or_codigo ='$request->or_codigo'");
             return $query;
    }
    public function get_detalleOrdenTrabajo(Request $request){
        $query = DB::SELECT("SELECT * FROM  1_1_detalle_orden_trabajo as dot
        inner join 1_4_cal_producto as p on dot.pro_codigo=p.pro_codigo
        where or_codigo='$request->or_codigo'");

             return $query;
    }
    public function get_detalleOrdenTrabajoCantidades(Request $request){
        $query = DB::SELECT("SELECT dot.or_codigo, p.pro_codigo, SUM(dcd.det_com_cantidad) AS det_com_cantidad
        FROM 1_1_detalle_orden_trabajo AS dot
        INNER JOIN 1_4_cal_producto AS p ON dot.pro_codigo = p.pro_codigo
        INNER JOIN 1_4_cal_detalle_compra AS dcd ON p.pro_codigo = dcd.pro_codigo
        INNER JOIN 1_4_cal_compra AS cc ON dot.or_codigo = cc.orden_trabajo
        WHERE dot.pro_codigo = dcd.pro_codigo
        AND dcd.com_codigo = cc.com_codigo
        AND dot.or_codigo = '$request->or_codigo'
        GROUP BY dot.or_codigo, p.pro_codigo;");

             return $query;
    }
     public function PostOrdenDetalle_Registra(Request $request)
       {
        //variables
        // $cod = $request->or_codigo;
        $contador=0;
        $contadornoingresado=0;
        $arreglodatosnoingresado=collect();
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $miarray=json_decode($request->data_detalleorden);
        foreach($miarray as $key => $item){

                DetalleOrdenTrabajo::create(array(
                'or_codigo' => $request->or_codigo,
                'pro_codigo' => $item->pro_codigo,
                'det_or_cantidad' => $item->cantidad,
                'det_or_posible_entrega' => $item->fe_entrega,
                'det_or_observaciones' => $item->observacion,
                'det_or_tamaño' => $item-> pro_tamaño,
                'det_or_int_paginas' => $item->pro_int_pagina,
                'det_or_in_codigo' => $item->mat_in_codigo,
                'det_or_in_tintas' => $item-> pro_int_tinta,
                'mat_cub_codigo' => $item-> mat_cub_codigo,
                'det_or_cub_tintas' => $item->pro_cub_tintas,
                'det_or_acabados' =>$item->pro_acabados,
                'det_or_recubrimiento' => $item->pro_cub_recubrimiento,
             ));
        }
    }
    public function PostOrdenDetalle_Editar(Request $request)
       {

        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $miarray=json_decode($request->data_detalleorden);
        foreach($miarray as $key => $item){

                $orden = DetalleOrdenTrabajo::findOrFail($item->det_or_codigo);
                    $orden->det_or_cantidad = $item->det_or_cantidad;
                    $orden->det_or_posible_entrega = $item->det_or_posible_entrega;
                    $orden->det_or_observaciones = $item->det_or_observaciones;

                    $orden->save();
        }
        if($orden){
                        return $orden;
                    }else{
                        return "No se pudo actualizar";
                    }




    }
     public function Eliminar_OrdenDetalle(Request $request)
    {
        if ($request->det_or_codigo) {
            $orden = DetalleOrdenTrabajo::find($request->det_or_codigo);

            if (!$orden) {
                return "El det_or_codigo no existe en la base de datos";
            }


            $orden->delete();

            return $orden;
        } else {
            return "No está ingresando ningún det_or_codigo";
        }


    }

}
