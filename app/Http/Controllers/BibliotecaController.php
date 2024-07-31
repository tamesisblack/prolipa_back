<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class BibliotecaController extends Controller
{

    /**
     * Obtener areas
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAreas(Request $request): JsonResponse
    {
        try {
            // Obtener los parámetros de la consulta
            $data = DB::table('area')
                ->select('idarea', 'nombrearea')
                ->where('estado', '1')
                ->where('tipoareas_idtipoarea', '1')
                ->get();

            return response()->json([
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Summary of getCategorias
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $area
     * @return JsonResponse|mixed
     */
    public function getCategorias(Request $request, $area)
    {
        try {
            // Obtener los parámetros de la consulta
            $query = DB::select(
                "SELECT id, nombre, descripcion
                FROM area_categoria
                WHERE estado = '1'
                AND idarea = ?",
                [$area]
            );

            /* Response */
            return response()->json([
                'data' => $query
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener libros
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLibros(Request $request): JsonResponse
    {
        try {
            // Parámetros de la consulta
            $idArea = $request->query('idArea', 0);
            $idSerie = $request->query('idSerie', 0);

            // Consulta a la BDD
            $resultado = DB::table('libro AS l')
                ->select('ar.nombrearea', 's.nombre_serie', 'a.idasignatura AS asignatura', 'l.*')
                ->join('libros_series AS ls', 'ls.idLibro', '=', 'l.idlibro')
                ->join('series AS s', 's.id_serie', '=', 'ls.id_serie')
                ->join('asignatura AS a', 'a.idasignatura', '=', 'l.asignatura_idasignatura')
                ->join('area AS ar', 'ar.idarea', '=', 'a.area_idarea')
                // Si idArea es distinto de 0, entonces filtrar por idArea
                ->when($idArea != 0, function ($query) use ($idArea) {
                    return $query->where('ar.idarea', $idArea);
                })
                // Si idSerie es distinto de 0, entonces filtrar por idSerie
                ->when($idSerie != 0, function ($query) use ($idSerie) {
                    return $query->where('s.id_serie', $idSerie);
                })
                ->get();

            /* Response */
            return response()->json([
                'data' => $resultado
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener unidades de un libro
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $libro
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnidades(Request $request, $libro): JsonResponse
    {
        try {
            // Consulta a la BDD
            $query = DB::select(
                "SELECT ul.id_unidad_libro, CONCAT('UNIDAD', ' ',ul.unidad, ': ', ul.nombre_unidad) AS unidad, ul.unidad as numero_unidad
                FROM `libro` l
                INNER JOIN `unidades_libros` ul ON l.idlibro = ul.id_libro
                WHERE l.idlibro = ?
                ORDER BY ul.unidad;",
                [$libro]
            );

            /* Response */
            return response()->json([
                'data' => $query
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener contenidos de un libro
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|mixed
     */
    public function getContenidos(Request $request)
    {
        try {
            $idArea = $request->query('idArea', null);
            $serie = $request->query('serie', null);
            $idLibro = $request->query('idLibro', null);
            $idUnidad = $request->query('idUnidad', null);
            $param = $request->query('param', null);
            $filterContent = $request->query('filterContent', false);
            $boolValue = filter_var($filterContent, FILTER_VALIDATE_BOOLEAN);
            /*
            $param = $request->query('param', '');
            $idArea = $request->query('idArea', 0);
            $idLibro = $request->query('idLibro', 0);
            $idUnidadLibro = $request->query('idUnidadLibro', 0);
            $filterContent = $request->query('filterContent', false);
            $pa = filter_var($filterContent, FILTER_VALIDATE_BOOLEAN); */

            // Verificar si el área cuenta con formato
            $formato = DB::table('formato')
                ->select('idformato')
                ->where('estadoformato', 1)
                ->where('idarea', $idArea)
                ->first();

            if ($formato == null) {
                return response()->json([
                    'data' => []
                ], 200);
            }

            /* // Consulta a la BDD
            $cacheKey = "getContenidos:param:$param:idArea:$idArea:serie:$serie:idLibro:$idLibro:idUnidadLibro:$idUnidadLibro:filterContent:$boolValue";
            $res = Cache::remember($cacheKey, 60 * 60, function () use ($param, $idArea, $serie, $idLibro, $idUnidadLibro, $boolValue) {
                $query = DB::table('contenido_libro')
                    ->select(
                        'contenido_libro.id as contenido_libro_id',
                        'contenido_libro.contenido',
                        'contenido_libro.created_at',
                        'series.nombre_serie',
                        'area.idarea',
                        'area.nombrearea',
                        'unidades_libros.unidad',
                        'unidades_libros.nombre_unidad',
                        DB::raw("CONCAT('UNIDAD ', unidades_libros.unidad, ': ', unidades_libros.nombre_unidad) AS texto_unidad"),
                        'libro.idlibro as libro_id',
                        'libro.nombrelibro',
                        DB::raw("CONCAT(usuario.apellidos, ' ', usuario.nombres) AS nombre_completo")
                    )
                    ->join('libro', 'contenido_libro.idlibro', '=', 'libro.idlibro')
                    ->join('libros_series', 'libro.idlibro', '=', 'libros_series.idLibro')
                    ->join('series', 'libros_series.id_serie', '=', 'series.id_serie')
                    ->join('asignatura', 'libro.asignatura_idasignatura', '=', 'asignatura.idasignatura')
                    ->join('area', 'asignatura.area_idarea', '=', 'area.idarea')
                    ->join('unidades_libros', 'contenido_libro.idunidad', '=', 'unidades_libros.id_unidad_libro')
                    ->join('usuario', 'contenido_libro.idusuario', '=', 'usuario.idusuario')
                    ->where('contenido_libro.estado', 1)
                    ->when($param != '', function ($query) use ($param, $boolValue) {
                        if ($boolValue) {
                            return $query->where('contenido_libro.contenido', 'LIKE', '%' . $param . '%')->orWhere('unidades_libros.nombre_unidad', 'LIKE', '%' . $param . '%');
                        } else {
                            return $query->where('unidades_libros.nombre_unidad', 'LIKE', '%' . $param . '%');
                        }
                    })
                    ->when($serie != '' && $serie != null && $serie != 'todas', function ($query) use ($serie) {
                        return $query->where('series.nombre_serie', 'LIKE', '%' . $serie . '%');
                    })
                    ->when($idLibro != 0 && $idLibro != null, function ($query) use ($idLibro) {
                        return $query->where('libro.idlibro', $idLibro);
                    })
                    ->when($idUnidadLibro != 0 && $idUnidadLibro != null, function ($query) use ($idUnidadLibro) {
                        return $query->where('unidades_libros.id_unidad_libro', $idUnidadLibro);
                    })
                    ->get();

                return $query;
            });

            return response()->json([
                'data' => $res
            ], 200); */

            $query = DB::table('contenido_libro')
                ->select(
                    'contenido_libro.id as contenido_libro_id',
                    'contenido_libro.contenido',
                    'contenido_libro.created_at',
                    'series.nombre_serie',
                    'area.idarea',
                    'area.nombrearea',
                    'unidades_libros.unidad',
                    'unidades_libros.nombre_unidad',
                    DB::raw("CONCAT('UNIDAD ', unidades_libros.unidad, ': ', unidades_libros.nombre_unidad) AS texto_unidad"),
                    'libro.idlibro as libro_id',
                    'libro.nombrelibro',
                    DB::raw("CONCAT(usuario.apellidos, ' ', usuario.nombres) AS nombre_completo")
                )
                ->join('libro', 'contenido_libro.idlibro', '=', 'libro.idlibro')
                ->join('libros_series', 'libro.idlibro', '=', 'libros_series.idLibro')
                ->join('series', 'libros_series.id_serie', '=', 'series.id_serie')
                ->join('asignatura', 'libro.asignatura_idasignatura', '=', 'asignatura.idasignatura')
                ->join('area', 'asignatura.area_idarea', '=', 'area.idarea')
                ->join('unidades_libros', 'contenido_libro.idunidad', '=', 'unidades_libros.id_unidad_libro')
                ->join('usuario', 'contenido_libro.idusuario', '=', 'usuario.idusuario')
                ->where('contenido_libro.estado', 1)
                ->when($idArea, function ($query) use ($idArea) {
                    return $query->where('area.idarea', $idArea);
                })
                ->when($serie, function ($query) use ($serie) {
                    return $query->where('series.nombre_serie', 'LIKE', '%' . $serie . '%');
                })
                ->when($idLibro, function ($query) use ($idLibro) {
                    return $query->where('libro.idlibro', $idLibro);
                })
                ->when($param && !$boolValue, function ($query) use ($param) {
                    return $query->where('unidades_libros.nombre_unidad', 'LIKE', '%' . $param . '%')->orWhere('unidades_libros.nombre_unidad', 'LIKE', '%' . $param . '%');
                })
                ->when($param && $boolValue, function ($query) use ($param) {
                    return $query->where('contenido_libro.contenido', 'LIKE', '%' . $param . '%')->orWhere('unidades_libros.nombre_unidad', 'LIKE', '%' . $param . '%');
                })
                ->when($idUnidad, function ($query) use ($idUnidad) {
                    return $query->where('unidades_libros.id_unidad_libro', $idUnidad);
                })
                ->get();

            return response()->json([
                'data' => $query
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * obtener contenido
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|mixed
     */
    public function getContenido(Request $request)
    {
        try {
            // Parámetros de la consulta
            $id = $request->query('id', 0);

            // Consulta a la BDD
            $query = DB::table('contenido_libro')
                ->select('area.nombrearea', 'unidades_libros.*', 'contenido_libro.id', 'contenido_libro.contenido', 'libro.nombrelibro', 'series.nombre_serie', 'usuario.nombres', 'usuario.apellidos')
                ->join('libro', 'contenido_libro.idlibro', '=', 'libro.idlibro')
                ->join('libros_series', 'libro.idlibro', '=', 'libros_series.idLibro')
                ->join('series', 'libros_series.id_serie', '=', 'series.id_serie')
                ->join('asignatura', 'libro.asignatura_idasignatura', '=', 'asignatura.idasignatura')
                ->join('area', 'asignatura.area_idarea', '=', 'area.idarea')
                ->join('unidades_libros', 'contenido_libro.idunidad', '=', 'unidades_libros.id_unidad_libro')
                ->join('usuario', 'contenido_libro.idusuario', '=', 'usuario.idusuario')
                ->where('contenido_libro.estado', '1')
                ->where('contenido_libro.id', $id)
                // Obtener solo el primer resultado
                ->first();

            /* Response */
            return response()->json([
                'data' => $query
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene información del formato de contenido o un contenido específico según los parámetros.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFormatoContenido(Request $request)
    {
        try {
            // Obtener los parámetros de la consulta
            $contenidoId = $request->query('contenidoId', 0);
            $areaId = $request->query('areaId', 0);

            if ($contenidoId != 0) {
                // Si se proporciona un ID de contenido, obtener el formato asociado
                $query = DB::table('contenido_libro')
                    ->select('area.nombrearea', 'unidades_libros.*', 'contenido_libro.id', 'contenido_libro.contenido', 'libro.idlibro', 'libro.nombrelibro', 'series.nombre_serie')
                    ->join('libro', 'contenido_libro.idlibro', '=', 'libro.idlibro')
                    ->join('libros_series', 'libro.idlibro', '=', 'libros_series.idLibro')
                    ->join('series', 'libros_series.id_serie', '=', 'series.id_serie')
                    ->join('asignatura', 'libro.asignatura_idasignatura', '=', 'asignatura.idasignatura')
                    ->join('area', 'asignatura.area_idarea', '=', 'area.idarea')
                    ->join('unidades_libros', 'contenido_libro.idunidad', '=', 'unidades_libros.id_unidad_libro')
                    ->where('contenido_libro.estado', '1')
                    ->where('contenido_libro.id', $contenidoId)
                    ->first();

                // Respuesta JSON con los datos obtenidos y código de estado 200 (éxito)
                return response()->json([
                    'data' => $query
                ], 200);
            } else {
                // Si no se proporciona un ID de contenido, obtener el formato asociado al área
                $query = DB::table('formato')
                    ->select('*')
                    ->where('estadoformato', '1')
                    ->where('idarea', $areaId)
                    ->first();

                // Respuesta JSON con los datos obtenidos y código de estado 200 (éxito)
                return response()->json([
                    'data' => $query
                ], 200);
            }
        } catch (\Throwable $th) {
            // Manejo de errores: en caso de error, responder con un mensaje de error y código de estado 500 (error interno del servidor)
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Crea un nuevo contenido de libro.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function crearContenido(Request $request): JsonResponse
    {
        try {
            // Obtener todos los datos de la solicitud HTTP
            $input = $request->all();

            // Verificar si idContenido existe
            if (isset($input['idContenido'])) {
                // Actualizar el registro existente
                $data = DB::table('contenido_libro')
                    ->where('id', $input['idContenido'])
                    ->update([
                        'contenido' => $input['contenido'],
                    ]);

                // Respuesta JSON con los datos resultantes y código de estado 200 (éxito)
                return response()->json([
                    'data' => $data
                ], 200);
            }

            // Insertar un nuevo registro en la tabla 'contenido_libro' de la base de datos
            $data = DB::table('contenido_libro')->insert([
                'idlibro' => $input['idLibro'],
                'idunidad' => $input['idUnidad'],
                'contenido' => $input['contenido'],
                'estado' => '1',
                'idusuario' => $input['idUsuario'],
            ]);

            // Respuesta JSON con los datos resultantes y código de estado 200 (éxito)
            return response()->json([
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            // Manejo de errores: en caso de error, responder con un mensaje de error y código de estado 500 (error interno del servidor)
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function deleteContenido(Request $request, $id): JsonResponse
    {
        try {
            // Obtener el contenido a eliminar
            $contenido = DB::table('contenido_libro')
                ->select('id')
                ->where('id', $id)
                ->first();

            // Verificar si el contenido existe
            if ($contenido == null) {
                // Respuesta JSON con mensaje de error y código de estado 404 (no encontrado)
                return response()->json([
                    'error' => 'El contenido no existe'
                ], 404);
            }

            // Eliminar el contenido
            $data = DB::delete(
                "DELETE FROM contenido_libro
                WHERE id = ?",
                [$id]
            );

            // Respuesta JSON con los datos resultantes y código de estado 200 (éxito)
            return response()->json([
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            // Manejo de errores: en caso de error, responder con un mensaje de error y código de estado 500 (error interno del servidor)
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
