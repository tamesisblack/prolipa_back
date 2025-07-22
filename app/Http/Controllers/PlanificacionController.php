<?php

namespace App\Http\Controllers;

use App\Models\Planificacion;
use Illuminate\Http\Request;
use DB;
use App\Quotation;
class PlanificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // if (!$request->ajax()) return redirect('/') ;
        // $buscar = $request->buscar;
        // $criterio = $request->criterio;
        $idusuario = $request->idusuario;
        // $idInstitucion = auth()->user()->institucion_idInstitucion;
        // if($idInstitucion == 66){
        //     $planificaciones = DB::select("SELECT * FROM planificacion");
        // }else{
            $planificaciones = DB::select('CALL datosplanificaciond(?)',[$idusuario]);
        // }
        return $planificaciones;
    }

    public function aplicativo(Request $request)
    {
        $libros = DB::select('CALL datosplanificaciond(?)',[$request->idusuario]);
        return $libros;
    }

    public function planificacion(Request $request)
    {
        $planificacion = DB::select('SELECT * FROM planificacion inner join asignatura on asignatura.idasignatura = planificacion.asignatura_idasignatura');
        return $planificacion;
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
        Planificacion::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Planificacion  $planificacion
     * @return \Illuminate\Http\Response
     */
    public function show(Planificacion $planificacion)
    {
        //
    }

    public function planificacion_asignatura($id)
    {
        $idLibro = 0;
        //validar si no es un libro plus
        $validatePlus = DB::SELECT("SELECT * FROM libros_series ls
        WHERE ls.id_libro_plus = '$id'
        ");
        if(count($validatePlus) > 0){
           $idLibro = $validatePlus[0]->idLibro;
        }else{
            $idLibro = $id;
        }
        $planificaciones = DB::SELECT("SELECT p .*, l.nombrelibro, l.idlibro
         FROM planificacion p, libro l
         WHERE p.asignatura_idasignatura = l.asignatura_idasignatura
         AND l.Estado_idEstado = '1'
         AND p.Estado_idEstado = '1'
         AND l.idlibro = '$idLibro'
         ORDER BY p.idplanificacion");

        return $planificaciones;
    }


    public function planificacionesunidades(Request $request)
    {
        $planificaciones = DB::SELECT("SELECT p.idplanificacion, p.nombreplanificacion, p.descripcionplanificacion, p.unidad, p.webplanificacion, p.Estado_idEstado, p.asignatura_idasignatura FROM planificacion p, libro l WHERE p.asignatura_idasignatura = l.asignatura_idasignatura AND l.idlibro = $request->idlibro AND  (p.unidad = $request->unidad OR p.unidad = 0)");

        return $planificaciones;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Planificacion  $planificacion
     * @return \Illuminate\Http\Response
     */
    public function edit(Planificacion $planificacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Planificacion  $planificacion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Planificacion $planificacion)
    {
        $respuesta=DB::update('UPDATE planificacion SET nombreplanificacion = ? ,descripcionplanificacion = ? ,webplanificacion = ? ,Estado_idEstado = ? ,asignatura_idasignatura = ?  WHERE idplanificacion = ?',[$request->nombreplanificacion,$request->descripcionplanificacion,$request->webplanificacion,$request->Estado_idEstado,$request->asignatura_idasignatura,$request->idplanificacion]);
        return $respuesta;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Planificacion  $planificacion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::delete('DELETE FROM planificacion WHERE idplanificacion = ?',[$request->idplanificacion]);
    }
}
