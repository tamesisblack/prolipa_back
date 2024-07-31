<?php

namespace App\Http\Controllers\Facturacion\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Facturacion\Inventario\ConfiguracionGeneral;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //inventario/configuracion
    public function index()
    {
        $query = ConfiguracionGeneral::orderBy('id','desc')->get();
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
    //inventario/configuracion
    public function store(Request $request)
    {

        if($request->id > 0){ $dato = ConfiguracionGeneral::find($request->id); }
        else                { $dato = new ConfiguracionGeneral(); }
        $dato->minimo        = $request->minimo;
        $dato->maximo        = $request->maximo;
        $dato->nombre        = $request->nombre;
        $dato->descripcion   = $request->descripcion;
        $dato->user_created  = $request->user_created;
        $dato->save();
        return $dato;
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
        $dato = ConfiguracionGeneral::find($id)->delete();
        return ["status" => "1", "message" => "Se elimino correctamente"];
    }
}
