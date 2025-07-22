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
    public function getEvaluacionesUltimoPeriodo_(Request $request)
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
    public function getEvaluacionesUltimoPeriodo(Request $request)
    {

        $institucionId = $request->input('institucion_idInstitucion');
        $docenteId = $request->input('idusuario');


        try {
            // Obtener el último período escolar
            $ultimoPeriodo = DB::table('periodoescolar_has_institucion')
                ->where('institucion_idInstitucion', $institucionId)
                ->orderBy('periodoescolar_idperiodoescolar', 'desc')
                ->first();

            if (!$ultimoPeriodo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un período escolar para la institución.'
                ], 404);
            }

            $periodoId = $ultimoPeriodo->periodoescolar_idperiodoescolar;

            // Obtener el nombre del período escolar
            $nombrePeriodo = DB::table('periodoescolar')
                ->where('idperiodoescolar', $periodoId)
                ->value('periodoescolar');

                // Obtener el estado ifcodigoEvaluacion de la institución
            $institucion = DB::table('institucion')
            ->where('idInstitucion', $institucionId)
            ->select('ifcodigoEvaluacion')
            ->first();

            $mostrarCodigoEvaluacion = $institucion->ifcodigoEvaluacion == 1;

            // Obtener todas las asignaturas del docente en el período
            $asignaturas = DB::table('asignaturausuario as au')
                ->join('asignatura as a', 'au.asignatura_idasignatura', '=', 'a.idasignatura')
                ->where('au.periodo_id', $periodoId)
                ->where('au.usuario_idusuario', $docenteId)
                ->select('a.idasignatura', 'a.nombreasignatura')
                ->get();

            // Obtener todos los cursos del docente en el período
            $cursos = DB::table('curso as c')
                ->where('c.idusuario', $docenteId)
                ->where('c.id_periodo', $periodoId)
                ->where('c.estado', '1')
                ->select('c.idcurso', 'c.nombre', 'c.seccion', 'c.materia', 'c.codigo', 'c.id_asignatura','c.aula')
                ->get();

            // Obtener todas las evaluaciones de los cursos del docente
            $evaluaciones = DB::table('evaluaciones as e')
                ->where('e.id_docente', $docenteId)
                ->where('e.estado', '1')
                ->select('e.id', 'e.nombre_evaluacion', 'e.id_asignatura', 'e.codigo_curso',
                         'e.descripcion', 'e.puntos', 'e.duracion', 'e.fecha_inicio', 'e.fecha_fin','e.codigo_evaluacion')
                ->get();

            // Estructurar los datos jerárquicamente
            $resultado = $asignaturas->map(function ($asignatura) use ($cursos, $evaluaciones) {
                $cursosAsignatura = $cursos->where('id_asignatura', $asignatura->idasignatura);

                $cursosConEvaluaciones = $cursosAsignatura->map(function ($curso) use ($evaluaciones) {
                    $evals = $evaluaciones->where('codigo_curso', $curso->codigo);

                    return [
                        'idcurso' => $curso->idcurso,
                        'nombre' => $curso->nombre,
                        'seccion' => $curso->seccion,
                        'materia' => $curso->materia,
                        'codigo' => $curso->codigo,
                        'aula' => $curso->aula,
                        'evaluaciones' => $evals->map(function ($eval) {
                            return [
                                'id' => $eval->id,
                                'codigo_curso' => $eval->codigo_curso,
                                'nombre_evaluacion' => $eval->nombre_evaluacion,
                                'descripcion' => $eval->descripcion,
                                'puntos' => $eval->puntos,
                                'duracion' => $eval->duracion,
                                'fecha_inicio' => $eval->fecha_inicio,
                                'fecha_fin' => $eval->fecha_fin,
                                'codigo_evaluacion' => $eval->codigo_evaluacion,
                            ];
                        })->values()->toArray()
                    ];
                })->values()->toArray();

                return [
                    'idasignatura' => $asignatura->idasignatura,
                    'nombreasignatura' => $asignatura->nombreasignatura,
                    'cursos' => $cursosConEvaluaciones
                ];
            });

            return response()->json([
                'success' => true,
                'periodo_escolar' => $nombrePeriodo,
                'data' => $resultado,
                'mostrar_codigo_evaluacion' => $mostrarCodigoEvaluacion, // Opcional: informar al frontend
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function edit_fecha_evaluacion_admin(Request $request){
        $evaluacion = Evaluaciones::find($request->id);
        $evaluacion->fecha_inicio       = $request->fecha_inicio;
        $evaluacion->fecha_fin          = $request->fecha_fin;
        $evaluacion->save();
        return response()->json($evaluacion); // Retorna toda la evaluación actualizada
    }

    //api:Get/metodoGetEvaluacion
    public function metodoGetEvaluacion(Request $request){
        $action = $request->query('action');
        switch ($action) {
            case 'Get_Reporte_General':
                return $this->Get_Reporte_General($request);
            default:
                return response()->json(['error' => 'Acción no válida'], 400);
        }
    }
    //API:GET/metodoGetEvaluacion?action=Get_Reporte_General&id_docente=89379&institucion_idInstitucion=1838
    public function Get_Reporte_General($request) {
        $id_docente                 = $request->query('id_docente');
        $id_asignatura              = $request->query('id_asignatura');
        $id_nivel                   = $request->query('id_nivel');
        $id_periodo                 = $request->query('periodoSelected');

        // Validaciones
        if (empty($id_docente) || !is_numeric($id_docente)) {
            return response()->json(['error' => 'Falta el ID del docente o no es un número'], 400);
        }

        // Preparar consulta principal
        $params = [$id_docente, $id_periodo];
        $additionalFilters = "";

        // Filtro por asignatura
        if (!empty($id_asignatura) && is_numeric($id_asignatura)) {
            $additionalFilters .= " AND c.id_asignatura = ? ";
            $params[] = $id_asignatura;
        }

        // Filtro por nivel
        if (!empty($id_nivel) && is_numeric($id_nivel)) {
            $additionalFilters .= " AND a.nivel_idnivel = ? ";
            $params[] = $id_nivel;
        }

        // Consulta principal
        $resultados = DB::select("
            SELECT
                u.idusuario AS id_estudiante,
                CONCAT(u.nombres, ' ', u.apellidos) AS estudiante,
                u.cedula as cedulaEstudiante,
                u.name_usuario as emailEstudiante,
                a.nombreasignatura,
                c.nombre AS nombre_curso,
                e.descripcion AS nombre_evaluacion,
                IFNULL(cal.calificacion, 0) AS calificacion,
                c.id_asignatura,
                a.nivel_idnivel
            FROM evaluaciones e
            JOIN curso c ON e.codigo_curso = c.codigo
            JOIN asignatura a ON e.id_asignatura = a.idasignatura
            JOIN estudiante est ON est.codigo = c.codigo
            JOIN usuario u ON est.usuario_idusuario = u.idusuario
            LEFT JOIN calificaciones cal
                ON cal.id_evaluacion = e.id
                AND cal.id_estudiante = u.idusuario
            WHERE e.id_docente = ?
            AND c.id_periodo = ?
            AND e.estado = '1'
            $additionalFilters
            ORDER BY estudiante, c.nombre
        ", $params);

        // Agrupar por estudiante
        $arrayReporte = [];
        foreach ($resultados as $fila) {
            $id = $fila->id_estudiante;
            if (!isset($arrayReporte[$id])) {
                $arrayReporte[$id] = [
                    'id_estudiante' => $fila->id_estudiante,
                    'estudiante' => $fila->estudiante,
                    'cedulaEstudiante' => $fila->cedulaEstudiante,
                    'emailEstudiante' => $fila->emailEstudiante,
                    'evaluaciones' => []
                ];
            }

            $arrayReporte[$id]['evaluaciones'][] = [
                'nombre_curso' => $fila->nombre_curso,
                'nombre_evaluacion' => $fila->nombre_evaluacion,
                'nombreasignatura' => $fila->nombreasignatura,
                'calificacion' => $fila->calificacion
            ];
        }

        // Consulta de asignaturas del docente (sin filtro por nivel)
        $asignaturasDocente = DB::select("
            SELECT DISTINCT
                a.idasignatura AS id_asignatura,
                a.nombreasignatura
            FROM evaluaciones e
            JOIN curso c ON e.codigo_curso = c.codigo
            JOIN asignatura a ON e.id_asignatura = a.idasignatura
            WHERE e.id_docente = ?
            AND c.id_periodo = ?
            AND e.estado = '1'
            ORDER BY a.nombreasignatura
        ", [$id_docente, $id_periodo]);

        return [
            'arrayReporte' => array_values($arrayReporte),
            'arrayAsignaturasDocentes' => $asignaturasDocente
        ];
    }
}

