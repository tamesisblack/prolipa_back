<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HistoricoVisitas;
use App\Models\Rol;
use Illuminate\Http\Request;
use DB;
use GuzzleHttp\Client;

class HistoricoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->traerRecursos){
            $recursos = $this->traerRecursos();
            return $recursos;
        }
    }
    public function traerRecursos(){
        $recursos = DB::SELECT("SELECT * FROM historico_recursos ORDER BY id DESC");
        return $recursos;
    }
    public function HistoricoRecursos(Request $request){
        //eliminar
        if($request->eliminar){
            DB::DELETE("DELETE from historico_recursos WHERE id = '$request->id'");
        }else{
            //actualizar
            if($request->id > 0){
                DB::UPDATE("UPDATE historico_recursos set descripcion = '$request->descripcion' WHERE id = '$request->id'");
            }
            //guardar
            else{
                DB::INSERT("INSERT INTO historico_recursos (descripcion) values('$request->descripcion')");
            }
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
        $todate  = date('Y-m-d H:i:s');  
        if($request->idusuario=="5103" || $request->idusuario=="35748" || $request->idusuario=="4853"){
            return ["status" => "1","message" => "admin"];
        }else{
            $historico = new HistoricoVisitas();
            $historico->idusuario =      $request->idusuario;
            $historico->institucion_id = $request->institucion_id;
            //para traer el periodo
            $buscarPeriodo = $this->traerPeriodo($request->institucion_id);
            if($buscarPeriodo["status"] == "1"){
                $obtenerPeriodo = $buscarPeriodo["periodo"][0]->periodo;
                $historico->periodo_id = $obtenerPeriodo;   
            }
            $historico->id_group =              $request->id_group;
            $historico->nombreasignatura =      $request->nombreasignatura;
            $historico->idasignatura=           $request->idasignatura;
            $historico->recurso =               $request->recurso;
            $historico->datos =                 $request->datos;
            $historico->save();
            if($historico){
                return ["status" => "1","message" => "Historico se guardado correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo guardar el historico"];
            }
        }
            
        
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

    public function guardarHistoricoJson(Request $request){
    set_time_limit(6000000);
    ini_set('max_execution_time', 6000000);
    $idinstitucion = $request->idInstitucion;
    $buscarPeriodo = $this->traerPeriodo($idinstitucion);
    if($buscarPeriodo["status"] == "1"){
        $obtenerPeriodo = $buscarPeriodo["periodo"][0]->periodo;
        
    }else{
        return ["status" => "0", "message" => "La institucion no tiene periodo"];
    }

     $client = new Client([
            'base_uri'=> 'https://foro.prolipadigital.com.ec',
            // 'timeout' => 60.0,
    ]); 
    $datos = [];
   
     // $consulta=DB::select("CALL `docentes`(?);",[$idinstitucion]);
    $consulta = DB::SELECT("SELECT `usuario`.`idusuario`,`usuario`. `cedula`, UPPER(`usuario`.`nombres`) as nombres,
    UPPER(`usuario`.`apellidos`) as apellidos,
        `usuario`.`name_usuario`,
        `usuario`.`email`, `usuario`.`id_group`,
        `usuario`.`institucion_idInstitucion`
        from usuario
        LEFT JOIN institucion_cargos c ON usuario.cargo_id = c.id
        where usuario.institucion_idInstitucion = '$idinstitucion' 
        AND usuario.id_group=6
    ");
    
        foreach($consulta as $key => $item){
            $response = $client->request('GET','estudiantes?idusuario='.$item->idusuario.'&_limit=-1');
            $getDocente =   json_decode($response->getBody()->getContents());
        
            foreach($getDocente as $k => $tr){
                //GUARDAR
                $historico = new HistoricoVisitas();
                $historico->idusuario =      $item->idusuario;
                $historico->institucion_id = $item->institucion_idInstitucion;
                //para traer el periodo
                $historico->periodo_id =     $obtenerPeriodo;   
                $historico->id_group =       '';
                $historico->recurso =        '15';
                $historico->datos  =         "Ingreso al sistema";
                $historico->created_at =     $tr->createdAt;
                $historico->save();
            }
         
        }
        
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
