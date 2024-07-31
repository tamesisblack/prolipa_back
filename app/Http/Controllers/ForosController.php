<?php

namespace App\Http\Controllers;

use App\Models\Foros;
use App\Models\RespuestasForos;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ForosController extends Controller
{
    public function index()
    {

    }

    public function show($id)
    {
        $foro = DB::SELECT("SELECT * FROM `foros` WHERE `id_foro` = '$id'");
        return $foro;
    }

    public function foros_curso($codigo)
    {
        $foros = DB::SELECT("SELECT * FROM `foros` WHERE `codigo_curso` = '$codigo'");
        return $foros;
    }

    public function get_respuestas_foro($id_foro)
    {
        $respuestas = DB::SELECT("SELECT r.*, u.nombres, u.apellidos, u.foto_user, f.tema, f.descripcion, f.ver_comentarios, f.fecha_final, f.fecha_inicio  FROM foros f 
        LEFT JOIN foros_respuestas r ON r.id_foro = f.id_foro AND r.estado = 1
        LEFT JOIN usuario u ON r.id_usuario = u.idusuario
        WHERE f.id_foro = '$id_foro';");
        return $respuestas;
    }

    public function eliminar_foro($id_foro)
    {
        DB::DELETE("DELETE FROM `foros` WHERE `id_foro` = $id_foro");
    }

    public function store(Request $request)
    {
        if( $request->id_foro ){
            $foro = Foros::find($request->id_foro);
        }else{
            $foro = new Foros();
        }

        $foro->tema = $request->tema;
        $foro->descripcion = $request->descripcion;
        $foro->fecha_inicio = $request->fecha_inicio;
        $foro->fecha_final = $request->fecha_final;
        $foro->codigo_curso = $request->codigo_curso;
        $foro->id_usuario = $request->id_usuario;
        $foro->estado = $request->estado;
        $foro->ver_comentarios = $request->ver_comentarios;

        $foro->save();
        return $foro;
    }



    public function guardar_respuesta_foro(Request $request)
    {
        if( $request->id_respuesta ){
            $respuesta = RespuestasForos::find($request->id_respuesta);
        }else{
            $respuesta = new RespuestasForos();
        }

        $respuesta->id_foro = $request->id_foro;
        $respuesta->respuesta = $request->respuesta;
        $respuesta->nota = $request->nota;
        $respuesta->estado = $request->estado;
        $respuesta->id_usuario = $request->id_usuario;

        $respuesta->save();
        return $respuesta;
    }

    public function guardar_nota_foro(Request $request)
    {
        $respuesta = RespuestasForos::find($request->id);
        $respuesta->nota = $request->nota;

        $respuesta->save();
        return $respuesta;
    }


    ////METODOS PARA EMPAREJAR CON STRAPI
    public function cargar_foros()
    {
        $foros = Http::get('https://foro.prolipadigital.com.ec/foros');
        $json_foros = json_decode($foros, true);
        // return count($json_foros);

        foreach ($json_foros as $key => $value) {
            try {

                $curso_tarea = DB::SELECT("SELECT c.codigo FROM tarea t, curso c WHERE t.curso_idcurso = c.idcurso AND idtarea = ?;", [$value['idtarea']]);

                $foro = new Foros();
                $foro->id_foro_strapi = $value['id'];
                $foro->id_tarea = $value['idtarea'];
                $foro->codigo_curso = $curso_tarea[0]->codigo;
                $foro->id_usuario = $value['idusuario'];
                $foro->descripcion = $value['descripcion'];
                $foro->fecha_inicio = $value['fecha_inicio'];

                $estado_foro = 1;
                if( $value['estado'] == false ){ $estado_foro = 0; }
                $foro->estado = $estado_foro;

                $foro->save();

            } catch (\Throwable $th) {
                dump($th);
            }
        }

    }


    public function cargar_respuestas_foro()
    {
        $respuestas = Http::get('https://foro.prolipadigital.com.ec/respuestas');
        $json_respuestas = json_decode($respuestas, true);
        // return count($json_respuestas);

        foreach ($json_respuestas as $key => $value) {
            try {
                $respuesta = new RespuestasForos();
                $respuesta->id_foro = $value['id'];
                $respuesta->respuesta = $value['respuesta'];
                $respuesta->nota = $value['nota'];
                $respuesta->id_usuario = $value['idusuario'];

                $respuesta->save();

            } catch (\Throwable $th) {
                dump($th);
            }
        }

    }




}
