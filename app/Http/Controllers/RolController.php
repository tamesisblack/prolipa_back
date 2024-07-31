<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use App\Models\Usuario;
use DB;

class RolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return \Auth::user()->id_group;
        if(\Auth::user()->id_group == 11){
            return $data = [
                ['id'=>10,
                'level'=>'Director',
                'deskripsi'=>'Director'],
                ['id'=>4,
                'level'=>'Estudiantes',
                'deskripsi'=>'Estudiantes'],
                ['id'=>6,
                'level'=>'Docente',
                'deskripsi'=>'Docente'],
            ];
        }
        if(\Auth::user()->id_group == 10){
            return $data = [
                ['id'=>4,
                'level'=>'Estudiantes',
                'deskripsi'=>'Estudiantes'],
                ['id'=>6,
                'level'=>'Docente',
                'deskripsi'=>'Docente'],
            ];
        }

        if(\Auth::user()->id_group == 1){
            $rol = Rol::all();
            return $rol;
        }
    }

    public function select()
    {
        if(\Auth::user()->id_group == 11){
            return $data = [
                ['id'=>'11',
                'level'=>'Asesor',
                'deskripsi'=>'Asesor'],
                ['id'=>'4',
                'level'=>'Estudiantes',
                'deskripsi'=>'Estudiantes'],
                ['id'=>'6',
                'level'=>'Docente',
                'deskripsi'=>'Docente'],
            ];
        }
        if(\Auth::user()->id_group == 1){
            $rol = Rol::all();
            return $rol;
        }
    }
    public function getAdsUser(){
        $admis = DB::SELECT("SELECT * FROM usuario u
        WHERE u.id_group = '1'
        ");
        return $admis;
    }
    public function changeEstado(Request $request){
        if($request->estado == 1){
            DB::update("UPDATE `usuario` SET `estado_idEstado`= ?  WHERE `idusuario` = ?",[1,$request->idusuario]);
        }else{
            DB::update("UPDATE `usuario` SET `estado_idEstado`= ?  WHERE `idusuario` = ?",[2,$request->idusuario]);
        }
        return ["status" => "1", "message" => "Se guardo correctamente"];

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
     * @param  \App\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function edit(Rol $rol)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Rol $rol)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $usuario = DB::table('usuario as u')
        ->where('u.id_group',$id)
        ->first();

        if( count($usuario) > 0 )
        {
            return 'No se puede eliminar el rol, existen usuarios registrados cone ste rol';
        }else{
            $data = Rol::find($id);
            $data->delete();
            return $data;
        }
    }
}
