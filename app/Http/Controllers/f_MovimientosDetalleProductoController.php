<?php

namespace App\Http\Controllers;

use App\Models\f_movimientos_detalle_producto;
use App\Models\f_movimientos_producto;
use App\Models\f_tipo_documento;
use App\Models\_14ProductoStockHistorico;
use App\Models\_14Producto;
use Illuminate\Support\Facades\DB;
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
        LEFT JOIN empresas em ON fdp.emp_id = em.id
        WHERE fdp.fmp_id = '$request->fmp_id'
        ORDER BY fdp.fmdp_id ASC");
        return $query;
    }

    public function reporteCombosDetalleMovimientoProductos(Request $request){
        $query = DB::SELECT("SELECT fdp.*, cpro.pro_nombre, em.descripcion_corta
        FROM f_movimientos_detalle_producto fdp
        INNER JOIN 1_4_cal_producto cpro ON fdp.pro_codigo = cpro.pro_codigo
        INNER JOIN f_movimientos_producto fmp ON fdp.fmp_id = fmp.fmp_id
        LEFT JOIN empresas em ON fdp.emp_id = em.id
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
        // return $request;
        DB::beginTransaction();
        // Obtener los datos del request
        $DatosAcumuladosStockMasivo = $request->input('DatosAcumuladosStockMasivo', []);
        $fmp_id = $request->input('fmp_id');
        $tipoIngreso = $request->input('tipoIngreso');
        //Variables de almacenamiento
        $codigosConCambios = []; // Variable para almacenar los codigos que tuvieron cambios para ser procesados
        $productosNoEncontrados = []; // Variable para almacenar productos que no fueron encontrados
        $stocksInsuficientes = []; // Variable para almacenar productos que van a quedar con stock negativo o menor a 0
        $errores = []; //Variable para almacenar stocks y productos no encotnrados
        $HistoricoStock = []; // Variable para almacenar OldValues y NewValues
        try {
            if (empty($DatosAcumuladosStockMasivo)) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => 'No se realizaron cambios debido a que no se encontraron datos.',
                ], 200);
            }
             //1.- Listamos solo los productos con cambios
            // Procesar productos con cambios y validaciones de stock
            foreach ($DatosAcumuladosStockMasivo as $item) {
                // Verificar si el producto existe
                $producto = _14Producto::find($item['pro_codigo']);
                if (!$producto) {
                    $productosNoEncontrados[] = ['pro_codigo' => $item['pro_codigo']];
                    continue;
                }
                // Verificar si hubo cambio en el campo 'pro_reservar'
                $hayCambio = $item['pro_reservar'] != $item['pro_reservar_anterior'];
                // Si hubo cambio, almacenar los valores antiguos y agregar el producto a la lista de cambios
                if ($hayCambio) {
                    // Almacena el código del producto con cambios
                    $codigosConCambios[] = $item; 
                }
            }
            //2.- Verificamos si hay cambios o no en los stocks
            // Si no hay productos con cambios, revertir transacción
            if (empty($codigosConCambios)) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => 'No se realizó ninguna actualización porque no hubo cambios.',
                ], 200);
            }
            //3.- Agregar errores si existen de productos no encontrados
            if (!empty($productosNoEncontrados)) {
                $errores['productos_no_encontrados'] = [
                    'mensaje' => 'Productos no encontrados.',
                    'detalles' => $productosNoEncontrados,
                ];
            }
            //4.- Verificar si $codigosConCambios[] cualquier stock modificado no quede en stock negativo o menor a 0
            foreach ($codigosConCambios as $CodigosModificados) {
                $pro_codigo = $CodigosModificados['pro_codigo'];
                // Obtener los datos actuales del producto desde la tabla `1_4_cal_producto`
                $producto = DB::table('1_4_cal_producto')
                    ->where('pro_codigo', $pro_codigo)
                    ->select('pro_nombre', 'pro_stock', 'pro_stockCalmed', 'pro_deposito', 'pro_depositoCalmed')
                    ->first();
                if (!$producto) {
                    continue;  // Saltar al siguiente producto
                }
                $verificar_stocks_disponibles = [
                    'pro_stock' => 'pro_stock_nuevo',
                    'pro_stockCalmed' => 'pro_stockCalmed_nuevo',
                    'pro_deposito' => 'pro_deposito_nuevo',
                    'pro_depositoCalmed' => 'pro_depositoCalmed_nuevo',
                ];
                $detallesError = [];
                foreach ($verificar_stocks_disponibles as $campo_Stock => $campoNuevo) {
                    if (isset($CodigosModificados[$campoNuevo])) {
                        $disponible = $producto->$campo_Stock;
                        $requerido = $CodigosModificados[$campoNuevo];
                        // Si es tipoIngreso == 1 (resta), si es tipoIngreso == 0 (suma)
                        $resultado = ($tipoIngreso == 1) ? ($disponible - $requerido) : ($disponible + $requerido);
                        // Validar solo cuando se está restando
                        if ($resultado < 0) {
                            $detallesError[] = [
                                'campo' => $campo_Stock,
                                'disponible' => $disponible,
                                'requerido' => $requerido,
                            ];
                        }
                    }
                }
                // Si hay detalles de stock insuficiente, se agrega al array de errores
                if (!empty($detallesError)) {
                    $stocksInsuficientes[] = [
                        'pro_codigo' => $pro_codigo,
                        'pro_nombre' => $producto->pro_nombre,
                        'detalles' => $detallesError,
                        'mensaje' => 'Stock insuficiente'
                    ];
                }
            }
            if (!empty($stocksInsuficientes)) {
                $errores['stocks_insuficientes'] = [
                    'mensaje' => 'Productos con stock insuficiente.',
                    'detalles' => $stocksInsuficientes
                ];
            }
            // Si se han encontrado errores (productos no encontrados o stock insuficiente)
            if (!empty($errores)) {
                DB::rollBack();
                return response()->json([
                    'status' => 3,
                    'mensaje' => 'Se han encontrado errores en los productos.',
                    'detalles' => $errores,  // Los detalles ahora son organizados por tipo de error
                ]);
            }
            //4.- Obtenidos los productos con cambios continuamos con el proceso para el registro de los detalles de los movimientos iterando sobre $codigosConCambios
            foreach ($codigosConCambios as $nuevo_stock) {
                $pro_codigo = $nuevo_stock['pro_codigo'];
                $stock_x_emp_y_tipoemp = [
                    'pro_reservar_nuevo' => [null, 3],
                    'pro_stock_nuevo' => [1, 2],
                    'pro_stockCalmed_nuevo' => [3, 2],
                    'pro_deposito_nuevo' => [1, 1],
                    'pro_depositoCalmed_nuevo' => [3, 1],
                ];
                foreach ($stock_x_emp_y_tipoemp as $campo => [$emp_id, $fmdp_tipo_bodega]) {
                    if (isset($nuevo_stock[$campo]) && $nuevo_stock[$campo] != 0) {
                        // Registrar para pro_codigo
                        f_movimientos_detalle_producto::create([
                            'fmp_id'                 => $fmp_id,
                            'pro_codigo'             => $pro_codigo,
                            'emp_id'                 => $emp_id,
                            'fmdp_tipo_bodega'       => $fmdp_tipo_bodega,
                            'fmdp_cantidad'          => $nuevo_stock[$campo],
                        ]);
                    }
                }
            }
            //5.- Actualizar el movimiento items y estado
            // Contar la cantidad de registros insertados en f_movimientos_detalle_producto del fmp_id correspondiente
            $totalItems = f_movimientos_detalle_producto::where('fmp_id', $fmp_id)->count();
            // Sumar las cantidades de registros insertados en f_movimientos_detalle_producto del fmp_id correspondiente
            $totalCantidad = f_movimientos_detalle_producto::where('fmp_id', $fmp_id)->sum('fmdp_cantidad');
            $actualizarmovimiento = f_movimientos_producto::findOrFail($fmp_id);
            $actualizarmovimiento->fmp_estado = ($tipoIngreso == 0) ? 1 : 3; // Si $tipoIngreso es 0 → 1, si no → 3
            $actualizarmovimiento->fmp_items = $totalItems;
            $actualizarmovimiento->fmp_cantidad_total = $totalCantidad;
            $actualizarmovimiento->updated_at = now();
            $actualizarmovimiento->save();
            //6.- Actualizar Stock en la tabla 1_4_cal_producto y alamcena los new_values
            foreach ($codigosConCambios as $ActualizarStock) {
                $pro_codigo = $ActualizarStock['pro_codigo'];
                // Buscar el producto antes de modificarlo
                $producto = _14Producto::find($pro_codigo);
                if ($producto) {
                    // Guardar los valores antes de actualizar (OLD VALUES)
                    $old_values = [
                        'pro_codigo' => $producto->pro_codigo,
                        'pro_reservar' => $producto->pro_reservar,
                        'pro_stock' => $producto->pro_stock,
                        'pro_stockCalmed' => $producto->pro_stockCalmed,
                        'pro_deposito' => $producto->pro_deposito,
                        'pro_depositoCalmed' => $producto->pro_depositoCalmed,
                    ];
                    // Determinar la operación según $tipoIngreso
                    $factor = ($tipoIngreso == 0) ? 1 : -1;
                    // Actualizar los valores sumando o restando
                    $producto->pro_reservar += $factor * $ActualizarStock['pro_reservar_nuevo'];
                    $producto->pro_stock += $factor * $ActualizarStock['pro_stock_nuevo'];
                    $producto->pro_stockCalmed += $factor * $ActualizarStock['pro_stockCalmed_nuevo'];
                    $producto->pro_deposito += $factor * $ActualizarStock['pro_deposito_nuevo'];
                    $producto->pro_depositoCalmed += $factor * $ActualizarStock['pro_depositoCalmed_nuevo'];
                    // Guardar cambios
                    $producto->save();
                    // Guardar los valores después de actualizar (NEW VALUES)
                    $new_values = [
                        'pro_codigo' => $producto->pro_codigo,
                        'pro_reservar' => $producto->pro_reservar,
                        'pro_stock' => $producto->pro_stock,
                        'pro_stockCalmed' => $producto->pro_stockCalmed,
                        'pro_deposito' => $producto->pro_deposito,
                        'pro_depositoCalmed' => $producto->pro_depositoCalmed,
                    ];
                    // Agregar al historial
                    $HistoricoStock[] = [
                        'psh_old_values' => json_encode($old_values), // Valores antes de actualizar
                        'psh_new_values' => json_encode($new_values), // Valores después de actualizar
                    ];
                }
            }
            //7.- Registrar el historico
            // Determinar el tipo de historial según tipoIngreso.
            $psh_tipo = ($tipoIngreso == 0) ? 2 : 3;
            $registroHistorial = [
                'psh_old_values' => json_encode(array_column($HistoricoStock, 'psh_old_values', 'pro_codigo')),
                'psh_new_values' => json_encode(array_column($HistoricoStock, 'psh_new_values', 'pro_codigo')),
                'psh_tipo' => $psh_tipo, // Aquí se asigna el tipo dinámicamente
                'user_created' => $request->user_created,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            _14ProductoStockHistorico::insert($registroHistorial);
            //8.- Guardar los cambios
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
