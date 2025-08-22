<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\CodigosLibros;
use App\Models\HistoricoCodigos;
use App\Models\Libro;
use App\Models\LibroSerie;
use DataTables;
Use Exception;
use Carbon\Carbon;

class CodigosLibrosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $auxlibros = [];
        $auxlibrosf = [];
        $auxlibros = $codigos_libros = DB::SELECT("SELECT libro.*,asignatura.* from codigoslibros join libro on libro.idlibro = codigoslibros.libro_idlibro join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura  WHERE idusuario = ?",[$request->idusuario]);
        $usuario = DB::SELECT("SELECT * FROM usuario WHERE idusuario = ?",[$request->idusuario]);
        $idinstitucion = '';
        foreach ($usuario as $key => $value) {
            $idinstitucion = $value->institucion_idInstitucion;
        }

        if(!empty($codigos_libros)){
            foreach ($codigos_libros as $key => $value) {
                $free = DB::SELECT("SELECT libro.*,asignatura.* FROM institucion_libro join libro on libro.idlibro = institucion_libro.idlibro join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura  WHERE institucion_libro.idinstitucion = ? AND asignatura.nivel_idnivel = ? AND institucion_libro.estado = '1'",[$idinstitucion,$value->nivel_idnivel]);
                foreach ($free as $keyl => $valuel) {
                    array_push($auxlibros, $valuel);
                }
            }
        }
        $auxlibrosf = array_unique($auxlibros, SORT_REGULAR);


        return $auxlibrosf;
    }

    //api get>>/codigoslibrosEstudiante
    public function codigoslibrosEstudiante(Request $request){
        $auxlibros = [];
        $auxlibrosf = [];
        $auxlibros = $codigos_libros = DB::SELECT("SELECT libro.*,asignatura.* from codigoslibros join libro on libro.idlibro = codigoslibros.libro_idlibro join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura  WHERE idusuario = ?",[$request->idusuario]);
        $usuario = DB::SELECT("SELECT * FROM usuario WHERE idusuario = ?",[$request->idusuario]);
        $idinstitucion = '';
        foreach ($usuario as $key => $value) {
            $idinstitucion = $value->institucion_idInstitucion;
        }

        if(!empty($codigos_libros)){
            foreach ($codigos_libros as $key => $value) {
                $free = DB::SELECT("SELECT libro.*,asignatura.* FROM institucion_libro join libro on libro.idlibro = institucion_libro.idlibro join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura  WHERE institucion_libro.idinstitucion = ? AND asignatura.nivel_idnivel = ? AND institucion_libro.estado = '1'",[$idinstitucion,$value->nivel_idnivel]);
                foreach ($free as $keyl => $valuel) {
                    array_push($auxlibros, $valuel);
                }
            }
        }
        $auxlibrosf = array_unique($auxlibros, SORT_REGULAR);
        foreach($auxlibrosf as $k => $item){
            $data[] = DB::SELECT("SELECT j.*, a.nombreasignatura FROM juegos j
             LEFT JOIN asignatura a ON a.idasignatura = j.asignatura_idasignatura
             WHERE j.asignatura_idasignatura = $item->idasignatura

            ");
        }

        $contador1 =0;
        while ($contador1 < count($data)) {

            if(count($data) == 1){
                $array_resultante= $data[0];
            }

            if(count($data) == 2){
                $array_resultante= array_merge($data[0],$data[1]);
            }

            if(count($data) == 3){
                $array_resultante= array_merge($data[0],$data[1],$data[2]);
            }

            if(count($data) == 4){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3]);
            }

            if(count($data) == 5){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4]);
            }

            if(count($data) == 6){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5]);
            }

            if(count($data) == 7){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6]);
            }

            if(count($data) == 8){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7]);
            }

            if(count($data) == 9){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8]);
            }

            if(count($data) == 10){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9]);
            }

            if(count($data) == 11){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10]);
            }


            if(count($data) == 12){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11]);
            }

            if(count($data) == 13){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12]);
            }

            if(count($data) == 14){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12],$data[13]);
            }

            if(count($data) == 15){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12],$data[13],$data[14]);
            }

            if(count($data) == 16){
                $array_resultante= array_merge($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10],$data[11],$data[12],$data[13],$data[14],$data[15]);
            }

            $contador1=$contador1+1;
        }
        return $array_resultante;

    }

    public function codigosCuaderno(Request $request){
        $codigos_libros = DB::SELECT("SELECT cuaderno.* from codigoslibros join libro on libro.idlibro = codigoslibros.libro_idlibro join cuaderno on cuaderno.asignatura_idasignatura = libro.asignatura_idasignatura  WHERE idusuario = ? AND codigoslibros.codigo LIKE '%PLUS%'",[$request->idusuario]);
        return $codigos_libros;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function codigos_libros_estudiante($id,$institucion,$periodo,$region,$grupo){
        $auxlibros = [];
        $nivel = 0;
        // $auxlibros = $codigos_libros = DB::SELECT("SELECT libro.*,asignatura.*,
        // codigoslibros.serie as serieCodigo, codigoslibros.anio,codigoslibros.updated_at as fechaUpdate, codigoslibros.plus,
        // codigoslibros.codigo
        //  from codigoslibros
        //  join libro on libro.idlibro = codigoslibros.libro_idlibro
        //  join asignatura on asignatura.idasignatura = libro.asignatura_idasignatura
        //  WHERE codigoslibros.id_periodo = '$periodo'
        //  AND codigoslibros.idusuario = ?
        // AND (
		// 	codigoslibros.estado_liquidacion <> 3
		// 	OR codigoslibros.quitar_de_reporte = '1'
		// 	)
        //  AND codigoslibros.estado <> 2
        //  ",[$id]);
        //  return $auxlibros;

        $auxlibros = DB::SELECT("
            -- Casos donde plus = 1
            SELECT
                lib_plus.*,
                a_plus.*,
                codigoslibros.serie AS serieCodigo,
                codigoslibros.anio,
                codigoslibros.updated_at AS fechaUpdate,
                codigoslibros.plus,
                codigoslibros.codigo
            FROM codigoslibros
            JOIN libro ON libro.idlibro = codigoslibros.libro_idlibro
            JOIN asignatura ON asignatura.idasignatura = libro.asignatura_idasignatura
            LEFT JOIN libros_series ls ON ls.idLibro = libro.idlibro
            LEFT JOIN libros_series l_plus ON l_plus.idLibro = ls.id_libro_plus
            LEFT JOIN libro lib_plus ON ls.id_libro_plus = lib_plus.idlibro
            LEFT JOIN asignatura a_plus ON lib_plus.asignatura_idasignatura = a_plus.idasignatura
            WHERE codigoslibros.id_periodo = '$periodo'
            AND codigoslibros.idusuario = '$id'
            AND (
                    codigoslibros.estado_liquidacion <> 3
                    OR codigoslibros.quitar_de_reporte = '1'
                )
            AND codigoslibros.estado <> 2
            AND codigoslibros.plus = 1

            UNION ALL

            -- Casos donde plus ≠ 1
            SELECT
                libro.*,
                asignatura.*,
                codigoslibros.serie AS serieCodigo,
                codigoslibros.anio,
                codigoslibros.updated_at AS fechaUpdate,
                codigoslibros.plus,
                codigoslibros.codigo
            FROM codigoslibros
            JOIN libro ON libro.idlibro = codigoslibros.libro_idlibro
            JOIN asignatura ON asignatura.idasignatura = libro.asignatura_idasignatura
            LEFT JOIN libros_series ls ON ls.idLibro = libro.idlibro
            LEFT JOIN libros_series l_plus ON l_plus.idLibro = ls.id_libro_plus
            LEFT JOIN libro lib_plus ON ls.id_libro_plus = lib_plus.idlibro
            LEFT JOIN asignatura a_plus ON lib_plus.asignatura_idasignatura = a_plus.idasignatura
            WHERE codigoslibros.id_periodo = '$periodo'
            AND codigoslibros.idusuario = '$id'
            AND (
                    codigoslibros.estado_liquidacion <> 3
                    OR codigoslibros.quitar_de_reporte = '1'
                )
            AND codigoslibros.estado <> 2
            AND (codigoslibros.plus IS NULL OR codigoslibros.plus <> 1);
        ");
        if(!empty($auxlibros)){
            foreach ($auxlibros as $key => $value) {
                $nivel = $value->nivel_idnivel;
                $free = DB::SELECT("SELECT l.*, a.*,s.nombre_serie as serieCodigo,
                ls.year as anio, ls.updated_at  as fechaUpdate
                 FROM free_estudiante_libro f
                JOIN libro l ON f.libro_id  = l.idlibro
                JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                LEFT JOIN libros_series ls ON ls.idLibro = l.idlibro
                LEFT JOIN series s ON ls.id_serie = s.id_serie
                WHERE f.institucion_id = '$institucion'
                AND f.nivel_id = '$nivel'
                ");
                 foreach ($free as $keyl => $valuel) {
                    array_push($auxlibros, $valuel);
                }
            }
        }
        $datos = [];
        foreach ($auxlibros as $key => $item) {
            //variables por defecto
            $weblibro       = $item->weblibro;
            $portada        = $item->portada;
            $pdfsinguia     = $item->pdfsinguia;
            $pdfconguia     = $item->pdfconguia;
            $guiadidactica  = $item->guiadidactica;
            //sierra
            if($region == 1){
                //si no hay libro de costa asignado tomamos el por defecto
                if($item->s_weblibro != null || $item->s_weblibro != ""){
                    $weblibro       = $item->s_weblibro;
                    $portada        = $item->s_portada;
                    $pdfsinguia     = $item->s_pdfsinguia;
                    $pdfconguia     = $item->s_pdfconguia;
                    $guiadidactica  = $item->s_guiadidactica;
                }
            }
            //costa
            if($region == 2){
                //si no hay libro de costa asignado tomamos el por defecto
                if($item->c_weblibro != null || $item->c_weblibro != ""){
                    $weblibro       = $item->c_weblibro;
                    $portada        = $item->c_portada;
                    $pdfsinguia     = $item->c_pdfsinguia;
                    $pdfconguia     = $item->c_pdfconguia;
                    $guiadidactica  = $item->c_guiadidactica;
                }
            }
            $mostratCode = "";
            if(isset($item->codigo)){
                $mostratCode  = $item->codigo;
            }
            if($grupo == 10){
                $datos[$key] = (Object)[
                    "idlibro"                   => $item->idlibro,
                    "nombrelibro"               => $item->nombrelibro,
                    "descripcionlibro"          => $item->descripcionlibro,
                    "serie"                     => $item->serieCodigo,
                    "anio"                      => $item->anio,
                    "fechaUpdate"               => $item->fechaUpdate,
                    "codigo"                    => $mostratCode,
                    "titulo"                    => $item->titulo,
                    "portada"                   => $portada,
                    "weblibro"                  => $weblibro,
                    "pdfsinguia"                => $pdfsinguia,
                    "pdfconguia"                => $pdfconguia,
                    "guiadidactica"             => $guiadidactica,
                    "Estado_idEstado"           => $item->Estado_idEstado,
                    "asignatura_idasignatura"   => $item->asignatura_idasignatura,
                    "ziplibro"                  => $item->ziplibro,
                    "libroFechaModificacion"    => $item->libroFechaModificacion,
                    "grupo"                     => $item->grupo,
                    "puerto"                    => $item->puerto,
                    "creado_at"                 => $item->creado_at,
                    "actualizado_at"            => $item->actualizado_at,
                    "idasignatura"              => $item->idasignatura,
                    "nombreasignatura"          => $item->nombreasignatura,
                    "area_idarea"               => $item->area_idarea,
                    "nivel_idnivel"             => $item->nivel_idnivel,
                    "tipo_asignatura"           => $item->tipo_asignatura,
                    "estado"                    => $item->estado,
                    "created_at"                => $item->created_at,
                    "updated_at"                => $item->updated_at,
                    "plus"                      => $item->plus ?? 0,
                    "id_folleto"                => $item->id_folleto ?? null,
                ];
            }else{
                $datos[$key] =(Object)[
                    "idlibro"                   => $item->idlibro,
                    "nombrelibro"               => $item->nombrelibro,
                    "descripcionlibro"          => $item->descripcionlibro,
                    "serie"                     => $item->serieCodigo,
                    "anio"                      => $item->anio,
                    // "fechaUpdate"               => $item->fechaUpdate,
                    // "codigo"                    => $mostratCode,
                    "titulo"                    => $item->titulo,
                    "portada"                   => $portada,
                    "weblibro"                  => $weblibro,
                    "pdfsinguia"                => $pdfsinguia,
                    "pdfconguia"                => $pdfconguia,
                    "guiadidactica"             => $guiadidactica,
                    "Estado_idEstado"           => $item->Estado_idEstado,
                    "asignatura_idasignatura"   => $item->asignatura_idasignatura,
                    "ziplibro"                  => $item->ziplibro,
                    "libroFechaModificacion"    => $item->libroFechaModificacion,
                    "grupo"                     => $item->grupo,
                    "puerto"                    => $item->puerto,
                    // "creado_at"                 => $item->creado_at,
                    // "actualizado_at"            => $item->actualizado_at,
                    "idasignatura"              => $item->idasignatura,
                    "nombreasignatura"          => $item->nombreasignatura,
                    "area_idarea"               => $item->area_idarea,
                    "nivel_idnivel"             => $item->nivel_idnivel,
                    "tipo_asignatura"           => $item->tipo_asignatura,
                    "estado"                    => $item->estado,
                    "plus"                      => $item->plus ?? 0,
                    "id_folleto"                => $item->id_folleto ?? null,
                    // "created_at"                => $item->created_at,
                    // "updated_at"                => $item->updated_at,
                ];
            }
        }
        $data = [];
        $auxlibrosf = $datos; // libros iniciales

        foreach ($auxlibrosf as $item) {
            $idFolleto = $item->id_folleto ?? null;

            if ($idFolleto) {
                $folletoResult = DB::SELECT("
                    SELECT l.*, a.*, s.nombre_serie AS serie
                    FROM libro l
                    LEFT JOIN libros_series ls ON ls.idLibro = l.idlibro
                    LEFT JOIN asignatura a ON a.idasignatura = l.asignatura_idasignatura
                    LEFT JOIN series s ON s.id_serie = ls.id_serie
                    WHERE l.idlibro = ?
                ", [$idFolleto]);

                if (!empty($folletoResult)) {
                    $auxlibrosf[] = $folletoResult[0];
                }
            }
        }

        // ✅ Eliminar duplicados por idlibro
        $librosUnicos = [];
        foreach ($auxlibrosf as $libro) {
            $librosUnicos[$libro->idlibro] = $libro; // usa el idlibro como clave
        }
        $auxlibrosf = array_values($librosUnicos); // reindexar

        // ✅ Ordenar alfabéticamente por nombrelibro
        usort($auxlibrosf, function($a, $b) {
            return strcmp($a->nombrelibro, $b->nombrelibro);
        });

        $data = [
            'libros' => $auxlibrosf,
            'nivel' => $nivel,
            'institucion' => $institucion,
        ];


        return $data;

    }

    public function libros_estudiante($id, $institucion, $periodo, $region, $grupo)
{
    $auxlibros = [];
    $nivel = 0;

    $auxlibros = DB::SELECT("
        -- Casos donde plus = 1
        SELECT
            lib_plus.*,
            a_plus.*,
            codigoslibros.serie AS serieCodigo,
            codigoslibros.anio,
            codigoslibros.updated_at AS fechaUpdate,
            codigoslibros.plus,
            codigoslibros.codigo,
            ul.pag_inicio,
            ul.pag_fin
        FROM codigoslibros
        JOIN libro ON libro.idlibro = codigoslibros.libro_idlibro
        JOIN asignatura ON asignatura.idasignatura = libro.asignatura_idasignatura
        LEFT JOIN libros_series ls ON ls.idLibro = libro.idlibro
        LEFT JOIN libros_series l_plus ON l_plus.idLibro = ls.id_libro_plus
        LEFT JOIN libro lib_plus ON ls.id_libro_plus = lib_plus.idlibro
        LEFT JOIN asignatura a_plus ON lib_plus.asignatura_idasignatura = a_plus.idasignatura
        LEFT JOIN unidades_libros ul ON lib_plus.idlibro = ul.id_libro
        WHERE codigoslibros.id_periodo = '$periodo'
        AND codigoslibros.idusuario = '$id'
        AND (
                codigoslibros.estado_liquidacion <> 3
                OR codigoslibros.quitar_de_reporte = '1'
            )
        AND codigoslibros.estado <> 2
        AND codigoslibros.plus = 1

        UNION ALL

        -- Casos donde plus ≠ 1
        SELECT
            libro.*,
            asignatura.*,
            codigoslibros.serie AS serieCodigo,
            codigoslibros.anio,
            codigoslibros.updated_at AS fechaUpdate,
            codigoslibros.plus,
            codigoslibros.codigo,
            ul.pag_inicio,
            ul.pag_fin
        FROM codigoslibros
        JOIN libro ON libro.idlibro = codigoslibros.libro_idlibro
        JOIN asignatura ON asignatura.idasignatura = libro.asignatura_idasignatura
        LEFT JOIN libros_series ls ON ls.idLibro = libro.idlibro
        LEFT JOIN libros_series l_plus ON l_plus.idLibro = ls.id_libro_plus
        LEFT JOIN libro lib_plus ON ls.id_libro_plus = lib_plus.idlibro
        LEFT JOIN asignatura a_plus ON lib_plus.asignatura_idasignatura = a_plus.idasignatura
        LEFT JOIN unidades_libros ul ON libro.idlibro = ul.id_libro
        WHERE codigoslibros.id_periodo = '$periodo'
        AND codigoslibros.idusuario = '$id'
        AND (
                codigoslibros.estado_liquidacion <> 3
                OR codigoslibros.quitar_de_reporte = '1'
            )
        AND codigoslibros.estado <> 2
        AND (codigoslibros.plus IS NULL OR codigoslibros.plus <> 1);
    ");

    if (!empty($auxlibros)) {
        foreach ($auxlibros as $key => $value) {
            $nivel = $value->nivel_idnivel;
            $free = DB::SELECT("
                SELECT l.*, a.*, s.nombre_serie AS serieCodigo,
                ls.year AS anio, ls.updated_at AS fechaUpdate,
                ul.pag_inicio, ul.pag_fin
                FROM free_estudiante_libro f
                JOIN libro l ON f.libro_id = l.idlibro
                JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                LEFT JOIN libros_series ls ON ls.idLibro = l.idlibro
                LEFT JOIN series s ON ls.id_serie = s.id_serie
                LEFT JOIN unidades_libros ul ON l.idlibro = ul.id_libro
                WHERE f.institucion_id = '$institucion'
                AND f.nivel_id = '$nivel'
            ");
            foreach ($free as $keyl => $valuel) {
                array_push($auxlibros, $valuel);
            }
        }
    }

    $datos = [];
    foreach ($auxlibros as $key => $item) {
        // Variables por defecto
        $weblibro = $item->weblibro;
        $portada = $item->portada;
        $pdfsinguia = $item->pdfsinguia;
        $pdfconguia = $item->pdfconguia;
        $guiadidactica = $item->guiadidactica;

        // Sierra
        if ($region == 1) {
            if (!empty($item->s_weblibro)) {
                $weblibro = $item->s_weblibro;
                $portada = $item->s_portada;
                $pdfsinguia = $item->s_pdfsinguia;
                $pdfconguia = $item->s_pdfconguia;
                $guiadidactica = $item->s_guiadidactica;
            }
        }
        // Costa
        if ($region == 2) {
            if (!empty($item->c_weblibro)) {
                $weblibro = $item->c_weblibro;
                $portada = $item->c_portada;
                $pdfsinguia = $item->c_pdfsinguia;
                $pdfconguia = $item->c_pdfconguia;
                $guiadidactica = $item->c_guiadidactica;
            }
        }

        $mostratCode = $item->codigo ?? "";

        if ($grupo == 10) {
            $datos[$key] = (object) [
                "idlibro" => $item->idlibro,
                "nombrelibro" => $item->nombrelibro,
                "descripcionlibro" => $item->descripcionlibro,
                "serie" => $item->serieCodigo,
                "anio" => $item->anio,
                "fechaUpdate" => $item->fechaUpdate,
                "codigo" => $mostratCode,
                "titulo" => $item->titulo,
                "portada" => $portada,
                "weblibro" => $weblibro,
                "pdfsinguia" => $pdfsinguia,
                "pdfconguia" => $pdfconguia,
                "guiadidactica" => $guiadidactica,
                "Estado_idEstado" => $item->Estado_idEstado,
                "asignatura_idasignatura" => $item->asignatura_idasignatura,
                "ziplibro" => $item->ziplibro,
                "libroFechaModificacion" => $item->libroFechaModificacion,
                "grupo" => $item->grupo,
                "puerto" => $item->puerto,
                "creado_at" => $item->creado_at,
                "actualizado_at" => $item->actualizado_at,
                "idasignatura" => $item->idasignatura,
                "nombreasignatura" => $item->nombreasignatura,
                "area_idarea" => $item->area_idarea,
                "nivel_idnivel" => $item->nivel_idnivel,
                "tipo_asignatura" => $item->tipo_asignatura,
                "estado" => $item->estado,
                "created_at" => $item->created_at,
                "updated_at" => $item->updated_at,
                "plus" => $item->plus ?? 0,
                "id_folleto" => $item->id_folleto ?? null,
                "pag_inicio" => $item->pag_inicio ?? null,
                "pag_fin" => $item->pag_fin ?? null,
            ];
        } else {
            $datos[$key] = (object) [
                "idlibro" => $item->idlibro,
                "nombrelibro" => $item->nombrelibro,
                "descripcionlibro" => $item->descripcionlibro,
                "serie" => $item->serieCodigo,
                "anio" => $item->anio,
                "titulo" => $item->titulo,
                "portada" => $portada,
                "weblibro" => $weblibro,
                "pdfsinguia" => $pdfsinguia,
                "pdfconguia" => $pdfconguia,
                "guiadidactica" => $guiadidactica,
                "Estado_idEstado" => $item->Estado_idEstado,
                "asignatura_idasignatura" => $item->asignatura_idasignatura,
                "ziplibro" => $item->ziplibro,
                "libroFechaModificacion" => $item->libroFechaModificacion,
                "grupo" => $item->grupo,
                "puerto" => $item->puerto,
                "idasignatura" => $item->idasignatura,
                "nombreasignatura" => $item->nombreasignatura,
                "area_idarea" => $item->area_idarea,
                "nivel_idnivel" => $item->nivel_idnivel,
                "tipo_asignatura" => $item->tipo_asignatura,
                "estado" => $item->estado,
                "plus" => $item->plus ?? 0,
                "id_folleto" => $item->id_folleto ?? null,
                "pag_inicio" => $item->pag_inicio ?? null,
                "pag_fin" => $item->pag_fin ?? null,
            ];
        }
    }

    $data = [];
    $auxlibrosf = $datos; // Libros iniciales

    foreach ($auxlibrosf as $item) {
        $idFolleto = $item->id_folleto ?? null;

        if ($idFolleto) {
            $folletoResult = DB::SELECT("
                SELECT l.*, a.*, s.nombre_serie AS serie,
                       ul.pag_inicio, ul.pag_fin
                FROM libro l
                LEFT JOIN libros_series ls ON ls.idLibro = l.idlibro
                LEFT JOIN asignatura a ON a.idasignatura = l.asignatura_idasignatura
                LEFT JOIN series s ON s.id_serie = ls.id_serie
                LEFT JOIN unidades_libros ul ON l.idlibro = ul.id_libro
                WHERE l.idlibro = ?
            ", [$idFolleto]);

            if (!empty($folletoResult)) {
                $auxlibrosf[] = $folletoResult[0];
            }
        }
    }

    // Eliminar duplicados por idlibro
    $librosUnicos = [];
    foreach ($auxlibrosf as $libro) {
        $librosUnicos[$libro->idlibro] = $libro; // Usa el idlibro como clave
    }
    $auxlibrosf = array_values($librosUnicos); // Reindexar

    // Ordenar alfabéticamente por nombrelibro
    usort($auxlibrosf, function($a, $b) {
        return strcmp($a->nombrelibro, $b->nombrelibro);
    });

    $data = [
        'libros' => $auxlibrosf,
        'nivel' => $nivel,
        'institucion' => $institucion,
    ];

    return $data;
}

    public function store(Request $request)
    {
        $validacion = DB::SELECT("SELECT *
        FROM  codigoslibros WHERE
        codigo = ?",["$request->codigo"]);
        $iduser = '';
        foreach ($validacion as $key => $value) {
            $iduser             = $value->idusuario;
            $estadoCodigo       = $value->estado;
            $estado_liquidacion = $value->estado_liquidacion;
            $prueba_diagnostica = $value->prueba_diagnostica;
            $quitar_de_reporte  = $value->quitar_de_reporte;
        }
        //para obtener los datos del estudiante para abrir el ticket
        $datosEstudiante = DB::SELECT("SELECT CONCAT(e.nombres,' ', e.apellidos)
            as estudiante ,e.name_usuario,e.cedula,e.idusuario, i.nombreInstitucion
            FROM usuario e, institucion i
            WHERE e.id_group = '4'
            AND e.idusuario = $request->idusuario
            AND e.institucion_idInstitucion = i.idInstitucion
        ");
        //para ver cuantos tickets abiertos tiene el usuario
        $cantidadTicketOpen = DB::SELECT("SELECT t.* FROM tickets t
         WHERE t.usuario_id = $request->idusuario
         AND t.estado = '1'
         AND t.ticket_asesor = '0'
        ");
        $realizarTicket = "no";
        if(empty($cantidadTicketOpen)){
            $realizarTicket = "ok";
        }
        //EL CODIGO NO EXISTE
        if(empty($validacion)){
            $data = [
                'status'            => '2',
                'codigo'            => $request->codigo,
                'institucion'       => $request->id_institucion,
                'usuario'           => $request->idusuario,
                'datosEstudiante'   => $datosEstudiante,
                'realizarTicket'    => $realizarTicket,
            ];
            return $data;
        }
        //validar que el codigo no sea de diagnostico
        if($prueba_diagnostica == 1){
            return ["status" => '5' ,"message" => "El código que esta ingresando, es una Prueba Diagnóstica, no tiene libro digital."];
        }
        //para mandar los codigos que esten bloqueados
        else if($estadoCodigo == '2'){
            $data = [
                'status'            => '3',
                'codigo'            => $request->codigo,
                'institucion'       => $request->id_institucion,
                'usuario'           => $request->idusuario,
                'datosEstudiante'   => $datosEstudiante,
                'realizarTicket'    => $realizarTicket,
            ];
            return $data;
        }
        //para mandar los codigos que esten devueltos
        else if(($estado_liquidacion == '3' || $estado_liquidacion == '4') && $quitar_de_reporte == '0'){
            //si esta devuelto por yaneth  no mandar ticket
            $data = [
                'status'            => '4',
                'codigo'            => $request->codigo,
                'institucion'       => $request->id_institucion,
                'usuario'           => $request->idusuario,
                'datosEstudiante'   => $datosEstudiante,
                'realizarTicket'    => $realizarTicket,
            ];
            return $data;
        }
        else{
            $institucion = $request->id_institucion;
            if(empty($iduser) || $iduser == 0 || $iduser == NULL ){
                ///Para buscar el periodo
                $verificarperiodoinstitucion = DB::table('periodoescolar_has_institucion')
                ->select('periodoescolar_has_institucion.periodoescolar_idperiodoescolar')
                ->where('periodoescolar_has_institucion.institucion_idInstitucion','=',$request->id_institucion)
                ->get();
                foreach($verificarperiodoinstitucion  as $clave=>$item){
                    $verificarperiodos =DB::SELECT("SELECT p.idperiodoescolar
                    FROM periodoescolar p
                    WHERE p.estado = '1'
                    and p.idperiodoescolar = $item->periodoescolar_idperiodoescolar
                    ");
                }
                if(count($verificarperiodoinstitucion) <=0){
                    return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];
                }
                    //verificar que el periodo exista
                if(count($verificarperiodos) <= 0){
                    return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];
                }
                //fin de busqueda del periodo
                //almancenar el periodo
                $periodo =  $verificarperiodos[0]->idperiodoescolar;
                DB::INSERT("INSERT INTO hist_codlibros(id_usuario, codigo_libro, idInstitucion, usuario_editor, observacion,id_periodo) VALUES ($request->idusuario, '$request->codigo', $request->idusuario, $request->id_institucion, 'registrado',$periodo)");
                $contenido = CodigosLibros::find($request->codigo)->update(
                    [
                        'idusuario' => $request->idusuario,
                        'id_periodo' => $periodo
                    ]
                );
                $data = [
                    'status' => '1',
                ];
                return $data;
            }else{
                $data = [
                    'status' => '0',
                    'codigo' => $request->codigo,
                    'institucion' => $request->id_institucion,
                    'usuario' => $request->idusuario,
                    'datosEstudiante' => $datosEstudiante,
                    'realizarTicket' => $realizarTicket,
                ];
                return $data;
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $codigos_libros = DB::SELECT("SELECT * from codigoslibros WHERE libro = '$id'");
        return $codigos_libros;
    }



    public function codigosLibrosFecha($datos)
    {
        $data = explode("*", $datos);

        if( $data[0] != "" ){
            $libro = $data[0];
            $fecha = $data[1];

            $codigos_libros = DB::SELECT("SELECT * from codigoslibros WHERE libro = '$libro' AND created_at like '$fecha%' ORDER BY `codigoslibros`.`fecha_create` ASC");

            return $codigos_libros;

        }else{
            return 0;
        }

    }


    public function librosBuscar(){
        $codigos_libros = DB::SELECT("SELECT id_libro_serie as id, nombre as label from libros_series");
        return $codigos_libros;
    }




    public function codigosLibrosExportados($data){
        $datos = explode("*", $data);
        $usuario = $datos[0];
        $cantidad = $datos[1];

        $codigos_libros = DB::SELECT("SELECT * from codigoslibros WHERE idusuario = '$usuario' ORDER BY fecha_create DESC LIMIT $cantidad");

        return $codigos_libros;
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    //api:get/getEstudianteCodigos
    public function getEstudianteCodigos($data){
        $datos = explode("*", $data);
        $periodo     = $datos[0];
        $institucion = $datos[1];
        $query = DB::SELECT("SELECT c.idusuario,
        CONCAT(u.nombres,' ',u.apellidos) AS estudiante,
          c.codigo,l.nombrelibro
         FROM codigoslibros c
        LEFT JOIN usuario u ON c.idusuario = u.idusuario
        LEFT JOIN libro l ON l.idlibro = c.libro_idlibro
        WHERE u.institucion_idInstitucion ='$institucion'
        AND c.id_periodo = '$periodo'
        AND c.estado <> '2'
        ");
        return $query;
    }

    public function codigos_from_historico($codigo)  {
        $dato = DB::table('hist_codlibros as hc')
        ->leftjoin('usuario as u','hc.idInstitucion','=','u.idusuario')
        ->leftjoin('institucion as i','hc.usuario_editor','=','i.idInstitucion')
        ->leftJoin('periodoescolar as p','hc.id_periodo','=','p.idperiodoescolar')
        ->where('hc.codigo_libro','like','%'.$codigo.'%')
        ->select('hc.*','u.idusuario','u.nombres','u.apellidos','i.nombreInstitucion','p.periodoescolar')
        ->get();
        return $dato;
    }


    public function validarcodigo(Request $request)
    {
        $validacion = DB::SELECT("SELECT *
        FROM  codigoslibros WHERE
        codigo = ?",["$request->codigo"]);
        $iduser = '';
        foreach ($validacion as $key => $value) {
            $iduser             = $value->idusuario;
            $estadoCodigo       = $value->estado;
            $estado_liquidacion = $value->estado_liquidacion;
            $prueba_diagnostica = $value->prueba_diagnostica;
            $quitar_de_reporte  = $value->quitar_de_reporte;
        }
        //para obtener los datos del estudiante para abrir el ticket
        $datosEstudiante = DB::SELECT("SELECT CONCAT(e.nombres,' ', e.apellidos)
            as estudiante ,e.name_usuario,e.cedula,e.idusuario, i.nombreInstitucion
            FROM usuario e, institucion i
            WHERE e.id_group = '4'
            AND e.idusuario = $request->idusuario
            AND e.institucion_idInstitucion = i.idInstitucion
        ");
        //para ver cuantos tickets abiertos tiene el usuario
        $cantidadTicketOpen = DB::SELECT("SELECT t.* FROM tickets t
         WHERE t.usuario_id = $request->idusuario
         AND t.estado = '1'
         AND t.ticket_asesor = '0'
        ");
        $realizarTicket = "no";
        if(empty($cantidadTicketOpen)){
            $realizarTicket = "ok";
        }
        //EL CODIGO NO EXISTE
        if(empty($validacion)){
            $data = [
                'status'            => '2',
                'codigo'            => $request->codigo,
                'institucion'       => $request->id_institucion,
                'usuario'           => $request->idusuario,
                'datosEstudiante'   => $datosEstudiante,
                'realizarTicket'    => $realizarTicket,
            ];
            return $data;
        }
        //validar que el codigo no sea de diagnostico
        if($prueba_diagnostica == 1){
            return ["status" => '5' ,"message" => "El código que esta ingresando, es una Prueba Diagnóstica, no tiene libro digital."];
        }
        //para mandar los codigos que esten bloqueados
        else if($estadoCodigo == '2'){
            $data = [
                'status'            => '3',
                'codigo'            => $request->codigo,
                'institucion'       => $request->id_institucion,
                'usuario'           => $request->idusuario,
                'datosEstudiante'   => $datosEstudiante,
                'realizarTicket'    => $realizarTicket,
            ];
            return $data;
        }
        //para mandar los codigos que esten devueltos
        else if(($estado_liquidacion == '3' || $estado_liquidacion == '4') && $quitar_de_reporte == '0'){
            //si esta devuelto por yaneth  no mandar ticket
            $data = [
                'status'            => '4',
                'codigo'            => $request->codigo,
                'institucion'       => $request->id_institucion,
                'usuario'           => $request->idusuario,
                'datosEstudiante'   => $datosEstudiante,
                'realizarTicket'    => $realizarTicket,
            ];
            return $data;
        }
        else{
            $institucion = $request->id_institucion;
            if(empty($iduser) || $iduser == 0 || $iduser == NULL ){
                ///Para buscar el periodo
                $verificarperiodoinstitucion = DB::table('periodoescolar_has_institucion')
                ->select('periodoescolar_has_institucion.periodoescolar_idperiodoescolar')
                ->where('periodoescolar_has_institucion.institucion_idInstitucion','=',$request->id_institucion)
                ->get();
                foreach($verificarperiodoinstitucion  as $clave=>$item){
                    $verificarperiodos =DB::SELECT("SELECT p.idperiodoescolar
                    FROM periodoescolar p
                    WHERE p.estado = '1'
                    and p.idperiodoescolar = $item->periodoescolar_idperiodoescolar
                    ");
                }
                if(count($verificarperiodoinstitucion) <=0){
                    return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];
                }
                    //verificar que el periodo exista
                if(count($verificarperiodos) <= 0){
                    return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];
                }
                //fin de busqueda del periodo
                //almancenar el periodo
                $periodo =  $verificarperiodos[0]->idperiodoescolar;
                DB::INSERT("INSERT INTO hist_codlibros(id_usuario, codigo_libro, idInstitucion, usuario_editor, observacion,id_periodo) VALUES ($request->idusuario, '$request->codigo', $request->idusuario, $request->id_institucion, 'registrado',$periodo)");
                $contenido = CodigosLibros::find($request->codigo)->update(
                    [
                        'idusuario' => $request->idusuario,
                        'id_periodo' => $periodo
                    ]
                );
                $data = [
                    'status' => '1',
                ];
                return $data;
            }else{
                $data = [
                    'status' => '0',
                    'codigo' => $request->codigo,
                    'institucion' => $request->id_institucion,
                    'usuario' => $request->idusuario,
                    'datosEstudiante' => $datosEstudiante,
                    'realizarTicket' => $realizarTicket,
                ];
                return $data;
            }
        }
    }

    //INICIO METODOS JEYSON
    public function Regalado_NoLiquidado_Temporada(Request $request)
    {
        try {
            $periodo = $request->PeriodoSelect;
            $query = DB::select('SELECT
                    c.codigo_proforma,
                    c.proforma_empresa,
                    c.libro_idlibro,
                    c.combo,
                    c.plus,
                    COUNT(*) as cantidad_regalados_leidos
                FROM codigoslibros c
                WHERE c.bc_periodo = ?
                    -- Regalado
                    AND c.estado_liquidacion = 2
                    -- Solo codigos sin prueba diagnostica
                    AND c.prueba_diagnostica = 0
                    -- Que tenga documento
                    AND c.codigo_proforma IS NOT NULL
                    -- Que sea regalado y no esten liquidados
                    -- AND c.liquidado_regalado = 0
                GROUP BY c.codigo_proforma, c.proforma_empresa, c.libro_idlibro, c.combo, c.plus
                ORDER BY c.libro_idlibro, c.codigo_proforma, c.proforma_empresa, c.combo, c.plus',
                [$periodo]
            );
            // Recorremos cada resultado para agregar nombre, codigo_liquidacion e idlibro real
            foreach ($query as &$registro) {
                if ($registro->plus == 1) {
                    // Paso 1: Buscar id_libro_plus desde libro original
                    $libro_original = DB::table('libros_series')
                        ->select('id_libro_plus')
                        ->where('idLibro', $registro->libro_idlibro)
                        ->first();
                    if ($libro_original && $libro_original->id_libro_plus) {
                        // Paso 2: Buscar datos del libro plus
                        $libro_plus = DB::table('libros_series')
                            ->select('idLibro', 'nombre', 'codigo_liquidacion')
                            ->where('idLibro', $libro_original->id_libro_plus)
                            ->first();
                        // Si existe el libro plus, asignamos
                        if ($libro_plus) {
                            $registro->idLibro_normal_plus = $libro_plus->idLibro;
                            $registro->nombre = $libro_plus->nombre;
                            $registro->codigo_liquidacion = $libro_plus->codigo_liquidacion;
                            continue;
                        }
                    }
                    // Si no se encuentra el libro plus, colocamos valores por defecto
                    $registro->idLibro_normal_plus = null;
                    $registro->nombre = null;
                    $registro->codigo_liquidacion = null;
                } else {
                    // Caso plus == 0, libro normal
                    $libro = DB::table('libros_series')
                        ->select('idLibro', 'nombre', 'codigo_liquidacion')
                        ->where('idLibro', $registro->libro_idlibro)
                        ->first();
                    if ($libro) {
                        $registro->idLibro_normal_plus = $libro->idLibro;
                        $registro->nombre = $libro->nombre;
                        $registro->codigo_liquidacion = $libro->codigo_liquidacion;
                    } else {
                        $registro->idLibro_normal_plus = null;
                        $registro->nombre = null;
                        $registro->codigo_liquidacion = null;
                    }
                }
            }
            return response()->json([
                'status' => 1,
                'data' => $query
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Ocurrió un error al obtener los datos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function Regalado_Leido_Temporada(Request $request)
    {
        try {
            $periodo = $request->PeriodoSelect;
            $query = DB::select('SELECT
                    c.codigo_proforma,
                    c.proforma_empresa,
                    c.libro_idlibro,
                    c.combo,
                    c.plus,
                    COUNT(*) as cantidad_regalado_leido
                FROM codigoslibros c
                WHERE c.bc_periodo = ?
                    -- Regalado
                    AND c.estado_liquidacion = 2
                    -- Solo codigos sin prueba diagnostica
                    AND c.prueba_diagnostica = 0
                    -- Que tenga documento
                    AND c.codigo_proforma IS NOT NULL
                    -- Que sea regalado y esten liquidados
                    AND c.bc_estado = 2
                GROUP BY c.codigo_proforma, c.proforma_empresa, c.libro_idlibro, c.combo, c.plus
                ORDER BY c.libro_idlibro, c.codigo_proforma, c.proforma_empresa, c.combo, c.plus',
                [$periodo]
            );
            // Recorremos cada resultado para agregar nombre, codigo_liquidacion e idlibro real
            foreach ($query as &$registro) {
                if ($registro->plus == 1) {
                    // Paso 1: Buscar id_libro_plus desde libro original
                    $libro_original = DB::table('libros_series')
                        ->select('id_libro_plus')
                        ->where('idLibro', $registro->libro_idlibro)
                        ->first();
                    if ($libro_original && $libro_original->id_libro_plus) {
                        // Paso 2: Buscar datos del libro plus
                        $libro_plus = DB::table('libros_series')
                            ->select('idLibro', 'nombre', 'codigo_liquidacion')
                            ->where('idLibro', $libro_original->id_libro_plus)
                            ->first();
                        // Si existe el libro plus, asignamos
                        if ($libro_plus) {
                            $registro->idLibro_normal_plus = $libro_plus->idLibro;
                            $registro->nombre = $libro_plus->nombre;
                            $registro->codigo_liquidacion = $libro_plus->codigo_liquidacion;
                            continue;
                        }
                    }
                    // Si no se encuentra el libro plus, colocamos valores por defecto
                    $registro->idLibro_normal_plus = null;
                    $registro->nombre = null;
                    $registro->codigo_liquidacion = null;
                } else {
                    // Caso plus == 0, libro normal
                    $libro = DB::table('libros_series')
                        ->select('idLibro', 'nombre', 'codigo_liquidacion')
                        ->where('idLibro', $registro->libro_idlibro)
                        ->first();
                    if ($libro) {
                        $registro->idLibro_normal_plus = $libro->idLibro;
                        $registro->nombre = $libro->nombre;
                        $registro->codigo_liquidacion = $libro->codigo_liquidacion;
                    } else {
                        $registro->idLibro_normal_plus = null;
                        $registro->nombre = null;
                        $registro->codigo_liquidacion = null;
                    }
                }
            }
            return response()->json([
                'status' => 1,
                'data' => $query
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Ocurrió un error al obtener los datos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function Regalado_Liquidado_Temporada(Request $request)
    {
        try {
            $periodo = $request->PeriodoSelect;
            $query = DB::select('SELECT
                    c.codigo_proforma,
                    c.proforma_empresa,
                    c.libro_idlibro,
                    c.combo,
                    c.plus,
                    COUNT(*) as cantidad_regalados_liquidados
                FROM codigoslibros c
                WHERE c.bc_periodo = ?
                    -- Regalado
                    AND c.estado_liquidacion = 2
                    -- Solo codigos sin prueba diagnostica
                    AND c.prueba_diagnostica = 0
                    -- Que tenga documento
                    AND c.codigo_proforma IS NOT NULL
                    -- Que sea regalado y esten liquidados
                    AND c.liquidado_regalado = 1
                GROUP BY c.codigo_proforma, c.proforma_empresa, c.libro_idlibro, c.combo, c.plus
                ORDER BY c.libro_idlibro, c.codigo_proforma, c.proforma_empresa, c.combo, c.plus',
                [$periodo]
            );
            // Recorremos cada resultado para agregar nombre, codigo_liquidacion e idlibro real
            foreach ($query as &$registro) {
                if ($registro->plus == 1) {
                    // Paso 1: Buscar id_libro_plus desde libro original
                    $libro_original = DB::table('libros_series')
                        ->select('id_libro_plus')
                        ->where('idLibro', $registro->libro_idlibro)
                        ->first();
                    if ($libro_original && $libro_original->id_libro_plus) {
                        // Paso 2: Buscar datos del libro plus
                        $libro_plus = DB::table('libros_series')
                            ->select('idLibro', 'nombre', 'codigo_liquidacion')
                            ->where('idLibro', $libro_original->id_libro_plus)
                            ->first();
                        // Si existe el libro plus, asignamos
                        if ($libro_plus) {
                            $registro->idLibro_normal_plus = $libro_plus->idLibro;
                            $registro->nombre = $libro_plus->nombre;
                            $registro->codigo_liquidacion = $libro_plus->codigo_liquidacion;
                            continue;
                        }
                    }
                    // Si no se encuentra el libro plus, colocamos valores por defecto
                    $registro->idLibro_normal_plus = null;
                    $registro->nombre = null;
                    $registro->codigo_liquidacion = null;
                } else {
                    // Caso plus == 0, libro normal
                    $libro = DB::table('libros_series')
                        ->select('idLibro', 'nombre', 'codigo_liquidacion')
                        ->where('idLibro', $registro->libro_idlibro)
                        ->first();
                    if ($libro) {
                        $registro->idLibro_normal_plus = $libro->idLibro;
                        $registro->nombre = $libro->nombre;
                        $registro->codigo_liquidacion = $libro->codigo_liquidacion;
                    } else {
                        $registro->idLibro_normal_plus = null;
                        $registro->nombre = null;
                        $registro->codigo_liquidacion = null;
                    }
                }
            }
            return response()->json([
                'status' => 1,
                'data' => $query
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Ocurrió un error al obtener los datos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function Regalado_Devuelto_Temporada(Request $request)
    {
        try {
            $periodo = $request->PeriodoSelect;
            $query = DB::select('SELECT
                    s.documento,
                    s.id_empresa,
                    s.id_libro,
                    s.combo,
                    s.plus,
                    COUNT(*) AS cantidad_devueltos
                FROM codigoslibros_devolucion_son s
                LEFT JOIN codigoslibros_devolucion_header h ON h.id = s.codigoslibros_devolucion_id
                WHERE s.id_periodo = ?
                    -- Código normal
                    AND s.tipo_codigo = 0
                    -- Diferente de creado
                    AND s.estado <> 0
                    -- Regalado
                    AND s.documento_estado_liquidacion = 2
                    -- Que tenga documento
                    AND s.documento IS NOT NULL
                    -- Diferente de anulado
                    AND h.estado <> "3"
                GROUP BY s.documento, s.id_empresa, s.id_libro, s.combo, s.plus
                ORDER BY s.id_libro, s.documento, s.id_empresa, s.combo, s.plus',
                [$periodo]
            );
            // Obtener todos los id_libro únicos
            $ids_libros = collect($query)->pluck('id_libro')->unique();
            // Obtener datos de esos libros en una sola consulta
            $libros_info = DB::table('libros_series')
                ->select('idLibro', 'nombre', 'codigo_liquidacion')
                ->whereIn('idLibro', $ids_libros)
                ->get()
                ->keyBy('idLibro');
            // Añadir info a cada registro
            foreach ($query as &$registro) {
                if (isset($libros_info[$registro->id_libro])) {
                    $registro->idLibro_normal_plus = $libros_info[$registro->id_libro]->idLibro;
                    $registro->nombre = $libros_info[$registro->id_libro]->nombre;
                    $registro->codigo_liquidacion = $libros_info[$registro->id_libro]->codigo_liquidacion;
                } else {
                    $registro->idLibro_normal_plus = 0;
                    $registro->nombre = '';
                    $registro->codigo_liquidacion = '';
                }
            }
            return response()->json([
                'status' => 1,
                'data' => $query
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Ocurrió un error al obtener los datos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function Regalado_DevueltoyLiquidado_Temporada(Request $request)
    {
        try {
            $periodo = $request->PeriodoSelect;
            $query = DB::select('SELECT
                    s.documento,
                    s.id_empresa,
                    s.id_libro,
                    s.combo,
                    s.plus,
                    COUNT(*) AS cantidad_devueltosliquidados
                FROM codigoslibros_devolucion_son s
                LEFT JOIN codigoslibros_devolucion_header h ON h.id = s.codigoslibros_devolucion_id
                WHERE s.id_periodo = ?
                    -- Código normal
                    AND s.tipo_codigo = 0
                    -- Diferente de creado
                    AND s.estado <> 0
                    -- Regalado
                    AND s.documento_estado_liquidacion = 2
                    -- Regalado y liquidado
                    AND s.documento_regalado_liquidado = 1
                    -- Que tenga documento
                    AND s.documento IS NOT NULL
                    -- Diferente de anulado
                    AND h.estado <> "3"
                GROUP BY s.documento, s.id_empresa, s.id_libro, s.combo, s.plus
                ORDER BY s.id_libro, s.documento, s.id_empresa, s.combo, s.plus',
                [$periodo]
            );
            // Obtener todos los id_libro únicos
            $ids_libros = collect($query)->pluck('id_libro')->unique();
            // Obtener datos de esos libros en una sola consulta
            $libros_info = DB::table('libros_series')
                ->select('idLibro', 'nombre', 'codigo_liquidacion')
                ->whereIn('idLibro', $ids_libros)
                ->get()
                ->keyBy('idLibro');
            // Añadir info a cada registro
            foreach ($query as &$registro) {
                if (isset($libros_info[$registro->id_libro])) {
                    $registro->idLibro_normal_plus = $libros_info[$registro->id_libro]->idLibro;
                    $registro->nombre = $libros_info[$registro->id_libro]->nombre;
                    $registro->codigo_liquidacion = $libros_info[$registro->id_libro]->codigo_liquidacion;
                } else {
                    $registro->idLibro_normal_plus = 0;
                    $registro->nombre = '';
                    $registro->codigo_liquidacion = '';
                }
            }
            return response()->json([
                'status' => 1,
                'data' => $query
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Ocurrió un error al obtener los datos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Agrupar_Regalados_Devueltos_Temporada(Request $request)
    {
        $devueltos = $request->devueltos;
        $devueltos_liquidados = $request->devueltos_liquidados;
        $agrupados = [];
        // Procesar los devueltos
        foreach ($devueltos as $item) {
            $key = $item['id_empresa'] . '_' . $item['documento'] . '_' . $item['id_libro'] . '_' . $item['combo'] . '_' . $item['plus'];
            $agrupados[$key] = [
                'id_empresa' => $item['id_empresa'],
                'documento' => $item['documento'],
                'id_libro' => $item['id_libro'],
                'combo' => $item['combo'],
                'plus' => $item['plus'],
                'idLibro_normal_plus' => $item['idLibro_normal_plus'] ?? null,
                'nombre' => $item['nombre'] ?? null,
                'codigo_liquidacion' => $item['codigo_liquidacion'] ?? null,
                'cantidad_devueltos_total' => $item['cantidad_devueltos']
            ];
        }
        // Procesar los devueltos liquidados
        foreach ($devueltos_liquidados as $item) {
            $key = $item['id_empresa'] . '_' . $item['documento'] . '_' . $item['id_libro'] . '_' . $item['combo'] . '_' . $item['plus'];
            if (isset($agrupados[$key])) {
                $agrupados[$key]['cantidad_devueltos_total'] += $item['cantidad_devueltosliquidados'];
            } else {
                $agrupados[$key] = [
                    'id_empresa' => $item['id_empresa'],
                    'documento' => $item['documento'],
                    'id_libro' => $item['id_libro'],
                    'combo' => $item['combo'],
                    'plus' => $item['plus'],
                    'idLibro_normal_plus' => $item['idLibro_normal_plus'] ?? null,
                    'nombre' => $item['nombre'] ?? null,
                    'codigo_liquidacion' => $item['codigo_liquidacion'] ?? null,
                    'cantidad_devueltos_total' => $item['cantidad_devueltosliquidados']
                ];
            }
        }
        return response()->json([
            'status' => 1,
            'data' => array_values($agrupados)
        ]);
    }
    public function Agrupar_Regalados_Temporada(Request $request)
    {
        $no_liquidados = $request->no_liquidados;
        $liquidados = $request->liquidados;
        $devueltos = $request->devueltos;
        $leidos = $request->leidos;
        $agrupado = [];
        // 🔹 Unificar NO liquidados DESPACHADOS
        foreach ($no_liquidados as $item) {
            $empresa = $item['proforma_empresa'];
            $key = $empresa . '_' . $item['codigo_proforma'] . '_' . $item['idLibro_normal_plus'] . '_' . $item['combo'] . '_' . $item['plus'];
            $agrupado[$key] = [
                'empresa_id' => $empresa,
                'codigo_proforma' => $item['codigo_proforma'],
                'idLibro_normal_plus' => $item['idLibro_normal_plus'],
                'nombre' => $item['nombre'] ?? null,
                'codigo_liquidacion' => $item['codigo_liquidacion'] ?? null,
                'combo' => $item['combo'],
                'plus' => $item['plus'],
                'cantidad_despachados' => $item['cantidad_regalados_leidos'],
                'cantidad_liquidados' => 0,
                'cantidad_devueltos_total' => 0,
                'cantidad_leidos' => 0
            ];
        }
        // 🔹 Unificar LIQUIDADOS
        foreach ($liquidados as $item) {
            $empresa = $item['proforma_empresa'];
            $key = $empresa . '_' . $item['codigo_proforma'] . '_' . $item['idLibro_normal_plus'] . '_' . $item['combo'] . '_' . $item['plus'];
            if (!isset($agrupado[$key])) {
                $agrupado[$key] = [
                    'empresa_id' => $empresa,
                    'codigo_proforma' => $item['codigo_proforma'],
                    'idLibro_normal_plus' => $item['idLibro_normal_plus'],
                    'nombre' => $item['nombre'] ?? null,
                    'codigo_liquidacion' => $item['codigo_liquidacion'] ?? null,
                    'combo' => $item['combo'],
                    'plus' => $item['plus'],
                    'cantidad_despachados' => 0,
                    'cantidad_liquidados' => $item['cantidad_regalados_liquidados'],
                    'cantidad_devueltos_total' => 0,
                    'cantidad_leidos' => 0
                ];
            } else {
                $agrupado[$key]['cantidad_liquidados'] = $item['cantidad_regalados_liquidados'];
                // 🛠 Validar consistencia de nombre y código_liquidacion
                if (
                    $agrupado[$key]['nombre'] !== ($item['nombre'] ?? null) ||
                    $agrupado[$key]['codigo_liquidacion'] !== ($item['codigo_liquidacion'] ?? null)
                ) {
                    \Log::warning("Inconsistencia detectada en nombre/código_liquidacion para key: $key");
                }
            }
        }

        // 🔹 Unificar DEVUELTOS
        foreach ($devueltos as $item) {
            $empresa = $item['id_empresa'];
            $key = $empresa . '_' . $item['documento'] . '_' . $item['idLibro_normal_plus'] . '_' . $item['combo'] . '_' . $item['plus'];
            if (isset($agrupado[$key])) {
                $agrupado[$key]['cantidad_devueltos_total'] = $item['cantidad_devueltos_total'];
            } else {
                $agrupado[$key] = [
                    'empresa_id' => $empresa,
                    'codigo_proforma' => $item['documento'],
                    'idLibro_normal_plus' => $item['idLibro_normal_plus'],
                    'nombre' => $item['nombre'] ?? null,
                    'codigo_liquidacion' => $item['codigo_liquidacion'] ?? null,
                    'combo' => $item['combo'],
                    'plus' => $item['plus'],
                    'cantidad_despachados' => 0,
                    'cantidad_liquidados' => 0,
                    'cantidad_devueltos_total' => $item['cantidad_devueltos_total'],
                    'cantidad_leidos' => 0
                ];
            }
        }
        // 🔹 Unificar LEÍDOS EXTRA
        foreach ($leidos as $item) {
            $empresa = $item['proforma_empresa'];
            $key = $empresa . '_' . $item['codigo_proforma'] . '_' . $item['idLibro_normal_plus'] . '_' . $item['combo'] . '_' . $item['plus'];
            if (isset($agrupado[$key])) {
                $agrupado[$key]['cantidad_leidos'] = $item['cantidad_regalado_leido'];
            } else {
                $agrupado[$key] = [
                    'empresa_id' => $empresa,
                    'codigo_proforma' => $item['codigo_proforma'],
                    'idLibro_normal_plus' => $item['idLibro_normal_plus'],
                    'nombre' => $item['nombre'] ?? null,
                    'codigo_liquidacion' => $item['codigo_liquidacion'] ?? null,
                    'combo' => $item['combo'],
                    'plus' => $item['plus'],
                    'cantidad_despachados' => 0,
                    'cantidad_liquidados' => 0,
                    'cantidad_devueltos_total' => 0,
                    'cantidad_leidos' => $item['cantidad_regalado_leido']
                ];
            }
        }
        // 🔹 Agrupar por código_proforma y empresa
        $porDocumento = [];
        foreach ($agrupado as $item) {
            $empresa_y_proforma = $item['empresa_id'] . '_' . $item['codigo_proforma'];
            if (!isset($porDocumento[$empresa_y_proforma])) {
                $porDocumento[$empresa_y_proforma] = [
                    'empresa_id' => $item['empresa_id'],
                    'codigo_proforma' => $item['codigo_proforma'],
                    'libros' => []
                ];
            }
            $porDocumento[$empresa_y_proforma]['libros'][] = [
                'idLibro_normal_plus' => $item['idLibro_normal_plus'],
                'nombre' => $item['nombre'],
                'codigo_liquidacion' => $item['codigo_liquidacion'],
                'combo' => $item['combo'],
                'plus' => $item['plus'],
                'cantidad_despachados' => $item['cantidad_despachados'],
                'cantidad_liquidados' => $item['cantidad_liquidados'],
                'cantidad_devueltos_total' => $item['cantidad_devueltos_total'],
                'cantidad_leidos' => $item['cantidad_leidos']
            ];
        }
        return response()->json([
            'status' => 1,
            'data' => array_values($porDocumento)
        ]);
    }


    // public function Agregar_Nombres_ConsultaFinalRegalados(Request $request)
    // {
    //     $documentos_agrupados = $request->documentos_agrupados;
    //     // Paso 1: Obtener todos los libro_idlibro únicos
    //     $ids_libros = [];
    //     $ids_empresas = [];
    //     foreach ($documentos_agrupados as $documento) {
    //         $ids_empresas[] = $documento['empresa_id'];
    //         foreach ($documento['libros'] as $libro) {
    //             $ids_libros[] = $libro['libro_idlibro'];
    //         }
    //     }
    //     $ids_libros = array_unique($ids_libros);
    //     $ids_empresas = array_unique($ids_empresas);
    //     // Paso 2: Obtener todos los libros de la base de datos en una sola consulta
    //     $libros_info = DB::table('libros_series')
    //         ->select('idLibro', 'nombre', 'codigo_liquidacion')
    //         ->whereIn('idLibro', $ids_libros)
    //         ->get()
    //         ->keyBy('idLibro'); // Indexa por idLibro para acceso rápido
    //     // Paso 3: Obtener info de las empresas
    //     $empresas_info = DB::table('empresas')
    //         ->select('id', 'descripcion_corta')
    //         ->whereIn('id', $ids_empresas)
    //         ->get()
    //         ->keyBy('id');
    //     // Paso 4: Recorremos y asignamos los valores
    //     foreach ($documentos_agrupados as &$documento) {
    //         // Añadir descripción corta de empresa
    //         $empresa_id = $documento['empresa_id'];
    //         $documento['empresa_nombre'] = isset($empresas_info[$empresa_id])
    //             ? $empresas_info[$empresa_id]->descripcion_corta
    //             : null;
    //         // Añadir nombre y código de los libros
    //         foreach ($documento['libros'] as &$libro) {
    //             $id = $libro['libro_idlibro'];
    //             if (isset($libros_info[$id])) {
    //                 $libro['nombre'] = $libros_info[$id]->nombre;
    //                 $libro['codigo_liquidacion'] = $libros_info[$id]->codigo_liquidacion;
    //             } else {
    //                 $libro['nombre'] = null;
    //                 $libro['codigo_liquidacion'] = null;
    //             }
    //         }
    //     }
    //     return response()->json([
    //         'status' => 1,
    //         'data' => $documentos_agrupados
    //     ]);
    // }

    public function Agregar_F_DetalleVenta_ConsultaFinalRegalados(Request $request)
    {
        $documentos = $request->documentos_con_cod_y_nombre;
        // Paso 1: Reunimos todos los posibles filtros para hacer solo una consulta
        $filtros = [];
        foreach ($documentos as $documento) {
            $empresa_id = $documento['empresa_id'];
            $codigo_proforma = $documento['codigo_proforma'];
            foreach ($documento['libros'] as $libro) {
                $pro_codigo = $libro['combo'] ?? $libro['codigo_liquidacion'];
                if ($pro_codigo) {
                    $filtros[] = [
                        'id_empresa' => $empresa_id,
                        'ven_codigo' => $codigo_proforma,
                        'pro_codigo' => $pro_codigo
                    ];
                }
            }
        }
        // Paso 2: Eliminamos duplicados
        $filtros = collect($filtros)->unique();
        // Paso 3: Consultamos todos los det_ven_cantidad que coincidan
        $query = DB::table('f_detalle_venta')
            ->select('id_empresa', 'ven_codigo', 'pro_codigo', 'det_ven_cantidad');
        foreach ($filtros as $i => $filtro) {
            if ($i === 0) {
                $query->where(function ($q) use ($filtro) {
                    $q->where('id_empresa', $filtro['id_empresa'])
                        ->where('ven_codigo', $filtro['ven_codigo'])
                        ->where('pro_codigo', $filtro['pro_codigo']);
                });
            } else {
                $query->orWhere(function ($q) use ($filtro) {
                    $q->where('id_empresa', $filtro['id_empresa'])
                        ->where('ven_codigo', $filtro['ven_codigo'])
                        ->where('pro_codigo', $filtro['pro_codigo']);
                });
            }
        }
        $resultados = $query->get();
        // Paso 4: Indexamos resultados para acceso rápido
        $mapa_cantidades = [];
        foreach ($resultados as $fila) {
            $key = "{$fila->id_empresa}|{$fila->ven_codigo}|{$fila->pro_codigo}";
            $mapa_cantidades[$key] = $fila->det_ven_cantidad;
        }
        // Paso 5: Añadimos det_ven_cantidad al JSON recibido
        foreach ($documentos as &$documento) {
            $empresa_id = $documento['empresa_id'];
            $codigo_proforma = $documento['codigo_proforma'];

            foreach ($documento['libros'] as &$libro) {
                $pro_codigo = $libro['combo'] ?? $libro['codigo_liquidacion'];
                $key = "{$empresa_id}|{$codigo_proforma}|{$pro_codigo}";

                $libro['det_ven_cantidad'] = $mapa_cantidades[$key] ?? 0;
            }
        }
        return response()->json([
            'status' => 1,
            'data' => $documentos
        ]);
    }
    //FIN METODOS JEYSON
}
