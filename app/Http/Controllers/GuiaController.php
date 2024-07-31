<?php

namespace App\Http\Controllers;

use App\Models\Guia;
use Illuminate\Http\Request;
use DB;
use App\Quotation;
class GuiaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $idusuario = $request->idusuario;
        // $idInstitucion = auth()->user()->institucion_idInstitucion;
        // if($idInstitucion == 66){
        //     $guias = DB::select("SELECT * FROM guia");
        // }else{
            $guias = DB::select('CALL datosguiasd(?)',[$idusuario]);
        // }
        return $guias;
    }

    public function aplicativo(Request $request)
    {
        $libros = DB::select('CALL datosguiasd(?)',[$request->idusuario]);
        return $libros;
    }

    public function guia(Request $request)
    {
        $guia = DB::select('SELECT * FROM guia');
        return $guia;
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
        Guia::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Guia  $guia
     * @return \Illuminate\Http\Response
     */
    public function show(Guia $guia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Guia  $guia
     * @return \Illuminate\Http\Response
     */
    public function edit(Guia $guia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Guia  $guia
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Guia $guia)
    {
        $respuesta=DB::update('UPDATE guia SET nombreguia = ? ,descripcionguia = ? ,webguia = ? ,exeguia = ? ,pdfsinguia = ? ,pdfconguia = ? ,guiadidactica = ? ,Estado_idEstado = ? ,asignatura_idasignatura = ? ,zipguia = ?  WHERE idguia = ?',[$request->nombreguia,$request->descripcionguia,$request->webguia,$request->exeguia,$request->pdfsinguia,$request->pdfconguia,$request->guiadidactica,$request->Estado_idEstado,$request->asignatura_idasignatura,$request->zipguia,$request->idguia]);
        return $respuesta;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Guia  $guia
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::delete('DELETE FROM guia WHERE idguia = ?',[$request->idguia]);
    }
}
