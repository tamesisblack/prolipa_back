<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Models\Distribuidor\Distribuidor;
use App\Models\Models\Distribuidor\DistribuidorTemporada;
use App\Models\User;
use DB;
use App\Traits\Usuario\TraitUsuarioGeneral;
use Illuminate\Http\Request;


class DistribuidorController extends Controller
{
    use TraitUsuarioGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //API:GET/distribuidor
    public function index(Request $request)
    {
        if($request->getDistribuidores)             { return $this->getDistribuidores(); }
        if($request->getDistribuidorTemporadas)     { return $this->getDistribuidorTemporadas(); }
        if($request->getDistribuidorTemporadasXId)  { return $this->getDistribuidorTemporadasXId($request->id); }
        if($request->getUserxRol)                   { return $this->userxRol(11); }
        if($request->movimientosDistribuidor)       { return $this->movimientosDistribuidor(); }
    }
    public function getDistribuidores(){
        $query = DB::SELECT("SELECT
        CONCAT(u.nombres, ' ',u.apellidos) AS distribuidorUser, u.cedula,d.*
        FROM distribuidor  d
        LEFT JOIN usuario u ON d.idusuario = u.idusuario
        ORDER BY d.distribuidor_id DESC
        ");
        return $query;
    }
    public function getDistribuidorTemporadas(){
        $query = DB::SELECT("SELECT dt.*,
        CONCAT(u.nombres, ' ',u.apellidos) AS distribuidorUser,
        p.periodoescolar
         FROM distribuidor_temporada dt
        LEFT JOIN usuario u ON dt.idusuario = u.idusuario
        LEFT JOIN periodoescolar p ON dt.periodo_id = p.idperiodoescolar
        ORDER BY dt.id DESC
        ");
        $datos=[];
        foreach($query as $key => $item){
            $getValorPendiente = 0;
            $query2 = $this->getPendienteAprobarxDistribudor($item->id); 
            if(empty($query2)) {  $getValorPendiente = 0;}
            else { $getValorPendiente = $query2[0]->valorPendiente; }
            $datos[$key] = [
                "id"                => $item->id,
                "idusuario"         => $item->idusuario,
                "periodo_id"        => $item->periodo_id,
                "saldo_inicial"     => $item->saldo_inicial,
                "saldo_final"       => $item->saldo_final,
                "saldo_actual"      => $item->saldo_actual,
                "user_created"      => $item->user_created,
                "created_at"        => $item->created_at,
                "updated_at"        => $item->updated_at,
                "distribuidorUser"  => $item->distribuidorUser,
                "periodoescolar"    => $item->periodoescolar,
                "pendienteAprobar"  => $getValorPendiente == null || $getValorPendiente == "" ? 0 : $getValorPendiente
            ];
        }
        return $datos;
    }
    public function getDistribuidorTemporadasXId($id){
        $query = DB::SELECT("SELECT dt.*,
        CONCAT(u.nombres, ' ',u.apellidos) AS distribuidorUser,
        p.periodoescolar
         FROM distribuidor_temporada dt
        LEFT JOIN usuario u ON dt.idusuario = u.idusuario
        LEFT JOIN periodoescolar p ON dt.periodo_id = p.idperiodoescolar
        WHERE dt.id = ?
        ORDER BY dt.id DESC
        ",[$id]);
        $datos=[];
        foreach($query as $key => $item){
            $getValorPendiente = 0;
            $query2 = $this->getPendienteAprobarxDistribudor($item->id); 
            if(empty($query2)) {  $getValorPendiente = 0;}
            else { $getValorPendiente = $query2[0]->valorPendiente; }
            $datos[$key] = [
                "id"                => $item->id,
                "idusuario"         => $item->idusuario,
                "periodo_id"        => $item->periodo_id,
                "saldo_inicial"     => $item->saldo_inicial,
                "saldo_final"       => $item->saldo_final,
                "saldo_actual"      => $item->saldo_actual,
                "user_created"      => $item->user_created,
                "created_at"        => $item->created_at,
                "updated_at"        => $item->updated_at,
                "distribuidorUser"  => $item->distribuidorUser,
                "periodoescolar"    => $item->periodoescolar,
                "pendienteAprobar"  => $getValorPendiente == null || $getValorPendiente == "" ? 0 : $getValorPendiente
            ];
        }
        return $datos;
    }
    public function movimientosDistribuidor(){
        $query = DB::SELECT("SELECT h.*, pe.periodoescolar AS periodo,
        CONCAT(u.nombres,' ',u.apellidos) AS distribuidor,
        CONCAT(edit.nombres,' ',edit.apellidos) AS editor
         FROM distribuidor_historico h
        LEFT JOIN periodoescolar pe ON h.periodo_id = pe.idperiodoescolar
        LEFT JOIN distribuidor d ON  h.distribuidor_id = d.distribuidor_id
        LEFT JOIN usuario u ON d.idusuario = u.idusuario
        LEFT JOIN usuario edit ON h.user_created = edit.idusuario
        WHERE pe.estado = '1'
        ");
        return $query;
    }
    public function getPendienteAprobarxDistribudor($distribuidorTemp){
        $query = DB::SELECT("SELECT SUM(pd.valor) AS valorPendiente
        FROM verificaciones_pagos_detalles pd
        lEFT JOIN verificaciones_pagos vp ON vp.verificacion_pago_id = pd.verificacion_pago_id
        WHERE pd.distribuidor_temporada_id = ?
       AND vp.estado = '0'
        ",[$distribuidorTemp]);
        return $query;
    }
    public function getTemporadaDistribuidor($idusuario,$periodo_id){
        //validar que no este ingresado
        $query = DB::SELECT("SELECT * FROM distribuidor_temporada t
        WHERE t.idusuario  = ?
        AND t.periodo_id    = ?
        ",[$idusuario,$periodo_id]);
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
    //API:POST/distribuidor
    public function store(Request $request)
    {
        if($request->save_distribuidor){
            return $this->save_distribuidor($request);
        }
        if($request->save_distribuidorTemporada){
            return $this->save_distribuidorTemporada($request);
        }
        if($request->save_user){
            return $this->save_user($request);
        }
    }

    public function save_distribuidor($request){
        if($request->id){
            $distribuidor = Distribuidor::findOrFail($request->id);
        }else{
            $distribuidor = new Distribuidor();
            $distribuidor->user_created = $request->user_created;
        }
        $distribuidor->idusuario    = $request->idusuario;
        $distribuidor->estado       = $request->estado;
        $distribuidor->save();
        if($distribuidor){
            return ["status" => "1","Se guardo correctamente"];
        }else{
            return ["status" => "0", "No se pudo guardar"];
        }
    }
    public function save_distribuidorTemporada($request){

        if($request->id){
            $distribuidorT = DistribuidorTemporada::findOrFail($request->id);
        }else{
            //validar
           $validate =  $this->getTemporadaDistribuidor($request->idusuario,$request->periodo_id);
            if(count($validate) > 0){
                return ["status" => "0","message" => "Ya existe asignado una temporada para el distribuidor"];
            }
            $distribuidorT = new DistribuidorTemporada();
            $distribuidorT->periodo_id      = $request->periodo_id;
            $distribuidorT->saldo_actual    = $request->saldo_inicial;
        }
        $distribuidorT->idusuario        = $request->idusuario;
        $distribuidorT->saldo_inicial    = $request->saldo_inicial;
        $distribuidorT->user_created     = $request->user_created;
        $distribuidorT->save();
        if($distribuidorT){
            return ["status" => "1","Se guardo correctamente"];
        }else{
            return ["status" => "0", "No se pudo guardar"];
        }
    }
    public function save_user($request){
        $datosValidados = $request->validate([
            'cedula'                    => 'required|max:15|unique:usuario',
            'nombres'                   => 'required',
            'apellidos'                 => 'required',
            'email'                     => 'required|email|unique:usuario',
            'institucion_idInstitucion' => 'required',
        ]);
        // LUEGO SE GUARDA EN BASE PROLIPA
        $password                           = sha1(md5($request->cedula));
        $user                               = new User();
        $user->cedula                       = $request->cedula;
        $user->nombres                      = $request->nombres;
        $user->apellidos                    = $request->apellidos;
        $user->name_usuario                 = $request->email;
        $user->password                     = $password;
        $user->email                        = $request->email;
        $user->id_group                     = $request->id_group;
        $user->institucion_idInstitucion    = $request->institucion_idInstitucion;
        $user->estado_idEstado              = 1;
        $user->idcreadorusuario             = $request->idcreadorusuario;
        $user->telefono                     = $request->telefono;
        $user->save();
        return $user;
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
