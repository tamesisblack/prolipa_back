<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SallePeriodo;
use DB;
class SallePeriodoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //api:get/salle/periodos
    public function index()
    {
        $query = SallePeriodo::orderBy('id', 'desc')->get();
        return $query;
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
        //desactivar/activar periodo
        if($request->cambiarEstadoPeriodo){
            return $this->cambiarEstadoPeriodo($request);
        }else{
            if($request->id > 0){
                $periodo = SallePeriodo::findOrFail($request->id);
            }else{
                $periodo                    = new SallePeriodo();
            }
            $periodo->nombre            = $request->nombre;
            // //fecha inicio
            // $fecha_inicio               = $request->fecha_inicio;
            // if($fecha_inicio == null || $fecha_inicio == "" || $fecha_inicio == "null"){
            //     $periodo->fecha_inicio  = null;
            // }else{
            //     $periodo->fecha_inicio  = $request->fecha_inicio;
            // }
            // //fecha fin
            // $fecha_fin                  = $request->fecha_fin;
            // if($fecha_fin == null || $fecha_fin == "" || $fecha_fin == "null"){
            //     $periodo->fecha_fin  = null;
            // }else{
            //     $periodo->fecha_fin  = $request->fecha_fin;
            // }
            $periodo->save();
            return $this->saveStatus($periodo);
        }
    }
    public function cambiarEstadoPeriodo($request){
        //validate que no hay otro periodo abierto solo puede aver uno
        $id     = $request->id;
        $estado = $request->estado;
        if($estado == 1){
            $query = DB::SELECT("SELECT * FROM salle_periodos_evaluacion p
            WHERE p.estado = '1'
            ");
            if(count($query) > 0){
                return ["status" => "0", "message" => "Ya existe activo un período de evaluación"];
            }
        }
        $periodo            = SallePeriodo::findOrFail($request->id);
        $periodo->estado    = $request->estado;
        $periodo->save();
        return $this->saveStatus($periodo);
    }
    public function saveStatus($periodo){
        if($periodo){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
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
