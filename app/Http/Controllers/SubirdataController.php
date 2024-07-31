<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubirdataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $institucion = "92";
             $sestudiante = DB::select("select c.libro_idlibro, c.libro,c.serie, c.idusuario, c.contrato, l.codigo_liquidacion,  u.nombres, u.apellidos,u.institucion_idInstitucion 
         from codigoslibros c, usuario u, libros_series l
         where c.idusuario  = u.idusuario  
         and c.libro_idlibro   = l.idLibro 
         and u.institucion_idInstitucion  = $institucion
         
        
      ");
      return $sestudiante;
       
    
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($institucion)
    {
        set_time_limit(60000);
        ini_set('max_execution_time', 60000);

  

        $contrato= DB::table('institucion')
        ->select('institucion.cod_contrato')
        ->where('idInstitucion', $institucion)
        
        ->get();
        $obtenercontrato = $contrato[0]->cod_contrato;

    //      $idsestudiante = DB::select("select c.codigo,  c.idusuario,  u.nombres, u.apellidos,u.institucion_idInstitucion 
    //      from codigoslibros c, usuario u
    //      where c.idusuario  = u.idusuario  
    //      and u.institucion_idInstitucion  = $institucion
        
        
    //   ");

      $idsestudiante = DB::select("select c.idusuario
      from codigoslibros c, usuario u
      where c.idusuario  = u.idusuario  
      and u.institucion_idInstitucion  = $institucion

     
   ");

    foreach ($idsestudiante as $key => $value) {
        DB::UPDATE("UPDATE codigoslibros SET contrato = ? WHERE idusuario = ?", [$obtenercontrato, $value->idusuario]);
    }
    
   return $idsestudiante;
     
    

       
    }

    //este codigo es para ver la liquidacion
//     $estudiante = DB::select("select COUNT(c.libro_idlibro) as cantidad , c.libro_idlibro, c.serie, c.contrato,l.nombre as libro, l.codigo_liquidacion, u.institucion_idInstitucion 
//     from codigoslibros c, usuario u, libros_series l
//     where c.idusuario  = u.idusuario  
//     and c.libro_idlibro   = l.idLibro 
//     and c.contrato = $contrato
//     GROUP BY  c.libro_idlibro, l.nombre  ,c.serie, c.contrato, l.codigo_liquidacion, u.institucion_idInstitucion 



// ");
//     return $estudiante;
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
