<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;///instanciamos base de datos para poder hacer consultas con varias tablas
use App\Models\Calificaciones;//modelo Calificaciones.php
use App\Models\Evaluaciones;

class CalificacionEvalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Calificaciones::all();

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
    //api:post
    public function empezarEvaluacion(Request $request){
        $validate = DB::SELECT("SELECT * FROM calificaciones c
        WHERE c.id_estudiante = '$request->id_estudiante'
        AND c.id_evaluacion = '$request->id_evaluacion'
        ");
        $codigoUnico = "";
        if(empty($validate)){
            //si el estado es 1 es porque ya se ha finalizo la evaluación y ya no puede
            //volver a iniciar la evaluación

            $calificacion                           = new Calificaciones();
            $calificacion->id_estudiante            = $request->id_estudiante;
            $calificacion->id_evaluacion            = $request->id_evaluacion;
            $calificacion->grupo                    = $request->grupo;
            $calificacion->ip                       = $request->ipDireccion;
            $calificacion->fecha_inicio_evaluacion  = date('Y-m-d H:i:s');
            //estado
            $calificacion->estado                   = 0;
            //uuid
            $calificacion->codigo_inicial           = uniqid();
            $calificacion->save();
            $codigoUnico = $calificacion->codigo_inicial;
        }
        if(count($validate) > 0){
            $getEstado      = $validate[0]->estado;
            $codigo_inicial = $validate[0]->codigo_inicial;
            if($getEstado == 1){
                return ["status" => "0", "message" => "Ya finalizo la evaluación"];
            }
            if($request->codigoUnico != $codigo_inicial){
                return ["status" => "3", "message" => "Ya ha iniciado la evaluacion en otro dispositivo"];
            }
            return ["status" => "1", "message" => "Ya inicio la evaluación"];
        }
        return ["status" => "2", "message" => "Ya inicio la evaluación","codigoUnico" => $codigoUnico];
    }
    //api:post/detallesCalificaciones
    public function detallesCalificaciones(Request $request){
        $query = $this->calificacionesEstudiante($request->idUser,$request->idEvaluacion);
        return $query;
    }
    public function calificacionesEstudiante($estudiante,$evaluacion){
        $query = DB::SELECT("SELECT c.*, CONCAT(u.nombres, ' ',u.apellidos) as estudiante, u.cedula, u.idusuario
        FROM calificaciones c
        LEFT JOIN usuario u ON c.id_estudiante = u.idusuario
        WHERE c.id_estudiante = '$estudiante'
        AND c.id_evaluacion = '$evaluacion'
        ");
        return $query;
    }
    public function store(Request $request)
    {
        // $validate = $this->calificacionesEstudiante($request->estudiante, $request->evaluacion);

        // $getCalificacionFinal = DB::select("SELECT COALESCE(SUM(r.puntaje), 0) AS puntaje
        //     FROM respuestas_preguntas r
        //     WHERE r.id_evaluacion = :evaluacion
        //     AND r.id_estudiante = :estudiante
        // ", [
        //     'evaluacion' => $request->evaluacion,
        //     'estudiante' => $request->estudiante
        // ]);

        // $puntajeFinal = $getCalificacionFinal[0]->puntaje;

        // if (empty($validate)) {
        //     $nuevaCalificacion = new Calificaciones();
        //     $nuevaCalificacion->id_estudiante   = $request->estudiante;
        //     $nuevaCalificacion->id_evaluacion   = $request->evaluacion;
        //     $nuevaCalificacion->grupo           = $request->grupo;
        //     $nuevaCalificacion->calificacion    = $puntajeFinal; // usar $puntajeFinal aquí
        //     $nuevaCalificacion->ip              = $request->ip();
        //     $nuevaCalificacion->estado          = 1;
        //     $nuevaCalificacion->save();

        //     $evaluacion = Evaluaciones::findOrFail($request->evaluacion);
        //     return $evaluacion;
        // } else {
        //     $getId = $validate[0]->id;
        //     $calificacionExistente                  = Calificaciones::find($getId);
        //     $calificacionExistente->calificacion    = $puntajeFinal; // usar $puntajeFinal aquí
        //     $calificacionExistente->estado          = 1;
        //     $calificacionExistente->grupo           = $request->grupo;
        //     $calificacionExistente->save();
        // }
        $evaluacion = Evaluaciones::findOrFail($request->evaluacion);
        return $evaluacion;
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


    public function verifRespEvaluacion(Request $request)
    {
        $calificaciones = DB::SELECT("SELECT * FROM calificaciones WHERE id_evaluacion = $request->evaluacion AND id_estudiante = $request->estudiante AND estado = 1");

        if($calificaciones){
            return $calificaciones;
        }else{
            return 0;
        }
    }



    public function modificarEvaluacion(Request $request)
    {
        $calificacion = DB::UPDATE("UPDATE `calificaciones` SET `calificacion`=$request->calificacion WHERE `id_evaluacion`=$request->evaluacion AND `id_estudiante`=$request->estudiante");

        $respuesta = DB::UPDATE("UPDATE `respuestas_preguntas` SET `puntaje`=$request->puntaje WHERE `id_respuesta_pregunta` = $request->id_respuesta");
    }



    public function guardarRespuesta(Request $request)
    {
        $codigoUnico        = $request->codigoUnico;
        //ifcodigoEvaluacion 1 = validacion con codigo
        $getCalificacion = Calificaciones::where('id_estudiante', $request->estudiante)
            ->where('id_evaluacion', $request->evaluacion)
            ->first();
        if(!$getCalificacion){
            return ["status" => "0", "message" => "Evaluacion no encontrada"];
        }
        if($getCalificacion){
            //si el codigo es diferente al codigo de la base de datos
            if($getCalificacion->codigo_inicial != $codigoUnico){
                return ["status" => "0", "message" => "Ya ha iniciado la evaluacion en otro dispositivo"];
            }
        }
        $validate = DB::SELECT("SELECT * FROM respuestas_preguntas r
        WHERE r.id_pregunta = '$request->pregunta'
        AND r.id_evaluacion = '$request->evaluacion'
        AND r.id_estudiante = '$request->estudiante'
        ");
        if(empty($validate)){
            $respuestas = DB::INSERT("INSERT INTO `respuestas_preguntas`(`id_evaluacion`,
            `id_pregunta`, `id_estudiante`, `respuesta`, `puntaje`)
            VALUES ($request->evaluacion, $request->pregunta, $request->estudiante,
            '$request->respuesta', $request->puntaje)");
        }
        // $respuestas = DB::INSERT("INSERT INTO `respuestas_preguntas`(`id_evaluacion`, `id_pregunta`, `id_estudiante`, `respuesta`, `puntaje`) VALUES ($request->evaluacion, $request->pregunta, $request->estudiante, '$request->respuesta', $request->puntaje)");
        ///=======CALIFICAR
        $validate = $this->calificacionesEstudiante($request->estudiante, $request->evaluacion);
        $getCalificacionFinal = DB::select("SELECT COALESCE(SUM(r.puntaje), 0) AS puntaje
            FROM respuestas_preguntas r
            WHERE r.id_evaluacion = '$request->evaluacion'
            AND r.id_estudiante = '$request->estudiante'
        ");
        $puntajeFinal = $getCalificacionFinal[0]->puntaje;
        if (empty($validate)) {
            $nuevaCalificacion = new Calificaciones();
            $nuevaCalificacion->id_estudiante   = $request->estudiante;
            $nuevaCalificacion->id_evaluacion   = $request->evaluacion;
            $nuevaCalificacion->grupo           = $request->grupo;
            $nuevaCalificacion->calificacion    = $puntajeFinal; // usar $puntajeFinal aquí
            $nuevaCalificacion->ip              = $request->ip();
            // $nuevaCalificacion->ip              = 123456;
            $nuevaCalificacion->estado          = 1;
            $nuevaCalificacion->save();
            $evaluacion = Evaluaciones::findOrFail($request->evaluacion);
            return $evaluacion;
        } else {
            $getId = $validate[0]->id;
            $calificacionExistente                  = Calificaciones::find($getId);
            $calificacionExistente->calificacion    = $puntajeFinal; // usar $puntajeFinal aquí
            $calificacionExistente->estado          = 1;
            $calificacionExistente->grupo           = $request->grupo;
            $calificacionExistente->fecha_fin_evaluacion = now();
            $calificacionExistente->save();
        }
        $evaluacion = Evaluaciones::findOrFail($request->evaluacion);
        return $evaluacion;
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
        /*$calificacion = Calificaciones::find($id);
        $calificacion->id_estudiante = $request->estudiante;
        $calificacion->id_evaluacion = $request->evaluacion;
        $calificacion->calificacion = $request->calificacion;
        $calificacion->save();
        return $calificacion;*/
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $calificacion = Calificaciones::find($id);
        $calificacion->delete();
    }


     public function evaluacionEstudiante($id)
    {
        $responder = DB::SELECT("SELECT *, now() as fecha_actual FROM evaluaciones e, asignatura a WHERE e.id = $id AND e.id_asignatura = a.idasignatura AND e.estado = 1");
        if($responder){
            // Iterar sobre cada resultado y eliminar la columna 'codigoEvaluacion'
            foreach ($responder as $resultado) {
                unset($resultado->codigo_evaluacion);
            }
            return $responder;
        }else{
            return 0;
        }
    }

}
