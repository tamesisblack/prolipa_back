<?php

namespace App\Http\Controllers;

use App\Models\Juegos;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Provider\Image;
use Illuminate\Support\Facades\File;
use DB;
class JuegosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // if(empty($request->idusuario)){
        //     $juegos = DB::SELECT("SELECT *  FROM juegos join asignatura on asignatura.idasignatura = juegos.asignatura_idasignatura");
        // }else{
        // }
        $juegos = DB::SELECT("
        SELECT juegos . * ,asignatura.* FROM juegos
        JOIN asignaturausuario ON juegos.asignatura_idasignatura = asignaturausuario.asignatura_idasignatura
        JOIN asignatura ON asignatura.idasignatura = asignaturausuario.asignatura_idasignatura
        WHERE asignaturausuario.usuario_idusuario = ? ORDER BY asignatura.idasignatura
        ",[$request->idusuario]);
        return $juegos;
    }

    public function juegosEstudainte(Request $request){
        $juegos = DB::SELECT("SELECT * FROM juegos WHERE asignatura_idasignatura = ?",[$request->idasignatura]);
        return $juegos;
    }

    public function juegos_unidad($id_unidad)
    {
        $juegos = DB::SELECT("SELECT DISTINCT j .* FROM juegos j, temas_has_juego jt, temas t, unidades_libros un WHERE j.idjuegos = jt.id_juego AND jt.id_tema = t.id AND t.id_unidad = un.id_unidad_libro AND un.id_unidad_libro = $id_unidad");

        return $juegos;
    }

    public function juegos_tema($id_tema)
    {
        $juegos = DB::SELECT('SELECT DISTINCT j .* FROM juegos j, temas_has_juego jt WHERE j.idjuegos = jt.id_juego AND jt.id_tema = ?',[$id_tema]);

        return $juegos;
    }


    public function juegos_asignatura($id)
    {
        $juegos = DB::SELECT("SELECT * FROM juegos j WHERE j.asignatura_idasignatura = $id");

        return $juegos;
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
        if(!empty($request->idjuegos)){
            $juego = Juegos::find($request->idjuegos)->update($request->all());
        }else{
            $juego = new Juegos($request->all());
            $juego->save();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Juegos  $juegos
     * @return \Illuminate\Http\Response
     */
    public function show($idlibro)
    {
        $juegos = DB::SELECT("SELECT j.idjuegos, j.nombre, j.descripcion, j.carpeta, j.asignatura_idasignatura FROM juegos j, libro l WHERE j.asignatura_idasignatura = l.asignatura_idasignatura AND l.idlibro = $idlibro");

        return $juegos;
    }

    //==================FIN METODOS DE PROYECTOS==========================

    public function get_img_rompecabezas($id_juego)
    {
        $imagenes = DB::SELECT("SELECT * FROM `j_contenido_juegos` WHERE `estado` = 1 AND `id_juego` = $id_juego");
        return $imagenes;
    }


}
