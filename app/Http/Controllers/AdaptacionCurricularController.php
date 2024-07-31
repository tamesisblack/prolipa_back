<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class AdaptacionCurricularController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //PARA TRAER LAS ADAPTACIONES 
        if($request->admin){
            $proyectos = DB::SELECT("SELECT p.*, CONCAT(u.nombres, ' ',u.apellidos) as responsable, a.nombreasignatura
            FROM adaptaciones_curriculares p 
            LEFT JOIN usuario u ON p.idusuario = u.idusuario
            LEFT JOIN asignatura a ON a.idasignatura = p.asignatura_id
            ORDER BY p.id DESC
            ");  
        }
        if($request->docentes){
            $proyectos = DB::SELECT("SELECT p.*, CONCAT(u.nombres, ' ',u.apellidos) as responsable, a.nombreasignatura
            FROM adaptaciones_curriculares p 
            LEFT JOIN usuario u ON p.idusuario = u.idusuario
            LEFT JOIN asignatura a ON a.idasignatura = p.asignatura_id
            WHERE p.estado = '1'
            ORDER BY p.id DESC
            ");  
        }
        
        $datos = [];
        //traer las asignaturas de los proyectos
        foreach($proyectos as $key => $item){
            //TRAER LOS FILES DEL PROYECTO
            $files = DB::SELECT("SELECT f.*
                FROM adaptaciones_files f
                WHERE  f.adaptacion_id = '$item->id'
            "); 
            $datos[$key] = [
                "id" =>              $item->id,
                "idusuario" =>       $item->idusuario,
                "responsable" =>     $item->responsable,
                "grupo_usuario"=>    $item->grupo_usuario,
                "nombre" =>          $item->nombre,
                "descripcion" =>     $item->descripcion,
                "nombreasignatura" =>$item->nombreasignatura,
                "asignatura_id" =>   $item->asignatura_id,
                "estado"    =>       $item->estado,
                "files" =>           $files,
                "created_at" =>      $item->created_at,
                "updated_at" =>      $item->updated_at
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
