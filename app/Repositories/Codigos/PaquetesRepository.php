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
        $proforma_empresa            = $request->proforma_empresa;
        $codigo_proforma             = $request->codigo_proforma;
        $ifSetProforma               = $request->ifSetProforma;
        $datosProforma               = [
            "proforma_empresa"      => $proforma_empresa,
            "codigo_proforma"       => $codigo_proforma,
        ];
        $ifChangeProformaA           = false;
        $ifErrorProformaA            = false;
        $ifChangeProformaD           = false;
        $ifErrorProformaD            = false;
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
        //validar si el codigo tiene proforma empresa proforma_empresa
        $ifproforma_empresaA             = $validarA[0]->proforma_empresa;
        //validar si el codigo tiene proforma codigo_proforma
        $ifcodigo_proformaA             = $validarA[0]->codigo_proforma;
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
        //validar si el codigo tiene proforma empresa proforma_empresa
        $ifproforma_empresaD            = $validarD[0]->proforma_empresa;
        //validar si el codigo tiene proforma codigo_proforma
        $ifcodigo_proformaD             = $validarD[0]->codigo_proforma;
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
         //=============PROFORMA ==========
        //cambiar codigo de proforma
        if($ifSetProforma == 1){
            //si codigo proforma es nulo le permito que actualize el codigo proforma activacion
            if($ifcodigo_proformaA == null || $ifcodigo_proformaA == "" ){
                $ifChangeProformaA = true;
                $ifErrorProformaA  = false;
            }else{
                //valido que el ifcodigo_proforma sea igual codigo_proforma y el ifproforma_empresa es igual a proforma_empresa
                if($ifcodigo_proformaA == $codigo_proforma && $ifproforma_empresaA == $proforma_empresa){
                    $ifChangeProformaA = true;
                    $ifErrorProformaA  = false;
                }
                //si no es igual guardo en un array
                else{
                    $ifChangeProformaA = false;
                    $ifErrorProformaA  = true;
                }
            }
            //si codigo proforma es nulo le permito que actualize el codigo proforma diagnostico
            if($ifcodigo_proformaD == null || $ifcodigo_proformaD == "" ){
                $ifChangeProformaD = true;
                $ifErrorProformaD  = false;
            }else{
                //valido que el ifcodigo_proforma sea igual codigo_proforma y el ifproforma_empresa es igual a proforma_empresa
                if($ifcodigo_proformaD == $codigo_proforma && $ifproforma_empresaD == $proforma_empresa){
                    $ifChangeProformaD = true;
                    $ifErrorProformaD  = false;
                }
                //si no es igual guardo en un array
                else{
                    $ifChangeProformaD = false;
                    $ifErrorProformaD  = true;
                }
            }
        }
    ///============PROFORMA =======
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
                //validacion profomas
                if($ifErrorProformaA) $errorA = 1;
                if($ifErrorProformaD) $errorD = 1;
                //ifcodigo_proformaA
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura,"ifChangeProforma" => $ifChangeProformaA,"ifcodigo_proformaA" => $ifcodigo_proformaA];
            }
            //======REGALADO NO ENTRA A LA LIQUIDACION============
            if($tipoProceso == '1' || $tipoProceso == '5'){
                if( $ifLiquidadoA == '1' && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') )  $errorA = 0;
                if( $ifLiquidadoD == '1' && (($codigo_unionD == $codigoActivacion)  || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  ) $errorD = 0;
                //validacion profomas
                if($ifErrorProformaA) $errorA = 1;
                if($ifErrorProformaD) $errorD = 1;
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura,"ifChangeProforma" => $ifChangeProformaA];
            }
            //======REGALADO Y BLOQUEADO(No usan y no liquidan)=============
            if($tipoProceso == '2' || $tipoProceso == '6'){
                if( ($ifLiquidadoA !='0' && $ifLiquidadoA !='4') && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') )  $errorA = 0;
                if( ($ifLiquidadoD !='0' && $ifLiquidadoD !='4') && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion)  || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  ) $errorD = 0;
                //validacion profomas
                if($ifErrorProformaA) $errorA = 1;
                if($ifErrorProformaD) $errorD = 1;
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura,"ifChangeProforma" => $ifChangeProformaA];
            }
            //===== BLOQUEADO(No usan y no liquidan)=============
            if($tipoProceso == '3' || $tipoProceso == '7'){
                if( ($ifLiquidadoA !='0' && $ifLiquidadoA !='4') && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') )  $errorA = 0;
                if( ($ifLiquidadoD !='0' && $ifLiquidadoD !='4') && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == ""  || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  ) $errorD = 0;
                //validacion profomas
                if($ifErrorProformaA) $errorA = 1;
                if($ifErrorProformaD) $errorD = 1;
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura,"ifChangeProforma" => $ifChangeProformaA];
            }
            //===== GUIA=============
            if($tipoProceso == '4' || $tipoProceso == '8'){
                if( ($ifLiquidadoA == '1') && $ifBcEstadoA == '1' && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') )  $errorA = 0;
                if( ($ifLiquidadoD == '1') && $ifBcEstadoD == '1' && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == ""  || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  ) $errorD = 0;
                //validacion profomas
                if($ifErrorProformaA) $errorA = 1;
                if($ifErrorProformaD) $errorD = 1;
                return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura,"ifChangeProforma" => $ifChangeProformaA];
            }
        }else{
            return ["errorA" => $errorA, "errorD" => $errorD, "factura" => $factura,"ifChangeProforma" => $ifChangeProformaA];
        }
    }
    public function procesoGestionBodega($numeroProceso,$codigo,$codigo_union,$request,$factura,$codigoPaquete,$ifChangeProforma=false,$datosProforma=[]){
        //numero proceso => 0 = usan y liquidan; 1 = venta lista; 2 = regalado; 3 regalado y bloqueado; 4 = guia; 5 = regalado sin institucion; 6 = bloqueado y regalado sin institucion; 7 = bloqueado sin institucion; 8 = guia sin institucion;
        $estadoIngreso       = 0;
        $codigoU             = $this->codigosRepository->procesoUpdateGestionBodega($numeroProceso,$codigo_union,$codigo,$request,$factura,$codigoPaquete,$ifChangeProforma,$datosProforma);
        if($codigoU) $codigo = $this->codigosRepository->procesoUpdateGestionBodega($numeroProceso,$codigo,$codigo_union,$request,$factura,$codigoPaquete,$ifChangeProforma,$datosProforma);
        if($codigo && $codigoU)  $estadoIngreso = 1;
        else                     $estadoIngreso = 2;
        return $estadoIngreso;
    }

}
?>
