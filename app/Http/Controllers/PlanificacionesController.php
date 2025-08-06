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

    public function transferenciaPlanificaciones(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'libro_a_transferir' => 'required|integer', // ID del libro origen (asignatura)
            'libro_recibir_transferencia' => 'required|integer', // ID del libro destino (asignatura)
            'user_created' => 'required|integer',
        ]);

        // Eliminar planificaciones existentes del libro receptor
        Planificacion::where('asignatura_idasignatura', $request->libro_recibir_transferencia)->delete();

        // Buscar planificaciones del libro origen
        $planificaciones = Planificacion::where('asignatura_idasignatura', $request->libro_a_transferir)->get();

        // Clonar cada planificación al libro receptor
        foreach ($planificaciones as $plan) {
            Planificacion::create([
                'nombreplanificacion'     => $plan->nombreplanificacion,
                'descripcionplanificacion'=> $plan->descripcionplanificacion,
                'webplanificacion'        => $plan->webplanificacion,
                'asignatura_idasignatura' => $request->libro_recibir_transferencia,
                'user_created'            => $request->user_created, // puedes usar el actual si deseas
                'Estado_idEstado'         => $plan->Estado_idEstado,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
        }

        return response()->json(['message' => 'Transferencia de planificaciones completada exitosamente.'], 200);
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
