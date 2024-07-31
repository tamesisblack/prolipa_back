<?php

namespace App\Http\Controllers;

use App\Models\Cuaderno;
use Illuminate\Http\Request;
use DB;
use DateTime;
use App\Quotation;
class CuadernoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $idusuario = $request->idusuario;
        // $idInstitucion = auth()->user()->institucion_idInstitucion;
        // if($idInstitucion == 66){
        //     $cuaderno = DB::select("SELECT * FROM cuaderno ORDER BY  `cuaderno`.`asignatura_idasignatura` ASC ");
        // }else{
            $cuaderno = DB::select('CALL datoscuadernosd(?)',[$idusuario]);
        // }
        return $cuaderno;
    }

    public function cuadernos_usuario_libro(Request $request)
    {
        $cuadernos = DB::SELECT("SELECT * FROM cuaderno c left join asignaturausuario a on c.asignatura_idasignatura = a.asignatura_idasignatura WHERE a.usuario_idusuario = $request->id_usuario AND a.asignatura_idasignatura = $request->id_asignatura");

        return $cuadernos;
    }

    public function Historial(Request $request){
        $date = new DateTime();
        $idusuario = auth()->user()->idusuario;
        $idcuaderno = $request->idcuaderno;
        $fecha = $date->format('y-m-d');
        $hora = $date->format('H:i:s');
        DB::insert("INSERT INTO `cuaderno_has_usuario`(`cuaderno_idcuaderno`, `usuario_idusuario`, `fecha`, `hora`) VALUES (?,?,?,?)",[$idcuaderno,$idusuario,$fecha,$hora]);
    }

    public function aplicativo(Request $request)
    {
        $libros = DB::select('CALL datoscuadernosd(?)',[$request->idusuario]);
        return $libros;
    }

    public function cuaderno(Request $request)
    {
        
        $cuaderno = DB::select('SELECT * FROM cuaderno');
        return $cuaderno;
    }

    
    //api::get>>/getCuadernos
    public function getCuadernos(Request $request){
        $cuaderno = DB::SELECT("SELECT c.* ,a.nombreasignatura
        FROM cuaderno c 
        LEFT JOIN asignatura a ON a.idasignatura = c.asignatura_idasignatura 
        WHERE c.Estado_idEstado  = '1'
        ORDER  BY c.idcuaderno DESC
       ");

       $asignaturas = DB::SELECT("SELECT asignatura.* FROM asignatura WHERE estado = '1' ORDER BY idasignatura DESC");
    
        return["cuaderno" => $cuaderno, "asignaturas" => $asignaturas];
    }

    //api::post/cuadernoEliminar
    public function cuadernoEliminar(Request $request){
        DB::table('cuaderno')
        ->where('idcuaderno', $request->idcuaderno)
        ->update(['Estado_idEstado' => '2']);
    }

  

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->crud){

            if($request->idcuaderno){
                //para guardar el cuaderno
                $cuaderno = Cuaderno::findOrFail($request->idcuaderno);
                $cuaderno->nombrecuaderno =             $request->nombrecuaderno;
                $cuaderno->descripcioncuaderno =        $request->descripcioncuaderno;
                $cuaderno->webcuaderno =                $request->webcuaderno;
                $cuaderno->execuaderno =                $request->execuaderno;
                $cuaderno->pdfconguia =                 $request->pdfconguia;
                $cuaderno->pdfsinguia =                 $request->pdfsinguia;
                $cuaderno->guiadidactica =              $request->guiadidactica;
                $cuaderno->zipcuaderno =                $request->zipcuaderno;
                $cuaderno->asignatura_idasignatura =    $request->asignatura_idasignatura;
                $cuaderno->portada =                    $request->portada;

            }else{
                //para editar el cuaderno
                    $cuaderno = new Cuaderno;
                    $cuaderno->nombrecuaderno =             $request->nombrecuaderno;
                    $cuaderno->descripcioncuaderno =        $request->descripcioncuaderno;
                    $cuaderno->webcuaderno =                $request->webcuaderno;
                    $cuaderno->execuaderno =                $request->execuaderno;
                    $cuaderno->pdfconguia =                 $request->pdfconguia;
                    $cuaderno->pdfsinguia =                 $request->pdfsinguia;
                    $cuaderno->guiadidactica =              $request->guiadidactica;
                    $cuaderno->zipcuaderno =                $request->zipcuaderno;
                    $cuaderno->asignatura_idasignatura =    $request->asignatura_idasignatura;
                    $cuaderno->Estado_idEstado =            "1";
            }

               $cuaderno->save();

               if($cuaderno){
                    return ["status" => "1", "message" => "Se guardo correctamente"];
               }else{
                    return ["status" => "0", "message" => "No se pudo guardar"];
               }

        }
        Cuaderno::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Cuaderno  $cuaderno
     * @return \Illuminate\Http\Response
     */
    public function show(Cuaderno $cuaderno)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Cuaderno  $cuaderno
     * @return \Illuminate\Http\Response
     */
    public function edit(Cuaderno $cuaderno)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Cuaderno  $cuaderno
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cuaderno $cuaderno)
    {
        $respuesta=DB::update('UPDATE cuaderno SET nombrecuaderno = ? ,descripcioncuaderno = ? ,webcuaderno = ? ,execuaderno = ? ,pdfsinguia = ? ,pdfconguia = ? ,guiadidactica = ? ,Estado_idEstado = ? ,asignatura_idasignatura = ? ,zipcuaderno = ?  WHERE idcuaderno = ?',[$request->nombrecuaderno,$request->descripcioncuaderno,$request->webcuaderno,$request->execuaderno,$request->pdfsinguia,$request->pdfconguia,$request->guiadidactica,$request->Estado_idEstado,$request->asignatura_idasignatura,$request->zipcuaderno,$request->idcuaderno]);
        return $respuesta;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Cuaderno  $cuaderno
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::delete('DELETE FROM cuaderno WHERE idcuaderno = ?',[$request->idcuaderno]);
    }
}
