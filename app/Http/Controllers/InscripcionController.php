<?php

namespace App\Http\Controllers;

use App\Models\inscripcion;
use Illuminate\Http\Request;
use DB;
use Mail;
class InscripcionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $docentes = DB::SELECT("SELECT * FROM inscripcion join seminario on inscripcion.seminario_idseminario = seminario.idseminario WHERE seminario.idseminario = ?",[$request->idcurso]);
        return $docentes;
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
        $datosValidados=$request->validate([
            'cedula' => 'required|max:10',
            'nombres' => 'required',
            'apellidos' => 'required',
            'celular' => 'required',
            'email' => 'required|email',
            'idciudad' => 'required',
            'idnivel' => 'required',
            'asignatura' => 'required',
            'idseminario' => 'required',
        ]);

        $comprueba = DB::SELECT("SELECT * FROM inscripcion WHERE cedula = ? AND seminario_idseminario = ?",[$request->cedula,$request->idseminario]);
        $contador = DB::SELECT("CALL `inscripcion` (?)
        ",[$request->idseminario]);

        foreach ($contador as $key => $value) {
            $total = $value->total;
            $cantidad_participantes = $value->cantidad_participantes - 10;
        }

        if($total <= $cantidad_participantes){
            if(empty($comprueba) && $total <= $cantidad_participantes){
                $inscripcion = new inscripcion();
                $inscripcion->cedula = $request->cedula;
                $inscripcion->nombres = $request->nombres;
                $inscripcion->apellidos = $request->apellidos;
                $inscripcion->celular = $request->celular;
                $inscripcion->correo = $request->email;
                $inscripcion->idciudad = $request->idciudad;
                $inscripcion->idinstitucion = $request->idInstitucion;
                $inscripcion->seminario_idseminario = $request->idseminario;
                $inscripcion->idnivel = $request->idnivel;
                $inscripcion->asignatura = $request->asignatura;
                $inscripcion->institucion = $request->institucion;
                $inscripcion->save();
                
                $data = array(
                    'name'=>"Prolipa",
                    'email'=>$request->email,
                );
                
                Mail::send('plantilla.inscripcion', $data,function ($message){
                    $message->from($_GET['email'], 'Prolipa');
                    $message->to($_GET['email'])->subject('Información Prolipa');
                });
            }else{
                $repuesta = '0';
                return $repuesta;
            }
        }else{
            return $repuesta = '1';
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\inscripcion  $inscripcion
     * @return \Illuminate\Http\Response
     */
    public function show(inscripcion $inscripcion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\inscripcion  $inscripcion
     * @return \Illuminate\Http\Response
     */
    public function edit(inscripcion $inscripcion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\inscripcion  $inscripcion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, inscripcion $inscripcion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\inscripcion  $inscripcion
     * @return \Illuminate\Http\Response
     */
    public function destroy(inscripcion $inscripcion)
    {
        //
    }
}