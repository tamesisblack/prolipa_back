<?php

namespace App\Http\Controllers;

use App\Models\f_movimientos_detalle_producto;
use App\Models\f_movimientos_producto;
use App\Models\f_tipo_documento;
use App\Models\_14Producto;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class f_MovimientosDetalleProductoController extends Controller
{
    public function obtenerDetallesMovimientosProductos($idmovimientoproducto){
        $query = DB::SELECT("SELECT fdp.*, cpro.pro_nombre, em.descripcion_corta
        FROM f_movimientos_detalle_producto fdp
        INNER JOIN 1_4_cal_producto cpro ON fdp.pro_codigo = cpro.pro_codigo
        INNER JOIN f_movimientos_producto fmp ON fdp.fmp_id = fmp.fmp_id
        INNER JOIN empresas em ON fdp.emp_id = em.id
        WHERE fdp.fmp_id = '$idmovimientoproducto'");
        return $query;
    }

    public function reporteIngresoDetalleMovimientoProductos(Request $request){
        $query = DB::SELECT("SELECT fdp.*, cpro.pro_nombre, em.descripcion_corta
        FROM f_movimientos_detalle_producto fdp
        INNER JOIN 1_4_cal_producto cpro ON fdp.pro_codigo = cpro.pro_codigo
        INNER JOIN f_movimientos_producto fmp ON fdp.fmp_id = fmp.fmp_id
        INNER JOIN empresas em ON fdp.emp_id = em.id
        WHERE fdp.fmp_id = '$request->fmp_id'");
        return $query;
    }

    public function reporteEgresoDetalleMovimientoProductos(Request $request){
        $query = DB::SELECT("SELECT mp.*, fdp.*, cpro.pro_nombre, em.descripcion_corta FROM f_movimientos_producto mp
        INNER JOIN f_movimientos_detalle_producto fdp ON mp.fmp_id_referencia = fdp.fmp_id
        INNER JOIN 1_4_cal_producto cpro ON fdp.pro_codigo = cpro.pro_codigo
        INNER JOIN empresas em ON fdp.emp_id = em.id
        WHERE mp.fmp_id_referencia = '$request->fmp_id'");
        return $query;
    }

    public function conteodetallexfmp_id($id_movimientoproducto){
        $query = DB::SELECT("SELECT count(fmp_id) AS conteo FROM f_movimientos_detalle_producto WHERE fmp_id = '$id_movimientoproducto'");
        return $query[0]->conteo;
    }


    public function guardarDatosMovimientosProducto(Request $request)
    {
        DB::beginTransaction();
        // return $request;
        // Verificar si el campo librosConCantidad está presente en la solicitud
        if ($request->has('librosConCantidad')) {
            // Si está presente, realizar la validación completa
            $request->validate([
                'librosConCantidad' => 'array',
                'id_movimientoproducto' => 'required|exists:f_movimientos_producto,fmp_id',
                'cantidadTotal' => 'required|numeric',
            ]);
        } else {
            // Si no está presente, validar solo los otros campos
            $request->validate([
                'id_movimientoproducto' => 'required|exists:f_movimientos_producto,fmp_id',
                'cantidadTotal' => 'required|numeric',
            ]);
        }

        // Obtener los datos del request
        $librosConCantidad = $request->input('librosConCantidad', []);
        $id_movimientoproducto = $request->input('id_movimientoproducto');
        $cantidadTotal = $request->input('cantidadTotal');
        // $conteo = $this->conteodetallexfmp_id($id_movimientoproducto);
        // return $conteo;

        try {
            // Si hay libros, procesar los detalles del pedido
            foreach ($librosConCantidad as $libro) {
                $detalleLibro = f_movimientos_detalle_producto::where('pro_codigo', $libro['pro_codigo'])
                    ->where('fmp_id', $id_movimientoproducto)
                    ->where('emp_id', $libro['emp_id'])
                    ->where('fmdp_tipo_bodega', $libro['fmdp_tipo_bodega'])
                    ->first();
                if ($detalleLibro) {
                    // return response()->json([
                    //     'message' => 'Recibido correctamente',
                    //     'detalleLibro' => $detalleLibro,
                    // ]);
                    $detalleLibro->fmdp_cantidad = $libro['cantidad'];
                    $detalleLibro->fmdp_tipo_bodega = $libro['fmdp_tipo_bodega'];
                    $detalleLibro->emp_id = $libro['emp_id'];
                    $detalleLibro->save();
                } else {
                    $detalleLibro = new f_movimientos_detalle_producto([
                        'pro_codigo' => $libro['pro_codigo'],
                        'fmp_id' => $id_movimientoproducto,
                        'emp_id' => $libro['emp_id'],
                        'fmdp_tipo_bodega' => $libro['fmdp_tipo_bodega'],
                        'fmdp_cantidad' => $libro['cantidad'],
                    ]);
                    // $detalleLibro->updated_at = now();
                    $detalleLibro->save();
                }
            }

            // Actualizar los datos del pedido principal
            $conteo = $this->conteodetallexfmp_id($id_movimientoproducto);
            // return $conteo;
            $pedidoLibrosObsequios = f_movimientos_producto::findOrFail($id_movimientoproducto);
            $pedidoLibrosObsequios->fmp_cantidad_total = $cantidadTotal;
            $pedidoLibrosObsequios->fmp_items = $conteo;
            $pedidoLibrosObsequios->save();

            DB::commit();
            return response()->json(["status" => "1", 'message' => 'Datos actualizados correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }

    public function guardarDatosMovimientosProducto_Stock(Request $request)
    {
        DB::beginTransaction();
        // Obtener los datos del request
        $librosConCantidad = $request->input('librosConCantidad', []);
        $id_movimientoproducto = $request->input('id_movimientoproducto');

        try {
            // Si hay libros, procesar los detalles del pedido
            foreach ($librosConCantidad as $libro) {
                if($libro['emp_id'] == 1 && $libro['fmdp_tipo_bodega'] == 1){
                    //Producto
                    // Actualizar los datos del producto
                    $actualizacionproducto = _14Producto::findOrFail($libro['pro_codigo']);
                    $actualizacionproducto->pro_deposito = $actualizacionproducto->pro_deposito + $libro['cantidad'];
                    $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar + $libro['cantidad'];
                    $actualizacionproducto->save();
                }else if($libro['emp_id'] == 1 && $libro['fmdp_tipo_bodega'] == 2){
                    //Producto
                    // Actualizar los datos del producto
                    $actualizacionproducto = _14Producto::findOrFail($libro['pro_codigo']);
                    $actualizacionproducto->pro_stock = $actualizacionproducto->pro_stock + $libro['cantidad'];
                    $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar + $libro['cantidad'];
                    $actualizacionproducto->save();
                }else if($libro['emp_id'] == 3 && $libro['fmdp_tipo_bodega'] == 1){
                    //Producto
                    // Actualizar los datos del producto
                    $actualizacionproducto = _14Producto::findOrFail($libro['pro_codigo']);
                    $actualizacionproducto->pro_depositoCalmed = $actualizacionproducto->pro_depositoCalmed + $libro['cantidad'];
                    $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar + $libro['cantidad'];
                    $actualizacionproducto->save();
                }else if($libro['emp_id'] == 3 && $libro['fmdp_tipo_bodega'] == 2){
                    //Producto
                    // Actualizar los datos del producto
                    $actualizacionproducto = _14Producto::findOrFail($libro['pro_codigo']);
                    $actualizacionproducto->pro_stockCalmed = $actualizacionproducto->pro_stockCalmed + $libro['cantidad'];
                    $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar + $libro['cantidad'];
                    $actualizacionproducto->save();
                }
            }

            // Actualizar los datos del pedido principal
            $pedidoLibrosObsequios = f_movimientos_producto::findOrFail($id_movimientoproducto);
            $pedidoLibrosObsequios->fmp_estado = 1;//Pasa a estado finalizado el movimiento
            $pedidoLibrosObsequios->updated_at = now();
            $pedidoLibrosObsequios->save();

            DB::commit();
            return response()->json(["status" => "1", 'message' => 'Datos actualizados correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }

    public function guardarDatosMovimientosProducto_EgresoStock(Request $request)
    {
        DB::beginTransaction();
        //Agregar un nuevo MOVIMIENTO TIPO EGRESO
        // Buscar el movimientoegreso por su fmp_id o crear uno nuevo
        $idtipoocumento = 7;
        $fmp_estado2 = $request->fmp_estado;
        $movimientoegreso = f_movimientos_producto::firstOrNew(['fmp_id' => $request->fmp_id]);
        // Asignar los demás datos del movimientoegreso
        $movimientoegreso->id_periodo = $request->id_periodo;
        $movimientoegreso->fmp_id_referencia = $request->fmp_id_referencia;
        //Creamos el nuevo estado del egreso no operativo
        $movimientoegreso->fmp_estado = 3;
        $movimientoegreso->fmp_cantidad_total = $request->fmp_cantidad_total;
        $movimientoegreso->prov_codigo = $request->prov_codigo;

        
        // Verificar si es un nuevo registro o una actualización
        if ($movimientoegreso->exists) {
            return "No puede editar un egreso";
        } else {
            // Si es un nuevo registro, establecer user_created y updated_at
            $movimientoegreso->updated_at = now();
            $movimientoegreso->user_created = $request->user_created;
            $movimientoegreso->save();
            $tipo_doc = f_tipo_documento::findOrFail($idtipoocumento);
            $tipo_doc->tdo_secuencial_calmed = $request->secuencialconteo;
            $tipo_doc->save();
        }

        $librosConCantidad = $request->input('librosConCantidad', []);
        try {
            // Si hay libros, procesar los detalles del pedido
            foreach ($librosConCantidad as $libro) {
                if($libro['emp_id'] == 1 && $libro['fmdp_tipo_bodega'] == 1){
                    //Producto
                    // Actualizar los datos del producto
                    $actualizacionproducto = _14Producto::findOrFail($libro['pro_codigo']);
                    $actualizacionproducto->pro_deposito = $actualizacionproducto->pro_deposito - $libro['cantidad'];
                    $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar - $libro['cantidad'];
                    $actualizacionproducto->save();
                }else if($libro['emp_id'] == 1 && $libro['fmdp_tipo_bodega'] == 2){
                    //Producto
                    // Actualizar los datos del producto
                    $actualizacionproducto = _14Producto::findOrFail($libro['pro_codigo']);
                    $actualizacionproducto->pro_stock = $actualizacionproducto->pro_stock - $libro['cantidad'];
                    $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar - $libro['cantidad'];
                    $actualizacionproducto->save();
                }else if($libro['emp_id'] == 3 && $libro['fmdp_tipo_bodega'] == 1){
                    //Producto
                    // Actualizar los datos del producto
                    $actualizacionproducto = _14Producto::findOrFail($libro['pro_codigo']);
                    $actualizacionproducto->pro_depositoCalmed = $actualizacionproducto->pro_depositoCalmed - $libro['cantidad'];
                    $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar - $libro['cantidad'];
                    $actualizacionproducto->save();
                }else if($libro['emp_id'] == 3 && $libro['fmdp_tipo_bodega'] == 2){
                    //Producto
                    // Actualizar los datos del producto
                    $actualizacionproducto = _14Producto::findOrFail($libro['pro_codigo']);
                    $actualizacionproducto->pro_stockCalmed = $actualizacionproducto->pro_stockCalmed - $libro['cantidad'];
                    $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar - $libro['cantidad'];
                    $actualizacionproducto->save();
                }
            }
            //Se actualiza el estado del INGRESO NO OPERATIVO
            // Actualizar estado del pedido principal
            $movimientoingreso = f_movimientos_producto::findOrFail($movimientoegreso->fmp_id_referencia);
            $movimientoingreso->fmp_estado = $fmp_estado2;
            $movimientoingreso->save();

            DB::commit();
            return response()->json(["status" => "1", 'message' => 'Datos actualizados correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }
    
    public function eliminarDetalleMovimientoProducto(Request $request) {
        // Valida y filtra los datos de entrada
        // return $request;
        $codigo = $request->input('removerItem.pro_codigo');
        $emp_codigo = $request->input('removerItem.emp_id');
        $tipo_bodega = $request->input('removerItem.fmdp_tipo_bodega');
        $cantidad = $request->input('removerItem.cantidad');
        $codigo = $request->input('removerItem.pro_codigo');
        $id_movimientoproducto = $request->input('id_movimientoproducto');
        // $cantidad_pro = $request->input('removerItem.cantidadAnterior');
        // $bodega_pro = $request->input('removerItem.fmdp_tipo_bodegaAnterior');

        $detallePedido = f_movimientos_detalle_producto::where('pro_codigo', $codigo)
            ->where('fmp_id', $id_movimientoproducto)
            ->where('emp_id', $emp_codigo)
            ->where('fmdp_tipo_bodega', $tipo_bodega)
            ->delete();

        // Actualizar fmp_cantidad_total del pedido principal
        $conteo = $this->conteodetallexfmp_id($id_movimientoproducto);
        $movimientoingreso = f_movimientos_producto::findOrFail($id_movimientoproducto);
        $movimientoingreso->fmp_cantidad_total = $movimientoingreso->fmp_cantidad_total - $cantidad;
        $movimientoingreso->fmp_items = $conteo;
        $movimientoingreso->save();
        //RemoverStock del item eliminado
        // if($bodega_pro == 1){
        //     $actualizacionproducto = _14Producto::findOrFail($codigo);
        //     $actualizacionproducto->pro_deposito = $actualizacionproducto->pro_deposito - $cantidad_pro;
        //     $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar - $cantidad_pro;
        //     $actualizacionproducto->save();
        // }else if($bodega_pro == 2){
        //     $actualizacionproducto = _14Producto::findOrFail($codigo);
        //     $actualizacionproducto->pro_stock = $actualizacionproducto->pro_stock - $cantidad_pro;
        //     $actualizacionproducto->pro_reservar = $actualizacionproducto->pro_reservar - $cantidad_pro;
        //     $actualizacionproducto->save();
        // }
    }
}
