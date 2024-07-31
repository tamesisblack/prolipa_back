<?php

namespace App\Http\Controllers;

use App\Models\tipoJuegos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoJuegosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $juegos = DB::SELECT("SELECT * FROM j_tipos_juegos");

        return $juegos;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\tipoJuegos  $tipoJuegos
     * @return \Illuminate\Http\Response
     */
    public function unidadesAsignatura($id)
    {
        $unidadesA = DB::SELECT("SELECT * FROM unidades_libros WHERE id_libro =  $id");

        return $unidadesA;
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
        if( $request->id_tipo_juego  ){
            $juego = tipoJuegos::find($request->id_tipo_juego );
        }else{
            $juego = new tipoJuegos();
        }

        $ruta = public_path('/images/imagenes_juegos/portadas');
        if(!empty($request->file('imagen_juego'))){
            $file = $request->file('imagen_juego');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            $juego->imagen_juego  = $fileName;
        }
        $juego->nombre_tipo_juego = $request->nombre_tipo_juego;
        $juego->descripcion_tipo_juego = $request->descripcion_tipo_juego;
        $juego->estado = $request->estado;

        $juego->save();

        return $juego;
    }

    

    /**
     * Display the specified resource.
     *
     * @param  \App\tipoJuegos  $tipoJuegos
     * @return \Illuminate\Http\Response
     */
    public function show(tipoJuegos $tipoJuegos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\tipoJuegos  $tipoJuegos
     * @return \Illuminate\Http\Response
     */
    public function edit(tipoJuegos $tipoJuegos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\tipoJuegos  $tipoJuegos
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, tipoJuegos $tipoJuegos)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\tipoJuegos  $tipoJuegos
     * @return \Illuminate\Http\Response
     */
    public function destroy(tipoJuegos $tipoJuegos)
    {
        //
    }
}
