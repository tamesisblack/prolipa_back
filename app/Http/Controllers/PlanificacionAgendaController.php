<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\AgendaPlanificacion;
use DateTime;

class PlanificacionAgendaController extends Controller
{
    public function index(Request $request)
    {   
        $areas = DB::SELECT("SELECT * FROM salle_areas");

        return $areas;

    }

    public function create()
    {
        //
    }

    public function store(Request $request){

        if( $request->id != 0 ){
            $agenda = AgendaPlanificacion::find($request->id);
        }else{
            $agenda = new AgendaPlanificacion();
        }
        $agenda->id_usuario = $request->idusuario;
        $agenda->title = $request->title;
        $agenda->label = $request->label;
        $agenda->classes = $request->classes;
        $agenda->startDate = $request->startDate;
        $agenda->endDate = $request->endDate;
        $agenda->hora_inicio = $request->hora_inicio;
        $agenda->hora_fin = $request->hora_fin;
        $agenda->clasificacion = $request->clasificacion;
        $agenda->progreso = $request->progreso;
        $agenda->url = $request->url;

        $agenda->save();
        return $agenda;

    }

    public function show($id)
    {   
        $date = new DateTime();
        $fecha_actual = $date->format('Y-m-d H:i:s');
        $planificaciones = DB::SELECT("SELECT * FROM planificacion_agenda p WHERE `id_usuario` = $id AND `progreso` != 'finalizado' AND `endDate` > '$fecha_actual'");

        return $planificaciones;
    }

    public function get_finished_events($id)
    {
        $planificaciones = DB::SELECT("SELECT * FROM planificacion_agenda p WHERE `id_usuario` = $id AND `progreso` = 'finalizado'");

        return $planificaciones;
    }
    public function get_incomplete_events($id)
    {   
        $date = new DateTime();
        $fecha_actual = $date->format('Y-m-d H:i:s');
        $planificaciones = DB::SELECT("SELECT * FROM planificacion_agenda p WHERE `id_usuario` = $id AND `progreso` != 'finalizado' AND `endDate` < '$fecha_actual'");

        return $planificaciones;
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        
    }

    public function delete_agenda_planificacion($id)
    {
        DB::SELECT("DELETE FROM `planificacion_agenda` WHERE `id` = $id");
    }

}
