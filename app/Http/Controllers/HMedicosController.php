<?php

namespace App\Http\Controllers;

use App\Models\HMedicos;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class HMedicosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $datos = DB::table('hsp_usuarios as u')
        ->leftjoin('hsp_especialidades as ep','u.idespecialidad','=','ep.id')
        ->leftjoin('sys_group_users as gr','u.id_group','=','gr.id')
        ->where('u.id_group','=','21')
        ->orWhere('u.id_group','=','20')
        ->orWhere('u.id_group','=','19')
        ->orWhere('u.id_group','=','18')
        ->select('u.*','ep.id as idespecialidad','ep.nombre as especialidad','gr.level as grupo_nombre','gr.id as grupo_id')
        ->orderby('u.created_at','desc')
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
       if($request->idusuario > 0){
            $dato = HMedicos::find($request->idusuario);
       }else{
            $dato = new HMedicos();
       }
       $dato->nombres = $request->nombres;
       $dato->apellidos = $request->apellidos;
       $dato->email = $request->email;
       $dato->cedula = $request->cedula;
       $dato->password = sha1(md5($request->cedula));
       $dato->estado = $request->estado;
       $dato->fnacimiento = $request->fnacimiento;
       $dato->convencional = $request->convencional;
       $dato->movil = $request->movil;
       $dato->id_group = $request->id_group;
       $dato->idespecialidad = $request->idespecialidad;
       $dato->save();
       return $dato;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\HMedicos  $hMedicos
     * @return \Illuminate\Http\Response
     */
    public function show(HMedicos $hMedicos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\HMedicos  $hMedicos
     * @return \Illuminate\Http\Response
     */
    public function edit(HMedicos $hMedicos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HMedicos  $hMedicos
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HMedicos $hMedicos)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\HMedicos  $hMedicos
     * @return \Illuminate\Http\Response
     */
    public function destroy(H$id)
    {
        $dato = HMedicos::find($request->id);
        $dato->estado = 0;
        $dato->save();
        return $dato;
    }
    public function solo_medicos()
    {
        $datos = DB::SELECT("SELECT idusuario,id_group,estado,fnacimiento,concat(nombres,apellidos) as medico FROM hsp_usuarios
        WHERE estado = '1'
        AND id_group = '21'
       ");
        return $datos;
    }
    public function medicos_birthday()
    {
        $carbon = new \Carbon\Carbon();
        $mes_actual = $carbon->format('m');

        $dato = DB::table('hsp_usuarios as u')
        ->select('u.fnacimiento','nombres', 'apellidos')
        ->wheremonth('u.fnacimiento',$mes_actual)
        ->get();
        return $dato;
    }
}
