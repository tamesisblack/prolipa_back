<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Planificacion;
USE DB;

class PlanificacionesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->getPlanificacionesXIdAsignatura){
            return $this->getPlanificacionesXIdAsignatura($request->asignatura_idasignatura);
        }
        $planificaciones = DB::SELECT("SELECT p.*, a.idasignatura, a.nombreasignatura,a.area_idarea,a.nivel_idnivel,a.tipo_asignatura,
        CONCAT(u.nombres,' ',u.apellidos) as usuario
         FROM planificacion p
        LEFT JOIN asignatura a ON a.idasignatura = p.asignatura_idasignatura
        LEFT JOIN usuario u On p.user_created = u.idusuario
        -- WHERE p.Estado_idEstado = '1'
        ORDER BY p.idplanificacion DESC
        ");
        return $planificaciones;
    }
    public function getPlanificacionesXIdAsignatura($idasignatura){
        $planificaciones = DB::SELECT("SELECT p.*, a.idasignatura, a.nombreasignatura,a.area_idarea,a.nivel_idnivel,a.tipo_asignatura,
        CONCAT(u.nombres,' ',u.apellidos) as usuario
         FROM planificacion p
        LEFT JOIN asignatura a ON a.idasignatura = p.asignatura_idasignatura
        LEFT JOIN usuario u On p.user_created = u.idusuario
        WHERE a.idasignatura = ?
        ORDER BY p.idplanificacion DESC
        ",[$idasignatura]);
        return $planificaciones;
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
        $datosValidados=$request->validate([
            'asignatura_idasignatura' => 'required',
            'descripcionplanificacion' => 'required',
            'nombreplanificacion' => 'required'
        ]);
        $planificacion = Planificacion::find($request->idplanificacion)->update(
            $request->all()
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
}
