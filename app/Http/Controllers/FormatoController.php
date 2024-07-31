<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
