<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use Illuminate\Http\Request;
use DB;
class CalificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $idusuario = auth()->user()->idusuario;
        if(!empty($request->idcurso)){
            $calificacion = DB::SELECT("SELECT * FROM usuario_tarea WHERE usuario_idusuario = ? AND curso_idcurso = ?",[$idusuario,$request->idcurso]);
        }
        if(!empty($request->idtarea)){
            $calificacion = DB::SELECT("SELECT * FROM usuario_tarea join usuario on usuario.idusuario = usuario_tarea.usuario_idusuario WHERE tarea_idtarea = ?",[$request->idtarea]);
        }
        return $calificacion;
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Calificacion  $calificacion
     * @return \Illuminate\Http\Response
     */
    public function show(Calificacion $calificacion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Calificacion  $calificacion
     * @return \Illuminate\Http\Response
     */
    public function edit(Calificacion $calificacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Calificacion  $calificacion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Calificacion $calificacion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Calificacion  $calificacion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Calificacion $calificacion)
    {
        //
    }
}
