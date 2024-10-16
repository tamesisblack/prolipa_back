<?php
namespace App\Repositories\Codigos;

use App\Models\_14Producto;
use App\Models\CodigosDevolucion;
use App\Models\CodigosLibros;
use App\Models\DetalleVentas;
use App\Repositories\BaseRepository;
use App\Repositories\Facturacion\ProformaRepository;
use App\Repositories\pedidos\PedidosRepository;
use DB;
class  CodigosRepository extends BaseRepository
{
    protected $proformaRepository;
    protected $pedidosRepository = null;
    public function __construct(CodigosLibros $CodigoRepository,ProformaRepository $proformaRepository,PedidosRepository $pedidosRepository)
    {
        parent::__construct($CodigoRepository);
        $this->proformaRepository = $proformaRepository;
        $this->pedidosRepository  = $pedidosRepository;
    }

    public function procesoUpdateGestionBodega($numeroProceso,$codigo,$union,$request,$factura,$paquete=null,$ifChangeProforma=false,$datosProforma=[]){
        $venta_estado = $request->venta_estado;
        $arrayInstitucionGestion = [];
        if($venta_estado == 1){
            $arrayInstitucionGestion = ["bc_institucion" => $request->institucion_id ,"venta_lista_institucion" => 0];
        }
        if($venta_estado == 2){
            $arrayInstitucionGestion = ["venta_lista_institucion" => $request->institucion_id ,"bc_institucion" => 0];
        }

        $fecha                   = date("Y-m-d H:i:s");
        $arrayResutado           = [];
        $arraySave               = [];
        $arrayUnion              = [];
        $arrayPaquete            = [];
        $arrayPaqueteInstitucion = [];
        $arrayPaqueteVentaEstado = [];
        $arrayProforma           = [];
        $arrayCombo              = [];
        //combo
        $ifSetCombo              = $request->ifSetCombo;
        $combo                   = $request->comboSelected;
        //si ifSetCombo es 1 coloco el combo
        if($ifSetCombo == 1)            { $arrayCombo  = [ 'combo' => $combo]; }
        //paquete
        if($paquete == null)            { $arrayPaquete = []; }else{ $arrayPaquete  = [ 'codigo_paquete' => $paquete, 'fecha_registro_paquete'    => $fecha]; }
        //codigo de union
        if($union == null)              { $arrayUnion  = [];  } else{ $arrayUnion  = [ 'codigo_union' => $union ]; }
        //si proforma es true
        if($ifChangeProforma == false)  { $arrayProforma = []; } else{ $arrayProforma = [ 'codigo_proforma' => $datosProforma['codigo_proforma'], 'proforma_empresa' => $datosProforma['proforma_empresa'] ]; }
        //para import de gestion de paquetes si envia la institucion
        if($request->institucion_id)    { $arrayPaqueteInstitucion = [ 'bc_institucion' => $request->institucion_id, 'bc_periodo' => $request->periodo_id, 'venta_lista_institucion' => 0 ];   }
        //para import de gestion de paquetes si el estado de venta directa
        if($venta_estado == 1)          { $arrayPaqueteVentaEstado = [ 'venta_estado' => $request->venta_estado ]; }
        //Usan y liquidan
        if($numeroProceso == '0'){
            $arraySave = [
                'factura'           => $factura,
                'bc_periodo'        => $request->periodo_id,
                'venta_estado'      => $request->venta_estado,
            ];
            //arrayInstitucionGestion
            $arraySave = array_merge($arraySave, $arrayInstitucionGestion);
        }
        //regalado
        else if($numeroProceso == '1'){
            $arraySave  = [
                'factura'               => $factura,
                'bc_estado'             => '1',
                'estado_liquidacion'    => '2',
                'bc_periodo'            => $request->periodo_id,
                'venta_estado'          => $venta_estado,
            ];
            //arrayInstitucionGestion
            $arraySave = array_merge($arraySave, $arrayInstitucionGestion);
        }
        //regalado y bloqueado
        else if($numeroProceso == '2'){
            $arraySave  = [
                'factura'               => $factura,
                'bc_estado'             => '1',
                'estado'                => '2',
                'estado_liquidacion'    => '2',
                'bc_periodo'            => $request->periodo_id,
                'venta_estado'          => $venta_estado,
            ];
            //arrayInstitucionGestion
            $arraySave = array_merge($arraySave, $arrayInstitucionGestion);
        }
        //bloqueado
        else if($numeroProceso == '3'){
            $arraySave  = [
                'factura'                   => $factura,
                'bc_estado'                 => '1',
                'estado'                    => '2',
                'bc_periodo'                => $request->periodo_id,
                'venta_estado'              => $venta_estado,
            ];
            //arrayInstitucionGestion
            $arraySave = array_merge($arraySave, $arrayInstitucionGestion);
        }
        //guia
        else if($numeroProceso == '4'){
            $arraySave  = [
                'factura'                   => $factura,
                'bc_periodo'                => $request->periodo_id,
                'venta_estado'              => $venta_estado,
                'estado_liquidacion'        => 4,
                'asesor_id'                 => $request->asesor_id,
            ];
            //arrayInstitucionGestion
            $arraySave = array_merge($arraySave, $arrayInstitucionGestion);
        }
        //solo regalado
        //regalado sin institucion
        else if($numeroProceso == '5'){
            $arraySave  = [
                'factura'               => $factura,
                'bc_estado'             => '1',
                'estado_liquidacion'    => '2',
            ];
            $arraySave = array_merge($arraySave, $arrayPaqueteInstitucion,$arrayPaqueteVentaEstado);
        }
        //regalados y bloqueados sin institucion
        else if($numeroProceso == '6'){
            $arraySave  = [
                'factura'               => $factura,
                'bc_estado'             => '1',
                'estado'                => '2',
                'estado_liquidacion'    => '2',
            ];
            $arraySave = array_merge($arraySave, $arrayPaqueteInstitucion,$arrayPaqueteVentaEstado);
        }
        //bloqueado sin institucion
        else if($numeroProceso == '7'){
            $arraySave  = [
                'factura'               => $factura,
                'bc_estado'             => '1',
                'estado'                => '2',
            ];
            $arraySave = array_merge($arraySave, $arrayPaqueteInstitucion,$arrayPaqueteVentaEstado);
        }
        //guia sin institucion
        else if($numeroProceso == '8'){
            $arraySave  = [
                'factura'               => $factura,
                'estado_liquidacion'    => 4,
                'asesor_id'             => $request->asesor_id,
                'bc_periodo'            => $request->periodo_id
            ];
            $arraySave = array_merge($arraySave, $arrayPaqueteInstitucion,$arrayPaqueteVentaEstado);
        }
        //fusionar todos los arrays
        $arrayResutado = array_merge($arraySave, $arrayUnion,$arrayPaquete,$arrayProforma,$arrayCombo);
        //actualizar el primer codigo
        $codigo = DB::table('codigoslibros')
        ->where('codigo', '=', $codigo)
        ->where('estado_liquidacion','<>', '0')
        ->update($arrayResutado);
        return $codigo;
    }
    public function updateActivacion($codigo,$codigo_union,$objectCodigoUnion,$ifOmitirA=false,$todo){
        //si es regalado guia o bloqueado no se actualiza
        $arrayCombinar = [];
        if($ifOmitirA) { return 1; }
        $withCodigoUnion = 1;
        $estadoIngreso   = 0;
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($codigo_union == '0') $withCodigoUnion = 0;
        else                     $withCodigoUnion = 1;
        $arrayLimpiar  = [
            'idusuario'                 => "0",
            'id_periodo'                => "0",
            'id_institucion'            => null,
            'bc_estado'                 => '1',
            'estado'                    => '0',
            'estado_liquidacion'        => '1',
            'venta_estado'              => '0',
            'bc_periodo'                => null,
            'bc_institucion'            => null,
            'bc_fecha_ingreso'          => null,
            'contrato'                  => null,
            'verif1'                    => null,
            'verif2'                    => null,
            'verif3'                    => null,
            'verif4'                    => null,
            'verif5'                    => null,
            'verif6'                    => null,
            'verif7'                    => null,
            'verif8'                    => null,
            'verif9'                    => null,
            'verif10'                   => null,
            'venta_lista_institucion'   => '0',
            'porcentaje_descuento'      => '0',
            'factura'                   => null,
            'liquidado_regalado'        => '0',
            'codigo_proforma'           => null,
            'proforma_empresa'          => null,
            'devuelto_proforma'         => '0',
            'asesor_id'                 => 0,
            'combo'                     => null,
            'documento_devolucion'      => null,
        ];
        $arrayPaquete = [
            'codigo_paquete'            => null,
            'fecha_registro_paquete'    => null,
        ];
        if($todo == 1) { $arrayCombinar = array_merge($arrayLimpiar, $arrayPaquete); }
        else           { $arrayCombinar = $arrayLimpiar;}

        //si hay codigo de union lo actualizo
        if($withCodigoUnion == 1){
            //VALIDO SI NO EXISTE EL CODIGO DE UNION LO MANDO COMO ERROR
            if(count($objectCodigoUnion) == 0){
                //no se ingreso
                return 2;
            }
            $codigoU = DB::table('codigoslibros')
            ->where('codigo', '=', $codigo_union)
            ->where('estado_liquidacion',   '<>', '0')
            // ->where('bc_estado',            '=', '1')
            ->update($arrayCombinar);
            //si el codigo de union se actualiza actualizo el codigo
            //if($codigoU){
                //actualizar el primer codigo
                $codigo = DB::table('codigoslibros')
                ->where('codigo', '=', $codigo)
                ->where('estado_liquidacion',   '<>', '0')
                // ->where('bc_estado',            '=', '1')
                ->update($arrayCombinar);
            //}
        }else{
            //actualizar el primer codigo
            $codigo = DB::table('codigoslibros')
            ->where('codigo', '=', $codigo)
            ->where('estado_liquidacion',   '<>', '0')
            // ->where('bc_estado',            '=', '1')
            ->update($arrayCombinar);
        }
        //con codigo union
        ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
        if($withCodigoUnion == 1){
            if($codigo && $codigoU)  $estadoIngreso = 1;
            else                     $estadoIngreso = 1;
        }
        //si no existe el codigo de union
        if($withCodigoUnion == 0){
            if($codigo)              $estadoIngreso = 1;
        }
        return $estadoIngreso;
    }
    public function updateDocumentoDevolucion($codigo,$codigo_union,$objectCodigoUnion,$request,$codigo_ven){
        try{
            $withCodigoUnion        = 1;
            $arrayCombinar          = [];
            $unionCorrecto          = false;
            $messageIngreso         = "Problema con el código union $codigo_union";
            $arrayCombinar          = [ 'documento_devolucion' => $codigo_ven ];
            $estadoIngreso          = 0;
            ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
            if($codigo_union == '0') $withCodigoUnion = 0;
            else                     $withCodigoUnion = 1;
            if($withCodigoUnion == 1){
                //VALIDO SI NO EXISTE EL CODIGO DE UNION LO MANDO COMO ERROR
                if(count($objectCodigoUnion) == 0){
                    //no se ingreso
                    $estadoIngreso              = 2;
                    $messageIngreso             = "No se encontro el código union $codigo_union";
                }else{
                    $estadoIngreso = 1;
                    //validar si el codigo se encuentra liquidado
                    $ifLiquidado                = $objectCodigoUnion[0]->estado_liquidacion;
                    //para ver si es codigo regalado no este liquidado
                    $ifliquidado_regalado       = $objectCodigoUnion[0]->liquidado_regalado;
                    if($request->dLiquidado ==  '1'){
                        //VALIDACION AUNQUE ESTE LIQUIDADO
                        if($ifLiquidado == '0' || $ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4')             { $unionCorrecto = true; }
                        else                                                                                                     { $unionCorrecto = false; }
                    }else{
                        //VALIDACION QUE NO SEA LIQUIDADO
                        if(($ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4') && $ifliquidado_regalado == '0')  { $unionCorrecto = true; }
                        else                                                                                                     { $unionCorrecto = false; }
                    }

                    //==PROCESO====
                    ///correcto o que la proforma se vaya a estado 2 que es para asignar que devolvio despues de enviar de perseo
                    if($unionCorrecto){
                        $codigoU = DB::table('codigoslibros')
                        ->where('codigo', '=', $codigo_union)
                        ->update($arrayCombinar);
                        //si el codigo de union se actualiza actualizo el codigo
                        if($codigoU){
                            //actualizar el primer codigo
                            $codigo = DB::table('codigoslibros')
                            ->where('codigo', '=', $codigo)
                            ->update($arrayCombinar);
                        }
                    }else{
                        //no se ingreso
                        $estadoIngreso          =  2;
                        $sendEstadoEgreso       = [
                            "ingreso"           => $estadoIngreso,
                            "messageIngreso"    => $messageIngreso,
                        ];
                        return $sendEstadoEgreso;
                    }
                }
            }else{
                $estadoIngreso = 1;
                ///CODIGO UNICO===
                $codigo = DB::table('codigoslibros')
                ->where('codigo', '=', $codigo)
                ->update($arrayCombinar);
            }
            $sendEstadoEgreso = [
                "ingreso"           => $estadoIngreso,
                "messageIngreso"    => $messageIngreso,
            ];
            return $sendEstadoEgreso;
        }catch(\Exception $e){
            return [
                "ingreso"           => 2,
                "messageIngreso"    => $e->getMessage()
            ];
        }
    }
    public function updateDevolucion($codigo,$codigo_union,$objectCodigoUnion,$request,$ifGuardarProforma=0,$codigo_liquidacion=null,$proforma_empresa=null,$codigo_proforma=null){
        try{
            $withCodigoUnion = 1;
            $estadoIngreso   = 0;
            $unionCorrecto   = false;
            $arrayCombinar   = [];
            $arrayProforma   = [];
            $messageIngreso  = "Problema con el código union $codigo_union";
            $ifsetProforma   = 0;
            ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
            if($codigo_union == '0') $withCodigoUnion = 0;
            else                     $withCodigoUnion = 1;
            //Datos a actualizar
            //$ifsetProforma 0 => nada; 1 => devuelto en proforma; 2 => no devuelto en proforma
            $datosUpdate                                = [ 'estado_liquidacion' => '3', 'bc_estado' => '1'];
            if($ifGuardarProforma > 0){ $arrayProforma  = [ "devuelto_proforma"  => $ifGuardarProforma ];  }
            //para colocar como que se quiso devolver el codigo pero la pre factura ya se envio a perseo
            if($ifGuardarProforma == 2)                 { $arrayCombinar = $arrayProforma; }
            else                                        { $arrayCombinar = array_merge($datosUpdate, $arrayProforma); }
            //si hay codigo de union lo actualizo
            if($withCodigoUnion == 1){
                //VALIDO SI NO EXISTE EL CODIGO DE UNION LO MANDO COMO ERROR
                if(count($objectCodigoUnion) == 0){
                    //no se ingreso
                    $estadoIngreso              = 2;
                    $messageIngreso             = "No se encontro el código union $codigo_union";
                }else{
                    //validar si el codigo se encuentra liquidado
                    $ifLiquidado                = $objectCodigoUnion[0]->estado_liquidacion;
                    //para ver si es codigo regalado no este liquidado
                    $ifliquidado_regalado       = $objectCodigoUnion[0]->liquidado_regalado;
                    //para ver la empresa de la proforma
                    $ifproforma_empresa         = $objectCodigoUnion[0]->proforma_empresa;
                    //para ver el estado devuelto proforma
                    $ifdevuelto_proforma        = $objectCodigoUnion[0]->devuelto_proforma;
                    ///para ver el codigo de proforma
                    $ifcodigo_proforma          = $objectCodigoUnion[0]->codigo_proforma;
                    if($request->dLiquidado ==  '1'){
                        //VALIDACION AUNQUE ESTE LIQUIDADO
                        if($ifLiquidado == '0' || $ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4')             { $unionCorrecto = true; }
                        else                                                                                                     { $unionCorrecto = false; }
                    }else{
                        //VALIDACION QUE NO SEA LIQUIDADO
                        if(($ifLiquidado == '1' || $ifLiquidado == '2' || $ifLiquidado == '4') && $ifliquidado_regalado == '0')  { $unionCorrecto = true; }
                        else                                                                                                     { $unionCorrecto = false; }
                    }
                    //====PROFORMA============================================
                    //ifdevuelto_proforma => 0 => nada; 1 => devuelta antes del enviar el pedido; 2 => enviada despues de enviar al pedido
                    if($ifproforma_empresa > 0 && $ifdevuelto_proforma != 1){
                        $datosProforma     = $this->validateProforma($ifdevuelto_proforma,$ifcodigo_proforma,$ifproforma_empresa);
                        $messageIngreso    = $datosProforma["ifErrorProforma"];
                        $messageIngreso    = $datosProforma["messageProforma"];
                        $ifsetProforma     = $datosProforma["ifsetProforma"];
                        if($ifsetProforma == 1) { $unionCorrecto = true; }
                        else                    { $unionCorrecto = false; }
                    }
                    //====PROFORMA============================================
                    //==PROCESO====
                    ///correcto o que la proforma se vaya a estado 2 que es para asignar que devolvio despues de enviar de perseo
                    if($unionCorrecto || $ifGuardarProforma == 2){
                        //PROFORMA
                        //regresar el stock
                        if($ifGuardarProforma == 1){
                            if($ifsetProforma == 1){
                                $getEstadoIngreso = $this->validacionIngresoDevolucionPrefactura($codigo_liquidacion,$proforma_empresa,$codigo_proforma);
                                $estadoIngreso    = $getEstadoIngreso["estadoIngreso"];
                                $messageIngreso   = $getEstadoIngreso["messageIngreso"];
                            }
                        }
                        if($unionCorrecto){
                            $estadoIngreso = 1;
                        }
                        if($estadoIngreso == 1 || $ifGuardarProforma == 2){
                            //PROFORMA
                            $codigoU = DB::table('codigoslibros')
                            ->where('codigo', '=', $codigo_union)
                            ->update($arrayCombinar);
                            //si el codigo de union se actualiza actualizo el codigo
                            if($codigoU){
                                //actualizar el primer codigo
                                $codigo = DB::table('codigoslibros')
                                ->where('codigo', '=', $codigo)
                                ->update($arrayCombinar);
                            }
                        }
                    }else{
                        return "dos";
                        //no se ingreso
                        $estadoIngreso          =  2;
                        $sendEstadoEgreso       = [
                            "ingreso"           => $estadoIngreso,
                            "messageIngreso"    => $messageIngreso,
                        ];
                        return $sendEstadoEgreso;
                    }
                }
            }else{
                //regresar el stock
                if($ifGuardarProforma == 1){
                    $getEstadoIngreso = $this->validacionIngresoDevolucionPrefactura($codigo_liquidacion,$proforma_empresa,$codigo_proforma);
                    $estadoIngreso    = $getEstadoIngreso["estadoIngreso"];
                    $messageIngreso   = $getEstadoIngreso["messageIngreso"];
                }else                 { $estadoIngreso    = 1; }
                ///CODIGO UNICO===
                //actualizar el primer codigo CODIGO SIN UNION
                if($estadoIngreso == 1 || $ifGuardarProforma == 2){
                    $codigo = DB::table('codigoslibros')
                    ->where('codigo', '=', $codigo)
                    ->update($arrayCombinar);
                }
            }
            if($estadoIngreso == 2){
            }else{
                //con codigo union
                ///estadoIngreso => 1 = ingresado; 2 = no se puedo ingresar el codigo de union;
                if($withCodigoUnion == 1){
                    if($codigo && $codigoU)  {
                        $estadoIngreso = 1;
                    }
                    else  { $estadoIngreso = 2; }
                }
                //si no existe el codigo de union
                if($withCodigoUnion == 0){
                    if($codigo)              {
                        $estadoIngreso = 1;

                    }
                }
            }
            $sendEstadoEgreso = [
                "ingreso"           => $estadoIngreso,
                "messageIngreso"    => $messageIngreso,
            ];
            return $sendEstadoEgreso;
        }catch(\Exception $e){
            return [
                "ingreso"           => 2,
                "messageIngreso"    => $e->getMessage()
            ];
        }
    }


    public function validacionIngresoDevolucionPrefactura($codigo_liquidacion=null,$proforma_empresa=null,$codigo_proforma=null){
        try{
            $nuevoStockReserva          = 0;
            $nuevoStockEmpresa          = 0;
            $stockEmpresa               = 0;
            $det_ven_dev                = 0;
            $valorNew                   = 1;
            $nuevoValorDevolucion       = 0;
            $estadoIngreso              = 1;
            $messageIngreso             = "";

            //actualizar detalle venta devolucion
            $getDevolucion              = DetalleVentas::getLibroDetalle($codigo_proforma, $proforma_empresa, $codigo_liquidacion);
            if($getDevolucion->isEmpty()){ $estadoIngreso = 2; $messageIngreso = "No se encontro $codigo_liquidacion en la pre factura $codigo_proforma"; }
            else{
                $det_ven_dev            = $getDevolucion[0]->det_ven_dev;
                $nuevoValorDevolucion   = $det_ven_dev + $valorNew;
                //actualizar valor pre factura devolucion
                $result = DetalleVentas::updateDevolucion($codigo_proforma, $proforma_empresa, $codigo_liquidacion, $nuevoValorDevolucion);
                if ($result['status'] === 1) {
                    //get stock
                    $getStock                   = _14Producto::obtenerProducto($codigo_liquidacion);
                    $stockAnteriorReserva       = $getStock->pro_reservar;
                    //prolipa
                    if($proforma_empresa == 1)  { $stockEmpresa  = $getStock->pro_stock; }
                    //calmed
                    if($proforma_empresa == 3)  { $stockEmpresa  = $getStock->pro_stockCalmed; }
                    $nuevoStockReserva          = $stockAnteriorReserva + $valorNew;
                    $nuevoStockEmpresa          = $stockEmpresa + $valorNew;
                    //actualizar stock en la tabla de productos
                    _14Producto::updateStock($codigo_liquidacion,$proforma_empresa,$nuevoStockReserva,$nuevoStockEmpresa);
                } else {
                    { $estadoIngreso = 2; $messageIngreso = "No se pudo actualizar  la devolucion el detalle de la pre factura"; }
                }
            }

            return
            [
                "estadoIngreso"  => $estadoIngreso,
                "messageIngreso" => $messageIngreso
            ];
        }
        catch(\Exception $e){
            return [
                "estadoIngreso"  => 2,
                "messageIngreso" => $e->getMessage()
            ];
        }
    }
    //SAVE CODIGOS
    public function save_Codigos($request,$item,$codigo,$prueba_diagnostica,$contador){
        $contadorIngreso                            = 0;
        $codigos_libros                             = new CodigosLibros();
        $codigos_libros->serie                      = $item->serie;
        $codigos_libros->libro                      = $item->libro;
        $codigos_libros->anio                       = $item->anio;
        $codigos_libros->libro_idlibro              = $item->libro_idlibro;
        $codigos_libros->estado                     = 0;
        $codigos_libros->idusuario                  = 0;
        $codigos_libros->bc_estado                  = 1;
        $codigos_libros->idusuario_creador_codigo   = $request->user_created;
        $codigos_libros->prueba_diagnostica         = $prueba_diagnostica;
        $codigo_verificar                           = $codigo;
        $verificar_codigo = DB::SELECT("SELECT codigo from codigoslibros WHERE codigo = '$codigo_verificar'");
        if( $verificar_codigo ){
            $contadorIngreso = 0;
        }else{
            $codigos_libros->codigo = $codigo;
            $codigos_libros->contador = ++$contador;
            $codigos_libros->save();
            if($codigos_libros){
                $contadorIngreso = 1;
            }else{
                $contadorIngreso = 0;
            }
        }
        if($contadorIngreso == 1){
            return [
                "contadorIngreso" => $contadorIngreso,
                "contador"        => $codigos_libros->contador
            ];
        }else{
            return [
                "contadorIngreso" => $contadorIngreso,
                "contador"        => 0
            ];
        }

    }
    public function saveDevolucion($codigo,$cliente,$institucion_id,$periodo_id,$fecha,$observacion,$id_usuario){
        $devolucion                     = new CodigosDevolucion();
        $devolucion->codigo             = $codigo;
        $devolucion->cliente            = $cliente;
        $devolucion->institucion_id     = $institucion_id;
        $devolucion->periodo_id         = $periodo_id;
        $devolucion->fecha_devolucion   = $fecha;
        $devolucion->observacion        = $observacion;
        $devolucion->usuario_editor     = $id_usuario;
        $devolucion->save();
    }
    //validacion Proforma
    public function validateProforma($ifdevuelto_proforma,$ifcodigo_proforma,$ifproforma_empresa){
        $ifErrorProforma    = 0;
        $messageProforma    = "";
        $ifsetProforma      = 0;
        $query = DB::SELECT("SELECT * FROM f_venta v WHERE v.ven_codigo = '$ifcodigo_proforma' AND v.id_empresa = '$ifproforma_empresa'");
        if(empty($query))                   { $ifErrorProforma = 1 ; $messageProforma = "No existe proforma"; }
        else{
            $ifsetProforma    = 1;
            //si la prefactura esta colocada que no se devolvio porque ya se envio a perseo el pedido
            // if($ifdevuelto_proforma == 2)   { $ifErrorProforma = 1 ; $messageProforma = "No se puede devolver el código porque la $ifcodigo_proforma ya fue hecho pedido en perseo";  }
            // else{
            //     //validar si la prefactura ya se envio a perseo
            //     $getPreFactura = $this->proformaRepository->prefacturaValidaForDevolver($ifcodigo_proforma,$ifproforma_empresa);
            //     //si ya se envio a perseo voy a asignar el valor 2 para que no se devuelva
            //     if(count($getPreFactura) > 0)   { $ifErrorProforma  = 1; $ifsetProforma = 2; $messageProforma = "No se puede devolver el código porque la $ifcodigo_proforma ya fue hecho pedido en perseo";  }
            //     else                            { $ifsetProforma    = 1; }
            // }
        }
        return [
            "ifErrorProforma"   => $ifErrorProforma,
            "messageProforma"   => $messageProforma,
            "ifsetProforma"     => $ifsetProforma
        ];
    }
    public function getCombos(){
        $query = DB::SELECT("SELECT ls.*, s.nombre_serie,
        CONCAT(ls.codigo_liquidacion, ' - ',ls.nombre ) as combolibro,
        p.codigos_combos
        FROM libros_series ls
        LEFT JOIN series  s ON ls.id_serie = s.id_serie
        left join 1_4_cal_producto p on ls.codigo_liquidacion = p.pro_codigo
        WHERE s.id_serie = '19'
        ");
        //del codigos_combos crear una propiedad cantidad combo  y hacer un explode porque tiene comas
        //para ver si tiene combos
        foreach($query as $key => $item){
            //si es null dejar en cero
            if($item->codigos_combos == null){
                $item->cantidad_combos = 0;
            }else{
                $combos = explode(',',$item->codigos_combos);
                if(count($combos) > 0){
                    $item->cantidad_combos = count($combos);
                }else{
                    $item->cantidad_combos = 0;
                }
            }
        }
        return $query;
    }
    public function getCodigosBodega($filtro, $periodo,$institucion=0,$asesor_id=0){
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
        ->when($filtro == 0, function ($query) use ($institucion) {
            $query->where('codigoslibros.venta_lista_institucion', $institucion)
                  ->where(function ($query) {
                      $query->where('codigoslibros.estado_liquidacion', '0')
                            ->orWhere('codigoslibros.estado_liquidacion', '1');
                  });
        })
        ->when($filtro == 1,function($query) use ($asesor_id) {
            $query->where('codigoslibros.asesor_id', $asesor_id)
                  ->where('codigoslibros.estado_liquidacion', '4');
        })
        ->groupBy('libros_series.codigo_liquidacion', 'libros_series.nombre', 'codigoslibros.serie', 'codigoslibros.libro_idlibro', 'libros_series.year', 'libros_series.id_serie', 'asignatura.area_idarea')
        ->get();

        // Procesar los resultados para obtener el precio y multiplicar por la cantidad
        foreach ($arrayCodigosActivos as $item) {
            // Obtener el precio del libro usando el repositorio
            $precio = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
            $item->precio       = $precio;
            $item->valor        = $item->cantidad;
            // Multiplicar el precio por la cantidad
            $item->precio_total = number_format($precio * $item->cantidad, 2, '.', '');
        }
        return $arrayCodigosActivos;
    }
    public function getLibrosAsesores($periodo,$asesor_id){
        $val_pedido = DB::SELECT("SELECT pv.valor,
        pv.id_area, pv.tipo_val, pv.id_serie, pv.year,pv.plan_lector,pv.alcance,
        p.id_periodo,
        CONCAT(se.nombre_serie,' ',ar.nombrearea) as serieArea,
        se.nombre_serie,p.id_asesor, CONCAT(u.nombres,' ',u.apellidos) as asesor
        FROM pedidos_val_area pv
        LEFT JOIN area ar ON  pv.id_area    = ar.idarea
        LEFT JOIN series se ON pv.id_serie  = se.id_serie
        LEFT JOIN pedidos p ON pv.id_pedido = p.id_pedido
        LEFT JOIN usuario u ON p.id_asesor  = u.idusuario
        WHERE p.id_periodo      = '$periodo'
        AND p.id_asesor         = '$asesor_id'
        AND p.tipo              = '1'
        AND p.estado            = '1'
        AND p.estado_entrega    = '2'
        GROUP BY pv.id
        ");
         if(empty($val_pedido)){
            return $val_pedido;
        }
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
                $query = $this->getAlcanceAbiertoXId($alcance_id);
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
                "id_serie"          => $item->id_serie,
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
        return $result;
    }
    public function getAlcanceAbiertoXId($id){
        $query = DB::SELECT("SELECT * FROM pedidos_alcance a
        WHERE a.id = '$id'
        AND a.estado_alcance = '1'");
        return $query;
    }
}
