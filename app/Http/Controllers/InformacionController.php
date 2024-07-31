<?php

namespace App\Http\Controllers;

use App\Models\Informacion;
use Illuminate\Http\Request;
use DB;
class InformacionController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Informacion  $informacion
     * @return \Illuminate\Http\Response
     */
    public function show(Informacion $informacion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Informacion  $informacion
     * @return \Illuminate\Http\Response
     */
    public function edit(Informacion $informacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Informacion  $informacion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Informacion $informacion)
    {
        //
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Informacion  $informacion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Informacion $informacion)
    {
        //
    }

    public function accesoProlipa(){
        $visitas = DB::SELECT("CALL `accesoProlipa` ();");
        return $visitas;
    }
    
    public function accesoUsuarios(){
        $visitas = DB::SELECT("CALL `accesoUsuarios` ();");
        return $visitas;
    }

    public function institucionRegistradas(){
        $dato = DB::SELECT("CALL `institucionRegistradas` ();");
        return $dato;
    }

    public function usuariosRegistrados(){
        $dato = DB::SELECT("CALL `usuariosRegistrados` ();");
        return $dato;
    }

    public function numInstituciones(){
        $dato = DB::SELECT("CALL `numInstituciones` ();");
        return $dato;
    }
}
