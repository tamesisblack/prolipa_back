<?php

namespace App\Http\Controllers;

use App\Models\f_movimientos_detalle_producto;
use App\Models\f_movimientos_producto;
use App\Models\f_tipo_documento;
use App\Models\_14Producto;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class f_MovimientosProductoController extends Controller
{
    public function Get_Movimientos_NO_ProductoContador(Request $request){
        $valormasuno = $request->conteo+1;
        // $query = DB::SELECT("SELECT fmp_id FROM f_movimientos_producto where fmp_id like 'MINO%' ORDER BY created_at DESC LIMIT $valormasuno");
        $query = DB::SELECT("SELECT fmp_id FROM f_movimientos_producto where (fmp_id LIKE 'MINO%' OR fmp_id LIKE 'MENO%')  ORDER BY created_at DESC LIMIT $valormasuno");
        $conteogeneral = count($query);
        if($conteogeneral<$valormasuno){
            return response()->json(['mensaje' => 'data_menor', 'conteo' => $conteogeneral]);
        }else if($conteogeneral==$valormasuno){
            return response()->json(['mensaje' => 'data_igual', 'conteo' => $conteogeneral]);
        }
    }

    public function Get_Movimientos_NO_Producto(){
        $query = DB::SELECT("SELECT pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos ) AS nombreusuario, t.*, t.updated_at as dupdatedmov,
        t.created_at as dcreatedmov,t.fmp_id as codigoanterior, p.*
        FROM f_movimientos_producto t
        INNER JOIN periodoescolar pe ON t.id_periodo = pe.idperiodoescolar
        INNER JOIN usuario u ON t.user_created = u.idusuario
        LEFT JOIN 1_4_proveedor p ON t.prov_codigo = p.prov_codigo
        -- WHERE t.fmp_id like 'MINO%'
        WHERE (t.fmp_id LIKE 'MINO%' OR t.fmp_id LIKE 'MENO%')
        ORDER BY t.created_at ASC");
        return $query;
    }

    public function Get_Movimientos_NO_ProductoreporteEgreso(Request $request){
        $query = DB::SELECT("SELECT pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos ) AS nombreusuario, t.*, t.updated_at as dupdatedmov,
        t.created_at as dcreatedmov,t.fmp_id as codigoanterior, p.*
        FROM f_movimientos_producto t
        INNER JOIN periodoescolar pe ON t.id_periodo = pe.idperiodoescolar
        INNER JOIN usuario u ON t.user_created = u.idusuario
        LEFT JOIN 1_4_proveedor p ON t.prov_codigo = p.prov_codigo
        WHERE fmp_id_referencia = '$request->fmp_id'
        ORDER BY t.created_at ASC");
        return $query;
    }

    public function GetMovimientos_NO_Producto_xfiltro(Request $request){
        if ($request->busqueda == 'undefined' || $request->busqueda == 'codigomovimiento' || $request->busqueda == '' || $request->busqueda == null) {
            $query = DB::SELECT("SELECT pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos ) AS nombreusuario, t.*, t.updated_at as dupdatedmov,
            t.created_at as dcreatedmov, t.fmp_id as codigoanterior, p.*
            FROM f_movimientos_producto t
            INNER JOIN periodoescolar pe ON t.id_periodo = pe.idperiodoescolar
            INNER JOIN usuario u ON t.user_created = u.idusuario
            LEFT JOIN 1_4_proveedor p ON t.prov_codigo = p.prov_codigo
            WHERE t.fmp_id LIKE '%$request->razonbusqueda%' and t.fmp_id like 'MINO%'
            ORDER BY t.created_at ASC
            ");
            return $query;
        }
        if ($request->busqueda == 'periodomovimiento') {
            $query = DB::SELECT("SELECT pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos ) AS nombreusuario, t.*, t.updated_at as dupdatedmov,
            t.created_at as dcreatedmov, t.fmp_id as codigoanterior, p.*
            FROM f_movimientos_producto t
            INNER JOIN periodoescolar pe ON t.id_periodo = pe.idperiodoescolar
            INNER JOIN usuario u ON t.user_created = u.idusuario
            LEFT JOIN 1_4_proveedor p ON t.prov_codigo = p.prov_codigo
            WHERE t.id_periodo = '$request->razonbusqueda' and t.fmp_id like 'MINO%'
            ORDER BY t.created_at ASC
            ");
            return $query;
        }if ($request->busqueda == 'EstadoMovimiento') {
            $query = DB::SELECT("SELECT pe.periodoescolar, CONCAT(u.nombres,' ',u.apellidos ) AS nombreusuario, t.*, t.updated_at as dupdatedmov,
            t.created_at as dcreatedmov, t.fmp_id as codigoanterior, p.*
            FROM f_movimientos_producto t
            INNER JOIN periodoescolar pe ON t.id_periodo = pe.idperiodoescolar
            INNER JOIN usuario u ON t.user_created = u.idusuario
            LEFT JOIN 1_4_proveedor p ON t.prov_codigo = p.prov_codigo
            WHERE t.fmp_estado = '$request->razonbusqueda' and t.fmp_id like 'MINO%'
            ORDER BY t.created_at ASC
            ");
            return $query;
        }
    }

    public function Post_Registrar_modificar_movimiento_producto(Request $request)
    {
        // Buscar el movimientoproducto por su fmp_id o crear uno nuevo
        $idtipoocumento = $request->tipo_ingreso;
        $movimientoproducto = f_movimientos_producto::firstOrNew(['fmp_id' => $request->fmp_id]);
        // Asignar los demás datos del movimientoproducto
        $movimientoproducto->id_periodo = $request->id_periodo;
        $movimientoproducto->fmp_estado = $request->fmp_estado;
        $movimientoproducto->prov_codigo = $request->prov_codigo;
        $movimientoproducto->observacion = $request->observacion;


        // Verificar si es un nuevo registro o una actualización
        if ($movimientoproducto->exists) {
            // Si ya existe, omitir el campo user_created para evitar que se establezca en null
            $movimientoproducto->updated_at = now();
            // Guardar el movimientoproducto sin modificar user_created
            $movimientoproducto->save();
        } else {
            // Si es un nuevo registro, establecer user_created y updated_at
            $movimientoproducto->updated_at = now();
            $movimientoproducto->user_created = $request->user_created;
            $movimientoproducto->save();
            $tipo_doc = f_tipo_documento::findOrFail($idtipoocumento);
            $tipo_doc->tdo_secuencial_calmed = $request->secuencialconteo;
            $tipo_doc->save();
        }

        // Verificar si el producto se guardó correctamente
        if ($movimientoproducto->wasRecentlyCreated || $movimientoproducto->wasChanged()) {
            return $movimientoproducto;
        } else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function Post_Registrar_modificar_movimiento_productoEgreso(Request $request)
    {
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
            // Si ya existe, omitir el campo user_created para evitar que se establezca en null
            $movimientoegreso->updated_at = now();
            // Guardar el movimientoegreso sin modificar user_created
            $movimientoegreso->save();
        } else {
            // Si es un nuevo registro, establecer user_created y updated_at
            $movimientoegreso->updated_at = now();
            $movimientoegreso->user_created = $request->user_created;
            $movimientoegreso->save();
            $tipo_doc = f_tipo_documento::findOrFail($idtipoocumento);
            $tipo_doc->tdo_secuencial_calmed = $request->secuencialconteo;
            $tipo_doc->save();
        }

        // Actualizar estado del pedido principal
        $movimientoingreso = f_movimientos_producto::findOrFail($movimientoegreso->fmp_id_referencia);
        $movimientoingreso->fmp_estado = $fmp_estado2;
        $movimientoingreso->save();

        // Verificar si el producto se guardó correctamente
        if ($movimientoegreso->wasRecentlyCreated || $movimientoegreso->wasChanged()) {
            return "Se guardó correctamente";
        } else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function Modificarproveedor_MovientoProducto(Request $request)
    {
        // return $request;
        // Buscar el movimientoproducto_prov por su fmp_id o crear uno nuevo
        $movimientoproducto_prov = f_movimientos_producto::firstOrNew(['fmp_id' => $request->fmp_id]);
        // Verificar si es un nuevo registro o una actualización
        if ($movimientoproducto_prov->exists){
            // Si ya existe, omitir el campo user_created para evitar que se establezca en null
            $movimientoproducto_prov->prov_codigo = $request->prov_codigo;
            // Guardar el movimientoproducto_prov sin modificar user_created
            $movimientoproducto_prov->save();
        } else {
            return "No existe el movimiento en el que desea agregar la empresa";
        }

        // Verificar si el producto se guardó correctamente
        if ($movimientoproducto_prov->wasRecentlyCreated || $movimientoproducto_prov->wasChanged()) {
            return "Se guardó correctamente";
        } else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function Eliminar_MovimientoProducto(Request $request) {
        // return $request;
        DB::beginTransaction();
        try {
            if($request->fmp_estado == 0){
                f_movimientos_detalle_producto:: where('fmp_id', $request->fmp_id) -> delete ();
                // Buscar el movimiento por su ID
                $movimientoproducto_prov = f_movimientos_producto:: find($request->fmp_id);
                // Verificar si el movimiento existe
                if (!$movimientoproducto_prov) {
                    // Manejar el caso en el que el movimiento no existe
                    return response() -> json(['message' => 'Producto no encontrado'], 404);
                }
                // Eliminar el movimiento
                $movimientoproducto_prov -> delete ();
                DB::commit();
                // Retornar una respuesta exitosa
                return response() -> json(['message' => 'Producto eliminado correctamente'], 200);
            }else if($request->fmp_estado == 1){
                $librosConCantidad = $request->input('librosConCantidad', []);
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
                f_movimientos_detalle_producto:: where('fmp_id', $request->fmp_id) -> delete ();
                // Buscar el movimiento por su ID
                $movimientoproducto_prov = f_movimientos_producto:: find($request->fmp_id);
                // Verificar si el movimiento existe
                if (!$movimientoproducto_prov) {
                    // Manejar el caso en el que el movimiento no existe
                    return response() -> json(['message' => 'Movimiento no encontrado'], 404);
                }
                // Eliminar el movimiento
                $movimientoproducto_prov -> delete ();
                DB::commit();
                // Retornar una respuesta exitosa
                return response() -> json(['message' => 'Movimiento eliminado correctamente'], 200);
            }else if($request->fmp_estado == 2){
                f_movimientos_detalle_producto:: where('fmp_id', $request->fmp_id) -> delete ();
                f_movimientos_producto:: where('fmp_id_referencia', $request->fmp_id) -> delete ();
                // Buscar el movimiento por su ID
                $movimientoproducto_prov = f_movimientos_producto:: find($request->fmp_id);
                // Verificar si el movimiento existe
                if (!$movimientoproducto_prov) {
                    // Manejar el caso en el que el movimiento no existe
                    return response() -> json(['message' => 'Producto no encontrado'], 404);
                }
                // Eliminar el movimiento
                $movimientoproducto_prov -> delete ();
                DB::commit();
                // Retornar una respuesta exitosa
                return response() -> json(['message' => 'Producto eliminado correctamente'], 200);
            }else{
                DB::rollback();
                // Retornar una respuesta
                return response() -> json(['message' => 'Estado de Eliminación no controlado'], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }
}
