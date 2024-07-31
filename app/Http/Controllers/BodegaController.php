<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App;
use App\Models\BodegaCodigos;
use Illuminate\Support\Facades\DB;

class BodegaController extends Controller
{
  
    public function index(Request $request)
    {
      //busqueda Individual
      if($request->individual){
          $buscar = DB::SELECT("SELECT * FROM bodega_codigos  
          WHERE codigo = '$request->codigo'
          ORDER BY fecha_create DESC
          ");
          return $buscar;
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
        // echo "hola1";
        set_time_limit(6000);
        ini_set('max_execution_time', 6000);
        $codigos = json_decode($request->data_codigos); 

        // $tam = sizeof($codigos)-1;

        // $repetidos = [];
        // $porcentaje = 0;


        // for( $i=0; $i<$tam; $i++ ){
        //     $codigo = new BodegaCodigos;

        //         $getCodigo  = $codigos[0]->codigo;
               
        //         $verificar_codigo = DB::select("SELECT * FROM bodega_codigos
        //         where codigo  = '$getCodigo'");

        //         if(count($verificar_codigo) >1){
        //             $codigoRepetido = $codigos[$i]->codigo;
        //             $repetidos[$i] = [
        //                 "codigos" => $codigoRepetido,
        //                 "repetidas" => count($verificar_codigo)-1
        //             ];
        //         }
                
        //         $codigo->codigo = $codigos[$i]->codigo;
        //         $codigo->observacion = $request->observacion;
                
        //         $codigo->save();
        //         $porcentaje++;
             
                
              
        // }

       
        
        $porcentaje = 0;
        $repetidos = [];
        foreach($codigos as $key => $item){
            $validar = DB::select("SELECT * FROM bodega_codigos
            where codigo  = '$item->codigo'");

            if(count($validar) >1){
                $repetidos[$key] = [
                    "codigos" =>  $item->codigo,
                    "repetidas" => count($validar)-1
                ] ;
            }
            
            $codigo = new BodegaCodigos;
            $codigo->codigo = $item->codigo;
            $codigo->observacion = $request->observacion;
            $codigo->usuario_editor = $request->usuario;
            
            $codigo->save();
            $porcentaje++;
        }
       
        return ["codigosRepetidos" => $repetidos,"porcentaje" => $porcentaje,"production" =>"yes"];



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

    public function registro_codigo(request $request)
    {
   
        $validar = DB::select("SELECT * FROM bodega_codigos
        where codigo  = '$request->codigo'");

       

        $repetidos = [];
        if(count($validar) >1){
            $repetidos = [
                "codigos" =>  $request->codigo,
                "repetidas" => count($validar)-1
            ];


        }
        

       

        // return $request;
        $dato = new BodegaCodigos;
        $dato->codigo = $request->codigo;
        $dato->usuario_editor = $request->usuario;
        $dato->estado_institucion_temporal =$request->estado_institucion_temporal;
        //si crean una insitucion temporal
        if($request->estado_institucion_temporal == 1){
            $dato->periodo_id = $request->periodo_id_temporal;
            $dato->institucion_id_temporal = $request->institucion_id_temporal;
            $dato->nombre_institucion_temporal = $request->nombreInstitucion;
            $dato->institucion_id = "";
        } 
        if($request->estado_institucion_temporal == 0){
            $dato->institucion_id = $request->institucion_id;
            $dato->institucion_id_temporal = "";
            $dato->nombre_institucion_temporal = "";
               //para traer el periodo
            $buscarPeriodo = $this->traerPeriodo($request->institucion_id);
            if($buscarPeriodo["status"] == "1"){
                $obtenerPeriodo = $buscarPeriodo["periodo"][0]->periodo;
                $dato->periodo_id = $obtenerPeriodo;
                
            }
        }

        $dato->observacion = $request->observacion;
       
        $dato->save();
        $data = [];
        $data = [
            "codigosRepetidos" => $repetidos
        ];
        return $data;
        //  return $request->codigo;
    }

    public function bodegaFiltro(Request $request){
        $busqueda = DB::SELECT("SELECT DISTINCT observacion FROM bodega_codigos 
        WHERE observacion LIKE '%$request->busqueda%'
        ");
        return $busqueda;
    }

    public function traerPeriodo($institucion_id){
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo , periodoescolar AS descripcion FROM periodoescolar WHERE idperiodoescolar = ( 
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir         
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$institucion_id'))
        ");
        if(count($periodoInstitucion)>0){
            return ["status" => "1", "message"=>"correcto","periodo" => $periodoInstitucion];
        }else{
            return ["status" => "0", "message"=>"no hay periodo"];
        }
    }
    public function get_codigos()
    {
       
        $codigo = DB::table('bodega_codigos as cap')
        ->select('cap.id','cap.codigo', 'cap.observacion','cap.fecha_create')
        ->limit(500)
        ->orderBy('cap.created_at', 'DESC')
        ->get();
        return $codigo;

        
    }

    public function desgloseCodigo(Request $request){
        set_time_limit(6000);
        ini_set('max_execution_time', 6000);
       $escuela = DB::SELECT("SELECT  * FROM bodega_codigos WHERE  observacion = '$request->escuela'
       ");
       return $escuela;

        // $codigosLiquidacion = DB::SELECT("SELECT DISTINCT codigo_liquidacion FROM libros_series
        // WHERE codigo_liquidacion IS NOT NULL
        // AND codigo_liquidacion <> 'SINCODIGO'
       
        // ");

 

        
        // $datos = [];
        // $codigos = [];

        // foreach($codigosLiquidacion as $key => $item){
        //     $validar = DB::SELECT("SELECT * FROM bodega_codigos 
        //     WHERE codigo LIKE '$item->codigo_liquidacion%'
        //     AND observacion = 'Escuela_10_de_enero'
        //     ");
        //     if(count($validar) >0){
        //         foreach($validar as $k => $tr){
        //             $codigos[$k] =[
        //                 "codigo" => $tr->codigo
        //             ];
        //         }
        //         $datos[$key] = [
        //             "codigo_liquidacion" => $item->codigo_liquidacion,
        //             "codigo" => count($codigos)
                   
        //         ];
        //     }
       

           
        // }
        // return $datos;
      
     
       
    }
    public function delete_codigo(Request $request)
    {
        $dato= DB::table('bodega_eliminados')->insert(
            ['codigo' => $request->codigo, 'observacion' => $request->observacion]
        );
        
        $res=DB::table('bodega_codigos')
        ->where('id',$request->id)->delete();
        return 'eliminado';

    }
}
