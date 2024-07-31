<?php

namespace App\Http\Controllers;

use App\Models\HVisitas;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class HVisitasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $datos = DB::table('hsp_visitas as vis')
        ->leftjoin('hsp_empresas as emp', 'vis.idempresa','=','emp.id')
        ->leftjoin('hsp_usuarios as u', 'vis.idusuario','=','u.idusuario')
        ->select('vis.*', 'emp.nombre as empresa','u.nombres','u.apellidos')
        ->orderby('vis.created_at','desc')
        ->get();
        return $datos;
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
            $dato = HVisitas::find($request->id);
        }
        else{
            $dato = new HVisitas();
        }
        $dato->idusuario = $request->idusuario;
        $dato->idempresa = $request->idempresa;
        $dato->fecha_visita = $request->fecha_visita;
        $dato->observacion = $request->observacion;
        $dato->estado = $request->estado;
        $dato->save();
        return $dato;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\HVisitas  $hVisitas
     * @return \Illuminate\Http\Response
     */
    public function show(HVisitas $hVisitas)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\HVisitas  $hVisitas
     * @return \Illuminate\Http\Response
     */
    public function edit(HVisitas $hVisitas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HVisitas  $hVisitas
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HVisitas $hVisitas)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\HVisitas  $hVisitas
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $dato = HVisitas::findOrFail($id);
        $dato->delete();
        return $dato;
    }
    public function visitas_pendientes()
    {
        $datos = DB::table('hsp_visitas as vis')
        ->leftjoin('hsp_empresas as emp', 'vis.idempresa','=','emp.id')
        ->select('vis.*', 'emp.nombre as empresa')
        ->orderby('vis.created_at','desc')
        ->get();
        return $datos;
    }
    public function historicoHospital(Request $request)
    {
        $res = DB::INSERT('INSERT INTO `his_auditoria`(`idusuario`, `accion`) VALUES (?,?)',[$request->idusuario,$request->accion]);
        return $res;
    }
}
