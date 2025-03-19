<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;///instanciamos base de datos para poder hacer consultas con varias tablas
use App\Models\Evaluaciones;//modelo Evaluaciones.php
use App\Models\EvaluacionProlipaIntentos;
use App\Models\Institucion;
use App\Models\User;
use App\Models\Curso;
use App\Models\PeriodoInstitucion;
use App\Traits\Codigos\TraitCodigosGeneral;

class EvaluacionController extends Controller
{
    use TraitCodigosGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $evaluaciones = DB::SELECT("SELECT e.id, e.nombre_evaluacion, e.id_asignatura,e.id_docente, e.descripcion, e.puntos, e.fecha_inicio, e.fecha_fin, e.duracion, e.estado, a.nombreasignatura FROM evaluaciones e, asignatura a WHERE e.id_asignatura = a.idasignatura");

        //return Evaluaciones::all();
        return $evaluaciones;

    }


    public function evaluacionesDocente(Request $request)
    {
        $evaluaciones = DB::SELECT("SELECT DISTINCT c.nombre as nombre_curso, c.materia, c.aula, c.seccion, e.codigo_curso,
        e.id, e.nombre_evaluacion, e.id_asignatura,e.id_docente, e.descripcion, e.puntos, e.fecha_inicio, e.fecha_fin, e.duracion,
        e.estado, a.nombreasignatura, e.created_at, e.id_tipoeval, et.tipo_nombre, e.grupos_evaluacion, e.cant_unidades,e.ver_calificaciones,
        e.codigo_evaluacion
        FROM evaluaciones e, asignatura a, curso c, eval_tipos et
        WHERE e.id_asignatura = a.idasignatura
        and e.id_docente = $request->docente
        AND e.codigo_curso = '$request->codigo'
        AND e.codigo_curso = c.codigo
        AND et.id = e.id_tipoeval
        ORDER BY e.created_at DESC");
        return $evaluaciones;
    }

    public function TiposEvaluacion()
    {
        $tiposevaluacion = DB::SELECT("SELECT tipo_nombre as label, id FROM eval_tipos WHERE tipo_estado = 1");
        return $tiposevaluacion;
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
    public function store(Request $request)//request datos que ingreso en los input del formulario
    {//agregar

        if( $request->id ){
            $evaluacion = Evaluaciones::find($request->id);
        }else{
            $evaluacion = new Evaluaciones();
        }
        $evaluacion->nombre_evaluacion  = $request->nombre;
        $evaluacion->id_asignatura      = $request->asignatura;
        $evaluacion->descripcion        = $request->descripcion;
        $evaluacion->puntos             = $request->puntos;
        $evaluacion->fecha_inicio       = $request->fecha_inicio;
        $evaluacion->fecha_fin          = $request->fecha_fin;
        $evaluacion->duracion           = $request->duracion;
        $evaluacion->estado             = $request->estado;
        $evaluacion->id_docente         = $request->docente;
        $evaluacion->codigo_curso       = $request->codigo;
        $evaluacion->id_tipoeval        = $request->idtipoeval;
        $evaluacion->grupos_evaluacion  = $request->id_grupo_opciones;
        $evaluacion->cant_unidades      = $request->cant_unidades;
        $evaluacion->ver_calificaciones = $request->ver_calificaciones;
        $evaluacion->save();
        $user                           = User::where('idusuario', $request->docente)->first();
        if($user){
            $getE                       = Evaluaciones::find($evaluacion->id);
            $codigo_evaluacion          = $getE->codigo_evaluacion;
            $institucion                = $user->institucion_idInstitucion;
            $getInstitucion             = Institucion::where('idInstitucion', $institucion)->first();
            $ifcodigoEvaluacion         = $getInstitucion->ifcodigoEvaluacion;
            //si es 1 se crea el codigo de evaluacion
            if($ifcodigoEvaluacion == 1){
                if($codigo_evaluacion == null || $codigo_evaluacion == ''){
                    $codigo = $this->makeidNumbers(6);
                    DB::table('evaluaciones')
                    ->where('id', $evaluacion->id)
                    ->update(['codigo_evaluacion' => $codigo]);
                }
            }else{
                $codigo = null;
                DB::table('evaluaciones')
                ->where('id', $evaluacion->id)
                ->update(['codigo_evaluacion' => $codigo]);
            }
        }
        $getEvaluaciohn = Evaluaciones::find($evaluacion->id);
        return $getEvaluaciohn;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $evaluaciones = DB::SELECT("SELECT * FROM evaluaciones WHERE id = $id");

        if($evaluaciones){
            return $evaluaciones;
        }else{
            return 0;
        }
    }



    public function evaluacionesEstudianteCurso(Request $request)
    {
        // $evaluaciones = DB::SELECT("SELECT DISTINCT es.grupo, cu.nombre as nombre_curso,
        // cu.seccion, cu.materia, cu.aula, e.id, e.nombre_evaluacion, e.descripcion, e.puntos,
        // e.fecha_inicio, e.fecha_fin, e.duracion, e.estado, es.usuario_idusuario as id_estudiante, a.nombreasignatura, e.ver_calificaciones
        // FROM evaluaciones e, estudiante es, curso cu, asignatura a
        // WHERE e.codigo_curso = es.codigo
        // AND e.estado = 1
        // AND es.usuario_idusuario = $request->estudiante
        // AND es.usuario_idusuario NOT IN (SELECT c.id_estudiante from calificaciones c WHERE c.id_evaluacion = e.id AND c.estado = '1')
        // AND es.codigo = cu.codigo
        // AND cu.codigo = '$request->codigo'
        // AND e.id_asignatura = a.idasignatura");
        // return $evaluaciones;

        $evaluaciones = DB::SELECT("SELECT e.*, CONCAT(d.nombres, ' ', d.apellidos) as docente,a.nombreasignatura, c.idcurso, 1 as grupo
            FROM evaluaciones e
            LEFT JOIN estudiante es ON es.codigo = e.codigo_curso
            LEFT JOIN usuario d ON e.id_docente  = d.idusuario
            LEFT JOIN asignatura a ON a.idasignatura = e.id_asignatura
            LEFT JOIN curso c ON c.codigo = e.codigo_curso
            WHERE e.codigo_curso = '$request->codigo'
            AND es.codigo = '$request->codigo'
            AND es.usuario_idusuario NOT IN (SELECT c.id_estudiante from calificaciones c WHERE c.id_evaluacion = e.id AND c.estado = '1')
            AND es.usuario_idusuario = '$request->estudiante'
            AND e.estado = '1'
            -- AND e.fecha_fin >= NOW()
            -- AND e.fecha_inicio <= NOW()
        ");
        // Iterar sobre cada resultado y eliminar la columna 'codigoEvaluacion'
        foreach ($evaluaciones as $resultado) {
            unset($resultado->codigo_evaluacion);
        }
        return $evaluaciones;

    }


    public function evalCompleEstCurso(Request $request)
    {
        $evaluaciones = DB::SELECT("SELECT DISTINCT e.id, c.grupo,
         c.calificacion, e.nombre_evaluacion, e.descripcion,
          e.puntos, e.fecha_inicio, e.fecha_fin, e.duracion, a.nombreasignatura,e.ver_calificaciones,
          c.updated_at, c.fecha_fin_evaluacion
           FROM calificaciones c, evaluaciones e, estudiante es, asignatura a
           WHERE c.id_evaluacion = e.id
            AND c.id_estudiante = $request->estudiante
             AND e.codigo_curso = '$request->codigo'
              AND es.codigo = e.codigo_curso
               AND es.usuario_idusuario = c.id_estudiante
                AND e.estado = 1
                 AND e.id_asignatura = a.idasignatura
                 AND c.estado = 1
                  ORDER BY e.id
        ");

        return $evaluaciones;
    }
    //api:post/validateCodigo
    public function validateCodigo(Request $request){
        try{
            $id_evaluacion = $request->id_evaluacion;
            $codigo       = $request->codigo;
            $evaluacion   = Evaluaciones::where('codigo_evaluacion', $codigo)->where('id', $id_evaluacion)->first();
            //si existe envio 1 si no 0
            if($evaluacion){
                return 1;
            }else{
                return 0;
            }
        }catch(\Exception $e){
            return ["status" => 0, "message" => "Error al validar el código"];
        }
    }

     public function verCalificacionEval($codigo)
    {
        $estudiantes = DB::SELECT("CALL getCalificacionesEval ('$codigo');");

        /*$estudiantes = DB::SELECT("SELECT DISTINCT e.id, e.usuario_idusuario, e.codigo, u.cedula, u.nombres, u.apellidos, e.estado as estado_estudiante, u.estado_idEstado as estado_usuario, e.created_at FROM estudiante e, usuario u WHERE e.usuario_idusuario = u.idusuario AND u.estado_idEstado = 1 AND e.codigo = '$codigo'");*/

        if(!empty($estudiantes)){
            foreach ($estudiantes as $key => $value) {
                $calificaciones = DB::SELECT("SELECT DISTINCT e.id, e.nombre_evaluacion, e.puntos, e.duracion, es.usuario_idusuario, (SELECT c.calificacion FROM calificaciones c WHERE c.id_estudiante = es.usuario_idusuario AND c.id_evaluacion = e.id AND estado = '1') as calificacion FROM evaluaciones e, estudiante es WHERE e.codigo_curso = ? AND e.codigo_curso = es.codigo AND es.usuario_idusuario = ?",[$codigo, $value->usuario_idusuario]);

                $total = DB::SELECT("SELECT DISTINCT * FROM evaluaciones e WHERE e.codigo_curso = ?",[$codigo]);

                $data['items'][$key] = [
                    "idusuario" => $value->usuario_idusuario,
                    'id' => $value->id,
                    'cedula' => $value->cedula,
                    'nombres' => $value->nombres,
                    'apellidos' => $value->apellidos,
                    'usuario_idusuario' => $value->usuario_idusuario,
                    'codigo' => $value->codigo,
                    'estado_estudiante' => $value->estado_estudiante,
                    'estado_usuario' => $value->estado_usuario,
                    'created_at' => $value->created_at,
                    'calificaciones'=>$calificaciones,
                    'total'=>$total,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }



    public function verEstCursoEval($id)
    {
        $estudiantes = DB::SELECT("SELECT DISTINCT e.grupo, u.idusuario, u.nombres, u.apellidos, u.cedula, u.email, u.telefono FROM estudiante e, usuario u WHERE e.codigo = '$id' AND e.usuario_idusuario = u.idusuario AND e.estado = '1' ORDER BY e.grupo");

        return $estudiantes;
    }


    public function asignarGrupoEst(Request $request)
    {
        $estudiantes = DB::UPDATE("UPDATE estudiante SET grupo = $request->grupo WHERE usuario_idusuario = $request->estudiante AND codigo = '$request->codigo'");

        return $estudiantes;
    }

    public function verEvalCursoExport($codigo)
    {
        $evaluaciones = DB::SELECT("SELECT DISTINCT * FROM evaluaciones e WHERE e.codigo_curso = '$codigo'");

        return $evaluaciones;
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
        $evaluacion = Evaluaciones::find($id);
        $evaluacion->nombre_evaluacion = $request->nombre_evaluacion;
        $evaluacion->id_asignatura = $request->id_asignatura;
        $evaluacion->descripcion = $request->descripcion;
        $evaluacion->puntos = $request->puntos;
        $evaluacion->fecha_inicio = $request->fecha_inicio;
        $evaluacion->fecha_fin = $request->fecha_fin;
        $evaluacion->estado = $request->estado;
        $evaluacion->save();

        return $evaluacion;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */



    public function eliminar_evaluacion($id_evaluacion)
    {
        $evaluacion = DB::SELECT("SELECT * FROM calificaciones WHERE id_evaluacion = $id_evaluacion");

        if($evaluacion){
            return 0;
        }else{
            $preguntas = DB::DELETE("DELETE FROM `pre_evas` WHERE `id_evaluacion` = $id_evaluacion");
            $eval = DB::DELETE("DELETE FROM `evaluaciones` WHERE `id` = $id_evaluacion");
        }
    }




    public function destroy($id_evaluacion)
    {
        $evaluacion = Evaluaciones::find($id_evaluacion);
        $evaluacion->delete();
    }
    //api:post/generarIntentosEvaluacion
    public function generarIntentosEvaluacion(Request $request){
        $idusuario          = $request->idusuario;
        $evaluacion_id      = $request->evaluacion_id;
        $intentoE           = 0;
        //validar si existen intentos
        $getIntentos = EvaluacionProlipaIntentos::where('estudiante_id', $idusuario)->where('evaluacion_id', $evaluacion_id)->get();
        //si no existe lo creo
        if($getIntentos->isEmpty()){
            $intentos = new EvaluacionProlipaIntentos();
            $intentos->estudiante_id = $idusuario;
            $intentos->evaluacion_id = $evaluacion_id;
            $intentos->intentos      = 0;
            $intentos->save();
            $intentoE = 0;
            return $intentoE;
        }else{
            $getIntento = $getIntentos[0]->intentos;
            $getId     = $getIntentos[0]->id;
            //guardar intento
            if($request->saveIntento){
                $intentos = EvaluacionProlipaIntentos::find($getId);
                $intentos->intentos = $getIntento + 1;
                $intentos->save();
                return $getIntento + 1;
            }else{
                return $getIntento;
            }
        }
    }
    //API:GET/getEvaluacionesInstitucion/{idInstitucion}/{idPeriodo}
    public function getEvaluacionesInstitucion($idInstitucion,$idPeriodo){
        $query         = DB::SELECT("SELECT  DISTINCT  c.id_periodo, e.id as idEvaluacion, c.codigo,
         a.nombreasignatura
        FROM evaluaciones e
        LEFT JOIN usuario u ON e.id_docente = u.idusuario
        LEFT JOIN curso c ON   c.codigo = e.codigo_curso
        LEFT JOIN periodoescolar p ON c.id_periodo = c.id_periodo
        LEFT JOIN asignatura a ON c.id_asignatura = a.idasignatura
        WHERE u.institucion_idInstitucion = ?
        AND  c.id_periodo = ?
        AND e.estado = '1'
        ",[$idInstitucion,$idPeriodo]);
        return $query;
    }
    //resetear intentos
    public function resetearIntentos(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $intentosPermitidos = $request->intentosPermitidos;
        $arrayEvaluaciones  = json_decode($request->arrayEvaluaciones);
        foreach($arrayEvaluaciones as $item){
            $idEvaluacion       = $item->idEvaluacion;
            $evaluaciones = EvaluacionProlipaIntentos::where('evaluacion_id', $idEvaluacion)->where('intentos', '<', $intentosPermitidos)
            ->update(['intentos' => 0]);
        }
        return $evaluaciones;
    }
    //API:POST/resetearEvaluacion
    //resetear evaluacion
    public function resetearEvaluacion(Request $request){
        $id_evaluacion = $request->id_evaluacion;
        $id_estudiante = $request->id_estudiante;
        db::table('respuestas_preguntas')
        ->where('id_evaluacion',$id_evaluacion)
        ->where('id_estudiante',$id_estudiante)
        ->delete();
        //calificaciones
        db::table('calificaciones')
        ->where('id_evaluacion',$id_evaluacion)
        ->where('id_estudiante',$id_estudiante)
        ->delete();
        return ["status" => "1", "message" => "Se guardo correctamente"];
    }
    public function getEvaluacionesUltimoPeriodo(Request $request)
    {
        $institucionId = $request->input('institucion_idInstitucion');
        $docenteId = $request->input('idusuario');

        // Obtener el último período escolar registrado para la institución
        $ultimoPeriodo = DB::table('periodoescolar_has_institucion')
        ->where('institucion_idInstitucion', $institucionId)
        ->orderBy('periodoescolar_idperiodoescolar', 'desc')
        ->first();

        if (!$ultimoPeriodo) {
            return response()->json(['error' => 'No se encontró un período escolar para la institución.'], 404);
        }

        // Obtener el nombre del período escolar
        $nombrePeriodo = DB::table('periodoescolar')
            ->where('idperiodoescolar', $ultimoPeriodo->periodoescolar_idperiodoescolar)
            ->value('periodoescolar');

        // Obtener las evaluaciones junto con los cursos y las asignaturas
        $resultados = DB::table('evaluaciones')
        ->join('asignatura', 'evaluaciones.id_asignatura', '=', 'asignatura.idasignatura') // Relacionando con la tabla asignatura
        ->join('curso', 'evaluaciones.codigo_curso', '=', 'curso.codigo') // Relacionando con la tabla cursos
        ->join('eval_tipos', 'evaluaciones.id_tipoeval', '=', 'eval_tipos.id') // Relacionando con la tabla cursos
        ->where('evaluaciones.id_docente', $docenteId)
        ->select(
            'evaluaciones.id',
            'evaluaciones.nombre_evaluacion',
            'evaluaciones.id_docente',
            'evaluaciones.codigo_curso',
            'evaluaciones.puntos',
            'evaluaciones.duracion',
            'evaluaciones.descripcion',
            'evaluaciones.fecha_inicio',
            'evaluaciones.fecha_fin',
            'evaluaciones.estado',
            'evaluaciones.grupos_evaluacion',
            'evaluaciones.cant_unidades',
            'evaluaciones.codigo_evaluacion',
            'evaluaciones.ver_calificaciones',
            'evaluaciones.id_tipoeval',
            DB::raw("'" . $institucionId . "' as institucion_idInstitucion"),
            'eval_tipos.tipo_nombre',
            'asignatura.idasignatura as id_asignatura' , // Añadir id_asignatura
            'asignatura.nombreasignatura', // Añadir nombre de la asignatura
            'asignatura.estado as estado_asignatura', // Añadir el estado de la asignatura
            'curso.nombre as nombre_curso', // Añadir nombre del curso
            'curso.seccion', // Añadir la sección del curso
            'curso.aula', // Añadir el aula
            'curso.codigo' // Añadir el código del curso
        )
        ->get();

        // Agregar el nombre del período a cada evaluación
        $resultados->each(function ($evaluacion) use ($nombrePeriodo) {
            $evaluacion->nombre_periodo = $nombrePeriodo;
        });

        return response()->json($resultados);
    }
    public function getEvaluacionesUltimoPeriodo_2(Request $request) 
    {
        $institucionId = $request->input('institucion_idInstitucion');
        $docenteId = $request->input('idusuario');

        // Obtener el último período escolar registrado para la institución
        $ultimoPeriodo = DB::table('periodoescolar_has_institucion')
            ->where('institucion_idInstitucion', $institucionId)
            ->orderBy('periodoescolar_idperiodoescolar', 'desc')
            ->first();

        if (!$ultimoPeriodo) {
            return response()->json(['error' => 'No se encontró un período escolar para la institución.'], 404);
        }

        // Obtener el nombre del período escolar
        $nombrePeriodo = DB::table('periodoescolar')
            ->where('idperiodoescolar', $ultimoPeriodo->periodoescolar_idperiodoescolar)
            ->value('periodoescolar');

        // Obtener los cursos activos asignados al docente
        $cursos = DB::table('curso')
            ->join('asignatura', 'curso.id_asignatura', '=', 'asignatura.idasignatura')
            ->where('curso.idusuario', $docenteId)
            ->where('curso.estado', "1") // Filtrar solo los cursos activos
            ->select(
                'curso.codigo',
                'curso.nombre as nombre_curso',
                'curso.seccion',
                'curso.aula',
                'curso.estado as estado_curso', // Se añade el estado del curso
                'asignatura.idasignatura',
                'asignatura.nombreasignatura',
                'asignatura.estado as estado_asignatura'
            )
            ->get();

        // Obtener las evaluaciones asociadas a los cursos activos
        $evaluaciones = DB::table('evaluaciones')
            ->join('eval_tipos', 'evaluaciones.id_tipoeval', '=', 'eval_tipos.id')
            ->whereIn('evaluaciones.codigo_curso', $cursos->pluck('codigo')) // Filtrar por cursos activos
            ->select(
                'evaluaciones.id',
                'evaluaciones.nombre_evaluacion',
                'evaluaciones.id_docente',
                'evaluaciones.codigo_curso',
                'evaluaciones.puntos',
                'evaluaciones.duracion',
                'evaluaciones.descripcion',
                'evaluaciones.fecha_inicio',
                'evaluaciones.fecha_fin',
                'evaluaciones.estado',
                'evaluaciones.grupos_evaluacion',
                'evaluaciones.cant_unidades',
                'evaluaciones.codigo_evaluacion',
                'evaluaciones.ver_calificaciones',
                'evaluaciones.id_tipoeval',
                'eval_tipos.tipo_nombre'
            )
            ->get();

        // Anidar evaluaciones dentro de cada curso
        $cursos->each(function ($curso) use ($evaluaciones) {
            $curso->evaluaciones = $evaluaciones->where('codigo_curso', $curso->codigo)->values();
        });

        return response()->json([
            'institucion_id' => $institucionId,
            'nombre_periodo' => $nombrePeriodo,
            'cursos' => $cursos
        ]);
    }


}
