<?php

namespace App\Http\Controllers;

use App\Models\CursoLibro;
use DB;
use Illuminate\Http\Request;

class CursoLibroController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $cursoLibro = DB::select("CALL `curso_libro` ( $request->idcurso );");
        return $cursoLibro;
    }

    public function getAsignaturas(){
        $idusuario = auth()->user()->idusuario;
        $asignaturas = DB::SELECT("SELECT idasignatura,nombreasignatura,area_idarea FROM asignaturausuario join asignatura on asignatura.idasignatura =asignaturausuario.asignatura_idasignatura  WHERE usuario_idusuario = ? ",[$idusuario]);
        return $asignaturas;
    }
    public function getArea(){
        $idusuario = auth()->user()->idusuario;
        $area = DB::SELECT("SELECT idarea,nombrearea FROM asignaturausuario join asignatura on asignatura.idasignatura =asignaturausuario.asignatura_idasignatura join area on area.idarea = asignatura.area_idarea WHERE usuario_idusuario = ? ",[$idusuario]);
        return $area;
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
        $cursoLibro = new CursoLibro();
        $cursoLibro->libro_idlibro = $request->idlibro;
        $cursoLibro->curso_idcurso = $request->idcurso;
        $cursoLibro->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CursoLibro  $cursoLibro
     * @return \Illuminate\Http\Response
     */
    public function show(CursoLibro $cursoLibro)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CursoLibro  $cursoLibro
     * @return \Illuminate\Http\Response
     */
    public function edit(CursoLibro $cursoLibro)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CursoLibro  $cursoLibro
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CursoLibro $cursoLibro)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CursoLibro  $cursoLibro
     * @return \Illuminate\Http\Response
     */
    public function eliminar_libro_curso(Request $request)
    {
        DB::DELETE("DELETE FROM `libro_has_curso` WHERE id_libro_has_curso = ? ", [$request->id]);
    }
}
