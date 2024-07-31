<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HLogin;
use Illuminate\Http\Request;
use DB;

class LoginNorthHospitalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return "hola mundo";
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
        //login
        if($request->login){
            $buscarUsuario = DB::SELECT("SELECT idusuario,nombres,apellidos,email,cedula,estado, id_group FROM hsp_usuarios
             WHERE email = '$request->email'
             AND `password` = ?
             AND estado = '1'
            ",[sha1(md5($request->cedula))]);
            
            if(count($buscarUsuario) >0){
                return ["status" =>"1","message" => "usuario Logeado","usuario"=> $buscarUsuario];
            }else{
                return ["status" =>"0","message" => "No existe el usuario"];
            }
        }
        if($request->registro){
            $datosValidados=$request->validate([
                'cedula' => 'required|max:15|unique:hsp_usuarios', 
                'email' => 'required|email|unique:hsp_usuarios',
            ]);
            $registro = new HLogin();
            $registro->nombres = $request->nombres;
            $registro->apellidos = $request->apellidos;
            $registro->cedula = $request->cedula;
            $registro->email = $request->email;
            $registro->password = sha1(md5($request->cedula));
            $registro->id_group = $request->id_group;
            $registro->save();
            if($registro){
                return ["status" => "1","message"=>"Se registro correctamente","usuario"=>$registro];
            }else{
                return ["status" => "0","message"=>"No se pudo registrar"];
            }
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
