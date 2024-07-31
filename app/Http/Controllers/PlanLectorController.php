<?php

namespace App\Http\Controllers;
use DB;
use App\Quotation;
use App\Models\PlanLector;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Support\Facades\Storage;
class PlanLectorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //$buscar = $request->buscar;
        //$criterio = $request->criterio;
        $idusuario = $request->idusuario;
        // $idInstitucion = auth()->user()->institucion_idInstitucion;
        // if($idInstitucion == 66){
        //     $planlector = DB::select("CALL planlectorProlipa()");
        // }else{
        // }
        $planlector = DB::select('CALL datosplanlectorsd(?)',[$idusuario]);
        return $planlector;
    }

    public function Historial(Request $request){
        $date = new DateTime();
        $idusuario = auth()->user()->idusuario;
        $idplanlector = $request->idplanlector;
        $fecha = $date->format('y-m-d');
        $hora = $date->format('H:i:s');
        DB::insert("INSERT INTO `planlector_has_usuario`(`planlector_idplanlector`, `usuario_idusuario`, `fecha`, `hora`) VALUES (?,?,?,?)",[$idplanlector,$idusuario,$fecha,$hora]);
    }

    public function aplicativo(Request $request)
    {
        $libros = DB::select('CALL datosplanlectorsd(?)',[$request->idusuario]);
        return $libros;
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
        PlanLector::create($request->all());
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\PlanLector  $planLector
     * @return \Illuminate\Http\Response
     */
    public function show(PlanLector $planLector)
    {
        //
    }

    public function planlector(Request $request)
    {
        $planlector = DB::select('SELECT * FROM planlector');
        return $planlector;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\PlanLector  $planLector
     * @return \Illuminate\Http\Response
     */
    public function edit(PlanLector $planLector)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\PlanLector  $planLector
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PlanLector $planLector)
    {
        $respuesta=DB::update('UPDATE planlector SET nombreplanlector = ? ,descripcionplanlector = ? ,webplanlector = ? ,exeplanlector = ? ,pdfsinguia = ? ,pdfconguia = ? ,guiadidactica = ? ,Estado_idEstado = ? ,asignatura_idasignatura = ? ,zipplanlector = ?  WHERE idplanlector = ?',[$request->nombreplanlector,$request->descripcionplanlector,$request->webplanlector,$request->exeplanlector,$request->pdfsinguia,$request->pdfconguia,$request->guiadidactica,$request->Estado_idEstado,$request->asignatura_idasignatura,$request->zipplanlector,$request->idplanlector]);
        return $respuesta;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\PlanLector  $planLector
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::delete('DELETE FROM planlector WHERE idplanlector = ?',[$request->idplanlector]);
    }
}