<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Verificacion;
use App\Models\HistoricoCodigos;
use DB;
use PDO;
use Carbon\Carbon;
use App\Models\VerificacionHasInstitucion;


class VerificacionController extends Controller
{
    
    public function index(Request $request)
    {
        set_time_limit(60000);
        ini_set('max_execution_time', 60000);

        //PARA VER EL CONTRATO POR CODIGO

        if($request->id){
            $buscarContrato = DB::select("SELECT 
            v.* from verificaciones v
            WHERE v.id = '$request->id'
            ");
             if(empty($buscarContrato)){
                return ["status"=>"0","message"=>"No se encontro datos para este  contrato"];
            }else{
                return $buscarContrato;
            }
        }
        //PARA VER LA INFORMACION DE LAS FECHAS DE LAS VERIFICACIONES DEL CONTRATO
        if($request->fechasVerificacion){
            $fechas = DB::select("SELECT 
            v.* from verificaciones v
            where contrato = '$request->contrato'
            ");
            return $fechas;
        }

        //PARA VER LA INFORMACION DE LAS VERIFICACIONES DEL CONTRATO
        if($request->informacion){
           
            $traerInformacion = DB::select("SELECT 
            vt.*, (SELECT COUNT(*) FROM verificaciones WHERE contrato =  '$request->contrato') as contContrato

            FROM verificaciones_has_temporadas vt
            where vt.contrato = '$request->contrato'
            and vt.estado = '1'
            and vt.nuevo = '0'
          
            ORDER BY vt.verificacion_id desc
            ");

            if(empty($traerInformacion)){
                return ["status"=>"0","message"=>"No se encontro verificaciones para este contrato"];
            }else{
                return $traerInformacion;
            }
            
        }   
        //PARA VER EL HISTORIAL DE CADA CODIGO LIQUIDACION
        else{
            
                $buscarInstitucion= DB::select("SELECT idInstitucion,id_periodo
                    FROM temporadas
                    where contrato = '$request->contrato'
                ");

                
                if(empty($buscarInstitucion)){
                    return ["status"=>"0","message"=>"No se encontro la institucion o no tiene periodo"];
                }

                
                $periodo = $buscarInstitucion[0]->id_periodo;
                if($periodo == ""){
                    return ["status"=>"0","message"=>"Ingrese un periodo al contrato por favor"];
                }
                
                
                $buscarcodigosLibros = DB::select("SELECT contrato, codigoslibros.codigo, ls.codigo_liquidacion, libro.nombrelibro, libro.idlibro, 
                    codigoslibros.verif1, codigoslibros.verif2,codigoslibros.verif3,codigoslibros.verif4,codigoslibros.verif5
                    FROM codigoslibros, libro,  libros_series ls
            
            
                    WHERE codigoslibros.libro_idlibro = libro.idlibro
                    AND ls.idLibro = libro.idlibro
                    and codigoslibros.id_periodo = '$periodo'
                    and codigoslibros.contrato = '$request->contrato'
                   
                    AND ls.codigo_liquidacion = '$request->codigo'
                ");
                if(empty($buscarcodigosLibros)){
                    return ["status"=>"0","message"=>"No se encontro datos para este  contrato"];
                }else{
                    return $buscarcodigosLibros;
                }
        }



           
  
    }

    //PARA TRAER EL CONTRATO POR NUMERO DE VERIFICACION
    public function liquidacionVerificacionNumero($contrato,$numero){
        //validar si el contrato esta activo
        $validarContrato = DB::select("SELECT t.*
        FROM temporadas t
        WHERE t.contrato = '$contrato'
        and t.estado = '0'
        ");
        if(count($validarContrato) > 0){
            return ["status"=>"0", "message" => "El contrato esta inactivo"]; 
        }
        $traerInformacion = DB::select("SELECT  *


            FROM verificaciones_has_temporadas vt
            LEFT JOIN verificaciones v on v.contrato = vt.contrato
            where vt.contrato = '$contrato'
            and vt.estado = '1'
            and vt.nuevo = '0'
            and v.nuevo = '0'
            and v.num_verificacion = '$numero'
            and vt.verificacion_id = '$numero'
            ORDER BY vt.id_verificacion_inst asc

        ");

        if(empty($traerInformacion)){
            return ["status"=>"0","message"=>"No se encontro datos para este  contrato"];
        }else{
            return $traerInformacion;
        }
    }

    //PARA GUARDAR LA LIQUIDACION

    public function liquidacionVerificacion($contrato){
        set_time_limit(0);
         //validar si el contrato esta activo
         $validarContrato = DB::select("SELECT t.*
         FROM temporadas t
         WHERE t.contrato = '$contrato'
         and t.estado = '0'
         ");
         if(count($validarContrato) > 0){
             return ["status"=>"0", "message" => "El contrato esta inactivo"]; 
         }

        $buscarInstitucion= DB::table('temporadas')
        ->select('temporadas.idInstitucion')
        ->where('contrato', $contrato)
  
        ->get();
        if(count($buscarInstitucion) <= 0){
            return "no existe la institucion";
           

        }else{

            $institucion = $buscarInstitucion[0]->idInstitucion;
          
            //verificar que el periodo exista
            $verificarPeriodo = DB::select("SELECT t.contrato, t.id_periodo, p.idperiodoescolar
            FROM temporadas t, periodoescolar p
            WHERE t.id_periodo = p.idperiodoescolar
            AND contrato = '$contrato'
            ");
            if(empty($verificarPeriodo)){
               return ["status"=>"0", "message" => "No se encontro el periodo"]; 
            }
            //traer la liquidacion
            else{
                //almancenar el periodo
                 $periodo =  $verificarPeriodo[0]->idperiodoescolar;
                //traer temporadas
                $temporadas= DB::table('temporadas')
                ->select('temporadas.*')
                ->where('contrato', $contrato)
                ->where('estado','1')
                ->get();
            
                $data = DB::select("
                CALL`liquidacion_proc`($institucion,$periodo)
            
                ");
                  
                //SI TODO HA SALIDO BIEN TRAEMOS LA DATA 
                if(count($data) >0){

                    //obtener la fecha actual
                    $fechaActual  = date('Y-m-d'); 
                    //saber que es el primero contrato
                    $encontrarVerificacionContrato = DB::select("SELECT  
                    * FROM verificaciones 
                    WHERE contrato = '$contrato'
                    ORDER BY id DESC
                    ");

                  
                    //======PARA REALIZAR LA VERIFICACION EN CASO QUE EL CONTRATO YA TENGA VERIFICACIONES====

                    if(count($encontrarVerificacionContrato) >0){
                        
                          //obtener el numero de verificacion en el que se quedo el contrato
                          $traerNumeroVerificacion =  $encontrarVerificacionContrato[0]->num_verificacion;
                          $traeridVerificacion = $encontrarVerificacionContrato[0]->id;
                           //Para guardar la verificacion si  existe el contrato 
                        
                            //SI EXCEDE LAS 10 VERIFICACIONES
                            $finVerificacion="no";
                            if($traerNumeroVerificacion >10){

                                $finVerificacion = "yes";
                            }
                            else{

                                    //ACTUALIZAR FECHA A LA VERIFICACION ANTERIOR
                                    $verificacion_anterior = $traerNumeroVerificacion-1;
                                    
                                    //OBTENER LA CANTIDAD DE CADA VERIFICACION ANTERIOR PARA PODER RESTAR O SUMAR
                                    $obtenerCantidadVerificacionAnterior = DB::select("SELECT 
                                        * FROM verificaciones_has_temporadas
                                        WHERE contrato = '$contrato'
                                        AND verificacion_id = '$verificacion_anterior'
                                        ");
    
                                    foreach($obtenerCantidadVerificacionAnterior as $key=> $valor){

                                        $array_cantidad_anterior[$key] = [
                                                'cantidad' => $valor->cantidad,
                                                'codigo' => $valor->codigo
                                                
                                        ];
                                    }
  
                                    //OBTENER LA CANTIDAD DE LA VERIFICACION ACTUAL 
                                    $obtenerCantidadVerificacionActual = $this->verificacionActual($institucion,$periodo);
                                 

                                    // CAPTURAR LA LONGITUD DEL LA VERIFICACION ANTERIO Y LA NUEVA 
                                    $logintudverificacionActualOriginal = count($data);
                                    $logintudverificacionActualNuevo = count($obtenerCantidadVerificacionActual);


                                    
                                  
                                    //EN CASO QUE SE REALIZE CON TODA NORMALIDAD

                                    if($logintudverificacionActualOriginal == $logintudverificacionActualNuevo){
                                    
                                        
                                          $this->procesoGuardarLiquidacion($traeridVerificacion,$logintudverificacionActualOriginal,$data,$contrato,$array_cantidad_anterior,$institucion,$periodo,$traerNumeroVerificacion,$fechaActual);


                                    }
                                    //EN CASO QUE SE HAYA DESPERFECTOS
                                    else{
                                        
                                        //PARA GUARDAR LA DATA CON PROBLEMAS 
                                         $this->procesoGuardarLiquidacionConProblemas($traeridVerificacion,$data,$obtenerCantidadVerificacionAnterior,$institucion,$periodo,$contrato,$traerNumeroVerificacion);
                                      
                                        
                                         //PARA GUARDAR LA DATA SIN PROBLEMAS
                                          $this->procesoGuardarLiquidacionSinProblemas($traeridVerificacion,$logintudverificacionActualNuevo, $obtenerCantidadVerificacionActual,$contrato,$array_cantidad_anterior,$institucion,$periodo,$traerNumeroVerificacion);
                            
                                       

                                    }
                                       //Actualizo a estado 0 la verificacion anterior
                                       DB::table('verificaciones')
                                       ->where('id', $traeridVerificacion)
                                       ->update([
                                           'fecha_fin' => $fechaActual,
                                           'estado' => "0"
                                       ]);
                                       //  Para generar una verficacion y que quede abierta
                                       $verificacion =  new Verificacion;
                                       $verificacion->num_verificacion = $traerNumeroVerificacion+1;
                                       $verificacion->fecha_inicio = $fechaActual;
                                       $verificacion->contrato = $contrato;
                                       $verificacion->save();
                            }
                    }else{
                        
                        $finVerificacion = "0";
               
                        //=====PARA GUARDAR LA VERIFICACION SI EL CONTRATO AUN NO TIENE VERIFICACIONES======

                        $verificacion =  new Verificacion;
                        $verificacion->num_verificacion = 1;
                        $verificacion->fecha_inicio = $fechaActual;
                        $verificacion->fecha_fin = $fechaActual;
                        $verificacion->contrato = $contrato;
                        $verificacion->estado = "0";
                        $verificacion->save();

                           //SE GUARDA LA VERIFICACION EN LA TABLA VERIFICACION TEMPORADAS
                               //almacenar  los codigos de liquidacion y cantidades en un array
                               foreach($data as $key=> $item){
                                $array_codigo_liquidacion[] = $item->codigo;
                                $traerContrato = $contrato;
                                $nombreLibro[] = $item->nombrelibro;
                                $array_cantidad[] = $item->cantidad;

                            }

                              //UNA VEZ QUE SE ACTUALIZA LA VERIFICACION ANTERIOR SE AGREGA UNA NUEVA
                              $encontrarVerificacionContratoInicial = DB::select("SELECT  
                              * FROM verificaciones 
                              WHERE contrato = '$contrato'
                              ORDER BY id DESC
                              ");
  
                            //obtener el numero de verificacion en el que se quedo el contrato
                            $traerNumeroVerificacionInicial =  $encontrarVerificacionContratoInicial[0]->num_verificacion;
                            $traerNumeroVerificacionInicialId = $encontrarVerificacionContratoInicial[0]->id;

                            

                                $contador2 =0;
                                while ($contador2 < count($array_codigo_liquidacion)) {
                                    //Actualizar cada codigo la verificacion
                                $codigosDelLibro= $this->codigosIndividualVerificacion($array_codigo_liquidacion[$contador2],$institucion,$periodo);

                        
                                    //Actualizar cada codigo la verificacion
                                  $this->updateCodigoIndividualInicial($traerNumeroVerificacionInicialId,$codigosDelLibro,$contrato,$institucion);
                                    $contador2=$contador2+1;
                                }
                                
                               //Ingresar la liquidacion
                              

                               $this->guardarLiquidacionInicial($data,$traerNumeroVerificacionInicial,$traerContrato,$array_codigo_liquidacion,$array_cantidad,$nombreLibro);
                        

                                $encontrarVerificacionContratoInicial = DB::select("SELECT  
                                * FROM verificaciones 
                                WHERE contrato = '$contrato'
                                ORDER BY id DESC
                                ");

                         //obtener el numero de verificacion en el que se quedo el contrato
                         $traerNumeroVerificacionInicial =  $encontrarVerificacionContratoInicial[0]->num_verificacion;

                         

                          //Para guardar la verificacion si  existe el contrato 
                          $verificacion =  new Verificacion;
                          $verificacion->num_verificacion = $traerNumeroVerificacionInicial+1;
                          $verificacion->fecha_inicio = $fechaActual;
                          $verificacion->contrato = $contrato;
                          $verificacion->save();

                    }
                                                
                   if($finVerificacion =="yes"){
                    return ["verificaciones"=>"Ha alzancado el limite de verificaciones permitidas",'temporada'=>$temporadas,'codigos_libros' => $data];
                   }else{
                    return ['temporada'=>$temporadas,'codigos_libros' => $data];
                   }

                    
                }else{
                    return ["status"=>"0", "message" => "No se pudo cargar la informacion"];
                }
                

            }
        }

  
    }

    //PARA OBTENER LA VERIFCACION ACTUAL LIBRE DE LOS CODIGOS QUE SEAN DIFERENTES
    public function verificacionActual($institucion,$periodo){
        $obtenerCantidadVerificacionActual = DB::select("SELECT
      
        FORMAT(COUNT(codigoslibros.libro_idlibro), 0) AS cantidad, ls.codigo_liquidacion as codigo,  libro.nombrelibro, libro.idlibro,  institucion.nombreInstitucion  
       FROM codigoslibros,usuario, libro,institucion, usuario as ac , libros_series ls ,verificaciones_has_temporadas v
       
       
           WHERE  codigoslibros.libro_idlibro = libro.idlibro 
           AND ls.idLibro = libro.idlibro 
           AND ls.codigo_liquidacion = v.codigo
           and codigoslibros.idusuario = usuario.idusuario 
           and usuario.institucion_idInstitucion = institucion.idInstitucion 
           and usuario.institucion_idInstitucion = '$institucion' 
           and ac.cedula = institucion.vendedorInstitucion 
           and codigoslibros.id_periodo ='$periodo'
           and codigoslibros.estado <> '2'  
           GROUP BY  codigoslibros.libro_idlibro, libro.nombrelibro, ls.codigo_liquidacion, institucion.nombreInstitucion, ac.nombres,ac.apellidos
   ");
   return $obtenerCantidadVerificacionActual;
    }

    //METODOS DE LOS PROCESOS
    public function procesoGuardarLiquidacion($traeridVerificacion,$logintudverificacionActualOriginal,$data,$contrato,$array_cantidad_anterior,$institucion,$periodo,$traerNumeroVerificacion,$fechaActual){
     
            //almacenar  los codigos de liquidacion y cantidades en un array
    
            foreach($data as $key=> $item){
                $array_codigo_liquidacion[] = $item->codigo;
                $traerContrato = $contrato;
                $nombreLibro[] = $item->nombrelibro;
                $array_cantidad[] = $item->cantidad;

                for($i=0; $i<$logintudverificacionActualOriginal; $i++){
                  
                    if($data[$key]->codigo == $array_cantidad_anterior[$i]["codigo"]){
                       $array_cantidad_desface[] = $item->cantidad - $array_cantidad_anterior[$i]["cantidad"];
                    }else{
                        
                    }
                    
                }
                
            }

            $contador1 =0;
            while ($contador1 < count($array_codigo_liquidacion)) {
                //Actualizar cada codigo la verificacion
            $codigosDelLibro= $this->codigosIndividualVerificacion($array_codigo_liquidacion[$contador1],$institucion,$periodo);
                    //Actualizar cada codigo la verificacion
                $this->updateCodigoIndividual($traeridVerificacion,$codigosDelLibro,$traerNumeroVerificacion,$contrato,$institucion);
                $contador1=$contador1+1;
            }


            //Ingresar la liquidacion

            $this->guardarLiquidacion($data,$traerNumeroVerificacion,$traerContrato,$array_codigo_liquidacion,$array_cantidad,$nombreLibro,$array_cantidad_desface);
    
    }


    public function procesoGuardarLiquidacionSinProblemas($traeridVerificacion,$logintudverificacionActualNuevo,$obtenerCantidadVerificacionActual,$contrato,   $array_cantidad_anterior,$institucion,$periodo,$traerNumeroVerificacion){
        
       
        foreach($obtenerCantidadVerificacionActual as $key=> $item){
            $array_codigo_liquidacion[] = $item->codigo;
            $traerContrato = $contrato;
            $nombreLibro[] = $item->nombrelibro;
            $array_cantidad[] = $item->cantidad;

            for($i=0; $i<$logintudverificacionActualNuevo+1; $i++){
              
                if($obtenerCantidadVerificacionActual[$key]->codigo == $array_cantidad_anterior[$i]["codigo"]){
                   $array_cantidad_desface[] = $item->cantidad - $array_cantidad_anterior[$i]["cantidad"];
                }else{
                    
                }
                
            }
            
        }
       

        $contador1 =0;
        while ($contador1 < count($array_codigo_liquidacion)) {
            //Obtener la data de cada codigo para la verificacion
            $codigosDelLibro= $this->codigosIndividualVerificacion($array_codigo_liquidacion[$contador1],$institucion,$periodo);

            //Actualizar cada codigo la verificacion
            $this->updateCodigoIndividual($traeridVerificacion,$codigosDelLibro,$traerNumeroVerificacion,$contrato,$institucion);
            $contador1=$contador1+1;
        }

        //Ingresar la liquidacion
        $this->guardarLiquidacion($obtenerCantidadVerificacionActual,$traerNumeroVerificacion,$traerContrato,$array_codigo_liquidacion,$array_cantidad,$nombreLibro,$array_cantidad_desface);
    

    }

    public function procesoGuardarLiquidacionConProblemas($traeridVerificacion,$data,$obtenerCantidadVerificacionAnterior,$institucion,$periodo,$contrato,$traerNumeroVerificacion){

             
                //SE PROCEDE A SEPARAR LOS VALORES QUE NO COINCIDEN CON LA VERIFICACION ANTERIOR
                foreach($data as $key=> $value0){
                                                        
                    $datosActuales[] =$value0->codigo;  
            
                }
                // return $datosActuales;

                foreach($obtenerCantidadVerificacionAnterior as $key=> $value10){
                    
                    $datosAnterior[] =$value10->codigo;  
                
                
                }
              
                $resultado = array_diff($datosActuales, $datosAnterior); 
                
                
                foreach ($resultado as $k => $val) {
                $codePerdidos[] = $val;
                }
                
                

                $contador11 =0;
                while ($contador11 < count($codePerdidos)) {
                //Traer la data de la verificacion con codigos que no coinciden con la verificacion anterior
                    $traerDataCodePerdidos[] = DB::select("SELECT  COUNT(codigoslibros.libro_idlibro) AS cantidad, ls.codigo_liquidacion as codigo,  libro.nombrelibro, libro.idlibro,  institucion.nombreInstitucion  
                    FROM codigoslibros,usuario, libro,institucion, usuario as ac , libros_series ls 
                    
                    
                            WHERE  codigoslibros.libro_idlibro = libro.idlibro 
                            AND ls.idLibro = libro.idlibro 
                            and codigoslibros.idusuario = usuario.idusuario 
                            and usuario.institucion_idInstitucion = institucion.idInstitucion 
                            and usuario.institucion_idInstitucion = '$institucion' 
                            and ac.cedula = institucion.vendedorInstitucion 
                            and codigoslibros.id_periodo ='$periodo'
                            AND ls.codigo_liquidacion = '$codePerdidos[$contador11]'
                            and codigoslibros.estado <> '2'  
                            GROUP BY  codigoslibros.libro_idlibro, libro.nombrelibro, ls.codigo_liquidacion, institucion.nombreInstitucion, ac.nombres,ac.apellidos
                    ");
                    $contador11=$contador11+1;


                }

                //Para transformar los codigos perdidos en un array legible
                foreach($traerDataCodePerdidos as $p =>  $perdido){

                        $dataPerdidaCorregida[$p] = [
                            "cantidad" => $perdido[0]->cantidad,
                            "codigo" => $perdido[0]->codigo,
                            "nombrelibro" => $perdido[0]->nombrelibro
                        ];
                }   
            
                //PARA DATOS PERDIDOS
                
                //almacenar  los codigos de liquidacion y cantidades en un array
                foreach($dataPerdidaCorregida as $key=> $item){
                    $array_codigo_liquidacionPerdida[] = $dataPerdidaCorregida[$key]["codigo"];
                    $traerContrato = $contrato;
                    $nombreLibroPerdida[] = $dataPerdidaCorregida[$key]["nombrelibro"];
                    $array_cantidadPerdida[] = $dataPerdidaCorregida[$key]["cantidad"];

                }

        
                //PARA GUARDAR LA DATA CON PROBLEMAS

                $contador13 =0;
                while ($contador13 < count($array_codigo_liquidacionPerdida)) {
                    //Actualizar cada codigo la verificacion
                $codigosDelLibro= $this->codigosIndividualVerificacion($array_codigo_liquidacionPerdida[$contador13],$institucion,$periodo);
                
                    //Actualizar cada codigo la verificacion
                    $this->updateCodigoIndividualDataPerdida($traeridVerificacion,$codigosDelLibro,$traerNumeroVerificacion,$contrato,$institucion);
                    $contador13=$contador13+1;
                }


                //Ingresar la liquidacion

                $this->guardarLiquidacion($dataPerdidaCorregida,$traerNumeroVerificacion,$traerContrato,$array_codigo_liquidacionPerdida,$array_cantidadPerdida,$nombreLibroPerdida,$array_cantidadPerdida);
    
    }

    //PARA GUARDAR LA LIQUIDACION EN LA TABLA  VERIFICACION TEMPORADAS

    public function  guardarLiquidacion($data,$traerNumeroVerificacionInicial,$traerContrato,$array_codigo_liquidacion,$array_cantidad,$nombreLibro,$array_cantidad_desface){

        //Ingresar la liquidacion

        $cont =0;
        while ($cont < count($data)) {
            $liquidacion=new VerificacionHasInstitucion;
            $liquidacion->verificacion_id=$traerNumeroVerificacionInicial;
            $liquidacion->contrato = $traerContrato;
            $liquidacion->codigo=$array_codigo_liquidacion[$cont];
            $liquidacion->cantidad=$array_cantidad[$cont];
            $liquidacion->nombre_libro = $nombreLibro[$cont];
            $liquidacion->estado="1";
            $liquidacion->desface = $array_cantidad_desface[$cont];
            $liquidacion->save(); 
            $cont=$cont+1;
        }
     }

     public function guardarLiquidacionInicial($data,$traerNumeroVerificacionInicial,$traerContrato,$array_codigo_liquidacion,$array_cantidad,$nombreLibro){
           //Ingresar la liquidacion

        $cont =0;
        while ($cont < count($data)) {
            $liquidacion=new VerificacionHasInstitucion;
            $liquidacion->verificacion_id=$traerNumeroVerificacionInicial;
            $liquidacion->contrato = $traerContrato;
            $liquidacion->codigo=$array_codigo_liquidacion[$cont];
            $liquidacion->cantidad=$array_cantidad[$cont];
            $liquidacion->nombre_libro = $nombreLibro[$cont];
            $liquidacion->estado="1";
            $liquidacion->desface=0;
            $liquidacion->save(); 
            $cont=$cont+1;
        }
     }

     //PARA TRAER LOS CODIGOS DE CADA LIBRO PARA COLOCAR LA VERIFICACION EN CADA CODIGO
     public function codigosIndividualVerificacion($array_codigo_liquidacion,$institucion,$periodo){

         $verCodigo = DB::select("SELECT codigoslibros.contrato, codigoslibros.idusuario, codigoslibros.codigo, ls.codigo_liquidacion, institucion.idInstitucion, codigoslibros.id_periodo,  libro.nombrelibro, libro.idlibro,  institucion.nombreInstitucion 
         FROM `codigoslibros`,usuario, libro,institucion, usuario as ac , libros_series ls
         
    
            WHERE  codigoslibros.libro_idlibro = libro.idlibro
            AND ls.idLibro = libro.idlibro
            and codigoslibros.idusuario = usuario.idusuario
            and usuario.institucion_idInstitucion = institucion.idInstitucion
            and usuario.institucion_idInstitucion = '$institucion'
            and ac.cedula = institucion.vendedorInstitucion
            and codigoslibros.id_periodo ='$periodo'
            and codigoslibros.estado <> '2' 
            AND ls.codigo_liquidacion = '$array_codigo_liquidacion'
            
                 
        ");
        return $verCodigo;

     }
     //PARA ACTUALIZAR EL CODIGO DE CADA LIBRO CUANDO YA EL CONTRATO YA EXISTE

     public function updateCodigoIndividual($traeridVerificacion,$codigosDelLibro,$traerNumeroVerificacionInicial,$contrato,$institucion){

        $columnaVerificacion = "verif".$traerNumeroVerificacionInicial;
        
         //almacenar  los codigos de cada libro
         foreach($codigosDelLibro as $key=> $recorrer){
            $array_codigosIndividual[] = $recorrer->codigo;
            $array_contratosQueviene[] = [
                "contrato" => $recorrer->contrato,
                'codigo' => $recorrer->codigo,
                'idusuario' => $recorrer->idusuario,
                'idInstitucion' =>$institucion
            ];
        
        }

        $contador17 =0;
        while ($contador17 < count($array_contratosQueviene)) {
                //PARA  CUANDO YA EXISTE UNA VERIFICACION DE OTRO CONTRATO 


                $code =  $codigosDelLibro[$contador17]->codigo;
                $buscarVerificacion = DB::select("SELECT verif1,verif2,verif3,verif4,verif5,idusuario FROM codigoslibros WHERE codigo = '$code'");
                 
                $datoVerificacion =  $buscarVerificacion[0]->$columnaVerificacion;
        
                if($columnaVerificacion == "" || $columnaVerificacion == null){
                    
                }else{
                    $buscarContrato = DB::select("SELECT 
                    v.* from verificaciones v
                    WHERE v.id = '$datoVerificacion'
                    ");

                    if(!empty($buscarContrato)){
                         //para guarda en el historico
                        $historico = new HistoricoCodigos;
                        $historico->contrato_actual = $contrato;
                        $historico->contrato_anterior = $buscarContrato[0]->contrato;
                        $historico->codigo_libro   =  $codigosDelLibro[$contador17]->codigo;
                        $historico->id_usuario = $buscarVerificacion[0]->idusuario;
                        $historico->usuario_editor = $institucion;
                        $historico->observacion = "Cambio del num. de verificacion en una verificacion que le pertenece a otro contrato";
                        $historico->verificacion_id = $datoVerificacion;
                        $historico->verificacion_columna = $columnaVerificacion;
                        $historico->save();
                    }
            
                   

                    
                }
       
        $contador17=$contador17+1;
     }        
        


         $contador16 =0;
         while ($contador16 < count($array_contratosQueviene)) {
                //PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION

                //SI NO EXISTE ALGUN CONTRATO EN EL CODIGO 
                if($array_contratosQueviene[$contador16]["contrato"] =="" || $array_contratosQueviene[$contador16]["contrato"] == "0"  || $array_contratosQueviene[$contador16]["contrato"] == $contrato || $array_contratosQueviene[$contador16]["contrato"] == null ){

                    //PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION

                        $contador =0;
                        while ($contador < count($array_codigosIndividual)) {
                            $accesoLibro = DB::table('codigoslibros')
                            ->where('codigo', $array_codigosIndividual[$contador])
                        
                            ->update([
                                $columnaVerificacion => $traeridVerificacion,
                                'estado_liquidacion' => "0",
                                'contrato' => $contrato,
                            ]);
                            $contador=$contador+1;
                        }
                //SI EXISTE EL CONTRATO HAY QUE GUARDARLO EN HISTORICO
                }else{
                        //para guarda en el historico
                            $historico = new HistoricoCodigos;
                            $historico->contrato_actual = $contrato;
                            $historico->contrato_anterior = $array_contratosQueviene[$contador16]["contrato"];
                            $historico->codigo_libro   =  $array_contratosQueviene[$contador16]["codigo"];
                            $historico->id_usuario = $array_contratosQueviene[$contador16]["idusuario"];
                            $historico->usuario_editor = $array_contratosQueviene[$contador16]["idInstitucion"];
                            $historico->observacion = "Cambio del contrato del codigo del estudiante";
                            $historico->save();
                            
                        
                        // PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION

                            $contador =0;
                            while ($contador < count($array_codigosIndividual)) {
                                $accesoLibro = DB::table('codigoslibros')
                                ->where('codigo', $array_codigosIndividual[$contador])
                                
                                ->update([
                                    $columnaVerificacion => $traeridVerificacion,
                                    'estado_liquidacion' => "0",
                                    'contrato' => $contrato,
                                ]);
                                $contador=$contador+1;
                            }
                    
                }

            $contador16=$contador16+1;
        }

        
     }

     //PARA ACTUALIZAR EL CODIGO DE CADA LIBRO CUANDO YA EL CONTRATO YA EXISTE PARA LA DATA PERDIDA

     public function updateCodigoIndividualDataPerdida($traeridVerificacion,$codigosDelLibro,$traerNumeroVerificacionInicial,$contrato,$institucion){

        $columnaVerificacion = "verif".$traerNumeroVerificacionInicial;

          //almacenar  los codigos de cada libro
          foreach($codigosDelLibro as $key=> $recorrer){
            $array_codigosIndividual[] = $recorrer->codigo;
            $array_contratosQueviene[] = [
                "contrato" => $recorrer->contrato,
                'codigo' => $recorrer->codigo,
                'idusuario' => $recorrer->idusuario,
                'idInstitucion' =>$institucion
            ];
            
            
         }

         //CUANDO EXISTE UNA VERIFICACION EN UNA COLUMNA QUE PERTENECE A OTRO CONTRATO
         $contador17 =0;
         while ($contador17 < count($array_contratosQueviene)) {
                    //PARA  CUANDO YA EXISTE UNA VERIFICACION DE OTRO CONTRATO 


                    $code =  $codigosDelLibro[$contador17]->codigo;
                    $buscarVerificacion = DB::select("SELECT verif1,verif2,verif3,verif4,verif5,idusuario FROM codigoslibros WHERE codigo = '$code'");
                    
                    $datoVerificacion =  $buscarVerificacion[0]->$columnaVerificacion;
                    $VerificacionUsuario = $buscarVerificacion[0]->idusuario;
            
                
                    if($columnaVerificacion == "" || $columnaVerificacion == null){
                        
                    }else{
                        $buscarContrato = DB::select("SELECT 
                        v.* from verificaciones v
                        WHERE v.id = '$datoVerificacion'
                        ");

                        if(!empty($buscarContrato)){
                            //para guarda en el historico
                            $historico = new HistoricoCodigos;
                            $historico->contrato_actual = $contrato;
                            $historico->contrato_anterior = $buscarContrato[0]->contrato;
                            $historico->codigo_libro   =  $codigosDelLibro[$contador17]->codigo;
                            $historico->id_usuario = $buscarVerificacion[0]->idusuario;
                            $historico->usuario_editor = $institucion;
                            $historico->observacion = "Cambio del num. de verificacion en una verificacion que le pertenece a otro contrato";
                            $historico->verificacion_id = $datoVerificacion;
                            $historico->verificacion_columna = $columnaVerificacion;
                            $historico->save();
                        }
                
                    

                        
                    }
        
            $contador17=$contador17+1;
        }      


        //PARA INGRESAR LA EN CODIGOS DE LIBROS
        $contador16 =0;
        while ($contador16 < count($array_contratosQueviene)) {

                //SI NO EXISTE ALGUN CONTRATO EN EL CODIGO 
                if($array_contratosQueviene[$contador16]["contrato"] =="" || $array_contratosQueviene[$contador16]["contrato"] == "0" || $array_contratosQueviene[$contador16]["contrato"] == $contrato || $array_contratosQueviene[$contador16]["contrato"] == null ){

                    //PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION

                        $contador =0;
                        while ($contador < count($array_codigosIndividual)) {
                            $accesoLibro = DB::table('codigoslibros')
                            ->where('codigo', $array_codigosIndividual[$contador])
                        
                            ->update([
                                $columnaVerificacion => $traeridVerificacion,
                                'estado_liquidacion' => "0",
                                'contrato' => $contrato,
                            ]);
                            $contador=$contador+1;
                        }
                //SI EXISTE EL CONTRATO HAY QUE GUARDARLO EN HISTORICO
                }else{
                        //para guarda en el historico
                            $historico = new HistoricoCodigos;
                            $historico->contrato_actual = $contrato;
                            $historico->contrato_anterior = $array_contratosQueviene[$contador16]["contrato"];
                            $historico->codigo_libro   =  $array_contratosQueviene[$contador16]["codigo"];
                            $historico->id_usuario = $array_contratosQueviene[$contador16]["idusuario"];
                            $historico->usuario_editor = $array_contratosQueviene[$contador16]["idInstitucion"];
                            $historico->observacion = "Cambio del contrato del codigo del estudiante";
                            $historico->save();
                            
                        
                        // PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION

                            $contador =0;
                            while ($contador < count($array_codigosIndividual)) {
                                $accesoLibro = DB::table('codigoslibros')
                                ->where('codigo', $array_codigosIndividual[$contador])
                                
                                ->update([
                                    $columnaVerificacion => $traeridVerificacion,
                                    'estado_liquidacion' => "0",
                                    'contrato' => $contrato,
                                ]);
                                $contador=$contador+1;
                            }
                    
                }
        $contador16=$contador16+1;
        
        }




        
     }

     //PARA ACTUALIZAR EL CODIGO DE CADA LIBRO CUANDO EL CONTRATO NO EXISTE AUN 
     public function updateCodigoIndividualInicial($traerNumeroVerificacionInicialId,$codigosDelLibro,$contrato,$institucion){
        
        
          //almacenar  los codigos de cada libro
          foreach($codigosDelLibro as $key=> $recorrer){
            $array_codigosIndividual[] = $recorrer->codigo;
            $array_contratosQueviene[] = [
                "contrato" => $recorrer->contrato,
                'codigo' => $recorrer->codigo,
                'idusuario' => $recorrer->idusuario,
                'idInstitucion' =>$institucion
            ];
            
            
        }
        
        $contador16 =0;
        while ($contador16 < count($array_contratosQueviene)) {

                //PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION

                //SI NO EXISTE ALGUN CONTRATO EN EL CODIGO 
                if($array_contratosQueviene[$contador16]["contrato"] =="" || $array_contratosQueviene[$contador16]["contrato"] == "0"  || $array_contratosQueviene[$contador16]["contrato"] == $contrato || $array_contratosQueviene[$contador16]["contrato"] == null ){

                    //PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION

                        $contador =0;
                        while ($contador < count($array_codigosIndividual)) {
                            $accesoLibro = DB::table('codigoslibros')
                            ->where('codigo', $array_codigosIndividual[$contador])
                        
                            ->update([
                                'verif1' =>  $traerNumeroVerificacionInicialId,
                                'estado_liquidacion' => "0",
                                'contrato' => $contrato,
                            ]);
                            $contador=$contador+1;
                        }

                //SI EXISTE EL CONTRATO HAY QUE GUARDARLO EN HISTORICO
                }else{
                        //para guarda en el historico
                            $historico = new HistoricoCodigos;
                            $historico->contrato_actual = $contrato;
                            $historico->contrato_anterior = $array_contratosQueviene[$contador16]["contrato"];
                            $historico->codigo_libro   =  $array_contratosQueviene[$contador16]["codigo"];
                            $historico->id_usuario = $array_contratosQueviene[$contador16]["idusuario"];
                            $historico->usuario_editor = $array_contratosQueviene[$contador16]["idInstitucion"];
                            $historico->observacion = "Cambio del contrato del codigo del estudiante";
                            $historico->save();
                            
                        
                        // PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION

                        $contador =0;
                            while ($contador < count($array_codigosIndividual)) {
                                $accesoLibro = DB::table('codigoslibros')
                                ->where('codigo', $array_codigosIndividual[$contador])
                            
                                ->update([
                                    'verif1' => $traerNumeroVerificacionInicialId,
                                    'estado_liquidacion' => "0",
                                    'contrato' => $contrato,
                                ]);
                                $contador=$contador+1;
                            }
                    
                }
            
           $contador16=$contador16+1;
        
        }
    

      
     }

   

     //api:Get>> liquidacion/codigosmovidos/contrato
     public function codigosmovidos($contrato){

        $buscarCodigosPerdidos = DB::select("SELECT h.* , u.cedula,u.nombres,u.apellidos
        FROM hist_codlibros h, usuario u
        WHERE h.contrato_anterior = '$contrato'
        and u.idusuario = h.id_usuario
        ");
       if(!empty($buscarCodigosPerdidos)){
           return  ["status"=>"1","codigos" => $buscarCodigosPerdidos];
       }else{
           return  ["status"=>"0","message" => "No hay historial de codigos perdidos para ese contrato"];
       }

     }


}

  

    