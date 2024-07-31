<?php

namespace App\Http\Controllers;
use DB;
use App\Models\PeriodoInstitucion;
use Illuminate\Http\Request;

class PeriodoInstitucionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $datosValidados=$request->validate([
            'periodo_escolar' => 'required',
        ]);
        $periodoInstitucion = new PeriodoInstitucion();
        $periodoInstitucion->institucion_idInstitucion = $request->idInstitucion;
        $periodoInstitucion->periodoescolar_idperiodoescolar = $request->periodo_escolar;
        $periodoInstitucion->save();
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
     * @param  \App\PeriodoInstitucion  $periodoInstitucion
     * @return \Illuminate\Http\Response
     */
    public function show(PeriodoInstitucion $periodoInstitucion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\PeriodoInstitucion  $periodoInstitucion
     * @return \Illuminate\Http\Response
     */
    public function edit(PeriodoInstitucion $periodoInstitucion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\PeriodoInstitucion  $periodoInstitucion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PeriodoInstitucion $periodoInstitucion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\PeriodoInstitucion  $periodoInstitucion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::delete('DELETE FROM periodoescolar_has_institucion WHERE id = ?',[$request->id]);
    }

    //DIRECTOR, instituciones con periodo activo, asignadas a los directores
    public function institucionesDirector($id)
    {
        $instituciones = DB::SELECT("SELECT  i.*
        FROM  periodoescolar_has_institucion phi, periodoescolar pe, institucion i
        WHERE i.idinstitucion = $id
        AND phi.institucion_idInstitucion = i.idinstitucion
        AND phi.periodoescolar_idperiodoescolar = pe.idperiodoescolar
        AND pe.estado = '1' ");
        return $instituciones;
    }
    public function periodosXInstitucion($id)
    {
        $dato = DB::table('periodoescolar_has_institucion as phi')
        ->where('institucion_idinstitucion','=',$id)
        ->leftjoin('periodoescolar as pe','phi.periodoescolar_idperiodoescolar','=','pe.idperiodoescolar')
        ->leftjoin('region as r','pe.region_idregion','r.idregion')
        ->select('pe.descripcion','pe.periodoescolar','pe.estado','r.nombreregion','phi.id','phi.updated_at', 'pe.idperiodoescolar')
        ->get();
        return $dato;
    }
    public function verificaPeriodoInstitucion(Request $request)
    {
        $verifica = DB::table('periodoescolar_has_institucion')
        ->where('periodoescolar_idperiodoescolar','=',$request->idperiodoescolar)
        ->where('institucion_idinstitucion','=',$request->idinstitucion )
        ->count();
        if ($verifica > 0) {
            return $verifica;
        }else{
            $dato = new PeriodoInstitucion();
            $dato->periodoescolar_idperiodoescolar = $request->idperiodoescolar;
            $dato->institucion_idinstitucion = $request->idinstitucion;
            $dato->save();
            return $dato;            
        }
    }
    public function eliminarPeriodosXInstitucion($id)
    {
        $dato = PeriodoInstitucion::find($id);
        $dato->delete();
        return $dato;
    }
    public function cambiar_periodo_curso(Request $request)
    {   
        $curso = DB::UPDATE('UPDATE `curso` SET `id_periodo` = ? WHERE `idcurso` = ?',[$request->id_periodo, $request->id_curso]);  
        return  $curso;
    }

}
