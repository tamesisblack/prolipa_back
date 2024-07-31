<?php
namespace App\Repositories\Codigos;

use App\Models\CodigosPaquete;
use App\Repositories\BaseRepository;
use DB;
class  PaquetesRepository extends BaseRepository
{
    private $codigosRepository;
    public function __construct(CodigosPaquete $PaqueteRepository, CodigosRepository $codigosRepository)
    {
        parent::__construct($PaqueteRepository);
        $this->codigosRepository = $codigosRepository;
    }
    public function validateGestion($tipoProceso,$estadoPaquete,$validarA,$validarD,$item,$codigoActivacion,$codigoDiagnostico,$request){
        $periodo_id                  = $request->periodo_id;
        $errorA                      = 1;
        $errorD                      = 1;
        //====Activacion=====
        //validar si el codigo ya esta liquidado
        $ifLiquidadoA                = $validarA[0]->estado_liquidacion;
        //validar si el codigo no este bloqueado
        $ifBloqueadoA                = $validarA[0]->estado;
        //validar que el codigo de paquete sea nulo
        $ifcodigo_paqueteA           = $validarA[0]->codigo_paquete;
        //codigo de union
        $codigo_unionA               = strtoupper($validarA[0]->codigo_union);
        //liquidado regalado
        $ifliquidado_regaladoA       = $validarA[0]->liquidado_regalado;
        //validar que el periodo del estudiante sea 0 o sea igual al que se envia
        $ifid_periodoA               = $validarA[0]->id_periodo;
        //validar si el codigo no este leido
        $ifBcEstadoA                 = $validarA[0]->bc_estado;
        ///*//////===================Diagnostico=======*/////
        //validar si el codigo ya esta liquidado
        $ifLiquidadoD                = $validarD[0]->estado_liquidacion;
        //validar si el codigo no este bloqueado
        $ifBloqueadoD                = $validarD[0]->estado;
        //validar que el codigo de paquete sea nulo
        $ifcodigo_paqueteD           = $validarD[0]->codigo_paquete;
        //codigo de union
        $codigo_unionD               = strtoupper($validarD[0]->codigo_union);
        //liquidado regalado
        $ifliquidado_regaladoD       = $validarD[0]->liquidado_regalado;
        //validar que el periodo del estudiante sea 0 o sea igual al que se envia
        $ifid_periodoD               = $validarD[0]->id_periodo;
        //validar si el codigo no este leido
        $ifBcEstadoD                 = $validarD[0]->bc_estado;
        //obtener la factura si no envian nada le dejo lo mismo
        $facturaA                     = $validarA[0]->factura;
        if($request->factura == null || $request->factura == "")   $factura = $facturaA;
        else  $factura = $request->factura;
        //===PRIMERA VALIDACION====
        //error 0 => no hay error; 1 hay error
        //==SI EL PAQUETE ESTA CERRADO VALIDAR QUE SEA EL MISMO PAQUETE
        //estadoPaquete => 0 = utilizado; 1 abierto;
        if($estadoPaquete == 0){
            if( ($ifcodigo_paqueteA == $item->codigoPaquete) ||  ($ifcodigo_paqueteA == "" || $ifcodigo_paqueteA == null ) )  $errorA = 0;
            if( ($ifcodigo_paqueteD == $item->codigoPaquete) ||  ($ifcodigo_paqueteD == "" || $ifcodigo_paqueteD == null ) )  $errorD = 0;
        }else{
            if( $ifcodigo_paqueteA == null )                  $errorA = 0;
            if( $ifcodigo_paqueteD == null )                  $errorD = 0;
        }
        //si pasa la validacion voy la validacion por cada botton
        if($errorA == 0 && $errorD == 0){
            $errorA = 1;
            $errorD = 1;
            //=====USAN Y LIQUIDAN=========================
            if($tipoProceso == '0'){
                if(($ifid_periodoA  == $periodo_id || $ifid_periodoA == 0 ||  $ifid_periodoA == null  ||  $ifid_periodoA == "")  && ($ifBcEstadoA == '1')  && $ifLiquidadoA == '1' && $ifBloqueadoA !=2 &&  (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0"))  && $ifliquidado_regaladoA == '0') $errorA = 0;
                if(($ifid_periodoD  == $periodo_id || $ifid_periodoD == 0 ||  $ifid_periodoD == null  ||  $ifid_periodoD == "") && ($ifBcEstadoD == '1')  && $ifLiquidadoD == '1' && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && $ifliquidado_regaladoD == '0')     $errorD = 0;
                //si el codigo del paquete esta regalado tiene guia  o esta bloqueado no se edita estos campos
                if($ifLiquidadoA  == '2' || $ifLiquidadoA == '4' || $ifBloqueadoA == 2)  { $errorA = 0; $errorA = 0; }
                if($ifLiquidadoD  == '2' || $ifLiquidadoD == '4' || $ifBloqueadoD == 2)  { $errorD = 0; $errorD = 0; }
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura];
            }
            //======REGALADO NO ENTRA A LA LIQUIDACION============
            if($tipoProceso == '1' || $tipoProceso == '5'){
                if( $ifLiquidadoA == '1' && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') )  $errorA = 0;
                if( $ifLiquidadoD == '1' && (($codigo_unionD == $codigoActivacion)  || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  ) $errorD = 0;
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura];
            }
            //======REGALADO Y BLOQUEADO(No usan y no liquidan)=============
            if($tipoProceso == '2' || $tipoProceso == '6'){
                if( ($ifLiquidadoA !='0' && $ifLiquidadoA !='4') && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') )  $errorA = 0;
                if( ($ifLiquidadoD !='0' && $ifLiquidadoD !='4') && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion)  || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  ) $errorD = 0;
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura];
            }
            //===== BLOQUEADO(No usan y no liquidan)=============
            if($tipoProceso == '3' || $tipoProceso == '7'){
                if( ($ifLiquidadoA !='0' && $ifLiquidadoA !='4') && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') )  $errorA = 0;
                if( ($ifLiquidadoD !='0' && $ifLiquidadoD !='4') && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == ""  || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  ) $errorD = 0;
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura];
            }
            //===== GUIA=============
            if($tipoProceso == '4' || $tipoProceso == '8'){
                if( ($ifLiquidadoA == '1') && $ifBcEstadoA == '1' && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') )  $errorA = 0;
                if( ($ifLiquidadoD == '1') && $ifBcEstadoD == '1' && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == ""  || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  ) $errorD = 0;
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura];
            }
        }else{
            return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura];
        }
    }
    public function procesoGestionBodega($numeroProceso,$codigo,$codigo_union,$request,$factura,$codigoPaquete,$tipoBodega){
        //numero proceso => 0 = usan y liquidan; 1 = venta lista; 2 = regalado; 3 regalado y bloqueado; 4 = guia; 5 = regalado sin institucion; 6 = bloqueado y regalado sin institucion; 7 = bloqueado sin institucion; 8 = guia sin institucion;
        $estadoIngreso       = 0;
        $codigoU             = $this->codigosRepository->procesoUpdateGestionBodega($numeroProceso,$codigo_union,$codigo,$request,$factura,$codigoPaquete,$tipoBodega);
        if($codigoU) $codigo = $this->codigosRepository->procesoUpdateGestionBodega($numeroProceso,$codigo,$codigo_union,$request,$factura,$codigoPaquete,$tipoBodega);
        if($codigo && $codigoU)  $estadoIngreso = 1;
        else                     $estadoIngreso = 2;
        return $estadoIngreso;
    }

}
?>
