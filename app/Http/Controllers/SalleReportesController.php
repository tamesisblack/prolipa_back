<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\SalleEvaluaciones;
use App\Repositories\Evaluaciones\SalleRepository;
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
    protected $salleRepository;
    public function __construct(SalleRepository $salleRepository)
    {
        $this->salleRepository = $salleRepository;
    }
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
        //si es menor a 4 return reporte anterior
        if($n_evaluacion < 4){
            return $this->reporte_anterior($n_evaluacion);
        }else{
            return $this->reporte_evaluaciones_institucionNueva($n_evaluacion);
        }
    }
    public function reporte_anterior($n_evaluacion){
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
                  $puntaje_por_pregunta = DB::SELECT("CALL salle_puntaje_pregunta (?);",[$valueE]);
                  foreach ($puntaje_por_pregunta as $keyP => $valueP){
                      //puntaje obtenido por cada docente, cada evaluacion se califica por puntajes diferentes
                      $acum_doc = $acum_doc + $valueP->puntaje;
                  }
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
    public function reporte_evaluaciones_institucionNueva($n_evaluacion)
    {
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
                $totalCalificaciones = 0;
                // dump('*********************************institucion: ' . $value->idInstitucion);
                foreach ($vector_evaluaciones as $keyE => $valueE){
                   $getEvaluacion = SalleEvaluaciones::where('id_evaluacion', $valueE)->first();
                   if($getEvaluacion){
                    //calificacion_total es sobre 100% calificacion
                       $totalCalificaciones = $totalCalificaciones + $getEvaluacion->calificacion_total;
                   }
                }
                // dump($calificaciones);
                $promedio_eval_inst = $totalCalificaciones / count($vector_evaluaciones);
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
            $areas = DB::SELECT("CALL salle_areas_evaluacion (?);",[$value->id_evaluacion]);
            $data_areas = array(); $promedio_eval_area_acum = 0;
            $totalCalificaciones = 0;
            $totalPuntajePreguntas = 0;
            $data_areas = [];
            foreach ($areas as $keyR => $valueR){
                $calificacionXArea = 0;
                $puntajeXArea = 0;
                $promediox_area = 0;
                $id_evaluacion = $value->id_evaluacion;
                //  // Obtener el tipo de calificación
                $query = $this->salleRepository->getCalificacionPreguntasXArea($id_evaluacion,$valueR->id_area);
                // // //totla puntaje respuestas
                foreach ($query as $keyRespuestas => $valuerRespuestas) {
                    $totalCalificaciones += $valuerRespuestas->puntaje;
                    $calificacionXArea += $valuerRespuestas->puntaje;
                }
                // //total puntaje por area
                $query2 = $this->salleRepository->puntajePorArea($id_evaluacion,$valueR->id_area);
                foreach ($query2 as $keyPuntaje => $valuePuntaje) {
                    $totalPuntajePreguntas += $valuePuntaje->puntaje;
                    $puntajeXArea += $valuePuntaje->puntaje;
                }
                $promediox_area = ( $calificacionXArea / $puntajeXArea ) * 100;
                $data_areas[$keyR] = [
                    'id_area'         => $valueR->id_area,
                    'nombre_area'     => $valueR->nombre_area,
                    'puntaje'         => round($promediox_area, 2),
                ];

               
               
            }//end foreach areas
            $promedio_eval_area = ( $totalCalificaciones / $totalPuntajePreguntas ) * 100;
            $data_evaluaciones['items'][$key] = [
                'id_evaluacion'         => $value->id_evaluacion,
                'puntaje_evaluacion'    => round($promedio_eval_area, 2),
                'nombre_docente'        => $value->nombre_docente,
                'areas'                 => $data_areas
            ];
        }
        // esta data devuelve los promedios por areas de cada evaluacion, se debe procesar en el front
        return $data_evaluaciones;
    }



    public function salle_promedio_asignatura($periodo, $institucion, $id_area){
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
                $puntaje_por_pregunta = DB::SELECT(" SELECT a.id_asignatura, a.nombre_asignatura, SUM(p.calificacion_final) AS puntaje
                FROM salle_preguntas_evaluacion p
                LEFT JOIN salle_preguntas pp ON pp.id_pregunta = p.id_pregunta
                LEFT JOIN salle_asignaturas a ON pp.id_asignatura = a.id_asignatura
                WHERE p.id_evaluacion = '$value->id_evaluacion'
                AND pp.id_asignatura =  '$valueA->id_asignatura'
                ");
                // $puntaje_por_pregunta = DB::SELECT("CALL salle_puntaje_pregunta_asig (?, ?);",[$value->id_evaluacion, $valueA->id_asignatura]);
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
