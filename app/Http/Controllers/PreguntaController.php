<?php

namespace App\Http\Controllers;

use App\Models\EvaluacionInstitucionAsignada;
use App\Models\Institucion;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;///instanciamos base de datos para poder hacer consultas con varias tablas

use Illuminate\Provider\Image;

use Illuminate\Support\Facades\File;

use App\Models\Preguntas;//modelo Evaluaciones.php
use App\Repositories\Evaluaciones\PreguntasRepository;

class PreguntaController extends Controller
{
    protected $preguntasRepository;

    public function __construct(PreguntasRepository $preguntasRepository)
{
    $this->preguntasRepository = $preguntasRepository;
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema, preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta, evaluaciones.nombre_evaluacion, temas.nombre_tema FROM preguntas, evaluaciones, temas, tipos_preguntas ti WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta AND evaluaciones.id_asignatura = temas.id_asignatura AND preguntas.id_tema = temas.id AND preguntas.estado = 1 ORDER BY preguntas.descripcion");

        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo, cant_coincidencias FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data['items'][$key] = [
                    'id' => $value->id,
                    'id_tema' => $value->id_tema,
                    'nombre_tema' => $value->nombre_tema,
                    'nombre_evaluacion' => $value->nombre_evaluacion,
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
    {//agregar-editar

        $ruta = public_path('img/img_preguntas');

        if( $request->id ){

            //$pregunta = Preguntas::find($request->id);

            if($request->file('img_pregunta') && $request->file('img_pregunta') != null && $request->file('img_pregunta')!= 'null'){
                $file = $request->file('img_pregunta');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                if( file_exists('img/img_preguntas/'.$request->img_pregunta_old) && $request->img_pregunta_old != '' ){
                    unlink('img/img_preguntas/'.$request->img_pregunta_old);
                }
            }else{
                $fileName = $request->img_pregunta_old;
            }

            $preguntas = DB::UPDATE("UPDATE `preguntas` SET `id_tema`=$request->tema,`descripcion`='$request->descripcion',`img_pregunta`='$fileName', `puntaje_pregunta`=$request->puntaje_pregunta, `idusuario`=$request->idusuario WHERE `id`=$request->id");

            return $preguntas;
        }else{

            $pregunta = new Preguntas();

            if($request->file('img_pregunta')){
                $file = $request->file('img_pregunta');
                $ruta = public_path('img/img_preguntas');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
            }else{
                $fileName = '';
            }

            $pregunta->descripcion = $request->descripcion;
            $pregunta->id_tema = $request->tema;
            $pregunta->id_tipo_pregunta = $request->id_tipo_pregunta;
            $pregunta->img_pregunta = $fileName;
            $pregunta->puntaje_pregunta = $request->puntaje_pregunta;
            $pregunta->idusuario = $request->idusuario;

            $pregunta->save();

            return $pregunta;

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
        $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema, preguntas.idusuario, preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta, evaluaciones.nombre_evaluacion, temas.nombre_tema, evaluaciones.id_asignatura FROM preguntas, evaluaciones, temas, tipos_preguntas ti WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta AND evaluaciones.id_asignatura = temas.id_asignatura AND preguntas.id_tema = temas.id AND evaluaciones.id = $id AND preguntas.estado = 1 AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $id) ORDER BY preguntas.descripcion DESC");

        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, cant_coincidencias, tipo FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data['items'][$key] = [
                    'id' => $value->id,
                    'idusuario' => $value->idusuario,
                    'id_tema' => $value->id_tema,
                    'nombre_tema' => $value->nombre_tema,
                    'nombre_evaluacion' => $value->nombre_evaluacion,
                    'id_asignatura' => $value->id_asignatura,
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
    public function preguntasxUnidadytema(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $temas = json_decode($request->temas);
        $datos = [];
        foreach($temas as $key2 => $item2){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo,
                ti.descripcion_tipo, p.id, p.id_tema, p.descripcion,
                p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = '$item2->id'
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.id_tema = te.id
                AND p.estado = 1
                AND u.idusuario = p.idusuario
                AND u.id_group = '1'
                ORDER BY p.descripcion
                DESC
            ");
            if(count($preguntas)>0){
                foreach ($preguntas as $key => $value) {
                    $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta,
                    opcion, img_opcion, tipo, cant_coincidencias
                    FROM opciones_preguntas
                    WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                    $data[$key] = [
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
                        'opciones'          => $opciones,
                    ];
                }
            }else{
                $data = [];
            }
            $dataEnviar = [];
            if (count($data) > 0) {
                // Convertir $data en una colección si no lo es ya
                $dataColeccion = collect($data);
                // Aplicar el filtro utilizando el método where de la colección
                $dataEnviar = $dataColeccion->where('id_tema', $item2->id)->all();
            } else {
                $dataEnviar = [];
            }
            $datos[$key2] = [
                "idTema"        => $item2->id,
                "nombre_tema"   => $item2->nombre_tema,
                "unidad"        => $item2->unidad,
                "items"         => $dataEnviar
            ];
        }
        $colección = collect($datos);
        $resultado = $colección->filter(function ($objeto) {
            return count($objeto['items']) > 0;
        });
        //no duplicados
        return $resultado->values();
    }
    public function preguntasUnidad($temas){


    }
    function tiene_elementos($objeto) {
        return count($objeto['valores']) > 0;
    }
    //api:post/preguntas_x_institucion
    public function preguntas_x_institucion(Request $request){
       return $this->preguntasRepository->preguntasInstitucionesAsignadas($request);
    }
    //api:get/getCountQuestions
    public function getCountQuestions(Request $request){
        $query =  $this->preguntasRepository->preguntasInstitucionesAsignadasTodas($request->idinstitucion);
        if(count($query) == 0){
            return [];
        }
        //solo traer el pregunta_id
        $colección = collect($query);
        $resultado = $colección->map(function ($objeto) {
            return [
                'id' => $objeto['pregunta_id']
            ];
        });
        return $resultado->all();
    }
    //api:get/getCountQuestionsXasignatura?idinstitucion=433&id_asignatura=10
    public function getCountQuestionsXasignatura(Request $request){
        $query =  $this->preguntasRepository->preguntasInstitucionesAsignatura($request->idinstitucion,$request->id_asignatura);
        if(count($query) == 0){
            return [];
        }
        //solo traer el pregunta_id
        $colección = collect($query);
        $resultado = $colección->map(function ($objeto) {
            return [
                'id' => $objeto['pregunta_id']
            ];
        });
        return $resultado->all();
    }
    public function preguntas_x_tema_Docente(Request $request){
        $id_group                       = $request->id_group;
        $institucion_id                 = $request->institucion_id;
        $evaluacion_personalizada       = 0;
        //si es profesor
        if($id_group == 6 || $id_group == 10){
            $getInstitucion = Institucion::findOrfail($institucion_id);
            $evaluacion_personalizada   = $getInstitucion->evaluacion_personalizada;
        }
        //si hay evaluacion personalizada esta activada en la institucion
        if($evaluacion_personalizada == 1){ return $this->preguntasRepository->preguntasBancoConfiguradas($request);    }
        else                              { return $this->preguntasRepository->preguntasBancoSinConfigurar($request); }
    }
    public function preguntasxtema(Request $request)
    {
        switch ($request->tipobanco) {
        case "todos":
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo,
                ti.descripcion_tipo, p.id, p.id_tema, p.descripcion,
                p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario,
                CONCAT(u.nombres, ' ',u.apellidos) as editor,p.estado,p.created_at,p.updated_at
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = $request->id
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.id_tema = te.id
                -- AND p.estado = 1
                AND p.idusuario = u.idusuario
                AND (u.id_group = 1 OR p.idusuario = '$request->usuario')
                ORDER BY p.id DESC
            ");
            break;
        case "prolipa":
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo,
                p.id, p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario,
                CONCAT(u.nombres,' ',u.apellidos) as editor,p.estado,p.created_at,p.updated_at
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = $request->id
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.idusuario != $request->usuario
                AND p.id_tema = te.id
                -- AND p.estado = 1
                AND p.idusuario = u.idusuario
                AND u.id_group = 1
                ORDER BY p.id DESC
            ");
            break;
        default:
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo,
                p.id, p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta,
                p.puntaje_pregunta, te.nombre_tema, p.idusuario,
                CONCAT(u.nombres,' ',u.apellidos) as editor,p.estado,p.created_at,p.updated_at
                FROM preguntas p, tipos_preguntas ti, temas te,usuario u
                WHERE p.id_tema = $request->id
                AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                AND p.idusuario = $request->usuario
                AND p.id_tema = te.id
                -- AND p.estado = 1
                AND p.idusuario = u.idusuario
                ORDER BY p.id DESC
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
                    "created_at"        => $value->created_at,
                    "updated_at"        => $value->updated_at,
                    'opciones'          => $opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }
    public function preguntastipo(Request $request)
    {
        //docente
        if($request->id_group == 6 || $request->id_group == 10){
            $id_group                       = $request->id_group;
            $institucion_id                 = $request->institucion_id;
            $evaluacion_personalizada       = 0;
            //si es profesor o director
            if($id_group == 6 || $id_group == 10){
                $getInstitucion = Institucion::findOrfail($institucion_id);
                $evaluacion_personalizada   = $getInstitucion->evaluacion_personalizada;
            }
            //si hay evaluacion personalizada esta activada en la institucion
            if($evaluacion_personalizada == 1){ return $this->preguntasRepository->preguntasTipoConfiguradas($request);    }
            else                              { return $this->preguntasRepository->preguntasTipoSinConfigurar($request); }
        }else{
            switch ($request->tipobanco) {
                case "todos":
                    $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, p.id, p.id_tema, p.descripcion, p.img_pregunta,
                    p.id_tipo_pregunta, p.puntaje_pregunta, te.nombre_tema, p.idusuario
                    FROM preguntas p, tipos_preguntas ti, temas te
                    WHERE p.id_tema = $request->tema
                    AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                    AND p.id_tema = te.id
                    AND p.estado = 1
                    AND p.id_tipo_pregunta = $request->tipo
                    ORDER BY p.descripcion DESC");
                    break;
                case "prolipa":
                    $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, p.id, p.id_tema, p.descripcion, p.img_pregunta,
                    p.id_tipo_pregunta, p.puntaje_pregunta, te.nombre_tema, p.idusuario
                    FROM preguntas p, tipos_preguntas ti, temas te
                    WHERE p.id_tema = $request->tema
                    AND p.id_tipo_pregunta = ti.id_tipo_pregunta
                    AND p.idusuario != $request->usuario
                    AND p.id_tema = te.id
                    AND p.estado = 1
                    AND p.id_tipo_pregunta = $request->tipo
                    ORDER BY p.descripcion DESC");
                    break;
                default:
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
    }
    public function preguntasxunidad(Request $request)
    {
        $id_group                       = $request->id_group;
        $institucion_id                 = $request->institucion_id;
        $evaluacion_personalizada       = 0;
        //si es profesor
        if($id_group == 6 || $id_group == 10){
            $getInstitucion = Institucion::findOrfail($institucion_id);
            $evaluacion_personalizada   = $getInstitucion->evaluacion_personalizada;
        }
        //si hay evaluacion personalizada esta activada en la institucion
        if($evaluacion_personalizada == 1){ return $this->preguntasRepository->obtenerPreguntasConfiguradas($request);    }
        else                              { return $this->preguntasRepository->obtenerPreguntasSinConfigurarar($request); }
    }


    // public function preguntasevaltipounidad(Request $request)
    // {
    //     $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
    //     preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
    //      evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
    //      FROM preguntas, evaluaciones, temas, tipos_preguntas ti
    //      WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
    //       AND evaluaciones.id_asignatura = temas.id_asignatura
    //        AND preguntas.id_tipo_pregunta = $request->tipo
    //         AND preguntas.id_tema = temas.id
    //         AND preguntas.estado = 1
    //          AND evaluaciones.id = $request->evaluacion
    //          AND temas.estado=1
    //           AND temas.unidad = $request->unidad
    //           AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
    //           ORDER BY preguntas.descripcion DESC");

    //     if(!empty($preguntas)){
    //         foreach ($preguntas as $key => $value) {
    //             $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo, cant_coincidencias
    //             FROM opciones_preguntas
    //             WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
    //             $data['items'][$key] = [
    //                 'id' => $value->id,
    //                 'id_tema' => $value->id_tema,
    //                 'unidad' => $value->unidad,
    //                 'nombre_tema' => $value->nombre_tema,
    //                 'nombre_evaluacion' => $value->nombre_evaluacion,
    //                 'id_asignatura' => $value->id_asignatura,
    //                 'descripcion' => $value->descripcion,
    //                 'img_pregunta' => $value->img_pregunta,
    //                 'id_tipo_pregunta' => $value->id_tipo_pregunta,
    //                 'nombre_tipo' => $value->nombre_tipo,
    //                 'descripcion_tipo' => $value->descripcion_tipo,
    //                 'puntaje_pregunta' => $value->puntaje_pregunta,
    //                 'clasificacion' => $value->clasificacion,
    //                 'opciones'=>$opciones,
    //             ];
    //         }
    //     }else{
    //         $data = [];
    //     }
    //     return $data;
    // }


    public function preguntasDocente($id)
    {
        $pregunta = DB::SELECT("SELECT preguntas.id, preguntas.id_tema, preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, evaluaciones.nombre_evaluacion, temas.nombre_tema FROM preguntas, evaluaciones, temas WHERE evaluaciones.id_asignatura = temas.id_asignatura AND preguntas.id_tema = temas.id AND preguntas.estado = 1 AND evaluaciones.id = $id ORDER BY preguntas.descripcion");

        return $pregunta;
    }

    public function cargarOpcion(Request $request)
    {
        $ruta = public_path('img/img_preguntas');

        if($request->file('img_opcion')){
            $file = $request->file('img_opcion');
            $ruta = public_path('img/img_preguntas');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
        }else{
            $fileName = '';
        }

        $opcion = DB::INSERT("INSERT INTO `opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`) VALUES ($request->id_pregunta, '$request->opcion', '$fileName', $request->tipo, $request->cant_coincidencias)");

        $opciones = DB::SELECT("SELECT * FROM opciones_preguntas WHERE id_pregunta = $request->id_pregunta ORDER BY created_at");

        return $opciones;
    }


    public function cargarOpcionDico(Request $request)
    {
        if( $request->id_opcion ){
            $opcion = DB::DELETE("DELETE FROM `opciones_preguntas` WHERE id_pregunta = $request->id_pregunta");
        }

        $opcion = DB::INSERT("INSERT INTO `opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`) VALUES ($request->id_pregunta, '$request->opcion', '', $request->tipo, $request->cant_coincidencias)");

        if( $request->opcion == 'Verdadero' || $request->opcion == 'Si' ){
            if( $request->opcion == 'Verdadero' ){
                $nombre_opcion = 'Falso';
            }else{
                $nombre_opcion = 'No';
            }
            $opcion = DB::INSERT("INSERT INTO `opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`) VALUES ($request->id_pregunta, '$nombre_opcion', '', 0, $request->cant_coincidencias)");
        }else{
            if( $request->opcion == 'Falso' ){
                $nombre_opcion = 'Verdadero';
            }else{
                $nombre_opcion = 'Si';
            }
            $opcion = DB::INSERT("INSERT INTO `opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`) VALUES ($request->id_pregunta, '$nombre_opcion', '', 0, $request->cant_coincidencias)");
        }

        $opciones = DB::SELECT("SELECT * FROM opciones_preguntas WHERE id_pregunta = $request->id_pregunta ORDER BY created_at");

        return $opciones;
    }


    public function editarOpcion(Request $request)
    {
        $ruta = public_path('img/img_preguntas');

        if($request->file('img_opcion') && $request->file('img_opcion') != null && $request->file('img_opcion')!= 'null'){
            $file = $request->file('img_opcion');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            if( file_exists('img/img_preguntas/'.$request->img_opcion_old) && $request->img_pregunta_old != '' ){
                unlink('img/img_preguntas/'.$request->img_opcion_old);
            }
        }else{
            $fileName = $request->img_opcion_old;
        }

        $opcion = DB::UPDATE("UPDATE `opciones_preguntas` SET `opcion`='$request->opcion',`img_opcion`='$fileName',`tipo`=$request->tipo,`cant_coincidencias`=$request->cant_coincidencias WHERE `id_opcion_pregunta`= $request->id_opcion_pregunta");

        $opciones = DB::SELECT("SELECT * FROM opciones_preguntas WHERE id_pregunta = $request->id_pregunta ORDER BY created_at");

        return $opciones;

    }


    public function quitarOpcion($id)
    {
        $opciones = DB::DELETE("DELETE FROM opciones_preguntas WHERE id_opcion_pregunta = $id");
    }


    public function verOpciones($id)
    {///se usa order by created at para clasificar la respuesta correcta en las preguntas Dicotonicas
        $opciones = DB::SELECT("SELECT * FROM opciones_preguntas WHERE id_pregunta = $id ORDER BY created_at");

        return $opciones;
    }


    public function eliminarPregunta($id)
    {//estado 0 = eliminado
        $pregunta = DB::UPDATE("UPDATE `preguntas` SET `estado`=0 WHERE `id`= $id");

        return $pregunta;
    }


    public function tipospreguntas($asignatura, $unidades,$grupo,$institucion)
    {
        $unidad_v = explode(",", $unidades);
        sort($unidad_v, SORT_NUMERIC);
        $max = $unidad_v[count($unidad_v)-1];
        $min = $unidad_v[0];

        if( !isset($max) ){
            $max = $min;
        }
        $id_group                       = $grupo;
        $institucion_id                 = $institucion;
        $evaluacion_personalizada       = 0;
        //si es profesor
        if($id_group == 6 || $id_group == 10){
            $getInstitucion = Institucion::findOrfail($institucion_id);
            $evaluacion_personalizada   = $getInstitucion->evaluacion_personalizada;
        }
        //si hay evaluacion personalizada esta activada en la institucion
        if($evaluacion_personalizada == 1){
            $getTipos = DB::SELECT("SELECT t.id_tipo_pregunta as id, t.nombre_tipo as label, t.puntaje FROM tipos_preguntas t");
            foreach ($getTipos as $key => $value) {
                $tipos = DB::SELECT("SELECT t.id_tipo_pregunta as id, t.nombre_tipo as label,
                COUNT(p.id_tipo_pregunta) AS cantidad, t.puntaje
                FROM tipos_preguntas t, preguntas p, temas te,usuario u
                WHERE t.id_tipo_pregunta = p.id_tipo_pregunta
                AND p.id_tema = te.id
                AND te.id_asignatura = '$asignatura'
                AND te.unidad >= '$min'
                AND te.unidad <= '$max'
                AND p.estado = 1
                AND p.idusuario = u.idusuario
                AND u.id_group = 1
                AND t.id_tipo_pregunta = $value->id
                AND p.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = $institucion_id AND estado = '1')
                GROUP BY p.id_tipo_pregunta");
                if(count($tipos) == 0){
                    $value->cantidad = 0;
                }else{
                    $value->cantidad = $tipos[0]->cantidad;
                }
            }
            return $getTipos;
        }
        else
        {
            $getTipos = DB::SELECT("SELECT t.id_tipo_pregunta as id, t.nombre_tipo as label, t.puntaje FROM tipos_preguntas t");
            foreach ($getTipos as $key => $value) {
                $tipos = DB::SELECT("SELECT t.id_tipo_pregunta as id, t.nombre_tipo as label,
                COUNT(p.id_tipo_pregunta) AS cantidad, t.puntaje
                FROM tipos_preguntas t, preguntas p, temas te,usuario u
                WHERE t.id_tipo_pregunta = p.id_tipo_pregunta
                AND p.id_tema = te.id
                AND te.id_asignatura = '$asignatura'
                AND te.unidad >= '$min'
                AND te.unidad <= '$max'
                AND p.estado = 1
                AND p.idusuario = u.idusuario
                AND u.id_group = 1
                AND t.id_tipo_pregunta = $value->id
                GROUP BY p.id_tipo_pregunta");
                if(count($tipos) == 0){
                    $value->cantidad = 0;
                }else{
                    $value->cantidad = $tipos[0]->cantidad;
                }
            }
            return $getTipos;

            // $tipos = DB::SELECT("SELECT t.id_tipo_pregunta as id, t.nombre_tipo as label,
            // COUNT(p.id_tipo_pregunta) AS cantidad, t.puntaje
            // FROM tipos_preguntas t, preguntas p, temas te,usuario u
            // WHERE t.id_tipo_pregunta = p.id_tipo_pregunta
            // AND p.id_tema = te.id
            // AND te.id_asignatura = '$asignatura'
            // AND te.unidad >= '$min'
            // AND te.unidad <= '$max'
            // AND p.estado = 1
            // AND p.idusuario = u.idusuario
            // AND u.id_group = 1
            // GROUP BY p.id_tipo_pregunta");
            // return $tipos;
         }
        // $tipos = DB::SELECT("CALL tipospreguntas_cantidad($asignatura, $min, $max)");

    }

    // public function tipospreguntas($asignatura, $unidades,$grupo,$institucion)
    // {
    //     $unidad_v = explode(",", $unidades);
    //     sort($unidad_v, SORT_NUMERIC);
    //     $max = $unidad_v[count($unidad_v)-1];
    //     $min = $unidad_v[0];

    //     if( !isset($max) ){
    //         $max = $min;
    //     }

    //     $id_group                       = $grupo;
    //     $institucion_id                 = $institucion;
    //     $evaluacion_personalizada       = 0;
    //     //si es profesor
    //     if($id_group == 6){
    //         $getInstitucion = Institucion::findOrfail($institucion_id);
    //         $evaluacion_personalizada   = $getInstitucion->evaluacion_personalizada;
    //     }
    //     //si hay evaluacion personalizada esta activada en la institucion
    //     if($evaluacion_personalizada == 1){
    //         $tipos = DB::SELECT("SELECT t.id_tipo_pregunta as id, t.nombre_tipo as label,
    //         COUNT(p.id_tipo_pregunta) AS cantidad, t.puntaje
    //         FROM tipos_preguntas t, preguntas p, temas te,usuario u
    //         WHERE t.id_tipo_pregunta = p.id_tipo_pregunta
    //         AND p.id_tema = te.id
    //         AND te.id_asignatura = '$asignatura'
    //         AND te.unidad >= '$min'
    //         AND te.unidad <= '$max'
    //         AND p.estado = 1
    //         AND p.idusuario = u.idusuario
    //         AND u.id_group = 1
    //         AND p.id IN (select pregunta_id from institucion_evaluacion_asignada where institucion_id = $institucion_id AND estado = '1')
    //         GROUP BY p.id_tipo_pregunta");
    //         return $tipos;
    //     }
    //     else
    //     {
    //         $tipos = DB::SELECT("SELECT t.id_tipo_pregunta as id, t.nombre_tipo as label,
    //         COUNT(p.id_tipo_pregunta) AS cantidad, t.puntaje
    //         FROM tipos_preguntas t, preguntas p, temas te,usuario u
    //         WHERE t.id_tipo_pregunta = p.id_tipo_pregunta
    //         AND p.id_tema = te.id
    //         AND te.id_asignatura = '$asignatura'
    //         AND te.unidad >= '$min'
    //         AND te.unidad <= '$max'
    //         AND p.estado = 1
    //         AND p.idusuario = u.idusuario
    //         AND u.id_group = 1
    //         GROUP BY p.id_tipo_pregunta");
    //         return $tipos;
    //      }

    //     // $tipos = DB::SELECT("CALL tipospreguntas_cantidad($asignatura, $min, $max)");

    // }

    public function obtenerPreguntaAleatoria($tipo, $evaluacion, $unidad, $i, $intentos)
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
        AND p.idusuario = u.idusuario
        AND u.id_group = 1
        -- AND u.institucion_idInstitucion = 66
        ORDER BY RAND() LIMIT 1",[$tipo, $evaluacion, $unidad]);

        $this->guardarPreguntaRand($pregunta, $tipo, $evaluacion, $unidad, $i, $intentos);
    }

    public function guardarPreguntaRand($pregunta, $tipo, $evaluacion, $unidad, $i, $intentos)
    {
        if(!empty($pregunta)){
            foreach ($pregunta as $key => $value) {
                $id_pregunta = $value->id;
            }
            DB::INSERT("INSERT INTO pre_evas(`id_evaluacion`, `id_pregunta`, `grupo`) VALUES ($evaluacion, $id_pregunta, $i)");
        }else{
            $intentos++;
            if( $intentos < 10 ){
                $this->obtenerPreguntaAleatoria($tipo, $evaluacion, $unidad, $i, $intentos);
            }
        }
    }

    public function cargarPregsRand(Request $request)
    {
        $intentos                 = 0;
        $puntos                   = $request->puntos;
        $tipos                    = explode(",",$request->tipos);
        $unidades                 = explode(",",$request->unidades);
        $cantidades               = explode(",",$request->cant_pregs);
        $evaluacion_personalizada = 0;
        //personalizada
        $id_group                       = $request->id_group;
        $institucion_id                 = $request->institucion_id;
        $evaluacion_personalizada       = 0;
        //si es profesor
        if($id_group == 6 || $id_group == 10){
            $getInstitucion = Institucion::findOrfail($institucion_id);
            $evaluacion_personalizada   = $getInstitucion->evaluacion_personalizada;
        }
        if($evaluacion_personalizada == 1){
            for( $i=1; $i<=$request->grupos; $i++ ){
                for( $j=0; $j<count($tipos); $j++ ){
                    echo "test $j "."<br>";
                    for( $k=0; $k<$cantidades[$j]; $k++ ){
                        echo "entro";
                        echo "hola $j "."<br>";
                        $unidad_rand = rand(0, count($unidades)-1);
                        $this->preguntasRepository->obtenerPreguntaAleatoriaPersonalizada($tipos[$j], $request->evaluacion, $unidades[$unidad_rand], $i, $intentos,$institucion_id);
                    }
                }
            }
        }
        else{
            for( $i=1; $i<=$request->grupos; $i++ ){
                for( $j=0; $j<count($tipos); $j++ ){
                    for( $k=0; $k<$cantidades[$j]; $k++ ){
                        $unidad_rand = rand(0, count($unidades)-1);
                        $this->obtenerPreguntaAleatoria($tipos[$j], $request->evaluacion, $unidades[$unidad_rand], $i, $intentos);
                    }
                }
            }
        }
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


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($variable)
    {
        $variable_arr = preg_split("/[&]+/", $variable);

        $id = $variable_arr[0];
        $pregunta = Preguntas::find($id);

        if($pregunta->delete()){
            if( file_exists('img/img_preguntas/'.$variable_arr[1]) ){
                unlink('img/img_preguntas/'.$variable_arr[1]);
            }
            if( file_exists('img/img_preguntas/'.$variable_arr[2]) ){
                unlink('img/img_preguntas/'.$variable_arr[2]);
            }
            if( file_exists('img/img_preguntas/'.$variable_arr[3]) ){
                unlink('img/img_preguntas/'.$variable_arr[3]);
            }
            if( file_exists('img/img_preguntas/'.$variable_arr[4]) ){
                unlink('img/img_preguntas/'.$variable_arr[4]);
            }
            if( file_exists('img/img_preguntas/'.$variable_arr[5]) ){
                unlink('img/img_preguntas/'.$variable_arr[5]);
            }
        }else{
            header("Refresh:0");
        }

    }
    //api:post/preguntas/delete
    public function preguntasDelete(Request $request){
        //verificar que la pregunta no este utilizada y sea de una institucion que tenga activacion el evaluacion personalizada
        $query = DB::SELECT("SELECT pe.*, i.idInstitucion,i.nombreInstitucion,i.evaluacion_personalizada
        FROM pre_evas pe
        LEFT JOIN evaluaciones e ON pe.id_evaluacion = e.id
        LEFT JOIN usuario u ON e.id_docente = u.idusuario
        LEFT JOIN institucion i ON i.idInstitucion = u.institucion_idInstitucion
        WHERE pe.id_pregunta = '$request->id_Pregunta'
        AND i.estado_idEstado = '1'
        AND i.evaluacion_personalizada = '1'
        ");
        if(count($query) > 0){ return ["status" => "0", "message" => "La pregunta esta siendo utilizada en una evaluación personalizada"]; }
        $pregunta = EvaluacionInstitucionAsignada::findOrFail($request->id)->delete();
    }
}
