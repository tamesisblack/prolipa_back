<?php

namespace App\Http\Controllers;
use App\Models\OrdenTrabajo;
use App\Models\DetalleOrdenTrabajo;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrdenTrabajoController extends Controller
{
    //
     public function Get_OrdenTrabajo(){
        $query = DB::SELECT("SELECT * FROM 1_1_orden_trabajo ORDER BY or_fecha desc limit 10");
        
             return $query;
    }
     public function Get_Codigo(){
        $query1 = DB::SELECT("SELECT  ord.or_codigo AS cod FROM 1_1_orden_trabajo as ord INNER JOIN 1_1_detalle_orden_trabajo as det ON ord.or_codigo = det.or_codigo
ORDER BY or_fecha DESC LIMIT 1");
        $getSecuencia = 1;
             if(!empty($query1)){
                $cod= $query1[0]->cod;
                $codi = explode("-", $cod);
                $getSecuencia=$codi[3]+1;
                $pre=$codi[0];
                if($getSecuencia>0 && $getSecuencia<10){
                    $secuencia = "000000".$getSecuencia;
                } else if($getSecuencia>99 && $getSecuencia<1000){
                    $secuencia = "0000".$getSecuencia;
                }else if($getSecuencia>999 && $getSecuencia<10000){
                    $secuencia = "0000".$getSecuencia;
                }else if($getSecuencia>9999 && $getSecuencia<100000){
                    $secuencia = "000".$getSecuencia;
                }else if($getSecuencia>99999 && $getSecuencia<1000000){
                    $secuencia = "00".$getSecuencia;
                }else if($getSecuencia>999999 && $getSecuencia<10000000){
                    $secuencia = "0".$getSecuencia;
                }else if($getSecuencia>9999999 && $getSecuencia<100000000){
                    $secuencia = $getSecuencia;
                }
             }
             $array = array($pre,$secuencia);
             return $array;
    }
    public function GetProductxfiltro(Request $request){
        if($request->pro_nombre){
            $query = DB::SELECT("SELECT pro_codigo, pro_nombre FROM 1_4_cal_producto AS p
            INNER JOIN 1_4_cal_producto_caracteristica as pc ON p.pro_codigo=pc.pro_car_codigo
            WHERE p.pro_nombre like'%$request->pro_nombre%'");
            return $query;
        }else{
            return "No existe registro";
       }

    }

     public function GetProd(){
        
            $query = DB::SELECT("SELECT pro_codigo, pro_nombre FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            inner join 1_4_cal_producto_caracteristica c on p.pro_codigo=pro_car_codigo");
            return $query;
        
    }

    public function GetProvxfiltro(Request $request){
        if($request->prov_codigo){
            $query = DB::SELECT("SELECT pro_codigo, pro_nombre FROM 1_4_cal_producto 
            WHERE prov_codigo='$request->prov_codigo'");
            return $query;
        }else{
            return "No existe registro";
       }

    }
     public function GetProductoCaracter(Request $request){
        if($request->pro_codigo){
            $query = DB::SELECT("SELECT pro_codigo, pro_nombre, pro_tamaño, pro_int_pagina, mat_in_codigo, pro_int_tinta, mat_cub_codigo,pro_cub_recubrimiento,
            pro_cub_tintas, pro_acabados FROM 1_4_cal_producto AS pro INNER JOIN 1_4_cal_producto_caracteristica AS car ON pro.pro_codigo=car.pro_car_codigo
            WHERE pro_codigo='$request->pro_codigo'");
            return $query;
        }else{
            return "No existe registro";
       }
    }

    public function GetOrden_xfiltro(Request $request){
        if ($request->busqueda == 'codigo') {
            $query = DB::SELECT("SELECT o.*, pro.*, e.descripcion_corta AS nombreEmpresa FROM 1_1_orden_trabajo o 
            INNER JOIN 1_4_proveedor pro ON o.prov_codigo= pro.prov_codigo
            LEFT JOIN empresas e ON e.id = o.or_empresa
            WHERE or_codigo LIKE '%$request->razonbusqueda%'");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT o.*, pro.*, e.descripcion_corta AS nombreEmpresa FROM 1_1_orden_trabajo o 
            INNER JOIN 1_4_proveedor pro ON o.prov_codigo= pro.prov_codigo
            LEFT JOIN empresas e ON e.id = o.or_empresa
            WHERE or_codigo LIKE '%$request->razonbusqueda%'");
            return $query;
        }
        if ($request->busqueda == 'temporada') {
            $query = DB::SELECT("SELECT o.*, pro.*, e.descripcion_corta AS nombreEmpresa FROM 1_1_orden_trabajo o 
                     INNER JOIN 1_4_proveedor pro ON o.prov_codigo= pro.prov_codigo
                     LEFT JOIN empresas e ON e.id = o.or_empresa
                     WHERE or_codigo LIKE '%$request->razonbusqueda%'");

            return $query;
        }
        if ($request->busqueda == 'pendiente') {
            $query = DB::SELECT("SELECT o.*, pro.*, e.descripcion_corta AS nombreEmpresa FROM 1_1_orden_trabajo o 
            INNER JOIN 1_4_proveedor pro ON o.prov_codigo= pro.prov_codigo
            LEFT JOIN empresas e ON e.id = o.or_empresa
            WHERE or_estado =1 ORDER BY or_fecha DESC limit 100");
            return $query;
        }
        if ($request->busqueda == 'finalizado') {
            $query = DB::SELECT("SELECT o.*, pro.*, e.descripcion_corta AS nombreEmpresa FROM 1_1_orden_trabajo o 
            INNER JOIN 1_4_proveedor pro ON o.prov_codigo= pro.prov_codigo
            LEFT JOIN empresas e ON e.id = o.or_empresa
            WHERE or_estado =2 ORDER BY or_fecha DESC limit 100");
            return $query;
        }
        if ($request->busqueda == 'orden') {
            $query = DB::SELECT("SELECT o.*, pro.*, e.descripcion_corta AS nombreEmpresa FROM 1_1_orden_trabajo o 
                     INNER JOIN 1_4_proveedor pro ON o.prov_codigo= pro.prov_codigo
                     LEFT JOIN empresas e ON e.id = o.or_empresa
                     WHERE or_codigo LIKE '%$request->razonbusqueda%'");
            return $query;
        }
    }
    public function PostOrden_Registrar_modificar(Request $request)
    {
        try {
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
    
            DB::beginTransaction();
    
            $miarray = json_decode($request->data_detalleorden);
    
            $orden = OrdenTrabajo::where('or_codigo', $request->or_codigo)->first();
    
            if ($orden) {
                // Si la orden existe, actualizamos
                $orden->or_empresa = $request->or_empresa;
                $orden->or_observacion = $request->or_observacion;
                $orden->or_aprobacion = $request->or_aprobacion;
                $orden->or_solicitado = $request->or_solicitado;
                $orden->or_elaborado = $request->or_elaborado;
                $orden->save();
    
                foreach ($miarray as $item) {
                    $detalle = DetalleOrdenTrabajo::findOrFail($item->det_or_codigo);
                    $detalle->det_or_cantidad = $item->det_or_cantidad;
                    $detalle->det_or_posible_entrega = $item->det_or_posible_entrega;
                    $detalle->det_or_observaciones = $item->det_or_observaciones;
                    $detalle->save();
                }
            } else {
                // Si la orden no existe, creamos una nueva
                $orden = new OrdenTrabajo;
                $orden->or_codigo = $request->or_codigo;
                $orden->usu_codigo = $request->usu_codigo;
                $orden->or_fecha = $request->or_fecha;
                $orden->prov_codigo = $request->prov_codigo;
                $orden->or_estado = $request->or_estado;
                $orden->or_empresa = $request->or_empresa;
                $orden->or_observacion = $request->or_observacion;
                $orden->or_aprobacion = $request->or_aprobacion;
                $orden->or_solicitado = $request->or_solicitado;
                $orden->or_elaborado = $request->or_elaborado;
                $orden->user_created = $request->user_created;
                $orden->updated_at = now();
                $orden->save();
    
                foreach ($miarray as $item) {
                    DetalleOrdenTrabajo::create([
                        'or_codigo' => $request->or_codigo,
                        'pro_codigo' => $item->pro_codigo,
                        'det_or_cantidad' => $item->cantidad,
                        'det_or_posible_entrega' => $item->fe_entrega,
                        'det_or_observaciones' => $item->observacion,
                        'det_or_tamaño' => $item->pro_tamaño,
                        'det_or_int_paginas' => $item->pro_int_pagina,
                        'det_or_in_codigo' => $item->mat_in_codigo,
                        'det_or_in_tintas' => $item->pro_int_tinta,
                        'mat_cub_codigo' => $item->mat_cub_codigo,
                        'det_or_cub_tintas' => $item->pro_cub_tintas,
                        'det_or_acabados' => $item->pro_acabados,
                        'det_or_recubrimiento' => $item->pro_cub_recubrimiento,
                    ]);
                }
            }
    
            DB::commit();
    
            return response()->json($orden, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo guardar/actualizar la orden'
            ], 500);
        }
    }    
     public function Desactivar_Orden(Request $request)
    {
        if ($request->or_codigo) {
            $orden= OrdenTrabajo::find($request->or_codigo);

            if (!$orden){
                return "El or_codigo no existe en la base de datos";
            }

            $orden->or_estado = $request->or_estado;
            $orden->save();

            return $orden;       
         } else {
            return "No está ingresando ningún or_codigo";
        }
    }
    public function Eliminar_Orden(Request $request)
    {
        if ($request->or_codigo) {
            $orden = DetalleOrdenTrabajo::Where('or_codigo',$request->or_codigo)->get();
            $cont=count($orden);
            
                if (!$orden) {
                    return "El or_codigo no existe en la base de datos11";
                }else{
                    $orden = DetalleOrdenTrabajo::Where('or_codigo',$request->or_codigo)->delete();
                    
                }
            $orden = DetalleOrdenTrabajo::Where('or_codigo',$request->or_codigo)->get();
            if(count($orden)==0){
                $ordent = OrdenTrabajo::find($request->or_codigo);
                
            if (!$ordent) {
                return "El or_codigo no existe en la base de datos";
            } else {
                $ordent->delete();
                return $ordent;
            }
            }
            
        

        }
    
        

    }



}
