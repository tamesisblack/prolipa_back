<?php
namespace App\Repositories\Evaluaciones;

use App\Models\EvaluacionInstitucionAsignada;
use App\Models\Preguntas;
use App\Repositories\BaseRepository;
use DB;
class  PreguntasRepository extends BaseRepository
{
    public function __construct(Preguntas $preguntas)
    {
        parent::__construct($preguntas);
    }
    public function preguntasOnlyDocentesSinTipo($request){
        $preguntas = [];
        $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
        preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
        evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
        FROM preguntas, evaluaciones, temas, tipos_preguntas ti
        WHERE preguntas.idusuario = '$request->usuario'
        AND ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
        AND evaluaciones.id_asignatura = temas.id_asignatura
        AND preguntas.id_tema = temas.id
        AND preguntas.estado = 1
        AND evaluaciones.id = '$request->evaluacion'
        AND temas.estado=1
        AND temas.unidad = '$request->unidad'
        AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo) ORDER BY preguntas.descripcion DESC");
        return $preguntas;
    }
    public function preguntasOnlyDocentesTipo($request){
        $preguntas = [];
        $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
        preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
        evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
        FROM preguntas, evaluaciones, temas, tipos_preguntas ti
        WHERE preguntas.idusuario = $request->usuario
        AND ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
        AND evaluaciones.id_asignatura = temas.id_asignatura
        AND preguntas.id_tema = temas.id
        AND preguntas.estado = 1
        AND evaluaciones.id = $request->evaluacion
        AND temas.estado=1
        AND temas.unidad = $request->unidad
        AND preguntas.id_tipo_pregunta = $request->tipo
        AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
        ORDER BY preguntas.descripcion DESC");
        return $preguntas;
    }
    public function obtenerPreguntasConfiguradas($request){
        $institucion_id = $request->institucion_id;
        //TODAS LAS PREGUNTAS
        if($request->preguntasAll){
            if( $request->tipo == 'null' ){
                $preguntasAdmin = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id,preguntas.id_tema,
                preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta,preguntas.puntaje_pregunta,
                evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion,temas.unidad, evaluaciones.id_asignatura
                FROM preguntas, evaluaciones, temas, tipos_preguntas ti,usuario u
                WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                AND evaluaciones.id_asignatura = temas.id_asignatura
                AND preguntas.id_tema = temas.id
                AND preguntas.estado = 1
                AND evaluaciones.id = $request->evaluacion
                AND temas.estado=1
                AND temas.unidad = $request->unidad
                AND u.idusuario = preguntas.idusuario
                AND preguntas.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = $institucion_id AND estado = '1')
                AND u.id_group = '1'
                AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                ORDER BY preguntas.descripcion DESC");
                $preguntasProfesor = $this->preguntasOnlyDocentesSinTipo($request);
                $preguntas = array_merge($preguntasAdmin, $preguntasProfesor);
            }
            //tipo
            else{
                $preguntasAdmin = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
                evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                FROM preguntas, evaluaciones, temas, tipos_preguntas ti,usuario u
                WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                AND evaluaciones.id_asignatura = temas.id_asignatura
                AND preguntas.id_tema = temas.id
                AND preguntas.estado = 1
                AND evaluaciones.id = $request->evaluacion
                AND temas.estado=1
                AND u.idusuario = preguntas.idusuario
                AND u.id_group = '1'
                AND temas.unidad = $request->unidad
                AND preguntas.id_tipo_pregunta = $request->tipo
                AND preguntas.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = $institucion_id AND estado = '1')
                AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                ORDER BY preguntas.descripcion DESC");
                $preguntasProfesor = $this->preguntasOnlyDocentesTipo($request);
                $preguntas = array_merge($preguntasAdmin, $preguntasProfesor);
            }
        }
        //PREGUNTAS SOLO PROLIPA
        if($request->preguntasProlipa){
            if( $request->tipo == 'null' ){
                $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                    preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
                    evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                    FROM preguntas, evaluaciones, temas, tipos_preguntas ti, usuario u
                    WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                    AND evaluaciones.id_asignatura = temas.id_asignatura
                    AND preguntas.id_tema = temas.id
                    AND preguntas.estado = 1
                    AND preguntas.idusuario = u.idusuario
                    AND u.idusuario != $request->usuario
                    AND evaluaciones.id = $request->evaluacion
                    AND temas.estado=1
                    AND temas.unidad = $request->unidad
                    AND preguntas.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = $institucion_id AND estado = '1')
                    AND u.id_group = '1'
                    AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                    ORDER BY preguntas.descripcion DESC");
                }else{
                    $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                    preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta, evaluaciones.nombre_evaluacion,
                    temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                    FROM preguntas, evaluaciones, temas, tipos_preguntas ti, usuario u
                    WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                    AND evaluaciones.id_asignatura = temas.id_asignatura
                    AND preguntas.id_tema = temas.id
                    AND preguntas.estado = 1
                    AND preguntas.idusuario = u.idusuario
                    AND u.idusuario != $request->usuario
                    AND evaluaciones.id = $request->evaluacion
                    AND preguntas.id_tipo_pregunta = $request->tipo
                    AND temas.estado=1 AND temas.unidad = $request->unidad
                    AND u.id_group = '1'
                    AND preguntas.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = $institucion_id AND estado = '1')
                    AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                    ORDER BY preguntas.descripcion DESC");
                }
        }
        //PREGUNTAS SOLO DEL DOCENTE
        if($request->preguntasDocentes){
            if( $request->tipo == 'null' ){
                $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                    preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
                    evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                    FROM preguntas, evaluaciones, temas, tipos_preguntas ti
                    WHERE preguntas.idusuario = $request->usuario
                    AND ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                    AND evaluaciones.id_asignatura = temas.id_asignatura
                    AND preguntas.id_tema = temas.id
                    AND preguntas.estado = 1
                    AND evaluaciones.id = $request->evaluacion
                    AND temas.estado=1
                    AND temas.unidad = $request->unidad
                    AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo) ORDER BY preguntas.descripcion DESC");
            }else{
                $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
                evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                FROM preguntas, evaluaciones, temas, tipos_preguntas ti
                WHERE preguntas.idusuario = $request->usuario
                AND ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                AND evaluaciones.id_asignatura = temas.id_asignatura
                AND preguntas.id_tema = temas.id
                AND preguntas.estado = 1
                AND evaluaciones.id = $request->evaluacion
                AND temas.estado=1
                AND temas.unidad = $request->unidad
                AND preguntas.id_tipo_pregunta = $request->tipo
                AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                ORDER BY preguntas.descripcion DESC");
            }
        }

        $data = [];
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo, cant_coincidencias
                FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data['items'][$key] = [
                    'id' => $value->id,
                    'id_tema' => $value->id_tema,
                    'unidad' => $value->unidad,
                    'nombre_tema' => $value->nombre_tema,
                    'nombre_evaluacion' => $value->nombre_evaluacion,
                    'id_asignatura' => $value->id_asignatura,
                    'descripcion' => $value->descripcion,
                    'img_pregunta' => $value->img_pregunta,
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'nombre_tipo' => $value->nombre_tipo,
                    'descripcion_tipo' => $value->descripcion_tipo,
                    'puntaje_pregunta' => $value->puntaje_pregunta,
                    'clasificacion' => $value->clasificacion,
                    'opciones'=>$opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    public function obtenerPreguntasSinConfigurarar($request){
        $institucion_id = $request->institucion_id;
        //TODAS LAS PREGUNTAS
        if($request->preguntasAll){
            if( $request->tipo == 'null' ){
                $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id,preguntas.id_tema,
                preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta,preguntas.puntaje_pregunta,
                evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion,temas.unidad, evaluaciones.id_asignatura
                FROM preguntas, evaluaciones, temas, tipos_preguntas ti,usuario u
                WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                AND evaluaciones.id_asignatura = temas.id_asignatura
                AND preguntas.id_tema = temas.id
                AND preguntas.estado = 1
                AND evaluaciones.id = $request->evaluacion
                AND temas.estado=1
                AND temas.unidad = $request->unidad
                AND u.idusuario = preguntas.idusuario
                AND (u.id_group = 1 OR preguntas.idusuario  = '$request->idusuario')
                AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                ORDER BY preguntas.descripcion DESC");
            }
            //tipo
            else{
                $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
                evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                FROM preguntas, evaluaciones, temas, tipos_preguntas ti,usuario u
                WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                AND evaluaciones.id_asignatura = temas.id_asignatura
                AND preguntas.id_tema = temas.id
                AND preguntas.estado = 1
                AND evaluaciones.id = $request->evaluacion
                AND temas.estado=1
                AND temas.unidad = $request->unidad
                AND u.idusuario = preguntas.idusuario
                AND preguntas.id_tipo_pregunta = $request->tipo
                AND (u.id_group = 1 OR preguntas.idusuario  = '$request->idusuario')
                AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                ORDER BY preguntas.descripcion DESC");
            }
        }
        //PREGUNTAS SOLO PROLIPA
        if($request->preguntasProlipa){
            if( $request->tipo == 'null' ){
                $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                    preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
                    evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                    FROM preguntas, evaluaciones, temas, tipos_preguntas ti, usuario u
                    WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                    AND evaluaciones.id_asignatura = temas.id_asignatura
                    AND preguntas.id_tema = temas.id
                    AND preguntas.estado = 1
                    AND preguntas.idusuario = u.idusuario
                    AND u.idusuario != $request->usuario
                    AND evaluaciones.id = $request->evaluacion
                    AND temas.estado=1
                    AND temas.unidad = $request->unidad
                    AND u.id_group = '1'
                    AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                    ORDER BY preguntas.descripcion DESC");
                }else{
                    $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                    preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta, evaluaciones.nombre_evaluacion,
                    temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                    FROM preguntas, evaluaciones, temas, tipos_preguntas ti, usuario u
                    WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                    AND evaluaciones.id_asignatura = temas.id_asignatura
                    AND preguntas.id_tema = temas.id
                    AND preguntas.estado = 1
                    AND preguntas.idusuario = u.idusuario
                    AND u.idusuario != $request->usuario
                    AND evaluaciones.id = $request->evaluacion
                    AND preguntas.id_tipo_pregunta = $request->tipo
                    AND temas.estado=1 AND temas.unidad = $request->unidad
                    AND u.id_group = '1'
                    AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                    ORDER BY preguntas.descripcion DESC");
                }
        }
        //PREGUNTAS SOLO DEL DOCENTE
        if($request->preguntasDocentes){
            if( $request->tipo == 'null' ){
                $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                    preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
                    evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                    FROM preguntas, evaluaciones, temas, tipos_preguntas ti
                    WHERE preguntas.idusuario = $request->usuario
                    AND ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                    AND evaluaciones.id_asignatura = temas.id_asignatura
                    AND preguntas.id_tema = temas.id
                    AND preguntas.estado = 1
                    AND evaluaciones.id = $request->evaluacion
                    AND temas.estado=1
                    AND temas.unidad = $request->unidad
                    AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo) ORDER BY preguntas.descripcion DESC");
            }else{
                $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
                preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
                evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
                FROM preguntas, evaluaciones, temas, tipos_preguntas ti
                WHERE preguntas.idusuario = $request->usuario
                AND ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
                AND evaluaciones.id_asignatura = temas.id_asignatura
                AND preguntas.id_tema = temas.id
                AND preguntas.estado = 1
                AND evaluaciones.id = $request->evaluacion
                AND temas.estado=1
                AND temas.unidad = $request->unidad
                AND preguntas.id_tipo_pregunta = $request->tipo
                AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
                ORDER BY preguntas.descripcion DESC");
            }
        }

        $data = [];
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo, cant_coincidencias
                FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data['items'][$key] = [
                    'id' => $value->id,
                    'id_tema' => $value->id_tema,
                    'unidad' => $value->unidad,
                    'nombre_tema' => $value->nombre_tema,
                    'nombre_evaluacion' => $value->nombre_evaluacion,
                    'id_asignatura' => $value->id_asignatura,
                    'descripcion' => $value->descripcion,
                    'img_pregunta' => $value->img_pregunta,
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'nombre_tipo' => $value->nombre_tipo,
                    'descripcion_tipo' => $value->descripcion_tipo,
                    'puntaje_pregunta' => $value->puntaje_pregunta,
                    'clasificacion' => $value->clasificacion,
                    'opciones'=>$opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    ///===========DOCENTES TIPOS DE BANCOS CONFIGURADOS=========
    public function preguntasOnlyDocentesBanco($request){
        $institucion_id = $request->institucion_id;
        $preguntas = [];
        $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo,
            p.id, p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta,
            p.puntaje_pregunta, te.nombre_tema, p.idusuario,
            CONCAT(u.nombres,' ',u.apellidos) as editor,p.estado
            FROM preguntas p, tipos_preguntas ti, temas te,usuario u
            WHERE p.id_tema = $request->id
            AND p.id_tipo_pregunta = ti.id_tipo_pregunta
            AND p.idusuario = $request->usuario
            AND p.id_tema = te.id
            AND p.estado = 1
            AND p.idusuario = u.idusuario
            ORDER BY p.descripcion DESC
        ");
        return $preguntas;
    }
    public function preguntasBancoConfiguradas($request){
        $preguntas     = [];
        $institucion_id = $request->institucion_id;
        if($request->tipobanco == 'todos'){
            $preguntasAdmin = DB::SELECT("SELECT ti.nombre_tipo,
                ti.descripcion_tipo, p.id, p.id_tema, p.descripcion,
                p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario,
                CONCAT(u.nombres, ' ',u.apellidos) as editor,p.estado
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = '$request->id'
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.id_tema = te.id
                AND p.estado = 1
                AND p.idusuario = u.idusuario
                AND p.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = '$institucion_id' AND estado = '1')
                AND u.id_group = 1
                ORDER BY p.descripcion DESC
            ");
            $preguntasProfesor = $this->preguntasOnlyDocentesBanco($request);
            $preguntas = array_merge($preguntasAdmin, $preguntasProfesor);
        }
        //prolipa
        if($request->tipobanco == 'prolipa'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo,
                p.id, p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario,
                CONCAT(u.nombres,' ',u.apellidos) as editor,p.estado
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = $request->id
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.idusuario != $request->usuario
                AND p.id_tema = te.id
                AND p.estado = 1
                AND p.idusuario = u.idusuario
                AND u.id_group = 1
                AND p.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = $institucion_id AND estado = '1')
                ORDER BY p.descripcion DESC
            ");
        }
        //docente
        if($request->tipobanco == 'docente'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo,
                p.id, p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario,
                CONCAT(u.nombres,' ',u.apellidos) as editor,p.estado
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = $request->id
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.idusuario = $request->usuario
                AND p.id_tema = te.id
                AND p.estado = 1
                AND p.idusuario = u.idusuario
                ORDER BY p.descripcion DESC
            ");
        }
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta,
                opcion, img_opcion, tipo, cant_coincidencias
                FROM opciones_preguntas
                WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data['items'][$key] = [
                    'id'                => $value->id,
                    'idusuario'         => $value->idusuario,
                    'id_tema'           => $value->id_tema,
                    'nombre_tema'       => $value->nombre_tema,
                    'descripcion'       => $value->descripcion,
                    'img_pregunta'      => $value->img_pregunta,
                    'id_tipo_pregunta'  => $value->id_tipo_pregunta,
                    'nombre_tipo'       => $value->nombre_tipo,
                    'descripcion_tipo'  => $value->descripcion_tipo,
                    'puntaje_pregunta'  => $value->puntaje_pregunta,
                    'idusuario'         => $value->idusuario,
                    "editor"            => $value->editor,
                    "estado"            => $value->estado,
                    'opciones'          => $opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    ///===========DOCENTES TIPOS DE BANCOS SIN CONFIGURAR=========
    public function preguntasBancoSinConfigurar($request){
        $institucion_id = $request->institucion_id;
        $preguntas = [];
        if($request->tipobanco == 'todos'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo,
                ti.descripcion_tipo, p.id, p.id_tema, p.descripcion,
                p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario,
                CONCAT(u.nombres, ' ',u.apellidos) as editor,p.estado
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = $request->id
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.id_tema = te.id
                AND p.estado = 1
                AND p.idusuario = u.idusuario
                AND (u.id_group = 1 OR p.idusuario = '$request->usuario')
                ORDER BY p.descripcion DESC
            ");
        }
        //prolipa
        if($request->tipobanco == 'prolipa'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo,
                p.id, p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario,
                CONCAT(u.nombres,' ',u.apellidos) as editor,p.estado
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = $request->id
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.idusuario != $request->usuario
                AND p.id_tema = te.id
                AND p.estado = 1
                AND p.idusuario = u.idusuario
                AND u.id_group = 1
                ORDER BY p.descripcion DESC
            ");
        }
        if($request->tipobanco == 'docente'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo,
                p.id, p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario,
                CONCAT(u.nombres,' ',u.apellidos) as editor,p.estado
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = $request->id
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.idusuario = $request->usuario
                AND p.id_tema = te.id
                AND p.estado = 1
                AND p.idusuario = u.idusuario
                ORDER BY p.descripcion DESC
            ");
        }
        $data   = [];
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta,
                opcion, img_opcion, tipo, cant_coincidencias
                FROM opciones_preguntas
                WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data['items'][$key] = [
                    'id'                => $value->id,
                    'idusuario'         => $value->idusuario,
                    'id_tema'           => $value->id_tema,
                    'nombre_tema'       => $value->nombre_tema,
                    'descripcion'       => $value->descripcion,
                    'img_pregunta'      => $value->img_pregunta,
                    'id_tipo_pregunta'  => $value->id_tipo_pregunta,
                    'nombre_tipo'       => $value->nombre_tipo,
                    'descripcion_tipo'  => $value->descripcion_tipo,
                    'puntaje_pregunta'  => $value->puntaje_pregunta,
                    'idusuario'         => $value->idusuario,
                    "editor"            => $value->editor,
                    "estado"            => $value->estado,
                    'opciones'          => $opciones,
                ];
            }
        }else{
            return "no entro";
            $data = [];
        }
        return $data;
    }
    ///===========DOCENTES TIPOS DE PREGUNTAS CONFIGURADOS=========
    public function preguntasOnlyTipo($request){
        $institucion_id = $request->institucion_id;
        $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, p.id,
        p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta, p.puntaje_pregunta,
        te.nombre_tema, p.idusuario
        FROM preguntas p, tipos_preguntas ti, temas te
        WHERE p.id_tema = $request->tema
        AND p.id_tipo_pregunta = ti.id_tipo_pregunta
        AND p.idusuario = $request->usuario
        AND p.id_tema = te.id AND p.estado = 1
        AND p.id_tipo_pregunta = $request->tipo
        ORDER BY p.descripcion DESC");
        return $preguntas;
    }
    public function preguntasTipoConfiguradas($request){
        $institucion_id = $request->institucion_id;
        if($request->tipobanco == 'todos'){
            $preguntasAdmin = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, p.id, p.id_tema, p.descripcion, p.img_pregunta,
            p.id_tipo_pregunta, p.puntaje_pregunta, te.nombre_tema, p.idusuario
            FROM preguntas p, tipos_preguntas ti, temas te,usuario u
            WHERE p.id_tema = $request->tema
            AND p.id_tipo_pregunta = ti.id_tipo_pregunta
            AND p.id_tema = te.id
            AND p.estado = 1
            AND p.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = $institucion_id AND estado = '1')
            AND p.id_tipo_pregunta = $request->tipo
            AND u.idusuario = p.idusuario
            AND u.id_group = 1
            ORDER BY p.descripcion DESC");
            $preguntasProfesor = $this->preguntasOnlyTipo($request);
            $preguntas = array_merge($preguntasAdmin, $preguntasProfesor);
        }
        //prolipa
        if($request->tipobanco == 'prolipa'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, p.id, p.id_tema, p.descripcion, p.img_pregunta,
            p.id_tipo_pregunta, p.puntaje_pregunta, te.nombre_tema, p.idusuario
            FROM preguntas p, tipos_preguntas ti, temas te,usuario u
            WHERE p.id_tema = $request->tema
            AND p.id_tipo_pregunta = ti.id_tipo_pregunta
            AND p.idusuario != $request->usuario
            AND p.id_tema = te.id
            AND p.estado = 1
            AND u.idusuario = p.idusuario
            AND u.id_group = 1
            AND preguntas.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = $institucion_id AND estado = '1')
            AND p.id_tipo_pregunta = $request->tipo
            ORDER BY p.descripcion DESC");
        }
        //docente
        if($request->tipobanco == 'docente'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, p.id,
            p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta, p.puntaje_pregunta,
            te.nombre_tema, p.idusuario
            FROM preguntas p, tipos_preguntas ti, temas te
            WHERE p.id_tema = $request->tema
            AND p.id_tipo_pregunta = ti.id_tipo_pregunta
            AND p.idusuario = $request->usuario
            AND p.id_tema = te.id AND p.estado = 1
            AND p.id_tipo_pregunta = $request->tipo
            ORDER BY p.descripcion DESC");
        }
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo, cant_coincidencias FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data['items'][$key] = [
                    'id' => $value->id,
                    'id_tema' => $value->id_tema,
                    'idusuario' => $value->idusuario,
                    'nombre_tema' => $value->nombre_tema,
                    'descripcion' => $value->descripcion,
                    'img_pregunta' => $value->img_pregunta,
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'nombre_tipo' => $value->nombre_tipo,
                    'descripcion_tipo' => $value->descripcion_tipo,
                    'puntaje_pregunta' => $value->puntaje_pregunta,
                    'opciones'=>$opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    public function preguntasTipoSinConfigurar($request){
        $institucion_id = $request->institucion_id;
        $preguntas      = [];
        if($request->tipobanco == 'todos'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, p.id, p.id_tema, p.descripcion, p.img_pregunta,
            p.id_tipo_pregunta, p.puntaje_pregunta, te.nombre_tema, p.idusuario
            FROM preguntas p, tipos_preguntas ti, temas te,usuario u
            WHERE p.id_tema = $request->tema
            AND p.id_tipo_pregunta = ti.id_tipo_pregunta
            AND p.id_tema = te.id
            AND p.estado = 1
            AND p.idusuario = u.idusuario
            AND (u.id_group = 1 OR p.idusuario = '$request->usuario')
            AND p.id_tipo_pregunta = $request->tipo
            ORDER BY p.descripcion DESC");
        }
        //prolipa
        if($request->tipobanco == 'prolipa'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, p.id, p.id_tema, p.descripcion, p.img_pregunta,
            p.id_tipo_pregunta, p.puntaje_pregunta, te.nombre_tema, p.idusuario
            FROM preguntas p, tipos_preguntas ti, temas te,usuario u
            WHERE p.id_tema = $request->tema
            AND p.id_tipo_pregunta = ti.id_tipo_pregunta
            AND p.idusuario != $request->usuario
            AND p.id_tema = te.id
            AND p.estado = 1
            AND p.id_tipo_pregunta = $request->tipo
            AND p.idusuario = u.idusuario
            AND u.id_group = 1
            ORDER BY p.descripcion DESC");
        }
        //docente
        if($request->tipobanco == 'docente'){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, p.id,
            p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta, p.puntaje_pregunta,
            te.nombre_tema, p.idusuario
            FROM preguntas p, tipos_preguntas ti, temas te
            WHERE p.id_tema = $request->tema
            AND p.id_tipo_pregunta = ti.id_tipo_pregunta
            AND p.idusuario = $request->usuario
            AND p.id_tema = te.id AND p.estado = 1
            AND p.id_tipo_pregunta = $request->tipo
            ORDER BY p.descripcion DESC");
        }
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo, cant_coincidencias FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data['items'][$key] = [
                    'id' => $value->id,
                    'id_tema' => $value->id_tema,
                    'idusuario' => $value->idusuario,
                    'nombre_tema' => $value->nombre_tema,
                    'descripcion' => $value->descripcion,
                    'img_pregunta' => $value->img_pregunta,
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'nombre_tipo' => $value->nombre_tipo,
                    'descripcion_tipo' => $value->descripcion_tipo,
                    'puntaje_pregunta' => $value->puntaje_pregunta,
                    'opciones'=>$opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    //====PREGUNTAS X EVALUACION TODAS
    public function preguntasInstitucionesAsignadasTodas($institucion_id){
        $preguntas = EvaluacionInstitucionAsignada::where('institucion_id', $institucion_id)->where('estado', '1')->get();
        return $preguntas;
    }
    public function preguntasInstitucionesAsignatura($institucion_id,$id_asignatura){
        $preguntas      = [];
        $preguntas = EvaluacionInstitucionAsignada::query()
        ->select( DB::RAW("CONCAT(ed.nombres, ' ', ed.apellidos) as editor"),'pa.id AS idAsignado', 'pa.created_at', 'pa.pregunta_id',
        'ti.nombre_tipo', 'ti.descripcion_tipo', 'p.id', 'p.id_tema', 'p.descripcion', 'p.img_pregunta',
        'p.id_tipo_pregunta', 'p.puntaje_pregunta', 'te.nombre_tema', 'p.idusuario','te.unidad')
        ->from('institucion_evaluacion_asignada as pa')
        ->leftjoin('preguntas as p', 'pa.pregunta_id', '=', 'p.id')
        ->leftjoin('tipos_preguntas as ti', 'p.id_tipo_pregunta', '=', 'ti.id_tipo_pregunta')
        ->leftjoin('temas as te', 'p.id_tema', '=', 'te.id')
        ->leftjoin('usuario as u', 'p.idusuario', '=', 'u.idusuario')
        ->leftjoin('usuario as ed','pa.user_created','=','ed.idusuario')
        ->where('pa.institucion_id', $institucion_id)
        ->where('pa.estado', '1')
        ->where('p.estado', 1)
        ->where('u.id_group', 1)
        ->where('te.id_asignatura', $id_asignatura)
        ->orderByDesc('p.descripcion')
        ->get();
        return $preguntas;
    }
    //====PREGUNTAS X INSTITUCION y por asignatura
    public function preguntasInstitucionesAsignadas($request){
        $institucion_id = $request->institucion_id;
        $preguntas      = [];
        $preguntas = EvaluacionInstitucionAsignada::query()
        ->select( DB::RAW("CONCAT(ed.nombres, ' ', ed.apellidos) as editor"),'pa.id AS idAsignado', 'pa.created_at', 'pa.pregunta_id',
        'ti.nombre_tipo', 'ti.descripcion_tipo', 'p.id', 'p.id_tema', 'p.descripcion', 'p.img_pregunta',
        'p.id_tipo_pregunta', 'p.puntaje_pregunta', 'te.nombre_tema', 'p.idusuario','te.unidad')
        ->from('institucion_evaluacion_asignada as pa')
        ->leftjoin('preguntas as p', 'pa.pregunta_id', '=', 'p.id')
        ->leftjoin('tipos_preguntas as ti', 'p.id_tipo_pregunta', '=', 'ti.id_tipo_pregunta')
        ->leftjoin('temas as te', 'p.id_tema', '=', 'te.id')
        ->leftjoin('usuario as u', 'p.idusuario', '=', 'u.idusuario')
        ->leftjoin('usuario as ed','pa.user_created','=','ed.idusuario')
        ->where('pa.institucion_id', $institucion_id)
        ->where('pa.estado', '1')
        ->where('p.estado', 1)
        ->where('u.id_group', 1)
        ->where('te.id_asignatura', $request->id_asignatura)
        ->orderByDesc('p.descripcion')
        ->get();
    // Ahora $asignaciones contiene los resultados de la consulta.

        $data = [];
         if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo, cant_coincidencias
                FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data[$key] = [
                    'id' => $value->id,
                    'id_tema' => $value->id_tema,
                    'idusuario' => $value->idusuario,
                    'nombre_tema' => $value->nombre_tema,
                    'descripcion' => $value->descripcion,
                    'img_pregunta' => $value->img_pregunta,
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'nombre_tipo' => $value->nombre_tipo,
                    'descripcion_tipo' => $value->descripcion_tipo,
                    'puntaje_pregunta' => $value->puntaje_pregunta,
                    'opciones'=>$opciones,
                    'editor' => $value->editor,
                    'unidad' => $value->unidad,
                    'idAsignado' => $value->idAsignado,
                    'created_at' => date('Y-m-d H:i:s', strtotime($value->created_at)),
                    'updated_at' => $value->updated_at,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
      ////PREGUNTAS ALEATORIAS PERSONALIZADA
      public function obtenerPreguntaAleatoriaPersonalizada($tipo, $evaluacion, $unidad, $i, $intentos,$institucion_id)
      {
        $pregunta = DB::SELECT("SELECT p.id
        FROM preguntas p, evaluaciones e, temas t, usuario u
        WHERE p.id_tipo_pregunta=?
        AND p.estado=1
        AND p.id_tema = t.id
        AND t.id_asignatura = e.id_asignatura
        AND e.id = ?
        AND t.unidad = ?
        AND p.id NOT IN (SELECT pr.id_pregunta FROM pre_evas pr WHERE pr.id_evaluacion = $evaluacion AND pr.grupo = $i)
        AND p.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = '$institucion_id' AND estado = '1')
        AND p.idusuario = u.idusuario
        AND u.id_group = 1
        ORDER BY RAND() LIMIT 1",[$tipo, $evaluacion, $unidad]);
        $this->guardarPreguntaRand($pregunta, $tipo, $evaluacion, $unidad, $i, $intentos,$institucion_id);
      }
      public function guardarPreguntaRand($pregunta, $tipo, $evaluacion, $unidad, $i, $intentos,$institucion_id)
      {
          if(!empty($pregunta)){
              foreach ($pregunta as $key => $value) {
                  $id_pregunta = $value->id;
              }
              DB::INSERT("INSERT INTO pre_evas(`id_evaluacion`, `id_pregunta`, `grupo`) VALUES ($evaluacion, $id_pregunta, $i)");
          }else{
              $intentos++;
              if( $intentos < 10 ){
                  $this->obtenerPreguntaAleatoriaPersonalizada($tipo, $evaluacion, $unidad, $i, $intentos,$institucion_id);
              }
          }
      }
      //fin de preguntas aleatorias personalizadas
}
?>
