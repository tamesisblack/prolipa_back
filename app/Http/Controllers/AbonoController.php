<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\EvidenciaGlobalFiles;
use App\Models\AbonoHistorico;
use App\Models\Cheque;
use App\Rules\UniqueAbonoDocument;
use App\Models\f_tipo_documento;
use App\Models\Ventas;
use App\Models\DetalleVentas;
use App\Models\Usuario;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AbonoController extends Controller
{
    public function abono_registro(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'notasOfactura' => 'required|in:0,1',
            'abono_valor' => 'required|numeric',
            'abono_totalNotas' => 'required|numeric',
            'abono_totalFacturas' => 'required|numeric',
            'user_created' => 'required',
            'periodo' => 'required',
            'abono_tipo' => 'required',
            'abono_documento' => 'required',
            'abono_cuenta' => 'required',
            'abono_fecha' => 'required|date',
            'abono_empresa' => 'required',
            'abono_concepto' => 'required',
            'abono_ruc_cliente' => 'required',
        ]);

        // 'abono_documento' => 'required|unique:abono,abono_documento,' . $request->abono_id . ',abono_id',

        if ($validator->fails()) {
            $errors = $validator->errors();
            $message = '';
            foreach ($errors->all() as $error) {
                $message .= $error . ' ';
            }
            return response()->json([
                'status' => 0,
                'message' => $message,
                'errors' => $errors,
            ]);
        }

        // Validación personalizada para el número de documento
        $existingAbono = DB::table('abono')
            ->where('abono_documento', $request->abono_documento)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($existingAbono) {
            // Si el estado del abono existente es 0, no se permite guardar
            if ($existingAbono->abono_estado == 0) {
                // Obtener información del cliente para el mensaje de error
                $usuario = DB::table('usuario')
                    ->where('cedula', $existingAbono->abono_ruc_cliente)
                    ->select('nombres', 'apellidos')
                    ->first();

                $fullName = $usuario ? $usuario->nombres . ' ' . $usuario->apellidos : 'desconocido';

                return response()->json([
                    'status' => 0,
                    'message' => "El valor del campo documento ya está en uso con el Cliente $fullName.",
                ]);
            }
            // else{
            //     return ['si existe', $existingAbono];
            // }
        }

        \DB::beginTransaction();

        try {
            $abono = new Abono();
            $abono->abono_facturas = $request->notasOfactura == 0 ? $request->abono_valor : 0;
            $abono->abono_notas = $request->notasOfactura == 1 ? $request->abono_valor : 0;
            $abono->abono_totalNotas = round($request->abono_totalNotas, 2);
            $abono->abono_totalFacturas = round($request->abono_totalFacturas, 2);
            $abono->user_created = $request->user_created;
            $abono->abono_periodo = $request->periodo;
            $abono->abono_tipo = $request->abono_cuenta == '6' ? 4 : $request->abono_tipo;
            $abono->abono_documento = $request->abono_documento;
            $abono->abono_cuenta = $request->abono_cuenta;
            $abono->abono_fecha = $request->abono_fecha;
            $abono->abono_empresa = $request->abono_empresa;
            $abono->abono_concepto = $request->abono_concepto;
            $abono->abono_ruc_cliente = $request->abono_ruc_cliente;
            $abono->idClientePerseo = $request->idClientePerseo;
            $abono->clienteCodigoPerseo = $request->clienteCodigoPerseo;

            if (!$abono->save()) {
                \DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => 'Error al guardar el abono',
                ]);
            }
            $this->guardarAbonoHistorico($abono, 0, $request->user_created);

            \DB::commit();

            // Responder con éxito
            return response()->json([
                'status' => 1,
                'message' => 'Se guardó correctamente',
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => 'Error al guardar el abono: ' . $e->getMessage(),
            ]);
        }
    }
    public function abono_pedido(Request $request){
        $query = DB::SELECT("SELECT SUM(CASE WHEN ab.abono_facturas <> 0.00 AND ab.abono_estado = 0 AND ab.abono_tipo <> 4 THEN ab.abono_facturas ELSE 0 END) AS abonofacturas,
            COUNT(CASE WHEN ab.abono_facturas <> 0.00 AND ab.abono_estado = 0  AND ab.abono_tipo <> 4 THEN 1 ELSE NULL END) AS totalAbonoFacturas,
            SUM(CASE WHEN ab.abono_notas <> 0.00 AND ab.abono_estado = 0  AND ab.abono_tipo <> 4 THEN ab.abono_notas ELSE 0 END) AS abononotas,
            COUNT(CASE WHEN ab.abono_notas <> 0.00 AND ab.abono_estado = 0  AND ab.abono_tipo <> 4 THEN 1 ELSE NULL END) AS totalAbonoNotas,
            SUM(CASE WHEN ab.abono_tipo = 3 AND ab.abono_valor_retencion <> 0.00 AND ab.abono_estado = 0 THEN ab.abono_valor_retencion ELSE 0 END) AS retencionValor,
            COUNT(CASE WHEN ab.abono_tipo = 3 AND ab.abono_valor_retencion <> 0.00 AND ab.abono_estado = 0 THEN 1 ELSE NULL END) AS totalRetencionValor,
            SUM(CASE WHEN ab.abono_facturas <> 0.00 AND ab.abono_tipo = 4 AND ab.abono_cuenta = 9 AND ab.abono_estado = 0 THEN ab.abono_facturas ELSE 0 END) AS cruceValor,
            COUNT(CASE WHEN ab.abono_facturas <> 0.00 AND  ab.abono_tipo = 4 AND ab.abono_cuenta = 9 AND ab.abono_estado = 0 THEN 1 ELSE NULL END) AS totalCruceValor,
            SUM(CASE WHEN ab.abono_notas <> 0.00 AND ab.abono_estado = 0 AND ab.abono_tipo = 4 AND ab.abono_cuenta = 9 THEN ab.abono_notas ELSE 0 END) AS cruceValorNotas,
            COUNT(CASE WHEN ab.abono_notas <> 0.00 AND ab.abono_estado = 0 AND ab.abono_tipo = 4 AND ab.abono_cuenta = 9 THEN 1 ELSE NULL END) AS totalCruceValorNotas
            -- FROM abono ab WHERE ab.abono_institucion = '$request->institucion'
            FROM abono ab WHERE ab.abono_periodo = '$request->periodo'
            AND ab.abono_empresa = '$request->empresa'
            AND ab.abono_ruc_cliente ='$request->cliente'
            GROUP BY ab.abono_ruc_cliente;");
        return $query;
    }

    public function obtenerAbonos(Request $request)
    {
        $abonoNotas = DB::SELECT("SELECT bn.*, cp.cue_pag_nombre FROM abono bn
            LEFT JOIN 1_1_cuenta_pago cp ON cp.cue_pag_codigo = bn.abono_cuenta
            WHERE bn.abono_notas > 0
            AND bn.abono_facturas = 0
            AND bn.idClientePerseo ='$request->cliente'
            -- AND bn.abono_institucion = $request->institucion
            AND bn.abono_periodo = $request->periodo
            AND bn.abono_empresa = '$request->empresa'
            ORDER BY bn.created_at DESC");
        $abonosConNotas = $abonoNotas;

        $abonoFacturas = DB::SELECT("SELECT bn.*, cp.cue_pag_nombre FROM abono bn
            LEFT JOIN 1_1_cuenta_pago cp ON cp.cue_pag_codigo = bn.abono_cuenta
            WHERE bn.abono_facturas > 0
            AND bn.abono_notas = 0
            AND bn.idClientePerseo ='$request->cliente'
            -- AND bn.abono_institucion = $request->institucion
            AND bn.abono_periodo = $request->periodo
            AND bn.abono_empresa = $request->empresa
            ORDER BY bn.created_at DESC");
        $abonosConFacturas = $abonoFacturas;

        $abonosAll = DB::SELECT("SELECT bn.*, cp.cue_pag_nombre FROM abono bn
            LEFT JOIN 1_1_cuenta_pago cp ON cp.cue_pag_codigo = bn.abono_cuenta
            WHERE bn.idClientePerseo ='$request->cliente'
            -- AND bn.abono_institucion = $request->institucion
            AND bn.abono_periodo = $request->periodo
            AND bn.abono_empresa = $request->empresa
            ORDER BY bn.created_at DESC");

        // abonos con abono_facturas > 0 y abono_notas = 0
        // $abonosConFacturas = Abono::where('abono_facturas', '>', 0)
        //                         ->where('abono_notas', '=', 0)
        //                         ->where('abono_institucion', $request->institucion)  // Aquí ajustamos el uso de $request->pedido
        //                         ->where('abono_periodo', $request->periodo)
        //                         ->get();

        // abonos con abono_notas > 0 y abono_facturas = 0
        // $abonosConNotas = Abono::where('abono_notas', '>', 0)
        //                     ->where('abono_facturas', '=', 0)
        //                     ->where('abono_institucion', $request->pedido)  // Aquí también ajustamos el uso de $request->pedido
        //                     ->where('abono_periodo', $request->periodo)
        //                     ->get();

        return [
            'abonos_con_facturas' => $abonosConFacturas,
            'abonos_con_notas' => $abonosConNotas,
            'abonos_all' => $abonosAll
        ];
    }
    private function guardarAbonoHistorico($abono, $tipo, $usuario)
    {
        // Definir el tipo de abono con un array asociativo
        $tiposAbono = [
            10 => 'Cancellation Abono Cruce',
            9 =>  'Edit Abono Cruce',
            8 =>  'Create Abono Cruce',
            7 =>  'Cancellation Retencion',
            6 =>  'Edit Retencion',
            5 =>  'Cancellation Abono',
            4 =>  'Edit Abono',
            3 =>  'Create Retencion',
            2 =>  'Create Abono Cheque',
            1 =>  'Delete Abono',
            0 =>  'Create Abono',
        ];
    
        // Obtener el tipo de abono
        $tipoAbono = $tiposAbono[$tipo] ?? 'Desconocido';  // Por si acaso el tipo no está definido
    
        // Preparar los datos del abono histórico
        $datosAbono = [
            'notasOfactura' => $abono->abono_notas > 0 ? 'nota' : 'factura',
            'tipo' => $tipoAbono,
            'responsable' => $usuario,
        ];
    
            // // Convertir $abono a un array limpio sin metadatos internos
            // $abonoArray = $abono->toArray();

            // // Unir los datos del abono con los datos adicionales
            // $datosAbono = array_merge($datosAbono, $abonoArray);

        // Añadir propiedades del objeto $abono al array $datosAbono
        $datosAbono = array_merge($datosAbono, get_object_vars($abono));
    
        // Crear el registro histórico
        $abonoHistorico = new AbonoHistorico();
        $abonoHistorico->abono_id = $abono->abono_id;
        $abonoHistorico->ab_histotico_tipo = $tipo;
        $abonoHistorico->ab_historico_values = json_encode($datosAbono);
        $abonoHistorico->user_created = $abono->user_created;
    
        // Guardar el histórico y manejar errores
        if (!$abonoHistorico->save()) {
            // Obtener detalles del error para facilitar la depuración
            $errorMessage = implode(', ', $abonoHistorico->errors()->all());
            throw new \Exception('Error al guardar el registro histórico: ' . $errorMessage);
        }
    }
    

    public function eliminarAbono(Request $request)
    {
        \DB::beginTransaction();

        try {
            $abono = Abono::findOrFail($request->abono_id);

            $cheque = Cheque::where('chq_numero', $abono->abono_cheque_numero)
                        ->where('chq_cuenta', $abono->abono_cheque_cuenta)
                        ->first();

            if ($cheque) {
                $cheque->chq_estado = 2;
            }

            $this->guardarAbonoHistorico($abono, 1,$request->usuario);
            $abono->delete();

            \DB::commit();

            return response()->json(['message' => 'Abono eliminado correctamente'], 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'Error al eliminar el abono: ' . $e->getMessage()], 500);
        }
    }
    public function anularAbono(Request $request)
    {
        // Validar los datos de entrada
        $validatedData = $request->validate([
            'abono_id' => 'required',
            'usuario' => 'required',
        ]);

        \DB::beginTransaction();

        try {
            // Buscar el abono en la base de datos
            $abono = Abono::findOrFail($validatedData['abono_id']);

            // Cambiar el estado del abono a anulado (asumiendo que 1 es el estado "anulado")
            $abono->abono_estado = 1;

            // Verificar si el parámetro 'anularretencion' existe en el request
            if ($request->has('anularretencion') && $request->anularretencion == 'yes') {
                // Si 'anularretencion' es 'yes', se usa el tipo 7
                $this->guardarAbonoHistorico($abono, 7, $validatedData['usuario']);
            } else  if ($request->has('AnularCruce') && $request->AnularCruce == 'yes') {

                $this->guardarAbonoHistorico($abono, 10, $validatedData['usuario']);

                $venta = Ventas::where('ven_codigo', $request->abono_documento)
                ->where('id_empresa', $request->abono_empresa)
                ->where('periodo_id', $request->abono_periodo)    
                ->first();

                if (!$venta) {
                    throw new \Exception("El DOCUMENTO DE VENTA NO EXISTE.");
                }

                $venta->est_ven_codigo = 3;

                $venta->save();

            } else {
                // Guardar en histórico (aquí asumimos que este método existe y funciona correctamente)
                $this->guardarAbonoHistorico($abono, 5, $validatedData['usuario']);
            }

            // Guardar los cambios en la base de datos
            $abono->save();

            // Confirmar la transacción
            \DB::commit();

            // Responder con éxito
            return response()->json(['message' => 'Abono anulado correctamente'], 200);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            \DB::rollBack();

            // Registrar el error (opcional, para depuración)
            \Log::error('Error al anular el abono: ' . $e->getMessage());

            // Responder con un mensaje de error genérico
            return response()->json(['error' => 'Error al anular el abono'], 500);
        }
    }



    // public function retencion_registro(Request $request)
    // {
    //     return 'ESTA EN PRUEBAS';
    //     $validator = Validator::make($request->all(), [
    //         'abono_fecha' => 'required|date',
    //         'abono_porcentaje' => 'required',
    //         'abono_valor_retencion' => 'required|numeric',
    //         'institucion' => 'required',
    //         'periodo' => 'required',
    //         'abono_tipo' => 'required',
    //         'user_created' => 'required',

    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 0,
    //             'message' => 'Error en la validación de datos',
    //             'errors' => $validator->errors(),
    //         ]);
    //     }

    //     \DB::beginTransaction();

    //     try {
    //         $abono = new Abono();
    //         $abono->abono_fecha = $request->abono_fecha;
    //         $abono->abono_institucion = $request->institucion;
    //         $abono->abono_periodo = $request->periodo;
    //         $abono->abono_documento = $request->abono_documento;
    //         $abono->abono_tipo = $request->abono_tipo;
    //         $abono->abono_valor_retencion = $request->abono_valor_retencion;
    //         $abono->abono_porcentaje = $request->abono_porcentaje;
    //         $abono->user_created = $request->user_created;
    //         if (!$abono->save()) {
    //             \DB::rollBack();
    //             return response()->json([
    //                 'status' => 0,
    //                 'message' => 'Error al guardar el abono',
    //             ]);
    //         }
    //         $this->guardarAbonoHistorico($abono, 3);

    //         \DB::commit();

    //         // Responder con éxito
    //         return response()->json([
    //             'status' => 1,
    //             'message' => 'Se guardó correctamente',
    //         ]);
    //     } catch (\Exception $e) {
    //         \DB::rollBack(); // Revertir la transacción en caso de excepción
    //         return response()->json([
    //             'status' => 0,
    //             'message' => 'Error al guardar el abono: ' . $e->getMessage(),
    //         ]);
    //     }
    // }

    public function retencion_registro(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'abono_fecha' => 'required|date',
            'abono_porcentaje' => 'required',
            'abono_valor_retencion' => 'required|numeric',
            'abono_periodo' => 'required',
            'abono_tipo' => 'required',
            'user_created' => 'required',
            'abono_clientePerseo' => 'required',
            'clienteCodigoPerseo' => 'required',
            'abono_ruc_cliente' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => 'Error en la validación de datos',
                'errors' => $validator->errors(),
            ]);
        }

        \DB::beginTransaction();

        try {
            $abono = new Abono();
            $abono->abono_fecha = $request->abono_fecha;
            // $abono->abono_institucion = $request->institucion;
            $abono->abono_periodo = $request->periodo;
            // $abono->abono_documento = $request->abono_documento;
            $abono->abono_tipo = $request->abono_tipo;
            $abono->abono_valor_retencion = $request->abono_valor_retencion;
            $abono->abono_porcentaje = $request->abono_porcentaje;
            $abono->idClientePerseo = $request->idClientePerseo;
            $abono->abono_empresa = $request->abono_empresa;
            $abono->abono_periodo = $request->abono_periodo;
            
            $abono->clienteCodigoPerseo = $request->clienteCodigoPerseo;
            $abono->abono_ruc_cliente = $request->abono_ruc_cliente;

            $abono->user_created = $request->user_created;
            if (!$abono->save()) {
                \DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => 'Error al guardar el abono',
                ]);
            }
            $this->guardarAbonoHistorico($abono, 3,$request->user_created);

            \DB::commit();

            // Responder con éxito
            return response()->json([
                'status' => 1,
                'message' => 'Se guardó correctamente',
            ]);
        } catch (\Exception $e) {
            \DB::rollBack(); // Revertir la transacción en caso de excepción
            return response()->json([
                'status' => 0,
                'message' => 'Error al guardar el abono: ' . $e->getMessage(),
            ]);
        }
    }

    public function cobro_cheque_registro(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notasOfactura' => 'required|in:0,1',
            'abono_fecha' => 'required|date',
            'abono_tipo' => 'required',
            'abono_cuenta' => 'required',
            'abono_concepto' => 'required',
            'abono_documento' => 'required|unique:abono,abono_documento,' . $request->abono_id . ',abono_id',
            'abono_valor' => 'required|numeric',
            'abono_cheque_numero' => 'required|numeric',
            'abono_cheque_cuenta' => 'required|numeric',
            'abono_empresa' => 'required',
            // 'institucion' => 'required',
            'periodo' => 'required',
            'user_created' => 'required',
            'estado' => 'required',
            'abono_ruc_cliente' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $message = '';
            foreach ($errors->all() as $error) {
                $message .= $error . ' ';
            }
            return response()->json([
                'status' => 0,
                'message' => $message,
                'errors' => $errors,
            ]);
        }

        \DB::beginTransaction();

        try {
            // Crear instancia de Abono
            $abono = new Abono();
            $abono->abono_fecha = $request->abono_fecha;
            // $abono->abono_institucion = $request->institucion;
            $abono->abono_periodo = $request->periodo;
            $abono->abono_documento = $request->abono_documento;
            $abono->abono_tipo = $request->abono_tipo;
            $abono->abono_empresa = $request->abono_empresa;
            $abono->user_created = $request->user_created;
            $abono->abono_facturas = $request->notasOfactura == 0 ? $request->abono_valor : 0;
            $abono->abono_notas = $request->notasOfactura == 1 ? $request->abono_valor : 0;
            $abono->abono_cuenta = $request->abono_cuenta;
            $abono->abono_cheque_numero = $request->abono_cheque_numero;
            $abono->abono_cheque_cuenta = $request->abono_cheque_cuenta;
            $abono->abono_cheque_banco = $request->abono_cheque_banco;
            $abono->abono_concepto = $request->abono_concepto;
            $abono->idClientePerseo = $request->idClientePerseo;
            $abono->clienteCodigoPerseo = $request->clienteCodigoPerseo;
            $abono->abono_ruc_cliente = $request->abono_ruc_cliente;

            $cheque = Cheque::where('chq_numero', $request->abono_cheque_numero)
                            ->where('chq_cuenta', $request->abono_cheque_cuenta)
                            ->first();

            if (!$cheque) {
                \DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => 'El cheque no existe o no se encontró con el número y cuenta proporcionados',
                ]);
            }

            // Guardar el abono
            if (!$abono->save()) {
                \DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => 'Error al guardar el abono',
                ]);
            }


            $cheque->chq_id_abono = $abono->abono_id;
            $cheque->chq_estado = $request->estado;
            $cheque->save();

            // Guardar historial de abono
            $this->guardarAbonoHistorico($abono, 2,$request->user_created);

            \DB::commit();

            // Responder con éxito
            return response()->json([
                'status' => 1,
                'message' => 'Se guardó correctamente',
            ]);
        } catch (\Exception $e) {
            \DB::rollBack(); // Revertir la transacción en caso de excepción
            return response()->json([
                'status' => 0,
                'message' => 'Error al guardar el abono: ' . $e->getMessage(),
            ]);
        }
    }
    public function estado_cheque(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cheque_id' => 'required|numeric',
            'estado' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => 'Error en la validación de datos',
                'errors' => $validator->errors(),
            ]);
        }

        try {
            // Obtener el cheque por su ID
            $cheque = Cheque::findOrFail($request->cheque_id);

            // Cambiar el estado del cheque
            $cheque->chq_estado = $request->estado;
            $cheque->save();

            return response()->json([
                'status' => 1,
                'message' => 'Estado del cheque actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error al actualizar el estado del cheque: ' . $e->getMessage(),
            ]);
        }
    }
    public function get_facturasNotasxParametro(Request $request){
        // $query = DB::SELECT("SELECT fv.* FROM f_venta fv
        // WHERE fv.institucion_id='$request->institucion'
        // AND fv.periodo_id='$request->periodo'
        // AND fv.id_empresa='$request->empresa'
        // AND fv.clientesidPerseo ='$request->cliente'
        // AND fv.est_ven_codigo <> 3");
        $query = DB::SELECT("SELECT fv.* FROM f_venta fv
        WHERE fv.periodo_id='$request->periodo'
        AND fv.id_empresa='$request->empresa'
        AND fv.ruc_cliente REGEXP '$request->cliente'
        AND fv.est_ven_codigo <> 3
        AND fv.idtipodoc <> 16
        AND fv.idtipodoc <> 17
        ");
        return $query;
    }
    public function get_facturasNotasAll(Request $request){
        // $query = DB::SELECT("SELECT fv.* FROM f_venta fv
        // WHERE fv.institucion_id='$request->institucion'
        // AND fv.periodo_id='$request->periodo'
        // AND fv.id_empresa='$request->empresa'
        // AND fv.clientesidPerseo ='$request->cliente'
        // AND fv.est_ven_codigo <> 3");
        $query = DB::SELECT("SELECT fv.*, ep.descripcion_corta
        FROM f_venta fv
        LEFT JOIN empresas ep ON ep.id = fv.id_empresa
        WHERE fv.periodo_id='$request->periodo'
        AND fv.est_ven_codigo <> 3
        AND fv.idtipodoc <> 16
        AND fv.idtipodoc <> 17");
        return $query;
    }
    public function getClienteCobranzaxInstitucion(Request $request){

        $query = DB::SELECT("SELECT DISTINCT usu.* FROM f_venta fv
            LEFT JOIN usuario usu ON fv.ven_cliente  = usu.idusuario
            WHERE fv.id_ins_depacho = '$request->institucion'
            AND fv.periodo_id = '$request->periodo' ");
        return $query;
    }
    public function InstitucionesXCobranzas(Request $request)
    {
        $busqueda   = $request->busqueda;
        $id_periodo = $request->id_periodo;
        $query = $this->tr_getPuntosVenta($busqueda);
        //traer datos de la tabla f_formulario_proforma por id_periodo
        foreach($query as $key => $item){
            $query[$key]->datosClienteInstitucion = DB::SELECT("SELECT DISTINCT usu.cedula, CONCAT(usu.nombres,' ', usu.apellidos) nombres
            FROM f_venta fv LEFT JOIN usuario usu ON fv.ven_cliente  = usu.idusuario
            WHERE fv.id_ins_depacho = '$item->idInstitucion'
            OR fv.institucion_id = '$item->idInstitucion'
            AND fv.periodo_id = '$request->id_periodo'
            AND (fv.idtipodoc = 3 or fv.idtipodoc = 4 or fv.idtipodoc = 1)
            AND fv.est_ven_codigo <> 3"); }
        return $query;

        // $lista = DB::SELECT("SELECT i.idInstitucion, i.nombreInstitucion,i.punto_venta
        // FROM pedidos p
        // INNER JOIN institucion i ON i.idInstitucion = p.id_institucion
        // WHERE p.contrato_generado IS NOT NULL
        // AND i.nombreInstitucion LIKE '%$request->busqueda%'
        // AND p.id_periodo = '$request->id_periodo'");

        // $lista = DB::SELECT("SELECT i.idInstitucion, i.nombreInstitucion
        // FROM f_venta fv
        // INNER JOIN institucion i ON i.idInstitucion = fv.institucion_id
        // WHERE fv.clientesidPerseo = '$request->cliente'
        // AND fv.id_empresa = '$request->empresa'
        // AND fv.periodo_id = '$request->periodo'
        // GROUP BY i.idInstitucion,i.nombreInstitucion");

        // return $lista;
    }
    public function tr_getPuntosVenta($busqueda){

        $query = DB::SELECT("SELECT  i.idInstitucion, i.nombreInstitucion,i.ruc,i.email,i.telefonoInstitucion,
        i.direccionInstitucion,  c.nombre as ciudad
        FROM institucion i
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        WHERE i.nombreInstitucion LIKE '%$busqueda%'");
        return $query;
    }
    public function traerCobros(Request $request){
        $cobros = DB::SELECT("SELECT * FROM abono ab
        WHERE ab.abono_periodo = $request->periodo");
        $totalCobros = DB::SELECT("SELECT SUM(ab.abono_facturas) AS total_facturas, SUM(ab.abono_notas) AS total_notas,
        COUNT(CASE WHEN ab.abono_notas <> '0.00' THEN 1 END) AS numero_notas, COUNT(CASE WHEN ab.abono_facturas <> '0.00' THEN 1 END) AS numero_facturas
        FROM abono ab WHERE ab.abono_periodo = $request->periodo");
        return [
            'datosCobros' => $cobros,
            'totalCobros' => $totalCobros
        ];
    }

    public function getClienteLocalDocumentos(Request $request)
    {
        // Validar el request
        $request->validate([
            'cedula' => 'required|string',
        ]);

        // Obtener el valor de 'cedula'
        $cedula = $request->cedula;
        $periodo = $request->periodo;
        $empresa = $request->empresa;

        // Realizar las consultas
        $resultados = \DB::table('f_venta')
            ->where('est_ven_codigo', '<>', 3)
            ->whereIn('idtipodoc', [2, 4])
            ->where('ruc_cliente', $cedula)
            ->where('periodo_id', $periodo)
            ->where('id_empresa', $empresa)
            ->get();

        // Retornar los resultados como JSON
        return response()->json($resultados);
    }
    public function getClienteLocal(Request $request)
    {
        // Validar el request
        $request->validate([
            'cedula' => 'required|string',
        ]);

        // Obtener el valor de 'cedula'
        $cedula = $request->input('cedula');

        // Realizar las consultas
        $resultados = DB::table('usuario')
            ->where('cedula', $cedula)
            ->first(); // Si esperas solo un resultado, usa ->first()

        // Verificar si se encontraron resultados
        if ($resultados) {
            // Retornar los resultados como JSON
            return response()->json($resultados, 200);
        } else {
            // Retornar un mensaje de error si no se encontraron resultados
            return response()->json(['message' => 'No se encontraron resultados'], 404);
        }
    }
    public function modificarAbono(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'abono_fecha' => 'required|date',
            'abono_facturas' => 'nullable|numeric',
            'abono_tipo' => 'required|integer|in:0,1,2,3',
            'abono_documento' => 'nullable|string|max:100',
            'abono_concepto' => 'nullable|string|max:500',
            'abono_cuenta' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()
            ], 422);
        }

        // Iniciar transacción
        DB::beginTransaction();

        try {
             // Buscar el abono
             $abono = Abono::findOrFail($request->abono_id);

             if ($abono->abono_documento != $request->abono_documento) {
                 $existingAbono = DB::table('abono')
                     ->where('abono_documento', $request->abono_documento)
                     ->where('abono_id', '!=', $request->abono_id)
                     ->where('abono_estado', 0)
                     ->orderBy('created_at', 'desc')
                     ->first();

                 if ($existingAbono) {
                     return response()->json([
                         'status' => 0,
                         'message' => 'El número de documento ya está en uso por otro abono.',
                     ]);
                 }
             }

            // Actualizar los campos
            $abono->abono_fecha = $request->abono_fecha;
            $abono->abono_facturas = $request->abono_facturas;
            $abono->abono_tipo = $request->abono_tipo;
            $abono->abono_documento = $request->abono_documento;
            $abono->abono_concepto = $request->abono_concepto;
            $abono->abono_cuenta = $request->abono_cuenta;
            // Aquí puedes añadir cualquier otro campo que desees actualizar

            // Guardar los cambios
            $abono->save();
            $this->guardarAbonoHistorico($abono, 4, $request->user_created);
            // Confirmar la transacción
            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Abono actualizado correctamente.',
                'data' => $abono
            ]);
        } catch (\Exception $e) {
            // En caso de error, revertir la transacción
            DB::rollback();
            return response()->json([
                'status' => 0,
                'message' => 'Error al actualizar el abono: ' . $e->getMessage()
            ], 500);
        }
    }
    public function modificarAbonoNotas(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'abono_fecha' => 'required|date',
            'abono_notas' => 'nullable|numeric',
            'abono_tipo' => 'required|integer|in:0,1,2,3',
            'abono_documento' => 'required|string|max:100',
            'abono_concepto' => 'nullable|string|max:500',
            'abono_cuenta' => 'nullable|integer',
            'abono_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()
            ], 422);
        }

        // Iniciar transacción
        DB::beginTransaction();

        try {
            // Buscar el abono
            $abono = Abono::findOrFail($request->abono_id);

            if ($abono->abono_documento != $request->abono_documento) {
                $existingAbono = DB::table('abono')
                ->where('abono_documento', $request->abono_documento)
                ->where('abono_id', '!=', $request->abono_id)
                ->where('abono_estado', 0)
                ->orderBy('created_at', 'desc')
                ->first();

                if ($existingAbono) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'El número de documento ya está en uso por otro abono.',
                    ]);
                }
            }

            // Actualizar los campos
            $abono->abono_fecha = $request->abono_fecha;
            $abono->abono_notas = $request->abono_notas;
            $abono->abono_tipo = $request->abono_tipo;
            $abono->abono_documento = $request->abono_documento;
            $abono->abono_concepto = $request->abono_concepto;
            $abono->abono_cuenta = $request->abono_cuenta;
            // Aquí puedes añadir cualquier otro campo que desees actualizar

            // Guardar los cambios
            $abono->save();

            // Confirmar la transacción
            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Abono actualizado correctamente.',
                'data' => $abono
            ]);
        } catch (\Exception $e) {
            // En caso de error, revertir la transacción
            DB::rollback();
            return response()->json([
                'status' => 0,
                'message' => 'Error al actualizar el abono: ' . $e->getMessage()
            ], 500);
        }
    }
    public function reporteAbonoVentas(Request $request) {
        // Ejecutar la primera consulta
        $reporte = DB::select("SELECT
                        fv.idtipodoc,
                        ft.tdo_id,
                        ft.tdo_nombre,
                        ep.descripcion_corta AS empresa,
                        ep.id AS id_empresa,
                        i.nombreInstitucion,
                        CONCAT(usu.nombres,' ',usu.apellidos) AS asesor,
                        i.idInstitucion,
                        i.punto_venta,
                        fv.institucion_id,
                        fv.ruc_cliente,
                        ROUND(SUM(fv.ven_subtotal), 2) AS subtotal_total,
                        ROUND(SUM(fv.ven_descuento), 2) AS descuento_total,
                        ROUND(SUM(fv.ven_valor), 2) AS valor_total
                    FROM f_tipo_documento ft
                    INNER JOIN f_venta fv ON fv.idtipodoc = ft.tdo_id
                    INNER JOIN institucion i ON i.idInstitucion = fv.institucion_id
                    INNER JOIN empresas ep ON ep.id = fv.id_empresa
                    LEFT JOIN usuario usu on usu.idusuario= i.asesor_id
                    WHERE fv.est_ven_codigo <> 3
                    AND fv.periodo_id = ?
                    GROUP BY
                        i.nombreInstitucion,
                        ft.tdo_nombre,
                        i.idInstitucion,
                        fv.institucion_id,
                        fv.idtipodoc,
                        ft.tdo_id,
                        fv.ruc_cliente,
                        ep.descripcion_corta,
                        i.punto_venta,
                        ep.id;", [$request->periodo]);

        // Recorrer cada registro del reporte para añadir el abono_total
        foreach ($reporte as $key => $registro) {
            // Construir la consulta de abono con condiciones específicas
            $abono_tipo = DB::select("SELECT
                                        CASE
                                            WHEN ? = 4 AND ? = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
                                            WHEN ? = 3 AND ? = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
                                            WHEN ? = 1 AND ? = 0 THEN COALESCE(SUM(ab.abono_facturas), 0)
                                            WHEN ? = 3 AND ? = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
                                            WHEN ? = 4 AND ? = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
                                            WHEN ? = 1 AND ? = 1 THEN COALESCE(SUM(ab.abono_facturas), 0)
                                            ELSE 0
                                        END AS abono_total
                                    FROM abono ab
                                    WHERE ab.abono_ruc_cliente = ?
                                    AND ab.abono_periodo = ?
                                    AND ab.abono_empresa = ?
                                    AND ab.abono_estado = 0", [
                                        $registro->idtipodoc, $registro->punto_venta,
                                        $registro->idtipodoc, $registro->punto_venta,
                                        $registro->idtipodoc, $registro->punto_venta,
                                        $registro->idtipodoc, $registro->punto_venta,
                                        $registro->idtipodoc, $registro->punto_venta,
                                        $registro->idtipodoc, $registro->punto_venta,
                                        $registro->ruc_cliente, $request->periodo,
                                        $registro->id_empresa
                                    ]);

            // Añadir el abono_total al registro
            $reporte[$key]->abono_total = $abono_tipo[0]->abono_total ?? 0;

            // //Devolucion por el Detalle Venta
            // $devolucion = DB::select("SELECT ROUND(SUM(dv.det_ven_dev * dv.det_ven_valor_u), 2) AS devolucion FROM f_detalle_venta dv
            // INNER JOIN f_venta fv ON fv.ven_codigo = dv.ven_codigo AND fv.id_empresa = dv.id_empresa
            // WHERE fv.ruc_cliente = '$registro->ruc_cliente'
            // AND dv.id_empresa = '$registro->id_empresa';");

            // Devolución por el Detalle Venta
            $devolucion = DB::select("SELECT
                    ROUND(SUM(
                        (dv.det_ven_dev * dv.det_ven_valor_u) * (1 - (fv.ven_desc_por / 100))
                    ), 2) AS devolucion
                FROM f_detalle_venta dv
                INNER JOIN f_venta fv ON fv.ven_codigo = dv.ven_codigo
                    AND fv.id_empresa = dv.id_empresa
                WHERE fv.ruc_cliente = '$registro->ruc_cliente'
                    AND dv.id_empresa = '$registro->id_empresa'
                    AND dv.det_ven_dev > 0
            ");

            // // Añadir la devolucion al registro
            $reporte[$key]->devolucion = $devolucion[0]->devolucion ?? 0;
        }
        // SELECT
        // fv.ruc_cliente, i.nombreInstitucion, ep.descripcion_corta AS empresa, fv.ven_tipo_inst,
        // ROUND(SUM(fv.ven_subtotal), 2) AS subtotal_total,
        // ROUND(SUM(fv.ven_descuento), 2) AS descuento_total, ROUND(SUM(fv.ven_valor), 2) AS valor_total,
        // ROUND(SUM(COALESCE(a.abono_facturas, 0) + COALESCE(a.abono_notas, 0)), 2) AS abono_total
        // FROM  f_venta fv
        // LEFT JOIN abono a ON a.abono_ruc_cliente = fv.ruc_cliente
        // LEFT JOIN institucion i ON fv.institucion_id = i.idInstitucion
        // LEFT JOIN empresas ep ON ep.id = fv.id_empresa
        // WHERE (fv.est_ven_codigo <> 3 AND fv.periodo_id = '$request->periodo')
        // OR (a.abono_estado = 0 AND a.abono_periodo = '$request->periodo')
        // GROUP BY fv.ruc_cliente, i.nombreInstitucion, ep.descripcion_corta, fv.ven_tipo_inst;

        return $reporte;
    }

    // public function reporteAbonoVentasXD(Request $request)
    // {
    //     // Paso 1: Obtener el reporte principal de f_venta
    //     $reporte = DB::table('f_tipo_documento as ft')
    //         ->join('f_venta as fv', 'fv.idtipodoc', '=', 'ft.tdo_id')
    //         ->join('institucion as i', 'i.idInstitucion', '=', 'fv.institucion_id')
    //         ->join('empresas as ep', 'ep.id', '=', 'fv.id_empresa')
    //         ->leftJoin('usuario as usu', 'usu.idusuario', '=', 'i.asesor_id')
    //         ->where('fv.est_ven_codigo', '<>', 3)
    //         ->where('fv.periodo_id', '=', $request->periodo)
    //         ->where('fv.ven_desc_por', '<',100)
    //         ->where('fv.idtipodoc', '<>', 16)
    //         ->select(
    //             'fv.idtipodoc',
    //             'ft.tdo_id',
    //             'ft.tdo_nombre',
    //             'ep.descripcion_corta AS empresa',
    //             'ep.id AS id_empresa',
    //             'i.nombreInstitucion',
    //             DB::raw("CONCAT(usu.nombres, ' ', usu.apellidos) AS asesor"),
    //             'i.idInstitucion',
    //             'i.punto_venta',
    //             'fv.institucion_id',
    //             'fv.ruc_cliente',
    //             DB::raw('ROUND(SUM(fv.ven_subtotal), 2) AS subtotal_total'),
    //             DB::raw('ROUND(SUM(fv.ven_descuento), 2) AS descuento_total'),
    //             DB::raw('ROUND(SUM(fv.ven_valor), 2) AS valor_total'),
    //             DB::raw('GROUP_CONCAT(fv.ven_codigo) AS todos_los_documentos')
    //         )
    //         ->groupBy(
    //             'ft.tdo_id',
    //             'ft.tdo_nombre',
    //             'ep.id',
    //             'i.idInstitucion',
    //             'i.nombreInstitucion',
    //             'fv.institucion_id',
    //             'fv.idtipodoc',
    //             'fv.ruc_cliente',
    //             'ep.descripcion_corta',
    //             'i.punto_venta'
    //         )
    //         ->get();

    //     // Paso 2: Procesar cada registro para obtener el abono y devoluciones
    //     foreach ($reporte as $key => $registro) {
    //         // Obtener el abono total para cada registro
    //         $abono_tipo = DB::table('abono as ab')
    //             ->select(DB::raw("CASE
    //                 WHEN {$registro->idtipodoc} = 4 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
    //                 WHEN {$registro->idtipodoc} = 3 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
    //                 WHEN {$registro->idtipodoc} = 1 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_facturas), 0)
    //                 WHEN {$registro->idtipodoc} = 3 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
    //                 WHEN {$registro->idtipodoc} = 4 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
    //                 WHEN {$registro->idtipodoc} = 1 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_facturas), 0)
    //                 ELSE 0
    //             END AS abono_total,
    //              SUM(CASE WHEN ab.abono_tipo = 3 THEN ab.abono_valor_retencion ELSE 0 END) AS retencion_total"))
    //             ->where('ab.abono_ruc_cliente', '=', $registro->ruc_cliente)
    //             ->where('ab.abono_periodo', '=', $request->periodo)
    //             ->where('ab.abono_empresa', '=', $registro->id_empresa)
    //             ->where('ab.abono_estado', '=', 0)
    //             ->first();

    //         // Asignar el resultado al objeto
    //         $reporte[$key]->abono_total = $abono_tipo->abono_total*1 ?? 0;
    //         $reporte[$key]->retencion_total = $abono_tipo->retencion_total * 1 ?? 0;
            
    //         // Paso 3: Obtener el detalle de devolución
    //         $todos_los_documentos = explode(',', $registro->todos_los_documentos);
    //         $documentosDevoluciones = [];
    //         $valorTotalDevolucion = 0;

    //         foreach ($todos_los_documentos as $documento) {
    //             $detallesDevolucion = $this->obtenerDetallesDevolucionXD($documento, $registro->id_empresa);
    //             // Verificamos si obtenemos un arreglo de devoluciones
    //             if (is_array($detallesDevolucion) && !empty($detallesDevolucion)) {
    //                 // Fusionamos los detalles de devolución en el arreglo principal
    //                 $documentosDevoluciones = array_merge($documentosDevoluciones, $detallesDevolucion);
    //                 // Sumar el valor de "ValorConDescuento" de cada detalle de devolución
    //                 foreach ($detallesDevolucion as $devolucion) {
    //                     // Verificar si la propiedad ValorConDescuento existe antes de sumarla
    //                     if (isset($devolucion->ValorConDescuento)) {
    //                         $valorTotalDevolucion += $devolucion->ValorConDescuento;
    //                     }
    //                 }
    //             }
    //             // $detallesDevolucion = DB::table('codigoslibros_devolucion_header as cdh')
    //             //     ->join('codigoslibros_devolucion_son as cls', 'cdh.id', '=', 'cls.codigoslibros_devolucion_id')
    //             //     ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'cdh.id_cliente')
    //             //     ->where('cls.documento', '=', $documento)
    //             //     ->groupBy('cls.documento', 'cls.id_empresa')
    //             //     ->select(
    //             //         'cls.documento',
    //             //         'cls.id_empresa',
    //             //         DB::raw('ROUND(SUM(cls.precio), 2) as total_precio')
    //             //     )
    //             //     ->get();

    //             //     foreach ($detallesDevolucion as $item) {
    //             //         // Consultar las ventas relacionadas con el documento
    //             //         $fVentas = DB::table('f_venta as fv')
    //             //             ->join('f_detalle_venta as fdv', function($join) {
    //             //                 $join->on('fdv.ven_codigo', '=', 'fv.ven_codigo')
    //             //                     ->on('fdv.id_empresa', '=', 'fv.id_empresa');
    //             //             })
    //             //             ->where('fv.ven_codigo', '=', $item->documento)
    //             //             ->where('fv.id_empresa', '=', $item->id_empresa)
    //             //             ->where('fv.est_ven_codigo', '<>', 3)
    //             //             ->where('fdv.det_ven_dev', '>', 0)  // Solo tomar los detalles con devolución
    //             //             ->first();  // Usamos `first()` para obtener el primer resultado

    //             //         // Si se encuentra una venta y tiene detalles de devolución
    //             //         if ($fVentas) {
    //             //             $descuento = $fVentas->ven_desc_por;
    //             //             $valorConDescuento = round(($item->total_precio - (($item->total_precio * $descuento) / 100)), 2);
    //             //             $devolucion_total += $valorConDescuento;  // Sumar el valor con descuento
    //             //         }
    //             //         // Si no hay detalles de devolución (fdv.det_ven_dev <= 0), no se hace nada
    //             //     }
    //         }

    //         // Asignar el total de la devolución
    //         // $reporte[$key]->devolucion = round($devolucion_total, 2);
    //         $reporte[$key]->devolucion = round($valorTotalDevolucion,2);
    //         $reporte[$key]->devolucion_todas = $documentosDevoluciones;
    //     }

    //     return $reporte;
    // }


    public function reporteAbonoVentasXD(Request $request)
    {
        // Paso 1: Obtener el reporte principal de f_venta
        $reporte = DB::table('f_tipo_documento as ft')
            ->join('f_venta as fv', 'fv.idtipodoc', '=', 'ft.tdo_id')
            ->join('institucion as i', 'i.idInstitucion', '=', 'fv.institucion_id')
            ->join('empresas as ep', 'ep.id', '=', 'fv.id_empresa')
            ->leftJoin('usuario as usu', 'usu.idusuario', '=', 'i.asesor_id')
            ->where('fv.est_ven_codigo', '<>', 3)
            ->where('fv.periodo_id', '=', $request->periodo)
            ->where('fv.ven_desc_por', '<', 100)
            ->where('fv.idtipodoc', '<>', 16)
            ->where('fv.idtipodoc', '<>', 17)
            ->select(
                'fv.idtipodoc',
                'ft.tdo_id',
                'ft.tdo_nombre',
                'ep.descripcion_corta AS empresa',
                'ep.id AS id_empresa',
                'i.nombreInstitucion',
                DB::raw("CONCAT(usu.nombres, ' ', usu.apellidos) AS asesor"),
                'i.idInstitucion',
                'i.punto_venta',
                'fv.institucion_id',
                'fv.ruc_cliente',
                DB::raw('ROUND(SUM(fv.ven_subtotal), 2) AS subtotal_total'),
                DB::raw('ROUND(SUM(fv.ven_descuento), 2) AS descuento_total'),
                DB::raw('ROUND(SUM(fv.ven_valor), 2) AS valor_total'),
                DB::raw('GROUP_CONCAT(fv.ven_codigo) AS todos_los_documentos')
            )
            ->groupBy(
                'ft.tdo_id',
                'ft.tdo_nombre',
                'ep.id',
                'i.idInstitucion',
                'i.nombreInstitucion',
                'fv.institucion_id',
                'fv.idtipodoc',
                'fv.ruc_cliente',
                'ep.descripcion_corta',
                'i.punto_venta'
            )
            ->get();

        // Paso 2: Procesar cada registro para obtener el abono y devoluciones
        foreach ($reporte as $key => $registro) {
            // Obtener el abono total para cada registro
            $abono_tipo = DB::table('abono as ab')
                ->select(DB::raw("CASE
                    WHEN {$registro->idtipodoc} = 4 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 3 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 1 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_facturas), 0)
                    WHEN {$registro->idtipodoc} = 3 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 4 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 1 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_facturas), 0)
                    ELSE 0
                END AS abono_total,
                SUM(CASE WHEN ab.abono_tipo = 3 THEN ab.abono_valor_retencion ELSE 0 END) AS retencion_total"))
                ->where('ab.abono_ruc_cliente', '=', $registro->ruc_cliente)
                ->where('ab.abono_periodo', '=', $request->periodo)
                ->where('ab.abono_empresa', '=', $registro->id_empresa)
                ->where('ab.abono_estado', '=', 0)
                ->where('abono_tipo', '<>', '4')
                ->where('abono_cuenta', '<>', '6')
                ->first();

            $abono_liquidacion = DB::table('abono as ab')
                ->select(DB::raw("CASE
                    WHEN {$registro->idtipodoc} = 4 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 3 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 1 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_facturas), 0)
                    WHEN {$registro->idtipodoc} = 3 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 4 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 1 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_facturas), 0)
                    ELSE 0
                END AS abono_total"))
                ->where('ab.abono_ruc_cliente', '=', $registro->ruc_cliente)
                ->where('ab.abono_periodo', '=', $request->periodo)
                ->where('ab.abono_empresa', '=', $registro->id_empresa)
                ->where('ab.abono_estado', '=', 0)
                ->where('abono_cuenta', '=', '6')
                ->where('abono_tipo', '<>', '4')
                ->first();

            $abono_cruce = DB::table('abono as ab')
                ->select(DB::raw("CASE
                    WHEN {$registro->idtipodoc} = 4 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 3 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 1 AND {$registro->punto_venta} = 0 THEN COALESCE(SUM(ab.abono_facturas), 0)
                    WHEN {$registro->idtipodoc} = 3 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 4 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_notas), 0)
                    WHEN {$registro->idtipodoc} = 1 AND {$registro->punto_venta} = 1 THEN COALESCE(SUM(ab.abono_facturas), 0)
                    ELSE 0
                END AS abono_total"))
                ->where('ab.abono_ruc_cliente', '=', $registro->ruc_cliente)
                ->where('ab.abono_periodo', '=', $request->periodo)
                ->where('ab.abono_empresa', '=', $registro->id_empresa)
                ->where('ab.abono_estado', '=', 0)
                ->where('ab.abono_cuenta', '=', '9')
                ->first();
    
            // Asignar el resultado al objeto
            $reporte[$key]->abono_total = $abono_tipo->abono_total * 1 ?? 0;
            $reporte[$key]->retencion_total = $abono_tipo->retencion_total * 1 ?? 0;
            $reporte[$key]->abono_liquidacion = $abono_liquidacion->abono_total * 1 ?? 0;
            $reporte[$key]->abono_cruce = $abono_cruce->abono_total * 1 ?? 0;

            // Paso 3: Obtener el detalle de devolución usando el nuevo método
            $devoluciones = $this->obtenerDevoluciones($registro->todos_los_documentos, $registro->id_empresa);

            // Asignar el total de la devolución y los detalles
            $reporte[$key]->devolucion = $devoluciones['valorTotalDevolucion'];
            $reporte[$key]->devolucion_todas = $devoluciones['documentosDevoluciones'];
        }

        // Paso 4: Obtener los clientes únicos sin empresa
        $clientesUnicos = $reporte->pluck('idInstitucion')->unique();

        // Paso 5: Obtener la información de devoluciones para los clientes sin empresa
        foreach ($clientesUnicos as $idInstitucion) {
            // Buscar el cliente en el reporte para obtener sus datos
            $clienteReporte = $reporte->firstWhere('idInstitucion', $idInstitucion);

            // Buscar los registros de la tabla codigoslibros_devolucion_header_facturador para los clientes sin empresa (id_empresa == 0)
            $devolucionesSinEmpresa = DB::table('codigoslibros_devolucion_header_facturador as h')
                ->join('codigoslibros_devolucion_son_facturador as d', 'd.codigoslibros_devolucion_header_facturador_id', '=', 'h.id')
                ->where('h.id_cliente', '=', $idInstitucion)
                ->where('h.periodo_id', '=', $request->periodo)
                ->where('h.id_empresa', '=', 0)
                ->select(
                    'h.id_cliente',
                    'd.descuento',
                    DB::raw('SUM(d.precio * d.cantidad) as devolucion_total')
                )
                ->groupBy('h.id_cliente')
                ->first();

            if ($devolucionesSinEmpresa) {
                // Calcular el total de la devolución (redondeado a 2 decimales)
                $devolucionTotal = round($devolucionesSinEmpresa->devolucion_total, 2);

                // Aplicar el descuento a la devolución
                $devolucionConDescuento = $devolucionTotal - ($devolucionTotal * $devolucionesSinEmpresa->descuento) / 100;

                if($devolucionConDescuento>0){
                    // Crear el objeto para agregarlo al reporte
                    $reporte[] = (object) [
                        'idtipodoc' => 10, // Código de tipo "DEVOLUCION SIN EMPRESA"
                        'tdo_id' => 10,
                        'tdo_nombre' => 'DEVOLUCION SIN EMPRESA',
                        'empresa' => 'SIN EMPRESA',
                        'id_empresa' => 0,
                        'nombreInstitucion' => $clienteReporte->nombreInstitucion, // Tomar del reporte original
                        'asesor' => $clienteReporte->asesor, // Tomar del reporte original
                        'idInstitucion' => $idInstitucion,
                        'punto_venta' => $clienteReporte->punto_venta, // Tomar del reporte original
                        'institucion_id' => $idInstitucion,
                        'ruc_cliente' => $clienteReporte->ruc_cliente, // Tomar del reporte original
                        'subtotal_total' => 0,
                        'descuento_total' => 0,
                        'valor_total' => 0,
                        'todos_los_documentos' => '',
                        'abono_total' => 0,
                        'retencion_total' => 0,
                        'devolucion' => $devolucionConDescuento,
                        'devolucion_todas' => [], // Puedes dejarlo vacío o añadir detalles si es necesario
                    ];
                }
            }
        }

        return $reporte;

    }
    
    public function obtenerDevoluciones($todos_los_documentos, $id_empresa)
    {
        // Convertir la cadena de documentos separados por comas en un array
        $documentosArray = explode(',', $todos_los_documentos);

        // Obtener las devoluciones en una sola consulta
        // $devoluciones = DB::table('codigoslibros_devolucion_son as cls')
        //     ->join('f_venta as fv', 'fv.ven_codigo', '=', 'cls.documento')
        //     ->join('codigoslibros_devolucion_header as cdh', 'cdh.id', '=', 'cls.codigoslibros_devolucion_id')
        //     ->whereIn('cls.documento', $documentosArray)
        //     ->where('cls.id_empresa', $id_empresa)
        //     ->select(
        //         'cls.documento',
        //         'cls.id_empresa',
        //         DB::raw('SUM(cls.precio * cls.combo_cantidad_devuelta) as total_precio_tipo1'),
        //         DB::raw('SUM(cls.precio) as total_precio_tipo0'),
        //         'fv.ven_desc_por',
        //         'fv.institucion_id',
        //         'cdh.codigo_devolucion',
        //     )
        //     ->groupBy('cls.documento', 'cls.id_empresa', 'fv.ven_desc_por', 'fv.institucion_id')
        //     ->get();

        $devoluciones = DB::table('f_detalle_venta as fd')
            ->join('f_venta as fv', function($join) {
                $join->on('fd.ven_codigo', '=', 'fv.ven_codigo')
                    ->on('fd.id_empresa', '=', 'fv.id_empresa');
            })
            ->whereIn('fd.ven_codigo', $documentosArray)
            ->where('fd.id_empresa', $id_empresa)
            ->select(
                'fd.ven_codigo',
                'fd.id_empresa',
                DB::raw('SUM(det_ven_dev * det_ven_valor_u) as total_precio'),
                'fv.ven_desc_por',
                'fv.institucion_id'
            )
            ->groupBy('fd.ven_codigo', 'fd.id_empresa', 'fv.ven_desc_por', 'fv.institucion_id')
            ->get();

        // Calcular el valor total de las devoluciones
        $valorTotalDevolucion = 0;
        $documentosDevoluciones = [];

        foreach ($devoluciones as $devolucion) {
            // Calcular el total de devoluciones (suma de tipo1 y tipo0)
            $totalDevoluciones = round($devolucion->total_precio,2);

            // Calcular el valor con descuento
            $valorConDescuento = round($totalDevoluciones - (($totalDevoluciones * $devolucion->ven_desc_por)/ 100), 2);

            // Sumar al valor total de devoluciones
            $valorTotalDevolucion += $valorConDescuento;

            // Almacenar los detalles de la devolución con la estructura original
            $documentosDevoluciones[] = [
                'documento' => $devolucion->ven_codigo,
                'id_empresa' => $devolucion->id_empresa,
                'institucion_id' => $devolucion->institucion_id, // Usamos institucion_id como id_cliente
                'total_precio' => $totalDevoluciones,
                'descuento' => $devolucion->ven_desc_por,
                'ValorConDescuento' => $valorConDescuento,
            ];
        }

        return [
            'valorTotalDevolucion' => round($valorTotalDevolucion, 2),
            'documentosDevoluciones' => $documentosDevoluciones,
        ];
    }

    public function obtenerDetallesDevolucionXD($documento, $empresa)
{
    // Ejecutamos la consulta en la base de datos para obtener los detalles de devolución
    $detallesDevolucion = DB::table('codigoslibros_devolucion_header as cdh')
        ->join('codigoslibros_devolucion_son as cls', 'cdh.id', '=', 'cls.codigoslibros_devolucion_id')
        ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'cdh.id_cliente')  // LEFT JOIN para traer nombreInstitucion
        ->where('cdh.estado','<>', 0)
        ->where('cls.documento', '=', $documento)
        ->where('cls.id_empresa', '=', $empresa)
        ->groupBy('cdh.codigo_devolucion', 'cls.documento', 'cls.id_empresa', 'cls.id_cliente', 'i.nombreInstitucion')
        ->select('cdh.codigo_devolucion', 'cls.documento', 'cls.id_empresa', 'cls.id_cliente', 'i.nombreInstitucion', DB::raw('ROUND(SUM(cls.precio), 2) as total_precio'))
        ->get();

    // Convertimos la colección a un arreglo
    $detallesDevolucionArray = $detallesDevolucion->toArray();

    // Recorrer cada uno de los detalles de devolución
    foreach ($detallesDevolucionArray as $key => $item) {
        // Consultar las ventas relacionadas con el documento
        $fVentas = DB::table('f_venta as fv')
            ->join('f_detalle_venta as fdv', function($join) {
                $join->on('fdv.ven_codigo', '=', 'fv.ven_codigo')
                     ->on('fdv.id_empresa', '=', 'fv.id_empresa');
            })
            ->where('fv.ven_codigo', '=', $item->documento)
            ->where('fv.id_empresa', '=', $item->id_empresa)
            ->where('fv.est_ven_codigo', '<>', 3)
            ->where('fdv.det_ven_dev', '>', 0)
            ->first();  // Usamos `first()` para obtener el primer resultado

        // Verificamos si se obtuvo un resultado de la venta
        if ($fVentas) {
            $detallesDevolucionArray[$key]->descuento = $fVentas->ven_desc_por;
            // Calcular el valor con descuento para este detalle de devolución
            $detallesDevolucionArray[$key]->ValorConDescuento = round($item->total_precio - (($item->total_precio * $fVentas->ven_desc_por) / 100), 2);
        } else {
            // Si no hay una venta asociada, asignamos 0 al descuento y el valor con descuento
            $detallesDevolucionArray[$key]->descuento = 0;
            $detallesDevolucionArray[$key]->total_precio = round($item->total_precio, 2);
        }
    }

    // Retornamos los detalles de devolución como un arreglo
    return $detallesDevolucionArray;
}





    public function reporteVariacionVentas(Request $request) {
        $query = DB::SELECT("SELECT fv.ven_codigo, fv.ruc_cliente,  fv.institucion_id, fv.ven_tipo_inst, i.nombreInstitucion AS nombre, e.descripcion_corta AS empresa
        FROM f_venta fv
        INNER JOIN institucion i ON i.idInstitucion = fv.institucion_id
        INNER JOIN empresas e ON e.id = fv.id_empresa
        WHERE fv.est_ven_codigo <> 3
        AND fv.periodo_id = '$request->periodo'");
        return $query;
    }
    public function clientesAbonoNoDocumentos(Request $request) {
        // Validar el periodo recibido en la solicitud
        $request->validate([
            'periodo' => 'required|string',
        ]);

        // Primera consulta: obtener datos de abonos
        $query = DB::SELECT("SELECT
                    ab.abono_ruc_cliente AS `Ruc/Ci Cliente`,
                    e.nombre AS `Empresa`,
                    ab.abono_empresa,
                    COUNT(CASE WHEN ab.abono_notas > 0 THEN 1 END) AS `Abono(Notas)`,
                    COUNT(CASE WHEN ab.abono_facturas > 0 THEN 1 END) AS `Abono(Facturas)`,
                    COUNT(CASE WHEN f.idtipodoc IN (3, 4) THEN 1 END) AS `Documento(Notas)`,
                    COUNT(CASE WHEN f.idtipodoc = 1 THEN 1 END) AS `Documento(Prefacturas)`
                FROM
                    abono ab
                LEFT JOIN
                    f_venta f ON ab.abono_ruc_cliente = f.ruc_cliente
                              AND ab.abono_empresa = f.id_empresa
                INNER JOIN
                    empresas e ON ab.abono_empresa = e.id
                WHERE
                    ab.abono_estado = 0
                AND
                    ab.abono_periodo = ?
                GROUP BY
                    ab.abono_ruc_cliente, e.nombre, ab.abono_empresa
                HAVING
                    COUNT(f.ven_codigo) = 0;", [$request->periodo]);

        // Obtener RUC/CIs de los clientes
        $rucClientes = array_column($query, 'Ruc/Ci Cliente');
        $rucClientesStr = implode(',', array_map(function($ruc) {
            return "'" . $ruc . "'";
        }, $rucClientes));

        // Segunda consulta: obtener nombres y apellidos de los usuarios
        $usuarios = DB::SELECT("SELECT
                    c.cedula,
                    CONCAT(c.nombres, ' ', c.apellidos) AS `Nombres y Apellidos Cliente`
                FROM
                    usuario c
                WHERE
                    c.cedula IN ($rucClientesStr);");

        // Crear un arreglo asociativo para facilitar la combinación de resultados
        $usuariosAssoc = [];
        foreach ($usuarios as $usuario) {
            $usuariosAssoc[$usuario->cedula] = $usuario->{'Nombres y Apellidos Cliente'};
        }

        // Combinar resultados
        foreach ($query as &$item) {
            $item->{'Nombres y Apellidos Cliente'} = $usuariosAssoc[$item->{'Ruc/Ci Cliente'}] ?? null;
        }

        // Devolver la respuesta
        return response()->json($query);
    }


    public function getSalesAndPayments(Request $request)
    {
        $request->validate([
            'institucion' => 'required|integer',
            'periodo' => 'required|integer',
        ]);

        $institucionId = $request->institucion;
        $periodoId = $request->periodo;
        $ventas = DB::table('f_venta as fv')
            ->where('fv.institucion_id', $institucionId)
            ->where('fv.periodo_id', $periodoId)
            ->where('fv.est_ven_codigo','<>', 3)
            ->get();

        $result = [];
        $valorVentaNeta = 0;
        $valorVentaBruta = 0;
        $valorAbonoTotal = 0;
        $descuentoPorcentaje = 0;

        $rucs = [];
        $descuentos = [];
        $result['documentos'] = [];

        foreach ($ventas as $venta) {
            $valorVentaNeta += round($venta->ven_valor, 2);
            $valorVentaBruta += round($venta->ven_subtotal, 2);

            if ($venta->ven_desc_por) {
                if($venta->idtipodoc==1||$venta->idtipodoc==3||$venta->idtipodoc==4){
                    $descuentos[] = $venta->ven_desc_por;
                }
            }

            if (!in_array($venta->ruc_cliente, $rucs)) {
                $rucs[] = $venta->ruc_cliente;
            }

            $result['documentos'][] = [
                'ven_codigo' => $venta->ven_codigo,
                'ruc_cliente' => $venta->ruc_cliente,
                'ven_subtotal' => $venta->ven_subtotal,
                'ven_valor' => $venta->ven_valor,
                'descuento_porcentaje' => $venta->ven_desc_por,
                'valor_desvuento' => $venta->ven_descuento,
                'idtipodoc'=> $venta->idtipodoc,
            ];
        }

        foreach ($rucs as $ruc) {
            $valorAbono = DB::table('abono as ab')
                ->where('ab.abono_ruc_cliente', $ruc)
                ->sum(DB::raw('ab.abono_facturas + ab.abono_notas'));

            $valorAbono = round($valorAbono, 2);
            $valorAbonoTotal += $valorAbono;
        }

        $descuentosUnicos = array_unique($descuentos);

        $result['total_venta'] = $valorVentaNeta;
        $result['total_ventaBruta'] = $valorVentaBruta;
        $result['total_abono'] = $valorAbonoTotal;
        $result['porcentaje_descuento'] = !empty($descuentosUnicos) ? $descuentosUnicos[0] : 0;


        return response()->json($result);
    }

    public function obtenerVentas(Request $request)
    {
        $periodo = $request->input('periodo');

        // Ejecuta la consulta SQL
        $ventas = DB::table('f_venta as fv')
        ->leftJoin('1_4_tipo_venta as tv', 'tv.tip_ven_codigo', '=', 'fv.tip_ven_codigo')
        ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'fv.institucion_id')
        ->leftJoin('empresas as e', 'e.id', '=', 'fv.id_empresa')
        ->leftJoin('f_tipo_documento as ft', 'ft.tdo_id', '=', 'fv.idtipodoc')
        ->leftJoin('usuario as u', 'u.idusuario', '=', 'fv.ven_cliente')
        ->leftJoin('f_detalle_venta as fdv', function($join) {
            $join->on('fdv.ven_codigo', '=', 'fv.ven_codigo')
                ->on('fdv.id_empresa', '=', 'fv.id_empresa');
        })
        ->select(
            'fv.ven_codigo as codigo',
            'ft.tdo_nombre as documento',
            'tv.tip_ven_nombre as tipo_venta',
            'fv.ven_idproforma as proforma',
            'fv.ven_subtotal as valor_bruto',
            'i.nombreInstitucion as Lugar_despacho_documento',
            'e.descripcion_corta as empresa',
            DB::raw("CONCAT(u.nombres, ' ', u.apellidos) as cliente_documento"),
            DB::raw("SUM(fdv.det_ven_cantidad) as cantidad_detalles")
        )
        ->where('fv.est_ven_codigo', '<>', 3)
        ->where('fv.periodo_id', $periodo)
        ->groupBy(
            'fv.ven_codigo',
            'ft.tdo_nombre',
            'tv.tip_ven_nombre',
            'fv.ven_idproforma',
            'fv.ven_subtotal',
            'i.nombreInstitucion',
            'e.descripcion_corta',
            'u.nombres',
            'u.apellidos'
        )
        ->get();

        // Retorna los resultados en formato JSON
        return response()->json($ventas);
    }

    // public function obtenerDetallesDevolucion(Request $request)
    // {
    //     // Validamos que el parámetro 'documento' esté presente
    //     $request->validate([
    //         'documento' => 'required|string',
    //     ]);

    //     // Recuperamos el documento desde el parámetro de la solicitud
    //     $documento = $request->input('documento');
    //     $empresa = $request->input('empresa');

    //    // Ejecutamos la consulta en la base de datos para obtener los detalles de devolución
    //     $detallesDevolucion = DB::table('codigoslibros_devolucion_header as cdh')
    //     ->join('codigoslibros_devolucion_son as cls', 'cdh.id', '=', 'cls.codigoslibros_devolucion_id')
    //     ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'cdh.id_cliente')  // LEFT JOIN para traer nombreInstitucion
    //     ->where('cdh.estado','<>', 0)
    //     ->where('cls.tipo_codigo', '=', 0)
    //     ->where('cls.documento', '=', $documento)
    //     ->where('cls.id_empresa', '=', $empresa)
    //     ->groupBy('cdh.codigo_devolucion', 'cdh.estado', 'cls.documento', 'cls.id_empresa', 'cls.id_cliente', 'i.nombreInstitucion')
    //     ->select('cdh.id', 'cdh.codigo_devolucion','cdh.estado', 'cls.documento', 'cls.id_empresa', 'cls.id_cliente', 'i.nombreInstitucion', DB::raw('ROUND(SUM(cls.precio), 2) as total_precio'))
    //     ->get();

    //     // Convertimos la colección a un arreglo
    //     $detallesDevolucionArray = $detallesDevolucion->toArray();

    //     // Recorrer cada uno de los detalles de devolución
    //     foreach ($detallesDevolucionArray as $key => $item) {
    //         // Consultar las ventas relacionadas con el documento
    //         $fVentas = DB::table('f_venta as fv')
    //             ->join('f_detalle_venta as fdv', function($join) {
    //                 $join->on('fdv.ven_codigo', '=', 'fv.ven_codigo')
    //                     ->on('fdv.id_empresa', '=', 'fv.id_empresa');
    //             })
    //             ->where('fv.ven_codigo', '=', $item->documento)
    //             ->where('fv.id_empresa', '=', $empresa)
    //             ->where('fv.est_ven_codigo', '<>', 3)
    //             ->where('fdv.det_ven_dev', '>', 0)
    //             ->first();  // Usamos `first()` para obtener el primer resultado

    //         // Verificamos si se obtuvo un resultado de la venta
    //         if ($fVentas) {
    //             $detallesDevolucionArray[$key]->descuento = $fVentas->ven_desc_por;
    //             // Calcular el valor con descuento para este detalle de devolución
    //             $detallesDevolucionArray[$key]->ValorConDescuento = round($item->total_precio - (($item->total_precio * $fVentas->ven_desc_por) / 100), 2);
    //         } else {
    //             // Si no hay una venta asociada, asignamos 0 al descuento y el valor con descuento
    //             $detallesDevolucionArray[$key]->descuento = 0;
    //             $detallesDevolucionArray[$key]->total_precio = round($item->total_precio, 2);
    //         }
    //     }
    //     $detallesDevolucionArray =collect($detallesDevolucionArray);

    //     foreach ($detallesDevolucionArray as $key => $item) {
    //         // Obtener los códigos relacionados con la devolución
    //         $codigos = DB::table('codigoslibros_devolucion_son as cls')
    //             ->join('codigoslibros_devolucion_header as cdh', 'cdh.id', '=', 'cls.codigoslibros_devolucion_id')
    //             ->where('cdh.estado','<>', 0)
    //             ->where('cls.tipo_codigo', '=', 0)
    //             ->where('cls.codigoslibros_devolucion_id', '=', $item->id)
    //             ->where('cls.documento', '=', $item->documento)
    //             ->select('cls.codigo', 'cls.codigo_union','cls.pro_codigo', 'cls.tipo_codigo')
    //             ->get();

    //         $detallesDevolucionArray[$key]->codigos = $codigos;

    //         // Filtrar códigos únicos por pro_codigo (eliminamos duplicados)
    //         $codigosUnicos = $codigos->unique('pro_codigo');

    //         // Inicializamos un arreglo vacío para almacenar los detalles de venta
    //         $detalleVenta = [];

    //         // Iteramos sobre los códigos únicos para obtener los detalles de venta
    //         foreach ($codigosUnicos as $codigo) {
    //             // Consultamos el detalle de venta para cada código de producto
    //             $detallesDeVentaPorCodigo = DB::table('f_detalle_venta as fdv')
    //                 ->where('fdv.ven_codigo', '=', $item->documento)
    //                 ->where('fdv.id_empresa', '=', $item->id_empresa)
    //                 ->where('fdv.pro_codigo', '=', $codigo->pro_codigo)
    //                 ->select('fdv.pro_codigo','fdv.det_ven_cantidad', 'fdv.det_ven_dev', 'fdv.det_ven_valor_u')
    //                 ->get();

    //             // Agregar los detalles de venta encontrados a detalleVenta
    //             $detalleVenta = array_merge($detalleVenta, $detallesDeVentaPorCodigo->toArray());
    //         }

    //         // Asignamos todos los detalles de venta encontrados a la propiedad detalleVenta
    //         $detallesDevolucionArray[$key]->detalleVenta = $detalleVenta;
    //     }

    //     // Retornamos los detalles de devolución como un arreglo
    //     return $detallesDevolucionArray;
    // }

    public function obtenerDetallesDevolucion(Request $request)
    {
        // Validamos que el parámetro 'documento' esté presente
        $request->validate([
            'documento' => 'required|string',
        ]);

        // Recuperamos el documento desde el parámetro de la solicitud
        $documento = $request->input('documento');
        $empresa = $request->input('empresa');

        //tipo combo
        $detallesDevolucionTipo1 = DB::table('codigoslibros_devolucion_header as cdh')
            ->join('codigoslibros_devolucion_son as cls', 'cdh.id', '=', 'cls.codigoslibros_devolucion_id')
            ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'cdh.id_cliente')  // LEFT JOIN para traer nombreInstitucion
            ->where('cdh.estado', '<>', 0)
            ->where('cls.documento', '=', $documento)
            ->where('cls.id_empresa', '=', $empresa)
            ->where('cls.tipo_codigo', '=', 1)  // Solo tipo_codigo = 1
            ->groupBy('cdh.codigo_devolucion', 'cdh.estado', 'cls.documento', 'cls.id_empresa', 'cls.id_cliente', 'i.nombreInstitucion')
            ->select(
                'cdh.id',
                'cdh.codigo_devolucion',
                'cdh.estado',
                'cls.documento',
                'cls.id_empresa',
                'cls.id_cliente',
                'i.nombreInstitucion',
                DB::raw('ROUND(SUM(cls.precio * cls.combo_cantidad_devuelta), 2) as total_precio') // Multiplicamos por combo_cantidad_devuelta
            )
            ->get();

        //tipo normal
        $detallesDevolucionTipo0 = DB::table('codigoslibros_devolucion_header as cdh')
            ->join('codigoslibros_devolucion_son as cls', 'cdh.id', '=', 'cls.codigoslibros_devolucion_id')
            ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'cdh.id_cliente')  // LEFT JOIN para traer nombreInstitucion
            ->where('cdh.estado', '<>', 0)
            ->where('cls.documento', '=', $documento)
            ->where('cls.id_empresa', '=', $empresa)
            ->where('cls.tipo_codigo', '=', 0)  // Solo tipo_codigo = 0
            ->whereNull('cls.combo')
            ->groupBy('cdh.codigo_devolucion', 'cdh.estado', 'cls.documento', 'cls.id_empresa', 'cls.id_cliente', 'i.nombreInstitucion')
            ->select(
                'cdh.id',
                'cdh.codigo_devolucion',
                'cdh.estado',
                'cls.documento',
                'cls.id_empresa',
                'cls.id_cliente',
                'i.nombreInstitucion',
                DB::raw('ROUND(SUM(cls.precio), 2) as total_precio') // No multiplicamos, solo usamos el precio
            )
            ->get();
            
        $detallesDevolucion = $detallesDevolucionTipo1->merge($detallesDevolucionTipo0);



        // Convertimos la colección a un arreglo
        $detallesDevolucionArray = $detallesDevolucion->toArray();

        // Recorrer cada uno de los detalles de devolución
        foreach ($detallesDevolucionArray as $key => $item) {
            // Consultar las ventas relacionadas con el documento
            $fVentas = DB::table('f_venta as fv')
                ->join('f_detalle_venta as fdv', function($join) {
                    $join->on('fdv.ven_codigo', '=', 'fv.ven_codigo')
                        ->on('fdv.id_empresa', '=', 'fv.id_empresa');
                })
                ->where('fv.ven_codigo', '=', $item->documento)
                ->where('fv.id_empresa', '=', $empresa)
                ->where('fv.est_ven_codigo', '<>', 3)
                ->where('fdv.det_ven_dev', '>', 0)
                ->first();  // Usamos `first()` para obtener el primer resultado

            // Verificamos si se obtuvo un resultado de la venta
            if ($fVentas) {
                $detallesDevolucionArray[$key]->descuento = $fVentas->ven_desc_por;
                // Calcular el valor con descuento para este detalle de devolución
                $detallesDevolucionArray[$key]->ValorConDescuento = round(round($item->total_precio, 2) - round((round($item->total_precio, 2) * $fVentas->ven_desc_por) / 100, 2), 2);
            } else {
                // Si no hay una venta asociada, asignamos 0 al descuento y el valor con descuento
                $detallesDevolucionArray[$key]->descuento = 0;
                $detallesDevolucionArray[$key]->total_precio = round($item->total_precio, 2);
            }
        }
        $detallesDevolucionArray = collect($detallesDevolucionArray);

        foreach ($detallesDevolucionArray as $key => $item) {
            // Obtener los códigos relacionados con la devolución
            $codigos = DB::table('codigoslibros_devolucion_son as cls')
                ->join('codigoslibros_devolucion_header as cdh', 'cdh.id', '=', 'cls.codigoslibros_devolucion_id')
                ->where('cdh.estado', '<>', 0)
                ->where('cls.codigoslibros_devolucion_id', '=', $item->id)
                ->where('cls.documento', '=', $item->documento)
                ->select('cls.codigo', 'cls.codigo_union', 'cls.pro_codigo', 'cls.tipo_codigo', 'cls.combo_cantidad_devuelta')
                ->get();

            // Aquí es donde ajustamos la lógica para repetir los códigos
            $codigosModificados = collect();

            foreach ($codigos as $codigo) {
                // Verificamos si el tipo de código es 1, y si es así, lo repetimos
                if ($codigo->tipo_codigo == 1) {
                    // Repetir el código por la cantidad de devuelta
                    for ($i = 0; $i < $codigo->combo_cantidad_devuelta; $i++) {
                        $codigosModificados->push([
                            'codigo' => $codigo->codigo,
                            'codigo_union' => $codigo->codigo_union,
                            'pro_codigo' => $codigo->pro_codigo,
                            'tipo_codigo' => $codigo->tipo_codigo
                        ]);
                    }
                } else {
                    // Si no es tipo_codigo 1, simplemente agregamos el código sin cambios
                    $codigosModificados->push([
                        'codigo' => $codigo->codigo,
                        'codigo_union' => $codigo->codigo_union,
                        'pro_codigo' => $codigo->pro_codigo,
                        'tipo_codigo' => $codigo->tipo_codigo
                    ]);
                }
            }

            // Asignamos los códigos modificados a la propiedad `codigos`
            $detallesDevolucionArray[$key]->codigos = $codigosModificados;

            // Filtrar códigos únicos por pro_codigo (eliminamos duplicados)
            $codigosUnicos = $codigosModificados->unique('pro_codigo');

            // Inicializamos un arreglo vacío para almacenar los detalles de venta
            $detalleVenta = [];

            // Iteramos sobre los códigos únicos para obtener los detalles de venta
            foreach ($codigosUnicos as $codigo) {
                // Verificar que el código es un objeto válido y tiene la propiedad 'pro_codigo'
                if (isset($codigo->pro_codigo)) {
                    // Consultamos el detalle de venta para cada código de producto
                    $detallesDeVentaPorCodigo = DB::table('f_detalle_venta as fdv')
                        ->where('fdv.ven_codigo', '=', $item->documento)
                        ->where('fdv.id_empresa', '=', $item->id_empresa)
                        ->where('fdv.pro_codigo', '=', $codigo->pro_codigo)
                        ->select('fdv.pro_codigo', 'fdv.det_ven_cantidad', 'fdv.det_ven_dev', 'fdv.det_ven_valor_u')
                        ->get();
            
                    // Agregar los detalles de venta encontrados a detalleVenta
                    $detalleVenta = array_merge($detalleVenta, $detallesDeVentaPorCodigo->toArray());
                }
            }

            // Asignamos todos los detalles de venta encontrados a la propiedad detalleVenta
            $detallesDevolucionArray[$key]->detalleVenta = $detalleVenta;
        }

        // Retornamos los detalles de devolución como un arreglo
        return $detallesDevolucionArray;
    }

    public function verifyCode(Request $request)
    {
        $codigo = $request->input('code');
        $referer = $request->input('referer');

        if (!$codigo) {
            return response()->json([
                'success' => false,
                'message' => 'El código no fue proporcionado.'
            ]);
        }

        if (!$referer) {
            return response()->json([
                'success' => false,
                'message' => 'El código no fue proporcionado.'
            ]);
        }

        $datosCodigoUrl = DB::table('librosinstituciones as li')
        ->where('li.li_codigo', $codigo)
        ->select('li_url')
        ->first();

        if (!$datosCodigoUrl) {
            return response()->json([
                'success' => false,
                'message' => 'El código no es válido.'
            ]);
        }

        if (!$datosCodigoUrl->li_url && $referer) {
            DB::table('librosinstituciones as li')
                ->where('li.li_codigo', $codigo)
                ->update([
                    'li_url' => $referer,
                ]);
        }else{

            if ($datosCodigoUrl->li_url !== $referer) {
                DB::table('librosinstituciones as li')
                    ->where('li.li_codigo', $codigo)
                    ->update([
                        'li_url_variacion' => $referer,
                    ]);
                return response()->json([
                    'success' => false,
                    'message' => 'La URL proporcionada es diferente, variación registrada.',
                ]);
            }

            DB::table('librosinstituciones as li')
            ->where('li.li_codigo', $codigo)
            ->where('li.li_url', $referer)
            ->increment('li_entradas');
        }

        $datosCodigo = DB::table('librosinstituciones as li')
            ->where('li.li_codigo', $codigo)
            ->where('li.li_url', $referer)
            ->where('p.estado', '=', '1')
            ->leftJoin('periodoescolar as p', 'p.idperiodoescolar', '=', 'li.li_periodo')
            ->select('li.li_idInstitucion', 'li.li_periodo')
            ->first();

        if ($datosCodigo) {
            return response()->json([
                'success' => true,
                'message' => 'Código verificado exitosamente.',
                'datos' => $datosCodigo,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'El código no es válido.',
            ]);
        }
    }

    public function getClienteDocumentos(Request $request){
        $cliente = $request->input('cliente');

        // Consulta los IDs de la institución para el cliente
        $clientesBase = DB::select("SELECT DISTINCT fv.institucion_id FROM f_venta fv
        WHERE fv.ruc_cliente = ?", [$cliente]);

        $descuentos = [];

        foreach($clientesBase as $key => $item){
            // Consulta los detalles de devoluciones con la validación
            $detallesDocumentoDevolucion = DB::table('codigoslibros_devolucion_header as cdh')
                ->join('codigoslibros_devolucion_son as cls', 'cdh.id', '=', 'cls.codigoslibros_devolucion_id')
                ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'cdh.id_cliente')
                ->where('cls.id_cliente', '=', $item->institucion_id)
                // Validación de documento: null, vacío o 0
                ->where(function ($query) {
                    $query->whereNull('cls.documento')
                        ->orWhere('cls.documento', '')
                        ->orWhere('cls.documento', 0);
                })
                // Validación de id_empresa: null, vacío o 0
                ->where(function ($query) {
                    $query->whereNull('cls.id_empresa')
                        ->orWhere('cls.id_empresa', '')
                        ->orWhere('cls.id_empresa', 0);
                })
                // Agrupar los resultados
                ->groupBy('cdh.codigo_devolucion', 'cls.documento', 'cls.id_empresa', 'cls.id_cliente', 'i.nombreInstitucion')
                ->select('cdh.codigo_devolucion', 'cls.documento', 'cls.id_empresa', 'cls.id_cliente', 'i.nombreInstitucion', DB::raw('ROUND(SUM(cls.precio), 2) as total_precio'))
                ->get();

            // Almacenar los resultados
            $descuentos[$key] = $detallesDocumentoDevolucion;
        }

        // Retornar los descuentos
        return $descuentos;
    }
    
    public function obtenerDatosClientes(Request $request)
    {
        $periodo = $request->periodo;
    
        // Obtener todos los clientes e instituciones desde f_venta (tabla base)
        $clientesBase = DB::table('f_venta AS f')
            ->leftJoin('institucion AS i', 'i.idInstitucion', '=', 'f.institucion_id')
            ->leftJoin('usuario AS u', 'u.idusuario', '=', 'f.ven_cliente')
            ->select(
                'f.ven_cliente',
                'f.institucion_id',
                'f.ruc_cliente',
                'f.ven_codigo',
                'f.id_empresa',
                'i.nombreInstitucion',
                'i.punto_venta',
                DB::raw('CONCAT(u.nombres, " ", u.apellidos) AS cliente')
            )
            ->where('f.est_ven_codigo', '<>', 3)
            ->where('f.periodo_id', $periodo)
            ->whereIn('f.idtipodoc', [1, 3, 4])
            ->distinct()
            ->get();
    
        // Consultas de totales (Prefacturas, Notas de Crédito, Notas, Abonos, Facturas)
        $prefacturas = DB::table('f_venta')
            ->select('ven_cliente', 'institucion_id', DB::raw('SUM(ven_valor) as total_prefacturas'))
            ->where('idtipodoc', 1)
            ->where('periodo_id', $periodo)
            ->where('est_ven_codigo', '<>', 3)
            ->groupBy('ven_cliente', 'institucion_id')
            ->get();
    
        $notasCredito = DB::table('f_venta')
            ->select('ven_cliente', 'institucion_id', DB::raw('SUM(ven_valor) as total_notas_credito'))
            ->where('idtipodoc', 16)
            ->where('periodo_id', $periodo)
            ->where('est_ven_codigo', '<>', 3)
            ->groupBy('ven_cliente', 'institucion_id')
            ->get();
    
        $notas = DB::table('f_venta')
            ->select('ven_cliente', 'institucion_id', DB::raw('SUM(ven_valor) as total_notas'))
            ->whereIn('idtipodoc', [3, 4])
            ->where('periodo_id', $periodo)
            ->where('est_ven_codigo', '<>', 3)
            ->groupBy('ven_cliente', 'institucion_id')
            ->get();
    
        $abonos = DB::table('abono')
            ->select('abono_ruc_cliente', DB::raw('SUM(abono_facturas) as abono_facturas'), DB::raw('SUM(abono_notas) as abono_notas'))
            ->where('abono_estado', 0)
            ->where('abono_cuenta','<>','6')
            ->where('abono_tipo', '<>', '4')
            // ->whereNotIn('abono_cuenta', [3, 4])
            ->where('abono_periodo', $periodo)
            ->groupBy('abono_ruc_cliente')
            ->get();
        
        $liquidaciones = DB::table('abono')
        ->select('abono_ruc_cliente', DB::raw('SUM(abono_facturas) as abono_facturas'), DB::raw('SUM(abono_notas) as abono_notas'))
        ->where('abono_estado', 0)
        ->where('abono_tipo', '<>', '4')
        ->where('abono_cuenta', '6')
        ->where('abono_periodo', $periodo)
        ->groupBy('abono_ruc_cliente')
        ->get();

        $retenciones = DB::table('abono')
        ->select('abono_ruc_cliente', DB::raw('SUM(abono_valor_retencion) as retencion_valor'))
        ->where('abono_estado', 0)
        ->where('abono_tipo', '3')
        ->whereNotNull('abono_valor_retencion')
        ->where('abono_periodo', $periodo)
        ->groupBy('abono_ruc_cliente')
        ->get();
        
        $cruces = DB::table('abono')
        ->select('abono_ruc_cliente', DB::raw('SUM(abono_facturas) as abono_facturas'), DB::raw('SUM(abono_notas) as abono_notas'))
        ->where('abono_estado', 0)
        ->where('abono_cuenta', '9')
        ->where('abono_periodo', $periodo)
        ->groupBy('abono_ruc_cliente')
        ->get();
        
        $facturas = DB::table('f_venta_agrupado')
            ->select('ven_cliente', 'institucion_id', DB::raw('SUM(ven_valor) as total_facturas'))
            ->where('periodo_id', $periodo)
            ->where('est_ven_codigo', 0)
            ->groupBy('ven_cliente', 'institucion_id')
            ->get();
    
        // Crear un array único de combinaciones de ven_codigo y id_empresa
        $documentosEmpresas = $clientesBase->map(function($cliente) {
            return [
                'ven_codigo' => $cliente->ven_codigo,
                'id_empresa' => $cliente->id_empresa
            ];
        })->unique(function($item) {
            return $item['ven_codigo'] . '-' . $item['id_empresa']; // Garantiza que sea único por combinación de ven_codigo e id_empresa
        })->toArray();
    
        // Obtener las devoluciones para todos los documentos y empresas (únicos)
        // $devoluciones = DB::table('codigoslibros_devolucion_son as cls')
        //     ->join('f_venta as fv', 'fv.ven_codigo', '=', 'cls.documento')
        //     ->whereIn('cls.documento', array_column($documentosEmpresas, 'ven_codigo'))
        //     ->whereIn('cls.id_empresa', array_column($documentosEmpresas, 'id_empresa'))
        //     ->select(
        //         'cls.documento',
        //         'cls.id_empresa',
        //         DB::raw('SUM(cls.precio * cls.combo_cantidad_devuelta) as total_precio_tipo1'),
        //         DB::raw('SUM(cls.precio) as total_precio_tipo0'),
        //         'fv.ven_desc_por',
        //         'fv.institucion_id'
        //     )
        //     ->groupBy('cls.documento', 'cls.id_empresa', 'fv.ven_desc_por', 'fv.institucion_id')
        //     ->get();
        $devoluciones = DB::table('f_detalle_venta as fd')
        ->join('f_venta as fv', function($join) {
            $join->on('fd.ven_codigo', '=', 'fv.ven_codigo')
                ->on('fd.id_empresa', '=', 'fv.id_empresa');
        })
        ->whereIn('fd.ven_codigo', array_column($documentosEmpresas, 'ven_codigo'))
        ->whereIn('fd.id_empresa', array_column($documentosEmpresas, 'id_empresa'))
        ->select(
            'fd.ven_codigo',
            'fd.id_empresa',
            DB::raw('SUM(det_ven_dev * det_ven_valor_u) as total_precio'),
            'fv.ven_desc_por',
            'fv.institucion_id'
        )
        ->groupBy('fd.ven_codigo', 'fd.id_empresa', 'fv.ven_desc_por', 'fv.institucion_id')
        ->get();

        // Consulta para devoluciones sin empresa (id_empresa == 0)
        $devolucionesSinEmpresa = DB::table('codigoslibros_devolucion_header_facturador as h')
        ->join('codigoslibros_devolucion_son_facturador as d', 'd.codigoslibros_devolucion_header_facturador_id', '=', 'h.id')
        ->where('h.periodo_id', $periodo)
        ->where('h.id_empresa', 0)
        ->select(
            'h.id_cliente',
            'd.descuento',
            DB::raw('SUM(d.precio * d.cantidad) as devolucion_total')
        )
        ->groupBy('h.id_cliente')
        ->get();
    
        // Agrupar documentos por cliente
        $documentosPorCliente = [];
        foreach ($clientesBase as $cliente) {
            $clienteKey = $cliente->ven_cliente . '-' . $cliente->institucion_id;
            
            if (!isset($documentosPorCliente[$clienteKey])) {
                $documentosPorCliente[$clienteKey] = [];
            }

            $documentosPorCliente[$clienteKey][] = [
                'Ven_codigo' => $cliente->ven_codigo,
                'empresa' => $cliente->id_empresa,
            ];
        }
    
        // Procesar datos combinados
        $datosCombinados = [];
        $clientesProcesados = [];  // Array para verificar si el cliente ya fue procesado

        foreach ($clientesBase as $cliente) {
            $clienteId = $cliente->ven_cliente;
            $institucionId = $cliente->institucion_id;
            $rucCliente = $cliente->ruc_cliente;
            $puntoVenta = $cliente->punto_venta;
            
            // Verificar si el cliente ya fue procesado
            $clienteKey = $clienteId . '-' . $institucionId;
            if (isset($clientesProcesados[$clienteKey])) {
                continue;  // Si ya fue procesado, saltar al siguiente
            }

            // Marcar cliente como procesado
            $clientesProcesados[$clienteKey] = true;

            // Obtener los documentos asociados al cliente
            $documentos = $documentosPorCliente[$clienteKey];

            // Consultar totales de cada cliente
            $totalPrefacturas = $prefacturas->where('ven_cliente', $clienteId)->where('institucion_id', $institucionId)->sum('total_prefacturas');
            $totalNotasCredito = $notasCredito->where('ven_cliente', $clienteId)->where('institucion_id', $institucionId)->sum('total_notas_credito');
            $totalNotas = $notas->where('ven_cliente', $clienteId)->where('institucion_id', $institucionId)->sum('total_notas');
            $abonoFacturas = $abonos->where('abono_ruc_cliente', $rucCliente)->sum('abono_facturas');
            $abonoNotas = $abonos->where('abono_ruc_cliente', $rucCliente)->sum('abono_notas');
            $liquidacionesFacturas = $liquidaciones->where('abono_ruc_cliente', $rucCliente)->sum('abono_facturas');
            $liquidacionesNotas = $liquidaciones->where('abono_ruc_cliente', $rucCliente)->sum('abono_notas');
            $totalFacturas = $facturas->where('ven_cliente', $clienteId)->where('institucion_id', $institucionId)->sum('total_facturas');
            $totalretenciones = $retenciones->where('abono_ruc_cliente', $rucCliente)->sum('retencion_valor');
            $totalCruces = $cruces->where('abono_ruc_cliente', $rucCliente)->sum('abono_facturas');
            $totalCrucesNotas = $cruces->where('abono_ruc_cliente', $rucCliente)->sum('abono_notas');

            // Obtener los datos de devoluciones para este cliente y sus documentos
            // $devolucionCliente = $devoluciones->whereIn('documento', array_column($documentos, 'Ven_codigo'))
            //                                 ->whereIn('id_empresa', array_column($documentos, 'empresa'))
            //                                 ->where('institucion_id', $institucionId);
            $devolucionCliente = $devoluciones->whereIn('ven_codigo', array_column($documentos, 'Ven_codigo'))
            ->whereIn('id_empresa', array_column($documentos, 'empresa'))
            ->where('institucion_id', $institucionId);
            // $totalDevoluciones = $devolucionCliente->sum(function($devolucion) {
            //     return $devolucion->total_precio_tipo1 + $devolucion->total_precio_tipo0;
            // });

            // $valorConDescuentoDevoluciones = $devolucionCliente->sum(function($devolucion) {
            //     $totalPrecio = $devolucion->total_precio_tipo1 + $devolucion->total_precio_tipo0;
            //     return round($totalPrecio - ($totalPrecio * $devolucion->ven_desc_por / 100), 2);
            // });
            $totalDevoluciones = $devolucionCliente->sum(function($devolucion) {
                return round($devolucion->total_precio,2);
            });

            $valorConDescuentoDevoluciones = $devolucionCliente->sum(function($devolucion) {
                $totalPrecio = round($devolucion->total_precio,2);
                return round($totalPrecio  - (($totalPrecio  * $devolucion->ven_desc_por) / 100), 2);
            });


            // Obtener las devoluciones sin empresa para este cliente
            $devolucionesSinEmpresaCliente = $devolucionesSinEmpresa->where('id_cliente', $institucionId);

            // Sumar devoluciones sin empresa
            $totalDevolucionesSinEmpresa = $devolucionesSinEmpresaCliente->sum(function($devolucion) {
                return round($devolucion->devolucion_total, 2);
            });

            $valorConDescuentoDevolucionesSinEmpresa = $devolucionesSinEmpresaCliente->sum(function($devolucion) {
                $totalPrecio = round($devolucion->devolucion_total,2);
                return round($totalPrecio  - (($totalPrecio  * $devolucion->descuento) / 100), 2);
            });

            // Agregar al arreglo de datos combinados
            $datosCombinados[] = [
                'ven_cliente' => $clienteId,
                'institucion_id' => $institucionId,
                'ruc_cliente' => $rucCliente,
                'cliente' => $cliente->cliente,
                'punto_venta' => $puntoVenta,
                'institucion' => $cliente->nombreInstitucion,
                'total_prefacturas' => round($totalPrefacturas, 2),
                'total_notas' => round($totalNotas, 2),
                'total_notas_credito' => round($totalNotasCredito, 2),
                'total_facturas' => round($totalFacturas, 2),
                'abono_facturas' => round($abonoFacturas, 2),
                'abono_notas' => round($abonoNotas, 2),
                'liquidaciones_facturas' => round($liquidacionesFacturas, 2),
                'liquidaciones_notas' => round($liquidacionesNotas, 2),
                'total_devoluciones' => round($totalDevoluciones, 2),
                'valor_con_descuento_devoluciones' => round($valorConDescuentoDevoluciones, 2) + round($valorConDescuentoDevolucionesSinEmpresa, 2),
                'documentos' => $documentos,
                'retenciones' => $totalretenciones,
                'cruces' => $totalCruces,
                'crucesNotas' => $totalCrucesNotas,
            ];
        }

    
        // Filtrar los resultados
        $datosFiltrados = array_filter($datosCombinados, function ($dato) {
            return $dato['total_prefacturas'] > 0 || 
                   $dato['total_notas'] > 0 || 
                   $dato['total_notas_credito'] > 0 || 
                   $dato['total_facturas'] > 0 || 
                   $dato['abono_facturas'] > 0 || 
                   $dato['abono_notas'] > 0 ||
                   $dato['liquidaciones_facturas'] > 0 ||
                   $dato['liquidaciones_notas'] > 0 ||
                   $dato['total_devoluciones'] > 0;
        });
    
        return array_values($datosFiltrados);
    }
    
    public function obtenerResumenDevolucion(Request $request)
    {
        // Validamos que los parámetros 'documento' y 'empresa' estén presentes
        $request->validate([
            'documento' => 'required|string',
            'empresa' => 'required|integer',
        ]);
    
        $documento = $request->input('documento');
        $empresa = $request->input('empresa');
    
        // Calcular el total_precio para tipo_codigo 1
        $totalPrecioTipo1 = DB::table('codigoslibros_devolucion_son as cls')
            ->where('cls.documento', '=', $documento)
            ->where('cls.id_empresa', '=', $empresa)
            ->where('cls.tipo_codigo', '=', 1)
            ->sum(DB::raw('cls.precio * cls.combo_cantidad_devuelta'));
    
        // Calcular el total_precio para tipo_codigo 0
        $totalPrecioTipo0 = DB::table('codigoslibros_devolucion_son as cls')
            ->where('cls.documento', '=', $documento)
            ->where('cls.id_empresa', '=', $empresa)
            ->where('cls.tipo_codigo', '=', 0)
            ->sum('cls.precio');
    
        // Total precio de la devolución
        $totalPrecio = round($totalPrecioTipo1 + $totalPrecioTipo0, 2);
    
        // Obtener descuento del documento relacionado
        $venta = DB::table('f_venta as fv')
            ->where('fv.ven_codigo', '=', $documento)
            ->where('fv.id_empresa', '=', $empresa)
            ->where('fv.est_ven_codigo', '<>', 3)
            ->select('fv.ven_desc_por', 'fv.institucion_id')
            ->first();
    
        // Calcular ValorConDescuento
        $valorConDescuento = $venta
            ? round($totalPrecio - ($totalPrecio * $venta->ven_desc_por / 100), 2)
            : $totalPrecio;
    
        return response()->json([
            'id_empresa' => $empresa,
            'institucion_id' => $venta ? $venta->institucion_id : null,
            'total_precio' => $totalPrecio,
            'ValorConDescuento' => $valorConDescuento,
        ]);
    }

    //JEYSON METODOS INICIO
    public function retencion_registro_update_new(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'abono_fecha' => 'required|date',
                'abono_porcentaje' => 'required',
                'abono_valor_retencion' => 'required|numeric',
                'abono_periodo' => 'required',
                'abono_tipo' => 'required',
                'user_created' => 'required|numeric',
                'referencia_retencion_factura' => 'required',
                'idClientePerseo' => 'required',
                'clienteCodigoPerseo' => 'required',
                'abono_ruc_cliente' => 'required',
                'abono_documento' => 'required',
                'abono_concepto' => 'required',
                'abono_empresa' => 'required',
                'egf_archivo' => 'nullable|string',
                'egft_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $referenciaRetencion = $request->referencia_retencion_factura;
            // Determina la referencia a usar según la condición
            if ($request->referencia_retencion_factura_anterior != 'vacio') {
                $referenciaRetencion = ($request->referencia_retencion_factura != $request->referencia_retencion_factura_anterior)
                    ? $request->referencia_retencion_factura_anterior
                    : $request->referencia_retencion_factura;
            }

            // Realiza la consulta con la referencia determinada
            $verificar_abono_documento = DB::select("SELECT ab.referencia_retencion_factura, em.descripcion_corta, ab.abono_ruc_cliente
                FROM abono ab
                INNER JOIN empresas em ON ab.abono_empresa = em.id
                WHERE ab.abono_tipo = 3 
                AND ab.abono_documento = :abono_documento
                AND ab.abono_estado = :abono_estado
                AND ab.referencia_retencion_factura <> :referencia_retencion_factura",
                [
                    'abono_documento' => $request->abono_documento,
                    'abono_estado' => 0,
                    'referencia_retencion_factura' => $referenciaRetencion,
                ]
            );

            // Validar si se encontró un registro
            if (!empty($verificar_abono_documento)) {
                $referencia_retencion_factura = $verificar_abono_documento[0]->referencia_retencion_factura;
                $descripcion_corta = $verificar_abono_documento[0]->descripcion_corta;
                $abono_ruc_cliente = $verificar_abono_documento[0]->abono_ruc_cliente;
                return response()->json([
                    'status' => 0, // Status 0 para indicar que no se puede continuar
                    'message' => "Este número de documento ya está registrado en la empresa $descripcion_corta cliente CI/RUC: $abono_ruc_cliente, en el documento: $referencia_retencion_factura.",
                ], 200); // Código 400 para indicar un error en la solicitud
            }

            // Buscar o crear un registro en la tabla Abono
            $retencionupdate = Abono::firstOrNew(['abono_id' => $request->abono_id]);

            if (!$retencionupdate->exists) {
                $verificacion_historico = 'new';
                // Crear un nuevo registro en EvidenciaGlobalFiles si es un nuevo registro
                $evidenciaGlobalFile = new EvidenciaGlobalFiles();
                $evidenciaGlobalFile->egft_id = $request->egft_id;
                $evidenciaGlobalFile->egf_archivo = $request->egf_archivo ?? null;
                $evidenciaGlobalFile->egf_url = $request->egf_archivo 
                    ? uniqid() . '_' . $request->egf_archivo 
                    : null;
                $evidenciaGlobalFile->user_created = $request->user_created;
                $evidenciaGlobalFile->egf_tamano = $request->egf_tamano ?? null;
                $evidenciaGlobalFile->save();

                // Asignar el ID generado al registro de Abono
                $retencionupdate->egf_id = $evidenciaGlobalFile->egf_id;
                $retencionupdate->user_created = $request->user_created;
                $retencionupdate->created_at = now();
            } else {
                $verificacion_historico = 'edit';
                // Si es una actualización, buscar el registro de EvidenciaGlobalFiles asociado
                $evidenciaGlobalFile = EvidenciaGlobalFiles::find($retencionupdate->egf_id);
                // Actualizar solo si 'egf_archivo' está presente en el request
                if ($evidenciaGlobalFile && $request->has('egf_archivo')) {
                    $evidenciaGlobalFile->egf_archivo = $request->egf_archivo ?? null;
                    $evidenciaGlobalFile->egf_url = $request->egf_archivo 
                    ? uniqid() . '_' . $request->egf_archivo 
                    : null;
                    $evidenciaGlobalFile->egf_tamano = $request->egf_tamano ?? null;
                    $evidenciaGlobalFile->updated_at = now();
                    $evidenciaGlobalFile->save();
                }
            }

            // Actualizar los datos en el registro de Abono
            $retencionupdate->abono_fecha = $request->abono_fecha;
            $retencionupdate->abono_porcentaje = $request->abono_porcentaje;
            $retencionupdate->abono_valor_retencion = $request->abono_valor_retencion;
            $retencionupdate->abono_periodo = $request->abono_periodo;
            $retencionupdate->abono_tipo = $request->abono_tipo;
            $retencionupdate->referencia_retencion_factura = $request->referencia_retencion_factura;
            $retencionupdate->idClientePerseo = $request->idClientePerseo;
            $retencionupdate->clienteCodigoPerseo = $request->clienteCodigoPerseo;
            $retencionupdate->abono_ruc_cliente = $request->abono_ruc_cliente;
            $retencionupdate->abono_documento = $request->abono_documento;
            $retencionupdate->abono_concepto = $request->abono_concepto;
            $retencionupdate->abono_empresa = $request->abono_empresa;
            $retencionupdate->abono_cuenta = 8;
            $retencionupdate->updated_at = now();
            $retencionupdate->save();

            // return $retencionupdate;
            // Llamar a guardarAbonoHistorico según corresponda
            if ($verificacion_historico == 'new') {
                // Para registros nuevos
                $retencionupdate->abono_notas = 0;
                $retencionupdateObject = json_decode(json_encode($retencionupdate));
                // Eliminar la propiedad 'tipo'
                unset($retencionupdateObject->tipo);
                // Añadir propiedades de evidenciaGlobalFile
                $retencionupdateObject->egf_archivo = $evidenciaGlobalFile->egf_archivo ?? null;
                $retencionupdateObject->egf_tamano = $evidenciaGlobalFile->egf_tamano ?? null;
                $this->guardarAbonoHistorico($retencionupdateObject, 3, $request->user_created);
            } else if($verificacion_historico == 'edit'){
                // Para registros existentes
                $retencionupdate->abono_notas = 0;
                $retencionupdateObject = json_decode(json_encode($retencionupdate));
                // Eliminar la propiedad 'tipo'
                unset($retencionupdateObject->tipo);
                // Añadir propiedades de evidenciaGlobalFile
                $retencionupdateObject->egf_archivo = $evidenciaGlobalFile->egf_archivo ?? null;
                $retencionupdateObject->egf_tamano = $evidenciaGlobalFile->egf_tamano ?? null;
                $this->guardarAbonoHistorico($retencionupdateObject, 6, $request->user_created);
            }else{
                return 'No se ha guardado el historico';
            }
            DB::commit();

            return response()->json([
                'message' => 'Registro procesado correctamente',
                'status' => 1,
                'abono_id' => $retencionupdate->abono_id,
                'egf_id' => $retencionupdate->egf_id,
                'egf_url' => $evidenciaGlobalFile->egf_url ?? null,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }

    public function GetListadoRetenciones(Request $request)
    {
        $identificacion = $request->input('identificacion');
        $id_empresa = $request->input('id_empresa');

        $query = DB::SELECT("SELECT a.abono_id, a.egf_id, a.abono_fecha, a.abono_tipo, a.abono_porcentaje, a.abono_documento,
                a.referencia_retencion_factura, a.referencia_retencion_factura as referencia_retencion_factura_anterior, a.abono_valor_retencion, a.abono_periodo, a.user_created,
                a.idClientePerseo, a.clienteCodigoPerseo, a.abono_concepto, a.abono_ruc_cliente,
                fva.ven_valor, fva.id_empresa, em.descripcion_corta, ins.nombreInstitucion, ins.direccionInstitucion, 
                usa.nombres, usa.apellidos, egf.egf_url,
                CONCAT(us.nombres, ' ', us.apellidos) AS nombrecreador, 
                COUNT(DISTINCT dfv.pro_codigo) AS item, 
                SUM(dfv.det_ven_cantidad) AS libros, a.abono_estado, egft.egft_nombre
            FROM abono a 
            INNER JOIN f_venta_agrupado fva ON a.referencia_retencion_factura = fva.id_factura
            INNER JOIN usuario us ON a.user_created = us.idusuario
            INNER JOIN institucion ins ON fva.institucion_id = ins.idInstitucion
            INNER JOIN usuario usa ON fva.ven_cliente = usa.idusuario
            INNER JOIN f_detalle_venta_agrupado dfv ON fva.id_factura = dfv.id_factura
            INNER JOIN empresas em ON em.id = fva.id_empresa
            INNER JOIN evidencia_global_files egf ON a.egf_id = egf.egf_id
            INNER JOIN evidencia_global_files_tipo egft ON egf.egft_id = egft.egft_id
            WHERE a.abono_tipo = 3
            AND a.abono_ruc_cliente = ?
            AND fva.id_empresa = ?
            GROUP BY a.abono_id, a.egf_id, a.abono_fecha, a.abono_tipo, a.abono_porcentaje, a.abono_documento,
                    a.referencia_retencion_factura, a.abono_valor_retencion, a.abono_periodo, a.user_created,
                    a.idClientePerseo, a.clienteCodigoPerseo, a.abono_concepto, a.abono_ruc_cliente,
                    fva.ven_valor, fva.id_empresa, em.descripcion_corta, ins.nombreInstitucion, ins.direccionInstitucion, 
                    usa.nombres, usa.apellidos, us.nombres, us.apellidos
                    ORDER BY a.created_at DESC
        ", [$identificacion, $id_empresa]);

        return $query;
    }

    public function GetVerificacionAbonoDocumento(Request $request)
    {
        $identificacion = $request->input('identificacion');
        $id_empresa = $request->input('id_empresa');

        $query = DB::SELECT("SELECT a.referencia_retencion_factura, a.abono_estado
            FROM abono a 
            INNER JOIN f_venta_agrupado fva ON a.referencia_retencion_factura = fva.id_factura
            WHERE a.abono_tipo = 3
            AND a.abono_ruc_cliente = ?
            AND fva.id_empresa = ?", [$identificacion, $id_empresa]);

        return $query;
    }

    public function GetEvidenciasGlobalxID(Request $request)
    {
        $egf_id = $request->input('egf_id');

        $query = DB::SELECT("SELECT *
            FROM evidencia_global_files egf
            INNER JOIN evidencia_global_files_tipo egft ON egf.egft_id = egft.egft_id
            WHERE egf.egf_id = ?", [$egf_id]);

        return $query;
    }

    public function anularretencion_quitarevidencia(Request $request)
    {
        // return $request;
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'abono_id' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 0, 'message' => 'Validación fallida','errors' => $validator->errors(),], 422);
            }

            // Buscar o crear un registro en la tabla Abono
            $retencionupdate = Abono::firstOrNew(['abono_id' => $request->abono_id]);

            if (!$retencionupdate->exists) {
                return response()->json(['status' => 0,'message' => 'No se encontró el registro de abono.',], 404);
            }

            // Buscar el registro de EvidenciaGlobalFiles asociado a egf_id
            $evidenciaGlobalFile = EvidenciaGlobalFiles::find($retencionupdate->egf_id);

            if ($evidenciaGlobalFile) {
                // Establecer todos los campos en null
                $evidenciaGlobalFile->egf_archivo = null;
                $evidenciaGlobalFile->egf_url = null;
                $evidenciaGlobalFile->egf_tamano = null;
                $evidenciaGlobalFile->updated_at = now();
                $evidenciaGlobalFile->save();
            } else {
                return response()->json(['status' => 0,'message' => 'No se encontró el registro asociado en EvidenciaGlobalFiles.',], 404);
            }
            DB::commit();
            return response()->json(['message' => 'Registro procesado correctamente','status' => 1,]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                "status" => "0", 
                'message' => 'Error al actualizar los datos: ' . $e->getMessage()
            ], 500);
        }
    }
    //JEYSON METODOS FIN

    public function buscarLibreriaOestablecimiento(Request $request)
    {
        $query = DB::SELECT("SELECT DISTINCT f.institucion_id, i.nombreInstitucion, i.direccionInstitucion, i.telefonoInstitucion FROM f_venta f
        INNER JOIN institucion i ON i.idInstitucion = f.institucion_id
        WHERE f.ruc_cliente = '$request->ruc'
        AND f.est_ven_codigo <> 3
        ");
        return $query;
    }
    
    public function getCodigoSecuencial(Request $request)
    {

        $id = $request->id;
        $empresa = $request->empresa;

        $tipoDocumento = f_tipo_documento::where('tdo_id', $id)->first();

        if (!$tipoDocumento) {
            return response()->json([
                'status' => 0,
                'message' => 'No existe el tipo de documento con ese ID'
            ]);

        }

        // Determinar qué secuencial utilizar según la empresa
        if ($empresa == 1) {
            $secuencial = $tipoDocumento->tdo_secuencial_Prolipa;
        } else if ($empresa == 3) {
            $secuencial = $tipoDocumento->tdo_secuencial_calmed;
        } else {
            $secuencial = null;
            return 'No hay empresa seleccionada';
        }

        // Obtener y actualizar la secuencia
        if ($secuencial !== null) {
            $getSecuencia = (int)$secuencial + 1;
            $id = $tipoDocumento->tdo_id;
            $letraSecuencial = $tipoDocumento->tdo_letra;
        }

        // Formatear la secuencia
        $secuenciaFormateada = str_pad($getSecuencia, 7, '0', STR_PAD_LEFT);

        return [$letraSecuencial, $secuenciaFormateada, $id];
    }

    public function cruce_registro(Request $request)
    {
        // Inicia la transacción
        DB::beginTransaction();

        try {
            // $existingAbono = DB::table('abono')
            //     ->where('referencia_nota_cred_interna', $request->abono_documento)
            //     ->orderBy('created_at', 'desc')
            //     ->first();

            // if ($existingAbono) {
            //     // Si el estado del abono existente es 0, no se permite guardar
            //     if ($existingAbono->abono_estado == 0) {
            //         // Obtener información del cliente para el mensaje de error
            //         $usuario = DB::table('usuario')
            //             ->where('cedula', $existingAbono->abono_ruc_cliente)
            //             ->select('nombres', 'apellidos')
            //             ->first();

            //         $fullName = $usuario ? $usuario->nombres . ' ' . $usuario->apellidos : 'desconocido';

            //         return response()->json([
            //             'status' => 0,
            //             'message' => "El valor del campo documento ya está en uso con el Cliente $fullName.",
            //         ]);
            //     }
            // }
            // Obtener el id del usuario asociado al ruc_cliente
            $usuario = Usuario::where('cedula', $request->ruc_cliente)->first();
            
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Registro en F_venta
            $venta = Ventas::create([
                'ven_codigo' => $request->numeroDocumento,
                'est_ven_codigo' => 1,
                'tip_ven_codigo' => 1,
                'ven_tipo_inst' => 'V',
                'ven_valor' => $request->ValorTOTAL,
                'ven_subtotal' => $request->subtotal,
                'ven_desc_por' => $request->descuento,
                'ven_descuento' => $request->ValorDescuento,
                'ven_fecha' => $request->fecha,
                'institucion_id' => $request->establecimiento,
                'periodo_id' => $request->periodo,
                'user_created' => $request->usuario,
                'id_empresa' => $request->empresa,
                'idtipodoc' => 17,
                'ruc_cliente' => $request->ruc_cliente,
                'ven_cliente' => $usuario->idusuario,
            ]);

            // Validar si la venta fue guardada correctamente
            if (!$venta) {
                throw new \Exception("Error al guardar la venta");
            }

            // Validar que "detalles" sea un array (y decodificar el JSON)
            $detalles = json_decode($request->detalles, true); // Decodificar el JSON a un array

            if (!$detalles || !is_array($detalles)) {
                throw new \Exception("El campo detalles debe ser un array.");
            }

            // Registro en F_detalle_venta
            foreach ($detalles as $detalle) {  // Ahora estamos iterando sobre el array decodificado
                $detalleVenta = DetalleVentas::create([
                    'ven_codigo' => $request->numeroDocumento,
                    'pro_codigo' => 'PROTEST',
                    'id_empresa' => $request->empresa,
                    'det_ven_cantidad' => $detalle['cantidad'],
                    'det_ven_valor_u' => $detalle['valorUnitario'],
                    'detalle_notaCreditInterna' => $detalle['descripcion'],// no se guardo
                ]);
    
                // Validar si el detalle de la venta fue guardado correctamente
                if (!$detalleVenta) {
                    throw new \Exception("Error al guardar el detalle de venta para el documento {$request->numeroDocumento}");
                }
            }
            // Crear el abono directamente
            $datosAbono = [
                'abono_estado' => 0,
                'abono_fecha' => $request->fecha,
                'abono_tipo' => 4,
                'abono_cuenta' => 9,
                'abono_documento' => $request->numeroDocumento,
                'abono_empresa' => $request->empresa,
                'abono_periodo' => $request->periodo,
                'user_created' => $request->usuario,
                'abono_concepto' => "CLIENTE: {$usuario->nombres} {$usuario->apellidos} DOCUMENTO: {$request->numeroDocumento}",
                'abono_ruc_cliente' => $request->ruc_cliente,
                'referencia_nota_cred_interna' => $request->referencia,
            ];
            
            // Asignar valores condicionalmente
            if ($request->tipo == 1) {
                $datosAbono['abono_notas'] = $request->ValorTOTAL;
                $datosAbono['abono_facturas'] = 0;
            } elseif ($request->tipo == 0) {
                $datosAbono['abono_notas'] = 0;
                $datosAbono['abono_facturas'] = $request->ValorTOTAL;
            }else{
                $datosAbono['abono_notas'] = 0;
                $datosAbono['abono_facturas'] = $request->ValorTOTAL;
            }
            
            // Crear el abono con los datos construidos correctamente
            $abono = Abono::create($datosAbono);
            
            // Validar si el abono fue guardado correctamente
            if (!$abono) {
                throw new \Exception("Error al guardar el abono para el documento {$request->numeroDocumento}");
            }else{
                $abonoCreado = json_decode(json_encode($abono));
                $this->guardarAbonoHistorico($abonoCreado, 8, $request->usuario);
            }

            // Obtener el valor de la empresa y actualizar secuencial
            $empresa = $request->empresa;

            // ACTUALIZAR SECUENCIAL
            try {
                if ($empresa == 1) {
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod FROM f_tipo_documento WHERE tdo_id = 17");
                } else if ($empresa == 3) {
                    $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod FROM f_tipo_documento WHERE tdo_id = 17");
                }

                if (empty($query1)) {
                    throw new \Exception('No se encontró el tipo de documento con ID 17');
                }

                $id = $query1[0]->id;
                $codi = $query1[0]->cod;
                $co = (int)$codi + 1;

                // Obtener el documento de tipo correspondiente
                $tipo_doc = f_tipo_documento::findOrFail($id);

                // Actualizar el secuencial
                if ($empresa == 1) {
                    $tipo_doc->tdo_secuencial_Prolipa = $co;
                } else if ($empresa == 3) {
                    $tipo_doc->tdo_secuencial_calmed = $co;
                }

                // Guardar el tipo de documento
                if (!$tipo_doc->save()) {
                    throw new \Exception('Error al actualizar el secuencial del tipo de documento');
                }

            } catch (\Exception $e) {
                // Si ocurre un error en el proceso de actualización, lanzamos una excepción
                DB::rollBack(); // Deshacer todos los cambios realizados hasta este punto
                return response()->json(['error' => 'Error al actualizar el secuencial', 'message' => $e->getMessage(), 'line' => $e->getLine()], 500);
            }

            // Si todo se guardó correctamente, se realiza el commit
            DB::commit();

            return response()->json(['success' => 'Venta registrada correctamente','estatus'=>'1'], 200);
            
        } catch (\Exception $e) {
            // Si algo falla, revertimos todo
            DB::rollBack();

            return response()->json(['error' => 'Error al registrar la venta', 'message' => $e->getMessage(), 'line' => $e->getLine(),'estatus'=>'0'], 500);
        }
    }

    public function editarVenta(Request $request)
    {
        DB::beginTransaction();

        try {
            $venta = Ventas::where('ven_codigo', $request->numeroDocumento)
                ->where('periodo_id', $request->periodo)    
                ->where('institucion_id', $request->establecimiento)
                ->where('id_empresa', $request->empresa)
                ->first();

            if (!$venta) {
                throw new \Exception("El DOCUMENTO DE VENTA NO EXISTE.");
            }

            // Intentar actualizar
            $updated = $venta->update([
                'ven_valor' => $request->ValorTOTAL,
                'ven_subtotal' => $request->subtotal,
                'ven_desc_por' => $request->descuento,
                'ven_descuento' => $request->ValorDescuento,
                'ven_fecha' => $request->fecha,
            ]);

            if (!$updated) {
                throw new \Exception("El DOCUMENTO DE VENTA NO SE ACTUALIZÓ.");
            }

            // Validar que "detalles" sea un array (y decodificar el JSON)
            $detalles = json_decode($request->detalles, true); // Decodificar el JSON a un array

            if (!$detalles || !is_array($detalles)) {
                throw new \Exception("El campo detalles debe ser un array.");
            }

            // Eliminar los detalles anteriores
            DetalleVentas::where('ven_codigo', $venta->ven_codigo)
            ->where('id_empresa', $request->empresa)->delete();

            // Insertar los nuevos detalles (F_detalle_venta)
            foreach ($detalles as $detalle) {
                DetalleVentas::create([
                    'ven_codigo' => $venta->ven_codigo,
                    'pro_codigo' => 'PROTEST',
                    'id_empresa' => $request->empresa,
                    'det_ven_cantidad' => $detalle['cantidad'],
                    'det_ven_valor_u' => $detalle['valorUnitario'],
                    'detalle_notaCreditInterna' => $detalle['descripcion'],
                ]);
            }

            // Buscar el abono existente
            $abono = Abono::where('abono_id', $request->abono)->first();

            if (!$abono) {
                throw new \Exception("El abono con ID {$request->abono} no existe.");
            }

            // Actualizar el abono y verificar si se hizo correctamente
            $actualizado = $abono->update([
                'abono_fecha' => $request->fecha,
                'abono_concepto' => "CLIENTE: {$venta->ven_cliente} DOCUMENTO: {$venta->ven_codigo}",
                'referencia_nota_cred_interna' => $request->referencia,
                'abono_notas' => $request->tipo == 1 ? $request->ValorTOTAL : 0,
                'abono_facturas' => $request->tipo == 0 ? $request->ValorTOTAL : 0,
                'abono_facturas' => $request->tipo == 0 ? $request->ValorTOTAL : 0,
                'referencia_nota_cred_interna' => $request->referencia,
            ]);

            if (!$actualizado) {
                throw new \Exception("Error al actualizar el abono para el documento {$request->numeroDocumento}");
            }else{
                $abonoActualizado = Abono::where('abono_id', $request->abono)->first();
                // Guardar el histórico si se actualizó correctamente
                $abonoActualizado = json_decode(json_encode($abonoActualizado));
                $this->guardarAbonoHistorico($abonoActualizado, 9, $request->usuario);
            }  

            

            // Confirmar los cambios en la BD
            DB::commit();
            return response()->json(['success' => 'Venta editada correctamente','status'=>1], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'No se pudo actualizar la venta',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'status' => 0,
            ], 500);
        }
    }


    public function buscarCrucesCuentas(Request $request)
    {
        $tipo = $request->tipo;
        if($tipo == 'facturas'){
             // Consulta principal: obtener los abonos que coinciden con los parámetros
            $abonos = DB::table('abono as a')
            ->join('1_1_cuenta_pago as cp', 'cp.cue_pag_codigo', '=', 'a.abono_cuenta')
            ->select('a.*', 'cp.cue_pag_numero', 'cp.cue_pag_nombre', 'cp.cue_pag_tipo_cuenta')
            ->where('a.abono_cuenta', 9)
            ->where('a.abono_periodo', $request->periodo)
            ->where('a.abono_ruc_cliente', $request->ruc)
            ->where('a.abono_empresa', $request->empresa)
            ->where('a.abono_facturas', '>', 0)
            ->where('a.abono_notas', 0)
            // ->where('a.abono_estado', 0)
            ->get();
        }else  if($tipo == 'notas'){
            // Consulta principal: obtener los abonos que coinciden con los parámetros
            $abonos = DB::table('abono as a')
            ->join('1_1_cuenta_pago as cp', 'cp.cue_pag_codigo', '=', 'a.abono_cuenta')
            ->select('a.*', 'cp.cue_pag_numero', 'cp.cue_pag_nombre', 'cp.cue_pag_tipo_cuenta')
            ->where('a.abono_cuenta', 9)
            ->where('a.abono_periodo', $request->periodo)
            ->where('a.abono_ruc_cliente', $request->ruc)
            ->where('a.abono_empresa', $request->empresa)
            ->where('a.abono_notas', '>',0)
            ->where('a.abono_facturas', 0)
            // ->where('a.abono_estado', 0)
            ->get();
        }
       
    
    
        // Agregar detalles a cada abono
        foreach ($abonos as $abono) {
            // Validar si existe la columna 'abono_documento'
            if (isset($abono->abono_documento)) {
                // Buscar el detalle de venta asociado al documento
                $detalleVenta = DB::table('f_detalle_venta')
                    ->where('ven_codigo', $abono->abono_documento)
                    ->where('id_empresa', $request->empresa)
                    ->first();
    
                // Agregar el detalle al abono si existe
                $abono->detalleVenta = $detalleVenta ?: null;
            } else {
                $abono->detalleVenta = null; // Si no tiene documento, asignar null
            }
        }
    
        // Retornar todos los abonos con sus detalles como JSON
        return response()->json($abonos);
    }

    public function getReferenciaNotaCredito(Request $request)
    {
        $query = DB::SELECT("SELECT DISTINCT a.referencia_nota_cred_interna FROM abono a WHERE a.abono_documento = '$request->id'");
        return $query;
    }
    
    

}
