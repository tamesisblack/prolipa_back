<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\CuentaBancaria;
use App\Models\Cheque;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BancoController extends Controller
{
    public function GetBanco_todo(){
        $query = DB::SELECT("SELECT * FROM 1_1_bancos");
        return $query;
    }
    public function GetCuentasXBanco(Request $request){
        $query = DB::SELECT("SELECT * FROM 1_1_cuenta_pago cpg
    INNER JOIN 1_1_bancos ban ON ban.ban_codigo = cpg.ban_codigo  
    WHERE cpg.ban_codigo = $request->banco");
        return $query;
    }

    public function GetCuentasAll(Request $request){
        $query = DB::SELECT("SELECT * FROM 1_1_cuenta_pago cpg");
        return $query;
    }

    public function GetCheque_todo(Request $request){
        $query = DB::SELECT("SELECT chq.* FROM cheque chq 
        -- INNER JOIN 1_1_bancos ban ON ban.ban_codigo = chq.ban_codigo
        -- WHERE chq_institucion = $request->institucion
        WHERE chq_cliente = $request->cliente
        AND chq_periodo = $request->periodo
        AND chq_empresa = $request->empresa
        ORDER BY chq.created_at DESC ");
        return $query;
    }

    public function PostRegistrar_modificar_Banco(Request $request)
    {
        // Validación de los datos recibidos
        $validator = Validator::make($request->all(), [
            'ban_nombre' => 'required|string|max:255',
            'ban_telefono' => 'required|string|max:20',
            'ban_direccion' => 'nullable|string|max:255',
            'user_created' => 'nullable|integer',
            'cuentas' => 'nullable|array',  // Asumiendo que 'cuentas' es un arreglo de datos de cuentas bancarias
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Utilizar transacción para asegurar la integridad de los datos
        DB::beginTransaction();

        try {
            // Crear o actualizar el banco
            $bancoData = [
                'ban_nombre' => $request->ban_nombre,
                'ban_telefono' => $request->ban_telefono,
                'ban_direccion' => $request->ban_direccion,
                'user_created' => $request->user_created,
            ];

            if ($request->has('ban_codigo')) {
                // Editar banco existente
                $banco = Banco::find($request->ban_codigo);
                if (!$banco) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Banco no encontrado'
                    ], 404);
                }
                $banco->update($bancoData);
            } else {
                // Crear nuevo banco
                $banco = Banco::create($bancoData + ['ban_estado' => 0]);
            }

            // Manejar las cuentas bancarias si se proporcionan
            if ($request->has('cuentas')) {
                CuentaBancaria::where('ban_codigo', $banco->ban_codigo)->delete();
                foreach ($request->cuentas as $cuenta) {
                    CuentaBancaria::create([
                        'ban_codigo' => $banco->ban_codigo,
                        'cue_pag_numero' => $cuenta['cue_pg_numero'],
                        'cue_pag_descripcion' => $cuenta['cue_pg_descripcion'],
                        'cue_pag_nombre' => $cuenta['cue_pag_nombre'],
                    ]);
                }
            }

            // Confirmar la transacción
            DB::commit();

            return response()->json(['message' => 'Banco guardado o actualizado correctamente', 'banco' => $banco], 200);

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cheque_registro(Request $request)
    {
        // Validación de los datos recibidos
        $request->validate([
            'ban_codigo' => 'required|integer',
            'chq_tipo' => 'required|integer',
            'chq_valor' => 'required|numeric|min:0',
            'chq_cuenta' => 'required',
            'chq_referenca' => 'required',
            'chq_numero' => 'required|integer',
            // 'chq_institucion' => 'required|string',
            'chq_periodo' => 'required|string',
            'chq_empresa' => 'required',
            'chq_cliente' => 'required',
            'chq_nombrebanco' => 'required',
            'user_created' => 'required',
        ]);

        // Verificar si el número de cheque ya existe
        $chequeExistente = Cheque::where('chq_numero', $request->chq_numero)
                                ->where('chq_cuenta', $request->chq_cuenta)
                                ->exists();

        if ($chequeExistente) {
            return response()->json([
                'status' => 0,
                'message' => 'El número de cheque ' . $request->chq_numero . ' ya está registrado para la cuenta ' . $request->chq_cuenta,
            ]);
        }

        // Crear una instancia del modelo Cheque con los datos validados
        $cheque = Cheque::create([
            'ban_codigo' => $request->ban_codigo,
            'chq_tipo' => $request->chq_tipo,
            'chq_valor' => $request->chq_valor,
            'chq_fecha_cobro' => $request->chq_fecha_cobro,
            'chq_cuenta' => $request->chq_cuenta,
            'chq_referenca' => $request->chq_referenca,
            'chq_numero' => $request->chq_numero,
            // 'chq_institucion' => $request->chq_institucion,
            'chq_periodo' => $request->chq_periodo,
            'chq_empresa' => $request->chq_empresa,
            'chq_cliente' => $request->chq_cliente,
            'user_created' => $request->user_created,
            'chq_nombrebanco' => $request->chq_nombrebanco
        ]);

         // Registrar el historial
         $this->registrarHistorial($cheque, 0, json_encode($cheque->toArray()), json_encode($cheque->toArray())); // Tipo 0 para 'crear'

        // Retornar una respuesta JSON adecuada
        return response()->json([
            'status' => 1,
            'message' => 'Cheque registrado correctamente',
            'cheque' => $cheque
        ]);
    }
    private function registrarHistorial(Cheque $cheque, $tipo, $new_value, $oldValue)
    {
        DB::table('cheque_historico')->insert([
            'chq_id' => $cheque->chq_id,
            'tipo' => $tipo,
            'old_value' => $oldValue,
            'new_value' => $new_value,
            'changed_at' => now(),
            'changed_by' => $cheque->user_created,
        ]);
    }
    public function cheque_eliminar(Request $request)
        {
            // Validación de los datos recibidos
            $request->validate([
                'chequeid' => 'required|integer',
            ]);

            // Buscar el cheque a eliminar
            $cheque = Cheque::find($request->chequeid);

            // Registrar el historial antes de eliminar
            $this->registrarHistorial($cheque, 1, json_encode($cheque->toArray()), json_encode($cheque->toArray())); // Tipo 1 para 'eliminar'

            // Eliminar el cheque
            $cheque->delete();

            // Retornar una respuesta JSON adecuada
            return response()->json([
                'status' => 1,
                'message' => 'Cheque eliminado correctamente'
            ]);
        }

    public function cuenta_registro(Request $request)
    {
        // Validación de los datos recibidos
        $request->validate([
            'ban_codigo' => 'required|integer',
            'cue_pag_numero' => 'required|integer',
            'cue_pag_nombre' => 'required|string',
            'cue_pag_tipo_cuenta' => 'required|integer',
        ]);
    
        if ($request->has('editar') && $request->editar === 'yes') {
            // Actualizar la cuenta existente
            $cuentaExistente = CuentaBancaria::where('cue_pag_codigo', $request->cue_pag_codigo)
                                            ->first();
            if ($cuentaExistente) {
                $cuentaExistente->update([
                    'cue_pag_descripcion' => $request->cue_pag_descripcion,
                    'cue_pag_nombre' => $request->cue_pag_nombre,
                    'cue_pag_numero' => $request->cue_pag_numero,
                    'ban_codigo' => $request->ban_codigo,
                    'cue_pag_tipo_cuenta' => $request->cue_pag_tipo_cuenta,
                ]);
    
                return response()->json([
                    'status' => 1,
                    'message' => 'Cuenta actualizada correctamente',
                    'cuenta' => $cuentaExistente
                ]);
            } else {
                return response()->json([
                    'status' => 0,
                    'message' => 'Cuenta no encontrada',
                ]);
            }
        } else {
            // Verificar si la cuenta ya existe
            $cuentaExistente = CuentaBancaria::where('cue_pag_numero', $request->cue_pag_numero)
                                            ->where('ban_codigo', $request->ban_codigo)
                                            ->exists();
    
            if ($cuentaExistente) {
                return response()->json([
                    'status' => 0,
                    'message' => 'La cuenta ' . $request->cue_pag_numero . ' ya está registrada para el banco ' . $request->ban_codigo,
                ]);
            } else {
                // Crear una nueva cuenta
                $cuenta = CuentaBancaria::create([
                    'ban_codigo' => $request->ban_codigo,
                    'cue_pag_numero' => $request->cue_pag_numero,
                    'cue_pag_descripcion' => $request->cue_pag_descripcion,
                    'cue_pag_nombre' => $request->cue_pag_nombre,
                    'cue_pag_tipo_cuenta' => $request->cue_pag_tipo_cuenta,
                    'user_created' => $request->user_created,
                ]);
    
                return response()->json([
                    'status' => 1,
                    'message' => 'Cuenta registrada correctamente',
                    'cuenta' => $cuenta
                ]);
            }
        }
    }

    public function GetCuentasBanco(Request $request){
        $query = DB::SELECT("SELECT * FROM 1_1_cuenta_pago cpg");
        return $query;
    }
    public function desactivar_activar_cuenta(Request $request)
    {
        $cue_pag_codigo = $request->cue_pag_codigo;
        $cue_pag_estado = $request->cue_pag_estado;

        $cuenta = CuentaBancaria::find($cue_pag_codigo);

        if ($cuenta) {
            $cuenta->cue_pag_estado = $cue_pag_estado;
            $cuenta->save();

            return response()->json(['mensaje' => 'Cambio de estado exitoso'], 200);
        } else {
            return response()->json(['mensaje' => 'No se encontró la cuenta'], 404);
        }
    }

    public function eliminar_cuenta(Request $request)
    {
        $cue_pag_codigo = $request->cue_pag_codigo;

        try {
            \DB::beginTransaction();

            $cuenta = CuentaBancaria::find($cue_pag_codigo);

            if ($cuenta) {
                $cuenta->delete();
            } else {
                throw new \Exception('No se encontró la cuenta');
            }

            $ultimoId  = CuentaBancaria::max('cue_pag_codigo') + 1;
            DB::statement('ALTER TABLE 1_1_cuenta_pago AUTO_INCREMENT = ' . $ultimoId);

            \DB::commit();

            return response()->json(['message' => 'Cuenta eliminada correctamente'], 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'Error al eliminar la cuenta: ' . $e->getMessage()], 500);
        }
    }
    
    
    public function obtenerCuentasPago()
    {
        $cuentasPago = DB::table('1_1_cuenta_pago as cp')
            ->where('cp.cue_pag_estado', 0)
            ->select('cp.cue_pag_codigo', 'cp.cue_pag_numero', 'cp.cue_pag_nombre', 'cp.cue_pag_tipo_cuenta')
            ->get();

        return response()->json($cuentasPago);
    }

    public function obtenerAbonosCuentasNotas(Request $request)
    {
        $fechaInicio = $request->fecha_inicio;
        $fechaFinal = $request->fecha_final;
        $cuenta = $request->cuenta;
    
        $abonos = DB::table('abono as a')
            ->join('1_1_cuenta_pago as cp', 'cp.cue_pag_codigo', '=', 'a.abono_cuenta')
            ->join('usuario as usu', 'usu.cedula', '=', 'a.abono_ruc_cliente')
            ->select('cp.cue_pag_numero', 'cp.cue_pag_nombre', DB::raw('concat(usu.nombres, " ", usu.apellidos) as cliente'), 'a.*')
            ->where('cp.cue_pag_tipo_cuenta', 1)
            ->where('cp.cue_pag_codigo', $cuenta)
            ->where('a.abono_estado','<>',1)
            ->whereBetween(DB::raw('DATE(a.abono_fecha)'), [$fechaInicio, $fechaFinal])
            ->get();
        foreach ($abonos as $key => $value) {
            $institucion = DB::table('f_venta as v')
                ->join('institucion as i', 'i.idInstitucion', '=', 'v.institucion_id')
                ->select('i.idInstitucion', 'i.nombreInstitucion')
                ->where('v.ruc_cliente', $value->abono_ruc_cliente)
                ->first();
                $abonos[$key]->institucion = $institucion->nombreInstitucion;
        }
    
        return response()->json($abonos);
    }
    
    public function obtenerAbonosCuentas(Request $request)
    {
        $fechaInicio = $request->fecha_inicio;
        $fechaFinal = $request->fecha_final;
        $cuenta = $request->cuenta;
        $busqueda = $request->busqueda;
        if($fechaInicio && $fechaFinal ){
            // Inicia la consulta base
            $abonos = DB::table('abono as a')
            ->join('1_1_cuenta_pago as cp', 'cp.cue_pag_codigo', '=', 'a.abono_cuenta')
            ->join('usuario as usu', 'usu.cedula', '=', 'a.abono_ruc_cliente')
            ->leftJoin('evidencia_global_files as egf', 'egf.egf_id', '=', 'a.egf_id') // Unión con evidencia_global_files
            ->leftJoin('evidencia_global_files_tipo as egft', 'egft.egft_id', '=', 'egf.egft_id') // Unión con evidencia_global_files_tipo
            ->select('cp.cue_pag_numero', 'cp.cue_pag_nombre', DB::raw('concat(usu.nombres, " ", usu.apellidos) as cliente'), 'a.*',
            'egf.egf_url', // Campo adicional de evidencia_global_files
            'egft.egft_nombre' // Campo adicional de evidencia_global_files_tipo
            )
            ->where('cp.cue_pag_codigo', $cuenta)
            ->whereBetween(DB::raw('DATE(a.abono_fecha)'), [$fechaInicio, $fechaFinal]);

            // Condicionales para el estado de abono
            if ($busqueda == 'Pendientes') {
                $abonos->where('a.abono_estado', 0);
            }

            if ($busqueda == 'Verificados') {
                $abonos->where('a.abono_estado', 2);
            }

            // Ejecuta la consulta
            $abonos = $abonos->get();

            // Recorre los resultados para agregar la institución
            foreach ($abonos as $key => $value) {
                $institucion = DB::table('f_venta as v')
                    ->join('institucion as i', 'i.idInstitucion', '=', 'v.institucion_id')
                    ->select('i.idInstitucion', 'i.nombreInstitucion')
                    ->where('v.ruc_cliente', $value->abono_ruc_cliente)
                    ->first();

                // Si existe una institución, se asigna
                if ($institucion) {
                    $abonos[$key]->institucion = $institucion->nombreInstitucion;
                }
            }
        }else{
            // Inicia la consulta base
            $abonos = DB::table('abono as a')
            ->join('1_1_cuenta_pago as cp', 'cp.cue_pag_codigo', '=', 'a.abono_cuenta')
            ->join('usuario as usu', 'usu.cedula', '=', 'a.abono_ruc_cliente')
            ->leftJoin('evidencia_global_files as egf', 'egf.egf_id', '=', 'a.egf_id') // Unión con evidencia_global_files
            ->leftJoin('evidencia_global_files_tipo as egft', 'egft.egft_id', '=', 'egf.egft_id') // Unión con evidencia_global_files_tipo
            ->select('cp.cue_pag_numero', 'cp.cue_pag_nombre', DB::raw('concat(usu.nombres, " ", usu.apellidos) as cliente'), 'a.*',
            'egf.egf_url', // Campo adicional de evidencia_global_files
            'egft.egft_nombre' // Campo adicional de evidencia_global_files_tipo
            )
            ->where('cp.cue_pag_codigo', $cuenta);

            // Condicionales para el estado de abono
            if ($busqueda == 'Pendientes') {
                $abonos->where('a.abono_estado', 0);
            }

            if ($busqueda == 'Verificados') {
                $abonos->where('a.abono_estado', 2);
            }

            // Ejecuta la consulta
            $abonos = $abonos->get();

            // Recorre los resultados para agregar la institución
            foreach ($abonos as $key => $value) {
                $institucion = DB::table('f_venta as v')
                    ->join('institucion as i', 'i.idInstitucion', '=', 'v.institucion_id')
                    ->select('i.idInstitucion', 'i.nombreInstitucion')
                    ->where('v.ruc_cliente', $value->abono_ruc_cliente)
                    ->first();

                // Si existe una institución, se asigna
                if ($institucion) {
                    $abonos[$key]->institucion = $institucion->nombreInstitucion;
                }
            }
        }
        
        

        return response()->json($abonos);
    }
    public function cambioEstadoCuentas(Request $request)
    {
        // Verifica si el array de abonos seleccionados está presente
        $abonosSeleccionados = $request->input('cuentas');
        $estado = $request->estado;
        $usuario = $request->usuario;
        
        if (empty($abonosSeleccionados)) {
            return response()->json(['message' => 'No se seleccionaron abonos'], 400);
        }

        try {
            // Realiza el update en la tabla 'abono' para los abonos seleccionados
            DB::table('abono')
            ->whereIn('abono_id', $abonosSeleccionados)
            ->update([
                'abono_estado' => $estado, 
                'usuario_verificador' => $usuario, 
                'fecha_verificacion' => now()
            ]); // Establece el estado a 'Verificado Gerencia' (2)

            // Si todo va bien, retorna una respuesta exitosa
            return response()->json(['message' => 'Estado de los abonos actualizado correctamente'], 200);
        } catch (\Exception $e) {
            // Si ocurre un error, devuelve un mensaje de error
            return response()->json(['message' => 'Error al actualizar los abonos', 'error' => $e->getMessage()], 500);
        }
    }

    public function clientesVentas(Request $request)
    {
        // 1. Obtener las instituciones distintas con el primer SELECT
        $clientes = DB::table('institucion')
            ->join('f_venta_agrupado as f', 'f.institucion_id', '=', 'institucion.idInstitucion')
            ->leftJoin('usuario as u', 'u.idusuario', '=', 'f.ven_cliente')
            ->where('f.periodo_id', $request->periodo)
            ->where('f.id_empresa', $request->empresa)
            ->where('f.est_ven_codigo', 0)
            ->select('f.institucion_id as clientes', 'institucion.nombreInstitucion as institucion', 'u.cedula as ruc')
            ->distinct()
            ->get();

        // Verificar si se obtuvieron resultados
        if ($clientes->isEmpty()) {
            return response()->json(['error' => 'No se encontraron clientes.'], 404);
        }

        // Aquí guardamos los resultados
        $resultados = [];

        // Recorrer los clientes
        foreach ($clientes as $cliente) {
            // 2. Obtener los detalles de venta para el cliente sin usar SUM
            $detalleVenta = DB::table('f_venta as fv')
                ->join('f_detalle_venta as fdv', function ($join) {
                    $join->on('fdv.id_empresa', '=', 'fv.id_empresa')
                        ->on('fv.ven_codigo', '=', 'fdv.ven_codigo');
                })
                ->leftJoin('libros_series as l', 'fdv.pro_codigo', '=', 'l.codigo_liquidacion')
                ->where('fv.est_ven_codigo', '<>', 3)
                ->where('fv.institucion_id', $cliente->clientes)
                ->where('fv.id_empresa', $request->empresa)
                ->whereNotIn('fv.idtipodoc', [17, 16])
                ->select('fdv.pro_codigo', 'l.nombre', 'fdv.det_ven_cantidad', 'fdv.det_ven_valor_u', 'fdv.det_ven_dev')
                ->get();

            // 3. Obtener los detalles de venta agrupados para el cliente
            $detalleVentaAgrupado = DB::table('f_venta_agrupado as fv')
                ->join('f_detalle_venta_agrupado as fdv', function ($join) {
                    $join->on('fdv.id_empresa', '=', 'fv.id_empresa')
                        ->on('fv.id_factura', '=', 'fdv.id_factura');
                })
                ->leftJoin('libros_series as l', 'fdv.pro_codigo', '=', 'l.codigo_liquidacion')
                ->where('fv.est_ven_codigo', 0)
                ->where('fv.institucion_id', $cliente->clientes)
                ->where('fv.id_empresa', $request->empresa)
                ->select('fdv.pro_codigo', 'l.nombre', 'fdv.det_ven_cantidad as det_ven_cantidad_facturada')
                ->get();

            // Inicializamos el array para combinar los resultados
            $detalleVentaCombinado = [];

            // Combinar los datos de detalleVenta sin sumar en la consulta
            foreach ($detalleVenta as $venta) {
                $proCodigo = $venta->pro_codigo;
                if (!isset($detalleVentaCombinado[$proCodigo])) {
                    $detalleVentaCombinado[$proCodigo] = [
                        'pro_codigo' => $proCodigo,
                        'nombre' => $venta->nombre,
                        'det_ven_cantidad' => 0,
                        'det_ven_valor_u' => $venta->det_ven_valor_u,
                        'det_ven_dev' => 0,
                        'det_ven_cantidad_facturada' => 0, // Inicializamos como 0
                    ];
                }
                // Acumulamos las cantidades y devoluciones
                $detalleVentaCombinado[$proCodigo]['det_ven_cantidad'] += $venta->det_ven_cantidad;
                $detalleVentaCombinado[$proCodigo]['det_ven_dev'] += $venta->det_ven_dev;
            }

            // Ahora agregamos la información de detalleVentaAgrupado
            foreach ($detalleVentaAgrupado as $factura) {
                $proCodigo = $factura->pro_codigo;
                // Si el producto ya existe en el array, actualizamos la cantidad facturada
                if (isset($detalleVentaCombinado[$proCodigo])) {
                    $detalleVentaCombinado[$proCodigo]['det_ven_cantidad_facturada'] += $factura->det_ven_cantidad_facturada;
                }
            }

            // Filtramos los productos según la condición
            $detalleVentaFinal = [];
            foreach ($detalleVentaCombinado as $producto) {
                $detVenCantidad = $producto['det_ven_cantidad'];
                $detVenDev = $producto['det_ven_dev'];
                $detVenCantidadFacturada = $producto['det_ven_cantidad_facturada'];

                // Verificamos la condición de cantidad - devoluciones > cantidad facturada
                if (($detVenCantidad - $detVenDev) < $detVenCantidadFacturada) {
                    $detalleVentaFinal[] = $producto;
                }
            }

            // Agregar los resultados al array final
            $resultados[] = [
                'cliente' => $cliente->clientes,
                'institucion' => $cliente->institucion,
                'ruc' => $cliente->ruc,
                'detalle_venta' => $detalleVentaFinal,
            ];
        }

        // Retornar los resultados como una respuesta JSON
        return response()->json($resultados);
    }

    public function clientesVentasALL(Request $request)
    {
        // 1. Obtener las instituciones distintas con el primer SELECT
        $clientes = DB::table('institucion')
            ->join('f_venta_agrupado as f', 'f.institucion_id', '=', 'institucion.idInstitucion')
            ->leftJoin('usuario as u', 'u.idusuario', '=', 'f.ven_cliente')
            ->where('f.periodo_id', $request->periodo)
            ->where('f.id_empresa', $request->empresa)
            ->where('f.est_ven_codigo', 0)
            ->select('f.institucion_id as clientes', 'institucion.nombreInstitucion as institucion', 'u.cedula as ruc')
            ->distinct()
            ->get();

        // Verificar si se obtuvieron resultados
        if ($clientes->isEmpty()) {
            return response()->json(['error' => 'No se encontraron clientes.'], 404);
        }

        // Aquí guardamos los resultados
        $resultados = [];

        // Recorrer los clientes
        foreach ($clientes as $cliente) {
            // 2. Obtener los detalles de venta para el cliente sin usar SUM
            $detalleVenta = DB::table('f_venta as fv')
                ->join('f_detalle_venta as fdv', function ($join) {
                    $join->on('fdv.id_empresa', '=', 'fv.id_empresa')
                        ->on('fv.ven_codigo', '=', 'fdv.ven_codigo');
                })
                ->leftJoin('libros_series as l', 'fdv.pro_codigo', '=', 'l.codigo_liquidacion')
                ->where('fv.est_ven_codigo', '<>', 3)
                ->where('fv.institucion_id', $cliente->clientes)
                ->where('fv.id_empresa', $request->empresa)
                ->whereNotIn('fv.idtipodoc', [17, 16])
                ->select('fdv.pro_codigo', 'l.nombre', 'fdv.det_ven_cantidad', 'fdv.det_ven_valor_u', 'fdv.det_ven_dev')
                ->get();

            // 3. Obtener los detalles de venta agrupados para el cliente
            $detalleVentaAgrupado = DB::table('f_venta_agrupado as fv')
                ->join('f_detalle_venta_agrupado as fdv', function ($join) {
                    $join->on('fdv.id_empresa', '=', 'fv.id_empresa')
                        ->on('fv.id_factura', '=', 'fdv.id_factura');
                })
                ->leftJoin('libros_series as l', 'fdv.pro_codigo', '=', 'l.codigo_liquidacion')
                ->where('fv.est_ven_codigo', 0)
                ->where('fv.institucion_id', $cliente->clientes)
                ->where('fv.id_empresa', $request->empresa)
                ->select('fdv.pro_codigo', 'l.nombre', 'fdv.det_ven_cantidad as det_ven_cantidad_facturada')
                ->get();

            // Inicializamos el array para combinar los resultados
            $detalleVentaCombinado = [];

            // Combinar los datos de detalleVenta sin sumar en la consulta
            foreach ($detalleVenta as $venta) {
                $proCodigo = $venta->pro_codigo;
                if (!isset($detalleVentaCombinado[$proCodigo])) {
                    $detalleVentaCombinado[$proCodigo] = [
                        'pro_codigo' => $proCodigo,
                        'nombre' => $venta->nombre,
                        'det_ven_cantidad' => 0,
                        'det_ven_valor_u' => $venta->det_ven_valor_u,
                        'det_ven_dev' => 0,
                        'det_ven_cantidad_facturada' => 0, // Inicializamos como 0
                    ];
                }
                // Acumulamos las cantidades y devoluciones
                $detalleVentaCombinado[$proCodigo]['det_ven_cantidad'] += $venta->det_ven_cantidad;
                $detalleVentaCombinado[$proCodigo]['det_ven_dev'] += $venta->det_ven_dev;
            }

            // Ahora agregamos la información de detalleVentaAgrupado
            foreach ($detalleVentaAgrupado as $factura) {
                $proCodigo = $factura->pro_codigo;
                // Si el producto ya existe en el array, actualizamos la cantidad facturada
                if (isset($detalleVentaCombinado[$proCodigo])) {
                    $detalleVentaCombinado[$proCodigo]['det_ven_cantidad_facturada'] += $factura->det_ven_cantidad_facturada;
                }
            }

            // Filtramos los productos según la condición
            $detalleVentaFinal = [];
            foreach ($detalleVentaCombinado as $producto) {
                $detVenCantidad = $producto['det_ven_cantidad'];
                $detVenDev = $producto['det_ven_dev'];
                $detVenCantidadFacturada = $producto['det_ven_cantidad_facturada'];

                $detalleVentaFinal[] = $producto;
            }

            // Agregar los resultados al array final
            $resultados[] = [
                'cliente' => $cliente->clientes,
                'institucion' => $cliente->institucion,
                'ruc' => $cliente->ruc,
                'detalle_venta' => $detalleVentaFinal,
            ];
        }

        // Retornar los resultados como una respuesta JSON
        return response()->json($resultados);
    }    
    
    public function obtenerCuentasXCobrar(Request $request)
    {
        // Recibir los parámetros
        $year = $request->input('year');
        $tipo = $request->input('tipoFecha');  // Ajustado para coincidir con el frontend
        
        // Validar que se haya seleccionado al menos un año y un tipo de fecha
        if (!$year) {
            return response()->json([
                'error' => 'Debe seleccionar un año.'
            ], 400);  // Retornar un error si no se pasa alguno de los parámetros requeridos
        }
    
        // Si no se pasa tipo de fecha, se establece uno por defecto
        if (!$tipo) {
            $tipo = 1;  // Asignamos el valor por defecto que corresponde a 'fv.fecha_notaCredito'
        }
    
        // Determinar qué campo de fecha usar basado en el tipo
        $campoFecha = '';
        $validacionAdicional='';
        if ($tipo == 1) {
            $campoFecha = 'fv.fecha_notaCredito';
        } elseif ($tipo == 2) {
            $campoFecha = 'fv.fecha_sri';
            $validacionAdicional='AND fv.est_ven_codigo = 15';
        } elseif ($tipo == 3) {
            $campoFecha = 'f.ven_fecha';
        } else {
            return response()->json([
                'error' => 'Tipo de fecha inválido.'
            ], 400);  // Validar que el tipo sea correcto
        }
    
        // Si no se pasa un rango de fechas, usamos el 1 de enero y el 31 de diciembre del año seleccionado
        $fechaInicio = $year . '-01-01';
        $fechaFin = $year . '-12-31';
    
        // Consulta SQL para la empresa 1 (Prolipa)
        $queryProlipa = "
            SELECT 
                f.ven_cliente, 
                u.cedula, 
                CONCAT(u.nombres, ' ', u.apellidos) AS cliente,
                f.institucion_id,
                i.nombreInstitucion,
                SUM(f.ven_valor) AS totalFacturado,
                (
                    SELECT COALESCE(SUM(a.abono_facturas), 0)
                    FROM abono AS a
                    WHERE a.abono_ruc_cliente = u.cedula
                    AND a.abono_empresa = 1
                    AND a.abono_estado = 0
                    AND DATE(a.abono_fecha) BETWEEN ? AND ?
                ) AS totalCobrado,
                COALESCE(nc.totalNotasCredito, 0) AS totalNotasCredito,
                f.id_empresa
            FROM f_venta_agrupado AS f
            INNER JOIN institucion AS i ON i.idInstitucion = f.institucion_id
            INNER JOIN usuario AS u ON u.idusuario = f.ven_cliente
            LEFT JOIN (
                SELECT 
                    fv.institucion_id, 
                    fv.ven_cliente,
                    SUM(fv.ven_valor) AS totalNotasCredito
                FROM f_venta AS fv
                WHERE fv.idtipodoc = 16
                AND fv.id_empresa = 1
                AND fv.id_empresa = 1
                $validacionAdicional
                AND DATE($campoFecha) BETWEEN ? AND ?
                GROUP BY fv.institucion_id, fv.ven_cliente
            ) AS nc ON nc.institucion_id = f.institucion_id AND nc.ven_cliente = f.ven_cliente
            WHERE DATE(f.ven_fecha) BETWEEN ? AND ?
            AND f.estadoPerseo = 1
            AND f.id_empresa = 1
            AND f.est_ven_codigo = 0
            GROUP BY f.ven_cliente, f.institucion_id, f.id_empresa
        ";
    
        // Ejecutar la consulta para la empresa Prolipa
        $resultadosProlipa = DB::select($queryProlipa, [
            $fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin
        ]);
    
        // Transformar los resultados para Prolipa
        $resultadosProlipa = array_map(function ($item) {
            // Asegurar que el totalCobrado sea un float
            $item->totalCobrado = (float) $item->totalCobrado;
            // Añadir el nombre de la empresa
            $item->empresa = 'Prolipa';
            return $item;
        }, $resultadosProlipa);
    
        // Consulta SQL para la empresa 3 (Calmed)
        $queryCalmed = "
            SELECT 
                f.ven_cliente, 
                u.cedula, 
                CONCAT(u.nombres, ' ', u.apellidos) AS cliente,
                f.institucion_id,
                i.nombreInstitucion,
                SUM(f.ven_valor) AS totalFacturado,
                (
                    SELECT COALESCE(SUM(a.abono_facturas), 0)
                    FROM abono AS a
                    WHERE a.abono_ruc_cliente = u.cedula
                    AND a.abono_empresa = 3
                    AND a.abono_estado = 0
                    AND DATE(a.abono_fecha) BETWEEN ? AND ?
                ) AS totalCobrado,
                COALESCE(nc.totalNotasCredito, 0) AS totalNotasCredito,
                f.id_empresa
            FROM f_venta_agrupado AS f
            INNER JOIN institucion AS i ON i.idInstitucion = f.institucion_id
            INNER JOIN usuario AS u ON u.idusuario = f.ven_cliente
            LEFT JOIN (
                SELECT 
                    fv.institucion_id, 
                    fv.ven_cliente,
                    SUM(fv.ven_valor) AS totalNotasCredito
                FROM f_venta AS fv
                WHERE fv.idtipodoc = 16
                AND fv.id_empresa = 3
                $validacionAdicional
                AND DATE($campoFecha) BETWEEN ? AND ?
                GROUP BY fv.institucion_id, fv.ven_cliente
            ) AS nc ON nc.institucion_id = f.institucion_id AND nc.ven_cliente = f.ven_cliente
            WHERE DATE(f.ven_fecha) BETWEEN ? AND ?
            AND f.estadoPerseo = 1
            AND f.id_empresa = 3
            AND f.est_ven_codigo = 0
            GROUP BY f.ven_cliente, f.institucion_id, f.id_empresa
        ";
    
        // Ejecutar la consulta para la empresa Calmed
        $resultadosCalmed = DB::select($queryCalmed, [
            $fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin
        ]);
    
        // Transformar los resultados para Calmed
        $resultadosCalmed = array_map(function ($item) {
            // Asegurar que el totalCobrado sea un float
            $item->totalCobrado = (float) $item->totalCobrado;
            // Añadir el nombre de la empresa
            $item->empresa = 'Calmed';
            return $item;
        }, $resultadosCalmed);
    
        // Organizar los resultados en el formato requerido
        $resultados = [
            'prolipa' => $resultadosProlipa,
            'calmed' => $resultadosCalmed
        ];
    
        // Devolver los resultados en una respuesta JSON
        return response()->json($resultados);
    }
    
    
    public function obtenerNotasCredito(Request $request)
    {
        $empresa = $request->empresa;
        $year = $request->year;
    
        // Realizamos la consulta con DB::table y uniones
        $resultados = DB::table('f_venta as f')
            ->select('f.*', 
                    DB::raw("CONCAT(u.nombres, ' ', u.apellidos) AS cliente"), 
                    'i.nombreInstitucion')
            ->join('institucion as i', 'i.idInstitucion', '=', 'f.institucion_id')
            ->join('usuario as u', 'u.idusuario', '=', 'f.ven_cliente')
            ->where('f.idtipodoc', 16)
            ->where('f.id_empresa', $empresa)
            ->whereBetween('f.fecha_notaCredito', ["$year-01-01", "$year-12-31"])
            ->get();
    
        // Devolver los resultados como un JSON
        return response()->json($resultados);
    }

    public function actualizardatosNotasCredito(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'documento_sri' => 'required',
            'empresa' => 'required',
            'fecha_sri' => 'required',
            'ven_codigo' => 'required',
            'usuario' => 'required',
        ]);
    
        // Realizar la actualización
        $updated = DB::table('f_venta')
                    ->where('ven_codigo', $request->ven_codigo)
                    ->where('id_empresa', $request->empresa)
                    ->update([
                        'fecha_sri' => $request->fecha_sri,
                        'documento_sri' => $request->documento_sri,
                        'est_ven_codigo' => 15,
                        'fecha_update_sri' => now(),
                        'usuario_update_sri' => $request->usuario,
                    ]);
    
        // Verificar si se actualizó al menos una fila
        if ($updated > 0) {
            // Si el número de filas afectadas es mayor que 0, significa que la actualización fue exitosa
            return response()->json(['message' => 'Datos actualizados correctamente'], 200);
        } else {
            // Si no se actualizó ninguna fila, podría ser que no se encontró el registro
            return response()->json(['message' => 'No se encontraron registros para actualizar'], 404);
        }
    }

}