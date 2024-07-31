<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\SalleAsignaturas;

class SalleAsignaturasController extends Controller
{
    //api:GET/asignaturas_salle
    public function index(Request $request)
    {
        $asignaturas = DB::SELECT("SELECT asi.*, a.nombre_area,
            IF(asi.estado = '1','Activo','Desactivado') as estadoAsignatura,
            p.nombre as periodo, a.n_evaluacion
            FROM salle_asignaturas asi
            LEFT JOIN salle_areas  a ON asi.id_area = a.id_area
            LEFT JOIN salle_periodos_evaluacion p ON a.n_evaluacion = p.id
        ");
        return $asignaturas;
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
    public function store(Request $request){

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $asignaturas = DB::SELECT("SELECT * FROM salle_asignaturas WHERE id_asignatura = $id");

        return $asignaturas;
    }

    public function asignaturas_area_salle($id)
    {
        $asignaturas = DB::SELECT("SELECT * FROM `salle_asignaturas` WHERE `id_area` = $id");

        return $asignaturas;
    }
    public function asignaturas_docente_salle($docente,$n_evaluacion)
    {
        $asignaturas = DB::SELECT("SELECT asd.*, sa.nombre_asignatura, area.nombre_area,
            area.id_area
            FROM salle_asignaturas_has_docente asd, salle_asignaturas  sa, salle_areas area
            WHERE sa.id_asignatura = asd.id_asignatura
            AND area.id_area = sa.id_area
            AND area.n_evaluacion = '$n_evaluacion'
            AND `id_docente` = '$docente'
        ");
        $areas = DB::SELECT("SELECT asd.id_asignatura_docente,
            sa.id_asignatura, area.nombre_area, area.id_area
            FROM salle_asignaturas_has_docente asd,
            salle_asignaturas sa, salle_areas area
            WHERE sa.id_asignatura = asd.id_asignatura
            AND area.id_area = sa.id_area
            AND `id_docente` = '$docente'
            AND area.n_evaluacion = '$n_evaluacion'
            GROUP BY sa.id_area
        ");
        // $areas = DB::select("CALL `areas_asignatura_docente_salle` ($docente);");
        $basicas = DB::SELECT("SELECT asi.* FROM salle_asignaturas asi
        LEFT JOIN salle_areas  a ON asi.id_area = a.id_area
        WHERE a.area_basica = '1'
        AND a.n_evaluacion = '$n_evaluacion'");
        return ['asignaturas' => $asignaturas, 'areas'=>$areas, 'basicas'=>$basicas];
    }

    public function save_asignaturas_docente_salle(Request $request)
    {
        DB::INSERT('INSERT INTO `salle_asignaturas_has_docente`(`id_asignatura`, `id_docente`,`n_evaluacion`) VALUES (?,?,?)', [$request->id_asignatura, $request->id_usuario,$request->n_evaluacion]);
    }

    public function delete_asignaturas_docente_salle($id)
    {
        $asignatura = DB::DELETE("DELETE FROM `salle_asignaturas_has_docente` WHERE `id_asignatura_docente` = $id");

        return $asignatura;
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

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        // $asignatura = SalleAsignaturas::find($request->id);

        // if($asignatura->delete()){
        //     return 1;
        // }else{
        //     return 0;
        // }

    }
    public function crea_asignatura_salle(Request $request)
    {
        if( $request->id_asignatura >0 ){
            $asignatura = SalleAsignaturas::find($request->id_asignatura);
        }else{
            $asignatura = new SalleAsignaturas();
        }

        $asignatura->id_area = $request->id_area;
        $asignatura->nombre_asignatura = $request->nombre_asignatura;
        $asignatura->descripcion_asignatura = $request->descripcion_asignatura;
        $asignatura->cant_preguntas = $request->cant_preguntas;
        $asignatura->estado = $request->estado;
        $asignatura->save();

        return $asignatura;
    }
    public function eliminaAsignatura($id)
    {
        $contarSP = DB::table('salle_preguntas as sp')
        ->where('sp.id_asignatura','=',$id)
        ->count();
        $contarSDOC = DB::table('salle_asignaturas_has_docente as sdoc')
        ->where('sdoc.id_asignatura','=',$id)
        ->count();
        if($contarSP > 0 ||  $contarSDOC >0 ){
            return compact('contarSP','contarSDOC');
        }else{
            $asignatura = SalleAsignaturas::find($id);
            $asignatura->delete();
           return $asignatura;
        }
    }
}
