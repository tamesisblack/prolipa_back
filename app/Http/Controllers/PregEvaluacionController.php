<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;///instanciamos base de datos para poder hacer consultas con varias tablas
use App\Models\Pre_eva; //modelo Pre_eva.php

class PregEvaluacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $preguntas = DB::SELECT("SELECT * FROM `pre_evas` WHERE `id_evaluacion` = $request->id_evaluacion AND `grupo` = $request->grupo AND `id_pregunta` = $request->id_pregunta");

        if( count($preguntas) > 0 ){
            return 0;
        }else{
            $pregunta = new Pre_eva();
            $pregunta->id_evaluacion = $request->id_evaluacion;
            $pregunta->id_pregunta = $request->id_pregunta;
            $pregunta->grupo = $request->grupo;
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
        $preguntas = DB::SELECT("SELECT p.puntaje_pregunta, p.id as id, p.id_tema, p.descripcion, p.img_pregunta, pe.id as 'id_pre_evas', p.id_tipo_pregunta, pe.id_evaluacion, ti.nombre_tipo FROM preguntas p, pre_evas pe, tipos_preguntas ti WHERE pe.id_pregunta = p.id AND ti.id_tipo_pregunta = p.id_tipo_pregunta AND pe.id_evaluacion = $id ORDER BY RAND()");

        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ? ORDER BY RAND()",[$value->id]);
                $data['items'][$key] = [
                    'id' => $value->id,
                    'id_pre_evas' => $value->id_pre_evas,
                    'id_evaluacion' => $value->id_evaluacion,
                    'descripcion' => $value->descripcion,
                    'img_pregunta' => $value->img_pregunta,
                    'puntaje_pregunta' => $value->puntaje_pregunta,
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'nombre_tipo' => $value->nombre_tipo,
                    'opciones'=>$opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;

    }


    public function pregEvaluacionGrupo(Request $request)
    {
        $preguntas = DB::SELECT("SELECT p.id as id, p.id_tema, p.descripcion, p.puntaje_pregunta,
        p.img_pregunta, pe.id as 'id_pre_evas', p.id_tipo_pregunta, pe.id_evaluacion, ti.nombre_tipo, ti.indicaciones, t.clasificacion,
        t.nombre_tema
        FROM preguntas p, pre_evas pe, tipos_preguntas ti, temas t
        WHERE t.id = p.id_tema
        AND pe.id_pregunta = p.id
        AND ti.id_tipo_pregunta = p.id_tipo_pregunta
        AND pe.id_evaluacion = $request->evaluacion
        AND p.estado=1
        AND pe.grupo = $request->grupo
        ORDER BY RAND()");
        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo
                FROM opciones_preguntas
                WHERE opciones_preguntas.id_pregunta = ?
                ORDER BY RAND()",[$value->id]);
                $data['items'][$key] = [
                    'id' => $value->id,
                    'id_pre_evas' => $value->id_pre_evas,
                    'id_evaluacion' => $value->id_evaluacion,
                    'descripcion' => $value->descripcion,
                    'img_pregunta' => $value->img_pregunta,
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'nombre_tipo' => $value->nombre_tipo,
                    'indicaciones' => $value->indicaciones,
                    'nombre_tema' => $value->nombre_tema,
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



    public function pregEvaluacionEstudiante(Request $request)
    {
        $preguntas = DB::SELECT("SELECT DISTINCT rp.puntaje, rp.id_respuesta_pregunta, rp.respuesta, (SELECT GROUP_CONCAT(op.id_opcion_pregunta) FROM opciones_preguntas op WHERE op.id_pregunta = pe.id_pregunta AND op.tipo=1) as respuestas_seleccion, (SELECT GROUP_CONCAT(op.opcion) FROM opciones_preguntas op WHERE op.id_pregunta = pe.id_pregunta AND op.tipo=1) as respuestas_escritas, p.id as id, p.id_tema, p.descripcion, p.puntaje_pregunta, p.img_pregunta, pe.id as 'id_pre_evas', p.id_tipo_pregunta, pe.id_evaluacion, ti.nombre_tipo, t.clasificacion FROM preguntas p, pre_evas pe, tipos_preguntas ti, temas t, respuestas_preguntas rp WHERE t.id = p.id_tema AND pe.id_pregunta = p.id AND ti.id_tipo_pregunta = p.id_tipo_pregunta AND pe.id_evaluacion = $request->evaluacion AND pe.grupo = $request->grupo AND p.estado=1 AND rp.id_evaluacion = pe.id_evaluacion AND rp.id_pregunta = pe.id_pregunta AND rp.id_estudiante=$request->estudiante ORDER BY p.id_tipo_pregunta");

        if(!empty($preguntas)){
            foreach ($preguntas as $key => $value) {
                $opciones = DB::SELECT("SELECT DISTINCT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
                $data['items'][$key] = [
                    'id' => $value->id,
                    'id_pre_evas' => $value->id_pre_evas,
                    'id_evaluacion' => $value->id_evaluacion,
                    'descripcion' => $value->descripcion,
                    'img_pregunta' => $value->img_pregunta,
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'nombre_tipo' => $value->nombre_tipo,
                    'puntaje_pregunta' => $value->puntaje_pregunta,
                    'clasificacion' => $value->clasificacion,
                    'puntaje' => $value->puntaje,
                    'id_respuesta_pregunta' => $value->id_respuesta_pregunta,
                    'respuesta' => $value->respuesta,
                    'respuestas_seleccion' => $value->respuestas_seleccion,
                    'respuestas_escritas' => $value->respuestas_escritas,
                    'opciones'=>$opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }




    public function verRespEstudianteEval(Request $request)
    {
        $respuestas = DB::SELECT("SELECT p.id_tipo_pregunta, COUNT(op.id_opcion_pregunta) as cantidadTipo, COUNT(DISTINCT op.id_pregunta) as cantPregTipo FROM preguntas p, pre_evas pe, opciones_preguntas op WHERE op.id_pregunta = pe.id_pregunta AND op.tipo = 1 AND pe.id_pregunta = p.id AND pe.id_evaluacion = $request->evaluacion GROUP BY p.id_tipo_pregunta");

        if(!empty($respuestas)){
            foreach ($respuestas as $key => $value) {
                $opciones = DB::SELECT("SELECT op.id_pregunta, op.id_opcion_pregunta, op.opcion, op.img_opcion, p.puntaje_pregunta FROM opciones_preguntas op, preguntas p, pre_evas pe WHERE op.id_pregunta = p.id AND op.tipo = 1 AND p.id_tipo_pregunta = ? AND pe.id_pregunta = p.id AND pe.id_evaluacion = ?",[$value->id_tipo_pregunta, $request->evaluacion]);

                $data['items'][$key] = [
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'cantidadTipo' => $value->cantidadTipo,
                    'cantPregTipo' => $value->cantPregTipo,
                    'opciones'=>$opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;

    }



    public function getRespuestasGrupo(Request $request)
    {
        $respuestas = DB::SELECT("SELECT p.id_tipo_pregunta, COUNT(op.id_opcion_pregunta) as cantidadTipo, COUNT(DISTINCT op.id_pregunta) as cantPregTipo FROM preguntas p, pre_evas pe, opciones_preguntas op WHERE op.id_pregunta = pe.id_pregunta AND op.tipo = 1 AND pe.id_pregunta = p.id AND pe.id_evaluacion = $request->evaluacion AND pe.grupo = $request->grupo AND p.estado=1 GROUP BY p.id_tipo_pregunta");

        if(!empty($respuestas)){
            foreach ($respuestas as $key => $value) {
                $opciones = DB::SELECT("SELECT op.id_pregunta, op.id_opcion_pregunta, op.opcion, op.img_opcion, p.puntaje_pregunta FROM opciones_preguntas op, preguntas p, pre_evas pe WHERE op.id_pregunta = p.id AND op.tipo = 1 AND p.id_tipo_pregunta = ? AND pe.id_pregunta = p.id AND pe.id_evaluacion = ? AND pe.grupo = ?",[$value->id_tipo_pregunta, $request->evaluacion, $request->grupo]);

                $data['items'][$key] = [
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'cantidadTipo' => $value->cantidadTipo,
                    'cantPregTipo' => $value->cantPregTipo,
                    'opciones' => $opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;

    }


    public function getRespuestas($id)
    {
        $respuestas = DB::SELECT("SELECT p.id_tipo_pregunta, COUNT(op.id_opcion_pregunta) as cantidadTipo, COUNT(DISTINCT op.id_pregunta) as cantPregTipo FROM preguntas p, pre_evas pe, opciones_preguntas op WHERE op.id_pregunta = pe.id_pregunta AND op.tipo = 1 AND pe.id_pregunta = p.id AND pe.id_evaluacion = $id AND p.estado=1 GROUP BY p.id_tipo_pregunta");

        if(!empty($respuestas)){
            foreach ($respuestas as $key => $value) {
                $opciones = DB::SELECT("SELECT op.id_pregunta, op.id_opcion_pregunta, op.opcion, op.img_opcion, p.puntaje_pregunta FROM opciones_preguntas op, preguntas p, pre_evas pe WHERE op.id_pregunta = p.id AND op.tipo = 1 AND p.id_tipo_pregunta = ? AND pe.id_pregunta = p.id AND pe.id_evaluacion = ?",[$value->id_tipo_pregunta, $id]);

                $data['items'][$key] = [
                    'id_tipo_pregunta' => $value->id_tipo_pregunta,
                    'cantidadTipo' => $value->cantidadTipo,
                    'cantPregTipo' => $value->cantPregTipo,
                    'opciones' => $opciones,
                ];
            }
        }else{
            $data = [];
        }
        return $data;

    }



    public function getRespuestasAcum(Request $request)
    {
        $respuestas = DB::SELECT("SELECT op.id_pregunta, GROUP_CONCAT(op.opcion) as opcion, GROUP_CONCAT(op.id_opcion_pregunta) AS id_opcion_pregunta, (SELECT p.puntaje_pregunta FROM preguntas p WHERE p.id = op.id_pregunta) AS puntaje_pregunta, GROUP_CONCAT(DISTINCT op.cant_coincidencias) AS cant_coincidencias FROM opciones_preguntas op, preguntas p, pre_evas pe WHERE op.id_pregunta = p.id AND op.tipo = 1 AND pe.id_pregunta = p.id AND pe.id_evaluacion = ? AND pe.grupo = ? GROUP BY op.id_pregunta",[$request->evaluacion, $request->grupo]);

        return $respuestas;
    }



    public function clasifGrupEstEval(Request $request)
    {
        $estudiantes = explode(",",$request->estudiantes);
        $interval = intval(intval($request->cantidad) / intval($request->grupos));
        $ini_interval = 0;
        $fin_interval = $interval;

        for( $i=1; $i<=$request->grupos; $i++ ){
            for( $j=$ini_interval; $j<$fin_interval; $j++ ){
                if( $request->cantidad > $j ){

                    DB::UPDATE("UPDATE estudiante SET grupo = ? WHERE usuario_idusuario = ? AND codigo = ?",[$i, $estudiantes[$j], $request->codigo]);

                }
            }
            $ini_interval = $ini_interval + $interval;
            $fin_interval = $fin_interval + $interval;
        }
    }




    // public function preguntasxbancoDocente(Request $request)
    // {
    //     if( $request->tipo == 'null' ){
    //         $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
    //          preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
    //           evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
    //            FROM preguntas, evaluaciones, temas, tipos_preguntas ti
    //             WHERE preguntas.idusuario = $request->usuario
    //              AND ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
    //              AND evaluaciones.id_asignatura = temas.id_asignatura
    //               AND preguntas.id_tema = temas.id
    //               AND preguntas.estado = 1
    //                AND evaluaciones.id = $request->evaluacion
    //                AND temas.estado=1
    //                AND temas.unidad = $request->unidad
    //                 AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo) ORDER BY preguntas.descripcion DESC");
    //     }else{
    //         $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema, preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta, evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura FROM preguntas, evaluaciones, temas, tipos_preguntas ti WHERE preguntas.idusuario = $request->usuario AND ti.id_tipo_pregunta = preguntas.id_tipo_pregunta AND evaluaciones.id_asignatura = temas.id_asignatura AND preguntas.id_tema = temas.id AND preguntas.estado = 1 AND evaluaciones.id = $request->evaluacion AND temas.estado=1 AND temas.unidad = $request->unidad AND preguntas.id_tipo_pregunta = $request->tipo AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo) ORDER BY preguntas.descripcion DESC");
    //     }

    //     if(!empty($preguntas)){
    //         foreach ($preguntas as $key => $value) {
    //             $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo
    //             FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
    //             $data['items'][$key] = [
    //                 'id' => $value->id,
    //                 'idusuario' => $request->idusuario,
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



    // public function preguntasxbancoProlipa(Request $request)
    // {
    //     if( $request->tipo == 'null' ){
    //         $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
    //         preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta,
    //         evaluaciones.nombre_evaluacion, temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
    //         FROM preguntas, evaluaciones, temas, tipos_preguntas ti, usuario u
    //         WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
    //         AND evaluaciones.id_asignatura = temas.id_asignatura
    //         AND preguntas.id_tema = temas.id
    //         AND preguntas.estado = 1
    //         AND preguntas.idusuario = u.idusuario
    //         AND u.idusuario != $request->usuario
    //         AND evaluaciones.id = $request->evaluacion
    //         AND temas.estado=1
    //         AND temas.unidad = $request->unidad
    //         AND u.id_group = '1'
    //         AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
    //         ORDER BY preguntas.descripcion DESC");
    //     }else{
    //         $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo, preguntas.id, preguntas.id_tema,
    //         preguntas.descripcion, preguntas.img_pregunta, preguntas.id_tipo_pregunta, preguntas.puntaje_pregunta, evaluaciones.nombre_evaluacion,
    //         temas.nombre_tema, temas.clasificacion, temas.unidad, evaluaciones.id_asignatura
    //         FROM preguntas, evaluaciones, temas, tipos_preguntas ti, usuario u
    //         WHERE ti.id_tipo_pregunta = preguntas.id_tipo_pregunta
    //          AND evaluaciones.id_asignatura = temas.id_asignatura
    //          AND preguntas.id_tema = temas.id
    //           AND preguntas.estado = 1
    //           AND preguntas.idusuario = u.idusuario
    //           AND u.idusuario != $request->usuario
    //           AND evaluaciones.id = $request->evaluacion
    //           AND preguntas.id_tipo_pregunta = $request->tipo
    //            AND temas.estado=1 AND temas.unidad = $request->unidad
    //            AND preguntas.id NOT IN (select id_pregunta from pre_evas where id_evaluacion = $request->evaluacion AND grupo = $request->grupo)
    //             ORDER BY preguntas.descripcion DESC");
    //     }


    //     if(!empty($preguntas)){
    //         foreach ($preguntas as $key => $value) {
    //             $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta, opcion, img_opcion, tipo FROM opciones_preguntas WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
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


    public function quitarPregEvaluacion($id)
    {
        $pregunta = Pre_eva::find($id);
        $pregunta->delete();
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pregunta = Pre_eva::find($id);
        $pregunta->delete();
    }
}
