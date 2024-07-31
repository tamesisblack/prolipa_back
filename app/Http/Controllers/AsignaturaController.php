<?php

namespace App\Http\Controllers;
use DB;
use App\Quotation;
use App\Models\Asignatura;
use Illuminate\Http\Request;

class AsignaturaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // return csrf_token();
        $asignatura = DB::select("SELECT ag.*, a.idarea,a.nombrearea,n.idnivel,n.nombrenivel
         FROM asignatura ag
         left join nivel n on n.idnivel = ag.nivel_idnivel
         left join area a on a.idarea = ag.area_idarea
         order by ag.idasignatura desc");

        $nivel = DB::SELECT("SELECT nivel.* FROM nivel ORDER BY idnivel DESC");
        $area = DB::SELECT("SELECT area.* FROM area WHERE estado = '1' ORDER BY idarea DESC");
        return["asignatura" => $asignatura,"nivel"=>$nivel,"area" => $area]; 
    }

    public function cambiarEstadoAsignatura(Request $request){
        $asignatura = Asignatura::findOrFail($request->asignatura_id);
        $asignatura->estado = $request->estado;
        $asignatura->save();
        if($asignatura){
            return ["status" => "1","Se cambio de estado correctamente"];
        }else{
            return ["status" => "0","No se pudo cambiar de estado"];
        }
    }

    public function asignatura(Request $request)
    {
        $asignatura = DB::select('SELECT * FROM asignatura 
        left join nivel on nivel.idnivel = asignatura.nivel_idnivel
        left join area on area.idarea = asignatura.area_idarea
        WHERE asignatura.estado = "1" 
        AND asignatura.tipo_asignatura = 1
        AND asignatura.nombreasignatura  NOT LIKE "%PLUS%"
        order by asignatura.idasignatura desc');
        return $asignatura;
    }

    public function select()
    {
        $asignatura = Asignatura::all();
        return $asignatura;
    }

    public function temas(Request $request){
        $temas = DB::SELECT("SELECT * FROM temas WHERE unidad = ? AND id_asignatura = ? AND estado = 1",[$request->unidad,$request->asignatura]);
        return $temas;
    }

    public function asigTemas(Request $request){
        $temas = DB::SELECT("SELECT * FROM temas_has_contenido WHERE contenido_idcontenido  = ?",[$request->idcontenido]);
        return $temas;
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
        if($request->otraAsignatura){
            $asignatura = new Asignatura;
            $asignatura->nombreasignatura = $request->nombreasignatura;
            $asignatura->area_idarea  = $request->area_idarea;
            $asignatura->nivel_idnivel   = $request->nivel_idnivel;
            $asignatura->tipo_asignatura  = $request->tipo_asignatura; 
            $asignatura->save();
            return $asignatura;
        }
        if($request->idasignatura){
    
        $asignatura = Asignatura::findOrFail($request->idasignatura);
        $asignatura->nombreasignatura = $request->nombreasignatura;
        $asignatura->area_idarea  = $request->area_idarea;
        $asignatura->nivel_idnivel   = $request->nivel_idnivel;
        $asignatura->tipo_asignatura  = $request->tipo_asignatura;    
        }else{
            $asignatura = new Asignatura;
            $asignatura->nombreasignatura = $request->nombreasignatura;
            $asignatura->area_idarea  = $request->area_idarea;
            $asignatura->nivel_idnivel   = $request->nivel_idnivel;
            $asignatura->tipo_asignatura  = $request->tipo_asignatura;    
        }
        $asignatura->save();
        if($asignatura){
            return "Se guardo correctamente";
        }else{
            return "No se pudo guardar/actualizar";
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Asignatura  $asignatura
     * @return \Illuminate\Http\Response
     */
    public function show($idusuario)
    {
        $usuario = DB::select("CALL `asignaturasDocente` ( $idusuario );");
        return $usuario;
    }



    public function asignaturasDocente($id)
    {
        $asignatura = DB::SELECT("SELECT a.idasignatura as id, a.nombreasignatura as label FROM asignaturausuario au, asignatura a WHERE au.asignatura_idasignatura = a.idasignatura AND au.usuario_idusuario = $id AND a.estado = '1' ORDER BY a.nombreasignatura");
        return $asignatura;
    }

    
    public function asignaturasDoc($id)
    {   
        if($id == 0){
            $asignatura = DB::SELECT("SELECT a.idasignatura as id, a.idasignatura, a.nombreasignatura as label, a.tipo_asignatura FROM asignatura a WHERE a.estado = '1' AND a.tipo_asignatura = 1 ORDER BY a.nombreasignatura");
        }else{
            $asignatura = DB::SELECT("SELECT a.idasignatura as id, a.idasignatura, a.nombreasignatura as label, a.tipo_asignatura FROM asignaturausuario au, asignatura a WHERE au.asignatura_idasignatura = a.idasignatura AND au.usuario_idusuario = $id AND a.estado = '1' ORDER BY a.nombreasignatura");
        }

        return $asignatura;
    }


    
    public function asignaturasCreaDoc($id)
    {
        $asignatura = DB::SELECT("SELECT a.idasignatura as id, a.nombreasignatura as label, a.tipo_asignatura FROM asignaturausuario au, asignatura a WHERE au.asignatura_idasignatura = a.idasignatura AND au.usuario_idusuario = $id AND a.tipo_asignatura = 0 AND a.estado = '1' ORDER BY a.nombreasignatura");

        return $asignatura;
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Asignatura  $asignatura
     * @return \Illuminate\Http\Response
     */
    public function edit(Asignatura $asignatura)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Asignatura  $asignatura
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Asignatura $asignatura)
    {
        DB::UPDATE("UPDATE `asignatura` SET `nombreasignatura`=?,`area_idarea`=?,`nivel_idnivel`=? WHERE idasignatura = ?",[$request->nombre,$request->area,$request->nivel,$request->id]);
        return $asignatura;

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Asignatura  $asignatura
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::delete('DELETE FROM asignatura WHERE idasignatura = ?',[$request->idasignatura]);
        $this->destroyAsigCurso($request->idasignatura);
        $this->destroyAsigUser($request->idasignatura);
    }


    public function destroyAsigCurso($id)
    {
        DB::UPDATE("UPDATE curso SET id_asignatura = 0 WHERE id_asignatura = $id");
    }

    public function destroyAsigUser($id)
    {
        DB::DELETE("DELETE FROM asignaturausuario WHERE asignatura_idasignatura = $id");
    }

}
