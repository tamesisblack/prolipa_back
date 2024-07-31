<?php

namespace App\Http\Controllers;

use App\Models\HQuirurgicas;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class HQuirurgicasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dato =  DB::table('hsp_quirugicas as q')
        ->leftjoin('hsp_usuarios as u','q.idmedico','=','u.idusuario')
        ->leftjoin('hsp_especialidades as e','q.idespecialidad','=','e.id')
        ->select('q.id as idquirurgica','q.*','u.*','e.id as idespecialidad','e.nombre as name_especialidad')
        ->orderby('q.created_at','desc')
        ->get();
        return $dato;
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
        if($request->id >0){
            $dato = HQuirurgicas::find($request->id);
        }else{
            $dato= new HQuirurgicas();
        }
        $dato->idmedico = $request->idmedico;
        $dato->idespecialidad = $request->idespecialidad;
        $dato->fseguimiento = $request->fseguimiento;
        $dato->direccion = $request->direccion;
        $dato->estado = $request->estado;
        $dato->convencional = $request->convencional;
        $dato->movil = $request->movil;
        $dato->observacion = $request->observacion;
        $dato->save();
        return $dato;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\HQuirurgicas  $hQuirurgicas
     * @return \Illuminate\Http\Response
     */
    public function show(HQuirurgicas $hQuirurgicas)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\HQuirurgicas  $hQuirurgicas
     * @return \Illuminate\Http\Response
     */
    public function edit(HQuirurgicas $hQuirurgicas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HQuirurgicas  $hQuirurgicas
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HQuirurgicas $hQuirurgicas)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\HQuirurgicas  $hQuirurgicas
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $dato = HQuirurgicas::find($id);
        $dato->delete();
        return $dato;
    }
}
