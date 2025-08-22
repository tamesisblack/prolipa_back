<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use DB;
use App\Models\PermisoSuper;

class ConfiguracionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //para validar si es usuario root
       if($request->permiso){
            $validaPermiso = DB::SELECT("SELECT * FROM permisos_super
            WHERE usuario_id =  '$request->usuario_id'
            ");
            if(count($validaPermiso) >0) {
                return ["status" => "1", "Usuario es root"];
            }else{
                return ["status" => "0", "Usuario no es root"];
            }
       }else{
            $root = DB::SELECT("SELECT r.*, CONCAT(u.nombres,' ', u.apellidos) AS usuario ,u.cedula, g.deskripsi AS rol
                FROM permisos_super r
                LEFT JOIN usuario u ON r.usuario_id = u.idusuario
                LEFT JOIN sys_group_users g ON r.id_group = g.id
                ORDER BY id DESC
            ");
            return $root;

       }


    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {


    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->eliminar){
            $usuario = PermisoSuper::findOrFail($request->id);
            $usuario->delete();
            return ["status" => "1", "message"=>"Se elimino el permiso al usuario correctamente"];
        }else{
             //si ya existe el permiso
            $validar = DB::SELECT("SELECT * FROM permisos_super r WHERE r.usuario_id = '$request->usuario_id'
            ");
            if(count($validar) > 0){
            return ["status" => "0", "message"=>"El usuario ya  se encuentra asignado"];
            }
            //guardar
            $permiso = new  PermisoSuper;
            $permiso->usuario_id = $request->usuario_id;
            $permiso->id_group = $request->id_group;
            $permiso->save();

            if($permiso){
                return ["status" => "1", "message"=>"Se asigno el permiso correctamente"];
            }else{
                return ["status" => "0", "message"=>"No se asigno el permiso"];
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
        $dato = PermisoSuper::where('usuario_id',$id)
        ->get();
        return $dato;
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
    //api:post/saveNotification
    public function saveNotification(Request $request){
        $notificacion = new Notification();
        $notificacion->descripcion    = $request->descripcion;
        $notificacion->tipo           = $request->tipo;
        if($request->tipo == "Solicitud libros"){
            $notificacion->administrador = "1";
        }
        $notificacion->user_created   = $request->idusuario;
        $notificacion->id_created     = $request->id_created;
        $notificacion->save();
        return $notificacion;
    }
    //api:get/getNotifications
    public function getNotifications(Request $request){
        $notificacions = DB::SELECT("SELECT * FROM notificaciones
        ORDER BY id DESC
        LIMIT 20
        ");
        return $notificacions;
    }
    public function UrlEncript(){
        $enc = encrypt(uniqid());
        $url = "https://plataforma.prolipadigital.com.ec/v/".$enc;
        return redirect($url);
    }
    public function getConfiguracionGenerlaXId($id){
        $query = DB::SELECT("SELECT * FROM configuracion_general c
        WHERE c.id = '$id'");
        return $query;
    }
}
