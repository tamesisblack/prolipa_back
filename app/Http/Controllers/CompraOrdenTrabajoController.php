<?php

namespace App\Http\Controllers;

use App\Models\CompraOrdenTrabajo;
use App\Models\DetalleCompraOrden;
use App\Models\_14Producto;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CompraOrdenTrabajoController extends Controller
{
    //
    public function GetComprar(){
        $query = DB::SELECT("SELECT * FROM 1_4_cal_compra");
        return $query;
    }
    public function Get_CodigoCompra()
    {
        $maxCodigo = CompraOrdenTrabajo::max('com_codigo');

        if ($maxCodigo !== null) {
            $nextCodigo = $maxCodigo + 1;
        } else {
            $nextCodigo = 1;
        }

        return $nextCodigo;
    }
    public function GetComprar_xfiltro(Request $request){
        if ($request->busqueda == 'codigo') {
            $query = DB::SELECT("SELECT * FROM 1_4_cal_compra as ord INNER JOIN 1_4_proveedor as pro on ord.prov_codigo= pro.prov_codigo
            INNER JOIN usuario as us  ON ord.usu_codigo= us.iniciales
            WHERE or_codigo LIKE '%$request->razonbusqueda%'");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT * FROM 1_4_cal_compra as ord INNER JOIN 1_4_proveedor as pro on ord.prov_codigo= pro.prov_codigo
            INNER JOIN usuario as us  ON ord.usu_codigo= us.iniciales
            WHERE or_codigo LIKE '%$request->razonbusqueda%'");
            return $query;
        }
        if ($request->busqueda == 'temporada') {
            $query = DB::SELECT("SELECT * FROM 1_4_cal_compra as ord INNER JOIN 1_4_proveedor as pro on ord.prov_codigo= pro.prov_codigo
            INNER JOIN usuario as us  ON ord.usu_codigo= us.iniciales
            WHERE or_codigo LIKE '%$request->razonbusqueda%'");
            return $query;
        }
        if ($request->busqueda == 'pendiente') {
            $query = DB::SELECT("SELECT * FROM 1_1_orden_trabajo as ord INNER JOIN 1_4_proveedor as pro on ord.prov_codigo= pro.prov_codigo
            INNER JOIN usuario as us  ON ord.usu_codigo= us.iniciales
            WHERE or_estado =1 ORDER BY or_fecha DESC limit 100");
            return $query;
        }
        if ($request->busqueda == 'finalizado') {
            $query = DB::SELECT("SELECT * FROM 1_1_orden_trabajo 
            WHERE or_estado =2 ORDER BY or_fecha DESC limit 100");
            return $query;
        }
    }

       public function PostCompraOrden_Registrar_modificar(Request $request)
       {      
         $compra1 = CompraOrdenTrabajo::Where('com_codigo',$request->com_codigo)->get();
         if(count($compra1)>0){
            try{
                set_time_limit(6000000);
                ini_set('max_execution_time', 6000000);
                $miarray=json_decode($request->data_detallecompra);
                DB::beginTransaction();
                $compra = CompraOrdenTrabajo::findOrFail($request->com_codigo);
                $compra->com_factura = $request->com_factura;
                $compra->com_valor = floatval($request->com_valor);
                $compra->com_observacion = $request->com_observacion;
                $compra->com_iva = floatval($request->com_iva);
                $compra->com_descuento = floatval($request->com_descuento);
                $compra->updated_at = now();
                $compra->save();
                foreach($miarray as $key => $item){
                    $query1 = DB::SELECT("SELECT pro_stock as stoc, pro_reservar as reserve, pro_deposito as depos from 1_4_cal_producto where pro_codigo='$item->pro_codigo'"); 
                    $codi=$query1[0]->stoc;
                    $codi1=$query1[0]->reserve;
                    $codi2=$query1[0]->depos;
                    $query2 = DB::SELECT("SELECT det_com_cantidad as cant, det_com_st as stoc from 1_4_cal_detalle_compra where com_codigo='$request->com_codigo' and pro_codigo='$item->pro_codigo'"); 
                    $cant=$query2[0]->cant;
                    $cants=$query2[0]->cants;
                    $canti=(int)$item->det_com_cantidad-(int)$cant;
                    $cantis=(int)$item->det_com_st-(int)$cants;
                    $can=(int)$canti-(int)$cantis;
                    $co=(int)$codi1+(int)$canti;
                    $co2=(int)$codi+(int)$cantis;
                    $co1=(int)$codi2+(int)$can;
                    $pro= _14Producto::findOrFail($item->pro_codigo);
                    $pro->pro_reservar = $co;
                    $pro->pro_stock = $co2;
                    $pro->pro_deposito = $co1;
                    $pro->save(); 
                    $compras = DetalleCompraOrden::findOrFail($item->det_com_codigo);
                    $compras->det_com_cantidad = $item->det_com_cantidad;
                    $compras->det_com_valor_u = $item->det_com_valor_u;
                    $compras->save();
                }
                DB::commit();
            }catch(\Exception $e){
                return ["error"=>"0", "message" => "No se pudo guardar","error"=>$e];
                DB::rollback();
            }
         }else{
            try{
                set_time_limit(6000000);
                ini_set('max_execution_time', 6000000);
                $miarray=json_decode($request->data_detallecompra);
                DB::beginTransaction();
                $compra = new CompraOrdenTrabajo;
                $compra->com_codigo = $request->com_codigo;
                $compra->com_descuento = floatval($request->com_descuento);
                $compra->com_factura = $request->com_factura;
                $compra->com_fecha = now();
                $compra->com_iva = floatval($request->com_iva);
                $compra->com_responsable = $request->com_responsable;
                $compra->com_valor = floatval($request->com_valor);
                $compra->com_observacion = $request->com_observacion;
                $compra->orden_trabajo = $request->orden_trabajo;
                $compra->prov_codigo = $request->prov_codigo;
                $compra->user_created = $request->user_created;
                $compra->created_at = now();
                $compra->updated_at = now();
                $compra->save();
                foreach($miarray as $key => $item){
                    $query1 = DB::SELECT("SELECT pro_stock as stoc, pro_reservar as reserve, pro_deposito as depos from 1_4_cal_producto where pro_codigo='$item->pro_codigo'"); 
                    $codi=$query1[0]->stoc;
                    $codi1=$query1[0]->reserve;
                    $codi2=$query1[0]->depos;
                    $co=(int)$codi1+(int)$item->cantidades;
                    $co2=(int)$codi+(int)$item->stock;
                    $co1=(int)$codi2+(int)$item->depositos;
                    $pro= _14Producto::findOrFail($item->pro_codigo);
                    $pro->pro_reservar = $co;
                    $pro->pro_stock = $co2;
                    $pro->pro_deposito = $co1;
                    $pro->save(); 
                    $compras = new DetalleCompraOrden;
                    $compras->com_codigo = $request->com_codigo;
                    $compras->pro_codigo = $item->pro_codigo;
                    $compras->det_com_cantidad = intval($item->cantidades);
                    $compras->det_com_st= intval($item->stock);
                    $compras->det_com_valor_u = floatval($item->valorunit);
                    $compras->save();        
                } 
                DB::commit();
            }catch(\Exception $e){
                return ["error"=>"0", "message" => "No se pudo guardar","error"=>$e];
                DB::rollback();
            }
         }
       
       if($compra){
           return $compra;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }
    
    public function PostCompraOrdenTrabajo_Registrar_modificar(Request $request)
    {
        try {
            $request->validate([
                'prov_codigo' => 'required',
                'empresa' => 'required'
            ]);
    
            DB::beginTransaction();
    
            // Variables de compra y operación de modificación o creación
            $compra = null;
            $empresa = $request->empresa;
    
            if ($request->editar == 'yes') {
                $compra = CompraOrdenTrabajo::find($request->com_codigo);
                if (!$compra) {
                    throw new \Exception('La compra que intenta editar no existe');
                }
                $compra->update([
                    'com_factura' => $request->com_factura,
                    'com_valor' => floatval($request->com_valor),
                    'com_observacion' => $request->com_observacion,
                    'com_iva' => floatval($request->com_iva),
                    'com_descuento' => floatval($request->com_descuento),
                    'prov_codigo' => $request->prov_codigo,
                    'com_fecha' => now(),
                    'updated_at' => now(),
                    'com_responsable' => $request->com_responsable,
                    'orden_trabajo' => $request->orden_trabajo,
                    'user_created' => $request->user_created,
                    'com_distribucion' => $request->distribuirStock,
                    'com_empresa' => $request->empresa,
                ]);
            } else {
                $compra = CompraOrdenTrabajo::create([
                    'prov_codigo' => $request->prov_codigo,
                    'com_factura' => $request->com_factura,
                    'com_valor' => floatval($request->com_valor),
                    'com_fecha' => now(),
                    'com_observacion' => $request->com_observacion,
                    'com_iva' => floatval($request->com_iva),
                    'com_descuento' => floatval($request->com_descuento),
                    'com_responsable' => $request->com_responsable,
                    'orden_trabajo' => $request->orden_trabajo,
                    'com_estado' => 1,
                    'com_distribucion' => $request->distribuirStock,
                    'user_created' => $request->user_created,
                    'com_empresa' => $request->empresa,
                ]);
            }
    
            // Actualización de productos y detalles de compra
            $detalleCompraItems = json_decode($request->data_detallecompra);
    
            foreach ($detalleCompraItems as $item) {
                $producto = _14Producto::findOrFail($item->pro_codigo);
    
                if ($request->distribuirStock) {
                    $this->actualizarProductoConDistribucion($request, $item, $producto, $compra, $empresa);
                } else {
                    $this->actualizarProductoSinDistribucion($request, $item, $producto, $compra, $empresa);
                }
            }
    
            DB::commit();
    
            return response()->json(["status" => "1", "message" => "Se envió correctamente"]);
    
        } catch (\Throwable $e) {
            DB::rollback();
            return response()->json(["status" => "0", "error" => "No se pudo guardar la compra de la orden de trabajo", "message" => $e->getMessage()]);
        }
    }
    
    private function actualizarProductoConDistribucion($request, $item, $producto, $compra, $empresa)
    {
        try {
            if ($request->editar == 'yes') {
                if ($empresa == "1") {
                    $producto->update([
                        'pro_stock' => ($producto->pro_stock - $item->stock_antiguo) + $item->stock,
                        'pro_deposito' => ($producto->pro_deposito - $item->depositos_antiguo) + $item->depositos,
                        'pro_reservar' => ($producto->pro_reservar - $item->cantidades_antiguo) + $item->cantidades,
                    ]);
                } else if ($empresa == "3") {
                    $producto->update([
                        'pro_stockCalmed' => ($producto->pro_stockCalmed - $item->stock_antiguo) + $item->stock,
                        'pro_depositoCalmed' => ($producto->pro_depositoCalmed - $item->depositos_antiguo) + $item->depositos,
                        'pro_reservar' => ($producto->pro_reservar - $item->cantidades_antiguo) + $item->cantidades,
                    ]);
                }
            } else {
                if ($empresa == "1") {
                    $producto->update([
                        'pro_stock' => $producto->pro_stock + $item->stock,
                        'pro_deposito' => $producto->pro_deposito + $item->depositos,
                        'pro_reservar' => $producto->pro_reservar + $item->cantidades,
                    ]);
                } else if ($empresa == "3") {
                    $producto->update([
                        'pro_stockCalmed' => $producto->pro_stockCalmed + $item->stock,
                        'pro_depositoCalmed' => $producto->pro_depositoCalmed + $item->depositos,
                        'pro_reservar' => $producto->pro_reservar + $item->cantidades,
                    ]);
                }
            }
    
            DetalleCompraOrden::updateOrCreate(
                ['com_codigo' => $compra->com_codigo, 'pro_codigo' => $item->pro_codigo],
                [
                    'com_codigo' => $compra->com_codigo,
                    'pro_codigo' => $item->pro_codigo,
                    'det_com_valor_u' => $item->valorunit,
                    'updated_at' => now(),
                    'det_com_nota' => $item->depositos,
                    'det_com_cantidad' => $item->cantidades,
                    'det_com_factura' => $item->stock,
                ]
            );
    
            CompraOrdenTrabajo::where('com_codigo', $compra->com_codigo)
                ->update(['com_distribucion' => 0]);
    
        } catch (\Throwable $e) {
            throw $e;
        }
    }
    
    private function actualizarProductoSinDistribucion($request, $item, $producto, $compra, $empresa)
    {
        try {
            if ($request->editar == 'yes') {
                if ($empresa == "1") {
                    $producto->update([
                        'pro_reservar' => ($producto->pro_reservar - $item->cantidades_antiguo) + $item->cantidades,
                        'pro_deposito' => ($producto->pro_deposito - $item->cantidades_antiguo) + $item->cantidades,
                    ]);
                } else if ($empresa == "3") {
                    $producto->update([
                        'pro_reservar' => ($producto->pro_reservar - $item->cantidades_antiguo) + $item->cantidades,
                        'pro_depositoCalmed' => ($producto->pro_depositoCalmed - $item->cantidades_antiguo) + $item->cantidades,
                    ]);
                }
            } else {
                if ($empresa == "1") {
                    $producto->update([
                        'pro_reservar' => $producto->pro_reservar + $item->cantidades,
                        'pro_deposito' => $producto->pro_deposito + $item->cantidades,
                    ]);
                } else if ($empresa == "3") {
                    $producto->update([
                        'pro_reservar' => $producto->pro_reservar + $item->cantidades,
                        'pro_depositoCalmed' => $producto->pro_depositoCalmed + $item->cantidades,
                    ]);
                }
            }
    
            DetalleCompraOrden::updateOrCreate(
                ['com_codigo' => $compra->com_codigo, 'pro_codigo' => $item->pro_codigo],
                [
                    'com_codigo' => $compra->com_codigo,
                    'pro_codigo' => $item->pro_codigo,
                    'det_com_valor_u' => $item->valorunit,
                    'det_com_nota' => $item->cantidades,
                    'updated_at' => now(),
                    'det_com_cantidad' => $item->cantidades,
                ]
            );
    
        } catch (\Throwable $e) {
            throw $e;
        }
    }
    

    
     
    public function Eliminar_CompraOrden(Request $request)
    {
        if ($request->com_codigo) {
            try{
                DB::beginTransaction();
                $compra = DetalleCompraOrden::Where('com_codigo',$request->com_codigo)->get();
                $cont=count($compra);
                if ($cont>0) {
                    foreach ($compra as $detalles) {
                        $codigo = $detalles->pro_codigo;
                        $cantidad = $detalles->det_com_cantidad;
                        $st= $detalles->det_com_st;
                        $query1 = DB::SELECT("SELECT pro_stock as stoc, pro_reservar as reserve, pro_deposito as depos from 1_4_cal_producto where pro_codigo='$detalles->pro_codigo'"); 
                        $codi=$query1[0]->stoc;
                        $codi1=$query1[0]->reserve;
                        $codi2=$query1[0]->depos;
                        $pro= _14Producto::findOrFail($codigo);
                        $pro->pro_reservar = (int)($codi1-$cantidad);
                        $pro->pro_stock = (int)($codi-$st);
                        $pro->pro_deposito = (int)($codi2-($cantidad-$st));
                    }
                    $compra = DetalleCompraOrden::Where('com_codigo',$request->com_codigo)->delete();
                }else{
                    return "El com_codigo no existe en la base de datos";
                }
                $compra = DetalleCompraOrden::Where('com_codigo',$request->com_codigo)->get();
                if(count($compra)==0){
                    $compra = CompraOrdenTrabajo::find($request->com_codigo);
                    if (!$compra) {
                        return "El com_codigo no existe en la base de datos22";
                    } else {
                        $compra->delete();
                    }
                }
                DB::commit();
            }catch(\Exception $e){
                return ["error"=>"0", "message" => "No se pudo guardar","error"=>$e];
                DB::rollback();
            }
        }
    }
    public function Eliminar_ComprasOrdentrabajo(Request $request)
    {
        if (!$request->com_codigo) {
            return response()->json(['error' => 'No se proporcionó un código de compra'], 400);
        }
        if (!$request->empresa) {
            return response()->json(['error' => 'No se proporcionó empresa de compra'], 400);
        }

        try {
            DB::beginTransaction();

            $compraDetalles = DetalleCompraOrden::where('com_codigo', $request->com_codigo)->get();

            if ($compraDetalles->isEmpty()) {
                return response()->json(['error' => 'El código de compra no existe en la base de datos'], 404);
            }

            foreach ($compraDetalles as $detalle) {
                $producto = _14Producto::find($detalle->pro_codigo);            
                if ($producto) {
                    if ($request->distribuirStock) {
                        if ($request->empresa == "1") {
                            $producto->pro_stock -= $detalle->det_com_factura;
                            $producto->pro_deposito -= $detalle->det_com_nota;
                            $producto->pro_reservar -= $detalle->det_com_cantidad;
                        } else if ($request->empresa == "3") {
                            $producto->pro_stockCalmed -= $detalle->det_com_factura;
                            $producto->pro_depositoCalmed -= $detalle->det_com_nota;
                            $producto->pro_reservar -= $detalle->det_com_cantidad;
                        }                        
                    } else {
                        if ($request->empresa == "1") {
                            $producto->pro_reservar -= $detalle->det_com_cantidad;
                            $producto->pro_deposito -= $detalle->det_com_cantidad;
                        } else if ($request->empresa == "3") {
                            $producto->pro_reservar -= $detalle->det_com_cantidad;
                            $producto->pro_depositoCalmed -= $detalle->det_com_cantidad;
                        }                       
                    }
                    $producto->save();
                }
            }

            DetalleCompraOrden::where('com_codigo', $request->com_codigo)->delete();

            $compra = CompraOrdenTrabajo::find($request->com_codigo);
            if (!$compra) {
                return response()->json(['error' => 'El código de compra no existe en la base de datos'], 404);
            }
            $compra->delete();

            // Actualizar el AUTO_INCREMENT después de eliminar registros
            $nextId = CompraOrdenTrabajo::max('com_codigo') + 1;
            DB::statement("ALTER TABLE 1_4_cal_compra AUTO_INCREMENT = $nextId");

            DB::commit();

            return response()->json(['message' => 'La compra y sus detalles fueron eliminados exitosamente'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'No se pudo eliminar la compra', 'exception' => $e->getMessage()], 500);
        }
    }
}
