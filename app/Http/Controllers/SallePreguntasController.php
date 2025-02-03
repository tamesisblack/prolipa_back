<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\SalleAreas;
use Illuminate\Support\Facades\DB;
use App\Models\SallePreguntas;
use App\Models\SalleEvaluaciones;
use App\Models\SallePreguntasOpcion;
use App\Models\SallePreguntasEvaluacion;
use App\Repositories\Evaluaciones\SalleRepository;
use Exception;
use Illuminate\Provider\Image;
use Illuminate\Support\Facades\File;


class SallePreguntasController extends Controller
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
        $preguntas = DB::SELECT("SELECT p . *, t.nombre_tipo, t.indicaciones, t.descripcion_tipo, a.nombre_asignatura, a.cant_preguntas, a.estado as estado_asignatura, ar.nombre_area, ar.id_area, ar.estado as estado_area FROM salle_preguntas p, tipos_preguntas t, salle_asignaturas a, salle_areas ar WHERE p.id_tipo_pregunta = t.id_tipo_pregunta AND p.id_asignatura = a.id_asignatura AND a.id_area = ar.id_area AND a.estado = 1 AND ar.estado = 1 AND p.estado = 1");
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT * FROM `salle_opciones_preguntas` WHERE `id_pregunta` = ?",[$value->id_pregunta]);
                $data['items'][$key] = [
                    'pregunta' => $value,
                    'opciones' => $opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
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
    //api:post/CambiarEstadoPreguntaSalle
    public function CambiarEstadoPreguntaSalle(Request $request){
        $pregunta = SallePreguntas::find($request->id_pregunta);
        $pregunta->estado = $request->estado;
        $pregunta->save();
    }
    public function store(Request $request)
    {
        $ruta = public_path('img/salle/img_preguntas');

        if( $request->id_pregunta >0 ){
            $pregunta = SallePreguntas::find($request->id_pregunta);
            if($request->file('img_pregunta') && $request->file('img_pregunta') != null && $request->file('img_pregunta')!= 'null'){
                $file = $request->file('img_pregunta');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                if( file_exists('img/salle/img_preguntas/'.$request->img_pregunta_old) && $request->img_pregunta_old != '' ){
                    unlink('img/salle/img_preguntas/'.$request->img_pregunta_old);
                }
            }else{
                $fileName = $request->img_pregunta_old;
            }
        }else{
            $pregunta = new SallePreguntas();
            if($request->file('img_pregunta')){
                $file = $request->file('img_pregunta');
                $ruta = public_path('img/salle/img_preguntas');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
            }else{
                $fileName = '';
            }
        }

        $pregunta->id_tipo_pregunta  = $request->id_tipo_pregunta ;
        $pregunta->id_asignatura  = $request->id_asignatura ;
        $pregunta->descripcion = $request->descripcion;
        $pregunta->img_pregunta = $fileName;
        $pregunta->puntaje_pregunta = $request->puntaje_pregunta;
        $pregunta->estado = 1;
        $pregunta->editor = $request->editor;

        $pregunta->save();

        return $pregunta;
    }



    public function cargar_opcion_salle(Request $request)
    {
        $ruta = public_path('img/salle/img_preguntas');

        if($request->file('img_opcion')){
            $file = $request->file('img_opcion');
            $ruta = public_path('img/salle/img_preguntas');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
        }else{
            $fileName = '';
        }

        $opcion = DB::INSERT("INSERT INTO `salle_opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`,`n_evaluacion`) VALUES ($request->id_pregunta, '$request->opcion', '$fileName', $request->tipo, $request->cant_coincidencias,$request->n_evaluacion)");

        $opciones = DB::SELECT("SELECT * FROM salle_opciones_preguntas WHERE id_pregunta = $request->id_pregunta ORDER BY created_at");

        return $opciones;
    }


    public function editar_opcion_salle(Request $request)
    {
        $ruta = public_path('img/salle/img_preguntas');

        if($request->file('img_opcion') && $request->file('img_opcion') != null && $request->file('img_opcion')!= 'null'){
            $file = $request->file('img_opcion');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            if( file_exists('img/salle/img_preguntas/'.$request->img_opcion_old) && $request->img_pregunta_old != '' ){
                unlink('img/salle/img_preguntas/'.$request->img_opcion_old);
            }
        }else{
            $fileName = $request->img_opcion_old;
        }

        $opcion = DB::UPDATE("UPDATE `salle_opciones_preguntas` SET `opcion`='$request->opcion',`img_opcion`='$fileName',`tipo`=$request->tipo,`cant_coincidencias`=$request->cant_coincidencias,`n_evaluacion` = $request->n_evaluacion WHERE `id_opcion_pregunta`= $request->id_opcion_pregunta");

        $opciones = DB::SELECT("SELECT * FROM salle_opciones_preguntas WHERE id_pregunta = $request->id_pregunta ORDER BY created_at");

        return $opciones;

    }

    public function quitar_opcion_salle($id)
    {
        $opciones = DB::DELETE("DELETE FROM salle_opciones_preguntas WHERE id_opcion_pregunta = $id");
    }

    public function eliminar_pregunta_salle($id)
    {
        $pregunta = DB::UPDATE("UPDATE `salle_preguntas` SET `estado` = 0 WHERE `id_pregunta` = $id");

        return $pregunta;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($datos)
    {
        $info       = explode("*", $datos);
        $id         = $info[0];
        $tipoFiltro = $info[2];
        $preguntas = DB::SELECT("SELECT a.nombre_asignatura, p . *, t.nombre_tipo, t.indicaciones,
        t.descripcion_tipo, a.nombre_asignatura, a.cant_preguntas,
        a.estado as estado_asignatura, ar.nombre_area, ar.id_area,
        ar.estado as estado_area,pe.nombre as periodo
        FROM salle_preguntas p
        LEFT JOIN tipos_preguntas t ON p.id_tipo_pregunta = t.id_tipo_pregunta
        LEFT JOIN salle_asignaturas a ON p.id_asignatura = a.id_asignatura
        LEFT JOIN salle_areas ar ON a.id_area = ar.id_area
        LEFT JOIN salle_periodos_evaluacion pe ON ar.n_evaluacion = pe.id
        WHERE a.estado = 1
        AND ar.estado = 1
        AND p.estado = '$tipoFiltro'
        AND p.id_asignatura = '$id'
        ORDER BY p.id_pregunta DESC
        ");
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT * FROM `salle_opciones_preguntas`
                WHERE `id_pregunta` = ?",[$value->id_pregunta]);
                $data['items'][$key] = [
                    'pregunta' => $value,
                    'opciones' => $opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }

    public function opciones_pregunta_salle($id)
    {
        $opciones = DB::SELECT("SELECT p . *, t.nombre_tipo, t.indicaciones, t.descripcion_tipo FROM salle_preguntas p, tipos_preguntas t WHERE p.id_tipo_pregunta = t.id_tipo_pregunta");

        return $opciones;
    }


    public function cargar_opcion_vf_salle(Request $request)
    {
        if( $request->id_opcion ){
            $opcion = DB::DELETE("DELETE FROM `salle_opciones_preguntas` WHERE id_pregunta = $request->id_pregunta");
        }

        $opcion = DB::INSERT("INSERT INTO `salle_opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`) VALUES ($request->id_pregunta, '$request->opcion', '', $request->tipo, $request->cant_coincidencias)");

        if( $request->opcion == 'Verdadero' || $request->opcion == 'Si' ){
            if( $request->opcion == 'Verdadero' ){
                $nombre_opcion = 'Falso';
            }else{
                $nombre_opcion = 'No';
            }
            $opcion = DB::INSERT("INSERT INTO `salle_opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`) VALUES ($request->id_pregunta, '$nombre_opcion', '', 0, $request->cant_coincidencias)");
        }else{
            if( $request->opcion == 'Falso' ){
                $nombre_opcion = 'Verdadero';
            }else{
                $nombre_opcion = 'Si';
            }
            $opcion = DB::INSERT("INSERT INTO `salle_opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`) VALUES ($request->id_pregunta, '$nombre_opcion', '', 0, $request->cant_coincidencias)");
        }

        $opciones = DB::SELECT("SELECT * FROM salle_opciones_preguntas WHERE id_pregunta = $request->id_pregunta ORDER BY created_at");

        return $opciones;
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


    /*********************************************************************************
    ************** EVALUACIONES SALLE
    ********************************************************************************/
    public function salle_getConfiguracion($institucion,$n_evaluacion)
    {
        $configuracion = DB::SELECT("SELECT i.nombreInstitucion, i.direccionInstitucion,
        c.fecha_inicio, c.fecha_fin, c.cant_evaluaciones, c.ver_respuestas, c.observaciones,c.n_evaluacion
        FROM salle_configuracion c
        LEFT JOIN institucion i ON i.idInstitucion = c.institucion_id
        WHERE c.institucion_id = '$institucion'
        AND c.n_evaluacion  = '$n_evaluacion'
        ");
        // $configuracion = DB::SELECT("SELECT i.nombreInstitucion, i.direccionInstitucion, c.fecha_inicio,
        // c.fecha_fin, c.cant_evaluaciones, c.ver_respuestas, c.observaciones
        // FROM institucion i, salle_configuracion c
        // WHERE i.id_configuracion = c.id_configuracion
        // AND i.idInstitucion = $institucion
        // ");
        $fecha_actual = date("Y-m-d G:i:s");
        $horario_permitido = 0;
        // return $fecha_actual .'<'. $configuracion[0]->fecha_fin .'&&'. $fecha_actual .'>'. $configuracion[0]->fecha_inicio;
        if( $fecha_actual < $configuracion[0]->fecha_fin && $fecha_actual > $configuracion[0]->fecha_inicio ){
            $horario_permitido = 1;
        }
        return response()->json(['configuracion' => $configuracion, 'horario_permitido' => $horario_permitido]);
    }

    public function salle_finalizarEvaluacion(Request $request)
    {
        DB::beginTransaction(); // Inicia la transacción

        try {
            // Estado 2 = evaluación finalizada
            DB::UPDATE("UPDATE `salle_evaluaciones` SET `estado` = 2 WHERE `id_evaluacion` = ? AND `id_usuario` = ?", [
                $request->id_evaluacion,
                $request->id_usuario
            ]);

            // Actualiza la calificación final
            $this->salleRepository->updateCalificacionFinal($request->id_evaluacion);

            DB::commit(); // Si todo va bien, confirma la transacción
        } catch (\Exception $e) {
            DB::rollBack(); // Si ocurre un error, revierte la transacción
            // Puedes registrar el error o lanzar una excepción personalizada
            return ["status" => 0, "message" => 'Error al finalizar la evaluación: ', "error" => $e->getMessage()];
        }
    }

    public function evaluaciones_resueltas_salle($docente,$n_evaluacion)
    {
        // para mostrar lisatdo de evaluaciones resuletas en el perfil del docente
        $evaluaciones = DB::SELECT("SELECT * FROM salle_evaluaciones se
        WHERE se.id_usuario = ?
        AND n_evaluacion = '$n_evaluacion'
        AND se.estado = 2",[$docente]
        );
        return $evaluaciones;
    }
    public function configuracionXInstitucion($institucion_id,$n_evaluacion){
        $query = DB::SELECT("SELECT * FROM salle_configuracion c
        WHERE c.institucion_id = '$institucion_id'
        AND c.n_evaluacion  = '$n_evaluacion'
        ");
        return $query;
    }
    public function generar_evaluacion_salle($id_docente, $id_institucion, $n_evaluacion, $admin)
    {
        try {
            set_time_limit(60000);
            ini_set('max_execution_time', 60000);
            $fecha_actual = date("Y-m-d H:i:s");
            $configuracion = $this->configuracionXInstitucion($id_institucion, $n_evaluacion);

            if (!empty($configuracion)) {
                if ($fecha_actual < $configuracion[0]->fecha_fin && $fecha_actual > $configuracion[0]->fecha_inicio) {
                    return DB::transaction(function () use ($id_docente, $n_evaluacion, $configuracion, $admin, $fecha_actual) {
                        // Limpio la evaluación y cargo las nuevas asignaturas
                        $eval_doc = DB::SELECT("SELECT * FROM `salle_evaluaciones`
                        WHERE `id_usuario` = $id_docente
                        AND `estado` != 3
                        AND intentos = '0'
                        AND n_evaluacion = '$n_evaluacion'
                        ");

                        // Elimino la evaluación de previsualización
                        if (count($eval_doc) > 0) {
                            $preIdEvaluacion = $eval_doc[0]->id_evaluacion;
                            DB::DELETE("DELETE FROM salle_evaluaciones WHERE id_evaluacion = '$preIdEvaluacion'");
                            DB::DELETE("DELETE FROM salle_preguntas_evaluacion WHERE id_evaluacion = '$preIdEvaluacion'");
                        }

                        // Evaluaciones del docente que no estén eliminadas
                        $eval_doc = DB::SELECT("SELECT * FROM `salle_evaluaciones`
                        WHERE `id_usuario` = $id_docente
                        AND `estado` != 3
                        AND n_evaluacion = '$n_evaluacion'
                        ");

                        if (count($eval_doc) < $configuracion[0]->cant_evaluaciones) {
                            $evaluacion = new SalleEvaluaciones();
                            $evaluacion->id_usuario = $id_docente;
                            $evaluacion->n_evaluacion = $n_evaluacion;
                            $evaluacion->tipo_calificacion = 1;
                            $evaluacion->save();
                            $id_evaluacion = $evaluacion->id_evaluacion;

                            // Asignaturas no básicas
                            $asignaturas = DB::SELECT("SELECT sa.id_asignatura, sa.id_area, sa.cant_preguntas
                                FROM salle_asignaturas_has_docente sd, salle_asignaturas sa
                                WHERE sd.id_docente = '$id_docente'
                                AND sd.id_asignatura = sa.id_asignatura
                                AND sd.n_evaluacion  = '$n_evaluacion'
                                AND sa.estado = 1
                            ");

                            foreach ($asignaturas as $value_asignaturas) {
                                $preguntas = DB::SELECT("SELECT sp.id_pregunta
                                FROM salle_preguntas sp
                                WHERE sp.id_asignatura = $value_asignaturas->id_asignatura
                                AND sp.estado = 1
                                ORDER BY RAND()
                                LIMIT $value_asignaturas->cant_preguntas
                                ");
                                foreach ($preguntas as $value_preguntas) {
                                    DB::INSERT("INSERT INTO `salle_preguntas_evaluacion`(`id_evaluacion`, `id_pregunta`)
                                    VALUES (?,?)", [$id_evaluacion, $value_preguntas->id_pregunta]);
                                }
                            }

                            // Asignaturas básicas
                            $asignaturas_basicas = DB::SELECT("SELECT sa.id_asignatura, sa.id_area, sa.cant_preguntas
                                FROM salle_asignaturas sa, salle_areas sar
                                WHERE sa.id_area = sar.id_area
                                AND sar.area_basica = '1'
                                AND sa.estado = 1
                                AND sar.estado = 1
                                AND sar.n_evaluacion = '$n_evaluacion'
                            ");

                            foreach ($asignaturas_basicas as $value_basicas) {
                                $preguntas = DB::SELECT("SELECT sp.id_pregunta
                                FROM salle_preguntas sp
                                WHERE sp.id_asignatura = $value_basicas->id_asignatura
                                AND sp.estado = 1
                                ORDER BY RAND()
                                LIMIT $value_basicas->cant_preguntas
                                ");
                                foreach ($preguntas as $value_preguntas) {
                                    DB::INSERT("INSERT INTO `salle_preguntas_evaluacion`(`id_evaluacion`, `id_pregunta`)
                                    VALUES (?,?)", [$id_evaluacion, $value_preguntas->id_pregunta]);
                                }
                            }

                            return $this->obtener_evaluacion_salle($id_evaluacion, $id_docente, $n_evaluacion);
                        } else {
                            if ($eval_doc[0]->estado === 1) {
                                return $this->obtener_evaluacion_salle($eval_doc[0]->id_evaluacion, $id_docente, $n_evaluacion);
                            } else {
                                if ($admin == 1) {
                                    return $this->obtener_evaluacion_salle($eval_doc[0]->id_evaluacion, $id_docente, $n_evaluacion);
                                }
                                return 2; // Esta evaluación ya fue completada
                            }
                        }
                    });
                } else {
                    return 0; // Horario no permitido
                }
            } else {
                return 1; // No existe configuración para su institución
            }
        } catch (\Exception $e) {
            return ["status" => "0", "message" => "Error al generar la evaluación.", "error" => $e->getMessage()];
        }
    }

    public function obtenerCalificacion_salle($id_evaluacion){
        // return $this->salleRepository->updateCalificacionFinal($id_evaluacion);
        // Actualiza la calificación final
        $calificacionFinal              = 0;
        $totalPuntaje                   = 0;
        $cantidadPreguntasRespondidas   = 0;
        $cantidadPreguntas              = 0;
        //obtener el tipo de calificacion
        $getTipoCalificacion = SalleEvaluaciones::where('id_evaluacion', $id_evaluacion)->first();
        if(!$getTipoCalificacion){
            return ["status" => "0", "message"=> "No existe esa evaluación."];
        }
        $tipo_calificacion = $getTipoCalificacion->tipo_calificacion;
        //tipo_calificacion => 0 => la version anterior; 1 => solo puntaje por pregunta
        if($tipo_calificacion == 0){
           $calificacionFinal = $getTipoCalificacion->calificacion_total;
        }else{
            $query = SallePreguntasEvaluacion::where('id_evaluacion',$id_evaluacion)->get();
            foreach ($query as $key => $value) {
                $calificacionFinal += $value->calificacion_final;
            }
        }
        //obtener cantidad preguntas respondidas
        $getRespondidas =  DB::SELECT("SELECT DISTINCT  r.id_pregunta
        FROM
        salle_respuestas_preguntas r
        WHERE r.id_evaluacion = ?
        ",[$id_evaluacion]);

        $cantidadPreguntasRespondidas = count($getRespondidas);
        //total puntaje preguntas
        $query2 = DB::SELECT("SELECT DISTINCT * FROM salle_preguntas_evaluacion p
        LEFT JOIN salle_preguntas pp ON pp.id_pregunta = p.id_pregunta
        WHERE p.id_evaluacion = ?
        ",[$id_evaluacion]);
        $cantidadPreguntas = count($query2);
        foreach ($query2 as $key => $value) {
            $totalPuntaje += $value->puntaje_pregunta;
        }
        return ['calificacionFinal' => $calificacionFinal, 'totalPuntaje' => $totalPuntaje, 'cantidadPreguntasRespondidas' => $cantidadPreguntasRespondidas, 'cantidadPreguntas' => $cantidadPreguntas];
    }
    //api:get/f_estadoPreguntas/id_evaluacion
    public function f_estadoPreguntas($id_evaluacion){
         //obtener cantidad preguntas respondidas
         $getRespondidas =  DB::SELECT("SELECT DISTINCT  r.id_pregunta
         FROM
         salle_respuestas_preguntas r
         WHERE r.id_evaluacion = ?
         ",[$id_evaluacion]);

        $cantidadPreguntasRespondidas = count($getRespondidas);
        //total puntaje preguntas
        $query2 = DB::SELECT("SELECT DISTINCT * FROM salle_preguntas_evaluacion p
        LEFT JOIN salle_preguntas pp ON pp.id_pregunta = p.id_pregunta
        WHERE p.id_evaluacion = ?
        ",[$id_evaluacion]);
        $cantidadPreguntas = count($query2);
        return [
            "cantidadPreguntasRespondidas"  => $cantidadPreguntasRespondidas,
            "cantidadPreguntas"             => $cantidadPreguntas
        ];
    }
    // public function obtener_evaluacion_salle($id_evaluacion, $id_docente,$n_evaluacion){
    //     set_time_limit(600000);
    //     ini_set('max_execution_time', 600000);
    //     $data = array();
    //     $data_asignaturas = array();
    //     $data_preguntas = array();
    //     $areas = DB::SELECT("SELECT DISTINCT sar.id_area, sar.nombre_area, sar.descripcion_area,
    //     sar.descripcion_area, spe.id_evaluacion
    //     FROM salle_preguntas_evaluacion spe, salle_preguntas sp, salle_asignaturas sa, salle_areas sar
    //     WHERE spe.id_pregunta = sp.id_pregunta
    //     AND sp.id_asignatura = sa.id_asignatura
    //     AND sa.id_area = sar.id_area
    //     AND spe.id_evaluacion = '$id_evaluacion'
    //     AND sar.n_evaluacion = '$n_evaluacion'
    //     ORDER BY `sar`.`id_area` ASC;
    //     ");
    //     $evaluacion = DB::SELECT("SELECT * FROM salle_evaluaciones WHERE id_evaluacion = '$id_evaluacion' ");
    //     foreach ($areas as $key_areas => $value_areas) {
    //         $asignaturas = DB::SELECT("SELECT DISTINCT sa . *
    //         FROM salle_evaluaciones se, salle_preguntas_evaluacion spe, salle_preguntas sp, salle_asignaturas sa
    //         WHERE se.id_evaluacion = ?
    //         AND se.id_evaluacion = spe.id_evaluacion
    //         AND spe.id_pregunta = sp.id_pregunta
    //         AND sa.id_asignatura = sa.id_asignatura
    //         AND sa.id_area = ?",[$id_evaluacion, $value_areas->id_area]
    //         );
    //         foreach ($asignaturas as $key_asignaturas => $value_asignaturas) {
    //             $preguntas = DB::SELECT("SELECT sp . *, t.nombre_tipo, t.indicaciones, t.descripcion_tipo,
    //             (SELECT SUM(puntaje) FROM salle_respuestas_preguntas WHERE id_pregunta = sp.id_pregunta AND id_evaluacion = '$id_evaluacion') as 'total_pregunta'
    //             FROM salle_preguntas_evaluacion pe, salle_preguntas sp, tipos_preguntas t
    //             WHERE sp.id_tipo_pregunta = t.id_tipo_pregunta
    //             AND pe.id_evaluacion = ?
    //             AND pe.id_pregunta = sp.id_pregunta
    //             AND sp.id_asignatura = ?
    //             AND sp.estado = 1",[$id_evaluacion, $value_asignaturas->id_asignatura]
    //             );
    //             foreach ($preguntas as $key_preguntas => $value_preguntas) {
    //                 $opciones = DB::SELECT("SELECT *
    //                 FROM `salle_opciones_preguntas`
    //                 WHERE `id_pregunta` = ?",[$value_preguntas->id_pregunta]
    //                 );
    //                 $respuestas = DB::SELECT("SELECT *
    //                 FROM `salle_respuestas_preguntas`
    //                 WHERE `id_pregunta` = ?
    //                 AND id_usuario = ?
    //                 AND `id_evaluacion` = ?",[$value_preguntas->id_pregunta, $id_docente, $id_evaluacion]
    //                 );
    //                 $data_preguntas['preguntas'][$key_preguntas] = ['pregunta' => $value_preguntas, 'opciones' => $opciones, 'respuestas' => $respuestas];
    //                 $data_asignaturas['asignaturas'][$key_asignaturas] = ['asignatura' => $value_asignaturas, $data_preguntas];
    //             }
    //             $data['areas'][$key_areas] = [
    //                 'evaluacion' => $evaluacion,
    //                 'area' => $value_areas,
    //                 $data_asignaturas
    //             ];
    //         }
    //         $data_preguntas = [];
    //         $data_asignaturas = [];
    //     }
    //     return $data;
    // }

    public function salle_guardarSeleccion(Request $request){
        try{
            if( $request->tipo_pregunta == 1 ){
                // pregunta opcion multiple
                $opcion = DB::SELECT("SELECT * FROM `salle_respuestas_preguntas`
                WHERE `id_evaluacion`   = $request->id_evaluacion
                AND `id_pregunta`       = $request->id_pregunta
                AND `respuesta`         = $request->id_opcion_pregunta
                AND `id_usuario`        = $request->id_usuario
                ");
                if( count($opcion) > 0 ){
                    DB::DELETE("DELETE FROM `salle_respuestas_preguntas`
                    WHERE `id_respuesta_pregunta` = ?", [$opcion[0]->id_respuesta_pregunta]
                    );
                }else{
                    DB::INSERT("INSERT INTO `salle_respuestas_preguntas`(`id_evaluacion`, `id_pregunta`, `id_usuario`, `respuesta`, `puntaje`) VALUES (?, ?, ?, ?, ?)", [$request->id_evaluacion, $request->id_pregunta, $request->id_usuario, $request->id_opcion_pregunta, $request->puntaje_seleccion]);
                }
                //calificar las preguntas
                return $this->calificarPreguntaMultiple($request->id_pregunta,$request);
            }else{
                DB::DELETE("DELETE FROM `salle_respuestas_preguntas` WHERE `id_evaluacion` = ? AND `id_pregunta` = ? AND `id_usuario` = ?", [$request->id_evaluacion, $request->id_pregunta, $request->id_usuario]);
                //para que no se duplique la respuesta
                $opcion = DB::SELECT("SELECT * FROM `salle_respuestas_preguntas`
                WHERE `id_evaluacion`   = $request->id_evaluacion
                AND `id_pregunta`       = $request->id_pregunta
                AND `respuesta`         = $request->id_opcion_pregunta
                AND `id_usuario`        = $request->id_usuario
                ");
                if( count($opcion) > 0 ){
                    DB::DELETE("DELETE FROM `salle_respuestas_preguntas`
                    WHERE `id_respuesta_pregunta` = ?", [$opcion[0]->id_respuesta_pregunta]
                    );
                }else{
                    DB::INSERT("INSERT INTO `salle_respuestas_preguntas`(`id_evaluacion`, `id_pregunta`, `id_usuario`, `respuesta`, `puntaje`) VALUES (?, ?, ?, ?, ?)", [$request->id_evaluacion, $request->id_pregunta, $request->id_usuario, $request->id_opcion_pregunta, $request->puntaje_seleccion]);
                }
                //calificar las preguntas
                $this->calificarPreguntaNormal($request->id_pregunta,$request);
            }
            //calificar las preguntas
            return $request;
        }
        catch(\Exception $e){
            return ["status" => "0", "message" => $e->getMessage()] ;
        }

    }
    // public function obtener_evaluacion_salle($id_evaluacion, $id_docente,$n_evaluacion){
    //     set_time_limit(600000);
    //     ini_set('max_execution_time', 600000);
    //     $i_area = 0;
    //     $data = array();
    //     $data_asignaturas = array();
    //     $data_preguntas = array();
    //     $areas = DB::SELECT("SELECT DISTINCT sar.id_area, sar.nombre_area, sar.descripcion_area,
    //     sar.descripcion_area, spe.id_evaluacion
    //     FROM salle_preguntas_evaluacion spe, salle_preguntas sp, salle_asignaturas sa, salle_areas sar
    //     WHERE spe.id_pregunta = sp.id_pregunta
    //     AND sp.id_asignatura = sa.id_asignatura
    //     AND sa.id_area = sar.id_area
    //     AND spe.id_evaluacion = '$id_evaluacion'
    //     AND sar.n_evaluacion = '$n_evaluacion'
    //     ORDER BY `sar`.`id_area` ASC;
    //     ");
    //     $evaluacion = DB::SELECT("SELECT * FROM salle_evaluaciones WHERE id_evaluacion = '$id_evaluacion' ");
    //     foreach ($areas as $key_areas => $value_areas) {
    //         $asignaturas = DB::SELECT("SELECT DISTINCT sa . *
    //         FROM salle_evaluaciones se, salle_preguntas_evaluacion spe, salle_preguntas sp, salle_asignaturas sa
    //         WHERE se.id_evaluacion = ?
    //         AND se.id_evaluacion = spe.id_evaluacion
    //         AND spe.id_pregunta = sp.id_pregunta
    //         AND sa.id_asignatura = sa.id_asignatura
    //         AND sa.id_area = ?",[$id_evaluacion, $value_areas->id_area]
    //         );
    //         foreach ($asignaturas as $key_asignaturas => $value_asignaturas) {
    //             $preguntas = DB::SELECT("SELECT sp . *, t.nombre_tipo, t.indicaciones, t.descripcion_tipo,
    //             (SELECT SUM(puntaje) FROM salle_respuestas_preguntas WHERE id_pregunta = sp.id_pregunta AND id_evaluacion = '$id_evaluacion') as 'total_pregunta'
    //             FROM salle_preguntas_evaluacion pe, salle_preguntas sp, tipos_preguntas t
    //             WHERE sp.id_tipo_pregunta = t.id_tipo_pregunta
    //             AND pe.id_evaluacion = ?
    //             AND pe.id_pregunta = sp.id_pregunta
    //             AND sp.id_asignatura = ?
    //             AND sp.estado = 1",[$id_evaluacion, $value_asignaturas->id_asignatura]
    //             );
    //             foreach ($preguntas as $key_preguntas => $value_preguntas) {
    //                 $opciones = DB::SELECT("SELECT *
    //                 FROM `salle_opciones_preguntas`
    //                 WHERE `id_pregunta` = ?",[$value_preguntas->id_pregunta]
    //                 );
    //                 $respuestas = DB::SELECT("SELECT *
    //                 FROM `salle_respuestas_preguntas`
    //                 WHERE `id_pregunta` = ?
    //                 AND id_usuario = ?
    //                 AND `id_evaluacion` = ?",[$value_preguntas->id_pregunta, $id_docente, $id_evaluacion]
    //                 );
    //                 $data_preguntas['preguntas'][$key_preguntas] = ['pregunta' => $value_preguntas, 'opciones' => $opciones, 'respuestas' => $respuestas];
    //                 $data_asignaturas['asignaturas'][$key_asignaturas] = ['asignatura' => $value_asignaturas, $data_preguntas];
    //             }
    //             $data['areas'][$key_areas] = [
    //                 'evaluacion' => $evaluacion,
    //                 'area' => $value_areas,
    //                 $data_asignaturas
    //             ];
    //         }
    //         $data_preguntas = [];
    //         $data_asignaturas = [];
    //     }
    //     return $data;
    // }
    public function obtener_evaluacion_salle($id_evaluacion, $id_docente, $n_evaluacion)
    {
        $areas = SalleAreas::with([
            'asignaturas' => function ($query) {
                $query->where('estado', '1'); // Filtrar asignaturas con estado = '1'
            },
            'asignaturas.preguntas' => function ($query) use ($id_evaluacion) {
                $query->leftJoin('salle_preguntas_evaluacion as spe', 'spe.id_pregunta', '=', 'salle_preguntas.id_pregunta')
                    ->where('spe.id_evaluacion', $id_evaluacion);
            },
            'asignaturas.preguntas.opciones',
            'asignaturas.preguntas.respuestas' => function ($query) use ($id_docente, $id_evaluacion) {
                $query->where('salle_respuestas_preguntas.id_usuario', $id_docente)
                    ->where('salle_respuestas_preguntas.id_evaluacion', $id_evaluacion);
            },
            'asignaturas.preguntas.tipo',
        ])
        ->whereHas('asignaturas.preguntas.evaluaciones', function ($query) use ($id_evaluacion) {
            $query->where('salle_preguntas_evaluacion.id_evaluacion', $id_evaluacion);
        })
        ->where('salle_areas.n_evaluacion', $n_evaluacion)
        ->get();

        // Transformar los datos para incluir el id_evaluacion y el campo intentos al nivel del área
        $areas = $areas->map(function ($area) use ($id_evaluacion) {
            // Obtener el campo intentos desde la relación evaluaciones de las preguntas
            $intentos = $area->asignaturas->flatMap(function ($asignatura) use ($id_evaluacion) {
                return $asignatura->preguntas->flatMap(function ($pregunta) use ($id_evaluacion) {
                    return $pregunta->evaluaciones->where('id_evaluacion', $id_evaluacion)->pluck('intentos');
                });
            })->first() ?? null;

            return [
                'id_area' => $area->id_area,
                'nombre_area' => $area->nombre_area,
                'descripcion_area' => $area->descripcion_area,
                'id_evaluacion' => $id_evaluacion,
                'intentos' => $intentos, // Agregar el campo intentos aquí
                'asignaturas' => $area->asignaturas->map(function ($asignatura) use ($id_evaluacion) {
                    return [
                        'id_asignatura' => $asignatura->id_asignatura,
                        'nombre_asignatura' => $asignatura->nombre_asignatura,
                        'preguntas' => $asignatura->preguntas->map(function ($pregunta) use ($id_evaluacion) {
                            // Calcular el total_pregunta
                            $total_pregunta = $pregunta->respuestas->sum('puntaje');

                            return [
                                'id_pregunta' => $pregunta->id_pregunta,
                                'descripcion' => $pregunta->descripcion,
                                'puntaje_pregunta' => $pregunta->puntaje_pregunta,
                                'id_tipo_pregunta' => $pregunta->id_tipo_pregunta,
                                'img_pregunta' => $pregunta->img_pregunta,
                                'nombre_tipo' => $pregunta->tipo->nombre_tipo,
                                'total_pregunta' => $total_pregunta,
                                'opciones' => $pregunta->opciones,
                                'n_pregunta' => $pregunta->calificacion_final,
                                'respuestas' => $pregunta->respuestas->map(function ($respuesta) {
                                    return [
                                        'id_pregunta' => $respuesta->id_pregunta,
                                        'id_opcion_pregunta' => $respuesta->id_opcion_pregunta,
                                        'puntaje' => $respuesta->puntaje,
                                        'tipo' => $respuesta->tipo,
                                        'respuesta' => $respuesta->respuesta,
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ];
        });

        return $areas; // Devolvemos el resultado
    }



    public function calificarPreguntaMultiple($id_pregunta, $request)
    {
        DB::beginTransaction(); // Inicia la transacción
        try {
            // Obtener el puntaje total de la pregunta
            $pregunta = DB::table('salle_preguntas')->where('id_pregunta', $id_pregunta)->first();
            if (!$pregunta) {
                throw new Exception("La pregunta con ID $id_pregunta no existe.");
            }
            $puntaje = $pregunta->puntaje_pregunta;

            // Obtener la cantidad de respuestas correctas
            $cant_correctas = DB::table('salle_opciones_preguntas')
                ->where('id_pregunta', $id_pregunta)
                ->where('tipo', 1)
                ->count();
            if ($cant_correctas == 0) {
                throw new Exception("No hay respuestas correctas configuradas para la pregunta con ID $id_pregunta.");
            }

            // Calcular el puntaje por respuesta correcta
            $puntajexPregunta = $puntaje / $cant_correctas;

            // Obtener las respuestas del usuario para la evaluación y pregunta
            $respuestas = DB::table('salle_respuestas_preguntas')
                ->where('id_evaluacion', $request->id_evaluacion)
                ->where('id_pregunta', $id_pregunta)
                ->where('id_usuario', $request->id_usuario)
                ->get();

            if ($respuestas->isEmpty()) {
            }

            $totalPuntaje = 0;

            foreach ($respuestas as $respuesta) {
                // Verificar el tipo de la opción seleccionada
                $opcion = DB::table('salle_opciones_preguntas')
                    ->where('id_pregunta', $id_pregunta)
                    ->where('id_opcion_pregunta', $respuesta->respuesta)
                    ->first();

                if (!$opcion) {
                    throw new Exception("La opción seleccionada con ID {$respuesta->respuesta} no es válida.");
                }

                // Calcular el puntaje
                $puntaje = $opcion->tipo == 1 ? $puntajexPregunta : -$puntajexPregunta;
                $puntaje = number_format($puntaje, 3, '.', '');

                // Actualizar el puntaje de la respuesta
                DB::table('salle_respuestas_preguntas')
                    ->where('id_respuesta_pregunta', $respuesta->id_respuesta_pregunta)
                    ->update(['puntaje' => $puntaje]);

                // Sumar al puntaje total
                $totalPuntaje += (float)$puntaje;
            }

            // Redondear el puntaje total a 2 decimales
            $totalPuntajeRedondeado = round($totalPuntaje, 2);
            //si es menor a cero color cero
            if($totalPuntajeRedondeado < 0){
                $totalPuntajeRedondeado = 0;
            }
            // Actualizar la calificación final en la tabla salle_respuestas_preguntas
            DB::table('salle_preguntas_evaluacion')
                ->where('id_evaluacion', $request->id_evaluacion)
                ->where('id_pregunta', $id_pregunta)
                ->update(['calificacion_final' => $totalPuntajeRedondeado]);

            DB::commit(); // Confirmar la transacción

            return $totalPuntajeRedondeado;

        } catch (Exception $e) {
            DB::rollBack(); // Revertir la transacción en caso de error
            throw new Exception("Error al calificar la pregunta: " . $e->getMessage());
        }
    }
    public function calificarPreguntaNormal($id_pregunta, $request)
    {
        // Iniciar transacción
        DB::beginTransaction();

        try {
            // Obtener el puntaje de la pregunta
            $query = DB::SELECT("SELECT * FROM salle_preguntas WHERE id_pregunta = '$id_pregunta'");
            if (empty($query)) {
                throw new Exception("Pregunta no encontrada.");
            }
            $puntaje = $query[0]->puntaje_pregunta;

            // Obtener la cantidad de respuestas correctas
            $query = DB::SELECT("SELECT COUNT(*) as 'cant_correctas' FROM salle_opciones_preguntas WHERE id_pregunta = '$id_pregunta' AND tipo = 1");
            $cant_correctas = $query[0]->cant_correctas;

            if ($cant_correctas <= 0) {
                throw new Exception("No se encontraron respuestas correctas.");
            }

            // Dividir el puntaje entre la cantidad de respuestas correctas
            $puntajexPregunta = $puntaje / $cant_correctas;

            // Obtener las respuestas del usuario
            $respuestas = DB::SELECT("SELECT * FROM salle_respuestas_preguntas WHERE id_evaluacion = $request->id_evaluacion AND id_pregunta = $request->id_pregunta AND id_usuario = $request->id_usuario");
            if (empty($respuestas)) {
                throw new Exception("No se encontraron respuestas para este usuario.");
            }

            foreach ($respuestas as $key => $item) {
                // Calificar cada opción
                $opcion = DB::SELECT("SELECT * FROM salle_opciones_preguntas WHERE id_pregunta = $id_pregunta AND id_opcion_pregunta = $item->respuesta");
                if (empty($opcion)) {
                    throw new Exception("Opción no encontrada.");
                }

                // Asignar puntaje
                if ($opcion[0]->tipo == 1) {
                    $puntajeRespuesta = $puntajexPregunta;
                } else {
                    $puntajeRespuesta = 0;
                }

                // Puntaje con 3 decimales
                $puntajeRespuesta = number_format($puntajeRespuesta, 3, '.', '');

                // Actualizar puntaje en la base de datos
                DB::UPDATE("UPDATE salle_respuestas_preguntas SET puntaje = ? WHERE id_respuesta_pregunta = ?", [$puntajeRespuesta, $item->id_respuesta_pregunta]);
            }

            // Redondear el puntaje total a 2 decimales
            $totalPuntajeRedondeado = round($puntajeRespuesta, 2);

            //si es menor a cero color cero
            if($totalPuntajeRedondeado < 0){
                $totalPuntajeRedondeado = 0;
            }
            // Actualizar la calificación final en la tabla salle_preguntas_evaluacion
            DB::table('salle_preguntas_evaluacion')
                ->where('id_evaluacion', $request->id_evaluacion)
                ->where('id_pregunta', $id_pregunta)
                ->update(['calificacion_final' => $totalPuntajeRedondeado]);

            // Confirmar la transacción
            DB::commit();

            return $totalPuntajeRedondeado;

        } catch (Exception $e) {
            // Si ocurre un error, hacer rollback y lanzar una excepción
            DB::rollBack();
            // Lanzar una nueva excepción con el mensaje de error
            throw new Exception("Error al calificar la pregunta: " . $e->getMessage());
        }
    }

    public function salle_sincronizar_preguntas($asignatura1, $asignatura2, $usuario,$n_evaluacion){
        set_time_limit(60000);
        ini_set('max_execution_time', 60000);
        // se consulta las preguntas de la asignatura que se desea copiar
        $preguntas = DB::SELECT("SELECT * FROM `salle_preguntas` WHERE `id_asignatura` = $asignatura1");
        // se eliminan las preguntas de la asignatura a cargar las nuevas
        DB::DELETE("DELETE FROM `salle_preguntas` WHERE `id_asignatura` = $asignatura2");
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
            $preg_sync                      = new SallePreguntas();
            $preg_sync->id_asignatura       = $asignatura2;
            $preg_sync->id_tipo_pregunta    = $value->id_tipo_pregunta;
            $preg_sync->descripcion         = $value->descripcion;
            $preg_sync->img_pregunta        = $value->img_pregunta;
            $preg_sync->puntaje_pregunta    = $value->puntaje_pregunta;
            $preg_sync->estado              = $value->estado;
            $preg_sync->editor              = $usuario;
            $preg_sync->n_evaluacion        = $n_evaluacion;
            $preg_sync->save();
            // return $preg_sync->id_pregunta;
            // se consulta las opciones de la asignatura que se desea copiar
            $opciones = DB::SELECT("SELECT * FROM `salle_opciones_preguntas` WHERE `id_pregunta` = $value->id_pregunta");
                foreach ($opciones as $keyO => $valueO) {
                    DB::INSERT("INSERT INTO `salle_opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`) VALUES (?,?,?,?,?)",[$preg_sync->id_pregunta, $valueO->opcion, $valueO->img_opcion, $valueO->tipo, $valueO->cant_coincidencias]);
                }
            }
            return "1"; // sincronizacion correcta
        }else{
            return "0"; // no hay preguntas para sicronizar
        }
    }
    // esta funcion se ejecuta solo cuando haya inconsistencias en las calificaciones
    public function validar_puntajes(){
        $respuestas = DB::SELECT("SELECT DISTINCT sr.id_usuario, sr.id_respuesta_pregunta, sr.id_pregunta, sr.respuesta, sr.puntaje, sp.puntaje_pregunta, so.tipo, if(sr.puntaje>0 AND so.tipo = 0, 'mal' ,'bien') as 'valid' FROM salle_respuestas_preguntas sr, salle_preguntas sp, salle_opciones_preguntas so WHERE sr.id_pregunta = sp.id_pregunta AND sp.id_pregunta = so.id_pregunta AND sr.respuesta = so.id_opcion_pregunta ORDER BY `sr`.`puntaje` ASC");
        foreach ($respuestas as $key => $value) {
            if( $value->valid == "mal" ){
                DB::UPDATE("UPDATE `salle_respuestas_preguntas` SET `puntaje`=0 WHERE `id_respuesta_pregunta` = ?",[$value->id_respuesta_pregunta]);
            }
        }
    }
    public function transformar_preguntas_salle(Request $request){
        $nuevo_tipo = 1;
        if( $request->id_tipo_pregunta == 1 ){ $nuevo_tipo = 5; }
        DB::UPDATE("UPDATE `salle_preguntas` SET `id_tipo_pregunta`= ? WHERE `id_pregunta` = ?",[$nuevo_tipo, $request->id_pregunta]);
    }
    public function salle_intento_eval(Request $request){
        $periodo = date('Y');
        // Se cambia a estado eliminado las evaluaciones que ya haya culminado el estudiante en el periodo actual.
        // Esto permite que se genere una nueva evaluacion.
        // DB::UPDATE("UPDATE `salle_evaluaciones` SET `estado` = 3
        // WHERE `id_usuario` = $request->idusuario
        // AND `estado` = 2
        // AND `created_at` LIKE '$periodo%'");
        DB::UPDATE("UPDATE `salle_evaluaciones` SET `estado` = 3
        WHERE `id_usuario` = '$request->idusuario'
        AND `estado` = 2
        AND `n_evaluacion` = '$request->n_evaluacion'");
    }
    //api para finalizar la evaluacion si el usuario se cambia de pestañas
    public function save_finalizar_evalIntentos(Request $request){
        //si el intento sube a 3 se finaliza la prueba
        $evaluacion     = SalleEvaluaciones::findOrFail($request->id_evaluacion);
        if($request->intentosEval == 2){
            $evaluacion->estado = '2';
        }else{
        }
        $getIntentos    = $evaluacion->intentos;
        $intentos       = 1 + $getIntentos;
        $evaluacion->intentos = $intentos;
        $evaluacion->save();
        return $evaluacion;
        //finalizar evaluacion
        //DB::UPDATE("UPDATE `salle_evaluaciones` SET `estado` = 2 WHERE `id_evaluacion` = ? AND `id_usuario` = ?",[$request->id_evaluacion, $request->id_usuario]);
    }
    public function modificar_periodo_codigos(){
        $codigos = [
            'MMA2-GZQPX51134581',
            'MM3-VVG953039',
            'MM3-GPJ690593',
            'MNA3-XHQB131257',
            'MNA3-DPWQ761894',
            'MNA3-WZMT736313'
        ];
            $codigos_no_econtrados = array();
            $cant_modificados = 0;
            for( $i=0; $i<count($codigos); $i++ ){
                DB::UPDATE("UPDATE IGNORE `codigoslibros` SET `id_periodo` = 16, `idusuario` = 29951 WHERE `codigo` = '?'", [$codigos[$i]]);
                $codigo = DB::SELECT("SELECT codigo FROM `codigoslibros` WHERE `codigo` = ?", [$codigos[$i]]);

                if( !$codigo ){
                    array_push($codigos_no_econtrados,$codigos[$i]);
                }else{
                    $cant_modificados++;
                }
            }

            // return $codigos_no_econtrados;
            return $cant_modificados;
    }
    //=====METODOS PARA MOVER LAS PREGUNTAS====
    //API POST/salle/ActivarPreguntas
    public function ActivarPreguntas(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $miArrayDeObjetos   = json_decode($request->data_preguntas);
        $contador           = 0;
        $arregloPreguntasActivas   = collect();
        foreach($miArrayDeObjetos as $key => $item){
            $preg_sync          = SallePreguntas::findOrFail($item->id_pregunta);
            $preg_sync->estado  = '1';
            $preg_sync->editor  = $request->user_created;
            $preg_sync->save();
            if($preg_sync){
                $pregunta = $this->getPreguntaXId($item->id_pregunta);
                $arregloPreguntasActivas->push($pregunta);
                $contador++;
            }
        }
        if(count($arregloPreguntasActivas) == 0){
            return[
                "ingresadas"              => $contador,
                "arregloPreguntasActivas"   => [],
            ];
        }else{
            return[
                "ingresadas"              => $contador,
                "arregloPreguntasActivas"   => array_merge(...$arregloPreguntasActivas->all()),
            ];
        }
    }
    public function getPreguntaXId($id_pregunta){
        $query = DB::SELECT("SELECT * FROM salle_preguntas p
        WHERE p.id_pregunta   = '$id_pregunta'
        ");
        return $query;
    }
    //API:POST/salle/MoverPreguntas
    public function MoverPreguntas(Request $request)
    {
        try {
            // Iniciar la transacción
            DB::beginTransaction();

            // Variables
            $id_asignatura                  = $request->id_asignatura;
            $n_evaluacion                   = $request->n_evaluacion;
            $user_created                   = $request->user_created;
            $contador                       = 0;
            $contadorNoIngresado            = 0;
            $arregloPreguntasNoIngresadas   = collect();

            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);

            $miArrayDeObjetos = json_decode($request->data_preguntas);
            foreach ($miArrayDeObjetos as $key => $item) {
                // Validar que la pregunta no esté ingresada
                $pregunta = $this->getPreguntaXNombre($request, $item);
                if (empty($pregunta) || $request->duplicate == 1) {
                    $preg_sync = new SallePreguntas();
                    $preg_sync->id_asignatura           = $id_asignatura;
                    $preg_sync->id_tipo_pregunta        = $item->id_tipo_pregunta;
                    $preg_sync->descripcion             = $item->descripcion;
                    $preg_sync->img_pregunta            = $item->img_pregunta;
                    $preg_sync->puntaje_pregunta        = $item->puntaje_pregunta;
                    $preg_sync->estado                  = 1;
                    $preg_sync->editor                  = $user_created;
                    $preg_sync->n_evaluacion            = $n_evaluacion;
                    $preg_sync->save();

                    // Crear opciones de preguntas
                    if ($preg_sync) {
                        $this->crearOpcionesPregunta($request, $item, $preg_sync);
                        $contador++;
                    }
                } else {
                    $arregloPreguntasNoIngresadas->push($pregunta);
                    $contadorNoIngresado++;
                }
            }

            // Confirmar la transacción
            DB::commit();

            if (count($arregloPreguntasNoIngresadas) == 0) {
                return [
                    "ingresadas"              => $contador,
                    "PreguntasNoIngresadas"   => [],
                ];
            } else {
                return [
                    "ingresadas"              => $contador,
                    "PreguntasNoIngresadas"   => array_merge(...$arregloPreguntasNoIngresadas->all()),
                ];
            }
        } catch (\Exception $ex) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            return ["status" => "0", "message" => "Hubo problemas con la conexión al servidor", "error" => $ex->getMessage()];
        }
    }
    public function crearOpcionesPregunta($request,$item,$pregunta){
        $idPreguntaNueva = $pregunta->id_pregunta;
        $query = DB::SELECT("SELECT * FROM salle_opciones_preguntas  o
        WHERE o.id_pregunta = '$item->id_pregunta'
        ");
        //si la pregunta tiene opciones las elimino
        if(count($query) > 0){
            foreach($query as $key => $item){
                //validar que si la opcion no existe se cree
                $validateOpcion = $this->getOpcionXPregunta($idPreguntaNueva,$item);
                if(empty($validateOpcion)){
                    $opcion = new SallePreguntasOpcion();
                    $opcion->id_pregunta        = $idPreguntaNueva;
                    $opcion->opcion             = $item->opcion;
                    $opcion->img_opcion         = $item->img_opcion;
                    $opcion->tipo               = $item->tipo;
                    $opcion->cant_coincidencias = $item->cant_coincidencias;
                    $opcion->n_evaluacion       = $request->n_evaluacion;
                    $opcion->save();
                }
            }
        }
    }
    public function getOpcionXPregunta($idPreguntaNueva,$item){
        $query = DB::SELECT("SELECT * FROM salle_opciones_preguntas  o
        WHERE o.id_pregunta = '$idPreguntaNueva'
        AND o.opcion        = '$item->opcion'
        ");
        return $query;
    }
    public function getPreguntaXNombre($request, $item)
    {
        $query = DB::select("
            SELECT *
            FROM salle_preguntas p
            WHERE p.id_asignatura   = ?
            AND p.n_evaluacion      = ?
            AND p.id_tipo_pregunta  = ?
            AND p.descripcion       = ?
        ", [
            $request->id_asignatura,
            $request->n_evaluacion,
            $item->id_tipo_pregunta,
            $item->descripcion,
        ]);

        return $query;
    }
    //API:GET/salle/exportAllPreguntas/periodo
    //traer todas las preguntas para exportar
    public function exportAllPreguntas($periodo){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $contadorArea = 0;
        $areas = DB::SELECT("SELECT a.*,
        IF(a.estado = '1','Activo','Desactivado') as estadoArea,
        p.nombre as periodo
        FROM salle_areas a
        LEFT JOIN salle_periodos_evaluacion p ON a.n_evaluacion = p.id
        WHERE a.n_evaluacion = '$periodo'
        AND a.estado         = '1'
        limit 1
        ");
        $datos = [];
        foreach($areas as $key => $item){
            $asignaturas = DB::SELECT("SELECT * FROM salle_asignaturas a WHERE a.id_area = '$item->id_area'");
            foreach($asignaturas as $key2 => $item2 ){
                $preguntas = DB::SELECT("SELECT a.nombre_asignatura, p . *, t.nombre_tipo, t.indicaciones,
                t.descripcion_tipo, a.nombre_asignatura, a.cant_preguntas,
                a.estado as estado_asignatura, ar.nombre_area, ar.id_area,
                ar.estado as estado_area,pe.nombre as periodo
                FROM salle_preguntas p
                LEFT JOIN tipos_preguntas t ON p.id_tipo_pregunta = t.id_tipo_pregunta
                LEFT JOIN salle_asignaturas a ON p.id_asignatura = a.id_asignatura
                LEFT JOIN salle_areas ar ON a.id_area = ar.id_area
                LEFT JOIN salle_periodos_evaluacion pe ON ar.n_evaluacion = pe.id
                WHERE a.estado = 1
                AND ar.estado = 1
                AND p.estado = 1
                AND p.id_asignatura = '$item2->id_asignatura'
                AND ar.n_evaluacion  = '$periodo'
                ");
                foreach ($preguntas as $key3 => $value) {
                    $opciones = DB::SELECT("SELECT * FROM `salle_opciones_preguntas`
                    WHERE `id_pregunta` = ?",[$value->id_pregunta]);
                    $data[$key3] = [
                        'pregunta'          => $value,
                        'opciones'          => $opciones,
                    ];
                }
                $datoAsignatura[$key2] = [
                    "nombre_asignatura" => $item2->nombre_asignatura,
                    "preguntas"         => $data
                ];
            }
            $datos[$contadorArea] =[
                "id_area"           =>  $item->id_area,
                "nombre_area"       =>  $item->nombre_area,
                "periodo"           =>  $item->periodo,
                "n_evaluacion"      =>  $item->n_evaluacion,
                "asignatura"        =>  $datoAsignatura,
            ];
            $contadorArea++;
        }
        return $datos;
    }
    public function exportAllPreguntasXAsignatura(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $periodo        = $request->periodo;
        $area           = $request->area;
        $contadorArea   = 0;
        //tipo 0 = por areas; 1 = por asignaturas
        $tipo           = $request->tipo;
        $areas = DB::SELECT("SELECT a.*,
        IF(a.estado = '1','Activo','Desactivado') as estadoArea,
        p.nombre as periodo
        FROM salle_areas a
        LEFT JOIN salle_periodos_evaluacion p ON a.n_evaluacion = p.id
        WHERE a.n_evaluacion = '$periodo'
        AND a.id_area        = '$area'
        ");
        $datos = [];
        foreach($areas as $key => $item){
            //tipo 0 = por areas; 1 = por asignaturas
            if($tipo == 1){
                $asignaturas = DB::SELECT("SELECT * FROM salle_asignaturas a WHERE a.id_area = '$item->id_area' AND  a.id_asignatura = '$request->asignatura'");
            }else{
                $asignaturas = DB::SELECT("SELECT * FROM salle_asignaturas a WHERE a.id_area = '$item->id_area'");
            }
            foreach($asignaturas as $key2 => $item2 ){
                $preguntas = DB::SELECT("SELECT a.nombre_asignatura, p . *, t.nombre_tipo, t.indicaciones,
                t.descripcion_tipo, a.nombre_asignatura, a.cant_preguntas,
                a.estado as estado_asignatura, ar.nombre_area, ar.id_area,
                ar.estado as estado_area,pe.nombre as periodo
                FROM salle_preguntas p
                LEFT JOIN tipos_preguntas t ON p.id_tipo_pregunta = t.id_tipo_pregunta
                LEFT JOIN salle_asignaturas a ON p.id_asignatura = a.id_asignatura
                LEFT JOIN salle_areas ar ON a.id_area = ar.id_area
                LEFT JOIN salle_periodos_evaluacion pe ON ar.n_evaluacion = pe.id
                WHERE a.estado = 1
                AND ar.estado = 1
                AND p.estado = 1
                AND p.id_asignatura = '$item2->id_asignatura'
                ");
                foreach ($preguntas as $key3 => $value) {
                    $opciones = DB::SELECT("SELECT * FROM `salle_opciones_preguntas`
                    WHERE `id_pregunta` = ?",[$value->id_pregunta]);
                    $data[$key3] = [
                        'pregunta'          => $value,
                        'opciones'          => $opciones,
                    ];
                }
                $datoAsignatura[$key2] = [
                    "nombre_asignatura" => $item2->nombre_asignatura,
                    "preguntas"         => $data
                ];
            }
            $datos[$contadorArea] =[
                "id_area"           =>  $item->id_area,
                "nombre_area"       =>  $item->nombre_area,
                "periodo"           =>  $item->periodo,
                "n_evaluacion"      =>  $item->n_evaluacion,
                "asignatura"        =>  $datoAsignatura,
            ];
            $contadorArea++;
        }
        return $datos;
    }
    //api:post/obtenerCalificacionSalle
    public function obtenerCalificacionSalle(Request $request){
        try{
            $totalPuntaje     = 0;
            $id_evaluacion    = $request->id_evaluacion;
            $id_area          = $request->id_area;
            // Obtener el tipo de calificación
            $query = $this->salleRepository->getCalificacionPreguntasXArea($id_evaluacion,$id_area);
            //total puntaje por area
            $query2 = $this->salleRepository->puntajePorArea($id_evaluacion,$id_area);
            foreach ($query2 as $key => $value) {
                $totalPuntaje += $value->puntaje;
            }
            return [
                'arrayCalificacionFinal' => $query,
                'totalPuntaje'           => $totalPuntaje
            ];
        }catch(\Exception $e){
            return ["status" => "0", "message" => "Error al obtener la calificación", "error" => $e->getMessage()];
        }
    }
}
