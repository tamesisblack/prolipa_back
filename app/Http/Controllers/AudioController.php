<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use Illuminate\Http\Request;
use DB;
use App\Quotation;
class AudioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // if (!$request->ajax()) return redirect('/') ;
        $idasignatura = $request->idasignatura;
        $audio = DB::select('CALL datosaudio(?)',[$idasignatura]);
        return $audio;
    }

    public function audio(Request $request)
    {
        $idasignatura = $request->idasignatura;
        $audio = DB::select('CALL datosaudio(?)',[$idasignatura]);
        return $audio;
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
        Audio::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Audio  $audio
     * @return \Illuminate\Http\Response
     */
    public function show(Audio $audio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Audio  $audio
     * @return \Illuminate\Http\Response
     */
    public function edit(Audio $audio)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Audio  $audio
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Audio $audio)
    {
        $respuesta=DB::update('UPDATE audio SET nombreaudio = ? ,descripcionaudio = ? ,webaudio = ? ,Estado_idEstado = ? ,asignatura_idasignatura = ?   WHERE idaudio = ?',[$request->nombreaudio,$request->descripcionaudio,$request->webaudio,$request->Estado_idEstado,$request->asignatura_idasignatura,$request->idaudio]);
        return $respuesta;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Audio  $audio
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::delete('DELETE FROM audio WHERE idaudio = ?',[$request->idaudio]);
    }
}
