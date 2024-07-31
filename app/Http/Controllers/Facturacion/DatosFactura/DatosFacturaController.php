<?php

namespace App\Http\Controllers\Facturacion\DatosFactura;

use App\Http\Controllers\Controller;
use App\Models\Institucion;
use App\Models\Pedidos;
use Illuminate\Http\Request;

class DatosFacturaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //Api:get/datosFactura/datosFactura
    public function index(Request $request)
    {
        if($request->getlibreria)    { return $this->getlibreria($request); }
    }
    //api:get/datosFactura/datosFactura?getlibreria=1&idInstitucion=426
    public function getlibreria($request){
        //traer datos de la institucion con la relacion representante
        $datos = Institucion::with('representante')->where('idInstitucion', $request->idInstitucion)->get();
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
    //API:POST/datosFactura/datosFactura
    public function store(Request $request)
    {
        if($request->actualizarDatosFactura)    { return $this->actualizarDatosFactura($request); }
        if($request->actualizarInstitucion)     { return $this->actualizarInstitucion($request); }
    }
    //api:post/datosFactura/datosFactura/actualizarDatosFactura=1
    public function actualizarDatosFactura($request){
        $datos  = Institucion::findOrFail($request->id);
        $datos->idrepresentante = $request->idrepresentante;
        $datos->save();
        return $datos;
    }
    //api:post/datosFactura/datosFactura/actualizarInstitucion=1
    public function actualizarInstitucion($request){
        $datos  = Institucion::findOrFail($request->id);
        $datos->direccionInstitucion    = $request->direccionInstitucion;
        $datos->telefonoInstitucion     = $request->telefonoInstitucion;
        $datos->email                   = $request->email;
        $datos->ruc                     = $request->ruc;
        $datos->save();
        if($datos){
            return ["status" => "1", "message" => "Datos actualizados correctamente"];
        }else{
            return ["status" => "0", "message" => "Error al actualizar los datos"];
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
