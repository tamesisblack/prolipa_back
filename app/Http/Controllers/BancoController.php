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
        $query = DB::SELECT("SELECT chq.*, ban.ban_nombre FROM cheque chq 
        INNER JOIN 1_1_bancos ban ON ban.ban_codigo = chq.ban_codigo
        WHERE chq_institucion = $request->institucion
        AND chq_periodo = $request->periodo
        AND chq_empresa = $request->empresa");
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
            'chq_cuenta' => 'required|integer',
            'chq_referenca' => 'required|integer',
            'chq_numero' => 'required|integer',
            'chq_institucion' => 'required|string', // Ajustar según tipo de dato
            'chq_periodo' => 'required|string', // Ajustar según tipo de dato
            'chq_empresa' => 'required', // Ajustar según tipo de dato
        ]);

        // Verificar si el número de cheque ya existe
        $chequeExistente = Cheque::where('chq_numero', $request->chq_numero)
                                ->where('chq_cuenta', $request->chq_cuenta)
                                ->exists();

        if ($chequeExistente) {
            return response()->json([
                'status' => 0,
                'message' => 'El número de cheque ya está registrado en la base de datos',
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
            'chq_institucion' => $request->chq_institucion,
            'chq_periodo' => $request->chq_periodo,
            'chq_empresa' => $request->chq_empresa,
        ]);

        // Retornar una respuesta JSON adecuada
        return response()->json([
            'status' => 1,
            'message' => 'Cheque registrado correctamente',
            'cheque' => $cheque
        ]);
    }

}
