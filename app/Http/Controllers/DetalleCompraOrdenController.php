<?php

namespace App\Http\Controllers;

use App\Models\DetalleCompraOrden;
use App\Models\CompraOrdenTrabajo;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DetalleCompraOrdenController extends Controller
{
    //
     public function Get_Orden1detalle(Request $request){
        $compra1 = CompraOrdenTrabajo::Where('orden_trabajo',$request->or_codigo)->get();

         if(count($compra1)>0){
        $query = DB::SELECT("SELECT * FROM  1_1_detalle_orden_trabajo as dot
        inner join 1_4_cal_producto as p on dot.pro_codigo=p.pro_codigo
        where or_codigo='$request->or_codigo'");
         return $query;
         }else{
            $query = DB::SELECT("SELECT * FROM  1_1_detalle_orden_trabajo as dot
            inner join 1_4_cal_producto as p on dot.pro_codigo=p.pro_codigo
        where or_codigo='$request->or_codigo'");
        $array=$query;
             return $array;
         }
        
     }
    public function Get_ComparOrdendetalle(Request $request){
        $query = DB::SELECT("SELECT d.pro_codigo, d.det_com_cantidad, d.det_com_valor_u, 
        dot.det_or_cantidad, d.det_com_factura, d.det_com_nota, c.orden_trabajo, d.det_com_codigo, p.pro_nombre  
        FROM  1_4_cal_compra AS c 
        INNER JOIN 1_4_cal_detalle_compra AS d ON c.com_codigo=d.com_codigo 
        INNER JOIN 1_1_orden_trabajo AS o ON c.orden_trabajo=o.or_codigo 
        INNER JOIN 1_1_detalle_orden_trabajo as dot ON o.or_codigo=dot.or_codigo
        INNER JOIN 1_4_cal_producto as p on dot.pro_codigo=p.pro_codigo
        WHERE d.pro_codigo=dot.pro_codigo AND c.com_codigo=$request->com_codigo");
        
             return $query;
    }
    public function Get_ComprasOrden(Request $request){
        $query = DB::SELECT("SELECT c.com_codigo, c.com_factura, c.com_valor, c.com_iva, c.com_descuento, c.com_empresa, em.descripcion_corta AS nombreEmpresa,
        c.orden_trabajo, c.com_fecha, c.com_observacion, c.com_distribucion, pr.prov_nombre, pr.prov_codigo FROM  1_4_cal_compra c  
        INNER JOIN 1_1_orden_trabajo o ON c.orden_trabajo=o.or_codigo
        INNER JOIN 1_4_proveedor pr on c.prov_codigo=pr.prov_codigo
        LEFT JOIN empresas em ON em.id = c.com_empresa
        WHERE c.orden_trabajo='$request->orden_trabajo'");
         return $query;
     }
     public function Get_DetComprasOrden(Request $request){
        $query = DB::SELECT("SELECT c.com_codigo, c.com_factura, c.com_valor, c.com_iva, c.com_descuento, d.pro_codigo, d.det_com_cantidad, d.det_com_valor_u, 
        dot.det_or_cantidad, c.orden_trabajo FROM  1_4_cal_compra AS c INNER JOIN 1_4_cal_detalle_compra AS d ON c.com_codigo=d.com_codigo 
        INNER JOIN 1_1_orden_trabajo AS o ON c.orden_trabajo=o.or_codigo 
        INNER JOIN 1_1_detalle_orden_trabajo as dot ON o.or_codigo=dot.or_codigo
        WHERE c.orden_trabajo='$request->or_codigo'");
         return $query;
     }
     public function PostDetalleComprarOrden_Registra(Request $request)
       {
       
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $miarray=json_decode($request->data_detallecompra);
        foreach($miarray as $key => $item){
            
            $compra = new DetalleCompraOrden;
            $compra->com_codigo = $request->com_codigo;
            $compra->pro_codigo = $item->pro_codigo;
            $compra->det_com_cantidad = intval($item->cantidades);
            $compra->det_com_valor_u = floatval($item->valorunit);
            $compra->save();            
        }
        if($compra){           
                        return $compra;
                    }else{
                        return "No se pudo actualizar";       
                    }
    }
    public function PostDetalleCompraOrden_Editar(Request $request)
       {
        
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $miarray=json_decode($request->data_detalleorden);
        foreach($miarray as $key => $item){
            
                $orden = DetalleCompraOrden::findOrFail($item->det_com_codigo);
                    $orden->det_com_cantidad = $item->det_com_cantidad;
                    $orden->det_com_valor_u = $item->det_com_valor_u;
                    
                    $orden->save();  
        }
        if($orden){           
                        return $orden;
                    }else{
                        return "No se pudo actualizar";       
                    }
        
    

    
    }
     public function Eliminar_DetalleCompraOrden(Request $request)
    {
        if ($request->det_com_codigo) {
            $orden = DetalleCompraOrden::find($request->det_com_codigo);

            if (!$orden) {
                return "El det_com_codigo no existe en la base de datos";
            }

           
            $orden->delete();

            return $orden;
        } else {
            return "No está ingresando ningún det_com_codigo";
        }
        

    }


}
