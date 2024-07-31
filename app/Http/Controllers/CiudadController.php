<?php

namespace App\Http\Controllers;

use App\Models\Ciudad;
use Illuminate\Http\Request;
use DB;

class CiudadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ciudad = Ciudad::all();
        return $ciudad;
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
        if($request->idciudad > 0){
            $ciudad = Ciudad::find($request->idciudad);
        }else{
            $ciudad = new Ciudad();
        }
        $ciudad->nombre = $request->nombre;
        $ciudad->provincia_idprovincia = $request->provincia_idprovincia;
        $ciudad->id_ciudad_milton = $request->id_ciudad_milton;
        $ciudad->save();
        return $ciudad;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Ciudad  $ciudad
     * @return \Illuminate\Http\Response
     */
    public function show(Ciudad $ciudad)
    {
        //
    }


    public function ciudades()
    {
        $ciudades = DB::SELECT("SELECT c.idciudad as id, c.nombre as label FROM `ciudad` c");

        return $ciudades;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Ciudad  $ciudad
     * @return \Illuminate\Http\Response
     */
    public function edit(Ciudad $ciudad)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Ciudad  $ciudad
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Ciudad $ciudad)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Ciudad  $ciudad
     * @return \Illuminate\Http\Response
     */
    public function destroy(Ciudad $ciudad)
    {
        //
    }
    public function getCiudadProvincia($id)
    {
        if ($id >0 ) {
            $dato = DB::table('ciudad as c')
            ->leftjoin('provincia as p','c.provincia_idprovincia', '=', 'p.idprovincia')
            ->select('c.*','p.*')
            ->where('c.idciudad',$id)
            ->get();
            return $dato;
        }
        $dato = DB::table('ciudad as c')
        ->leftjoin('provincia as p','c.provincia_idprovincia', '=', 'p.idprovincia')
        ->select('c.*','p.*')
        ->get();
        return $dato;
    }

    public function getProvincias()
    {
        $dato = DB::table('provincia')
        ->get();
        return $dato;
    }
}
