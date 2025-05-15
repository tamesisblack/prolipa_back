<?php

namespace App\Http\Controllers;

use App\Models\CodigoLibros;
use App\Models\Periodo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Imports\CodigosImport;
use App\Models\_14ProductoStockHistorico;
use App\Models\CodigosDevolucion;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionHeader;
use App\Models\CodigosLibrosDevolucionSon;
use App\Models\CodigosPaquete;
use App\Models\CombosCodigos;
use App\Models\f_tipo_documento;
use App\Models\Facturacion\Inventario\ConfiguracionGeneral;
use App\Models\HistoricoCodigos;
use App\Models\Institucion;
use App\Models\LibroSerie;
use App\Models\Ventas;
use App\Repositories\Codigos\CodigosRepository;
use App\Repositories\Facturacion\DevolucionRepository;
use App\Repositories\Facturacion\ProformaRepository;
use App\Repositories\pedidos\NotificacionRepository;
use App\Repositories\pedidos\PedidosRepository;
use App\Repositories\pedidos\VerificacionRepository;
use App\Traits\Codigos\TraitCodigosGeneral;
use App\Traits\Pedidos\TraitGuiasGeneral;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use PDO;

class CodigoLibrosController extends Controller
{
    use TraitCodigosGeneral;
    use TraitPedidosGeneral;
    use TraitGuiasGeneral;
    private $codigosRepository;
    protected $proformaRepository;
    protected $pedidosRepository;
    protected $devolucionRepository;
    protected $verificacionRepository;
    protected $NotificacionRepository;

