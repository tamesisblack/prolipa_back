<?php

namespace App\Http\Controllers;
use DB;
use App\Quotation;
use App\Models\Nivel;
use Illuminate\Http\Request;

class NivelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
 

            // return csrf_token();
        $nivel = DB::SELECT("SELECT n.*, o.nombreofertaAcademica
        FROM nivel n ,ofertaacademica  o
        where  n.ofertaacademica_idofertaAcademica  = o.idofertaAcademica  
        and n.estado = '1'
        ORDER  BY idnivel DESC
        ");
        $oferta = DB::SELECT("SELECT ofertaacademica.* FROM ofertaacademica");
         return["nivel" => $nivel,"oferta"=>$oferta];
    }

    public function getNiveles(Request $request){
        $niveles = DB::SELECT("SELECT * FROM nivel
        WHERE orden IS NOt NULL
        AND orden <> 0
        AND estado = '1'
        ORDER BY orden + 0
        ");
        return $niveles;
    }

    public function select()
    {
        $nivel = DB::select('SELECT * FROM nivel');
        return  $nivel;
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
        if($request->idnivel){
       
            $nivel = Nivel::findOrFail($request->idnivel);
            $nivel->nombrenivel = $request->nombrenivel;
            $nivel->ofertaacademica_idofertaAcademica = $request->ofertaacademica_idofertaAcademica;
    
           }else{
               $nivel = new Nivel;
               $nivel->nombrenivel = $request->nombrenivel;
               $nivel->ofertaacademica_idofertaAcademica = $request->ofertaacademica_idofertaAcademica;
           }
           $nivel->save();
           if($nivel){
               return "Se guardo correctamente";
           }else{
               return "No se pudo guardar/actualizar";
           }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Nivel  $nivel
     * @return \Illuminate\Http\Response
     */
    public function show(Nivel $nivel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Nivel  $nivel
     * @return \Illuminate\Http\Response
     */
    public function edit(Nivel $nivel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Nivel  $nivel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Nivel $nivel)
    {
        //
    }

    public function niveleliminar(Request $request)
    {
        DB::table('nivel')
        ->where('idnivel', $request->idnivel)
        ->update(['estado' => '0']);

    }
}
