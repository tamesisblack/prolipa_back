<?php

namespace App\Http\Controllers;

use App\Models\_14Empresa;
use Illuminate\Http\Request;

class _14EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dato = _14Empresa::get();
        return $dato;
    }
    public function empresa2()
    {
        $dato = _14Empresa::all('id','nombre','descripcion_corta');
        return $dato;
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
        if( $request->id > 0){
            $dato = _14Empresa::find($request->id);
            // return "id mayor a 0  " . $request->id  ;
        }else{
            $dato = new _14Empresa();
            // return "no hay id  "  . $request->id;
        }
        // return $request->img_base64;
        // return response()->json(['mensaje' => $request->img_base64 . ' hola']);
        $dato->nombre                   = $request->nombre;
        $dato->descripcion_corta        = $request->descripcion_corta ?? null;
        $dato->direccion                = $request->direccion;
        $dato->representante            = $request->representante;
        $dato->ruc                      = $request->ruc;
        $dato->email                    = $request->email;
        $dato->telefono                 = $request->telefono;
        $dato->estado                   = $request->estado;
        $dato->tipo                     = $request->tipo;
        $dato->secuencial               = $request->secuencial;
        $dato->facturas                 = $request->facturas;
        $dato->notas                    = $request->notas;
        if($request->img_base64 != '' || $request->img_base64 != null){
            $dato->img_base64 = $request->img_base64;
        }
        if($request->archivo)           { $dato->archivo = null; }
        if($request->url)               { $dato->url     = null; }
        $dato->save();
        return $dato;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\_14Empresa  $_14Empresa
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\_14Empresa  $_14Empresa
     * @return \Illuminate\Http\Response
     */
    public function edit(_14Empresa $_14Empresa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\_14Empresa  $_14Empresa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, _14Empresa $_14Empresa)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\_14Empresa  $_14Empresa
     * @return \Illuminate\Http\Response
     */
    public function destroy(_14Empresa $_14Empresa)
    {
        //
    }
}