    public function __construct(CodigosRepository $codigosRepository,ProformaRepository $proformaRepository, PedidosRepository $pedidosRepository, DevolucionRepository $devolucionRepository, VerificacionRepository $verificacionRepository, NotificacionRepository $NotificacionRepository) {
        $this->codigosRepository    = $codigosRepository;
        $this->proformaRepository   = $proformaRepository;
        $this->pedidosRepository    = $pedidosRepository;
        $this->devolucionRepository = $devolucionRepository;
        $this->verificacionRepository = $verificacionRepository;
        $this->NotificacionRepository = $NotificacionRepository;
    }
    //api:post//codigos/importar
    public function importar(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $institucion            = $request->institucion_id;
        $getDataInstitucion     = Institucion::Where('idInstitucion',$institucion)->get();
        $nombreInstitucion      = $getDataInstitucion[0]->nombreInstitucion;
        $traerPeriodo           = $request->periodo_id;
        // $nombreInstitucion      = $request->nombreInstitucion;
        $nombrePeriodo          = $request->nombrePeriodo;
        $venta_estado           = $request->venta_estado;
        $comentario             = "Codigo leido de ".$nombreInstitucion." - ".$nombrePeriodo;
        $id_usuario             = $request->id_usuario;
        $id_group               = $request->id_group;
        $codigosNoCambiados     = [];
        $contadorNoCambiado     = 0;
        $codigosLeidos          = [];
        $codigoNoExiste         = [];
        $codigosSinCodigoUnion  = [];
        $contadorNoExiste       = 0;
        $porcentaje             = 0;
        $contador               = 0;
        foreach($codigos as $key => $item){
            //validar si el codigo existe
            $validar = $this->getCodigos($item->codigo,0);
            //valida que el codigo existe
            if(count($validar) > 0){
                //validar si el codigo ya haya sido leido
                $ifLeido            = $validar[0]->bc_estado;
                //validar si el codigo ya esta liquidado
                $ifLiquidado        = $validar[0]->estado_liquidacion;
                //validar si el codigo no este liquidado
                $ifBloqueado        = $validar[0]->estado;
                //validar si tiene bc_institucion
                $ifBc_Institucion   = $validar[0]->bc_institucion;
                //validar que el periodo del estudiante sea 0 o sea igual al que se envia
                $ifid_periodo       = $validar[0]->id_periodo;
                //validar que el venta_estado sea cero o igual al enviado desde el front
                $ifventa_estado     = $validar[0]->venta_estado;
                //validar el bc_periodo
                $ifbc_periodo       = $validar[0]->bc_periodo;
                //validar si tiene codigo de union
                $codigo_union       = $validar[0]->codigo_union;
                $preValidate         = false;
                //VALIDACION
                if($id_group == 11) { $preValidate = (($ifid_periodo  == $traerPeriodo || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "") && ($ifBc_Institucion  == $institucion || $ifBc_Institucion == 0) && ($ifbc_periodo  == $traerPeriodo || $ifbc_periodo == 0) && ($ifventa_estado != 0) && $ifLeido == '1' && $ifLiquidado == '1' && $ifBloqueado !=2); }
                else{                 $preValidate = (($ifid_periodo  == $traerPeriodo || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "") && ($ifBc_Institucion  == $institucion || $ifBc_Institucion == 0) && ($ifbc_periodo  == $traerPeriodo || $ifbc_periodo == 0) && ($ifventa_estado == 0 || $ifventa_estado == $venta_estado) && $ifLeido == '1' && $ifLiquidado == '1' && $ifBloqueado !=2); }
                //===PROCESO===========
                if($preValidate){
                    //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
                    $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                    $getcodigoUnion   = CodigosLibros::Where('codigo',$codigo_union)->get();
                    if($codigo_union != null || $codigo_union != ""){
                        //PASAR A LEIDO CON CODIGO DE UNION
                        $ingreso = $this->pasarLeidos($item->codigo,$codigo_union,$request,$getcodigoUnion);
                        //si ingresa correctamente
                        if($ingreso == 1){
                            $porcentaje++;
                            //====CODIGO====
                            //ingresar en el historico codigo
                            $this->GuardarEnHistorico(0,$institucion,$traerPeriodo,$item->codigo,$id_usuario,$comentario,$getcodigoPrimero,null);
                            //====CODIGO UNION=====
                            $this->GuardarEnHistorico(0,$institucion,$traerPeriodo,$codigo_union,$id_usuario,$comentario,$getcodigoUnion,null);
                        }else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "Problema con el código union $codigo_union"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                    //ACTUALIZAR CODIGO SIN UNION
                    else{
                        $ingreso =  $this->pasarLeidos($item->codigo,0,$request,null);
                        if($ingreso == 1){
                            $porcentaje++;
                            //ingresar en el historico
                            $this->GuardarEnHistorico(0,$institucion,$traerPeriodo,$item->codigo,$id_usuario,$comentario,$getcodigoPrimero,null);
                            $codigosSinCodigoUnion[] = $validar[0];
                        }
                        else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "0"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                }else{
                    $codigosLeidos[$contador] = $validar[0];
                    $contador++;
                }
            }else{
                $codigoNoExiste[$contadorNoExiste] =[
                    "codigo" => $item->codigo
                ];
                $contadorNoExiste++;
            }
        }
        return [
            "cambiados"             => $porcentaje,
            "codigosNoCambiados"    => $codigosNoCambiados,
            "codigosLeidos"         => $codigosLeidos,
            "codigoNoExiste"        => $codigoNoExiste,
            "codigosSinCodigoUnion" => $codigosSinCodigoUnion,
        ];
    }
    public function pasarLeidos($codigo,$codigo_union,$request,$objectCodigoUnion){
        $withCodigoUnion = 1;
        $estadoIngreso   = 0;
        $todate          = date('Y-m-d H:i:s');
        $institucion     = $request->institucion_id;
        $traerPeriodo    = $request->periodo_id;
        $venta_estado    = $request->venta_estado;
        $id_group        = $request->id_group;
        $unionCorrecto   = false;
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($codigo_union == '0') $withCodigoUnion = 0;
        else                     $withCodigoUnion = 1;
        if($id_group == 11){
            $arraySave  = [
                'bc_institucion'        => $institucion,
                'bc_estado'             => 2,
                'bc_periodo'            => $traerPeriodo,
                // 'bc_fecha_ingreso'      => $todate,
                // 'venta_estado'          => $venta_estado
            ];
        }else{
            $arraySave  = [
                'bc_institucion'        => $institucion,
                'bc_estado'             => 2,
                'bc_periodo'            => $traerPeriodo,
                'bc_fecha_ingreso'      => $todate,
                'venta_estado'          => $venta_estado
            ];
        }
        //si hay codigo de union lo actualizo
        if($withCodigoUnion == 1){
            //VALIDO SI NO EXISTE EL CODIGO DE UNION LO MANDO COMO ERROR
            if(count($objectCodigoUnion) == 0){
                //no se ingreso
                return 2;
            }
            //VALIDO QUE EL CODIGO UNION CUMPLA LA VALIDACION
            //validar si el codigo ya haya sido leido
            $ifLeido            = $objectCodigoUnion[0]->bc_estado;
            //validar si el codigo ya esta liquidado
            $ifLiquidado        = $objectCodigoUnion[0]->estado_liquidacion;
            //validar si el codigo no este liquidado
            $ifBloqueado        = $objectCodigoUnion[0]->estado;
            //validar si tiene bc_institucion
            $ifBc_Institucion   = $objectCodigoUnion[0]->bc_institucion;
            //validar que el periodo del estudiante sea 0 o sea igual al que se envia
            $ifid_periodo       = $objectCodigoUnion[0]->id_periodo;
            //validar que el venta_estado sea cero o igual al enviado desde el front
            $ifventa_estado     = $objectCodigoUnion[0]->venta_estado;
            //validar el bc_periodo
            $ifbc_periodo       = $objectCodigoUnion[0]->bc_periodo;
            $preValidate        = false;
            //VALIDACIO N
            if($id_group == 11) { $preValidate = (($ifid_periodo  == $traerPeriodo || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "") && ($ifBc_Institucion  == $institucion || $ifBc_Institucion == 0) && ($ifbc_periodo  == $traerPeriodo || $ifbc_periodo == 0) && ($ifventa_estado != 0) && $ifLeido == '1' && $ifLiquidado == '1' && $ifBloqueado !=2); }
            else{                 $preValidate = (($ifid_periodo  == $traerPeriodo || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "") && ($ifBc_Institucion  == $institucion || $ifBc_Institucion == 0) && ($ifbc_periodo  == $traerPeriodo || $ifbc_periodo == 0) && ($ifventa_estado == 0 || $ifventa_estado == $venta_estado) && $ifLeido == '1' && $ifLiquidado == '1' && $ifBloqueado !=2); }
            //===PROCESO===========
            if($preValidate){
                $unionCorrecto = true;
            // if(($ifid_periodo  == $traerPeriodo || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "") && ($ifBc_Institucion  == $institucion || $ifBc_Institucion == 0) && ($ifbc_periodo  == $traerPeriodo || $ifbc_periodo == 0) && ($ifventa_estado == 0 || $ifventa_estado == $venta_estado) && $ifLeido == '1' && $ifLiquidado == '1' && $ifBloqueado !=2) $unionCorrecto = true;
            }else { $unionCorrecto = false; }
            if($unionCorrecto){
                $codigoU = DB::table('codigoslibros')
                ->where('codigo', '=', $codigo_union)
                ->where('bc_estado', '1')
                ->where('estado','<>', '2')
                ->where('estado_liquidacion','=', '1')
                ->update($arraySave);
                //si el codigo de union se actualiza actualizo el codigo
                if($codigoU){
                    //actualizar el primer codigo
                    $codigo = DB::table('codigoslibros')
                    ->where('codigo', '=', $codigo)
                    ->where('bc_estado', '1')
                    ->where('estado','<>', '2')
                    ->where('estado_liquidacion','=', '1')
                    ->update($arraySave);
                }
            }else{
                //no se ingreso
                return 2;
            }
        }else{
            //actualizar el primer codigo
            $codigo = DB::table('codigoslibros')
            ->where('codigo', '=', $codigo)
            ->where('bc_estado', '1')
            ->where('estado','<>', '2')
            ->where('estado_liquidacion','=', '1')
            ->update($arraySave);
        }
        //con codigo union
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($withCodigoUnion == 1){
            if($codigo && $codigoU)  $estadoIngreso = 1;
            else                     $estadoIngreso = 2;
        }
        //si no existe el codigo de union
        if($withCodigoUnion == 0){
            if($codigo)              $estadoIngreso = 1;
        }
        return $estadoIngreso;
    }
    public function procesoGestionBodega($numeroProceso,$codigo,$codigo_union,$request,$objectCodigoUnion,$factura,$setProforma=false,$datosProforma=[]){
        //numero proceso => 0 = usan y liquidan; 1 = venta lista; 2 = regalado; 3 regalado y bloqueado
        $withCodigoUnion    = 1;
        $estadoIngreso      = 0;
        $periodo_id         = $request->periodo_id;
        $unionCorrecto      = false;
        $codigo_proforma    = $datosProforma['codigo_proforma'];
        $proforma_empresa   = $datosProforma['proforma_empresa'];
        $ifChangeProforma   = false;
        $ifErrorProforma    = false;
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($codigo_union == null) $withCodigoUnion = 0;
        else                      $withCodigoUnion = 1;
        //si hay codigo de union lo actualizo
        if($withCodigoUnion == 1){
            //VALIDO SI NO EXISTE EL CODIGO DE UNION LO MANDO COMO ERROR
            if(count($objectCodigoUnion) == 0){
                //no se ingreso
                return 2;
            }
            //validar si el codigo ya haya sido leido
            $ifLeido                    = $objectCodigoUnion[0]->bc_estado;
            //validar si el codigo ya esta liquidado
            $ifLiquidado                = $objectCodigoUnion[0]->estado_liquidacion;
            //validar si el codigo no este liquidado
            $ifBloqueado                = $objectCodigoUnion[0]->estado;
            //validar si tiene bc_institucion
            $ifBc_Institucion           = $objectCodigoUnion[0]->bc_institucion;
            //validar que el periodo del estudiante sea 0 o sea igual al que se envia
            $ifid_periodo               = $objectCodigoUnion[0]->id_periodo;
            //validar si el codigo tiene venta_estado
            $venta_estado               = $objectCodigoUnion[0]->venta_estado;
            //venta lista
            $ifventa_lista_institucion  = $objectCodigoUnion[0]->venta_lista_institucion;
            //para ver si un codigo regalado esta liquidado
            $ifliquidado_regalado       = $objectCodigoUnion[0]->liquidado_regalado;
            //validar que no tenga paquete
            $codigo_paquete             = $objectCodigoUnion[0]->codigo_paquete;
            //validar si el codigo tiene proforma empresa proforma_empresa
            $ifproforma_empresa         = $objectCodigoUnion[0]->proforma_empresa;
            //validar si el codigo tiene proforma codigo_proforma
            $ifcodigo_proforma          = $objectCodigoUnion[0]->codigo_proforma;
            //validar si tiene codigo de union
            $ifNotPaquete               = false;
            $ifNotPaquete               = (($codigo_paquete == null || $codigo_paquete == ""));
            //=============PROFORMA ==========
                //cambiar codigo de proforma
                if($setProforma){
                    //si codigo proforma es nulo le permito que actualize el codigo proforma
                    if($ifcodigo_proforma == null || $ifcodigo_proforma == "" ){
                        $ifChangeProforma = true;
                        $ifErrorProforma  = false;
                    }else{
                        //valido que el ifcodigo_proforma sea igual codigo_proforma y el ifproforma_empresa es igual a proforma_empresa
                        if($ifcodigo_proforma == $codigo_proforma && $ifproforma_empresa == $proforma_empresa){
                            $ifChangeProforma = true;
                            $ifErrorProforma  = false;
                        }
                        //si no es igual guardo en un array
                        else{
                            $ifChangeProforma = false;
                            $ifErrorProforma  = true;
                        }
                    }
                }
            ///============PROFORMA =======
            //=====USAN Y LIQUIDAN=========================
            if($numeroProceso == '0'){
                if(($ifid_periodo  == $periodo_id || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "")  && ($ifLeido == '1') && $ifLiquidado == '1' && $ifBloqueado !=2 && $ifNotPaquete) $unionCorrecto = true;
                else $unionCorrecto = false;
            }
            //======REGALADO NO ENTRA A LA LIQUIDACION============
            if($numeroProceso == '1' || $numeroProceso == '5'){
                $booleanRegalado = false;
                if($numeroProceso == '5') { $booleanRegalado = ($ifLiquidado == '1' && $ifliquidado_regalado == '0' ); }
                //si no es el import de paquete valido que el codigo no tenga paquete
                else{ $booleanRegalado                     = ($ifLiquidado == '1' && $ifliquidado_regalado == '0' && $ifNotPaquete);}
                if($booleanRegalado) { $unionCorrecto = true; }
                else  { $unionCorrecto = false; }
            }
            //======REGALADO Y BLOQUEADO(No usan y no liquidan)=============
            if($numeroProceso == '2' || $numeroProceso == '6'){
                $booleanRegaladoB = false;
                //paquete
                if($numeroProceso == '6') { $booleanRegaladoB = (($ifLiquidado !='0' && $ifLiquidado !='4') && $ifBloqueado !=2 && $ifliquidado_regalado == '0' ); }
                //si no es el import de paquete valido que el codigo no tenga paquete
                else{ $booleanRegaladoB                     = (($ifLiquidado !='0' && $ifLiquidado !='4') && $ifBloqueado !=2 && $ifliquidado_regalado == '0' && $ifNotPaquete);}
                if($booleanRegaladoB) { $unionCorrecto = true; }
                else  { $unionCorrecto = false; }
            }
            //======BLOQUEADO(No usan y no liquidan)=============
            if($numeroProceso == '3' || $numeroProceso == '7'){
                $booleanBloqueado  = false;
                //paquete
                if($numeroProceso == '7') { $booleanBloqueado = (($ifLiquidado !='0' && $ifLiquidado !='4') && $ifBloqueado !=2 && $ifliquidado_regalado == '0' ); }
                //si no es el import de paquete valido que el codigo no tenga paquete
                else{ $booleanBloqueado                     = (($ifLiquidado !='0' && $ifLiquidado !='4') && $ifBloqueado !=2 && $ifliquidado_regalado == '0' && $ifNotPaquete);}
                if($booleanBloqueado) { $unionCorrecto = true; }
                else  { $unionCorrecto = false; }
            }
            //=======CODIGO GUIA==================================
            if($numeroProceso == '4' || $numeroProceso == '8'){
                $booleanGuia = false;
                //paquete
                if($numeroProceso == '8') { $booleanGuia = (($ifLiquidado == '1' || $ifLiquidado == '4') && $ifLeido == '1' && $ifBloqueado !=2 && $ifliquidado_regalado == '0'); }
                //si no es el import de paquete valido que el codigo no tenga paquete
                else{ $booleanGuia                     = (($ifLiquidado == '1' || $ifLiquidado == '4') && $ifLeido == '1' && $ifBloqueado !=2 && $ifliquidado_regalado == '0' && $ifNotPaquete);}
                if($booleanGuia) { $unionCorrecto = true; }
                else  { $unionCorrecto = false; }
            }
            if($unionCorrecto && $ifErrorProforma == false){
                $codigoU             = $this->codigosRepository->procesoUpdateGestionBodega($numeroProceso,$codigo_union,$codigo,$request,$factura,null,$ifChangeProforma,$datosProforma);
                if($codigoU) $codigo = $this->codigosRepository->procesoUpdateGestionBodega($numeroProceso,$codigo,$codigo_union,$request,$factura,null,$ifChangeProforma,$datosProforma);
            }else{
                //no se ingreso
                return 2;
            }
        }
        //SI EL CODIGO NO TIENE CODIGO DE UNION
        else{
            $ifChangeProforma = $setProforma;
            $codigo = $this->codigosRepository->procesoUpdateGestionBodega($numeroProceso,$codigo,null,$request,$factura,null,$ifChangeProforma,$datosProforma);
        }
        //resultado
        //con codigo union
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($setProforma){
            if($withCodigoUnion == 1){
                if($codigo && $codigoU && $ifErrorProforma == false)  $estadoIngreso = 1;
                else                     $estadoIngreso = 2;
            }
            //si no existe el codigo de union
            if($withCodigoUnion == 0){
                if($codigo && $ifErrorProforma == false)              $estadoIngreso = 1;
            }
        }else{
            if($withCodigoUnion == 1){
                if($codigo && $codigoU)  $estadoIngreso = 1;
                else                     $estadoIngreso = 2;
            }
            //si no existe el codigo de union
            if($withCodigoUnion == 0){
                if($codigo)              $estadoIngreso = 1;
            }
        }

        return $estadoIngreso;
    }
    //api:post//codigos/import/gestion
    public function importGestion(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                    = json_decode($request->data_codigos);
        //variables
        $usuario_editor             = $request->id_usuario;
        $institucion                = $request->institucion_id;
        $comentario                 = $request->comentario;
        $comboSelected              = $request->comboSelected;
        if($request->ifSetCombo == 1){
            $comentario = $comboSelected."_".$request->comentario;
        }else{
            $comentario = $request->comentario;
        }
        $periodo_id                 = $request->periodo_id;
        //0=> USAN Y LIQUIDAN ; 1=> regalado; 2 regalado y bloqueado; 3 = bloqueado
        $tipoProceso                = $request->regalado;
        $codigoNoExiste             = [];
        $codigosDemas               = [];
        $contadorNoExiste           = 0;
        $codigosNoCambiados         = [];
        $codigosSinCodigoUnion      = [];
        $arrayCodigosWithProforma   = [];
        $usuarioQuemado             = 45017;
        $instUserQuemado            = 66;
        $contador                   = 0;
        $contadorNoCambiado         = 0;
        $contadorWithProforma       = 0;
        $numeroProceso              = $request->regalado;
        $porcentaje                 = 0;
        $factura                    = "";
        $tipoBodega                 = $request->tipo_bodega;
        $proforma_empresa           = $request->proforma_empresa;
        $codigo_proforma            = $request->codigo_proforma;
        $ifSetProforma              = $request->ifSetProforma;
        $datosProforma              = [
            "proforma_empresa"      => $proforma_empresa,
            "codigo_proforma"       => $codigo_proforma,
        ];
        foreach($codigos as $key => $item){
            //validar si el codigo existe
            $validacionCodigo               = false;
            $ifChangeProforma               = false;
            $ifErrorProforma                = false;
            $user                           = 0;
            $validar = $this->getCodigos($item->codigo,0);
            //valida que el codigo existe
            if(count($validar) > 0){
                //validar si el codigo ya haya sido leido
                $ifLeido                    = $validar[0]->bc_estado;
                //validar si el codigo ya esta liquidado
                $ifLiquidado                = $validar[0]->estado_liquidacion;
                //validar si el codigo no este liquidado
                $ifBloqueado                = $validar[0]->estado;
                //validar si tiene bc_institucion
                $ifBc_Institucion           = $validar[0]->bc_institucion;
                //validar que el periodo del estudiante sea 0 o sea igual al que se envia
                $ifid_periodo               = $validar[0]->id_periodo;
                //validar si el codigo tiene venta_estado
                $ifventa_estado             = $validar[0]->venta_estado;
                //venta lista
                $ifventa_lista_institucion  = $validar[0]->venta_lista_institucion;
                //validar si tiene codigo de union
                $codigo_union               = $validar[0]->codigo_union;
                //obtener la factura si no envian nada le dejo lo mismo
                $facturaA                   = $validar[0]->factura;
                //el codigo de paquete debe ser vacio
                $codigo_paquete             = $validar[0]->codigo_paquete;
                //validar si un regalado esta liquidado
                $ifliquidado_regalado       = $validar[0]->liquidado_regalado;
                //validar si el codigo tiene proforma empresa proforma_empresa
                $ifproforma_empresa         = $validar[0]->proforma_empresa;
                //validar si el codigo tiene proforma codigo_proforma
                $ifcodigo_proforma          = $validar[0]->codigo_proforma;
                if($request->factura == null || $request->factura == "")   { $factura = $facturaA; }
                else{ $factura = $request->factura; }
                $ifNotPaquete  = false;
                $ifNotPaquete  = (($codigo_paquete == null || $codigo_paquete == ""));
                //=============PROFORMA ==========
                    //cambiar codigo de proforma
                    if($ifSetProforma == 1){
                        //si codigo proforma es nulo le permito que actualize el codigo proforma
                        if($ifcodigo_proforma == null || $ifcodigo_proforma == "" ){
                            $ifChangeProforma = true;
                            $ifErrorProforma  = false;
                        }else{
                            //valido que el ifcodigo_proforma sea igual codigo_proforma y el ifproforma_empresa es igual a proforma_empresa
                            if($ifcodigo_proforma == $codigo_proforma && $ifproforma_empresa == $proforma_empresa){
                                $ifChangeProforma = true;
                                $ifErrorProforma  = false;
                            }
                            //si no es igual guardo en un array
                            else{
                                $ifChangeProforma = false;
                                $ifErrorProforma  = true;
                            }
                        }
                    }
                ///============PROFORMA =======
                //===PROCESO===========
                //=====USAN Y LIQUIDAN=========================
                if($tipoProceso == '0'){
                    //SE QUITA ESTE CODIGO POR JORGE DICE QUE SI ENVIA A VENTA DIRECTA O LISTA NO HAY PROBLEMA
                    // //VENTA DIRECTA
                    // if($TipoVenta == 1){
                    //     $numeroProceso     = 0;
                    //     if(($ifid_periodo  == $periodo_id || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "") && ( $ifBc_Institucion == 0 || $ifBc_Institucion == $institucion )  && ($ifLeido == '1' || $ifLeido == '2') && $ifLiquidado == '1' && $ifBloqueado !=2 && ($venta_estado == 0  || $venta_estado == null || $venta_estado == "null")) $validacionCodigo = true;
                    //     else $validacionCodigo = false;
                    // }
                    // //VENTA LISTA
                    // if($TipoVenta == 2){
                    //     $numeroProceso     = 1;
                    //     if(($ifid_periodo  == $periodo_id || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "")  && ($ifLeido == '1' || $ifLeido == '2') && ($venta_estado == 0  || $venta_estado == null || $venta_estado == "null") && $ifLiquidado == '1' && $ifBloqueado !=2 && $ifventa_lista_institucion == '0') $validacionCodigo = true;
                    //     else $validacionCodigo = false;
                    // }
                    if(($ifid_periodo  == $periodo_id || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "") && $ifventa_estado == 0 && $ifLeido == '1' && $ifLiquidado == '1' && $ifBloqueado !=2  && $ifliquidado_regalado == '0' && $ifNotPaquete) {  $validacionCodigo = true; }
                    else { $validacionCodigo = false; }
                }
                //======REGALADO NO ENTRA A LA LIQUIDACION============
                if($tipoProceso == '1' || $tipoProceso == '5'){
                    $booleanRegalado = false;
                    if($tipoProceso == '5') { $booleanRegalado = ($ifLiquidado == '1' && $ifliquidado_regalado == '0' && $ifventa_estado == 0); }
                    //si no es el import de paquete valido que el codigo no tenga paquete
                    else{ $booleanRegalado                     = ($ifLiquidado == '1' && $ifliquidado_regalado == '0' && $ifNotPaquete && $ifventa_estado == 0);}
                    if($booleanRegalado) { $validacionCodigo = true; }
                    else  { $validacionCodigo = false; }
                }
                //======REGALADO Y BLOQUEADO(No usan y no liquidan)=============
                if($tipoProceso == '2' || $tipoProceso == '6'){
                    $user              = $usuarioQuemado;
                    $booleanRegaladoB = false;
                    //paquete
                    if($tipoProceso == '6') { $booleanRegaladoB = (($ifLiquidado !='0' && $ifLiquidado !='4') && $ifBloqueado !=2 && $ifliquidado_regalado == '0' && $ifventa_estado == 0); }
                    //si no es el import de paquete valido que el codigo no tenga paquete
                    else{ $booleanRegaladoB                     = (($ifLiquidado !='0' && $ifLiquidado !='4') && $ifBloqueado !=2 && $ifliquidado_regalado == '0' && $ifNotPaquete && $ifventa_estado == 0);}
                    if($booleanRegaladoB) { $validacionCodigo = true; }
                    else  { $validacionCodigo = false; }
                }
                //======BLOQUEADO(No usan y no liquidan)=============
                if($tipoProceso == '3' || $tipoProceso == '7'){
                    $user              = $usuarioQuemado;
                    $booleanBloqueado  = false;
                    //paquete
                    if($tipoProceso == '7') { $booleanBloqueado = (($ifLiquidado !='0' && $ifLiquidado !='4') && $ifBloqueado !=2 && $ifliquidado_regalado == '0' && $ifventa_estado == 0); }
                    //si no es el import de paquete valido que el codigo no tenga paquete
                    else{ $booleanBloqueado                     = (($ifLiquidado !='0' && $ifLiquidado !='4') && $ifBloqueado !=2 && $ifliquidado_regalado == '0' && $ifNotPaquete && $ifventa_estado == 0);}
                    if($booleanBloqueado) { $validacionCodigo = true; }
                    else  { $validacionCodigo = false; }
                }
                //=======CODIGO GUIA==================================
                if($tipoProceso == '4' || $tipoProceso == '8'){
                    $booleanGuia = false;
                    //paquete
                    if($tipoProceso == '8') { $booleanGuia = (($ifLiquidado == '1' || $ifLiquidado == '4') && $ifLeido == '1' && $ifBloqueado !=2 && $ifliquidado_regalado == '0' && $ifventa_estado == 0); }
                    //si no es el import de paquete valido que el codigo no tenga paquete
                    else{ $booleanGuia                     = (($ifLiquidado == '1' || $ifLiquidado == '4') && $ifLeido == '1' && $ifBloqueado !=2 && $ifliquidado_regalado == '0' && $ifNotPaquete && $ifventa_estado == 0);}
                    if($booleanGuia) { $validacionCodigo = true; }
                    else  { $validacionCodigo = false; }
                }
                //si todo sale bien
                if($validacionCodigo && $ifErrorProforma == false){
                    //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
                    $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                    $getcodigoUnion   = CodigosLibros::Where('codigo',$codigo_union)->get();
                    if($codigo_union != null || $codigo_union != ""){
                        //numero proceso => 0 = Usan y liquidan; 1 =  regalado; 2 = regalado y bloqueado ; 3 = bloqueado; 4 = guia; 5 = regalado; 6 = regalado y bloqueado; 7 = bloqueado; 8 = guia
                        $ingreso = $this->procesoGestionBodega($numeroProceso,$item->codigo,$codigo_union,$request,$getcodigoUnion,$factura,$ifChangeProforma,$datosProforma);
                        //si ingresa correctamente
                        if($ingreso == 1){
                            $porcentaje++;
                            //====CODIGO====
                            //ingresar en el historico codigo
                            $this->GuardarEnHistorico($user,$institucion,$periodo_id,$item->codigo,$usuario_editor,$comentario,$getcodigoPrimero,null);
                            //====CODIGO UNION=====
                            $this->GuardarEnHistorico($user,$institucion,$periodo_id,$codigo_union,$usuario_editor,$comentario,$getcodigoUnion,null);
                        }else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "Problema con el código union $codigo_union"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                    //ACTUALIZAR CODIGO SIN UNION
                    else{
                        $ingreso = $this->procesoGestionBodega($numeroProceso,$item->codigo,null,$request,null,$factura,$ifChangeProforma,$datosProforma);
                        if($ingreso == 1){
                            $porcentaje++;
                            //ingresar en el historico
                            $this->GuardarEnHistorico($user,$institucion,$periodo_id,$item->codigo,$usuario_editor,$comentario,$getcodigoPrimero,null);
                            $codigosSinCodigoUnion[] = $validar[0];
                        }
                        else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "0"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                }else{
                    //si el codigo no cumple con la validacion de la proforma lo guardo en un array
                    if($ifSetProforma == 1 && $ifErrorProforma){
                        $validar[0]->errorProforma      = 1;
                        //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                        $validar[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proforma, Proforma a ingresar: $codigo_proforma";
                        $codigosDemas[$contador]        = $validar[0];
                    }else{
                        $codigosDemas[$contador]        = $validar[0];
                    }
                    $contador++;
                }
            }else{
                $codigoNoExiste[$contadorNoExiste] =[
                    "codigo" => $item->codigo
                ];
                $contadorNoExiste++;
            }
        }
         $data = [
            "ingresados"            => $porcentaje,
            "codigosNoCambiados"    => $codigosNoCambiados,
            "codigosNoExisten"      => $codigoNoExiste,
            "codigoConProblemas"    => $codigosDemas,
            "codigosSinCodigoUnion" => $codigosSinCodigoUnion,
        ];
        return $data;
    }
    //API:POST/codigos/import/gestion/diagnostico
    public function importGestionDiagnostico(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //0=> venta directa ; 1=> regalado; 2 regalado y bloqueado, 3 = bloqueado, 4 = guia, 5 = regalado
        $tipoProceso                = $request->regalado;
        $miArrayDeObjetos           = json_decode($request->data_codigos);
        //variables
        $usuario_editor             = $request->id_usuario;
        $institucion_id             = $request->institucion_id;
        $comboSelected              = $request->comboSelected;
        if($request->ifSetCombo == '1'){
            $comentario                 = $comboSelected.'_'.$request->comentario;
        }else{
            $comentario                 = $request->comentario;
        }
        $periodo_id                 = $request->periodo_id;
        $contadorA                  = 0;
        $contadorD                  = 0;
        $getLongitud                = sizeof($miArrayDeObjetos);
        $longitud                   = $getLongitud/2;
        $TipoVenta                  = $request->venta_estado;
        $tipoBodega                 = $request->tipoBodega;
        $usuarioQuemado             = 45017;
        $facturaA                   = "";
        $proforma_empresa           = $request->proforma_empresa;
        $codigo_proforma            = $request->codigo_proforma;
        $ifSetProforma              = $request->ifSetProforma;
        $datosProforma              = [
            "proforma_empresa"      => $proforma_empresa,
            "codigo_proforma"       => $codigo_proforma,
        ];
        // Supongamos que tienes una colección vacía
        $codigosNoExisten   = collect();
        $codigoConProblemas = collect();
        for($i = 0; $i<$longitud; $i++){
            // Creamos un nuevo array para almacenar los objetos quitados
            $nuevoArray             = [];
            $codigoActivacion       = "";
            $codigoDiagnostico      = "";
            $validarA               = [];
            $validarD               = [];
            $old_valuesA            = [];
            $old_valuesD            = [];
            $ifChangeProformaA      = false;
            $ifErrorProformaA       = false;
            $ifChangeProformaD      = false;
            $ifErrorProformaD       = false;
            // Eliminamos los dos primeros objetos del array original y los agregamos al nuevo array
            $nuevoArray[]           = array_shift($miArrayDeObjetos);
            $nuevoArray[]           = array_shift($miArrayDeObjetos);
            //ACTIVACION - DIAGNOSTICO
            if($tipoBodega == 1){
                $codigoActivacion       = strtoupper($nuevoArray[0]->codigo);
                $codigoDiagnostico      = strtoupper($nuevoArray[1]->codigo);
            }
            //DIAGNOSTICO - ACTIVACION
            if($tipoBodega == 2){
                $codigoActivacion       = strtoupper($nuevoArray[0]->codigo);
                $codigoDiagnostico      = strtoupper($nuevoArray[1]->codigo);
            }
            //===CODIGO DE ACTIVACION====
            //validacion
            $validarA               = $this->getCodigos($codigoActivacion,0);
            $validarD               = $this->getCodigos($codigoDiagnostico,0);
            //======si ambos codigos existen========
            if(count($validarA) > 0 && count($validarD) > 0){
                //====VARIABLES DE CODIGOS===
                $booleanValidacionA = false;
                $booleanValidacionD = false;
                //====Activacion=====
                //validar si el codigo ya esta liquidado
                $ifLiquidadoA                   = $validarA[0]->estado_liquidacion;
                //validar si el codigo no este leido
                $ifBcEstadoA                    = $validarA[0]->bc_estado;
                //validar si el codigo no este liquidado
                $ifBloqueadoA                   = $validarA[0]->estado;
                //validar si tiene bc_institucion
                $ifBc_InstitucionA              = $validarA[0]->bc_institucion;
                //validar que el periodo del estudiante sea 0 o sea igual al que se envia
                $ifid_periodoA                  = $validarA[0]->id_periodo;
                //validar si el codigo tiene venta_estado
                $ifventa_estadoA                = $validarA[0]->venta_estado;
                //venta lista
                $ifventa_lista_institucionA     = $validarA[0]->venta_lista_institucion;
                //codigo de union
                $codigo_unionA                  = strtoupper($validarA[0]->codigo_union);
                $ifliquidado_regaladoA          = $validarA[0]->liquidado_regalado;
                //que el paquete sea vacio
                $codigo_paqueteA                = $validarA[0]->codigo_paquete;
                //validar si el codigo tiene proforma empresa proforma_empresa
                $ifproforma_empresaA             = $validarA[0]->proforma_empresa;
                //validar si el codigo tiene proforma codigo_proforma
                $ifcodigo_proformaA             = $validarA[0]->codigo_proforma;
                //======Diagnostico=====
                //validar si el codigo ya esta liquidado
                $ifLiquidadoD                   = $validarD[0]->estado_liquidacion;
                //validar si el codigo no este leido
                $ifBcEstadoD                    = $validarA[0]->bc_estado;
                //validar si el codigo no este liquidado
                $ifBloqueadoD                   = $validarD[0]->estado;
                //validar si tiene bc_institucion
                $ifBc_InstitucionD              = $validarD[0]->bc_institucion;
                //validar que el periodo del estudiante sea 0 o sea igual al que se envia
                $ifid_periodoD                  = $validarD[0]->id_periodo;
                //validar si el codigo tiene venta_estado
                $ifventa_estadoD                = $validarD[0]->venta_estado;
                //venta lista
                $ifventa_lista_institucionD     = $validarD[0]->venta_lista_institucion;
                //codigo de union
                $codigo_unionD                  = strtoupper($validarD[0]->codigo_union);
                $ifliquidado_regaladoD          = $validarD[0]->liquidado_regalado;
                //que el paquete sea vacio
                $codigo_paqueteD                = $validarD[0]->codigo_paquete;
                //validar si el codigo tiene proforma empresa proforma_empresa
                $ifproforma_empresaD            = $validarD[0]->proforma_empresa;
                //validar si el codigo tiene proforma codigo_proforma
                $ifcodigo_proformaD             = $validarD[0]->codigo_proforma;
                $old_valuesA                    = CodigosLibros::Where('codigo',$codigoActivacion)->get();
                $old_valuesD                    = CodigosLibros::findOrFail($codigoDiagnostico);
                //obtener la factura si no envian nada le dejo lo mismo
                $facturaA                       = $validarA[0]->factura;
                if($request->factura == null || $request->factura == "")   $factura = $facturaA;
                else  $factura = $request->factura;
                $ifNotPaqueteA  = false;
                $ifNotPaqueteD  = false;
                $ifNotPaqueteA  = (($codigo_paqueteA == null || $codigo_paqueteA == ""));
                $ifNotPaqueteD  = (($codigo_paqueteD == null || $codigo_paqueteD == ""));
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
                //===PROCESO===========
                //=====USAN Y LIQUIDAN=========================
                if($tipoProceso == '0'){
                    if(($ifid_periodoA  == $periodo_id || $ifid_periodoA == 0 ||  $ifid_periodoA == null  ||  $ifid_periodoA == "") && $ifventa_estadoA == 0  && ($ifBcEstadoA == '1')  && $ifLiquidadoA == '1' && $ifBloqueadoA !=2 &&  (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0')  && $ifNotPaqueteA && $ifErrorProformaA == false ){
                        if(($ifid_periodoD  == $periodo_id || $ifid_periodoD == 0 ||  $ifid_periodoD == null  ||  $ifid_periodoD == "") && $ifventa_estadoD == 0 && ($ifBcEstadoD == '1')  && $ifLiquidadoD == '1' && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0') && $ifNotPaqueteD && $ifErrorProformaD == false ){
                        //Ingresar Union a codigo de activacion
                        $codigoA     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoActivacion,$codigoDiagnostico,$request,$factura,null,$ifChangeProformaA,$datosProforma);
                        if($codigoA){ $contadorA++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null); }
                        //Ingresar Union a codigo de prueba diagnostico
                        $codigoB     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoDiagnostico,$codigoActivacion,$request,$factura,null,$ifChangeProformaD,$datosProforma);
                        if($codigoB){  $contadorD++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null); }
                        }else{
                            if($ifSetProforma == 1 && $ifErrorProformaD){
                                $validarD[0]->errorProforma      = 1;
                                //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                                $validarD[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaD, Proforma a ingresar: $codigo_proforma";
                                $codigoConProblemas->push($validarD);
                            }else{
                                $codigoConProblemas->push($validarD);
                            }
                        }
                    }else{
                        if($ifSetProforma == 1 && $ifErrorProformaA){
                            $validarA[0]->errorProforma      = 1;
                            //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                            $validarA[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaA, Proforma a ingresar: $codigo_proforma";
                            $codigoConProblemas->push($validarA);
                        }else{
                            $codigoConProblemas->push($validarA);
                        }
                    }
                }
                //======REGALADO NO ENTRA A LA LIQUIDACION============
                //numeroProceso => 1 regalado ; 2 regalado y bloqueado; 3 = bloqueado; 4 = guia; 5 = regalado sin institucion ; 6 = regalado y bloqueado sin institucion; gui
                if($tipoProceso == '1' || $tipoProceso == '5'){
                    if($tipoProceso == '5') {
                        $booleanValidacionA = ($ifLiquidadoA == '1' && $ifventa_estadoA == 0 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') );
                        $booleanValidacionD = ($ifLiquidadoD == '1' && $ifventa_estadoD == 0 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0') );
                    }
                    //si no es el import de paquete valido que el codigo no tenga paquete
                    else{
                        $booleanValidacionA = ($ifLiquidadoA == '1' && $ifventa_estadoA == 0 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') && $ifNotPaqueteA );
                        $booleanValidacionD = ($ifLiquidadoD == '1' && $ifventa_estadoD == 0 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0') && $ifNotPaqueteD);
                    }
                    if($booleanValidacionA && $ifErrorProformaA == false){
                        if($booleanValidacionD && $ifErrorProformaD == false){
                        //Cambiar a regalado a codigo de activacion
                        //(numeroProceso,codigo,$request)
                        $codigoA     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoActivacion,$codigoDiagnostico,$request,$factura,null,$ifChangeProformaA,$datosProforma);
                        if($codigoA){  $contadorA++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null); }
                        //Cambiar a regalado a codigo de diagnostico
                        $codigoB     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoDiagnostico,$codigoActivacion,$request,$factura,null,$ifChangeProformaD,$datosProforma);
                        if($codigoB){  $contadorD++; $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null); }
                        }else{
                            if($ifSetProforma == 1 && $ifErrorProformaD){
                                $validarD[0]->errorProforma      = 1;
                                //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                                $validarD[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaD, Proforma a ingresar: $codigo_proforma";
                                $codigoConProblemas->push($validarD);
                            }else{
                                $codigoConProblemas->push($validarD);
                            }
                        }
                    }else{
                        if($ifSetProforma == 1 && $ifErrorProformaA){
                            $validarA[0]->errorProforma      = 1;
                            //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                            $validarA[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaA, Proforma a ingresar: $codigo_proforma";
                            $codigoConProblemas->push($validarA);
                        }else{
                            $codigoConProblemas->push($validarA);
                        }
                    }
                }
                //======REGALADO Y BLOQUEADO(No usan y no liquidan)=============
                if($tipoProceso == '2' || $tipoProceso == '6'){
                    if($tipoProceso == '6') {
                        $booleanValidacionA = (($ifLiquidadoA !='0' && $ifLiquidadoA !='4')  && $ifventa_estadoA == 0 && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') );
                        $booleanValidacionD = (($ifLiquidadoD !='0' && $ifLiquidadoD !='4') && $ifventa_estadoD == 0 && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0') );
                    }
                    //si no es el import de paquete valido que el codigo no tenga paquete
                    else{
                        $booleanValidacionA = (( $ifLiquidadoA !='0' && $ifLiquidadoA !='4') && $ifventa_estadoA == 0 && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') && $ifNotPaqueteA);
                        $booleanValidacionD = (($ifLiquidadoD !='0' && $ifLiquidadoD !='4') &&  $ifventa_estadoD == 0 && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0') && $ifNotPaqueteD);
                    }
                    if($booleanValidacionA && $ifErrorProformaA == false){
                        if($booleanValidacionD && $ifErrorProformaD == false){
                        //Cambiar a regalado a codigo de activacion
                        //(numeroProceso,codigo,$request)
                        $codigoA     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoActivacion,$codigoDiagnostico,$request,$factura,null,$ifChangeProformaA,$datosProforma);
                        if($codigoA){  $contadorA++; $this->GuardarEnHistorico($usuarioQuemado,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null); }
                        //Cambiar a regalado a codigo de diagnostico
                        $codigoB     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoDiagnostico,$codigoActivacion,$request,$factura,null,$ifChangeProformaD,$datosProforma);
                        if($codigoB){  $contadorD++; $this->GuardarEnHistorico($usuarioQuemado,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null); }
                        }else{
                            if($ifSetProforma == 1 && $ifErrorProformaD){
                                $validarD[0]->errorProforma      = 1;
                                //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                                $validarD[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaD, Proforma a ingresar: $codigo_proforma";
                                $codigoConProblemas->push($validarD);
                            }else{
                                $codigoConProblemas->push($validarD);
                            }
                        }
                    }else{
                        if($ifSetProforma == 1 && $ifErrorProformaA){
                            $validarA[0]->errorProforma      = 1;
                            //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                            $validarA[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaA, Proforma a ingresar: $codigo_proforma";
                            $codigoConProblemas->push($validarA);
                        }else{
                            $codigoConProblemas->push($validarA);
                        }
                    }
                }
                //===== BLOQUEADO(No usan y no liquidan)=============
                if($tipoProceso == '3' || $tipoProceso == '7'){
                    if($tipoProceso == '7') {
                        $booleanValidacionA = (( $ifLiquidadoA !='0' && $ifLiquidadoA !='4')  && $ifventa_estadoA == 0 && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') );
                        $booleanValidacionD = (($ifLiquidadoD !='0' && $ifLiquidadoD !='4')   && $ifventa_estadoD == 0 && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  );
                    }
                    //si no es el import de paquete valido que el codigo no tenga paquete
                    else{
                        $booleanValidacionA = (( $ifLiquidadoA !='0' && $ifLiquidadoA !='4') && $ifventa_estadoA == 0 && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') && $ifNotPaqueteA );
                        $booleanValidacionD = (($ifLiquidadoD !='0' && $ifLiquidadoD !='4')  && $ifventa_estadoD == 0 && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion) || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0') && $ifNotPaqueteD );
                    }
                    if($booleanValidacionA && $ifErrorProformaA == false){
                        if($booleanValidacionD && $ifErrorProformaD == false){
                        //Cambiar a regalado a codigo de activacion
                        //(numeroProceso,codigo,$request)
                        $codigoA     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoActivacion,$codigoDiagnostico,$request,$factura,null,$ifChangeProformaA,$datosProforma);
                        if($codigoA){  $contadorA++; $this->GuardarEnHistorico($usuarioQuemado,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null); }
                        //Cambiar a regalado a codigo de diagnostico
                        $codigoB     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoDiagnostico,$codigoActivacion,$request,$factura,null,$ifChangeProformaD,$datosProforma);
                        if($codigoB){  $contadorD++; $this->GuardarEnHistorico($usuarioQuemado,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null); }
                        }else{
                            if($ifSetProforma == 1 && $ifErrorProformaD){
                                $validarD[0]->errorProforma      = 1;
                                //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                                $validarD[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaD, Proforma a ingresar: $codigo_proforma";
                                $codigoConProblemas->push($validarD);
                            }else{
                                $codigoConProblemas->push($validarD);
                            }
                        }
                    }else{
                        if($ifSetProforma == 1 && $ifErrorProformaA){
                            $validarA[0]->errorProforma      = 1;
                            //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                            $validarA[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaA, Proforma a ingresar: $codigo_proforma";
                            $codigoConProblemas->push($validarA);
                        }else{
                            $codigoConProblemas->push($validarA);
                        }
                    }
                }
                //==GUIA===
                if($tipoProceso == '4' || $tipoProceso == '8'){
                    if($tipoProceso == '8') {
                        $booleanValidacionA = (( $ifLiquidadoA   == '1' ||  $ifLiquidadoA == '4')   && $ifventa_estadoA == 0 && $ifBcEstadoA == '1' && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') );
                        $booleanValidacionD = (($ifLiquidadoD =='1' || $ifLiquidadoD == '4')        && $ifventa_estadoD == 0 && $ifBcEstadoD == '1' && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion)  || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  );
                    }
                    //si no es el import de paquete valido que el codigo no tenga paquete
                    else{
                        $booleanValidacionA = ((  $ifLiquidadoA   =='1' ||  $ifLiquidadoA == '4') && $ifventa_estadoA == 0 && $ifBcEstadoA == '1' && $ifBloqueadoA !=2 && (($codigo_unionA == $codigoDiagnostico) || ($codigo_unionA == null || $codigo_unionA == "" || $codigo_unionA == "0")) && ($ifliquidado_regaladoA == '0') && $ifNotPaqueteA);
                        $booleanValidacionD = (($ifLiquidadoD =='1' || $ifLiquidadoD == '4')      && $ifventa_estadoD == 0 && $ifBcEstadoD == '1' && $ifBloqueadoD !=2 && (($codigo_unionD == $codigoActivacion)  || ($codigo_unionD == null || $codigo_unionD == "" || $codigo_unionD == "0")) && ($ifliquidado_regaladoD == '0')  && $ifNotPaqueteD);
                    }
                    if($booleanValidacionA && $ifErrorProformaA == false){
                        if($booleanValidacionD && $ifErrorProformaD == false){
                        //Cambiar a regalado a codigo de activacion
                        //(numeroProceso,codigo,$request)
                        $codigoA     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoActivacion,$codigoDiagnostico,$request,$factura,null,$ifChangeProformaA,$datosProforma);
                        if($codigoA){  $contadorA++; $this->GuardarEnHistorico($usuarioQuemado,$institucion_id,$periodo_id,$codigoActivacion,$usuario_editor,$comentario,$old_valuesA,null); }
                        //Cambiar a regalado a codigo de diagnostico
                        $codigoB     = $this->codigosRepository->procesoUpdateGestionBodega($tipoProceso,$codigoDiagnostico,$codigoActivacion,$request,$factura,null,$ifChangeProformaD,$datosProforma);
                        if($codigoB){  $contadorD++; $this->GuardarEnHistorico($usuarioQuemado,$institucion_id,$periodo_id,$codigoDiagnostico,$usuario_editor,$comentario,$old_valuesD,null); }
                        }else{
                            if($ifSetProforma == 1 && $ifErrorProformaD){
                                $validarD[0]->errorProforma      = 1;
                                //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                                $validarD[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaD, Proforma a ingresar: $codigo_proforma";
                                $codigoConProblemas->push($validarD);
                            }else{
                                $codigoConProblemas->push($validarD);
                            }
                        }
                    }else{
                        if($ifSetProforma == 1 && $ifErrorProformaA){
                            $validarA[0]->errorProforma      = 1;
                            //agregar un mensaje error de proforma donde traigo la proforma del request y la proforma de codigos
                            $validarA[0]->mensajeErrorProforma = "Proforma del codigo: $ifcodigo_proformaA, Proforma a ingresar: $codigo_proforma";
                            $codigoConProblemas->push($validarA);
                        }else{
                            $codigoConProblemas->push($validarA);
                        }
                    }
                }
            }
            //Si uno de los 2 codigos no existen
            else{
                //si no existe el codigo de activacion
                if(count($validarA) == 0 && count($validarD) > 0){
                    $codigosNoExisten->push(['codigoNoExiste' => "activacion", 'codigoActivacion' => $codigoActivacion, 'codigoDiagnostico' => $codigoDiagnostico]);
                }
                //si no existe el codigo de diagnostico
                if(count($validarD) == 0 && count($validarA) > 0){
                    $codigosNoExisten->push(['codigoNoExiste' => "diagnostico",'codigoActivacion' => $codigoActivacion, 'codigoDiagnostico' => $codigoDiagnostico]);
                }
                //si no existe ambos
                if(count($validarA) == 0 && count($validarD) == 0){
                    $codigosNoExisten->push(['codigoNoExiste' => "ambos",      'codigoActivacion' => $codigoActivacion, 'codigoDiagnostico' => $codigoDiagnostico]);
                }
            }
        }
        if(count($codigoConProblemas) == 0){
            return [
                "CodigosDiagnosticoNoexisten"      => $codigosNoExisten->all(),
                "codigoConProblemas"               => [],
                "contadorA"                        => $contadorA,
                "contadorD"                        => $contadorD,
            ];
        }else{
            return [
                "CodigosDiagnosticoNoexisten"      => $codigosNoExisten->all(),
                "codigoConProblemas"               => array_merge(...$codigoConProblemas->all()),
                "contadorA"                        => $contadorA,
                "contadorD"                        => $contadorD,
            ];
        }
    }
     //api:get>>/codigos/revision
     public function revision(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos = json_decode($request->data_codigos);
        $datos=[];
        $data=[];
        $codigosNoExisten=[];
        $contador = 0;
        //conDevolucion => 1 = si; 0 = no;
        $conDevolucion = $request->conDevolucion;
        foreach($codigos as $key => $item){
            $consulta = $this->getCodigos($item->codigo,$conDevolucion);
            if(count($consulta) > 0){
               $datos[] = $consulta[0];
            }else{
                $codigosNoExisten[$contador] = [
                    "codigo" => $item->codigo
                ];
                $contador++;
            }
        }
        $data = [
            "codigosNoExisten" =>$codigosNoExisten,
            "informacion" => $datos
        ];
        return $data;

     }
    // public function getTipoVenta(Request $request){
    //     set_time_limit(6000000);
    //     ini_set('max_execution_time', 6000000);
    //     $tipoVenta = DB::SELECT("SELECT
    //     c.libro as book,c.serie,
    //     c.prueba_diagnostica,c.factura,c.codigo_union,
    //     IF(c.prueba_diagnostica ='1', 'Prueba de diagnóstico','Código normal') as tipoCodigo,
    //     c.contrato,c.porcentaje_descuento,
    //     c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,contador,
    //     c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,c.contrato,l.nombrelibro as libro,
    //     ib.nombreInstitucion as institucion_barras,
    //     pb.periodoescolar as periodo_barras,
    //     IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
    //     (case when (c.estado_liquidacion = '0') then 'liquidado'
    //         when (c.estado_liquidacion = '1') then 'sin liquidar'
    //         when (c.estado_liquidacion = '2') then 'codigo regalado'
    //         when (c.estado_liquidacion = '3') then 'codigo devuelto'
    //         when (c.estado_liquidacion = '4') then 'Código Guia'
    //     end) as liquidacion,
    //     (case when (c.bc_estado = '2') then 'codigo leido'
    //     when (c.bc_estado = '1') then 'codigo sin leer'
    //     end) as barrasEstado,
    //     (case when (c.venta_estado = '0') then ''
    //         when (c.venta_estado = '1') then 'Venta directa'
    //         when (c.venta_estado = '2') then 'Venta por lista'
    //     end) as ventaEstado,
    //     ib.nombreInstitucion as institucionBarra,
    //     pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
    //     c.codigo_proforma,c.proforma_empresa, c.combo,c.codigo_combo, ls.codigo_liquidacion
    //     FROM codigoslibros c
    //     LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
    //     LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
    //     LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
    //     LEFT JOIN libro l ON c.libro_idlibro = l.idlibro
    //     LEFT JOIN libros_series ls on ls.idLibro = l.idlibro
    //     WHERE (c.bc_institucion = '$request->institucion_id' OR venta_lista_institucion = '$request->institucion_id')
    //     AND c.bc_periodo = '$request->periodo_id'
    //     AND c.prueba_diagnostica = '0'
    //     AND c.estado_liquidacion <> '3'
    //     ");
    //     return $tipoVenta;
    // }
    public function getTipoVenta(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        // Obtener los detalles de tipoVenta
        $tipoVenta = DB::SELECT("SELECT
            c.libro as book, c.serie, c.prueba_diagnostica, c.factura, c.codigo_union,codigo_paquete,
            IF(c.prueba_diagnostica ='1', 'Prueba de diagnóstico','Código normal') as tipoCodigo,
            c.contrato, c.porcentaje_descuento, c.codigo, c.bc_estado, c.estado, c.estado_liquidacion, contador,
            c.venta_estado, c.bc_periodo, c.bc_institucion, c.idusuario, c.id_periodo, c.contrato, l.nombrelibro as libro,
            ib.nombreInstitucion as institucion_barras, pb.periodoescolar as periodo_barras,
            IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
            (case when (c.estado_liquidacion = '0') then 'liquidado'
                when (c.estado_liquidacion = '1') then 'sin liquidar'
                when (c.estado_liquidacion = '2') then 'codigo regalado'
                when (c.estado_liquidacion = '3') then 'codigo devuelto'
                when (c.estado_liquidacion = '4') then 'Código Guia'
            end) as liquidacion,
            (case when (c.bc_estado = '2') then 'codigo leido'
            when (c.bc_estado = '1') then 'codigo sin leer'
            end) as barrasEstado,
            (case when (c.venta_estado = '0') then ''
                when (c.venta_estado = '1') then 'Venta directa'
                when (c.venta_estado = '2') then 'Venta por lista'
            end) as ventaEstado,
            ib.nombreInstitucion as institucionBarra,
            pb.periodoescolar as periodo_barras, ivl.nombreInstitucion as InstitucionLista,
            c.codigo_proforma, c.proforma_empresa, c.combo, c.codigo_combo, ls.codigo_liquidacion
            FROM codigoslibros c
            LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
            LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
            LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
            LEFT JOIN libro l ON c.libro_idlibro = l.idlibro
            LEFT JOIN libros_series ls on ls.idLibro = l.idlibro
            WHERE (c.bc_institucion = '$request->institucion_id' OR c.venta_lista_institucion = '$request->institucion_id')
            AND c.bc_periodo = '$request->periodo_id'
            AND c.prueba_diagnostica = '0'
            AND c.estado_liquidacion <> '3'
        ");

        // Convertimos a una colección de Laravel
        $tipoVentaCollection = collect($tipoVenta);

        // Agrupamos por 'codigo_proforma' y 'proforma_empresa'
        $proformasGrouped = $tipoVentaCollection->groupBy(function ($item) {
            return $item->codigo_proforma . '-' . $item->proforma_empresa; // Agrupamos por codigo_proforma y proforma_empresa
        });

        // Ahora, procesamos cada grupo para contar cuántos códigos tiene y asignar el nombre de la empresa
        $proformas = $proformasGrouped->map(function ($group) {
            $codigoProforma = $group->first()->codigo_proforma;
            $proformaEmpresa = $group->first()->proforma_empresa;

            // Determinamos el nombre de la empresa
            $empresa = 'sin documentos';
            if ($proformaEmpresa == '1') {
                $empresa = 'Prolipa';
            } elseif ($proformaEmpresa == '3') {
                $empresa = 'Grupo Calmed';
            }

            // Contamos cuántos registros hay en este grupo
            return [
                'codigo_proforma' => $codigoProforma,
                'empresa' => $empresa,
                'cantidad_codigos' => $group->count(),
            ];
        });

        // Retornar los resultados en formato JSON
        return response()->json([
            'tipoVenta' => $tipoVentaCollection, // Los detalles originales
            'documentos' => $proformas->values(), // La agrupación y el conteo
        ]);
    }

    //api:post/codigos/bloquear
    public function bloquearCodigos(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $codigosNoCambiados     = [];
        $codigoLiquidados       = [];
        $codigoNoExiste         = [];
        $codigosSinCodigoUnion  = [];
        $porcentaje             = 0;
        $contador               = 0;
        $contadorNoExiste       = 0;
        $contadorNoCambiado     = 0;
        $id_usuario             = $request->id_usuario;
        $comentario             = $request->comentario;
        $traerPeriodo           = $request->periodo_id;
        $institucion            = $request->institucion_id;
        $usuarioQuemado         = 45017;
        foreach($codigos as $key => $item){
            //validar si el codigo existe
            $validar = $this->getCodigos($item->codigo,0);
            //valida que el codigo existe
            if(count($validar)>0){
                $ifLiquidado        = $validar[0]->estado_liquidacion;
                //validar si el codigo no este liquidado
                $ifBloqueado        = $validar[0]->estado;
                //validar si tiene codigo de union
                $codigo_union       = $validar[0]->codigo_union;
                //validar que el si es regalado no este liquidado
                $liquidado_regalado = $validar[0]->liquidado_regalado;
                if($ifLiquidado != '0' && $ifLiquidado != '4' && $ifBloqueado != 2 && $liquidado_regalado == '0'){
                    //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
                    $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                    $getcodigoUnion   = CodigosLibros::Where('codigo',$codigo_union)->get();
                    if($codigo_union != null || $codigo_union != ""){
                        //PASAR A LEIDO CON CODIGO DE UNION
                       $ingreso = $this->pasarABloqueados($item->codigo,$codigo_union,$request,$getcodigoUnion);
                       //si ingresa correctamente
                        if($ingreso == 1){
                            $porcentaje++;
                            //====CODIGO====
                            //ingresar en el historico codigo
                            $this->GuardarEnHistorico($usuarioQuemado,$institucion,$traerPeriodo,$item->codigo,$id_usuario,$comentario,$getcodigoPrimero,null);
                            //====CODIGO UNION=====
                            $this->GuardarEnHistorico($usuarioQuemado,$institucion,$traerPeriodo,$codigo_union,$id_usuario,$comentario,$getcodigoUnion,null);
                        }else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "Problema con el código union $codigo_union"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                    //ACTUALIZAR CODIGO SIN UNION
                    else{
                        $ingreso =  $this->pasarABloqueados($item->codigo,0,$request,null);
                        if($ingreso == 1){
                            $porcentaje++;
                            //ingresar en el historico
                            $this->GuardarEnHistorico($usuarioQuemado,$institucion,$traerPeriodo,$item->codigo,$id_usuario,$comentario,$getcodigoPrimero,null);
                            $codigosSinCodigoUnion[] = $validar[0];
                        }
                        else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "0"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                }else{
                    $codigoLiquidados[$contador] = $validar[0];
                    $contador++;
                }
            }else{
                $codigoNoExiste[$contadorNoExiste] =[
                    "codigo" => $item->codigo
                ];
                $contadorNoExiste++;
            }
        }
        return [
            "cambiados"             => $porcentaje,
            "codigosNoCambiados"    => $codigosNoCambiados,
            "codigoLiquidados"      => $codigoLiquidados,
            "codigoNoExiste"        => $codigoNoExiste,
            "codigosSinCodigoUnion" => $codigosSinCodigoUnion,
        ];
    }
    public function pasarABloqueados($codigo,$codigo_union,$request,$objectCodigoUnion){
        $withCodigoUnion = 1;
        $estadoIngreso   = 0;
        $unionCorrecto   = false;
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($codigo_union == '0') $withCodigoUnion = 0;
        else                     $withCodigoUnion = 1;
        $arraySave  = [
            'estado'             => 2,
        ];
        //si hay codigo de union lo actualizo
        if($withCodigoUnion == 1){
            //VALIDO SI NO EXISTE EL CODIGO DE UNION LO MANDO COMO ERROR
            if(count($objectCodigoUnion) == 0){
                //no se ingreso
                return 2;
            }
            //VALIDO QUE EL CODIGO UNION CUMPLA LA VALIDACION
            $ifLiquidado        = $objectCodigoUnion[0]->estado_liquidacion;
            //validar si el codigo no este liquidado
            $ifBloqueado        = $objectCodigoUnion[0]->estado;
            //validar que el si es regalado no este liquidado
            $liquidado_regalado = $objectCodigoUnion[0]->liquidado_regalado;
            if($ifLiquidado != '0' && $ifLiquidado != '4' && $ifBloqueado != 2 && $liquidado_regalado == '0') $unionCorrecto = true;
            else $unionCorrecto = false;
            if($unionCorrecto){
                $codigoU = DB::table('codigoslibros')
                ->where('codigo', '=', $codigo_union)
                ->where('estado','<>', '2')
                ->where('estado_liquidacion','<>', '0')
                ->update($arraySave);
                //si el codigo de union se actualiza actualizo el codigo
                if($codigoU){
                    //actualizar el primer codigo
                    $codigo = DB::table('codigoslibros')
                    ->where('codigo', '=', $codigo)
                    ->where('estado','<>', '2')
                    ->where('estado_liquidacion','<>', '0')
                    ->update($arraySave);
                }
            }else{
                //no se ingreso
                return 2;
            }
        }else{
            //actualizar el primer codigo
            $codigo = DB::table('codigoslibros')
            ->where('codigo', '=', $codigo)
            ->where('estado','<>', '2')
            ->where('estado_liquidacion','<>', '0')
            ->update($arraySave);
        }
        //con codigo union
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($withCodigoUnion == 1){
            if($codigo && $codigoU)  $estadoIngreso = 1;
            else                     $estadoIngreso = 2;
        }
        //si no existe el codigo de union
        if($withCodigoUnion == 0){
            if($codigo)              $estadoIngreso = 1;
        }
        return $estadoIngreso;
    }
    //API:POST/codigos/eliminar
    public function eliminar(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $codigosNoCambiados     = [];
        $codigoNoExiste         = [];
        $codigosConUsuario      = [];
        $codigosSinCodigoUnion  = [];
        $porcentaje             = 0;
        $contador               = 0;
        $contadorNoCambiado     = 0;
        $traerPeriodo           = $request->periodo_id;
        $id_usuario             = $request->id_usuario;
        $comentario             = $request->observacion;
        foreach($codigos as $key => $item){
            //validar si el codigo existe
            $validar = $this->getCodigos($item->codigo,0);
            //valida que el codigo existe
            if(count($validar)>0){
                //validar si el codigo tiene usuario y si ya esta liquidado
                $usuario            = $validar[0]->idusuario;
                $liquidado          = $validar[0]->estado_liquidacion;
                $liquidado_regalado = $validar[0]->liquidado_regalado;
                //validar si tiene codigo de union
                $codigo_union       = $validar[0]->codigo_union;
                if(($usuario == 0  || $usuario == null || $usuario == "null") && ($liquidado == '1' || $liquidado == '2' || $liquidado == '3') && $liquidado_regalado == '0'){
                    //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
                    $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                    $getcodigoUnion   = CodigosLibros::Where('codigo',$codigo_union)->get();
                    if($codigo_union != null || $codigo_union != ""){
                        //PASAR A LEIDO CON CODIGO DE UNION
                        $eliminar = $this->pasarAEliminado($item->codigo,$codigo_union,$request,$getcodigoUnion);
                        //si elimina correctamente correctamente
                        if($eliminar == 1){
                            $porcentaje++;
                            //====CODIGO====
                            //ingresar en el historico codigo
                            $this->GuardarEnHistorico(0,0,$traerPeriodo,$item->codigo,$id_usuario,$comentario,$getcodigoPrimero,null);
                            //====CODIGO UNION=====
                            $this->GuardarEnHistorico(0,0,$traerPeriodo,$codigo_union,$id_usuario,$comentario,$getcodigoUnion,null);
                        }else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "Problema con el código union $codigo_union"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                    //ACTUALIZAR CODIGO SIN UNION
                    else{
                        $ingreso =  $this->pasarAEliminado($item->codigo,0,$request,null);
                        if($ingreso == 1){
                            $porcentaje++;
                            //ingresar en el historico
                            $this->GuardarEnHistorico(0,0,$traerPeriodo,$item->codigo,$id_usuario,$comentario,$getcodigoPrimero,null);
                            $codigosSinCodigoUnion[] = $validar[0];
                        }
                        else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "0"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                }else{
                    $codigosConUsuario[$contador] = $validar[0];
                    $contador++;
                 }
            }else{
                $codigoNoExiste[$key] =[
                    "codigo" => $item->codigo
                ];
            }
        }
        return [
            "eliminados"                => $porcentaje,
            "codigosNoCambiados"        => $codigosNoCambiados,
            "codigosConUsuario"         => $codigosConUsuario,
            "codigoNoExiste"            => $codigoNoExiste,
            "codigosSinCodigoUnion"     => $codigosSinCodigoUnion
        ];
    }
    public function pasarAEliminado($codigo,$codigo_union,$request,$objectCodigoUnion){
        $withCodigoUnion = 1;
        $estadoIngreso   = 0;
        $unionCorrecto   = false;
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($codigo_union == '0') $withCodigoUnion = 0;
        else                     $withCodigoUnion = 1;
        //si hay codigo de union lo actualizo
        if($withCodigoUnion == 1){
            //VALIDO SI NO EXISTE EL CODIGO DE UNION LO MANDO COMO ERROR
            if(count($objectCodigoUnion) == 0){
                //no se ingreso
                return 2;
            }
            //VALIDO QUE EL CODIGO UNION CUMPLA LA VALIDACION
            //que el codigo no tenga estudiante ni este liquidado
            $usuario            = $objectCodigoUnion[0]->idusuario;
            $liquidado          = $objectCodigoUnion[0]->estado_liquidacion;
            $liquidado_regalado = $objectCodigoUnion[0]->liquidado_regalado;
            if( ($usuario == 0  || $usuario == null || $usuario == "null") && ($liquidado == '1' || $liquidado == '2' || $liquidado == '3') && $liquidado_regalado == '0') $unionCorrecto = true;
            else $unionCorrecto = false;
            if($unionCorrecto){
                $codigoU = DB::table('codigoslibros')->where('codigo', '=', $codigo_union)->delete();
                //si el codigo de union se elimina paso a eliminar el codigo
                if($codigoU){
                    $codigoU = DB::table('codigoslibros')->where('codigo', '=', $codigo)->delete();
                }
            }else{
                //no se ingreso
                return 2;
            }
        }else{
            $codigo = DB::table('codigoslibros')->where('codigo', '=', $codigo)->delete();
        }
        //con codigo union
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($withCodigoUnion == 1){
            if($codigo && $codigoU)  $estadoIngreso = 1;
            else                     $estadoIngreso = 2;
        }
        //si no existe el codigo de union
        if($withCodigoUnion == 0){
            if($codigo)              $estadoIngreso = 1;
        }
        return $estadoIngreso;
    }
    public function bodegaEliminar(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos = json_decode($request->data_codigos);
        $datos=[];
        $codigosNoCambiados=[];
        $codigoNoExiste = [];
        $codigosConLibro = [];
        $porcentaje = 0;
        $contador = 0;
        foreach($codigos as $key => $item){
            //validar si el codigo existe
            $validar = DB::SELECT("SELECT c.codigo, c.libro_idlibro
            FROM bodega_codigos c
            WHERE c.codigo = '$item->codigo'
            ORDER BY id DESC
            ");
            //valida que el codigo existe
            if(count($validar)>0){
                //validar si el codigo tiene un libro y no es un codigo original
                $libro_id = $validar[0]->libro_idlibro;
                if($libro_id == 0){
                    $codigo = DB::table('bodega_codigos')
                    ->where('codigo', '=', $item->codigo)
                    ->where('libro_idlibro', '=', '0')
                    ->delete();
                    if($codigo){
                        $porcentaje++;
                        //ingresar en el historico
                        $historico = new HistoricoCodigos();
                        $historico->id_usuario   =  $request->id_usuario;
                        $historico->codigo_libro   =  $item->codigo;
                        $historico->usuario_editor = '';
                        $historico->idInstitucion = $request->id_usuario;
                        $historico->observacion = 'Se elimino el codigo de la bodega de codigos';
                        $historico->save();
                    }else{
                        $codigosNoCambiados[$key] =[
                            "codigo" => $item->codigo
                        ];
                    }
                }else{
                    $codigosConLibro[$contador] = [
                    "codigo" => $item->codigo,
                    ];
                    $contador++;
                }
            }else{
                $codigoNoExiste[$key] =[
                "codigo" => $item->codigo
                ];
            }
        }
        return [
            "eliminados" => $porcentaje,
            "codigosNoCambiados" => $codigosNoCambiados,
            "codigosConLibro" => $codigosConLibro,
            "codigoNoExiste" => $codigoNoExiste
        ];
     }

     //api:post//codigos/import/periodo
     public function changePeriodo(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos = json_decode($request->data_codigos);
        $datos=[];
        $codigosNoCambiados=[];
        $codigosConUsuario =[];
        $codigoNoExiste = [];
        $porcentaje = 0;
        $contador = 0;
        foreach($codigos as $key => $item){
                //validar si el codigo existe
                $validar = $this->getCodigos($item->codigo,0);
                //valida que el codigo existe
                if(count($validar)>0){
                    //validar si el codigo tiene usuario
                    $usuario = $validar[0]->idusuario;
                    if($usuario == 0  || $usuario == null || $usuario == "null"){
                        $codigo = DB::table('codigoslibros')
                        ->where('codigo', '=', $item->codigo)
                        ->update([
                           'idusuario' =>  $request->usuario_id,
                           'id_periodo' => $request->periodo_id
                        ]);
                        if($codigo){
                            $porcentaje++;
                            //ingresar en el historico
                            $historico = new HistoricoCodigos();
                            $historico->id_usuario   =  $request->usuario_id;
                            $historico->codigo_libro   =  $item->codigo;
                            $historico->usuario_editor = '';
                            $historico->idInstitucion = $request->usuario_editor;
                            $historico->id_periodo = $request->periodo_id;
                            $historico->observacion = 'Se cambio el periodo del codigo';
                            $historico->save();
                        }else{
                            $codigosNoCambiados[$key] = [
                                "codigo" => $item->codigo
                            ];
                        }
                    }else{
                        $codigosConUsuario[$contador] = [
                            "codigo" => $item->codigo,
                            "prueba_diagnostica" => $validar[0]->prueba_diagnostica,
                            "tipoCodigo"         => $validar[0]->tipoCodigo,
                            "barrasEstado" => $validar[0]->barrasEstado,
                            "codigoEstado" => $validar[0]->codigoEstado,
                            "liquidacion" => $validar[0]->liquidacion,
                            "ventaEstado" => $validar[0]->ventaEstado,
                            "idusuario" => $validar[0]->idusuario,
                            "estudiante" => $validar[0]->estudiante,
                            "nombreInstitucion" => $validar[0]->nombreInstitucion,
                            "institucionBarra" => $validar[0]->institucionBarra,
                            "periodo" => $validar[0]->periodo,
                            "periodo_barras" => $validar[0]->periodo_barras,
                            "cedula" => $validar[0]->cedula,
                            "email" => $validar[0]->email,
                            "estado_liquidacion" => $validar[0]->estado_liquidacion,
                            "estado" => $validar[0]->estado,
                            "contador" => $validar[0]->contador,
                            "porcentaje_descuento" => $validar[0]->porcentaje_descuento,
                            "factura"               => $validar[0]->factura,
                            "codigo_union"          => $validar[0]->codigo_union,
                        ];
                        $contador++;
                    }
                }else{
                    $codigoNoExiste[$key] =[
                        "codigo" => $item->codigo
                    ];
                }
        }
        return [
            "cambiados" => $porcentaje,
            "codigosNoCambiados" => $codigosNoCambiados,
            "codigosConUsuario" => $codigosConUsuario,
            "codigoNoExiste" => $codigoNoExiste
        ];
     }
     //api:post//codigos/import/periodo/varios
    public function changePeriodoVarios(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos = json_decode($request->data_codigos);
        $datos=[];
        $codigosNoCambiados=[];
        $codigosConUsuario =[];
        $codigoNoExiste = [];
        $porcentaje = 0;
        $contador =0;
        foreach($codigos as $key => $item){
            //validar si el codigo existe
            $validar = $this->getCodigos($item->codigo,0);
            //valida que el codigo existe
            if(count($validar)>0){
                //validar si el codigo tiene usuario
                $usuario = $validar[0]->idusuario;
                if($usuario == 0  || $usuario == null || $usuario == "null"){
                    $codigo = DB::table('codigoslibros')
                    ->where('codigo', '=', $item->codigo)
                    ->update([
                        'bc_periodo' => $item->id_periodo
                    ]);
                    if($codigo){
                        $porcentaje++;
                        //ingresar en el historico
                        $historico                  = new HistoricoCodigos();
                        $historico->id_usuario      =  0;
                        $historico->codigo_libro    =  $item->codigo;
                        $historico->usuario_editor  = '';
                        $historico->idInstitucion   = $request->id_usuario;
                        $historico->id_periodo      = $item->id_periodo;
                        $historico->observacion     = $item->comentario;
                        $historico->save();
                    }else{
                        $codigosNoCambiados[$key] =[
                            "codigo" => $item->codigo
                        ];
                    }
                }else{
                    $codigosConUsuario[$contador] = [
                        "codigo"             => $item->codigo,
                        "prueba_diagnostica" => $validar[0]->prueba_diagnostica,
                        "tipoCodigo"         => $validar[0]->tipoCodigo,
                        "barrasEstado"       => $validar[0]->barrasEstado,
                        "codigoEstado"       => $validar[0]->codigoEstado,
                        "liquidacion"        => $validar[0]->liquidacion,
                        "ventaEstado"        => $validar[0]->ventaEstado,
                        "idusuario"          => $validar[0]->idusuario,
                        "estudiante"         => $validar[0]->estudiante,
                        "nombreInstitucion"  => $validar[0]->nombreInstitucion,
                        "institucionBarra"   => $validar[0]->institucionBarra,
                        "periodo"            => $validar[0]->periodo,
                        "periodo_barras"     => $validar[0]->periodo_barras,
                        "cedula"             => $validar[0]->cedula,
                        "email"              => $validar[0]->email,
                        "estado_liquidacion" => $validar[0]->estado_liquidacion,
                        "estado"             => $validar[0]->estado,
                        "contador"           => $validar[0]->contador,
                        "porcentaje_descuento" => $validar[0]->porcentaje_descuento,
                        "factura"            => $validar[0]->factura,
                        "codigo_union"          => $validar[0]->codigo_union,
                    ];
                    $contador++;
                }
            }else{
                $codigoNoExiste[$key] =[
                    "codigo" => $item->codigo,
                ];
            }
        }
        return [
            "cambiados" => $porcentaje,
            "codigosNoCambiados" => $codigosNoCambiados,
            "codigosConUsuario" => $codigosConUsuario,
            "codigoNoExiste" => $codigoNoExiste
        ];
    }
    //api:GET/codigo/devoluciones/{codigo}
    public function verDevoluciones($codigo){
        $getReturns = DB::SELECT("SELECT d.*,
        i.nombreInstitucion,p.periodoescolar,
        CONCAT(u.nombres, ' ', u.apellidos) AS editor
        FROM codigos_devolucion d
        LEFT JOIN institucion i ON d.institucion_id 	= i.idInstitucion
        LEFT JOIN periodoescolar p ON d.periodo_id      = p.idperiodoescolar
        LEFT JOIN usuario u ON d.usuario_editor         = u.idusuario
        WHERE d.codigo = '$codigo'
        ORDER BY id DESC
        ");
        return $getReturns;
    }
     //api:post/codigos/bodega/devolver
    public function devolucionBodega(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos = json_decode($request->data_codigos);
        $codigosNoCambiados     = [];
        $codigosConLiquidacion  = [];
        $codigoNoExiste         = [];
        $codigosSinCodigoUnion  = [];
        $porcentaje             = 0;
        $contador               = 0;
        $institucion_id         = $request->institucion_id;
        $fecha                  = date('Y-m-d H:i:s');
        $contadorNoCambiado     = 0;
        $contadorNoexiste       = 0;
        $mensaje                = $request->observacion;
        $setContrato            = null;
        $verificacion_liquidada = null;
        $tipo_importacion       = $request->tipo_importacion;
        try{
            DB::beginTransaction();
            if($request->codigo){
                $resultado = $this->devolucionIndividualBodega($request->codigo,$request->id_usuario,$request->cliente,$request->institucion_id,$request->periodo_id,$request->observacion,$mensaje,$request);
                //si ingresa correctamente commit
                DB::commit();
                return $resultado;
            }else{
                foreach($codigos as $key => $item){
                    //validar si el codigo existe
                    $validar                        = $this->getCodigos($item->codigo,0);
                    $ingreso                        = 0;
                    $ifsetProforma                  = 0;
                    $ifErrorProforma                = 0;
                    $messageProforma                = "";
                    $datosProforma                  = [];
                    $ingreso                        = 0;
                    $messageIngreso                 = "";
                    //valida que el codigo existe
                    if(count($validar)>0){
                        $codigo_union               = $validar[0]->codigo_union;
                        //validar si el codigo se encuentra liquidado
                        $ifLiquidado                = $validar[0]->estado_liquidacion;
                        //contrato
                        $ifContrato                 = $validar[0]->contrato;
                        //numero de verificacion
                        $ifVerificacion             = $validar[0]->verificacion;
                        //para ver si es codigo regalado no este liquidado
                        $ifliquidado_regalado       = $validar[0]->liquidado_regalado;
                        //para ver la empresa de la proforma
                        $ifproforma_empresa         = $validar[0]->proforma_empresa;
                        //para ver el estado devuelto proforma
                        $ifdevuelto_proforma        = $validar[0]->devuelto_proforma;
                        ///para ver el codigo de proforma
                        $ifcodigo_proforma          = $validar[0]->codigo_proforma;
                        //codigo de liquidacion
                        $ifcodigo_liquidacion       = $validar[0]->codigo_liquidacion;
                        //ADMINISTRADOR (SI PUEDE DEVOLVER AUNQUE LA INSTITUCION SEA DIFERENTE)
                        $EstatusProceso             = false;
                        if($request->dLiquidado ==  '1'){
                            $setContrato            = $ifContrato;
                            $verificacion_liquidada = $ifVerificacion;
                            //VALIDACION AUNQUE ESTE LIQUIDADO
                            if($ifLiquidado == '0' || $ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4') $EstatusProceso = true;
                        }else{
                            //VALIDACION QUE NO SEA LIQUIDADO
                            if(($ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4') && $ifliquidado_regalado == '0' ) $EstatusProceso = true;
                        }
                        //jorge dice que se quita esta validacion
                        // if(($ifLiquidado == '1' || $ifLiquidado == '2') && ($ifBc_Institucion == $institucion_id || $ifventa_lista_institucion == $institucion_id )) $EstatusProceso = true;
                        //SI CUMPLE LA VALIDACION
                        if($EstatusProceso){
                            //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
                            $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                            if($codigo_union != null || $codigo_union != ""){
                                //devolucion
                                $getcodigoUnion     = CodigosLibros::Where('codigo',$codigo_union)->get();
                                $getIngreso         =  $this->codigosRepository->updateDevolucion($item->codigo,$codigo_union,$getcodigoUnion,$request,$ifsetProforma,$ifcodigo_liquidacion,$ifproforma_empresa,$ifcodigo_proforma,$tipo_importacion);
                                $ingreso            = $getIngreso["ingreso"];
                                $messageIngreso     = $getIngreso["messageIngreso"];
                                //si ingresa correctamente
                                if($ingreso == 1){
                                    $newValusPrimero = CodigosLibros::where('codigo',$item->codigo)->get();
                                    $newValuesUnion      = CodigosLibros::where('codigo',$codigo_union)->get();
                                    $porcentaje++;
                                    //====CODIGO====
                                    //ingresar en el historico codigo
                                    $this->GuardarEnHistorico(0,$request->institucion_id,$request->periodo_id,$item->codigo,$request->id_usuario,$mensaje,$getcodigoPrimero,$newValusPrimero,$setContrato,$verificacion_liquidada);
                                    //ingresar a la tabla de devolucion
                                    $this->codigosRepository->saveDevolucion($item->codigo,$request->cliente,$request->institucion_id,$request->periodo_id,$fecha,$request->observacion,$request->id_usuario);
                                    //====CODIGO UNION=====
                                    $this->GuardarEnHistorico(0,$request->institucion_id,$request->periodo_id,$codigo_union,$request->id_usuario,$mensaje,$getcodigoUnion,$newValuesUnion,$setContrato,$verificacion_liquidada);
                                    $this->codigosRepository->saveDevolucion($codigo_union,$request->cliente,$request->institucion_id,$request->periodo_id,$fecha,$request->observacion,$request->id_usuario);
                                }
                                else{
                                    $codigosNoCambiados[$contadorNoCambiado] = [
                                        "codigo"        => $item->codigo,
                                        "mensaje"       => $messageIngreso
                                    ];
                                    $contadorNoCambiado++;
                                }
                            }
                            //ACTUALIZAR CODIGO SIN UNION
                            else{
                                $getIngreso         = $this->codigosRepository->updateDevolucion($item->codigo,0,null,$request,$ifsetProforma,$ifcodigo_liquidacion,$ifproforma_empresa,$ifcodigo_proforma,$tipo_importacion);
                                $ingreso            = $getIngreso["ingreso"];
                                $messageIngreso     = $getIngreso["messageIngreso"];
                                if($ingreso == 1){
                                    $newValuesPrimero    = CodigosLibros::where('codigo',$item->codigo)->get();
                                    $porcentaje++;
                                    //ingresar en el historico
                                    $this->GuardarEnHistorico(0,$request->institucion_id,$request->periodo_id,$item->codigo,$request->id_usuario,$mensaje,$getcodigoPrimero,$newValuesPrimero,$setContrato,$verificacion_liquidada);
                                    //ingresar a la tabla de devolucion
                                    $this->codigosRepository->saveDevolucion($item->codigo,$request->cliente,$request->institucion_id,$request->periodo_id,$fecha,$request->observacion,$request->id_usuario);
                                    $codigosSinCodigoUnion[] = $validar[0];
                                }
                                else{
                                    $codigosNoCambiados[$contadorNoCambiado] = [
                                        "codigo"        => $item->codigo,
                                        "mensaje"       => $messageIngreso
                                    ];
                                    $contadorNoCambiado++;
                                }
                            }
                        }
                        //SI NO CUMPLE LA VALIDACION
                        else{
                            //admin
                            if($request->admin == "yes"){
                                if($ifErrorProforma == 1)               { $validar[0]->errorProforma = 1; $validar[0]->mensajeErrorProforma   = $messageProforma; }
                                $codigosConLiquidacion[]                = $validar[0];
                                $contador++;
                            }
                            //bodega
                            else{
                                $mensaje_personalizado                  = "";
                                //mensaje personalizado front
                                if($ifLiquidado == 0)                   { $mensaje_personalizado  = "Código liquidado"; }
                                if($ifLiquidado == 3)                   { $mensaje_personalizado  = "Código  ya devuelto"; }
                                if($ifliquidado_regalado == '1')        { $mensaje_personalizado  = "Código Regalado liquidado"; }
                                //error proforma
                                if($ifErrorProforma == 1)               { $mensaje_personalizado  = $messageProforma; }
                                //add array to front
                                $validar[0]->mensaje                    = $mensaje_personalizado;
                                $codigosConLiquidacion[]                = $validar[0];
                                $contador++;
                            }
                        }
                    }else{
                        $codigoNoExiste[$contadorNoexiste] = [ "codigo" => $item->codigo ];
                        $contadorNoexiste++;
                    }
                }
                DB::commit();
                return [
                    "cambiados"             => $porcentaje,
                    "codigosNoCambiados"    => $codigosNoCambiados,
                    "codigosConLiquidacion" => $codigosConLiquidacion,
                    "codigoNoExiste"        => $codigoNoExiste,
                    "codigosSinCodigoUnion" => $codigosSinCodigoUnion,
                ];
            }

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                "status"  => 0,
                'message' => $e->getMessage()
            ], 200);
        }
    }
    //api:post/codigos/bodega/devolver/version2
    // public function devolucionBodega2(Request $request){
    //     set_time_limit(6000000);
    //     ini_set('max_execution_time', 6000000);
    //     $codigos                = json_decode($request->data_codigos);
    //     $codigosNoCambiados     = [];
    //     $codigosConLiquidacion  = [];
    //     $codigoNoExiste         = [];
    //     $codigosSinCodigoUnion  = [];
    //     $arrayOldValues         = [];
    //     $arrayNewValues         = [];
    //     $porcentaje             = 0;
    //     $contadorNoCambiado     = 0;
    //     $contadorNoexiste       = 0;
    //     $mensaje                = $request->observacion;
    //     $id_devolucion          = $request->id_devolucion;
    //     $id_usuario             = $request->id_usuario;
    //     $setContrato            = null;
    //     $verificacion_liquidada = null;
    //     try{
    //         //si el estado de devolucion es 2
    //         $devolucion = CodigosLibrosDevolucionHeader::find($id_devolucion);
    //         if($devolucion->estado == 2){
    //             return [
    //                 "status"  => 0,
    //                 "message" => "La devolucion ya se encuentra finalizada"
    //             ];
    //         }
    //         $periodo_id             = $devolucion->periodo_id;
    //         DB::beginTransaction();
    //             ///===PROCESO===
    //             foreach($codigos as $key => $item){
    //                 //validar si el codigo existe
    //                 $validar                        = $this->getCodigos($item->codigo,0);
    //                 $ingreso                        = 0;
    //                 $ifsetProforma                  = 0;
    //                 $ingreso                        = 0;
    //                 $messageIngreso                 = "";
    //                 $id_cliente                     = $item->id_cliente;
    //                 $bc_periodo                     = $item->id_periodo;
    //                 $tipo_importacion               = $item->tipo_importacion;
    //                 $cantidadLibroDescontar         = 1;
    //                 //valida que el codigo existe
    //                 if(count($validar)>0){

    //                     $codigo_union               = $validar[0]->codigo_union;
    //                     //if_codigo_combo
    //                     $if_codigo_combo            = $validar[0]->codigo_combo;
    //                     //validar si el codigo se encuentra liquidado
    //                     $ifLiquidado                = $validar[0]->estado_liquidacion;
    //                     //contrato
    //                     $ifContrato                 = $validar[0]->contrato;
    //                     //numero de verificacion
    //                     $ifVerificacion             = $validar[0]->verificacion;
    //                     //codigo de combo
    //                     $ifCombo                    = $item->combo;
    //                     //codigo de factura
    //                     $ifFactura                  = $validar[0]->factura;
    //                     //tipo_venta
    //                     $ifTipoVenta                = $validar[0]->venta_estado;
    //                     //codigo_paquete
    //                     $ifcodigo_paquete           = $validar[0]->codigo_paquete;
    //                     //para ver si es codigo regalado no este liquidado
    //                     $ifliquidado_regalado       = $validar[0]->liquidado_regalado;
    //                     //para ver la empresa de la proforma
    //                     $ifproforma_empresa         = $validar[0]->proforma_empresa;
    //                     //para ver el estado devuelto proforma
    //                     $ifdevuelto_proforma        = $validar[0]->devuelto_proforma;
    //                     ///para ver el codigo de proforma
    //                     $ifcodigo_proforma          = $validar[0]->codigo_proforma;
    //                     //codigo de liquidacion
    //                     $ifcodigo_liquidacion       = $validar[0]->codigo_liquidacion;
    //                     //ADMINISTRADOR (SI PUEDE DEVOLVER AUNQUE LA INSTITUCION SEA DIFERENTE)
    //                     $EstatusProceso             = false;
    //                     if($request->ifLiquidado == '0' || $ifliquidado_regalado == '1'){
    //                         $setContrato            = $ifContrato;
    //                         $verificacion_liquidada = $ifVerificacion;
    //                         //VALIDACION AUNQUE ESTE LIQUIDADO
    //                         $EstatusProceso = true;
    //                     }else{
    //                         //VALIDACION QUE NO SEA LIQUIDADO
    //                         $EstatusProceso = true;
    //                     }

    //                     //====PROFORMA============================================
    //                     //tipo_importacion => 1 => importacion codigos; 2 => importacion paquetes, 3 => importacion combos
    //                     //ifdevuelto_proforma => 0 => nada; 1 => devuelta antes del enviar el pedido; 2 => enviada despues de enviar al pedido
    //                     if($ifproforma_empresa > 0 && $ifdevuelto_proforma != 1 && ($tipo_importacion == 1 || $tipo_importacion == 2)){
    //                         //validacion de documento
    //                         $datos = (object) [
    //                             "pro_codigo"        => $ifcodigo_liquidacion,
    //                             "id_institucion"    => $id_cliente,
    //                             "id_periodo"        => $periodo_id,
    //                             "id_empresa"        => $ifproforma_empresa
    //                         ];
    //                         //VALIDAR QUE EL DOCUMENTO ACTUAL ESTE DISPONIBLE
    //                         $getDisponibilidadDocumento = $this->devolucionRepository->getFacturaAvailable($datos, $cantidadLibroDescontar, [],$ifcodigo_proforma,$item->codigo);
    //                         if($getDisponibilidadDocumento){
    //                             $mensaje           = $request->observacion." - Se devolvio a $ifcodigo_proforma";
    //                             $ifsetProforma     = 1;
    //                             $EstatusProceso    = true;
    //                         }else{
    //                             $messageIngreso    = "No se pudo devolver al documento $ifcodigo_proforma porque no se encuentra disponible.";
    //                             $ifsetProforma     = 0;
    //                             $EstatusProceso    = false;
    //                         }
    //                     }
    //                     //jorge dice que se quita esta validacion
    //                     // if(($ifLiquidado == '1' || $ifLiquidado == '2') && ($ifBc_Institucion == $institucion_id || $ifventa_lista_institucion == $institucion_id )) $EstatusProceso = true;
    //                     //SI CUMPLE LA VALIDACION
    //                     if($EstatusProceso){
    //                         //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
    //                         $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
    //                         if($codigo_union != null || $codigo_union != ""){
    //                             //devolucion
    //                             $getcodigoUnion     = CodigosLibros::Where('codigo',$codigo_union)->get();
    //                             $datos = (object) [
    //                                 "codigo"             => $item->codigo,
    //                                 "codigo_union"       => $codigo_union,
    //                                 "ifsetProforma"      => $ifsetProforma,
    //                                 "codigo_liquidacion" => $ifcodigo_liquidacion,
    //                                 "proforma_empresa"   => $ifproforma_empresa,
    //                                 "codigo_proforma"    => $ifcodigo_proforma,
    //                                 "tipo_importacion"   => $tipo_importacion
    //                             ];
    //                             $getIngreso         =  $this->codigosRepository->updateDevolucionDocumento($datos);
    //                             $ingreso            = $getIngreso["ingreso"];
    //                             $messageIngreso     = $getIngreso["message"];
    //                             /// Guardar en historico de STOCK en el array
    //                             if(count($getIngreso["oldValues"]) > 0){
    //                                 $arrayOldValues[]   = $getIngreso["oldValues"];
    //                                 $arrayNewValues[]   = $getIngreso["newValues"];
    //                             }
    //                             //si ingresa correctamente
    //                             if($ingreso == 1){
    //                                 //newValues
    //                                 $newValuesPrimero    = CodigosLibros::where('codigo',$item->codigo)->get();
    //                                 $newValuesUnion      = CodigosLibros::where('codigo',$codigo_union)->get();
    //                                 if($tipo_importacion == 1){
    //                                     if($if_codigo_combo){
    //                                         $mensaje = $mensaje." - Se quito el combo $if_codigo_combo";
    //                                     }
    //                                     if($ifcodigo_paquete){
    //                                         $mensaje = $mensaje." - Se quito el paquete $ifcodigo_paquete";
    //                                     }
    //                                 }
    //                                 $porcentaje++;
    //                                 //====CODIGO====
    //                                 //ingresar en el historico codigo
    //                                 $this->GuardarEnHistorico(0,$id_cliente,$periodo_id,$item->codigo,$request->id_usuario,$mensaje,$getcodigoPrimero,$newValuesPrimero,$setContrato,$verificacion_liquidada);
    //                                 //ingresar a la tabla de devolucion
    //                                 //====CODIGO UNION=====
    //                                 $this->GuardarEnHistorico(0,$id_cliente,$periodo_id,$codigo_union,$request->id_usuario,$mensaje,$getcodigoUnion,$newValuesUnion,$setContrato,$verificacion_liquidada);
    //                                 //GUARDAR EN LA TABLA DE DEVOLUCION CODIGO LIBRO
    //                                 $this->tr_updateDevolucionHijos($item->codigo,1,$id_devolucion);
    //                                 // //GUARDAR EN LA TABLA DE DEVOLUCION CODIGO UNION
    //                                 $this->tr_updateDevolucionHijos($codigo_union,1,$id_devolucion);
    //                             }
    //                             else{
    //                                 $codigosNoCambiados[$contadorNoCambiado] = [
    //                                     "codigo"        => $item->codigo,
    //                                     "mensaje"       => $messageIngreso
    //                                 ];
    //                                 $contadorNoCambiado++;
    //                             }
    //                         }
    //                         //ACTUALIZAR CODIGO SIN UNION
    //                         else{
    //                             $datos = (object) [
    //                                 "codigo"             => $item->codigo,
    //                                 "codigo_union"       => 0,
    //                                 "ifsetProforma"      => $ifsetProforma,
    //                                 "codigo_liquidacion" => $ifcodigo_liquidacion,
    //                                 "proforma_empresa"   => $ifproforma_empresa,
    //                                 "codigo_proforma"    => $ifcodigo_proforma,
    //                                 "tipo_importacion"   => $tipo_importacion
    //                             ];
    //                             $getIngreso         =  $this->codigosRepository->updateDevolucionDocumento($datos);
    //                             $ingreso            = $getIngreso["ingreso"];
    //                             $messageIngreso     = $getIngreso["message"];
    //                             /// Guardar en historico de STOCK en el array
    //                             if(count($getIngreso["oldValues"]) > 0){
    //                                 $arrayOldValues[]   = $getIngreso["oldValues"];
    //                                 $arrayNewValues[]   = $getIngreso["newValues"];
    //                             }
    //                             if($ingreso == 1){
    //                                 $newValuesPrimero    = CodigosLibros::where('codigo',$item->codigo)->get();
    //                                 if($tipo_importacion == 1){
    //                                     if($if_codigo_combo){
    //                                         $mensaje = $mensaje." - Se quito el combo $if_codigo_combo";
    //                                     }
    //                                     if($ifcodigo_paquete){
    //                                         $mensaje = $mensaje." - Se quito el paquete $ifcodigo_paquete";
    //                                     }
    //                                 }
    //                                 $porcentaje++;
    //                                 //ingresar en el historico
    //                                 $this->GuardarEnHistorico(0,$id_cliente,$periodo_id,$item->codigo,$request->id_usuario,$mensaje,$getcodigoPrimero,$newValuesPrimero,$setContrato,$verificacion_liquidada);
    //                                 //GUARDAR EN LA TABLA DE DEVOLUCION CODIGO LIBRO
    //                                 $this->tr_updateDevolucionHijos($item->codigo,1,$id_devolucion);                                    $codigosSinCodigoUnion[] = $validar[0];
    //                             }
    //                             else{
    //                                 $codigosNoCambiados[$contadorNoCambiado] = [
    //                                     "codigo"        => $item->codigo,
    //                                     "mensaje"       => $messageIngreso
    //                                 ];
    //                                 $contadorNoCambiado++;
    //                             }
    //                         }
    //                     }
    //                     //SI NO CUMPLE LA VALIDACION
    //                     else{
    //                         $codigosNoCambiados[$contadorNoCambiado] = [
    //                             "codigo"        => $item->codigo,
    //                             "mensaje"       => $messageIngreso
    //                         ];
    //                         $contadorNoCambiado++;
    //                     }
    //                 }else{
    //                     $codigoNoExiste[$contadorNoexiste] = [ "codigo" => $item->codigo ];
    //                     $contadorNoexiste++;
    //                 }
    //             }
    //             //si cambiados es mayor a cero actualizar el estado de la devolucion
    //             if($porcentaje > 0){
    //                 //actualizar total venta
    //                 //hijos
    //                 $totalValor    = 0;
    //                 $totalCantidad = 0;
    //                 $hijos = DB::SELECT("SELECT  h.pro_codigo,COUNT(h.pro_codigo) AS cantidad, h.precio
    //                     FROM codigoslibros_devolucion_son h
    //                     WHERE h.codigoslibros_devolucion_id = '$id_devolucion'
    //                     AND h.estado <> '0'
    //                     GROUP BY h.pro_codigo, h.precio
    //                 ");
    //                 //sumar total cantidad
    //                 foreach($hijos as $key => $item){
    //                     $totalCantidad += $item->cantidad;
    //                     //multiplicar precio por cantidad
    //                     $totalValor += $item->cantidad * $item->precio;
    //                 }
    //                 //fin actualizar total venta
    //                 $devolucion                         = CodigosLibrosDevolucionHeader::find($id_devolucion);
    //                 $devolucion->estado                 = 1;
    //                 $devolucion->user_created_revisado  = $request->id_usuario;
    //                 $devolucion->fecha_revisado         = date('Y-m-d H:i:s');
    //                 $devolucion->ven_total              = $totalValor;
    //                 $devolucion->total_items            = $totalCantidad;
    //                 $devolucion->save();
    //                 //actualizar historico stock
    //                 // Historico
    //                 return $arrayOldValues;
    //                 if(count($arrayOldValues) > 0){
    //                     _14ProductoStockHistorico::insert([
    //                         'psh_old_values'                        => json_encode($arrayOldValues),
    //                         'psh_new_values'                        => json_encode($arrayNewValues),
    //                         'psh_tipo'                              => 9,
    //                         'id_codigoslibros_devolucion_header'    => $id_devolucion,
    //                         'user_created'                          => $id_usuario,
    //                         'created_at'                            => now(),
    //                         'updated_at'                            => now(),
    //                     ]);
    //                 }
    //                 //enviar notificacion push
    //                 // Registrar notificación
    //                 $formData = (Object)[
    //                     'nombre'        => 'Devolución Bodega',
    //                     'descripcion'   => null,
    //                     'tipo'          => '5',
    //                     'user_created'  => $id_usuario,
    //                     'id_periodo'    => $periodo_id,
    //                     'id_padre'      => $id_devolucion,
    //                 ];
    //                 $color = '#7a4af1';
    //                 $notificacion = $this->verificacionRepository->save_notificacion($formData,$color);
    //                 $channel = 'admin.notifications_verificaciones';
    //                 $event = 'NewNotification';
    //                 $data = [
    //                     'message' => 'Nueva notificación',
    //                 ];
    //                 // notificacion en pusher
    //                 $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);
    //             }
    //             // DB::commit();
    //             return [
    //                 "cambiados"             => $porcentaje,
    //                 "codigosNoCambiados"    => $codigosNoCambiados,
    //                 "codigosConLiquidacion" => $codigosConLiquidacion,
    //                 "codigoNoExiste"        => $codigoNoExiste,
    //                 "codigosSinCodigoUnion" => $codigosSinCodigoUnion,
    //             ];
    //     }catch(\Exception $e){
    //         DB::rollBack();
    //         return response()->json([
    //             "status"  => 0,
    //             'message' => $e->getMessage()
    //         ], 200);
    //     }
    // }
public function devolucionBodega2(Request $request){
    set_time_limit(6000000);
    ini_set('max_execution_time', 6000000);
    $codigos                = json_decode($request->data_codigos);
    $codigosNoCambiados     = [];
    $codigosConLiquidacion  = [];
    $codigoNoExiste         = [];
    $codigosSinCodigoUnion  = [];
    $arrayOldValues         = [];
    $arrayNewValues         = [];
    $porcentaje             = 0;
    $contadorNoCambiado     = 0;
    $contadorNoexiste       = 0;
    $mensaje                = $request->observacion;
    $id_devolucion          = $request->id_devolucion;
    $id_usuario             = $request->id_usuario;
    $setContrato            = null;
    $verificacion_liquidada = null;
    try{
        //si el estado de devolucion es 2
        $devolucion = CodigosLibrosDevolucionHeader::find($id_devolucion);
        if($devolucion->estado == 2){
            return [
                "status"  => 0,
                "message" => "La devolucion ya se encuentra finalizada"
            ];
        }
        $periodo_id             = $devolucion->periodo_id;
        DB::beginTransaction();
            ///===PROCESO===
            foreach($codigos as $key => $item){
                //validar si el codigo existe
                $validar                        = $this->getCodigos($item->codigo,0);
                $ingreso                        = 0;
                $ifsetProforma                  = 0;
                $ingreso                        = 0;
                $messageIngreso                 = "";
                $id_cliente                     = $item->id_cliente;
                $bc_periodo                     = $item->id_periodo;
                $tipo_importacion               = $item->tipo_importacion;
                $cantidadLibroDescontar         = 1;
                //valida que el codigo existe
                if(count($validar)>0){
                    $codigo_union               = $validar[0]->codigo_union;
                    //if_codigo_combo
                    $if_codigo_combo            = $validar[0]->codigo_combo;
                    //validar si el codigo se encuentra liquidado
                    $ifLiquidado                = $validar[0]->estado_liquidacion;
                    //contrato
                    $ifContrato                 = $validar[0]->contrato;
                    //numero de verificacion
                    $ifVerificacion             = $validar[0]->verificacion;
                    //codigo de combo
                    $ifCombo                    = $item->combo;
                    //codigo de factura
                    $ifFactura                  = $validar[0]->factura;
                    //tipo_venta
                    $ifTipoVenta                = $validar[0]->venta_estado;
                    //codigo_paquete
                    $ifcodigo_paquete           = $validar[0]->codigo_paquete;
                    //para ver si es codigo regalado no este liquidado
                    $ifliquidado_regalado       = $validar[0]->liquidado_regalado;
                    //para ver la empresa de la proforma
                    $ifproforma_empresa         = $validar[0]->proforma_empresa;
                    //para ver el estado devuelto proforma
                    $ifdevuelto_proforma        = $validar[0]->devuelto_proforma;
                    ///para ver el codigo de proforma
                    $ifcodigo_proforma          = $validar[0]->codigo_proforma;
                    //codigo de liquidacion
                    $ifcodigo_liquidacion       = $validar[0]->codigo_liquidacion;
                    //ADMINISTRADOR (SI PUEDE DEVOLVER AUNQUE LA INSTITUCION SEA DIFERENTE)
                    $EstatusProceso             = false;
                    if($request->ifLiquidado == '0' || $ifliquidado_regalado == '1'){
                        $setContrato            = $ifContrato;
                        $verificacion_liquidada = $ifVerificacion;
                        //VALIDACION AUNQUE ESTE LIQUIDADO
                        $EstatusProceso = true;
                    }else{
                        //VALIDACION QUE NO SEA LIQUIDADO
                        $EstatusProceso = true;
                    }

                    //====PROFORMA============================================
                    //tipo_importacion => 1 => importacion codigos; 2 => importacion paquetes, 3 => importacion combos
                    //ifdevuelto_proforma => 0 => nada; 1 => devuelta antes del enviar el pedido; 2 => enviada despues de enviar al pedido
                    if($ifproforma_empresa > 0 && $ifdevuelto_proforma != 1 && ($tipo_importacion == 1 || $tipo_importacion == 2)){
                        //validacion de documento
                        $datos = (object) [
                            "pro_codigo"        => $ifcodigo_liquidacion,
                            "id_institucion"    => $id_cliente,
                            "id_periodo"        => $periodo_id,
                            "id_empresa"        => $ifproforma_empresa
                        ];
                        //VALIDAR QUE EL DOCUMENTO ACTUAL ESTE DISPONIBLE
                        $getDisponibilidadDocumento = $this->devolucionRepository->getFacturaAvailable($datos, $cantidadLibroDescontar, [],$ifcodigo_proforma,$item->codigo);
                        if($getDisponibilidadDocumento){
                            $mensaje           = $request->observacion." - Se devolvio a $ifcodigo_proforma";
                            $ifsetProforma     = 1;
                            $EstatusProceso    = true;
                        }else{
                            $messageIngreso    = "No se pudo devolver al documento $ifcodigo_proforma porque no se encuentra disponible.";
                            $ifsetProforma     = 0;
                            $EstatusProceso    = false;
                        }
                    }
                    //jorge dice que se quita esta validacion
                    // if(($ifLiquidado == '1' || $ifLiquidado == '2') && ($ifBc_Institucion == $institucion_id || $ifventa_lista_institucion == $institucion_id )) $EstatusProceso = true;
                    //SI CUMPLE LA VALIDACION
                    if($EstatusProceso){
                        //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
                        $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                        if($codigo_union != null || $codigo_union != ""){
                            //devolucion
                            $getcodigoUnion     = CodigosLibros::Where('codigo',$codigo_union)->get();
                            $datos = (object) [
                                "codigo"             => $item->codigo,
                                "codigo_union"       => $codigo_union,
                                "ifsetProforma"      => $ifsetProforma,
                                "codigo_liquidacion" => $ifcodigo_liquidacion,
                                "proforma_empresa"   => $ifproforma_empresa,
                                "codigo_proforma"    => $ifcodigo_proforma,
                                "tipo_importacion"   => $tipo_importacion
                            ];
                            $getIngreso         =  $this->codigosRepository->updateDevolucionDocumento($datos);
                            $ingreso            = $getIngreso["ingreso"];
                            $messageIngreso     = $getIngreso["message"];
                            /// Guardar en historico de STOCK en el array
                            if(count($getIngreso["oldValues"]) > 0){
                                $arrayOldValues[]   = $getIngreso["oldValues"];
                                $arrayNewValues[]   = $getIngreso["newValues"];
                            }
                            //si ingresa correctamente
                            if($ingreso == 1){
                                //newValues
                                $newValuesPrimero    = CodigosLibros::where('codigo',$item->codigo)->get();
                                $newValuesUnion      = CodigosLibros::where('codigo',$codigo_union)->get();
                                if($tipo_importacion == 1){
                                    if($if_codigo_combo){
                                        $mensaje = $mensaje." - Se quito el combo $if_codigo_combo";
                                    }
                                    if($ifcodigo_paquete){
                                        $mensaje = $mensaje." - Se quito el paquete $ifcodigo_paquete";
                                    }
                                }
                                $porcentaje++;
                                //====CODIGO====
                                //ingresar en el historico codigo
                                $this->GuardarEnHistorico(0,$id_cliente,$periodo_id,$item->codigo,$request->id_usuario,$mensaje,$getcodigoPrimero,$newValuesPrimero,$setContrato,$verificacion_liquidada);
                                //ingresar a la tabla de devolucion
                                //====CODIGO UNION=====
                                $this->GuardarEnHistorico(0,$id_cliente,$periodo_id,$codigo_union,$request->id_usuario,$mensaje,$getcodigoUnion,$newValuesUnion,$setContrato,$verificacion_liquidada);
                                //GUARDAR EN LA TABLA DE DEVOLUCION CODIGO LIBRO
                                $this->tr_updateDevolucionHijos($item->codigo,1,$id_devolucion);
                                // //GUARDAR EN LA TABLA DE DEVOLUCION CODIGO UNION
                                $this->tr_updateDevolucionHijos($codigo_union,1,$id_devolucion);
                            }
                            else{
                                $codigosNoCambiados[$contadorNoCambiado] = [
                                    "codigo"        => $item->codigo,
                                    "mensaje"       => $messageIngreso
                                ];
                                $contadorNoCambiado++;
                            }
                        }
                        //ACTUALIZAR CODIGO SIN UNION
                        else{
                            $datos = (object) [
                                "codigo"             => $item->codigo,
                                "codigo_union"       => 0,
                                "ifsetProforma"      => $ifsetProforma,
                                "codigo_liquidacion" => $ifcodigo_liquidacion,
                                "proforma_empresa"   => $ifproforma_empresa,
                                "codigo_proforma"    => $ifcodigo_proforma,
                                "tipo_importacion"   => $tipo_importacion
                            ];
                            $getIngreso         =  $this->codigosRepository->updateDevolucionDocumento($datos);
                            $ingreso            = $getIngreso["ingreso"];
                            $messageIngreso     = $getIngreso["message"];
                            /// Guardar en historico de STOCK en el array
                            if(count($getIngreso["oldValues"]) > 0){
                                $arrayOldValues[]   = $getIngreso["oldValues"];
                                $arrayNewValues[]   = $getIngreso["newValues"];
                            }
                            if($ingreso == 1){
                                $newValuesPrimero    = CodigosLibros::where('codigo',$item->codigo)->get();
                                if($tipo_importacion == 1){
                                    if($if_codigo_combo){
                                        $mensaje = $mensaje." - Se quito el combo $if_codigo_combo";
                                    }
                                    if($ifcodigo_paquete){
                                        $mensaje = $mensaje." - Se quito el paquete $ifcodigo_paquete";
                                    }
                                }
                                $porcentaje++;
                                //ingresar en el historico
                                $this->GuardarEnHistorico(0,$id_cliente,$periodo_id,$item->codigo,$request->id_usuario,$mensaje,$getcodigoPrimero,$newValuesPrimero,$setContrato,$verificacion_liquidada);
                                //GUARDAR EN LA TABLA DE DEVOLUCION CODIGO LIBRO
                                $this->tr_updateDevolucionHijos($item->codigo,1,$id_devolucion);                                    $codigosSinCodigoUnion[] = $validar[0];
                            }
                            else{
                                $codigosNoCambiados[$contadorNoCambiado] = [
                                    "codigo"        => $item->codigo,
                                    "mensaje"       => $messageIngreso
                                ];
                                $contadorNoCambiado++;
                            }
                        }
                    }
                    //SI NO CUMPLE LA VALIDACION
                    else{
                        $codigosNoCambiados[$contadorNoCambiado] = [
                            "codigo"        => $item->codigo,
                            "mensaje"       => $messageIngreso
                        ];
                        $contadorNoCambiado++;
                    }
                }else{
                    $codigoNoExiste[$contadorNoexiste] = [ "codigo" => $item->codigo ];
                    $contadorNoexiste++;
                }
            }
            //si cambiados es mayor a cero actualizar el estado de la devolucion
            if($porcentaje > 0){
                //actualizar total venta
                //hijos
                $totalValor    = 0;
                $totalCantidad = 0;
                $hijos = DB::SELECT("SELECT  h.pro_codigo,COUNT(h.pro_codigo) AS cantidad, h.precio
                    FROM codigoslibros_devolucion_son h
                    WHERE h.codigoslibros_devolucion_id = '$id_devolucion'
                    AND h.estado <> '0'
                    GROUP BY h.pro_codigo, h.precio
                ");
                //sumar total cantidad
                foreach($hijos as $key => $item){
                    $totalCantidad += $item->cantidad;
                    //multiplicar precio por cantidad
                    $totalValor += $item->cantidad * $item->precio;
                }
                //fin actualizar total venta
                $devolucion                         = CodigosLibrosDevolucionHeader::find($id_devolucion);
                $devolucion->estado                 = 1;
                $devolucion->user_created_revisado  = $request->id_usuario;
                $devolucion->fecha_revisado         = date('Y-m-d H:i:s');
                $devolucion->ven_total              = $totalValor;
                $devolucion->total_items            = $totalCantidad;
                $devolucion->save();
                //actualizar historico stock
                // Agrupar oldValues por pro_codigo, tomando el último valor
                $groupedOldValues = $this->agruparPorCodigoPrimerValor($arrayOldValues);
                $groupedNewValues = $this->agruparPorCodigo($arrayNewValues);
                // Historico
                if(count($groupedOldValues) > 0){
                    _14ProductoStockHistorico::insert([
                        'psh_old_values'                        => json_encode($groupedOldValues),
                        'psh_new_values'                        => json_encode($groupedNewValues),
                        'psh_tipo'                              => 9,
                        'id_codigoslibros_devolucion_header'    => $id_devolucion,
                        'user_created'                          => $id_usuario,
                        'created_at'                            => now(),
                        'updated_at'                            => now(),
                    ]);
                }
                //enviar notificacion push
                // Registrar notificación
                $formData = (Object)[
                    'nombre'        => 'Devolución Bodega',
                    'descripcion'   => null,
                    'tipo'          => '5',
                    'user_created'  => $id_usuario,
                    'id_periodo'    => $periodo_id,
                    'id_padre'      => $id_devolucion,
                ];
                $color = '#7a4af1';
                $notificacion = $this->verificacionRepository->save_notificacion($formData,$color);
                $channel = 'admin.notifications_verificaciones';
                $event = 'NewNotification';
                $data = [
                    'message' => 'Nueva notificación',
                ];
                // notificacion en pusher
                $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);
            }
            DB::commit();
            return [
                "cambiados"             => $porcentaje,
                "codigosNoCambiados"    => $codigosNoCambiados,
                "codigosConLiquidacion" => $codigosConLiquidacion,
                "codigoNoExiste"        => $codigoNoExiste,
                "codigosSinCodigoUnion" => $codigosSinCodigoUnion,
            ];
    }catch(\Exception $e){
        DB::rollBack();
        return response()->json([
            "status"  => 0,
            'message' => $e->getMessage()
        ], 200);
    }
}

/**
 * Agrupa arrayOldValues por pro_codigo, tomando el último valor de cada campo
 * @param array $arrayOldValues
 * @return array
 */
private function agruparPorCodigo($arrayOldValues) {
    $grouped = [];

    foreach ($arrayOldValues as $item) {
        $codigo = $item['pro_codigo'];

        // Reemplazamos el registro completo con el último valor para este pro_codigo
        $grouped[$codigo] = [
            'pro_codigo' => $codigo,
            'pro_reservar' => $item['pro_reservar'],
            'pro_stock' => $item['pro_stock'],
            'pro_stockCalmed' => $item['pro_stockCalmed'],
            'pro_deposito' => $item['pro_deposito'],
            'pro_depositoCalmed' => $item['pro_depositoCalmed']
        ];
    }

    // Convertimos el array asociativo a un array indexado
    return array_values($grouped);
}
private function agruparPorCodigoPrimerValor($arrayOldValues) {
    $grouped = [];

    foreach ($arrayOldValues as $item) {
        $codigo = $item['pro_codigo'];

        // Solo asignamos el registro si no existe ya para este pro_codigo
        if (!isset($grouped[$codigo])) {
            $grouped[$codigo] = [
                'pro_codigo' => $codigo,
                'pro_reservar' => $item['pro_reservar'],
                'pro_stock' => $item['pro_stock'],
                'pro_stockCalmed' => $item['pro_stockCalmed'],
                'pro_deposito' => $item['pro_deposito'],
                'pro_depositoCalmed' => $item['pro_depositoCalmed']
            ];
        }
    }

    // Convertimos el array asociativo a un array indexado
    return array_values($grouped);
}

    //api:post/codigos/devolucionCrearDocumentos
    public function devolucionCrearDocumentos(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $codigosNoCambiados     = [];
        $codigosConLiquidacion  = [];
        $codigoNoExiste         = [];
        $codigosSinCodigoUnion  = [];
        $porcentaje             = 0;
        $contador               = 0;
        $periodo_id             = $request->periodo_id;
        $cantidadCajas          = $request->cantidadCajas;
        $cantidadPaquetes       = $request->cantidadPaquetes;
        $contadorNoCambiado     = 0;
        $contadorNoexiste       = 0;
        $mensaje                = $request->observacion;
        $setContrato            = null;
        $verificacion_liquidada = null;
        $tipo_importacion       = $request->tipo_importacion;
        $id_usuario             = $request->id_usuario;
        //la empresa es 1 porque ya no se hace por empresa
        $iniciales              = $request->iniciales;
        $ifNuevoDocumento       = $request->ifNuevoDocumento;
        $documento_cliente      = $request->documento_cliente;
        $documento_clienteId    = $request->documento_clienteId;
        $institucion_id_select  = $request->institucion_id_select;
        try{
            //iniciales
            if($iniciales == null || $iniciales == "" || $iniciales == "null"){ return ["status" => "0", "message" => "No se pudo obtener la iniciales del usuario"]; }
            $codigo_contrato                = Periodo::where('idperiodoescolar', $periodo_id)->value('codigo_contrato');
            if (!$codigo_contrato)          { return ["status" => "0", "message" => "No se pudo obtener el codigo del contrato"]; }
            DB::beginTransaction();
            //si es nuevo documento
            if($ifNuevoDocumento == 1){
                $secuencia                      = 0;
                $getSecuencia                   = f_tipo_documento::obtenerSecuencia("DEVOLUCION-CODIGO");
                if(!$getSecuencia)              { return ["status" => "0", "message" => "No se pudo obtener la secuencia de guias"]; }
                $letra                          = $getSecuencia->tdo_letra;
                $secuencia                      = $getSecuencia->tdo_secuencial_Prolipa;
                $secuencia                      = $secuencia + 1;
                $format_id_pedido               = f_tipo_documento::formatSecuencia($secuencia);
                $codigo_ven                     = $letra.'-'. $codigo_contrato .'-'. $iniciales.'-'. $format_id_pedido;
                //ACTUALIZAR LA SECUENCIA
                $tipoDocumento                  = f_tipo_documento::find(14); // Encuentra el registro con tdo_id = 14
                if ($tipoDocumento) {
                    $tipoDocumento->tdo_secuencial_Prolipa = $secuencia; // Actualiza la propiedad
                    $tipoDocumento->save(); // Guarda los cambios
                    $devolucion                         = new CodigosLibrosDevolucionHeader();
                    $devolucion->codigo_devolucion      = $codigo_ven;
                    //0 porque el cliente ahora se guarda en cada codigo individual
                    $devolucion->id_cliente             = $institucion_id_select;
                    $devolucion->observacion            = $mensaje;
                    $devolucion->user_created           = $id_usuario;
                    $devolucion->periodo_id             = $periodo_id;
                    $devolucion->cantidadCajas          = $cantidadCajas;
                    $devolucion->cantidadPaquetes       = $cantidadPaquetes;
                    //0 porque la empresa se guarda en cada codigo individual
                    $devolucion->id_empresa             = 0;
                    $devolucion->save();
                    $id_devolucion                      = $devolucion->id;
                }else {
                    return ["status" => "0", "message" => "No se pudo obtener la secuencia de guias"];
                }
            }
            //si es documento existente
            else{
                $codigo_ven    = $documento_cliente;
                $id_devolucion = $documento_clienteId;
                //si el codigo_ven es nulo o vacio
                if($codigo_ven == null || $codigo_ven == "" || $codigo_ven == "null"){
                    return ["status" => "0", "message" => "No se pudo obtener el codigo del documento"];
                }
                $devolucion                         = CodigosLibrosDevolucionHeader::findOrFail($id_devolucion);
                $devolucion->observacion            = $mensaje;
                $devolucion->user_created           = $request->id_usuario;
                $devolucion->periodo_id             = $periodo_id;
                $devolucion->cantidadCajas          = $cantidadCajas;
                $devolucion->cantidadPaquetes       = $cantidadPaquetes;
                $devolucion->save();
            }
            //codigos de documentos devolucion por cliente
            ///===PROCESO===
            foreach($codigos as $key => $item){
                $codigo                 = $item->codigo;
                $combo                  = $item->combo;
                $codigo_liquidacion     = $item->codigo_liquidacion;
                $proforma_empresa       = $item->proforma_empresa;
                $codigo_proforma        = $item->codigo_proforma;
                $cantidadLibroDescontar = 1;
                $ifBusquedaDocumento    = 0;
                $getidtipodoc           = 1;
                //si no hay combo asignar un documento
                if(($combo == null || $combo == "") && $proforma_empresa > 0){
                    //validacion de documento
                    $datos = (object) [
                        "pro_codigo"        => $codigo_liquidacion,
                        "id_institucion"    => $institucion_id_select,
                        "id_periodo"        => $periodo_id,
                        "id_empresa"        => $proforma_empresa
                    ];

                    //VALIDAR QUE EL DOCUMENTO ACTUAL ESTE DISPONIBLE
                    $getDisponibilidadDocumento = $this->devolucionRepository->getFacturaAvailable($datos, $cantidadLibroDescontar, [],$codigo_proforma);
                    if($getDisponibilidadDocumento){
                        $getidtipodoc = $getDisponibilidadDocumento->idtipodoc;
                        //si es 1 no hacer nada porque es pre factura
                        if($getidtipodoc == 1 || $getidtipodoc == 2)   { $ifBusquedaDocumento  = 0; }
                        else                                           { $ifBusquedaDocumento  = 1; }
                    }

                    //si no hay disponibilidad asignar un documento o si es una nota buscar una pre factura
                    if(!$getDisponibilidadDocumento || $ifBusquedaDocumento == 1){
                        // Intentar obtener la prefactura
                        $getPrefactura = $this->devolucionRepository->getFacturaAvailable($datos, $cantidadLibroDescontar, [1]);
                        if (!$getPrefactura) {
                            // Si no hay prefactura disponible, intentar obtener una nota
                            $getPrefactura = $this->devolucionRepository->getFacturaAvailable($datos, $cantidadLibroDescontar, [3, 4]);
                            if (!$getPrefactura) {
                                // No hay notas ni prefacturas disponibles
                                $item->mensaje = 'No hay suficientes en notas ni pre facturas disponibles';
                                $codigosNoCambiados[] = $item;
                                $contadorNoCambiado++;
                                continue;
                            }else{
                                // Llamada a la función de actualización de código, pasando los parámetros correctos
                                $this->devolucionRepository->actualizarCodigo($codigo, $codigo_proforma, $getPrefactura->ven_codigo,  $institucion_id_select, $periodo_id, $id_usuario);
                            }
                        }else{
                            // Llamada a la función de actualización de código, pasando los parámetros correctos
                            $this->devolucionRepository->actualizarCodigo($codigo, $codigo_proforma, $getPrefactura->ven_codigo,  $institucion_id_select, $periodo_id, $id_usuario);
                        }
                    }
                }
                //validar si el codigo existe
                $validar                        = $this->getCodigos($item->codigo,0);
                $ingreso                        = 0;
                $ifErrorProforma                = 0;
                $messageProforma                = "";
                $ingreso                        = 0;
                $messageIngreso                 = "";
                $id_cliente                     = $item->institucion_id_select;
                $bc_periodo                     = $item->bc_periodo;
                $libro_idlibro                  = $item->libro_idlibro;
                $codigo_liquidacion             = $item->codigo_liquidacion;
                $precio                         = $item->precio;
                $estado_codigo                  = $item->estado;
                //valida que el codigo existe
                if(count($validar)>0){
                    $codigo_union               = $validar[0]->codigo_union;
                    //validar si el codigo se encuentra liquidado
                    $ifLiquidado                = $validar[0]->estado_liquidacion;
                    //contrato
                    $ifContrato                 = $validar[0]->contrato;
                    //numero de verificacion
                    $ifVerificacion             = $validar[0]->verificacion;
                    //codigo de combo
                    $ifCombo                    = $validar[0]->combo;
                    //codigo_combo
                    $ifCodigoCombo              = $validar[0]->codigo_combo;
                    //codigo de factura
                    $ifFactura                  = $validar[0]->factura;
                    //tipo_venta
                    $ifTipoVenta                = $validar[0]->venta_estado;
                    //codigo_paquete
                    $ifcodigo_paquete           = $validar[0]->codigo_paquete;
                    //para ver si es codigo regalado no este liquidado
                    $ifliquidado_regalado       = $validar[0]->liquidado_regalado;
                    //para ver la empresa de la proforma
                    $ifproforma_empresa         = $validar[0]->proforma_empresa;
                    ///para ver el codigo de proforma
                    $ifcodigo_proforma          = $validar[0]->codigo_proforma;
                    //ADMINISTRADOR (SI PUEDE DEVOLVER AUNQUE LA INSTITUCION SEA DIFERENTE)
                    $EstatusProceso             = false;
                    if($request->dLiquidado ==  '1'){
                        $setContrato            = $ifContrato;
                        $verificacion_liquidada = $ifVerificacion;
                        //VALIDACION AUNQUE ESTE LIQUIDADO
                        if($ifLiquidado == '0' || $ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4') $EstatusProceso = true;
                    }else{
                        //VALIDACION QUE NO SEA LIQUIDADO
                        if(($ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4') && $ifliquidado_regalado == '0' ) $EstatusProceso = true;
                    }
                    //jorge dice que se quita esta validacion
                    // if(($ifLiquidado == '1' || $ifLiquidado == '2') && ($ifBc_Institucion == $institucion_id || $ifventa_lista_institucion == $institucion_id )) $EstatusProceso = true;
                    //SI CUMPLE LA VALIDACION
                    if($EstatusProceso){
                        //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
                        $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                        if($codigo_union != null || $codigo_union != ""){
                            //devolucion
                            $getcodigoUnion     = CodigosLibros::Where('codigo',$codigo_union)->get();
                            $getIngreso         = $this->codigosRepository->updateDocumentoDevolucion($item->codigo,$codigo_union,$getcodigoUnion,$request,$codigo_ven);
                            $ingreso            = $getIngreso["ingreso"];
                            $messageIngreso     = $getIngreso["messageIngreso"];
                            //si ingresa correctamente
                            if($ingreso == 1){
                                $newValuesCodigoActivacion = CodigosLibros::where('codigo',$item->codigo)->get();
                                $newValuesCodigoDiagnostico = CodigosLibros::where('codigo',$codigo_union)->get();
                                $porcentaje++;
                                //====CODIGO====
                                //ingresar en el historico codigo
                                $this->GuardarEnHistorico(0,$id_cliente,$periodo_id,$item->codigo,$request->id_usuario,$mensaje,$getcodigoPrimero,$newValuesCodigoActivacion,$setContrato,$verificacion_liquidada);
                                //ingresar a la tabla de devolucion
                                //====CODIGO UNION=====
                                $this->GuardarEnHistorico(0,$id_cliente,$periodo_id,$codigo_union,$request->id_usuario,$mensaje,$getcodigoUnion,$newValuesCodigoDiagnostico,$setContrato,$verificacion_liquidada);
                                //GUARDAR EN LA TABLA DE DEVOLUCION CODIGO LIBRO
                                $this->tr_GuardarDevolucionHijos($id_devolucion,$item->codigo,$item->codigo_liquidacion,$id_cliente,$ifCombo,$ifFactura,$ifcodigo_proforma,$ifproforma_empresa,$ifTipoVenta,$bc_periodo,0,$codigo_union,$libro_idlibro,$ifcodigo_paquete,$ifLiquidado,$ifliquidado_regalado,$precio,$tipo_importacion,$estado_codigo,$ifCodigoCombo);
                                //GUARDAR EN LA TABLA DE DEVOLUCION CODIGO UNION

                            }
                            else{
                                $codigosNoCambiados[] = [
                                    "codigo"        => $item->codigo,
                                    "mensaje"       => $messageIngreso
                                ];
                                $contadorNoCambiado++;
                            }
                        }
                        //ACTUALIZAR CODIGO SIN UNION
                        else{
                            $getIngreso         = $this->codigosRepository->updateDocumentoDevolucion($item->codigo,0,null,$request,$codigo_ven);
                            $ingreso            = $getIngreso["ingreso"];
                            $messageIngreso     = $getIngreso["messageIngreso"];
                            if($ingreso == 1){
                                $newValuesCodigoActivacion = CodigosLibros::where('codigo',$item->codigo)->get();
                                $porcentaje++;
                                //ingresar en el historico
                                $this->GuardarEnHistorico(0,$id_cliente,$periodo_id,$item->codigo,$request->id_usuario,$mensaje,$getcodigoPrimero,$newValuesCodigoActivacion,$setContrato,$verificacion_liquidada);
                                //GUARDAR EN LA TABLA DE DEVOLUCION CODIGO LIBRO
                                $this->tr_GuardarDevolucionHijos($id_devolucion,$item->codigo,$item->codigo_liquidacion,$id_cliente,$ifCombo,$ifFactura,$ifcodigo_proforma,$ifproforma_empresa,$ifTipoVenta,$bc_periodo,0,null,$libro_idlibro,$ifcodigo_paquete,$ifLiquidado,$ifliquidado_regalado,$precio,$tipo_importacion,$estado_codigo,$ifCodigoCombo);
                                $codigosSinCodigoUnion[] = $validar[0];
                            }
                            else{
                                $codigosNoCambiados[] = [
                                    "codigo"        => $item->codigo,
                                    "mensaje"       => $messageIngreso
                                ];
                                $contadorNoCambiado++;
                            }
                        }
                    }
                    //SI NO CUMPLE LA VALIDACION
                    else{
                        if($ifErrorProforma == 1)               { $validar[0]->errorProforma = 1; $validar[0]->mensajeErrorProforma   = $messageProforma; }
                        $codigosConLiquidacion[]                = $validar[0];
                        $contador++;
                    }
                }else{
                    $codigoNoExiste[$contadorNoexiste] = [ "codigo" => $item->codigo ];
                    $contadorNoexiste++;
                }
            }
            //validar si es combo actualizar a importacion_con_combos a 1
            $getHijos = CodigosLibrosDevolucionSon::whereNotNull('codigo_combo')
            ->whereNotNull('combo')
            ->where('codigoslibros_devolucion_id',$id_devolucion)
            ->get();
            if(count($getHijos) > 0){
                $padre = CodigosLibrosDevolucionHeader::find($id_devolucion);
                if($padre){
                    $padre->importacion_con_combos = 1;
                    $padre->save();
                }
            }
            if($contadorNoCambiado > 0 || $contadorNoexiste > 0){
                $porcentaje = 0;
                DB::rollBack();
            }else{
                DB::commit();
            }
            return [
                "cambiados"             => $porcentaje,
                "contadorNoCambiado"    => $contadorNoCambiado,
                "codigosNoCambiados"    => $codigosNoCambiados,
                "codigosConLiquidacion" => $codigosConLiquidacion,
                "codigoNoExiste"        => $codigoNoExiste,
                "codigosSinCodigoUnion" => $codigosSinCodigoUnion,
            ];

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                "status"  => 0,
                'message' => $e->getMessage()
            ], 200);
        }
    }

    public function devolucionIndividualBodega($getCodigo,$id_usuario,$cliente,$institucion_id,$periodo_id,$observacion,$mensaje,$request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigosNoCambiados     = [];
        $codigosConLiquidacion  = [];
        $codigoNoExiste         = [];
        $codigosSinCodigoUnion  = [];
        $porcentaje             = 0;
        $contador               = 0;
        $fecha                  = date('Y-m-d H:i:s');
        $messageProforma        = "";
        $ifErrorProforma        = 0;
        $ifsetProforma          = 0;
        $EstatusProceso         = false;
        $ingreso                = 0;
        $messageIngreso         = "";
        $tipo_importacion       = $request->tipo_importacion;
        try{
            //validar si el codigo existe
            $validar = $this->getCodigos($getCodigo,0);
            //valida que el codigo existe
            if(count($validar)>0){
                //trae codigo union
                $codigo_union                   = $validar[0]->codigo_union;
                // combo generado
                $if_codigo_combo                = $validar[0]->codigo_combo;
                //validar si el codigo no se encuentra liquidado
                $ifLiquidado                    = $validar[0]->estado_liquidacion;
                $ifliquidado_regalado           = $validar[0]->liquidado_regalado;
                //para ver la empresa de la proforma
                $ifproforma_empresa             = $validar[0]->proforma_empresa;
                //para ver el estado devuelto proforma
                $ifdevuelto_proforma            = $validar[0]->devuelto_proforma;
                ///para ver el codigo de proforma
                $ifcodigo_proforma              = $validar[0]->codigo_proforma;
                //codigo de liquidacion
                $ifcodigo_liquidacion           = $validar[0]->codigo_liquidacion;
                //ifcodigo_paquete
                $ifcodigo_paquete               = $validar[0]->codigo_paquete;
                if(($ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4') && $ifliquidado_regalado == '0' ) $EstatusProceso = true;
                if($EstatusProceso){
                //SE QUITA ESTA VALIDACION
                    $getcodigoPrimero = CodigosLibros::Where('codigo',$getCodigo)->get();
                    if($codigo_union != null || $codigo_union != ""){
                        //devolucion con codigo de union
                        $getcodigoUnion     = CodigosLibros::Where('codigo',$codigo_union)->get();
                        $getIngreso         =  $this->codigosRepository->updateDevolucion($getCodigo,$codigo_union,$getcodigoUnion,$request,$ifsetProforma,$ifcodigo_liquidacion,$ifproforma_empresa,$ifcodigo_proforma,$tipo_importacion);
                        $ingreso            = $getIngreso["ingreso"];
                        $messageIngreso     = $getIngreso["messageIngreso"];
                        if($ingreso == 1){
                            if($tipo_importacion == 1){
                                if($if_codigo_combo){
                                    $mensaje = $mensaje." - Se quito el combo $if_codigo_combo";
                                }
                                if($ifcodigo_paquete){
                                    $mensaje = $mensaje." - Se quito el paquete $ifcodigo_paquete";
                                }
                            }
                            $porcentaje++;
                            $newValusPrimero = CodigosLibros::where('codigo',$getCodigo)->get();
                            $newValusUnion   = CodigosLibros::where('codigo',$codigo_union)->get();
                            //====CODIGO====
                            //ingresar en el historico codigo
                            $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$getCodigo,$id_usuario,$mensaje,$getcodigoPrimero,$newValusPrimero);
                            //ingresar a la tabla de devolucion
                            $this->codigosRepository->saveDevolucion($getCodigo,$cliente,$institucion_id,$periodo_id,$fecha,$observacion,$id_usuario);
                            //====CODIGO UNION=====
                            $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigo_union,$id_usuario,$mensaje,$getcodigoUnion,$newValusUnion);
                            $this->codigosRepository->saveDevolucion($codigo_union,$cliente,$institucion_id,$periodo_id,$fecha,$observacion,$id_usuario);
                        }else{
                            $codigosNoCambiados[0]  = [
                                "codigo"    	    => $getCodigo,
                                "mensaje"           => $messageIngreso
                            ];
                        }
                    }
                    //devolucion sin codigo de union
                    else{
                        $getIngreso         = $this->codigosRepository->updateDevolucion($getCodigo,0,null,$request,$ifsetProforma,$ifcodigo_liquidacion,$ifproforma_empresa,$ifcodigo_proforma,$ifdevuelto_proforma,$tipo_importacion);
                        $ingreso            = $getIngreso["ingreso"];
                        $messageIngreso     = $getIngreso["messageIngreso"];
                        if($ingreso == 1){
                            if($tipo_importacion == 1){
                                if($if_codigo_combo){
                                    $mensaje = $mensaje." - Se quito el combo $if_codigo_combo";
                                }
                                if($ifcodigo_paquete){
                                    $mensaje = $mensaje." - Se quito el paquete $ifcodigo_paquete";
                                }
                            }

                            $porcentaje++;
                            $newValusPrimero = CodigosLibros::where('codigo',$getCodigo)->get();
                            //ingresar en el historico
                            $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$getCodigo,$id_usuario,$observacion,$getcodigoPrimero,$newValusPrimero);
                            //ingresar a la tabla de devolucion
                            $this->codigosRepository->saveDevolucion($getCodigo,$cliente,$institucion_id,$periodo_id,$fecha,$observacion,$id_usuario);
                            $codigosSinCodigoUnion[] = $validar[0];
                        }
                        else{
                            $codigosNoCambiados[0] =[
                                "codigo"        => $getCodigo,
                                "mensaje"       => $messageIngreso
                            ];
                        }
                    }
                }else{
                    $mensaje_personalizado              = "";
                    //mensaje personalizado front
                    if($ifLiquidado == 0)               { $mensaje_personalizado = "Código liquidado"; }
                    if($ifLiquidado == 2)               { if($ifliquidado_regalado == '1') { $mensaje_personalizado = "Código  Regalado liquidado"; } }
                    if($ifLiquidado == 3)               { $mensaje_personalizado = "Código  ya devuelto"; }
                    if($ifLiquidado == 4)               { $mensaje_personalizado = "Código Guia"; }
                    //error proforma
                    if($ifErrorProforma == 1)           { $mensaje_personalizado  = $messageProforma; }
                    ////add array to front
                    $codigosConLiquidacion[$contador]   = [
                        "codigo"                         => $getCodigo,
                        "prueba_diagnostica"             => $validar[0]->prueba_diagnostica,
                        "tipoCodigo"                     => $validar[0]->tipoCodigo,
                        "liquidacion"                    => $validar[0]->liquidacion,
                        "institucionBarra"               => $validar[0]->institucion_barras,
                        "periodo_barras"                 => $validar[0]->periodo_barras,
                        "estado_liquidacion"             => $validar[0]->estado_liquidacion,
                        "codigo_union"                   => $validar[0]->codigo_union,
                        "mensaje"                        => $mensaje_personalizado
                    ];
                    $contador++;
                }
            }else{
                $codigoNoExiste[0] =[
                    "codigo" => $getCodigo
                ];
            }
            return [
                "cambiados"             => $porcentaje,
                "codigosNoCambiados"    => $codigosNoCambiados,
                "codigosConLiquidacion" => $codigosConLiquidacion,
                "codigoNoExiste"        => $codigoNoExiste,
                "codigosSinCodigoUnion" => $codigosSinCodigoUnion
            ];
        }
        catch(\Exception $e){
            return response()->json([
                "status"  => 0,
                'message' => $e->getMessage()
            ], 200);
        }
    }
       //api:post//codigos/devolucion/activar
       public function ActivardevolucionCodigos(Request $request){
        //api:post//codigos/import/periodo
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos = json_decode($request->data_codigos);
        $codigosNoCambiados     = [];
        $codigoSinDevolucion    = [];
        $codigoNoExiste         = [];
        $codigosSinCodigoUnion  = [];
        $porcentaje             = 0;
        $contador               = 0;
        $contadorNoExiste       = 0;
        $mensaje                = $request->observacion;
        $contadorNoCambiado     = 0;
        foreach($codigos as $key => $item){
            //validar si el codigo existe
            $validar = $this->getCodigos($item->codigo,0);
            //valida que el codigo existe
            if(count($validar)>0){
                $EstatusProceso         = false;
                //validar si el codigo se encuentra liquidado
                $ifDevuelto             = $validar[0]->estado_liquidacion;
                //validar si tiene codigo de union
                $codigo_union           = $validar[0]->codigo_union;
                $ifliquidado_regalado   = $validar[0]->liquidado_regalado;
                //para ver el estado devuelto proforma
                // $ifdevuelto_proforma        = $validar[0]->devuelto_proforma;
                //VALIDACION QUE NO SEA LIQUIDADO
                if($ifDevuelto != '0' && $ifliquidado_regalado == '0' && $ifDevuelto != '4'){ $EstatusProceso = true; }
                //====PROFORMA============================================
                //ifdevuelto_proforma => 0 => nada; 1 => devuelta antes del enviar el pedido; 2 => enviada despues de enviar al pedido
                // if($ifdevuelto_proforma == 2 ){ $EstatusProceso = false; $messageProforma = "No se puede activar el código, ya que tiene proforma que no se pudo devolver por que ya fue enviado a perseo el pedido"; }
                //PROFORMA si tiene proforma que no se pudo devolver despues de enviar el pedido
                if($EstatusProceso){
                    //VALIDAR CODIGOS QUE NO TENGA CODIGO UNION
                    if($codigo_union != null || $codigo_union != ""){
                        $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                        $getcodigoUnion   = CodigosLibros::Where('codigo',$codigo_union)->get();
                        //ACTIVACION CON CODIGO DE UNION
                        $ingreso =  $this->codigosRepository->updateActivacion($item->codigo,$codigo_union,$getcodigoUnion,false,1,$request);
                        //si ingresa correctamente
                        if($ingreso == 1){
                            $porcentaje++;
                            //====CODIGO====
                            //ingresar en el historico codigo
                            $this->GuardarEnHistorico(0,null,null,$item->codigo,$request->id_usuario,$mensaje,$getcodigoPrimero,null);
                            //====CODIGO UNION=====
                            $this->GuardarEnHistorico(0,null,null,$codigo_union,$request->id_usuario,$mensaje,$getcodigoUnion,null);
                        }else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "Problema con el código union $codigo_union"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                    //ACTUALIZAR CODIGO SIN UNION
                    else{
                        $ingreso =  $this->codigosRepository->updateActivacion($item->codigo,0,null,false,1,$request);
                        if($ingreso == 1){
                            $porcentaje++;
                            //ingresar en el historico
                            $this->GuardarEnHistorico(0,null,null,$item->codigo,$request->id_usuario,$mensaje,null,null);
                            $codigosSinCodigoUnion[] = $validar[0];
                        }
                        else{
                            $codigosNoCambiados[$contadorNoCambiado] =[
                                "codigo"        => $item->codigo,
                                "mensaje"       => "0"
                            ];
                            $contadorNoCambiado++;
                        }
                    }
                }else{
                    //si el codigo tiene proforma que no se pudo devolver despues de enviar el pedido
                    // if($ifdevuelto_proforma == 2)     { $validar[0]->errorProforma = 1; $validar[0]->mensajeErrorProforma   = $messageProforma; }
                    $codigoSinDevolucion[] = $validar[0];
                    $contador++;
                }
            }else{
                $codigoNoExiste[$contadorNoExiste] =[
                    "codigo" => $item->codigo
                ];
                $contadorNoExiste++;
            }
        }
        return [
            "cambiados"             => $porcentaje,
            "codigosNoCambiados"    => $codigosNoCambiados,
            "codigoSinDevolucion"   => $codigoSinDevolucion,
            "codigoNoExiste"        => $codigoNoExiste,
            "codigosSinCodigoUnion" => $codigosSinCodigoUnion,
        ];
    }

    public function PeriodoInstitucion($institucion){
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo , periodoescolar AS descripcion FROM periodoescolar WHERE idperiodoescolar = (
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$institucion'))
        ");
        return $periodoInstitucion;
    }

    public function index(Request $request)
    {
        $libros = DB::SELECT("SELECT * FROM codigoslibros join libro on libro.idlibro = codigoslibros.libro_idlibro  WHERE codigoslibros.idusuario = ?",[$request->idusuario]);
        return $libros;
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
        $codigo = DB::UPDATE("UPDATE `codigoslibros` SET `idusuario`= ? WHERE `codigo` = ?",[$request->idusuario,"$request->codigo"]);
        return $codigo;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CodigoLibros  $codigoLibros
     * @return \Illuminate\Http\Response
     */
    public function show(CodigoLibros $codigoLibros)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CodigoLibros  $codigoLibros
     * @return \Illuminate\Http\Response
     */
    public function edit(CodigoLibros $codigoLibros)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CodigoLibros  $codigoLibros
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CodigoLibros $codigoLibros)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CodigoLibros  $codigoLibros
     * @return \Illuminate\Http\Response
     */
    public function destroy(CodigoLibros $codigoLibros)
    {
        //
    }
     //api:get/getEstudianteCodigos
     public function getEstudianteCodigos($data){
        $datos = explode("*", $data);
        $periodo     = $datos[0];
        $institucion = $datos[1];
        $query = DB::SELECT("SELECT c.idusuario,
        CONCAT(u.nombres,' ',u.apellidos) AS estudiante,
          c.codigo,l.nombrelibro
         FROM codigoslibros c
        LEFT JOIN usuario u ON c.idusuario = u.idusuario
        LEFT JOIN libro l ON l.idlibro = c.libro_idlibro
        WHERE u.institucion_idInstitucion ='$institucion'
        AND c.id_periodo = '$periodo'
        AND c.estado <> '2'
        ");
        return $query;
    }
    //api:post/codigos/ingreso
    public function importIngresoCodigos(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos           = json_decode($request->data_codigos);
        $idlibro           = $request->idlibro;
        $id_usuario        = $request->id_usuario;
        $anio              = $request->anio;
        $libro             = $request->libro;
        $serie             = $request->serie;
        $comentario        = $request->comentario;
        $periodo_id        = $request->periodo_id;
        $datos             = [];
        $NoIngresados      = [];
        $porcentaje        = 0;
        $contador          = 0;
        foreach($codigos as $key => $item){
            $consulta = $this->getCodigos($item->codigo,0);
            //si ya existe el codigo lo mando a un array
            if(count($consulta) > 0){
               $datos[] = $consulta[0];
            }else{
                //si no existen los agrego
                $codigos_libros                             = new CodigosLibros();
                $codigos_libros->serie                      = $serie;
                $codigos_libros->libro                      = $libro;
                $codigos_libros->anio                       = $anio;
                $codigos_libros->libro_idlibro              = $idlibro;
                $codigos_libros->estado                     = 0;
                $codigos_libros->idusuario                  = 0;
                $codigos_libros->bc_estado                  = 1;
                $codigos_libros->idusuario_creador_codigo   = $id_usuario;
                $codigos_libros->prueba_diagnostica         = $item->diagnostica;
                $codigos_libros->codigo                     = $item->codigo;
                $codigos_libros->contador                   = 1;
                $codigos_libros->save();
                if($codigos_libros){
                    $porcentaje++;
                    //ingresar en el historico
                     $this->GuardarEnHistorico(0,null,$periodo_id,$item->codigo,$id_usuario,$comentario,null,null);
                }else{
                    $NoIngresados[$contador] =[
                        "codigo" => $item->codigo
                    ];
                    $contador++;
                }
            }
        }
        $data = [
            "cambiados"             => $porcentaje,
            "CodigosExisten"        => $datos,
            "CodigosNoIngresados"   => $NoIngresados,
        ];
        return $data;
    }
    public function revisarUltimoHistoricoCodigo(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos           = json_decode($request->data_codigos);
        $datos             = [];
        $NoIngresados      = [];
        $porcentaje        = 0;
        $contadorNoExiste  = 0;
        foreach($codigos as $key => $item){
            // $consulta = $this->getCodigos($item->codigo,0);
            //si ya existe el codigo lo mando a un array
            // if(count($consulta) > 0){
                $query  = DB::SELECT("SELECT h.id_codlibros,h.codigo_libro,h.idInstitucion,
                h.usuario_editor,h.observacion,h.id_periodo ,
                 CONCAT(u.nombres,'', u.apellidos) AS editor ,
                i.nombreInstitucion
                FROM hist_codlibros h
                LEFT JOIN usuario u ON h.idInstitucion = u.idusuario
                LEFT JOIN institucion i ON h.usuario_editor  = i.idInstitucion
                WHERE h.codigo_libro = '$item->codigo'
                ORDER BY h.id_codlibros DESC
                LIMIT 1
                ");
                $porcentaje++;
               $datos[] = $query[0];
            // }else{
            //     $codigoNoExiste[$contadorNoExiste] =[
            //         "codigo" => $item->codigo
            //     ];
            //     $contadorNoExiste++;
            // }
        }
        $data = [
            "porcentaje"            => $porcentaje,
            "codigosHistorico"      => $datos,
            "CodigosNoIngresados"   => $NoIngresados,
        ];
        return $data;
    }
    //API:GET/procesosbodega
    public function procesosbodega(Request $request){
        //buscar factura like
        if($request->obtenerFacturasLike){
            $key = "obtenerFacturasLike".$request->factura;
            if (Cache::has($key)) {
            $query = Cache::get($key);
            } else {
                $query = $this->obtenerFacturasLike($request->factura);
                Cache::put($key,$query);
            }
            return $query;
        }
        //Buscar documento
        if($request->getCodigosXDocumento)    {
            $key = "searchDocumento".$request->factura;
            if (Cache::has($key)) {
            $query = Cache::get($key);
            } else {
                $query = $this->getCodigosXDocumento($request->factura);
                Cache::put($key,$query);
            }
            return $query;
        }
    }
    //API:POST/procesosFacturador
    public function procesosFacturador(Request $request){
        if($request->mandarDevueltoRegalado){ return $this->mandarDevueltoRegalado($request); }
        if($request->cambiarEstadoCodigos)  { return $this->cambiarEstadoCodigos($request); }
    }
    //API:POST/procesosFacturador/mandarDevueltoRegalado
    public function mandarDevueltoRegalado($request){
        //limpiar cache
        Cache::flush();
        $institucion_id             = $request->institucion_id;
        $periodo_id                 = $request->periodo_id;
        $id_usuario                 = $request->id_usuario;
        $observacion                = $request->observacion;
        $arrayCodigos               = json_decode($request->arrayCodigos);
        $contador                   = 0;
        foreach($arrayCodigos as $key => $item){
            $verificacion_liquidada = $item->verificacion;
            $contrato               = $item->contrato;
            $getCodigoActivacion    = $this->getCodigos($item->codigo,0);
            //codigo de union
            $codigo_union           = $getCodigoActivacion[0]->codigo_union;
            //si el codigo es diferente de nulo
            if($codigo_union != null || $codigo_union != ""){
                //devolucion
                $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                $getcodigoUnion   = CodigosLibros::Where('codigo',$codigo_union)->get();
                //devolucion codigo normal
                $this->moveToDevolucion($item->codigo);
                //devolucion codigo union
                $this->moveToDevolucion($codigo_union);
                //si ingresa correctamente
                //====CODIGO====
                //ingresar en el historico codigo
                $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$item->codigo,$id_usuario,$observacion,$getcodigoPrimero,null,$contrato,$verificacion_liquidada);
                //====CODIGO UNION=====
                $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigo_union,$id_usuario,$observacion,$getcodigoUnion,null,$contrato,$verificacion_liquidada);
                $contador++;
            }
            else{
                $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                //solo el codigo normal
                $this->moveToDevolucion($item->codigo);
                $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$item->codigo,$id_usuario,$observacion,$getcodigoPrimero,null,$contrato,$verificacion_liquidada);
                $contador++;
            }
        }
        return [
            "cambiados"             => $contador,
        ];
    }
    public function moveToDevolucion($codigo){
        DB::table('codigoslibros')
        ->where('codigo', '=', $codigo)
        ->update([
            // 'estado_liquidacion'    => '3',
            // 'bc_estado'             => '1',//sin leer
            'quitar_de_reporte'        => '1', // no se visualizará en el reporte de facturacion, pero el estudiante si puede seguir usando en su módulo
        ]);
    }
    public function cambiarEstadoCodigos($request){
        //limpiar cache
        Cache::flush();
        $institucion_id             = $request->institucion_id;
        $periodo_id                 = $request->periodo_id;
        $id_usuario                 = $request->id_usuario;
        $observacion                = $request->observacion;
        $arrayCodigos               = json_decode($request->arrayCodigos);
        $tipo                       = $request->filtroTipo;
        $contador                   = 0;
        foreach($arrayCodigos as $key => $item){
            $getCodigoActivacion    = $this->getCodigos($item->codigo,0);
            //codigo de union
            $codigo_union           = $getCodigoActivacion[0]->codigo_union;
            //si el codigo es diferente de nulo
            if($codigo_union != null || $codigo_union != ""){
                //devolucion
                $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                $getcodigoUnion   = CodigosLibros::Where('codigo',$codigo_union)->get();
                //devolucion codigo normal
                $this->changeToEstado($item->codigo,$tipo);
                //devolucion codigo union
                $this->changeToEstado($codigo_union,$tipo);
                //si ingresa correctamente
                //====CODIGO====
                //ingresar en el historico codigo
                $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$item->codigo,$id_usuario,$observacion,$getcodigoPrimero,null,null,null);
                //====CODIGO UNION=====
                $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$codigo_union,$id_usuario,$observacion,$getcodigoUnion,null,null,null);
                $contador++;
            }
            else{
                $getcodigoPrimero = CodigosLibros::Where('codigo',$item->codigo)->get();
                //solo el codigo normal
                $this->changeToEstado($item->codigo,$tipo);
                $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$item->codigo,$id_usuario,$observacion,$getcodigoPrimero,null,null,null);
                $contador++;
            }
        }
        return [
            "cambiados"             => $contador,
        ];
    }
    public function changeToEstado($codigo,$tipo){
        $datos = [];
        //cambiar a codigos liquidados Normales
        if($tipo == 2){
            $datos = [
                'estado_liquidacion'    => '0',
                'liquidado_regalado'    => '0',
            ];
        }
        //cambiar a codigos liquidados regalados
        if($tipo == 0){
            $datos = [
                'estado_liquidacion'    => '2',
                'liquidado_regalado'    => '1',
            ];
        }
        DB::table('codigoslibros')
        ->where('codigo', '=', $codigo)
        ->update($datos);
    }
    public function getCodigosPlanlectorLiquidadoRegalado(Request $request){
        $query = DB::SELECT("SELECT c.libro_idlibro, ls.codigo_liquidacion, ls.nombre,
            SUM(CASE WHEN c.estado_liquidacion = '2' AND c.liquidado_regalado = '1' AND c.prueba_diagnostica = '0' THEN 1 ELSE 0 END) AS codigos_regalado_liquidado,
            SUM(CASE WHEN c.estado_liquidacion = '2' AND c.liquidado_regalado = '0' AND c.prueba_diagnostica = '0' THEN 1 ELSE 0 END) AS codigos_regalado,
            SUM(CASE WHEN c.estado_liquidacion = '0' AND c.prueba_diagnostica = '0' AND c.prueba_diagnostica = '0' THEN 1 ELSE 0 END) AS codigos_liquidados
            FROM codigoslibros c
            LEFT JOIN libros_series ls ON c.libro_idlibro = ls.idLibro
            LEFT JOIN series s ON ls.id_serie = s.id_serie
            WHERE c.bc_periodo = '$request->periodo'
            AND ls.id_serie = '6'
            GROUP BY c.libro_idlibro, ls.nombre, ls.codigo_liquidacion;");
        return $query;
    }
    public function importCodigosRemplazoPaquetes(Request $request)
    {
        $dataCodigosRemplazo = json_decode($request->input('dataCodigosRemplazo'), true);

        // Arrays para almacenar resultados
        $confirmacionesQuitarPaquete = [];
        $confirmacionesActualizarPaquete = [];
        $errores = [
            'error_codigo' => [],
        ];

        // Arrays para gestionar códigos válidos y a actualizar
        $codigosActualizar = [];
        $codigosUnionActualizar = [];
        $codigosNoActualizados = [];
        $paquetesInvalidos = [];

        DB::beginTransaction();

        try {
            foreach ($dataCodigosRemplazo as $item) {
                $paquete = $item['paquete'];
                $codigoRemplazo = $item['codigo_remplazo'];

                // Buscar códigos con estado 2 y prueba_diagnostica 0 para el paquete
                $codigos = DB::table('codigoslibros')
                    ->where('codigo_paquete', $paquete)
                    ->where('estado', 2)
                    ->where('prueba_diagnostica', 0)
                    ->get();

                if ($codigos->isEmpty()) {
                    $errores['error_codigo'][] = [
                        'Codigo' => $paquete,
                        'Identificador' => 'Paquete',
                        'error' => 'Codigo del Paquete no valido.'
                    ] ;
                    continue;
                }

                // Busca el codigo relacion del codigo_remplazo
                $codigoUnionNuevoRemplazo = DB::table('codigoslibros')
                    ->where('codigo', $codigoRemplazo)
                    ->first();

                if (empty($codigoUnionNuevoRemplazo)) {
                    $errores['error_codigo'][] = [
                        'Codigo' => $codigoRemplazo,
                        'Identificador' => 'Codigo',
                        'error' => "No se encontró el código de unión correcto. Referencia: Paquete-$paquete"
                    ] ;
                    $codigoRemplazo;
                    continue;
                }

                $códigoUnionEncontrado = false;

                foreach ($codigos as $codigo) {
                    // Buscar el código de unión correspondiente
                    $codigoUnion = DB::table('codigoslibros')
                        ->where('codigo', $codigo->codigo_union)
                        ->first();

                    if ($codigoUnion) {
                        $códigoUnionEncontrado = true;
                        $codigosActualizar[] = $codigo->codigo;
                        $codigosUnionActualizar[] = $codigoUnion->codigo;
                    } else {
                        $errores['error_codigo'][] = [
                            'codigo' => $codigoUnion,
                            'Identificador' => 'Codigo',
                            'error' => 'No se encontró el código de unión correcto.'
                        ];
                    }
                }

                if (!$códigoUnionEncontrado) {
                    $codigosNoActualizados[] = $codigoRemplazo;
                }
            }

            // Actualizar los códigos después del procesamiento
            if (count($codigosActualizar)) {

                DB::table('codigoslibros')
                ->whereIn('codigo', $codigosActualizar)
                ->update([
                    'codigo_paquete' => null,
                    'estado_liquidacion' => 3
                ]);

                // Obtener los códigos antes de actualizar para confirmación
                $confirmacionesQuitarPaquete = DB::table('codigoslibros')
                    ->whereIn('codigo', $codigosActualizar)
                    ->get(['codigo', 'estado_liquidacion', 'codigo_paquete'])
                    ->toArray();


            }

            if (count($codigosUnionActualizar)) {

                DB::table('codigoslibros')
                    ->whereIn('codigo', $codigosUnionActualizar)
                    ->update([
                        'codigo_paquete' => null,
                        'estado_liquidacion' => 3
                    ]);

                // Obtener los códigos antes de actualizar para confirmación
                $confirmacionesQuitarPaquete = array_merge($confirmacionesQuitarPaquete, DB::table('codigoslibros')
                    ->whereIn('codigo', $codigosUnionActualizar)
                    ->get(['codigo', 'estado_liquidacion', 'codigo_paquete'])
                    ->toArray());
            }

            // Actualizar el codigo de reemplazo y su código de unión
            foreach ($dataCodigosRemplazo as $item) {
                $paquete = $item['paquete'];
                $codigoRemplazo = $item['codigo_remplazo'];

                $codigoUnionRemplazo = DB::table('codigoslibros')
                    ->where('codigo', $codigoRemplazo)
                    ->value('codigo_union');

                if ($codigoUnionRemplazo) {
                    DB::table('codigoslibros')
                    ->whereIn('codigo', [$codigoRemplazo, $codigoUnionRemplazo])
                    ->update([
                        'codigo_paquete' => $paquete,
                        'estado' => 2
                    ]);

                    // Obtener los códigos antes de actualizar para confirmación
                    $confirmacionesActualizarPaquete[] = DB::table('codigoslibros')
                        ->whereIn('codigo', [$codigoRemplazo, $codigoUnionRemplazo])
                        ->get(['codigo', 'estado', 'codigo_paquete'])
                        ->toArray();


                } else {
                    // $errores['codigos_invalidos'][] = [
                    //     'paquete' => $paquete,
                    //     'codigo_remplazo' => $codigoRemplazo,
                    //     'error' => 'El código de reemplazo no tiene código unión.'
                    // ];
                }
            }

            // Confirmar la transacción
            DB::commit();

            return response()->json([
                'mensaje' => 'Proceso completado exitosamente.',
                'confirmaciones' => [
                    'quitar_paquete' => $confirmacionesQuitarPaquete,
                    'actualizar_paquete' => $confirmacionesActualizarPaquete,
                ],
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            // Deshacer la transacción en caso de error
            DB::rollBack();

            return response()->json([
                'confirmaciones' => [
                    'quitar_paquete' => $confirmacionesQuitarPaquete,
                    'actualizar_paquete' => $confirmacionesActualizarPaquete,
                ],
                'errores' => array_merge($errores, [
                    'mensaje' => 'Error en el proceso: ' . $e->getMessage()
                ])
            ]);
        }
    }
    //api:get/metodosGetCodigos
    public function metodosGetCodigos(Request $request){
        if($request->reporteBodega)                             { return $this->reporteBodega($request); }
        if($request->reporteBodega_new)                         { return $this->reporteBodega_new($request); }
        if($request->reporteVentaCodigos)                      { return $this->reporteVentaCodigos($request); }
        if($request->reporteBodegaCombos)                       { return $this->reporteBodegaCombos($request); }
        if($request->reporteBodegaCombos_new)                   { return $this->reporteBodegaCombos_new($request); }
        if($request->getCombos)                                 { return $this->codigosRepository->getCombos(); }
        if($request->getReporteLibrosAsesores)                  { return $this->getReporteLibrosAsesores($request); }
        if($request->getReporteLibrosAsesores_new)              { return $this->getReporteLibrosAsesores_new($request); }
        if($request->getReporteLibrosXAsesor)                   { return $this->getReporteLibrosXAsesor($request); }
        if($request->getCodigosIndividuales)                    { return $this->getCodigosIndividuales($request); }
        if($request->getReporteXTipoVenta)                      { return $this->getReporteXTipoVenta($request); }
    }
    //api:get/metodosGetCodigos?reporteBodega=1&periodo=25
    public function reporteBodega($request){
        $periodo            = $request->input('periodo');
        $activos            = $request->input('activos');
        $activosNoCombos    = $request->input('activosNoCombos');
        $regalados          = $request->input('regalados');
        $bloqueados         = $request->input('bloqueados');
        $puntoVenta         = $request->input('puntoVenta');
        $puntoVentaActivos  = $request->input('puntoVentaActivos');
        $ventaDirecta       = $request->input('ventaDirecta');
        // Realizar la consulta
        $arrayCodigosActivos = CodigosLibros::select(
            'libros_series.codigo_liquidacion AS codigo',
            DB::raw('COUNT(libros_series.codigo_liquidacion) AS cantidad'),
            'codigoslibros.serie',
            'codigoslibros.libro_idlibro',
            'libros_series.nombre AS nombrelibro',
            'libros_series.year',
            'libros_series.id_serie',
            'asignatura.area_idarea'
        )
        ->leftJoin('libros_series', 'libros_series.idLibro', '=', 'codigoslibros.libro_idlibro')
        ->leftJoin('libro', 'libro.idlibro', '=', 'libros_series.idLibro')
        ->leftJoin('asignatura', 'asignatura.idasignatura', '=', 'libro.asignatura_idasignatura')
        ->where('codigoslibros.prueba_diagnostica', '0')
        ->where('codigoslibros.bc_periodo', $periodo)
        ->when($activos, function ($query) {
            $query->where(function ($query) {
                $query->where('codigoslibros.estado_liquidacion', '1')
                      ->orWhere('codigoslibros.estado_liquidacion', '0')
                      ->orWhere('codigoslibros.estado_liquidacion', '2');
            });
            // ->whereNotNull('codigoslibros.codigo_combo'); // Comparar como string si la columna es de tipo string
            // ->whereNull('codigoslibros.codigo_combo'); // Comparar como string si la columna es de tipo string
        })
        ->when($activosNoCombos, function ($query) {
            $query->where(function ($query) {
                $query->where('codigoslibros.estado_liquidacion', '1')
                      ->orWhere('codigoslibros.estado_liquidacion', '0')
                      ->orWhere('codigoslibros.estado_liquidacion', '2');
            })
            ->whereNull('codigoslibros.codigo_combo'); // Comparar como string si la columna es de tipo string
        })
        ->when($regalados, function ($query) {
            $query->where('codigoslibros.estado_liquidacion', '2')
                  ->where('codigoslibros.estado', '<>', '2'); // Comparar como string si la columna es de tipo string
        })
        ->when($bloqueados, function ($query) {
            $query->where('codigoslibros.estado_liquidacion','<>', '3')
                  ->where('codigoslibros.estado', '2'); // Comparar como string si la columna es de tipo string
        })
        ->when($puntoVenta, function ($query) {
            $query->where('codigoslibros.venta_lista_institucion', request('puntoVenta'))
                  ->where(function ($query) {
                      $query->where('codigoslibros.estado_liquidacion', '0')
                            ->orWhere('codigoslibros.estado_liquidacion', '1')
                            ->orWhere('codigoslibros.estado_liquidacion', '2')
                            ->orWhere('codigoslibros.estado', '2');
                  });
        })
        ->when($ventaDirecta, function ($query) {
            $query->where('codigoslibros.bc_institucion', request('ventaDirecta'))
                  ->where(function ($query) {
                      $query->where('codigoslibros.estado_liquidacion', '0')
                            ->orWhere('codigoslibros.estado_liquidacion', '1')
                            ->orWhere('codigoslibros.estado_liquidacion', '2')
                            ->orWhere('codigoslibros.estado', '2');
                  });
        })
        ->when($puntoVentaActivos, function ($query) {
            $query->where('codigoslibros.venta_lista_institucion', request('puntoVentaActivos'))
                  ->where(function ($query) {
                      $query->where('codigoslibros.estado_liquidacion', '0')
                            ->orWhere('codigoslibros.estado_liquidacion', '1')
                            ->orWhere('codigoslibros.estado_liquidacion', '2');
                  });
        })
        ->groupBy('libros_series.codigo_liquidacion', 'libros_series.nombre', 'codigoslibros.serie', 'codigoslibros.libro_idlibro', 'libros_series.year', 'libros_series.id_serie', 'asignatura.area_idarea')
        ->get();

        // Procesar los resultados para obtener el precio y multiplicar por la cantidad
        foreach ($arrayCodigosActivos as $item) {
            // Obtener el precio del libro usando el repositorio
            $precio = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
            $item->precio       = $precio;
            // Multiplicar el precio por la cantidad
            $item->precio_total = number_format($precio * $item->cantidad, 2, '.', '');
        }

        return $arrayCodigosActivos;
    }
    //api:get/metodosGetCodigos?reporteVentaCodigos=1&periodo=26
    public function reporteVentaCodigos($request){
        $periodo            = $request->input('periodo');
        $tipoReporte        = $request->input('tipoReporte');
        $resultados = DB::table('f_venta as fv')
            ->leftJoin('empresas as ep', 'ep.id', '=', 'fv.id_empresa')
            ->leftJoin('f_detalle_venta as fdv', function ($join) {
                $join->on('fdv.ven_codigo', '=', 'fv.ven_codigo')
                    ->on('fdv.id_empresa', '=', 'fv.id_empresa');
            })
            ->leftJoin('1_4_cal_producto as pp', 'pp.pro_codigo', '=', 'fdv.pro_codigo')
            ->select([
                'fdv.pro_codigo as codigo',
                'pp.pro_nombre as nombrelibro',
                DB::raw('COALESCE(SUM(fdv.det_ven_cantidad - fdv.det_ven_dev), 0) as cantidad'),
                DB::raw('MAX(fdv.det_ven_valor_u) as precio'),
                DB::raw('COALESCE(SUM((fdv.det_ven_cantidad - fdv.det_ven_dev) * fdv.det_ven_valor_u), 0) as precio_total'),
                'pp.ifcombo',
                'pp.codigos_combos'
            ])
            ->when($tipoReporte == 0, function ($query) {
              //todos
            })
            ->when($tipoReporte == 1, function ($query) {
              //solo libros
              $query->where('pp.ifcombo', '0');
            })
            ->when($tipoReporte == 2, function ($query) {
              //solo combos
              $query->where('pp.ifcombo', '1');
            })
            ->where('fv.periodo_id', '=', $periodo)
            ->where('fv.est_ven_codigo', '<>', 3)
            ->whereNotIn('fv.idtipodoc', [2, 16, 17])
            ->groupBy('fdv.pro_codigo', 'pp.pro_nombre', 'pp.ifcombo', 'pp.codigos_combos')
            ->get();
        return $resultados;

    }
    //api:get/metodosGetCodigos?reporteBodegaCombos=1&periodo=25&combos=1
    public function reporteBodegaCombos($request){
        $periodo            = $request->input('periodo');
        $combos             = $request->input('combos');
        $puntoVenta         = $request->input('puntoVenta');
        $puntoVentaCombo    = $request->input('puntoVentaCombo');
        $result = DB::table('f_detalle_venta as v')
        ->leftJoin('f_venta as d', function($join) {
            $join->on('v.ven_codigo', '=', 'd.ven_codigo')
                 ->on('v.id_empresa', '=', 'd.id_empresa');
        })
        ->leftJoin('1_4_cal_producto as p', 'v.pro_codigo', '=', 'p.pro_codigo')
        ->leftJoin('libros_series as ls', 'ls.codigo_liquidacion', '=', 'p.pro_codigo')
        ->leftJoin('libro as l', 'l.idlibro', '=', 'ls.idLibro')
        ->leftJoin('asignatura as a', 'a.idasignatura', '=', 'l.asignatura_idasignatura')
        ->select(
            'v.pro_codigo as codigo',
            'p.pro_nombre as nombrelibro',
            'ls.idLibro as libro_idlibro',
            'ls.year',
            'ls.id_serie',
            'a.area_idarea',
            'p.codigos_combos',
            'p.ifcombo',
            // DB::raw('SUM(v.det_ven_cantidad) as cantidad'),
            DB::raw('SUM(v.det_ven_dev) as cantidad_devuelta'),
            DB::raw('SUM(v.det_ven_cantidad) - SUM(v.det_ven_dev) as cantidad')
        )
        ->where('d.periodo_id', $periodo)
        ->when($combos, function ($query) {
            $query->where('p.ifcombo', '1');
        })
        ->when($puntoVenta, function ($query) {
            $query->where('d.institucion_id', '=', request('puntoVenta'));
        })
        ->when($puntoVentaCombo, function ($query) {
            $query->where('d.institucion_id', '=', request('puntoVentaCombo'))
           ->where('p.ifcombo', '1');
        })
        ->where('d.est_ven_codigo','<>','3')
        ->groupBy('v.pro_codigo', 'p.pro_nombre', 'ls.idLibro', 'ls.year', 'ls.id_serie', 'a.area_idarea', 'p.codigos_combos')
        ->get();
        // Procesar los resultados para obtener el precio y multiplicar por la cantidad
        foreach ($result as $item) {
            // Obtener el precio del libro usando el repositorio
            $precio             = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
            $item->precio       = $precio;
            // Multiplicar el precio por la cantidad
            $item->precio_total = number_format($precio * $item->cantidad, 2, '.', '');
        }
        return $result;
    }
    //api:get/metodosGetCodigos?getReporteLibrosAsesores=1&periodo=24&codigo=SM1
    public function getReporteLibrosAsesores($request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $periodo        = $request->periodo;
        $codigoBusqueda = $request->codigo;
        // $GuiasBodega   = $this->codigosRepository->getCodigosBodega(1,$periodo,0,4179);
        // return $GuiasBodega;
        $val_pedido2 = DB::SELECT("SELECT DISTINCT p.id_asesor, CONCAT(u.nombres,' ',u.apellidos) as asesor
        FROM pedidos_val_area pv
        LEFT JOIN area ar ON  pv.id_area = ar.idarea
        LEFT JOIN series se ON pv.id_serie = se.id_serie
        LEFT JOIN pedidos p ON pv.id_pedido = p.id_pedido
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        where p.tipo        = '1'
        and p.id_periodo  = '$periodo'
        AND p.estado        = '1'
        AND p.estado_entrega = '2'
        GROUP BY u.nombres ORDER BY u.nombres
        ");
        foreach($val_pedido2 as $key11 => $itemAsesor){
            $guias = $this->codigosRepository->getLibrosAsesores($periodo,$itemAsesor->id_asesor);
            $resultado = [];
            //filtrar por el libro_id = 652 los libros
            $guiasPedidos = collect($guias)->where('codigo',$codigoBusqueda)->values();
            if(count($guiasPedidos) == 0){
                $GuiasBodega   = $this->codigosRepository->getCodigosBodega(1,$periodo,0,$itemAsesor->id_asesor);
                //filtrar por codigo
                $resultado          = collect($GuiasBodega)->where('codigo',$codigoBusqueda)->values();
            }else{
                $getBodega   = $this->codigosRepository->getCodigosBodega(1,$periodo,0,$itemAsesor->id_asesor);
                $GuiasBodega = collect($getBodega)->where('codigo',$codigoBusqueda)->values();
                if(count($GuiasBodega) == 0){
                    $resultado = $guiasPedidos;
                }else{
                    $resultado = $guiasPedidos;
                    $resultado[0]->valor = $resultado[0]->valor + $GuiasBodega[0]->cantidad;
                }
            }

            //guardar un campo totalguias obtener el [0]->valor
            $itemAsesor->guias      = $resultado;
            if(count($resultado) == 0){
                $itemAsesor->totalguias = 0;
            }else{
                $itemAsesor->totalguias = $resultado[0]->valor;
            }
            ////ESCUELAS
            $pedidos = $this->tr_institucionesAsesorPedidos($periodo,$itemAsesor->id_asesor);
            foreach($pedidos as $key8 => $itempedido){
                $val_pedido = DB::table('pedidos_val_area as pv')
                ->selectRaw('DISTINCT pv.valor, pv.id_area, pv.tipo_val, pv.id_serie, pv.year, pv.plan_lector, pv.alcance,
                            p.id_periodo,
                            CONCAT(se.nombre_serie, " ", ar.nombrearea) as serieArea,
                            se.nombre_serie')
                ->leftJoin('area as ar', 'pv.id_area', '=', 'ar.idarea')
                ->leftJoin('series as se', 'pv.id_serie', '=', 'se.id_serie')
                ->leftJoin('pedidos as p', 'pv.id_pedido', '=', 'p.id_pedido')
                ->leftJoin('usuario as u', 'p.id_asesor', '=', 'u.idusuario')
                // ->whereIn('p.id_pedido', $ids)
                ->where('p.id_pedido',$itempedido->id_pedido)
                ->where('p.tipo', '0')
                ->where('p.estado', '1')
                ->where('p.id_periodo',$periodo)
                ->groupBy('pv.id')
                ->get();
                if(empty($val_pedido)){
                    // return $val_pedido;
                }else{
                    $arreglo = [];
                    $cont    = 0;
                    //obtener solo los alcances activos
                    foreach($val_pedido as $k => $tr){
                        //Cuando es el pedido original
                        $alcance_id = 0;
                        $alcance_id = $tr->alcance;
                        if($alcance_id == 0){
                            $arreglo[$cont] =   (object)[
                                "valor"             => $tr->valor,
                                "id_area"           => $tr->id_area,
                                "tipo_val"          => $tr->tipo_val,
                                "id_serie"          => $tr->id_serie,
                                "year"              => $tr->year,
                                "plan_lector"       => $tr->plan_lector,
                                "id_periodo"        => $tr->id_periodo,
                                "serieArea"         => $tr->serieArea,
                                "nombre_serie"      => $tr->nombre_serie,
                                "alcance"           => $tr->alcance,
                                "alcance"           => $alcance_id
                            ];
                        }else{
                            //validate que el alcance este cerrado o aprobado
                            $query = $this->codigosRepository->getAlcanceAbiertoXId($alcance_id);
                            if(count($query) > 0){
                                $arreglo[$cont] = (object) [
                                    "valor"             => $tr->valor,
                                    "id_area"           => $tr->id_area,
                                    "tipo_val"          => $tr->tipo_val,
                                    "id_serie"          => $tr->id_serie,
                                    "year"              => $tr->year,
                                    "plan_lector"       => $tr->plan_lector,
                                    "id_periodo"        => $tr->id_periodo,
                                    "serieArea"         => $tr->serieArea,
                                    "nombre_serie"      => $tr->nombre_serie,
                                    "alcance"           => $tr->alcance,
                                    "alcance"           => $alcance_id
                                ];
                            }
                        }
                        $cont++;
                    }
                    //mostrar el arreglo bien
                    $renderSet = [];
                    $renderSet = array_values($arreglo);
                    if(count($renderSet) == 0){
                        return $renderSet;
                    }
                    $datos = [];
                    $contador = 0;
                    //return $renderSet;
                    foreach($renderSet as $key => $item){
                        $valores = [];
                        //plan lector
                        if($item->plan_lector > 0 ){
                            $getPlanlector = DB::SELECT("SELECT l.nombrelibro,l.idlibro,pro.pro_reservar, l.descripcionlibro,
                            (
                                SELECT f.pvp AS precio
                                FROM pedidos_formato f
                                WHERE f.id_serie = '6'
                                AND f.id_area = '69'
                                AND f.id_libro = '$item->plan_lector'
                                AND f.id_periodo = '$item->id_periodo'
                            )as precio, ls.codigo_liquidacion,ls.version,ls.year
                            FROM libro l
                            left join libros_series ls  on ls.idLibro = l.idlibro
                            inner join 1_4_cal_producto pro on ls.codigo_liquidacion=pro.pro_codigo
                            WHERE l.idlibro = '$item->plan_lector'
                            ");
                            $valores = $getPlanlector;
                        }else{
                            $getLibros = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro,pro.pro_reservar, l.descripcionlibro,
                            (
                                SELECT f.pvp AS precio
                                FROM pedidos_formato f
                                WHERE f.id_serie = ls.id_serie
                                AND f.id_area = a.area_idarea
                                AND f.id_periodo = '$item->id_periodo'
                            )as precio
                            FROM libros_series ls
                            LEFT JOIN libro l ON ls.idLibro = l.idlibro
                            inner join 1_4_cal_producto pro on ls.codigo_liquidacion=pro.pro_codigo
                            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                            WHERE ls.id_serie = '$item->id_serie'
                            AND a.area_idarea  = '$item->id_area'
                            AND l.Estado_idEstado = '1'
                            AND a.estado = '1'
                            AND ls.year = '$item->year'
                            LIMIT 1
                            ");
                            $valores = $getLibros;
                        }
                        $datos[$contador] = (Object)[
                            "id_area"           => $item->id_area,
                            "valor"             => $item->valor,
                            // "tipo_val"          => $item->tipo_val,
                            "id_serie"          => $item->id_serie,
                            // "year"              => $item->year,
                            // "anio"              => $valores[0]->year,
                            // "version"           => $valores[0]->version,
                            // "plan_lector"       => $item->plan_lector,
                            "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$valores[0]->nombrelibro : $item->serieArea,
                            "libro_id"          => $valores[0]->idlibro,
                            "nombrelibro"       => $valores[0]->nombrelibro,
                            "nombre_serie"      => $item->nombre_serie,
                            "precio"            => $valores[0]->precio,
                            "codigo"            => $valores[0]->codigo_liquidacion,
                            "stock"             => $valores[0]->pro_reservar,
                            "descripcion"       => $valores[0]->descripcionlibro,
                        ];
                        $contador++;
                    }
                       //si el codigo de liquidacion se repite sumar en el valor
                    // Crear un array asociativo para agrupar por codigo_liquidacion
                    $grouped = [];

                    foreach ($datos as $item) {
                        $codigo = $item->codigo;

                        if (!isset($grouped[$codigo])) {
                            $grouped[$codigo] = $item;
                        } else {
                            $grouped[$codigo]->valor += $item->valor;
                        }
                    }

                    // Convertir el array asociativo de nuevo a un array indexado
                    $result = array_values($grouped);
                    //subtotal
                    foreach($result as $key => $item){
                        $result[$key]->subtotal = $item->valor * $item->precio;
                    }
                    //filtrar por el codigo
                    $resultadoLibros = collect($result)->where('codigo',$codigoBusqueda)->values();
                    $itempedido->librosEscuela = $resultadoLibros;
                }

            }
            //excluyo dentro del array de pedidos los que tiene la propiedad librosEscuela length == 0
            $pedidos = collect($pedidos)->filter(function ($value, $key) {
                return count($value->librosEscuela) > 0;
            })->values();
            $val_pedido2[$key11]->pedidos = $pedidos;
            // $val_pedido2[$key11]->pedidos = $pedidos;
            //contar en los pedidos cuantos librosEscuela mayor a 0 hay
            $contador = 0;
            foreach($pedidos as $key => $item20){
                if(count($item20->librosEscuela) > 0){
                    $contador++;
                }
            }
            $val_pedido2[$key11]->totalLibrosConPedido = $contador;
        }
        return $val_pedido2;
    }
    //api:get/metodosGetCodigos?getReporteLibrosXAsesor=1&periodo=25&id_asesor=4179
    public function getReporteLibrosXAsesor($request){
        $periodo                        = $request->periodo;
        $id_asesor                      = $request->id_asesor;
        $guias                          = $this->codigosRepository->getLibrosAsesores($periodo, $id_asesor);
        $resultado                      = [];
        $guiasPedidos                   = collect($guias);
        $GuiasBodega                    = collect($this->codigosRepository->getCodigosBodega(1, $periodo, 0, $id_asesor));
        if ($guiasPedidos->isEmpty())   { $resultado = $GuiasBodega;}
        else {
            if ($GuiasBodega->isEmpty()){ $resultado = $guiasPedidos; }
            else {
               $GuiasBodega->map(function($item) use ($guiasPedidos){
                $codigo     = $item->codigo;
                $existing   = $guiasPedidos->firstWhere('codigo', $codigo);
                if ($existing) {
                    $existing->valor += $item->valor;
                } else {
                    $guiasPedidos->push($item);
                }
               });
               $resultado = $guiasPedidos;
            }
        }
        foreach ($resultado as $key => $item) {
            // Obtener cantidad devuelta
            $getCantidadDevuelta = $this->tr_cantidadDevuelta($id_asesor, $resultado[$key]->codigo, $periodo);
            if($getCantidadDevuelta > 0) {
                $resultado[$key]->cantidad_devuelta = (int) $getCantidadDevuelta;
            } else {
                $resultado[$key]->cantidad_devuelta = 0;
            }
            $resultado[$key]->stockAsesor = $resultado[$key]->valor - $resultado[$key]->cantidad_devuelta;
        }
        return $resultado;
    }
    //api:get/metodosGetCodigos?getCodigosIndividuales=1&periodo=25
    public function getCodigosIndividuales($request) {
        $activos        = $request->input('activos');
        $periodo        = $request->input('periodo');
        $puntoVenta     = $request->input('puntoVenta');
        $libro          = $request->input('libro');
        $libroId        = 0;
        if($libro){
            $getLibro       = LibroSerie::obtenerProducto($libro);
            if (!$getLibro) {
               return ["status" => "0", "message" => "No se encontró el libro $libro"];
            }
            $libroId   = $getLibro->idLibro;
        }
        $codigosLibros = CodigosLibros::select('codigoslibros.codigo',
            'codigoslibros.factura',
            'codigoslibros.codigo_proforma',
            'codigoslibros.proforma_empresa',
            'emp.descripcion_corta'
            )
            ->leftjoin('empresas as emp','emp.id','=','codigoslibros.proforma_empresa')
            ->where('codigoslibros.prueba_diagnostica', '0')
            ->where('codigoslibros.bc_periodo', $periodo)
            ->when($activos, function ($query) {
                $query->where(function ($query) {
                    $query->where('codigoslibros.estado_liquidacion', '1')
                          ->orWhere('codigoslibros.estado_liquidacion', '0');
                })
                ->where('codigoslibros.estado', '<>', '2');
            })
            ->when($puntoVenta, function ($query) use ($puntoVenta) {
                $query->where('codigoslibros.venta_lista_institucion', $puntoVenta);
            })
            ->when($libro, function ($query) use ($libroId) {
                $query->where('codigoslibros.libro_idlibro', $libroId);
            })
            ->get();

        return $codigosLibros;
    }


    public function getReporteXTipoVenta($request)
    {
        $periodo            = $request->periodo;
        $tipoVenta          = $request->tipoVenta;
        //solo venta directa
        if($tipoVenta == 1){
              // Paso 1: Ejecutar la consulta SQL
            $query = DB::SELECT("
                SELECT
                    i.nombreInstitucion,            -- Nombre de la institución
                    ls.nombrelibro,                 -- Nombre del libro
                    pp.idlibro,                     -- ID del libro
                    pp.pfn_pvp,                     -- Precio del libro
                    c.bc_institucion as idInstitucion,
                    COUNT(c.codigo) AS cantidad,    -- Contamos los códigos por libro e institución
                    (COUNT(c.codigo) * pp.pfn_pvp) AS valortotal,  -- Valor total (cantidad de códigos * precio)
                     u.idusuario,                    -- ID del asesor
                    CONCAT(u.nombres, ' ', u.apellidos) AS asesor  -- Nombre completo del asesor
                FROM
                    codigoslibros c
                JOIN
                    institucion i ON c.bc_institucion = i.idInstitucion  -- Relacionamos la institución
                JOIN
                    usuario u ON i.asesor_id = u.idusuario  -- Relacionamos la institución con el asesor
                JOIN
                    pedidos_formato_new pp ON pp.idlibro = c.libro_idlibro  -- Relacionamos los libros
                JOIN
                    libro ls ON pp.idlibro = ls.idlibro  -- Relacionamos el nombre del libro
                WHERE
                    c.bc_periodo = '$periodo'  -- Filtro por periodo
                    AND (c.estado_liquidacion = '0' OR c.estado_liquidacion = '1' OR c.estado_liquidacion = '2')  -- Filtro por estado de liquidación
                    AND c.prueba_diagnostica = '0'  -- Filtro para no incluir prueba diagnóstica
                    AND ( c.venta_estado = '1' OR c.venta_estado = '0')
                    AND pp.idperiodoescolar = '$periodo'  -- Filtro por periodo escolar
                GROUP BY
                    i.nombreInstitucion, ls.nombrelibro, pp.idlibro, pp.pfn_pvp  -- Agrupamos por institución, libro y precio
                ORDER BY
                    i.nombreInstitucion, ls.nombrelibro;
            ");
        }

        //Venta lista
        if($tipoVenta == 2){
            $query = DB::SELECT("
                SELECT
                    i.nombreInstitucion,            -- Nombre de la institución
                    ls.nombrelibro,                 -- Nombre del libro
                    pp.idlibro,                     -- ID del libro
                    pp.pfn_pvp,                     -- Precio del libro
                    c.venta_lista_institucion as idInstitucion,
                    COUNT(c.codigo) AS cantidad,    -- Contamos los códigos por libro e institución
                    (COUNT(c.codigo) * pp.pfn_pvp) AS valortotal  -- Valor total (cantidad de códigos * precio)
                FROM
                    codigoslibros c
                JOIN
                    institucion i ON c.venta_lista_institucion = i.idInstitucion  -- Relacionamos la institución
                JOIN
                    pedidos_formato_new pp ON pp.idlibro = c.libro_idlibro  -- Relacionamos los libros
                JOIN
                    libro ls ON pp.idlibro = ls.idlibro  -- Relacionamos el nombre del libro
                WHERE
                    c.bc_periodo = '$periodo'  -- Filtro por periodo
                    AND (c.estado_liquidacion = '0' OR c.estado_liquidacion = '1' OR c.estado_liquidacion = '2')  -- Filtro por estado de liquidación
                    AND c.prueba_diagnostica = '0'  -- Filtro para no incluir prueba diagnóstica
                    AND c.venta_estado = '2'  -- Filtro por estado de venta
                    AND pp.idperiodoescolar = '$periodo'  -- Filtro por periodo escolar
                GROUP BY
                    i.nombreInstitucion, ls.nombrelibro, pp.idlibro, pp.pfn_pvp  -- Agrupamos por institución, libro y precio
                ORDER BY
                    i.nombreInstitucion, ls.nombrelibro;
            ");
        }
        //Devueltos
        if($tipoVenta == 3){
            $query = DB::SELECT("SELECT i.idInstitucion, i.nombreInstitucion,
                SUM(h.ven_total) AS total_valor,
                SUM(h.total_items) AS cantidad_codigos
                FROM codigoslibros_devolucion_header h
                LEFT JOIN institucion i ON i.idInstitucion = h.id_cliente
                WHERE h.periodo_id = ?
                AND h.estado <> '0'
                GROUP BY i.nombreInstitucion, i.idInstitucion
                ORDER BY i.nombreInstitucion DESC
            ",[$periodo]);
            return $query;
        }
        // Paso 2: Convertir los resultados a una colección
        $resultados = collect($query);

        // Paso 3: Agrupar por institución
        $instituciones = $resultados->groupBy('nombreInstitucion')->map(function ($grupoInstitucion) {
            // Para cada institución, sumar la cantidad y el valor total de los libros
            $totalCantidad = $grupoInstitucion->sum('cantidad');  // Sumar cantidad
            $totalValor = $grupoInstitucion->sum('valortotal');   // Sumar valor total de los libros

            return [
                'idInstitucion'    => $grupoInstitucion->first()->idInstitucion,  // Nombre de la institución
                'nombreInstitucion' => $grupoInstitucion->first()->nombreInstitucion,  // Nombre de la institución
                'asesor' => $grupoInstitucion->first()->asesor,  // Nombre del asesor
                'cantidad_codigos' => $totalCantidad,  // Total de códigos por institución
                'total_valor' => $totalValor // Total valor de los libros con 2 decimales
            ];
        });

        // Paso 4: Devolver los resultados agrupados y sumados
        return $instituciones->values()->toArray();
    }
    //api:post/metodosPostCodigos=1
    public function metodosPostCodigos(Request $request){
        if($request->getPrevisualizarCodigos)               { return $this->getPrevisualizarCodigos($request); }
        if($request->getPrevisualizarPaquetes)              { return $this->getPrevisualizarPaquetes($request); }
        if($request->getPrevisualizarCodigosTablaSon)       { return $this->getPrevisualizarCodigosTablaSon($request); }
        if($request->getPrevisualizarPaquetesTablaSon)      { return $this->getPrevisualizarPaquetesTablaSon($request); }
        if($request->saveImportPlus)                        { return $this->saveImportPlus($request); }
        if($request->saveImportGuias)                       { return $this->saveImportGuias($request); }
    }
    //api:post/metodosPostCodigos?getPrevisualizarCodigos=1
    public function getPrevisualizarCodigos($request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $mostrarSoloCodigos     = $request->mostrarSoloCodigos;
        // Verificar si se decodificó correctamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid JSON data'];
        }
        // Extraer solo los códigos a buscar
        $codigosABuscar = array_column($codigos, 'codigo');
        // Filtrar la colección usando whereIn y hacer un LEFT JOIN
        $resultados = CodigosLibros::whereIn('codigo', $codigosABuscar)
            // ->where('proforma_empresa', $empresa['idEmpresa'])
            ->leftJoin('institucion', 'codigoslibros.bc_institucion', '=', 'institucion.idInstitucion')
            ->leftJoin('institucion as i2', 'codigoslibros.venta_lista_institucion', '=', 'i2.idInstitucion')
            ->leftJoin('periodoescolar as pe', 'codigoslibros.bc_periodo', '=', 'pe.idperiodoescolar')
            ->leftJoin('libros_series as ls', 'codigoslibros.libro_idlibro', '=', 'ls.idLibro')
            ->leftJoin('libro as l', 'ls.idLibro', '=', 'l.idlibro')
            ->leftJoin('asignatura as a', 'l.asignatura_idasignatura', '=', 'a.idasignatura')
            ->select('codigoslibros.codigo', 'codigoslibros.bc_periodo', 'codigoslibros.bc_institucion',
            'codigoslibros.venta_lista_institucion','codigoslibros.libro_idlibro','codigoslibros.documento_devolucion',
            'codigoslibros.combo','codigoslibros.codigo_combo','codigoslibros.proforma_empresa','codigoslibros.codigo_proforma',
            'codigoslibros.estado_liquidacion','codigoslibros.liquidado_regalado', 'codigoslibros.venta_estado',
            'codigoslibros.codigo_paquete','codigoslibros.permitir_devolver_nota',
            'codigoslibros.prueba_diagnostica', 'codigoslibros.plus',
            'ls.codigo_liquidacion','ls.nombre as nombrelibro',
            'codigoslibros.estado', 'institucion.nombreInstitucion as institucionDirecta','i2.nombreInstitucion as institucionPuntoVenta','pe.periodoescolar',
                'ls.id_serie','a.area_idarea','ls.year'
            )
            ->get();
        //traer el precio
        foreach ($resultados as $item) {
            $periodo = $item->bc_periodo;
            $codigo_proforma = $item->codigo_proforma;
            if($periodo == null || $periodo == 0){
                $item->precio = 0;
            }else{
                // Obtener el precio del libro usando el repositorio
                $precio             = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
                $item->precio       = $precio;
            }
            //PERSEO
            if($codigo_proforma == null || $codigo_proforma == ""){
                $item->proformaEnviadaPerseo = 0;
            }else{
                $venta = Ventas::where('ven_codigo', $codigo_proforma)->where('id_empresa', $item->proforma_empresa)->first();
                if($venta){
                    $estadoPerseo = $venta->estadoPerseo;
                    if($estadoPerseo == 0){
                        $item->proformaEnviadaPerseo = 0;
                    }else{
                        $item->proformaEnviadaPerseo = 1;
                    }
                }else{
                    $item->proformaEnviadaPerseo = 0;
                }
            }
        }

        // Extraer los códigos encontrados
        $codigosEncontrados         = $resultados->pluck('codigo')->toArray();
        // Determinar los códigos que no fueron encontrados
        $codigosNoEncontrados       = array_values(array_diff($codigosABuscar, $codigosEncontrados));
        //solo traer los codigos con prueba diagnostico cero
        $resultados                 = $resultados->where('prueba_diagnostica',0)->values();
        if($mostrarSoloCodigos){
            return [
                "encontrados"    => $resultados,
                "no_encontrados" => $codigosNoEncontrados
            ];
        }
        //agregar codigos sin empresa en arrayCodigosSinEmpresas
        //instruccion:
        //se agrupa por el venta_estado si es 2 se agrupa por venta_lista_institucion si es 1 se agrupa por bc_institucion
        //si es 0 en venta_estado se agrupa por bc_institucion
        //se crea un campo adicional en el resultado llamado institucion_select que es el nombre de la institucion que se debe mostrar en el select
        //se crea un campo adicional en el resultado llamado institucion_id_select que es el id de la institucion que se debe mostrar en el select
        $agrupados = $resultados->groupBy(function ($item) {
            return $item->bc_institucion . '-' . $item->venta_lista_institucion; // Agrupamos por ambos campos
        })->map(function ($items, $key) {
            // Separamos la clave para obtener el bc_institucion y venta_lista_institucion
            list($institucionKey, $ventaKey) = explode('-', $key);

            // Determinamos el nombre a usar en institucion_select
            $nombreInstitucionSeleccionado = $items->first()->venta_estado == '2'
                ? $items->first()->institucionPuntoVenta ?? 'Institución no encontrada'
                : ($items->first()->venta_estado == '0'
                    ? (is_null($ventaKey) || $ventaKey == '0'
                        ? $items->first()->institucionDirecta ?? 'Institución no encontrada'
                        : $items->first()->institucionDirecta ?? 'Institución no encontrada')
                    : $items->first()->institucionDirecta ?? 'Institución no encontrada');

            // institucion_id_select
            $institucionIdSeleccionado = $items->first()->venta_estado == '2'
                ? $ventaKey
                : ($items->first()->venta_estado == '0'
                    ? (is_null($ventaKey) || $ventaKey == '0'
                        ? $institucionKey
                        : $ventaKey)
                    : $institucionKey);

            return [
                'bc_institucion'           => $institucionKey,
                'venta_lista_institucion'  => $ventaKey,
                'institucionDirecta'       => $items->first()->institucionDirecta ?? 'Institución no encontrada',
                'institucionPuntoVenta'    => $items->first()->institucionPuntoVenta ?? 'Institución no encontrada',
                'institucion_select'       => $nombreInstitucionSeleccionado, // Campo adicional
                'institucion_id_select'    => $institucionIdSeleccionado, // Campo adicional
                'data'                     => $items
            ];
        })->values();


        // Resultado
        //agrupar por institucion_id_select
        $agrupadosPorInstitucion = $agrupados->groupBy('institucion_id_select')->map(function ($items, $institucionId) {
            // Obtener el primer item para extraer el institucion_select
            $primerItem = $items->first();

            return [
                'institucion_id_select' => $institucionId,
                'institucion_select'    => $primerItem['institucion_select'] ?? 'Institución no encontrada', // Agregar institucion_select
                'data'                  => $items->pluck('data')->flatten() // Extraer y aplanar el data de cada item
            ];
        })->values();
        //agregar a cada propiedad data un campo adicional institucion_id_select
        foreach ($agrupadosPorInstitucion as &$item) {
            foreach ($item['data'] as &$dataItem) {
                $dataItem->institucion_id_select = $item['institucion_id_select'];
                $dataItem->institucion_select    = $item['institucion_select'];
            }
        }
        $resultados =  [
            'encontrados'    => $agrupadosPorInstitucion, // Resultados encontrados, agrupados por bc_institucion y bc_periodo
            'no_encontrados' => $codigosNoEncontrados // Códigos no encontrados
        ];
        return $resultados;
    }
    //api:post/metodosPostCodigos?getPrevisualizarCodigosTablaSon=1
    public function getPrevisualizarCodigosTablaSon($request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $mostrarSoloCodigos     = $request->mostrarSoloCodigos;
        // Verificar si se decodificó correctamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid JSON data'];
        }
        // Extraer solo los códigos a buscar
        $codigosABuscar = array_column($codigos, 'codigo');
        // Filtrar la colección usando whereIn y hacer un LEFT JOIN
        $resultados = CodigosLibrosDevolucionSon::whereIn('codigo', $codigosABuscar)
            // ->where('proforma_empresa', $empresa['idEmpresa'])
            ->leftJoin('institucion', 'codigoslibros_devolucion_son.id_cliente', '=', 'institucion.idInstitucion')
            ->leftJoin('periodoescolar as pe', 'codigoslibros_devolucion_son.id_periodo', '=', 'pe.idperiodoescolar')
            ->leftJoin('libros_series as ls', 'codigoslibros_devolucion_son.id_libro', '=', 'ls.idLibro')
            ->leftJoin('libro as l', 'ls.idLibro', '=', 'l.idlibro')
            ->leftJoin('asignatura as a', 'l.asignatura_idasignatura', '=', 'a.idasignatura')
            ->leftjoin('codigoslibros_devolucion_header as ch', 'codigoslibros_devolucion_son.codigoslibros_devolucion_id', '=', 'ch.id')
            ->select('codigoslibros_devolucion_son.codigo', 'codigoslibros_devolucion_son.id_periodo as bc_periodo', 'codigoslibros_devolucion_son.id_cliente',
            'codigoslibros_devolucion_son.id_libro','ch.codigo_devolucion as documento_devolucion',
            'codigoslibros_devolucion_son.combo','codigoslibros_devolucion_son.codigo_combo','codigoslibros_devolucion_son.id_empresa','codigoslibros_devolucion_son.documento as codigo_proforma',
            'codigoslibros_devolucion_son.documento_estado_liquidacion as estado_liquidacion','codigoslibros_devolucion_son.documento_regalado_liquidado as liquidado_regalado', 'codigoslibros_devolucion_son.tipo_venta as venta_estado',
            'codigoslibros_devolucion_son.codigo_paquete',
            'codigoslibros_devolucion_son.prueba_diagnostico',
            'ls.codigo_liquidacion','ls.nombre as nombrelibro',
            'institucion.nombreInstitucion','pe.periodoescolar',
                'ls.id_serie','a.area_idarea','ls.year'
            )
            ->get();
        //traer el precio
        foreach ($resultados as $item) {
            $periodo = $item->bc_periodo;
            $codigo_proforma = $item->codigo_proforma;
            if($periodo == null || $periodo == 0){
                $item->precio = 0;
            }else{
                // Obtener el precio del libro usando el repositorio
                $precio             = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->id_libro, $item->area_idarea, $periodo, $item->year);
                $item->precio       = $precio;
            }
            //PERSEO
            if($codigo_proforma == null || $codigo_proforma == ""){
                $item->proformaEnviadaPerseo = 0;
            }else{
                $venta = Ventas::where('ven_codigo', $codigo_proforma)->where('id_empresa', $item->proforma_empresa)->first();
                if($venta){
                    $estadoPerseo = $venta->estadoPerseo;
                    if($estadoPerseo == 0){
                        $item->proformaEnviadaPerseo = 0;
                    }else{
                        $item->proformaEnviadaPerseo = 1;
                    }
                }else{
                    $item->proformaEnviadaPerseo = 0;
                }
            }
        }

        // Extraer los códigos encontrados
        $codigosEncontrados         = $resultados->pluck('codigo')->toArray();
        // Determinar los códigos que no fueron encontrados
        $codigosNoEncontrados       = array_values(array_diff($codigosABuscar, $codigosEncontrados));
        //solo traer los codigos con prueba diagnostico cero
        $resultados                 = $resultados->where('prueba_diagnostica',0)->values();
        if($mostrarSoloCodigos){
            return [
                "encontrados"    => $resultados,
                "no_encontrados" => $codigosNoEncontrados
            ];
        }
    }
    //api:post/metodosPostCodigos?getPrevisualizarPaquetes=1
    public function getPrevisualizarPaquetes($request) {

        // Inicializamos arrays para almacenar los paquetes encontrados y no encontrados
        $arrayPaquetesNoEncontrados = [];

        // Configurar tiempo de ejecución
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        // Verificar si se decodificó correctamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid JSON data'];
        }
        $paquetes       = json_decode($request->data_codigos);
        $mostrarCombos  = $request->mostrarCombos?? null;

        // Extraer solo los códigos a buscar
        $paquetesBuscar = array_column($paquetes, 'codigo');
        $mostrarSoloCodigos     = $request->mostrarSoloCodigos;

        if($mostrarCombos == 1){
            // Buscar códigos en la base de datos
            $codigosPaquete = CombosCodigos::whereIn('codigo', $paquetesBuscar)->get();
        }else{
            // Buscar códigos en la base de datos
            $codigosPaquete = CodigosPaquete::whereIn('codigo', $paquetesBuscar)->get();
        }

        // Extraer los códigos encontrados
        $paquetesEncontrados = $codigosPaquete->pluck('codigo')->toArray();

        // Guardar en array paquetes encontrados
        $arrayPaquetesEncontrados = $paquetesEncontrados;

        // Determinar los códigos que no fueron encontrados
        $paquetesNoEncontrados = array_values(array_diff($paquetesBuscar, $paquetesEncontrados));

        // Almacenar en array paquetes no encontrados
        $arrayPaquetesNoEncontrados = $paquetesNoEncontrados;

        // Realizar la consulta con joins, añadiendo el filtro de prueba_diagnostica
        $query = CodigosLibros::query()
        ->where('prueba_diagnostica', '0') // Filtrar por prueba_diagnostica
        ->leftJoin('institucion', 'codigoslibros.bc_institucion', '=', 'institucion.idInstitucion')
        ->leftJoin('institucion as i2', 'codigoslibros.venta_lista_institucion', '=', 'i2.idInstitucion')
        ->leftJoin('periodoescolar as pe', 'codigoslibros.bc_periodo', '=', 'pe.idperiodoescolar')
        ->leftJoin('libros_series as ls', 'codigoslibros.libro_idlibro', '=', 'ls.idLibro')
        ->leftJoin('libro as l', 'ls.idLibro', '=', 'l.idlibro')
        ->leftJoin('asignatura as a', 'l.asignatura_idasignatura', '=', 'a.idasignatura')
        ->select(
            'codigoslibros.codigo', 'codigoslibros.codigo_union', 'codigoslibros.bc_periodo', 'codigoslibros.bc_institucion',
            'codigoslibros.venta_lista_institucion', 'codigoslibros.libro_idlibro', 'codigoslibros.documento_devolucion',
            'codigoslibros.combo','codigoslibros.codigo_combo', 'codigoslibros.proforma_empresa', 'codigoslibros.codigo_proforma',
            'codigoslibros.estado_liquidacion', 'codigoslibros.liquidado_regalado', 'codigoslibros.venta_estado',
            'codigoslibros.codigo_paquete', 'ls.codigo_liquidacion', 'ls.nombre as nombrelibro',
            'codigoslibros.permitir_devolver_nota', 'codigoslibros.plus',
            'codigoslibros.estado', 'institucion.nombreInstitucion as institucionDirecta',
            'i2.nombreInstitucion as institucionPuntoVenta', 'pe.periodoescolar',
            'ls.id_serie', 'a.area_idarea', 'ls.year'
        )
        //Aquí usamos `when()` para agregar la condición de acuerdo con el valor de `mostrarCombos`
        ->when($mostrarCombos == 1, function ($query) use ($paquetesEncontrados) {
            return $query->whereIn('codigo_combo', $paquetesEncontrados);
        })
        ->when($mostrarCombos != 1, function ($query) use ($paquetesEncontrados) {
            return $query->whereIn('codigo_paquete', $paquetesEncontrados);
        });

        $resultados = $query->get();
        // Traer el precio del libro
        foreach ($resultados as $item) {
            $periodo = $item->bc_periodo;
            $codigo_proforma = $item->codigo_proforma;
            if ($periodo == null || $periodo == 0) {
                $item->precio = 0;
            } else {
                // Obtener el precio del libro usando el repositorio
                $precio = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
                $item->precio = $precio;
            }
            //PERSEO
            if($codigo_proforma == null || $codigo_proforma == ""){
                $item->proformaEnviadaPerseo = 0;
            }else{
                $venta = Ventas::where('ven_codigo', $codigo_proforma)->where('id_empresa', $item->proforma_empresa)->first();
                if($venta){
                    $estadoPerseo = $venta->estadoPerseo;
                    if($estadoPerseo == 0){
                        $item->proformaEnviadaPerseo = 0;
                    }else{
                        $item->proformaEnviadaPerseo = 1;
                    }
                }else{
                    $item->proformaEnviadaPerseo = 0;
                }
            }
        }
        $arrayAgrupadosCombos = [];
        if ($request->agrupadoCombos) {
            $arrayAgrupadosCombos = $resultados->groupBy(function ($item) {
                return $item->codigo_combo;
            })->values()
            ->map(function ($items) {
                return [
                    'codigoCombo'  => $items->first()->codigo_combo,
                    'codigosHijos' => $items->map(function ($item) {
                        return [
                            'codigoActivacion'   => $item->codigo,
                            'codigoDiagnostico'  => $item->codigo_union,
                        ];
                    }),
                ];
            });
        }

        //MOSTRAR SOLO LOS CODIGOS
        if($mostrarSoloCodigos){
            return [
                'paquetesEncontrados'   => $paquetesEncontrados,
                'no_encontrados'        => $arrayPaquetesNoEncontrados,
                'encontrados'           => $resultados,
                'arrayAgrupadosCombos'  => $arrayAgrupadosCombos,
            ];
        }
        // Agrupamos los resultados según la lógica proporcionada
        $agrupados = $resultados->groupBy(function ($item) {
            return $item->bc_institucion . '-' . $item->venta_lista_institucion;
        })->map(function ($items, $key) {
            list($institucionKey, $ventaKey) = explode('-', $key);

            // Determinación del nombre y el ID de la institución
            $nombreInstitucionSeleccionado = $items->first()->venta_estado == '2'
                ? $items->first()->institucionPuntoVenta ?? 'Institución no encontrada'
                : ($items->first()->venta_estado == '0'
                    ? (is_null($ventaKey) || $ventaKey == '0'
                        ? $items->first()->institucionDirecta ?? 'Institución no encontrada'
                        : $items->first()->institucionDirecta ?? 'Institución no encontrada')
                    : $items->first()->institucionDirecta ?? 'Institución no encontrada');

            $institucionIdSeleccionado = $items->first()->venta_estado == '2'
                ? $ventaKey
                : ($items->first()->venta_estado == '0'
                    ? (is_null($ventaKey) || $ventaKey == '0'
                        ? $institucionKey
                        : $ventaKey)
                    : $institucionKey);

            return [
                'bc_institucion'           => $institucionKey,
                'venta_lista_institucion'  => $ventaKey,
                'institucionDirecta'       => $items->first()->institucionDirecta ?? 'Institución no encontrada',
                'institucionPuntoVenta'    => $items->first()->institucionPuntoVenta ?? 'Institución no encontrada',
                'institucion_select'       => $nombreInstitucionSeleccionado,
                'institucion_id_select'    => $institucionIdSeleccionado,
                'data'                     => $items
            ];
        })->values();

        // Agrupamos por institucion_id_select
        $agrupadosPorInstitucion = $agrupados->groupBy('institucion_id_select')->map(function ($items, $institucionId) {
            $primerItem = $items->first();

            return [
                'institucion_id_select' => $institucionId,
                'institucion_select'    => $primerItem['institucion_select'] ?? 'Institución no encontrada',
                'data'                  => $items->pluck('data')->flatten()
            ];
        })->values();

        // Agregar a cada propiedad data un campo adicional institucion_id_select
        foreach ($agrupadosPorInstitucion as &$item) {
            foreach ($item['data'] as &$dataItem) {
                $dataItem->institucion_id_select = $item['institucion_id_select'];
                $dataItem->institucion_select    = $item['institucion_select'];
            }
        }



        // Retornar los resultados finales
        return [
            'paquetesEncontrados'   => $paquetesEncontrados,
            'no_encontrados'        => $arrayPaquetesNoEncontrados,
            'encontrados'           => $agrupadosPorInstitucion,
        ];
    }

    //api:post/metodosPostCodigos?getPrevisualizarPaquetesTablaSon=1
    public function getPrevisualizarPaquetesTablaSon($request) {
        // Inicializamos arrays para almacenar los paquetes encontrados y no encontrados
        $arrayPaquetesNoEncontrados = [];

        // Configurar tiempo de ejecución
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        // Verificar si se decodificó correctamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid JSON data'];
        }
        $paquetes       = json_decode($request->data_codigos);
        // Extraer solo los códigos a buscar
        $paquetesBuscar = array_column($paquetes, 'codigo');
        $mostrarSoloCodigos     = $request->mostrarSoloCodigos;

        // Buscar códigos en la base de datos
        $codigosPaquete = CodigosPaquete::whereIn('codigo', $paquetesBuscar)->get();

        // Extraer los códigos encontrados
        $paquetesEncontrados = $codigosPaquete->pluck('codigo')->toArray();

        // Guardar en array paquetes encontrados
        $arrayPaquetesEncontrados = $paquetesEncontrados;

        // Determinar los códigos que no fueron encontrados
        $paquetesNoEncontrados = array_values(array_diff($paquetesBuscar, $paquetesEncontrados));

        // Almacenar en array paquetes no encontrados
        $arrayPaquetesNoEncontrados = $paquetesNoEncontrados;

        // Realizar la consulta con joins, añadiendo el filtro de prueba_diagnostica
        $resultados = CodigosLibrosDevolucionSon::whereIn('codigo_paquete', $paquetesEncontrados)
            ->leftJoin('institucion', 'codigoslibros_devolucion_son.id_cliente', '=', 'institucion.idInstitucion')
            ->leftJoin('periodoescolar as pe', 'codigoslibros_devolucion_son.id_periodo', '=', 'pe.idperiodoescolar')
            ->leftJoin('libros_series as ls', 'codigoslibros_devolucion_son.id_libro', '=', 'ls.idLibro')
            ->leftJoin('libro as l', 'ls.idLibro', '=', 'l.idlibro')
            ->leftJoin('asignatura as a', 'l.asignatura_idasignatura', '=', 'a.idasignatura')
            ->leftjoin('codigoslibros_devolucion_header as ch', 'codigoslibros_devolucion_son.codigoslibros_devolucion_id', '=', 'ch.id')
            ->select('codigoslibros_devolucion_son.codigo', 'codigoslibros_devolucion_son.id_periodo as bc_periodo', 'codigoslibros_devolucion_son.id_cliente',
            'codigoslibros_devolucion_son.id_libro','ch.codigo_devolucion as documento_devolucion',
            'codigoslibros_devolucion_son.combo','codigoslibros_devolucion_son.codigo_combo','codigoslibros_devolucion_son.id_empresa','codigoslibros_devolucion_son.documento as codigo_proforma',
            'codigoslibros_devolucion_son.documento_estado_liquidacion as estado_liquidacion','codigoslibros_devolucion_son.documento_regalado_liquidado as liquidado_regalado', 'codigoslibros_devolucion_son.tipo_venta as venta_estado',
            'codigoslibros_devolucion_son.codigo_paquete',
            'codigoslibros_devolucion_son.prueba_diagnostico',
            'ls.codigo_liquidacion','ls.nombre as nombrelibro',
            'institucion.nombreInstitucion','pe.periodoescolar',
                'ls.id_serie','a.area_idarea','ls.year'
            )
            ->get();

        // Traer el precio del libro
        foreach ($resultados as $item) {
            $periodo = $item->bc_periodo;
            $codigo_proforma = $item->codigo_proforma;
            if ($periodo == null || $periodo == 0) {
                $item->precio = 0;
            } else {
                // Obtener el precio del libro usando el repositorio
                $precio = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->id_libro, $item->area_idarea, $periodo, $item->year);
                $item->precio = $precio;
            }
            //PERSEO
            if($codigo_proforma == null || $codigo_proforma == ""){
                $item->proformaEnviadaPerseo = 0;
            }else{
                $venta = Ventas::where('ven_codigo', $codigo_proforma)->where('id_empresa', $item->proforma_empresa)->first();
                if($venta){
                    $estadoPerseo = $venta->estadoPerseo;
                    if($estadoPerseo == 0){
                        $item->proformaEnviadaPerseo = 0;
                    }else{
                        $item->proformaEnviadaPerseo = 1;
                    }
                }else{
                    $item->proformaEnviadaPerseo = 0;
                }
            }
        }
        //MOSTRAR SOLO LOS CODIGOS
        if($mostrarSoloCodigos){
            return [
                'paquetesEncontrados'   => $paquetesEncontrados,
                'no_encontrados'        => $arrayPaquetesNoEncontrados,
                'encontrados'           => $resultados,
            ];
        }
    }

    //api:post/metodosPostCodigos?saveImportPlus=1
    public function saveImportPlus(Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        $request->validate([
            'data_codigos' => 'required|json',
            'periodo_id'   => 'required|integer',
            'id_usuario'   => 'required|integer',
            'comentario'   => 'required|string',
            'tipo'         => 'required|integer',
        ]);

        $codigos            = json_decode($request->data_codigos);
        $periodo_id         = $request->periodo_id;
        $id_usuario         = $request->id_usuario;
        $comentario         = $request->comentario;
        $tipo               = $request->tipo;
        $permitirRegalados  = $request->permitirRegalados;

        $codigosNoCambiados = [];
        $contador           = 0;

        try {
            DB::beginTransaction();

            $configuracionGeneral = ConfiguracionGeneral::find(8);
            if (!$configuracionGeneral) {
                return response()->json(['status' => '0', 'message' => 'No se encontró la configuración general'], 200);
            }

            $id_serieConfigurada = $configuracionGeneral->id_seleccion;

            $codigosDB = CodigosLibros::whereIn('codigo', array_column($codigos, 'codigo'))
                ->leftJoin('libros_series', 'codigoslibros.libro_idlibro', '=', 'libros_series.idLibro')
                ->select('codigoslibros.*', 'libros_series.id_serie')
                ->get()
                ->keyBy('codigo');

            foreach ($codigos as $item) {
                if (!isset($item->codigo)) {
                    $item->mensaje = "El código no existe";
                    $codigosNoCambiados[] = $item->codigo ?? 'Desconocido';
                    continue;
                }

                $codigo = $codigosDB[$item->codigo] ?? null;

                if ($codigo) {
                    $old_values = $codigo->getOriginal();

                    if ($codigo->id_serie != $id_serieConfigurada) {
                        $item->mensaje = "El código no pertenece a la serie plus configurada";
                        $codigosNoCambiados[] = $item;
                        continue;
                    }

                    if ($permitirRegalados == 0 && $codigo->estado_liquidacion == '2') {
                        $item->mensaje = "El código ya se encuentra en estado regalado";
                        $codigosNoCambiados[] = $item;
                        continue;
                    }

                    $codigo->plus = ($tipo == 0) ? 1 : 0;
                    $codigo->save();

                    if ($codigo->codigo_union) {
                        $codigoUnion = CodigosLibros::where('codigo', $codigo->codigo_union)->first();
                        if ($codigoUnion) {
                            $old_valuesUnion = $codigoUnion->getOriginal();
                            $codigoUnion->plus = ($tipo == 0) ? 1 : 0;
                            $codigoUnion->save();

                            $this->GuardarEnHistorico(
                                0, 0, $periodo_id,
                                $codigo->codigo_union,
                                $id_usuario,
                                $comentario,
                                json_encode($old_valuesUnion),
                                json_encode($codigoUnion->getAttributes())
                            );
                        }
                    }

                    $contador++;
                    $this->GuardarEnHistorico(
                        0, 0, $periodo_id,
                        $codigo->codigo,
                        $id_usuario,
                        $comentario,
                        json_encode($old_values),
                        json_encode($codigo->getAttributes())
                    );
                } else {
                    $item->mensaje = "El código no existe";
                    $codigosNoCambiados[] = $item->codigo;
                }
            }

            DB::commit();

            return response()->json([
                'status'             => '1',
                'message'            => 'Se guardaron correctamente',
                'codigosNoCambiados' => $codigosNoCambiados,
                'totalCodigos'       => count($codigos),
                'cambiados'          => $contador,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => '0',
                'message' => 'Hubo un error: ' . $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 200);
        }
    }


    //api:post/metodosPostCodigos?saveImportGuias=1
    public function saveImportGuias($request){
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        $request->validate([
            'data_codigos'      => 'required|json',
            'id_usuario'        => 'required|integer',
            'comentario'        => 'required|string',
        ]);

        $codigos                = json_decode($request->data_codigos);
        $periodo_id             = 0;
        $id_usuario             = $request->id_usuario;
        $comentario             = $request->comentario;
        $periodo_id             = $request->periodo_id ?? 0;

        $codigosNoCambiados     = [];
        $contador               = 0;

        try {
            DB::beginTransaction();

            $codigosDB = CodigosLibros::whereIn('codigo', array_column($codigos, 'codigo'))
                ->leftJoin('libros_series', 'codigoslibros.libro_idlibro', '=', 'libros_series.idLibro')
                ->select('codigoslibros.*', 'libros_series.id_serie')
                ->get()
                ->keyBy('codigo');

            foreach ($codigos as $item) {
                if (!isset($item->codigo)) {
                    $item->mensaje        = Codigoslibros::CODIGO_NO_EXISTE;
                    $codigosNoCambiados[] = $item->codigo ?? 'Desconocido';
                    continue;
                }

                $codigo = $codigosDB[$item->codigo] ?? null;
                if ($codigo) {
                    $old_values = $codigo->getOriginal(); // ✅ Obtiene una copia real de los valores antes de cambiar nada
                    $getPeriodo = $codigo->bc_periodo ?? $periodo_id;
                    if ($codigo->estado_liquidacion  == 0) {
                        $item->mensaje = Codigoslibros::CODIGO_LIQUIDADO;
                        $codigosNoCambiados[] = $item;
                        continue;
                    }

                    // marcar como codigo guia
                    $codigo->estado_liquidacion = 4;
                    $codigo->save();

                    // Guardar en historico
                    if ($codigo->codigo_union) {
                        $codigoUnion = CodigosLibros::where('codigo', $codigo->codigo_union)->first();
                        $old_valuesUnion = $codigoUnion->getOriginal(); // ✅ Obtiene una copia real de los valores antes de cambiar nada
                        if ($codigoUnion) {
                            $codigoUnion->estado_liquidacion = 4;
                            $codigoUnion->save();
                            $this->GuardarEnHistorico(0, 0, $getPeriodo, $codigo->codigo_union, $id_usuario, $comentario, json_encode($old_valuesUnion), json_encode($codigoUnion->getAttributes()));
                        }
                    }

                    $contador++;
                    $this->GuardarEnHistorico(0, 0, $getPeriodo, $codigo->codigo, $id_usuario, $comentario, json_encode($old_values), json_encode($codigo->getAttributes()));
                } else {
                    $item->mensaje        = Codigoslibros::CODIGO_NO_EXISTE;
                    $codigosNoCambiados[] = $item->codigo;
                }
            }

            DB::commit();

            return response()->json([
                'status'                => '1',
                'message'               => 'Se guardaron correctamente',
                'codigosNoCambiados'    => $codigosNoCambiados,
                'totalCodigos'          => count($codigos),
                'cambiados'             => $contador,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => '0',
                'message' => 'Hubo un error: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 200);
        }
    }
    //api:post/codigos/asignarCombos
    public function asignarCombos(Request $request) {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);

        $codigos = json_decode($request->data_codigos);
        $combo = $request->combo;

        try {
            DB::beginTransaction();

            // Obtener los códigos a buscar
            $codigosBuscar = array_column($codigos, 'codigo');

            // Buscar códigos en la base de datos
            $busqueda = CodigosLibros::whereIn('codigo', $codigosBuscar)->get();

            // Extraer los códigos encontrados
            $codigosEncontrados = $busqueda->pluck('codigo')->toArray();

            // Determinar los códigos que no fueron encontrados
            $codigosNoEncontrados = array_values(array_diff($codigosBuscar, $codigosEncontrados));

            // Actualizar el campo 'combo' de los códigos encontrados
            $contadorEditados = 0;
            foreach ($busqueda as $codigo) {
                $codigo->combo = $combo; // Asignar el nuevo combo
                if ($codigo->save()) { // Guardar cambios en la base de datos
                    $contadorEditados++; // Contar cuántos se editaron
                }
            }

            DB::commit();

            return response()->json([
                "status" => 1,
                "message" => "Operación exitosa",
                "codigoNoExiste" => $codigosNoEncontrados,
                "cambiados" => $contadorEditados
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => 0,
                "message" => $e->getMessage()
            ], 200);
        }
    }

    //INICIO METODOS JEYSON
    public function reporteBodega_new($request){
        $periodo            = $request->input('periodo');
        $activos            = $request->input('activos');
        $regalados          = $request->input('regalados');
        $bloqueados         = $request->input('bloqueados');
        $puntoVenta         = $request->input('puntoVenta');
        $puntoVentaActivos  = $request->input('puntoVentaActivos');
        $serie              = $request->input('serie');
        $plus               = 0;
        if($serie){
            $getPlus = ConfiguracionGeneral::where('id_seleccion_padre',$serie)->first();
            if($getPlus){
                $plus = $getPlus->id_seleccion;
            }
        }
        // Realizar la consulta
        $arrayCodigosActivos = CodigosLibros::select(
            'libros_series.codigo_liquidacion AS codigo',
            DB::raw('COUNT(libros_series.codigo_liquidacion) AS cantidad'),
            'codigoslibros.serie',
            'codigoslibros.libro_idlibro',
            'libros_series.nombre AS nombrelibro',
            'libros_series.year',
            'libros_series.id_serie',
            'asignatura.area_idarea'
        )
        ->leftJoin('libros_series', 'libros_series.idLibro', '=', 'codigoslibros.libro_idlibro')
        ->leftJoin('libro', 'libro.idlibro', '=', 'libros_series.idLibro')
        ->leftJoin('asignatura', 'asignatura.idasignatura', '=', 'libro.asignatura_idasignatura')
        ->where('codigoslibros.prueba_diagnostica', '0')
        ->where('codigoslibros.bc_periodo', $periodo)
        ->when($activos, function ($query) {
            $query->where(function ($query) {
                $query->where('codigoslibros.estado_liquidacion', '1')
                      ->orWhere('codigoslibros.estado_liquidacion', '0')
                      ->orWhere('codigoslibros.estado_liquidacion', '2');
            });
            // ->where('codigoslibros.estado', '<>', '2'); // Comparar como string si la columna es de tipo string
        })
        ->when($regalados, function ($query) {
            $query->where('codigoslibros.estado_liquidacion', '2')
                  ->where('codigoslibros.estado', '<>', '2'); // Comparar como string si la columna es de tipo string
        })
        ->when($bloqueados, function ($query) {
            $query->where('codigoslibros.estado_liquidacion','<>', '3')
                  ->where('codigoslibros.estado', '2'); // Comparar como string si la columna es de tipo string
        })
        ->when($puntoVenta, function ($query) {
            $query->where('codigoslibros.venta_lista_institucion', request('puntoVenta'))
                  ->where(function ($query) {
                      $query->where('codigoslibros.estado_liquidacion', '0')
                            ->orWhere('codigoslibros.estado_liquidacion', '1')
                            ->orWhere('codigoslibros.estado_liquidacion', '2')
                            ->orWhere('codigoslibros.estado', '2');
                  });
        })
        ->when($puntoVentaActivos, function ($query) {
            $query->where('codigoslibros.venta_lista_institucion', request('puntoVentaActivos'))
                  ->where(function ($query) {
                      $query->where('codigoslibros.estado_liquidacion', '0')
                            ->orWhere('codigoslibros.estado_liquidacion', '1')
                            ->orWhere('codigoslibros.estado_liquidacion', '2');
                  });
        })
        ->when($serie && $plus == 0, function ($query) {
            $query->where('libros_series.id_serie', request('serie'))
            ->where('estado_liquidacion','<>','3')
            ->where('plus','0');
        })
        ->when($serie && $plus > 0, function ($query) use ($plus) {
            $query->where('libros_series.id_serie', $plus)
            ->where('estado_liquidacion','<>','3')
            ->where('plus','1');
        })
        ->groupBy('libros_series.codigo_liquidacion', 'libros_series.nombre', 'codigoslibros.serie', 'codigoslibros.libro_idlibro', 'libros_series.year', 'libros_series.id_serie', 'asignatura.area_idarea')
        ->get();

        // Procesar los resultados para obtener el precio y multiplicar por la cantidad
        foreach ($arrayCodigosActivos as $item) {
            // Obtener el precio del libro usando el repositorio
            $precio = $this->pedidosRepository->getPrecioXLibro_new($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
            $item->precio       = $precio;
            // Multiplicar el precio por la cantidad
            $item->precio_total = number_format($precio * $item->cantidad, 2, '.', '');
        }

        return $arrayCodigosActivos;
    }

    //api:get/metodosGetCodigos?reporteBodegaCombos_new=1&periodo=25&combos=1
    public function reporteBodegaCombos_new($request){
        $periodo            = $request->input('periodo');
        $combos             = $request->input('combos');
        $puntoVenta         = $request->input('puntoVenta');
        $puntoVentaCombo    = $request->input('puntoVentaCombo');
        $result = DB::table('f_detalle_venta as v')
        ->leftJoin('f_venta as d', function($join) {
            $join->on('v.ven_codigo', '=', 'd.ven_codigo')
                 ->on('v.id_empresa', '=', 'd.id_empresa');
        })
        ->leftJoin('1_4_cal_producto as p', 'v.pro_codigo', '=', 'p.pro_codigo')
        ->leftJoin('libros_series as ls', 'ls.codigo_liquidacion', '=', 'p.pro_codigo')
        ->leftJoin('libro as l', 'l.idlibro', '=', 'ls.idLibro')
        ->leftJoin('asignatura as a', 'a.idasignatura', '=', 'l.asignatura_idasignatura')
        ->select(
            'v.pro_codigo as codigo',
            'p.pro_nombre as nombrelibro',
            'ls.idLibro as libro_idlibro',
            'ls.year',
            'ls.id_serie',
            'a.area_idarea',
            'p.codigos_combos',
            'p.ifcombo',
            DB::raw('SUM(v.det_ven_cantidad) as cantidad'),
            DB::raw('SUM(v.det_ven_dev) as cantidad_devuelta'),
            DB::raw('SUM(v.det_ven_cantidad) - SUM(v.det_ven_dev) as cantidad')
        )
        ->where('d.periodo_id', $periodo)
        ->when($combos, function ($query) {
            $query->where('p.ifcombo', '1');
        })
        ->when($puntoVenta, function ($query) {
            $query->where('d.institucion_id', '=', request('puntoVenta'));
        })
        ->when($puntoVentaCombo, function ($query) {
            $query->where('d.institucion_id', '=', request('puntoVentaCombo'))
           ->where('p.ifcombo', '1');
        })
        ->where('d.est_ven_codigo','<>','3')
        ->groupBy('v.pro_codigo', 'p.pro_nombre', 'ls.idLibro', 'ls.year', 'ls.id_serie', 'a.area_idarea', 'p.codigos_combos')
        ->get();
        // Procesar los resultados para obtener el precio y multiplicar por la cantidad
        foreach ($result as $item) {
            // Obtener el precio del libro usando el repositorio
            $precio             = $this->pedidosRepository->getPrecioXLibro_new($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
            $item->precio       = $precio;
            // Multiplicar el precio por la cantidad
            $item->precio_total = number_format($precio * $item->cantidad, 2, '.', '');
        }
        return $result;
    }
     //api:get/metodosGetCodigos?getReporteLibrosAsesores_new=1&periodo=24&codigo=SM1
     public function getReporteLibrosAsesores_new($request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $periodo        = $request->periodo;
        $codigoBusqueda = $request->codigo;
        // $GuiasBodega   = $this->codigosRepository->getCodigosBodega(1,$periodo,0,4179);
        // return $GuiasBodega;
        $val_pedido2 = DB::SELECT("SELECT DISTINCT p.id_asesor, CONCAT(u.nombres,' ',u.apellidos) as asesor
        FROM pedidos_val_area_new pv
        LEFT JOIN pedidos p ON pv.id_pedido = p.id_pedido
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        where p.tipo        = '1'
        and p.id_periodo  = '$periodo'
        AND p.estado        = '1'
        AND p.estado_entrega = '2'
        GROUP BY u.nombres ORDER BY u.nombres
        ");
        foreach($val_pedido2 as $key11 => $itemAsesor){
            $guias = $this->codigosRepository->getLibrosAsesores_new($periodo,$itemAsesor->id_asesor);
            $resultado = [];
            //filtrar por el libro_id = 652 los libros
            $guiasPedidos = collect($guias)->where('codigo',$codigoBusqueda)->values();
            if(count($guiasPedidos) == 0){
                $GuiasBodega   = $this->codigosRepository->getCodigosBodega_new(1,$periodo,0,$itemAsesor->id_asesor);
                //filtrar por codigo
                $resultado          = collect($GuiasBodega)->where('codigo',$codigoBusqueda)->values();
            }else{
                $getBodega   = $this->codigosRepository->getCodigosBodega_new(1,$periodo,0,$itemAsesor->id_asesor);
                $GuiasBodega = collect($getBodega)->where('codigo',$codigoBusqueda)->values();
                if(count($GuiasBodega) == 0){
                    $resultado = $guiasPedidos;
                }else{
                    $resultado = $guiasPedidos;
                    $resultado[0]->valor = $resultado[0]->valor + $GuiasBodega[0]->cantidad;
                }
            }
            //guardar un campo totalguias obtener el [0]->valor
            $itemAsesor->guias      = $resultado;
            if(count($resultado) == 0){
                $itemAsesor->totalguias = 0;
            }else{
                $itemAsesor->totalguias = $resultado[0]->valor;
            }
            ////ESCUELAS
            $pedidos = $this->tr_institucionesAsesorPedidos($periodo,$itemAsesor->id_asesor);
            // Agregar los resultados actuales al array acumulativo
            // $resultadoFinal[] = [
            //     'saludo' => "hola",
            //     'val_pedido2' => $guias
            // ];
            foreach($pedidos as $key8 => $itempedido){
                $val_pedido = DB::table('pedidos_val_area_new as pv')
                ->selectRaw('DISTINCT pv.pvn_cantidad as valor,
                            CASE
                                WHEN se.id_serie = 6 THEN l.idlibro
                                ELSE ar.idarea
                            END as id_area,
                            se.id_serie,
                            CASE
                                WHEN se.id_serie = 6 THEN 0
                                ELSE ls.year
                            END as year,
                            CASE
                                WHEN se.id_serie = 6 THEN l.idlibro
                                ELSE 0
                            END as plan_lector,
                            pv.pvn_tipo as alcance,
                            p.id_periodo,
                            CASE
                                WHEN se.id_serie = 6 THEN NULL
                                ELSE CONCAT(se.nombre_serie, " ", ar.nombrearea)
                            END as serieArea,
                            se.nombre_serie,
                            ls.codigo_liquidacion as codigo,
                            l.nombrelibro,
                            l.idlibro,
                            l.descripcionlibro')  // Añadimos el campo plan_lector
                ->leftJoin('libro as l', 'pv.idlibro', '=', 'l.idlibro')
                ->leftJoin('libros_series as ls', 'pv.idlibro', '=', 'ls.idLibro')
                ->leftJoin('asignatura as asi', 'l.asignatura_idasignatura', '=', 'asi.idasignatura')
                ->leftJoin('area as ar', 'asi.area_idarea', '=', 'ar.idarea')
                ->leftJoin('series as se', 'ls.id_serie', '=', 'se.id_serie')
                ->leftJoin('pedidos as p', 'pv.id_pedido', '=', 'p.id_pedido')
                ->leftJoin('usuario as u', 'p.id_asesor', '=', 'u.idusuario')
                ->where('p.id_pedido', $itempedido->id_pedido)
                ->where('p.tipo', '0')
                ->where('p.estado', '1')
                ->where('p.id_periodo', $periodo)
                ->groupBy('pv.pvn_id')
                ->get();
                if(empty($val_pedido)){
                    // return $val_pedido;
                }else{
                    $arreglo = [];
                    $cont    = 0;
                    //obtener solo los alcances activos
                    foreach($val_pedido as $k => $tr){
                        //Cuando es el pedido original
                        $alcance_id = 0;
                        $alcance_id = $tr->alcance;
                        if($alcance_id == 0){
                            $arreglo[$cont] =   (object)[
                                "valor"             => $tr->valor,
                                "id_area"           => $tr->id_area,
                                "id_serie"          => $tr->id_serie,
                                "year"              => $tr->year,
                                "plan_lector"       => $tr->plan_lector,
                                "id_periodo"        => $tr->id_periodo,
                                "serieArea"         => $tr->serieArea,
                                "nombre_serie"      => $tr->nombre_serie,
                                "codigo"            => $tr->codigo,
                                "idlibro"           => $tr->idlibro,
                                "nombrelibro"       => $tr->nombrelibro,
                                "descripcionlibro"  => $tr->descripcionlibro,
                                "alcance"           => $tr->alcance,
                                "alcance"           => $alcance_id,
                            ];
                        }else{
                            //validate que el alcance este cerrado o aprobado
                            $query = $this->codigosRepository->getAlcanceAbiertoXId($alcance_id);
                            if(count($query) > 0){
                                $arreglo[$cont] = (object) [
                                    "valor"             => $tr->valor,
                                    "id_area"           => $tr->id_area,
                                    "id_serie"          => $tr->id_serie,
                                    "year"              => $tr->year,
                                    "plan_lector"       => $tr->plan_lector,
                                    "id_periodo"        => $tr->id_periodo,
                                    "serieArea"         => $tr->serieArea,
                                    "nombre_serie"      => $tr->nombre_serie,
                                    "codigo"            => $tr->codigo,
                                    "idlibro"           => $tr->idlibro,
                                    "nombrelibro"       => $tr->nombrelibro,
                                    "descripcionlibro"  => $tr->descripcionlibro,
                                    "alcance"           => $tr->alcance,
                                    "alcance"           => $alcance_id,
                                ];
                            }
                        }
                        $cont++;
                    }

                    //mostrar el arreglo bien
                    $renderSet = [];
                    $renderSet = array_values($arreglo);
                    if(count($renderSet) == 0){
                        return $renderSet;
                    }
                    $datos = [];
                    $contador = 0;
                    //return $renderSet;
                    foreach($renderSet as $item){
                        $valores = [];
                        $pfn_pvp_result = (float) DB::table('pedidos_formato_new')
                        ->where('idperiodoescolar', $item->id_periodo)
                        ->where('idlibro', $item->idlibro)
                        ->value('pfn_pvp');

                        // Obtener los valores de pro_stock y pro_deposito
                        $stock_producto = DB::table('1_4_cal_producto')
                        ->where('pro_codigo', $item->codigo)
                        ->select('pro_reservar')
                        ->first();
                        $datos[$contador] = (Object)[
                            "id_area"           => $item->id_area,
                            "valor"             => $item->valor,
                            // "tipo_val"          => $item->tipo_val,
                            "id_serie"          => $item->id_serie,
                            // "year"              => $item->year,
                            // "anio"              => $valores[0]->year,
                            // "version"           => $valores[0]->version,
                            // "plan_lector"       => $item->plan_lector,
                            "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$item->nombrelibro : $item->serieArea,
                            "libro_id"          => $item->idlibro,
                            "nombrelibro"       => $item->nombrelibro,
                            "nombre_serie"      => $item->nombre_serie,
                            "precio"            => $pfn_pvp_result,
                            "codigo"            => $item->codigo,
                            "stock"             => $stock_producto->pro_reservar,
                            "descripcion"       => $item->descripcionlibro,
                        ];
                        $contador++;
                    }
                       //si el codigo de liquidacion se repite sumar en el valor
                    // Crear un array asociativo para agrupar por codigo_liquidacion
                    $grouped = [];

                    foreach ($datos as $item) {
                        $codigo = $item->codigo;

                        if (!isset($grouped[$codigo])) {
                            $grouped[$codigo] = $item;
                        } else {
                            $grouped[$codigo]->valor += $item->valor;
                        }
                    }

                    // Convertir el array asociativo de nuevo a un array indexado
                    $result = array_values($grouped);
                    //subtotal
                    foreach($result as $key => $item){
                        $result[$key]->subtotal = $item->valor * $item->precio;
                    }
                    //filtrar por el codigo
                    $resultadoLibros = collect($result)->where('codigo',$codigoBusqueda)->values();
                    $itempedido->librosEscuela = $resultadoLibros;
                    // return [
                    //     'saludo' => "hola",
                    //     'val_pedido2' => $renderSet
                    // ];
                }
            }
            //excluyo dentro del array de pedidos los que tiene la propiedad librosEscuela length == 0
            $pedidos = collect($pedidos)->filter(function ($value, $key) {
                return count($value->librosEscuela) > 0;
            })->values();
            $val_pedido2[$key11]->pedidos = $pedidos;
            // $val_pedido2[$key11]->pedidos = $pedidos;
            //contar en los pedidos cuantos librosEscuela mayor a 0 hay
            $contador = 0;
            foreach($pedidos as $key => $item20){
                if(count($item20->librosEscuela) > 0){
                    $contador++;
                }
            }
            $val_pedido2[$key11]->totalLibrosConPedido = $contador;
        }
        // return $resultadoFinal;
        return $val_pedido2;
    }
    //FIN METODOS JEYSON
    //api:post/restaurarALiquidado_devueltos
    public function restaurarALiquidado_devueltos(Request $request)
    {
        // Limpiar caché
        Cache::flush();

        $codigo = $request->codigo;
        $contrato = $request->contrato;
        $usuario_editor = $request->id_usuario;
        $comentario = "Restaurar código $codigo del contrato $contrato";

        try {
            DB::beginTransaction(); // Inicia la transacción

            $getCodigo = CodigosLibros::where('codigo', $codigo)->first();

            // Verificar si el código existe
            if (!$getCodigo) {
                return response()->json([
                    'status' => 0,
                    'message' => 'El código no existe.',
                ], 200);
            }

            // Clonar los valores originales antes de modificarlos
            $old_ValuesA = $getCodigo->toArray();
            $old_ValuesD = null;

            $getquitar_de_reporte = $getCodigo->quitar_de_reporte;
            $bc_institucion       = $getCodigo->bc_institucion;
            $bc_periodo           = $getCodigo->bc_periodo;
            $estado_liquidacion   = $getCodigo->estado_liquidacion;
            $contratoCodigo       = $getCodigo->contrato;
            //el contratoCodigo debe ser igual al contrato que se está restaurando
            if($contratoCodigo != $contrato){
                return response()->json([
                    'status' => 0,
                    'message' => 'El contrato del código no coincide con el contrato que se está restaurando.',
                ], 200);
            }
            if ($getquitar_de_reporte == '1' && ($estado_liquidacion == '0' || $estado_liquidacion == '2')) {
                // Activación
                $getCodigo->quitar_de_reporte = '0';
                $getCodigo->save();

                // Actualizar diagnóstico
                $getCodigoUnion = CodigosLibros::where('codigo_union', $codigo)->first();
                if ($getCodigoUnion) {
                    $old_ValuesD = $getCodigoUnion->toArray(); // Clonar valores originales
                    $getCodigoUnion->quitar_de_reporte = '0';
                    $getCodigoUnion->save();
                }
            } else {
                // Restaurar estado de liquidación
                $getCodigoUnion = CodigosLibros::where('codigo_union', $codigo)->first();
                if ($getCodigoUnion) {
                    $old_ValuesD = $getCodigoUnion->toArray(); // Clonar valores originales
                    $getCodigoUnion->quitar_de_reporte = '0';
                    $getCodigoUnion->estado_liquidacion = '0';
                    $getCodigoUnion->bc_estado = '2';
                    $getCodigoUnion->save();
                }

                DB::table('codigoslibros')
                    ->where('codigo', '=', $codigo)
                    ->update([
                        'estado_liquidacion' => '0',
                        'bc_estado'          => '2',
                        'quitar_de_reporte'  => '0',
                    ]);
            }

            // Quitar del histórico
            HistoricoCodigos::where('codigo_libro', $codigo)
                ->where('devueltos_liquidados', $contrato)
                ->update([
                    'devueltos_liquidados' => null,
                ]);

            if ($getCodigo->codigo_union) {
                HistoricoCodigos::where('codigo_libro', $getCodigo->codigo_union)
                    ->where('devueltos_liquidados', $contrato)
                    ->update([
                        'devueltos_liquidados' => null,
                    ]);
                $this->GuardarEnHistorico(0, $bc_institucion, $bc_periodo, $getCodigo->codigo_union, $usuario_editor, $comentario, json_encode($old_ValuesD), json_encode($getCodigoUnion->getAttributes()), null);
            }

            // Guardar en la tabla histórico
            $this->GuardarEnHistorico(0, $bc_institucion, $bc_periodo, $codigo, $usuario_editor, $comentario, json_encode($old_ValuesA), json_encode($getCodigoUnion->getAttributes()), null);

            DB::commit(); // Confirma la transacción

            return response()->json([
                'status' => 1,
                'message' => 'Se restauró el código con éxito.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción en caso de error

            return response()->json([
                'status' => 0,
                'message' => 'Error al restaurar el código.',
                'error' => $e->getMessage(),
            ], 200);
        }
    }
}
