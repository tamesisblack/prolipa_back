<?php

namespace App\Http\Controllers;

use App\Models\Fichas;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class FichasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->fichasEstudiante){
            return $this->fichasEstudiante($request->curso);
        }
        $dato = DB::table('fichas as f')
        ->leftjoin('asignatura as a','f.id_asignatura','=','a.idasignatura')
        ->select('f.*','a.idasignatura','a.nombreasignatura')
        ->orderby('f.created_at','desc')
        ->get();
        if(empty($dato)){
            return $dato;
        }
        $datos = [];
        foreach($dato as $key => $item){
            $files = DB::SELECT("SELECT * FROM fichas_files WHERE ficha_id = '$item->id'");
            $datos[$key] = [
                "id"                => $item->id,
                "titulo"            => $item->titulo,
                "tipo"              => $item->tipo,
                "estado"            => $item->estado,
                "id_asignatura"     => $item->id_asignatura,
                "id_unidad"         => $item->id_unidad,
                "created_at"        => $item->created_at,
                "updated_at"        => $item->updated_at,
                "idasignatura"      => $item->idasignatura,
                "nombreasignatura"  => $item->nombreasignatura,
                "files"             => $files
            ];
        }
        return $datos;
    }
    public function cursoDocentesFicha(Request $request){
        $cursos = DB::SELECT("SELECT c.* 
        FROM curso c
        WHERE c.idusuario   = '$request->id_usuario'
        AND c.id_asignatura = '$request->id_asignatura'
        AND c.id_periodo    = '$request->periodo_id'
        AND c.estado       = '1'
        GROUP BY c.codigo
        ");
        $datos = [];
        foreach($cursos as $key => $item){
            $cursos= DB::SELECT("SELECT * FROM fichas_has_cursos cm 
            WHERE cm.codigo_curso = '$item->codigo' 
            AND cm.ficha_id = '$request->ficha_id'
            ");
            $datos[$key] = [
                "idcurso"       => $item->idcurso,
                "id_asignatura" => $item->id_asignatura,
                "nombre"        => $item->nombre,
                "seccion"       => $item->seccion,
                "materia"       => $item->materia,
                "aula"          => $item->aula,
                "codigo"        => $item->codigo,
                "idusuario"     => $item->idusuario,
                "id_periodo"    => $item->id_periodo,
                "generado"      => $item->generado,
                "estado"        => $item->estado,
                "getCurso"      => $cursos,
            ];
        }
        return $datos;
    }
    public function fichasEstudiante($curso){
        $fichas = DB::SELECT("SELECT *
        FROM fichas_has_cursos f
        WHERE f.codigo_curso = '$curso' ");
        if(empty($fichas)){
            return $fichas;
        }
        $id = $fichas[0]->ficha_id;
        $dato = DB::table('fichas as f')
        ->leftjoin('asignatura as a','f.id_asignatura','=','a.idasignatura')
        ->select('f.*','a.idasignatura','a.nombreasignatura')
        ->orderby('f.created_at','desc')
        ->Where('f.id','=',$id)
        ->get();
        if(empty($dato)){
            return $dato;
        }
        $datos = [];
        foreach($dato as $key => $item){
            $files = DB::SELECT("SELECT * FROM fichas_files WHERE ficha_id = '$item->id'");
            $datos[$key] = [
                "id"                => $item->id,
                "titulo"            => $item->titulo,
                "tipo"              => $item->tipo,
                "estado"            => $item->estado,
                "id_asignatura"     => $item->id_asignatura,
                "id_unidad"         => $item->id_unidad,
                "created_at"        => $item->created_at,
                "updated_at"        => $item->updated_at,
                "idasignatura"      => $item->idasignatura,
                "nombreasignatura"  => $item->nombreasignatura,
                "files"             => $files
            ];
        }
        return $datos;
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
       //asignar
       $cursos= DB::SELECT("SELECT * FROM fichas_has_cursos cm 
       WHERE cm.codigo_curso = '$request->codigo_curso'
       AND cm.ficha_id       = $request->ficha_id
       ");
        if( empty($cursos) ){
            DB::INSERT("INSERT INTO `fichas_has_cursos`(`codigo_curso`, `ficha_id`,`periodo_id`) VALUES ('$request->codigo_curso', $request->ficha_id,$request->periodo_id)");
            return ["status" => "1", "message" => "Asignado correctamente"];
        }else{
            return ["status" => "0", "message" => "Esta ficha ya se encuentra asignado a este curso"];
        }
    }
    //api:post/eliminarAsignacionFicha
    public function eliminarAsignacionFicha(Request $request){
        DB::DELETE("DELETE FROM fichas_has_cursos WHERE ficha_id ='$request->ficha_id' AND codigo_curso = '$request->codigo_curso' ");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Fichas  $fichas
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ficha = DB::SELECT("SELECT * FROM fichas WHERE id = '$id'");
        $files = DB::SELECT("SELECT * FROM fichas_files WHERE ficha_id = '$id'");
        return ["ficha" => $ficha, "files" => $files];
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Fichas  $fichas
     * @return \Illuminate\Http\Response
     */
    public function edit(Fichas $fichas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Fichas  $fichas
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Fichas $fichas)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Fichas  $fichas
     * @return \Illuminate\Http\Response
     */
    public function destroy(Fichas $fichas)
    {
        //
    }
}
