<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use Illuminate\Http\Request;
use App\Models\VerificacionesBc;
use App\Models\HistoricoCodigos;
use DB;
use PDO;
use Carbon\Carbon;
use App\Models\VerificacionhasLiquidacionBc;

class VerificacionBarrasController extends Controller
{
    public function index(Request $request)
    {
        set_time_limit(60000);
        ini_set('max_execution_time', 60000);

        //PARA VER EL CONTRATO POR CODIGO

        if($request->id){
            $buscarContrato = DB::select("SELECT 
            v.* from verificaciones_bc v
            WHERE v.id = '$request->id'
            ");
             if(empty($buscarContrato)){
                return ["status"=>"0","message"=>"No se encontro datos para este  contrato"];
            }else{
                return $buscarContrato;
            }
        }
    
        //PARA VER LA INFORMACION DE LAS VERIFICACIONES DEL CONTRATO
        if($request->informacion){     
            $verificaciones = DB::SELECT("SELECT * FROM verificaciones_bc
            WHERE contrato = '$request->contrato'
            ");
            return $verificaciones;
         
        }   
        //para traer los detalle de cada verificacion
        if($request->detalles){
            $detalles = DB::SELECT("SELECT vl.* ,ls.idLibro AS libro_id
            FROM verificaciones_has_liquidacion_bc vl
            LEFT JOIN libros_series ls ON vl.codigo = ls.codigo_liquidacion
            WHERE vl.num_verificacion = '$request->verificacion_id'
            AND vl.contrato = '$request->contrato'
            AND vl.estado = '1'
            ");
            return $detalles;
        }
        //para ver los codigos de cada libro
        if($request->verCodigos){
            $columnaVerificacion = "verif".$request->num_verificacion;
            $codigos = DB::table('codigoslibros')
            ->where($columnaVerificacion, $request->verificacion_id)
            ->where('contrato', $request->contrato)
            ->where('libro_idlibro', $request->libro_id)
            ->get();
            return $codigos;

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
        $traerInformacion = DB::select("SELECT 
           vt.num_verificacion as numero_verificacion,vt.contrato,vt.codigo,vt.cantidad,
            vt.nombre_libro, v.fecha_inicio, v.fecha_fin, vt.contrato 
            FROM verificaciones_bc v
            LEFT JOIN verificaciones_has_liquidacion_bc vt ON v.num_verificacion = vt.num_verificacion
            WHERE v.num_verificacion ='$numero'
            AND v.contrato = '$contrato'
            AND vt.num_verificacion = '$numero'
            AND vt.contrato = '$contrato'
            and vt.estado = '1'
            ORDER BY vt.num_verificacion desc
        ");

        if(empty($traerInformacion)){
            return ["status"=>"0","message"=>"No se encontro datos para este  contrato"];
        }else{
            return $traerInformacion;
        }
    }

    //PARA GUARDAR LA LIQUIDACION

    public function liquidacionVerificacion($contrato){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //validar si el contrato esta activo
        $validarContrato = DB::select("SELECT t.*
        FROM temporadas t
        WHERE t.contrato = '$contrato'
        and t.estado = '0'
        ");
        if(count($validarContrato) > 0){
            return ["status"=>"0", "message" => "El contrato esta inactivo"]; 
        }
        $buscarContrato= DB::select("SELECT t.*, p.idperiodoescolar
        FROM temporadas t, periodoescolar p
        WHERE t.id_periodo = p.idperiodoescolar
        AND contrato = '$contrato'
        ");
        if(count($buscarContrato) <= 0){
            return ["status"=>"0", "message" => "No existe el contrato o no tiene asignado a un período"]; 
        }else{
            //almacenar el id de la institucion
            $institucion = $buscarContrato[0]->idInstitucion;
            //almancenar el periodo
            $periodo =  $buscarContrato[0]->idperiodoescolar;
         
            //traer temporadas
            $temporadas= $buscarContrato;
            //traigo la liquidacion actual por cantidad
            $data = DB::select("SELECT ls.codigo_liquidacion AS codigo,  COUNT(ls.codigo_liquidacion) AS cantidad, c.serie,
                c.libro_idlibro,c.libro as nombrelibro, i.nombreInstitucion , 
                CONCAT(u.nombres, ' ', u.apellidos) AS asesor
                FROM codigoslibros c 
                LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
                LEFT JOIN institucion i ON i.idInstitucion = c.bc_institucion
                LEFT JOIN usuario u ON u.cedula = i.vendedorInstitucion
                WHERE c.bc_estado = '2'
                AND c.estado <> 2
                AND c.bc_estado <> 3
                and c.estado_liquidacion = '1'
                AND c.bc_periodo  = '$periodo'
                AND c.bc_institucion = '$institucion'
                AND ls.idLibro = c.libro_idlibro 
                GROUP BY ls.codigo_liquidacion,c.libro, c.serie,c.libro_idlibro, u.nombres,u.apellidos
            "); 
            //traigo la liquidacion  con los codigos invidivuales
            $traerCodigosIndividual = DB::SELECT("SELECT c.codigo, ls.codigo_liquidacion,   c.serie,
            c.libro_idlibro,c.libro as nombrelibro
               FROM codigoslibros c 
               LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
               WHERE c.bc_estado = '2'
               AND c.estado <> 2
               AND c.bc_estado <> 3
               and c.estado_liquidacion = '1'
               AND c.bc_periodo  = '$periodo'
               AND c.bc_institucion = '$institucion'
               AND ls.idLibro = c.libro_idlibro              
            ");          
            //SI TODO HA SALIDO BIEN TRAEMOS LA DATA 
            if(count($data) >0){
                //obtener la fecha actual
                $fechaActual  = date('Y-m-d'); 
                //verificar si es el primer contrato
                $vericacionContrato = DB::select("SELECT  
                * FROM verificaciones_bc 
                WHERE contrato = '$contrato'
                ORDER BY id DESC
                ");
                //======PARA REALIZAR LA VERIFICACION EN CASO QUE EL CONTRATO YA TENGA VERIFICACIONES====
                if(count($vericacionContrato) >0){
                    //obtener el numero de verificacion en el que se quedo el contrato
                    $traerNumeroVerificacion =  $vericacionContrato[0]->num_verificacion;
                    $traeridVerificacion     =  $vericacionContrato[0]->id;
                    //Para guardar la verificacion si  existe el contrato 
                    //SI EXCEDE LAS 10 VERIFICACIONES
                    $finVerificacion="no";
                    if($traerNumeroVerificacion >10){
                        $finVerificacion = "yes";
                    }
                    else{        
                        //OBTENER LA CANTIDAD DE LA VERIFICACION ACTUAL 
                        $this->updateCodigoIndividualInicial($traeridVerificacion,$traerCodigosIndividual,$contrato,$traerNumeroVerificacion);
                        //Ingresar la liquidacion en la base
                        $this->guardarLiquidacion($data,$traerNumeroVerificacion,$contrato);
                        //Actualizo a estado 0 la verificacion anterior
                        DB::table('verificaciones_bc')
                        ->where('id', $traeridVerificacion)
                        ->update([
                            'fecha_fin' => $fechaActual,
                            'estado' => "0"
                        ]);
                        //  Para generar una verficacion y que quede abierta
                        $verificacion =  new VerificacionesBc;
                        $verificacion->num_verificacion = $traerNumeroVerificacion+1;
                        $verificacion->fecha_inicio = $fechaActual;
                        $verificacion->contrato = $contrato;
                        $verificacion->save();
                    }
                }else{
                    //=====PARA GUARDAR LA VERIFICACION SI EL CONTRATO AUN NO TIENE VERIFICACIONES======
                    //para indicar que aun no existe el fin de la verificacion
                     $finVerificacion = "0";
                    //Para guardar la primera verificacin en la tabla
                    $verificacion =  new VerificacionesBc;
                    $verificacion->num_verificacion = 1;
                    $verificacion->fecha_inicio = $fechaActual;
                    $verificacion->fecha_fin = $fechaActual;
                    $verificacion->contrato = $contrato;
                    $verificacion->estado = "0";
                    $verificacion->save();
                        //Obtener Verificacion actual
                        $encontrarVerificacionContratoInicial = DB::select("SELECT  
                            * FROM verificaciones_bc 
                            WHERE contrato = '$contrato'
                            ORDER BY id DESC
                        ");
                        //obtener el numero de verificacion en el que se quedo el contrato
                        $traerNumeroVerificacionInicial =  $encontrarVerificacionContratoInicial[0]->num_verificacion;
                        //obtener la clave primaria de la verificacion actual
                        $traerNumeroVerificacionInicialId = $encontrarVerificacionContratoInicial[0]->id;
                        //Actualizar cada codigo de la verificacion
                        $this->updateCodigoIndividualInicial($traerNumeroVerificacionInicialId,$traerCodigosIndividual,$contrato,$traerNumeroVerificacionInicial);
                        //Ingresar la liquidacion en la base
                        $this->guardarLiquidacion($data,$traerNumeroVerificacionInicial,$contrato);
                        //Para generar la siguiente verificacion y quede abierta
                        $verificacion =  new VerificacionesBc;
                        $verificacion->num_verificacion = $traerNumeroVerificacionInicial+1;
                        $verificacion->fecha_inicio = $fechaActual;
                        $verificacion->contrato = $contrato;
                        $verificacion->save();
                }                               
                if($finVerificacion =="yes"){
                    return [
                        "verificaciones"=>"Ha alzancado el limite de verificaciones permitidas",
                        'temporada'=>$temporadas,
                        'codigos_libros' => $data
                     ];
                }else{
                    return ['temporada'=>$temporadas,'codigos_libros' => $data];
                }
            }else{
                return ["status"=>"0", "message" => "No existe NUEVOS VALORES CON BARRAS para guardar la verificación"];
            }      
        }
    }

     public function guardarLiquidacion($data,$traerNumeroVerificacionInicial,$traerContrato){
        //Ingresar la liquidacion
        foreach($data as $item){
            VerificacionhasLiquidacionBc::create([
                'num_verificacion' => $traerNumeroVerificacionInicial,
                'contrato' => $traerContrato,
                'codigo' => $item->codigo,
                'cantidad' => $item->cantidad,
                'nombre_libro' => $item->nombrelibro,
                'estado' => '1',
            ]);
        }
     }

     public function updateCodigoIndividualInicial($traerNumeroVerificacionInicialId,$traerCodigosIndividual,$contrato,$num_verificacion){       
        $columnaVerificacion = "verif".$num_verificacion;
        //PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION
        foreach($traerCodigosIndividual as $item){
            DB::table('codigoslibros')
            ->where('codigo', $item->codigo)
            ->update([
                $columnaVerificacion =>  $traerNumeroVerificacionInicialId,
                'estado_liquidacion' => "0",
                'contrato' => $contrato,
                'bc_estado' => '3'
            ]);
        }
     }

   
}
