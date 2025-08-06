<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReporteVersionado;
use App\Models\ReporteVersionadoCodigos;
use Illuminate\Support\Facades\DB;

class ReporteVersionadoController extends Controller
{
    public function guardarVersion(Request $request)
    {
        $nombre = $request->nombre;
        $periodo_id = $request->periodo_id;
        $serie_id = $request->serie;
        $data = $request->data;
        $usuario = $request->usuario;
        $totalCodigosActual = $request->total; // Usar el total enviado desde el frontend

        DB::beginTransaction();

        try {

            // Obtener la última versión guardada
            $ultima = ReporteVersionado::where('nombre_reporte', $nombre)
                ->where('periodo_id', $periodo_id)
                ->where('serie_id', $serie_id)
                ->orderByDesc('version')
                ->first();

            // Si existe una versión anterior, comparar el total de códigos
            if ($ultima && $ultima->total_codigos == $totalCodigosActual) {
                DB::rollBack();
                return response()->json([
                    'mensaje' => 'Sin cambios. No se guarda nueva versión.',
                    'nueva_version' => false
                ]);
            }

            // Crear nueva versión del reporte
            $nuevaVersion = new ReporteVersionado();
            $nuevaVersion->nombre_reporte = $nombre;
            $nuevaVersion->periodo_id = $periodo_id;
            $nuevaVersion->serie_id = $serie_id;
            $nuevaVersion->total_codigos = $totalCodigosActual;
            $nuevaVersion->version = $ultima ? $ultima->version + 1 : 1;
            $nuevaVersion->user_created = $usuario;
            $nuevaVersion->save();

            // Procesar y guardar los códigos en la tabla separada
            $this->procesarYGuardarCodigos($nuevaVersion->id, $data);

            DB::commit();

            return response()->json([
                'mensaje' => 'Nueva versión guardada.',
                'nueva_version' => true,
                'version' => $nuevaVersion->version
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'mensaje' => 'Error al guardar la versión: ' . $e->getMessage(),
                'nueva_version' => false
            ], 500);
        }
    }



    private function procesarYGuardarCodigos($reporteVersionadoId, $data)
    {
        foreach ($data as $item) {
            $codigoLibro = $item['codigo'] ?? $item['codigo_liquidacion'] ?? '';
            $cantidad = $item['cantidad'] ?? $item['cantidad_guias'] ?? 0;
            $codigos = $item['Codigos'] ?? '';

            // Si hay códigos, guardar todos en un solo registro
            if (!empty($codigos) && !empty($codigoLibro)) {
                ReporteVersionadoCodigos::create([
                    'reporte_versionado_id' => $reporteVersionadoId,
                    'codigo_libro' => $codigoLibro,
                    'cantidad' => $cantidad,
                    'codigo' => $codigos
                ]);
            }
        }
    }

    public function listarVersiones(Request $request)
    {
        $periodo_id = $request->input('periodo_id');
        $serie_id = $request->input('serie_id');
        $nombre = $request->input('nombre');

        $query = ReporteVersionado::select(
            'reportes_versionados.*',
            'p.periodoescolar as nombre_periodo',
            's.nombre_serie',
            DB::raw('concat(u.nombres, " ", u.apellidos) as nombre_usuario')
        )
            ->leftJoin('periodoescolar as p', 'reportes_versionados.periodo_id', '=', 'p.idperiodoescolar')
            ->leftJoin('series as s', 'reportes_versionados.serie_id', '=', 's.id_serie')
            ->leftJoin('usuario as u', 'reportes_versionados.user_created', '=', 'u.idusuario');

        if ($periodo_id) {
            $query->where('reportes_versionados.periodo_id', $periodo_id);
        }
        if ($serie_id) {
            $query->where('reportes_versionados.serie_id', $serie_id);
        }
        if ($nombre) {
            $query->where('reportes_versionados.nombre_reporte', $nombre);
        }

        $versiones = $query->orderByDesc('reportes_versionados.created_at')->get();

        // Optionally, include the count of codes for each version
        foreach ($versiones as $version) {
            $version->codigos_count = ReporteVersionadoCodigos::where('reporte_versionado_id', $version->id)->count();
        }

        return response()->json($versiones);
    }

    // New method to retrieve codes for a specific report version
    public function getCodigos(Request $request, $reporte_versionado_id)
    {
        $codigos = ReporteVersionadoCodigos::where('reporte_versionado_id', $reporte_versionado_id)
            ->pluck('codigo')
            ->toArray();

        return response()->json($codigos);
    }

    public function obtenerDetalleVersion(Request $request)
    {
        $versionId = $request->input('version_id');

        $version = ReporteVersionado::find($versionId);

        if (!$version) {
            return response()->json(['error' => 'Versión no encontrada'], 404);
        }

        // Obtener todos los registros de códigos para esta versión con información del producto
        $codigos = DB::table('reportes_versionados_codigos as rvc')
            ->leftJoin('1_4_cal_producto as p', 'rvc.codigo_libro', '=', 'p.pro_codigo')
            ->select(
                'rvc.codigo_libro', 
                'rvc.cantidad', 
                'rvc.codigo',
                'p.pro_nombre as nombre_libro'
            )
            ->where('rvc.reporte_versionado_id', $versionId)
            ->get();

        // Crear formato para la vista
        $data = [];
        foreach ($codigos as $item) {
            // Contar cuántos códigos hay (separados por comas)
            $totalCodigos = !empty($item->codigo) ? count(explode(',', $item->codigo)) : 0;
            
            $data[] = [
                'codigo_libro' => $item->codigo_libro,
                'nombre_libro' => $item->nombre_libro ?? 'Sin nombre',
                'cantidad' => $item->cantidad,
                'total_codigos' => $totalCodigos,
                'codigos' => $item->codigo,
            ];
        }

        return response()->json([
            'version' => $version,
            'data' => $data
        ]);
    }
}
