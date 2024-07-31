<?php

namespace App\Http\Controllers;

use App\Models\ReporteEstudiante;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class ReporteEstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    //    $data = new ArrayObject();
       $array = array();
       $cont = 0;
       $reporte = DB::SELECT("CALL reporteCostaCodigos()");
       foreach ($reporte as $key => $post) {
            echo $idinstitucion=$post->idInstitucion;
             $estudiantes = DB::SELECT("SELECT * FROM estudiante WHERE codigo = ?",[$post->codigo]);
             foreach ($estudiantes as $aux => $value) {
                 $idusuario = $value->usuario_idusuario;
                 DB::SELECT("UPDATE `usuario` SET `institucion_idInstitucion`= ? WHERE `idusuario` = ?",[$idinstitucion,$idusuario]);
             }


            // foreach ($estudiantes as $aux => $value) {
            //     $contador = DB::SELECT("SELECT COUNT(*) as cont FROM registro_usuario WHERE usuario_idusuario = ?",[$value->idusuario]);
            //     // $cont = $cont + $contador->cont;
            // }
       }
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ReporteEstudiante  $reporteEstudiante
     * @return \Illuminate\Http\Response
     */
    public function show(ReporteEstudiante $reporteEstudiante)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ReporteEstudiante  $reporteEstudiante
     * @return \Illuminate\Http\Response
     */
    public function edit(ReporteEstudiante $reporteEstudiante)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ReporteEstudiante  $reporteEstudiante
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReporteEstudiante $reporteEstudiante)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ReporteEstudiante  $reporteEstudiante
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReporteEstudiante $reporteEstudiante)
    {
        //
    }
}
