<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Provider\Image;
use Illuminate\Support\Facades\File;


class SalleReportesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

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


    // REPORTES

    public function reporte_evaluaciones_institucion($n_evaluacion)
    {
        // PROCEDIMIENTO OBTIENE EVALUACIONES AGRUPADAS POR INSTITUCION (una evaluacion solo puede pertenecer a un docente)
        // $evaluaciones = DB::SELECT("CALL `salle_reporte_evaluaciones_institucion` (?);", [$fecha]);
        // $evaluaciones = DB::SELECT("SELECT GROUP_CONCAT(CONCAT (se.id_evaluacion) ORDER BY se.id_evaluacion) AS evaluaciones,
        //     se.created_at AS fecha_evaluacion, i.idInstitucion, i.nombreInstitucion, i.ciudad_id
        //     FROM salle_evaluaciones se, usuario u, institucion i
        //     WHERE se.estado = 2
        //     AND i.idInstitucion != 1036
        //     AND se.created_at LIKE CONCAT($fecha, '%')
        //     AND se.id_usuario = u.idusuario
        //     AND u.institucion_idInstitucion = i.idInstitucion
        //     AND i.tipo_institucion = 2
        //     GROUP BY i.idInstitucion
        // ");
        $evaluaciones = DB::SELECT("SELECT
          GROUP_CONCAT(CONCAT (se.id_evaluacion) ORDER BY se.id_evaluacion) AS evaluaciones,
          se.created_at AS fecha_evaluacion, i.idInstitucion, i.nombreInstitucion, i.ciudad_id
          FROM salle_evaluaciones se
          LEFT JOIN usuario u ON se.id_usuario = u.idusuario
          LEFT JOIN institucion i ON  u.institucion_idInstitucion = i.idInstitucion
          WHERE se.estado = 2
          AND i.idInstitucion != 1036
          AND se.n_evaluacion = '$n_evaluacion'
          AND i.tipo_institucion = 2
          GROUP BY i.idInstitucion
        ");
        // dump($evaluaciones);
        if(!empty($evaluaciones)){
            foreach ($evaluaciones as $key => $value) {
                $vector_evaluaciones = explode(",", $value->evaluaciones);
                $promedio_eval_inst = 0;
                $acum_eval = 0; $acum_doc = 0;
                // dump('*********************************institucion: ' . $value->idInstitucion);
                foreach ($vector_evaluaciones as $keyE => $valueE){
                    // $respuestas = DB::SELECT("SELECT SUM(sp.puntaje_pregunta) AS puntaje
                    // FROM salle_preguntas_evaluacion spe, salle_preguntas sp
                    // WHERE spe.id_evaluacion = id_evaluacion
                    // AND spe.id_pregunta = sp.id_pregunta");
                    $puntaje_respuestas = DB::SELECT("CALL salle_puntaje_respuestas (?);",[$valueE]);
                    // se acumula los puntajes de cada evaluacion por institucion
                    $acum_eval = $acum_eval + $puntaje_respuestas[0]->puntaje;
                    // $pun_preguntas = DB::SELECT("SELECT sr.id_pregunta, sr.id_usuario,
                    // IF(
                    //     SUM(sr.puntaje)>=0
                    //     AND SUM(sr.puntaje)<=sp.puntaje_pregunta ,SUM(sr.puntaje),
                    //     (IF(sp.id_tipo_pregunta!=1, sp.puntaje_pregunta, 0))
                    //  ) AS puntaje
                    //  FROM salle_respuestas_preguntas sr, salle_preguntas sp
                    //  WHERE sr.id_evaluacion = id_evaluacion
                    //  AND sr.id_pregunta = sp.id_pregunta
                    //  GROUP by sr.id_pregunta
                    // ");
                    $puntaje_por_pregunta = DB::SELECT("CALL salle_puntaje_pregunta (?);",[$valueE]);
                    foreach ($puntaje_por_pregunta as $keyP => $valueP){
                        //puntaje obtenido por cada docente, cada evaluacion se califica por puntajes diferentes
                        $acum_doc = $acum_doc + $valueP->puntaje;
                    }
                    // dump($acum_doc);
                    // $acum_doc = 0;
                }
                // dump($calificaciones);
                $promedio_eval_inst = ( $acum_doc * 100 ) / $acum_eval;
                $promedio_eval_inst = floatval(number_format($promedio_eval_inst, 2));
                $data['items'][$key] = [
                    'idInstitucion'     => $value->idInstitucion,
                    'nombreInstitucion' => $value->nombreInstitucion,
                    'fecha_evaluacion'  => $value->fecha_evaluacion,
                    'ciudad_id'         => $value->ciudad_id,
                    'puntaje'           => $promedio_eval_inst,
                    'cant_evaluaciones' => count($vector_evaluaciones)
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }


    public function salle_promedio_areas($n_evaluacion, $institucion){
        //estado = 2; resuelta
        // $evaluaciones = DB::SELECT("CALL salle_evaluaciones_institucion ($periodo, $institucion);");
        // $evaluaciones = DB::SELECT("SELECT MAX(se.id_evaluacion) AS id_evaluacion, CONCAT(u.nombres, ' ', u.apellidos) AS nombre_docente, u.cedula, u.email FROM salle_evaluaciones se, usuario u WHERE se.estado = 2 AND se.created_at LIKE CONCAT(periodo, '%') AND se.id_usuario = u.idusuario AND u.institucion_idInstitucion = institucion GROUP BY u.idusuario
        // ");
        $evaluaciones = DB::SELECT("SELECT MAX(se.id_evaluacion) AS id_evaluacion,
            CONCAT(u.nombres, ' ', u.apellidos) AS nombre_docente, u.cedula, u.email
            FROM salle_evaluaciones se, usuario u
            WHERE se.estado = 2
            AND se.n_evaluacion = '$n_evaluacion'
            AND se.id_usuario = u.idusuario
            AND u.institucion_idInstitucion = '$institucion'
            GROUP BY u.idusuario
       ");
        $data_evaluaciones = array();
        foreach ($evaluaciones as $key => $value) {
            // areas de cada evaluacion
            // $areas = DB::SELECT("SELECT sar.id_area, sar.nombre_area
            //     FROM salle_evaluaciones se, salle_preguntas_evaluacion spe, salle_preguntas sp, salle_asignaturas sa,
            //     salle_areas sar
            //     WHERE se.id_evaluacion = '$value->id_evaluacion'
            //     AND se.id_evaluacion = spe.id_evaluacion
            //     AND spe.id_pregunta = sp.id_pregunta
            //     AND sp.id_asignatura = sa.id_asignatura
            //     AND sa.id_area = sar.id_area
            //     GROUP BY sar.id_area
            // ");
            // return $areas;
            $areas = DB::SELECT("CALL salle_areas_evaluacion (?);",[$value->id_evaluacion]);
            $data_areas = array(); $promedio_eval_area_acum = 0;
            foreach ($areas as $keyR => $valueR){
                $calif_area_eval = 0; $calif_area_doc = 0; $promedio_eval_area = 0;
                // puntaje de la evaluacion por area
                // $puntaje_areas = DB::SELECT("SELECT sa.id_area, sar.nombre_area, SUM(sp.puntaje_pregunta) AS puntaje,
                //  COUNT(sp.id_pregunta) AS cant_preguntas
                //  FROM salle_preguntas_evaluacion spe, salle_preguntas sp, salle_asignaturas sa, salle_areas sar
                //   WHERE spe.id_evaluacion = '$value->id_evaluacion'
                //   AND spe.id_pregunta = sp.id_pregunta
                //   AND sp.id_asignatura = sa.id_asignatura
                //   AND sa.id_area = sar.id_area AND sar.id_area = '$valueR->id_area'
                // ");
                $puntaje_areas = DB::SELECT("CALL salle_puntaje_evaluacion_areas (?, ?)",[$value->id_evaluacion, $valueR->id_area]);
                $calif_area_eval = $puntaje_areas[0]->puntaje;
                // $puntaje_por_pregunta = DB::SELECT("SELECT sr.id_pregunta, sr.id_usuario,
                //  IF(SUM(sr.puntaje)>=0 AND SUM(sr.puntaje)<=sp.puntaje_pregunta,SUM(sr.puntaje),
                //  (IF(sp.id_tipo_pregunta!=1, sp.puntaje_pregunta, 0))) AS puntaje
                //   FROM salle_respuestas_preguntas sr, salle_preguntas sp, salle_asignaturas sa
                //   WHERE sr.id_evaluacion = '$value->id_evaluacion'
                //   AND sr.id_pregunta = sp.id_pregunta
                //   AND sp.id_asignatura = sa.id_asignatura
                //   AND sa.id_area = '28'
                //   GROUP BY sr.id_pregunta
                // ");
                $puntaje_por_pregunta = DB::SELECT("CALL salle_puntaje_area (?, ?);",[$value->id_evaluacion, $valueR->id_area]);
                foreach ($puntaje_por_pregunta as $keyP => $valueP){
                    //puntaje obtenido de cada docente por area
                    $calif_area_doc = $calif_area_doc + $valueP->puntaje;
                }
                if( $calif_area_doc <= 0 ){ $promedio_eval_area = 0; }
                else{ $promedio_eval_area = ( $calif_area_doc * 100 ) / $calif_area_eval; }

                if( $promedio_eval_area > 100 ){ $promedio_eval_area = 100; }

                $data_areas[$keyR] = [
                    'id_area' => $puntaje_areas[0]->id_area,
                    'nombre_area' => $puntaje_areas[0]->nombre_area,
                    'puntaje' => floatval(number_format($promedio_eval_area, 2)),
                    'cant_preguntas' => $puntaje_areas[0]->cant_preguntas
                ];

                $promedio_eval_area_acum += $promedio_eval_area;
            }

            if( count($areas) > 0 ){
                $puntaje_evaluacion = $promedio_eval_area_acum / count($areas);
            }else{
                $puntaje_evaluacion = 0;
            }

            $data_evaluaciones['items'][$key] = [
                'id_evaluacion' => $value->id_evaluacion,
                'puntaje_evaluacion' => floatval(number_format($puntaje_evaluacion, 2)),
                'nombre_docente' => $value->nombre_docente,
                'areas' => $data_areas
            ];
        }
        // esta data devuelve los promedios por areas de cada evaluacion, se debe procesar en el front
        return $data_evaluaciones;
    }



    public function salle_promedio_asignatura($periodo, $institucion, $id_area){
        // $evaluaciones = DB::SELECT("CALL salle_evaluaciones_institucion_area ($periodo, $institucion, $id_area);");
        // $evaluaciones = DB::SELECT("SELECT MAX(se.id_evaluacion) AS id_evaluacion,
        // CONCAT(u.nombres, ' ', u.apellidos) AS nombre_docente, u.cedula, u.email
        // FROM salle_evaluaciones se, usuario u, salle_preguntas_evaluacion spe,
        // salle_preguntas sp, salle_asignaturas sa
        // WHERE se.estado = 2
        // AND se.created_at LIKE CONCAT(periodo, '%')
        // AND se.id_usuario = u.idusuario
        // AND u.institucion_idInstitucion = '$institucion'
        // AND se.id_evaluacion = spe.id_evaluacion
        // AND spe.id_pregunta = sp.id_pregunta
        // AND sp.id_asignatura = sa.id_asignatura
        // AND sa.id_area = id_area
        // GROUP BY u.idusuario
        // ");
        $evaluaciones = DB::SELECT("SELECT MAX(se.id_evaluacion) AS id_evaluacion,
        CONCAT(u.nombres, ' ', u.apellidos) AS nombre_docente, u.cedula, u.email
        FROM salle_evaluaciones se, usuario u, salle_preguntas_evaluacion spe,
        salle_preguntas sp, salle_asignaturas sa
        WHERE se.estado = 2
        AND se.n_evaluacion = '$periodo'
        AND se.id_usuario = u.idusuario
        AND u.institucion_idInstitucion = '$institucion'
        AND se.id_evaluacion = spe.id_evaluacion
        AND spe.id_pregunta = sp.id_pregunta
        AND sp.id_asignatura = sa.id_asignatura
        AND sa.id_area = id_area
        GROUP BY u.idusuario
        ");
        $data_evaluaciones = array();
        foreach ($evaluaciones as $key => $value) {
            // asignaturas de cada evaluacion
            // $asignaturas = DB::SELECT("SELECT sa.id_asignatura, sa.nombre_asignatura
            // FROM salle_evaluaciones se, salle_preguntas_evaluacion sr, salle_preguntas sp, salle_asignaturas sa
            // WHERE se.id_evaluacion = '$value->id_evaluacion'
            // AND se.id_evaluacion = sr.id_evaluacion
            // AND sr.id_pregunta = sp.id_pregunta
            // AND sp.id_asignatura =sa.id_asignatura
            // AND sa.id_area = '$id_area'
            // GROUP BY sa.id_asignatura
            // ");
            $asignaturas = DB::SELECT("CALL salle_asignaturas_evaluacion (?, ?);",[$value->id_evaluacion, $id_area]);
            $data_asignaturas = array(); $promedio_eval_asig_acum = 0;
            foreach ($asignaturas as $keyA => $valueA){
                $calif_asig_eval = 0; $calif_asig_doc = 0; $promedio_eval_asig = 0; $promedio_eval_asignatura = 0;
                // puntaje de la evaluacion por asignatura y cantidad de preguntas por asignatura
                // $puntaje_asignaturas = DB::SELECT("SELECT sa.id_asignatura, sa.nombre_asignatura,
                // SUM(sp.puntaje_pregunta) AS puntaje, COUNT(sp.id_pregunta) AS cant_preguntas
                //     FROM salle_preguntas_evaluacion spe, salle_preguntas sp, salle_asignaturas sa
                //     WHERE spe.id_evaluacion = '$value->id_evaluacion'
                //     AND spe.id_pregunta     = sp.id_pregunta
                //     AND sp.id_asignatura    = '$valueA->id_asignatura'
                //     AND sp.id_asignatura    = sa.id_asignatura
                // ");
                $puntaje_asignaturas = DB::SELECT("CALL salle_puntaje_evaluacion_asignaturas (?, ?);",[$value->id_evaluacion, $valueA->id_asignatura]);
                $calif_asig_eval = $puntaje_asignaturas[0]->puntaje;
                //obtener los puntajes por cada  pregunta
                // $puntaje_por_pregunta = DB::SELECT("SELECT sr.id_pregunta, sr.id_usuario,
                //     IF(SUM(sr.puntaje)>=0
                //     AND SUM(sr.puntaje)<=sp.puntaje_pregunta,SUM(sr.puntaje),
                //     (IF(sp.id_tipo_pregunta!=1, sp.puntaje_pregunta, 0))) AS puntaje
                //     FROM salle_respuestas_preguntas sr, salle_preguntas sp
                //     WHERE sr.id_evaluacion = '$value->id_evaluacion'
                //     AND sr.id_pregunta = sp.id_pregunta
                //     AND sp.id_asignatura = '$valueA->id_asignatura'
                //     GROUP BY sr.id_pregunta
                // ");
                $puntaje_por_pregunta = DB::SELECT("CALL salle_puntaje_pregunta_asig (?, ?);",[$value->id_evaluacion, $valueA->id_asignatura]);
                foreach ($puntaje_por_pregunta as $keyP => $valueP){
                    //puntaje obtenido de cada docente por asig
                    $calif_asig_doc = $calif_asig_doc + $valueP->puntaje;
                }
                if( $calif_asig_doc <= 0 ){ $promedio_eval_asig = 0; }
                else{ $promedio_eval_asig = ( $calif_asig_doc * 100 ) / $calif_asig_eval; }
                if( $promedio_eval_asig > 100 ){ $promedio_eval_asig = 100; }
                $data_asignaturas[$keyA] = [
                    'id_asignatura'         => $puntaje_asignaturas[0]->id_asignatura,
                    'nombre_asignatura'     => $puntaje_asignaturas[0]->nombre_asignatura,
                    'puntaje'               => floatval(number_format($promedio_eval_asig, 2)),
                    'cant_preguntas'        => $puntaje_asignaturas[0]->cant_preguntas
                ];
                $promedio_eval_asig_acum    += $promedio_eval_asig;
            }
            if( count($asignaturas) > 0 ){
                $puntaje_evaluacion = $promedio_eval_asig_acum / count($asignaturas);
            }else{
                $puntaje_evaluacion = 0;
            }
            $data_evaluaciones['items'][$key] = [
                'id_evaluacion'         => $value->id_evaluacion,
                'puntaje_evaluacion'    => floatval(number_format($puntaje_evaluacion, 2)),
                'nombre_docente'        => $value->nombre_docente,
                'asignaturas'           => $data_asignaturas
            ];
        }
        // esta data devuelve los promedios por asignaturas de cada evaluacion, se debe procesar en el front
        return $data_evaluaciones;
    }



    public function salle_promedios_tipos_pregunta($periodo, $institucion, $id_asignatura){

    }


}
