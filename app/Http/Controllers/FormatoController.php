<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\FormatoPedidoNew;
use Illuminate\Support\Facades\DB;

class FormatoController extends Controller
{
    public function getFormatos(Request $request)
    {
        try {
            $areaId = $request->query('areaId');

            if ($areaId) {
                $query = DB::table('formato')
                    ->select('*')
                    ->where('estadoformato', '1')
                    ->where('idarea', $areaId)
                    ->first();
                return response()->json([
                    'data' => $query
                ], 200);
            } else {
                $query = DB::table('formato')
                    ->select('*')
                    ->where('estadoformato', '1')
                    ->first();
                return response()->json([
                    'data' => $query
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Create format
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function crearFormato(Request $request): JsonResponse
    {
        try {
            $area = $request->area;

            $data = $request->all();

            if (!$area) {
                return response()->json([
                    'error' => 'No se ha enviado el area'
                ], 400);
            }

            $formato = DB::table('formato')
                ->select('*')
                ->where('idarea', $area['idarea'])
                ->first();

            if ($formato) {
                $data = DB::table('formato')
                    ->where('idarea', $area['idarea'])
                    ->update([
                        'contenidoformato' => json_encode($request->contenidos)
                    ]);

                return response()->json([
                    'data' => $data
                ], 200);
            } else {
                $data = DB::table('formato')->insert([
                    'idarea' => $area['idarea'],
                    'nombreformato' => 'Formato ' . $area['nombrearea'],
                    'contenidoformato' => json_encode($request->contenidos),
                    'estadoformato' => '1'
                ]);

                return response()->json([
                    'data' => $data
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    //METODOS JEYSON
    public function Post_GuardarActualizar_formatopedidonew(Request $request)
    {
        DB::beginTransaction();
        // return $request;
        // Obtener los datos del request
        $data_libros = $request->input('data_libros', []);
        $periodo = $request->input('periodo');
        $user_editor = $request->input('user_editor');
        // $conteo = $this->conteodetallexfmp_id($id_movimientoproducto);
        // return $conteo;

        try {
            // Si hay libros, procesar los detalles del pedido
            foreach ($data_libros as $libro) {
                $detalleFormatoPedido = FormatoPedidoNew::where('idlibro', $libro['idlibro'])
                    ->where('idperiodoescolar', $periodo)
                    ->first();
                if ($detalleFormatoPedido) {
                    $detalleFormatoPedido->pfn_pvp = $libro['pfn_pvp'];
                    $detalleFormatoPedido->user_editor = $user_editor;
                    $detalleFormatoPedido->save();
                } else {
                    $detalleFormatoPedido = new FormatoPedidoNew([
                        'idlibro'           => $libro['idlibro'],
                        'pfn_pvp'           => $libro['pfn_pvp'],
                        'idperiodoescolar'  => $periodo,
                        'user_editor'       => $user_editor,
                    ]);
                    // $detalleFormatoPedido->updated_at = now();
                    $detalleFormatoPedido->save();
                }
            }
            DB::commit();
            return response()->json(["status" => "1", 'message' => 'Datos actualizados correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }

    public function ActivarDesactivar_formatopedidonew(Request $request)
    {
        // return $request;
        if ($request->pfn_id) {
            $estado_FormatoPedido = FormatoPedidoNew::find($request->pfn_id);

            if (!$estado_FormatoPedido) {
                return "El pfn_id no existe en la base de datos";
            }

            $estado_FormatoPedido->pfn_estado = $request->pfn_estado;
            $estado_FormatoPedido->save();

            return $estado_FormatoPedido;
        } else {
            return "No está ingresando ningún pfn_id";
        }
    }

    //FIN METODOS JEYSON
}
