<?php
namespace App\Repositories\Codigos;

use App\Models\_14Producto;
use App\Models\CodigosDevolucion;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionDesarmadoHeader;
use App\Models\CodigosLibrosDevolucionDesarmadoSon;
use App\Models\DetalleVentas;
use App\Models\Ventas;
use App\Repositories\BaseRepository;
use App\Repositories\Facturacion\ProformaRepository;
use App\Repositories\pedidos\PedidosRepository;
use DB;
use Schema;

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

    public function procesoUpdateGestionBodega($numeroProceso,$codigo,$union,$request,$factura,$paquete=null,$ifChangeProforma=false,$datosProforma=[],$comboIndividual=null){
        $venta_estado               = $request->venta_estado;
        //tipoComboImportacion => 0 combo general; 1 = combo individual del excel
        $tipoComboImportacion       = $request->tipoComboImportacion;
        $arrayInstitucionGestion    = [];
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
        $arrayProforma           = [];
        $arrayCombo              = [];
        //combo
        $ifSetCombo              = $request->ifSetCombo;
        $combo                   = $request->comboSelected;
        //si ifSetCombo es 1 coloco el combo
        if($ifSetCombo == 1)            { $arrayCombo  = [ 'combo' => $combo]; }
        if($tipoComboImportacion == 1)  { $arrayCombo  = [ 'combo' => $comboIndividual, ]; }
        //paquete
        // if($paquete == null)            { $arrayPaquete = []; }else{ $arrayPaquete  = [ 'codigo_paquete' => $paquete, 'fecha_registro_paquete'    => $fecha]; }
        //codigo de union
        if($union == null)              { $arrayUnion  = [];  } else{ $arrayUnion  = [ 'codigo_union' => $union ]; }
        //si proforma es true
        if($ifChangeProforma == false)  { $arrayProforma = []; } else{ $arrayProforma = [ 'codigo_proforma' => $datosProforma['codigo_proforma'], 'proforma_empresa' => $datosProforma['proforma_empresa'] ]; }
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
        else if($numeroProceso == '1' || $numeroProceso == '5'){
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
        else if($numeroProceso == '2' || $numeroProceso == '6'){
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
        else if($numeroProceso == '3' || $numeroProceso == '7'){
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
        else if($numeroProceso == '4' || $numeroProceso == '8'){
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
        //fusionar todos los arrays
        $arrayResutado = array_merge($arraySave, $arrayUnion,$arrayPaquete,$arrayProforma,$arrayCombo);
        //actualizar el primer codigo
        $codigo = DB::table('codigoslibros')
        ->where('codigo', '=', $codigo)
        ->where('estado_liquidacion','<>', '0')
        ->update($arrayResutado);
        return $codigo;
    }
    public function updateActivacion($codigo,$codigo_union,$objectCodigoUnion,$ifOmitirA=false,$todo,$request){
        //si es regalado guia o bloqueado no se actualiza
        $arrayCombinar = [];
        $arrayPlus     = [];
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
            'permitir_devolver_nota'    => '0',
            'quitar_de_reporte'         => null,
        ];
        $arrayPaquete = [
            'codigo_paquete'            => null,
            'fecha_registro_paquete'    => null,
        ];
        $arrayCombo = [
            'codigo_combo'              => null,
            'fecha_registro_combo'      => null,
        ];
        if($todo == 1) { $arrayCombinar = array_merge($arrayLimpiar, $arrayPaquete, $arrayCombo); }
        else           { $arrayCombinar = $arrayLimpiar;}
        //PLUS
        if($request->Removeplus == '1'){
            $arrayPlus = [
                'plus'              => 0,
            ];
            $arrayCombinar = array_merge($arrayCombinar, $arrayPlus);
        }

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
                //actualizar el primer codigo
                $codigo = DB::table('codigoslibros')
                ->where('codigo', '=', $codigo)
                ->where('estado_liquidacion',   '<>', '0')
                ->update($arrayCombinar);
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
            $messageIngreso         = "";
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
    public function updateDocumentoDevolucionSueltos($codigo,$codigo_union=null,$codigo_ven){
        $estadoIngreso = 1;
        $messageIngreso = "";
        DB::table('codigoslibros')
            ->where('codigo', '=', $codigo)
            ->update(['documento_devolucion' => $codigo_ven]);
        // Si hay un código de unión, actualizarlo también
        if ($codigo_union !== "no") {
            $codigoUnion = CodigosLibros::find($codigo_union);
            if (!$codigoUnion) {
                $estadoIngreso = 2;
                $messageIngreso = "No se encontró el código de unión: $codigo_union.";
            } else {
                $codigoUnion->documento_devolucion = $codigo_ven;
                $codigoUnion->save();
            }
        }
         $sendEstadoEgreso = [
                "ingreso"           => $estadoIngreso,
                "messageIngreso"    => $messageIngreso,
        ];
        return $sendEstadoEgreso;
    }
    public function validacionPrefacturaCodigo($datos){
        $codigo             = $datos->codigo;
        $codigo_union       = $datos->codigo_union;
        $ifsetProforma      = $datos->ifsetProforma;
        $codigo_liquidacion = $datos->codigo_liquidacion;
        $proforma_empresa   = $datos->proforma_empresa;
        $codigo_proforma    = $datos->codigo_proforma;
        $estadoIngreso      = 1; // 1 = Éxito, 2 = Error
        $message            = "";

        //validar si el codigo_union existe
        if($codigo_union != '0'){
            $getUnion = CodigosLibros::find($codigo_union);
            if(!$getUnion){
                $estadoIngreso = 2;
                $message .= "No se encontró el código de unión con el código $codigo_union. ";
            }
        }

        // Si los registros principales y unidos se guardaron correctamente, procesar la proforma
        // if ($ifsetProforma == 1 && $estadoIngreso == 1) {
        //     $getDevolucion = DetalleVentas::getLibroDetalle($codigo_proforma, $proforma_empresa, $codigo_liquidacion);

        //     if (!empty($getDevolucion) && isset($getDevolucion[0]->det_ven_dev)) {

        //     } else {
        //         $estadoIngreso = 2;
        //         $message .= "No se encontró el detalle del libro con código de proforma: $codigo_proforma. ";
        //     }
        // }

        // Retornar resultados
        return [
            "ingreso" => $estadoIngreso,
            "message" => trim($message), // Mensaje único
        ];
    }
    public function updateDevolucionDocumento($datos)
    {
        $codigo             = $datos->codigo;
        $codigo_union       = $datos->codigo_union;
        $ifsetProforma      = $datos->ifsetProforma;
        $codigo_liquidacion = $datos->codigo_liquidacion;
        $proforma_empresa   = $datos->proforma_empresa;
        $codigo_proforma    = $datos->codigo_proforma;
        //tipo_importacion 1 => importacion codigos; 2 => importacion paquetes, 3 => importacion combos
        $tipo_importacion   = $datos->tipo_importacion;
        $estadoIngreso      = 1; // 1 = Éxito, 2 = Error
        $message            = ""; // Para acumular un solo mensaje
        $oldValues          = [];
        $newValues          = [];

        //actualizar pre factura
        if ($ifsetProforma == 1) {

            $getDevolucion = DetalleVentas::getLibroDetalle($codigo_proforma, $proforma_empresa, $codigo_liquidacion);

            if (!empty($getDevolucion) && isset($getDevolucion[0]->det_ven_dev)) {
                // old values STOCK
                $oldValues              = $this->save_historicoStockOld($codigo_liquidacion);
                $documento = Ventas::where('ven_codigo', $codigo_proforma)->where('id_empresa', $proforma_empresa)->first();
                //idtipodoc => 1 es prefactura; 2 = notas
                $idtipodoc = $documento->idtipodoc;
                $documentoPrefactura = $idtipodoc == '1' ? '0' : '1';
                $det_ven_dev = $getDevolucion[0]->det_ven_dev;
                $nuevoValorDevolucion = $det_ven_dev + 1;

                // Actualizar el detalle de venta devolución
                $result = DetalleVentas::updateDevolucion($codigo_proforma, $proforma_empresa, $codigo_liquidacion, $nuevoValorDevolucion);
                if ($result['status'] !== 1) {
                    $estadoIngreso = 2;
                    $message .= "No se pudo actualizar la devolución del detalle de la pre factura. ";
                } else {
                    $valorNew                   = 1;
                    //get stock
                    $getStock                   = _14Producto::obtenerProducto($codigo_liquidacion);
                    $stockAnteriorReserva       = $getStock->pro_reservar;
                    //prolipa
                    // if($proforma_empresa == 1)  {
                    //     //si es documento de prefactura
                    //     if($documentoPrefactura == 0)  { $stockEmpresa  = $getStock->pro_stock; }
                    //     //si es documento de notas
                    //     if($documentoPrefactura == 1)  { $stockEmpresa  = $getStock->pro_deposito; }
                    // }
                    // //calmed
                    // if($proforma_empresa == 3)  {
                    //     //si es documento de prefactura
                    //     if($documentoPrefactura == 0)  { $stockEmpresa  = $getStock->pro_stockCalmed; }
                    //     //si es documento de notas
                    //     if($documentoPrefactura == 1)  { $stockEmpresa  = $getStock->pro_depositoCalmed; }
                    // }
                    // SOLO SE VA DEVOLVER A CALMED STOCK NOTAS
                    $empresaNuevo               = 3;
                    // tipoDocumento = 0; => prefactura; tipoDocumento = 1 => notas
                    $tipoDocumento              = 1;
                    $stockEmpresa               = $getStock->pro_depositoCalmed;
                    $nuevoStockReserva          = $stockAnteriorReserva + $valorNew;
                    $nuevoStockEmpresa          = $stockEmpresa + $valorNew;
                    //actualizar stock en la tabla de productos
                    _14Producto::updateStock($codigo_liquidacion,$empresaNuevo,$nuevoStockReserva,$nuevoStockEmpresa,$tipoDocumento);
                    // new values STOCK
                    $newValues              = $this->save_historicoStockNew($codigo_liquidacion);
                    $estadoIngreso = 1;
                }
            } else {
                $estadoIngreso = 2;
                $message .= "No se encontró el detalle del libro con código de proforma: $codigo_proforma. ";
            }
        }
        if($estadoIngreso == 1){
            // Método auxiliar para actualizar registros
            $actualizarLibro = function ($libro, $codigo, $tipo_importacion) use (&$estadoIngreso, &$message, &$ifsetProforma) {
                if ($libro) {
                    $libro->bc_estado = '1';
                    $libro->estado_liquidacion = '3';
                    if ($tipo_importacion == 1) {
                        $libro->codigo_paquete = null;
                        $libro->combo = null;
                        $libro->codigo_combo = null;
                    }
                    if($ifsetProforma == 1){
                        $libro->devuelto_proforma = 1;
                    }
                    $libro->save();

                    // Validar si se actualizó correctamente
                    if (!$libro->wasChanged()) {
                        $estadoIngreso = 2;
                        $message .= "No se pudo actualizar el registro con código: $codigo. ";
                    } else {
                        $message .= "Registro actualizado con éxito: $codigo. ";
                    }
                } else {
                    $estadoIngreso = 2;
                    $message .= "El registro con código: $codigo no existe. ";
                }
            };

            // Actualizar el registro principal
            $actualizarLibro(CodigosLibros::find($codigo), $codigo, $tipo_importacion);

            // Si el registro principal se guardó correctamente, actualizar el registro unido
            if ($codigo_union != '0') {
                $actualizarLibro(CodigosLibros::find($codigo_union), $codigo_union, $tipo_importacion);
            }
        }

        // Retornar resultados
        return [
            "ingreso" => $estadoIngreso,
            "message" => trim($message), // Mensaje único
            "oldValues" => $oldValues,
            "newValues" => $newValues,
        ];
    }


    public function updateDevolucion($codigo, $codigo_union)
    {
        try {
            // Verificar si el código principal existe
            $codigoExistente = DB::table('codigoslibros')->where('codigo', $codigo)->exists();

            if (!$codigoExistente) {
                return [
                    "ingreso" => 2,
                    "messageIngreso" => "No se encontró el código: $codigo."
                ];
            }

            // Actualizar el código principal
            DB::table('codigoslibros')
                ->where('codigo', $codigo)
                ->update(['estado_liquidacion' => 3, 'bc_estado' => 1]);

            // Si hay un código de unión, actualizarlo también
            if (!empty($codigo_union) && $codigo_union !== "no") {
                $codigoUnion = CodigosLibros::find($codigo_union);
                if (!$codigoUnion) {
                    return [
                        "ingreso" => 2,
                        "messageIngreso" => "No se encontró el código de unión: $codigo_union."
                    ];
                }

                $codigoUnion->estado_liquidacion = 3;
                $codigoUnion->bc_estado = 1;
                $codigoUnion->save();
            }

            return [
                "ingreso" => 1,
                "messageIngreso" => "Devolución actualizada correctamente."
            ];
        } catch (\Exception $e) {
            return [
                "ingreso" => 2,
                "messageIngreso" => $e->getMessage()
            ];
        }
    }

     public function updateDevolucionSueltosSinDocumentos($codigo,$codigo_union,$objectCodigoUnion,$request,$ifGuardarProforma=0,$codigo_liquidacion=null,$proforma_empresa=null,$codigo_proforma=null,$tipo_importacion=null){
        try{
            $withCodigoUnion = 1;
            $estadoIngreso   = 0;
            $unionCorrecto   = false;
            $arrayCombinar   = [];
            $arrayProforma   = [];
            $messageIngreso  = "Problema con el código union $codigo_union";
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
            //limpiar paquete
            $arrayPaquete = ['codigo_paquete' => null];
            $arrayCombos  = ['codigo_combo'   => null, 'combo' => null];
            //si la importacion es 1 individual se elimina el paquete y combo
            if($tipo_importacion == 1){
                $arrayCombinar = array_merge($arrayCombinar, $arrayPaquete, $arrayCombos);
            }
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
                    //para ver si es codigo regalado no este liquidado
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
                    if($unionCorrecto || $ifGuardarProforma == 2){
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

                $estadoIngreso    = 1;
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
     public function validateProforma($ifcodigo_proforma,$ifproforma_empresa,$codigo_liquidacion){
        $ifErrorProforma    = 0;
        $messageProforma    = "";
        $ifsetProforma      = 0;
        $query = DB::SELECT("SELECT * FROM f_venta v WHERE v.ven_codigo = '$ifcodigo_proforma' AND v.id_empresa = '$ifproforma_empresa'");
        if(empty($query))                   { $ifErrorProforma = 1 ; $messageProforma = "No existe pre factura $ifcodigo_proforma"; }
        else{
            $ifsetProforma    = 1;
              // Si los registros principales y unidos se guardaron correctamente, procesar la proforma
            if ($ifsetProforma == 1) {
                $getDevolucion = DetalleVentas::getLibroDetalle($ifcodigo_proforma, $ifproforma_empresa, $codigo_liquidacion);
                if (!empty($getDevolucion) && isset($getDevolucion[0]->det_ven_dev)) {

                } else {
                    $ifErrorProforma = 1;
                    $messageProforma = "No se encontró el detalle del libro con código de proforma: $ifcodigo_proforma. ";
                }
            }
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
        p.codigos_combos, a.area_idarea, l.nombrelibro
        FROM libros_series ls
        LEFT JOIN series  s ON ls.id_serie = s.id_serie
        left join 1_4_cal_producto p on ls.codigo_liquidacion = p.pro_codigo
        left join libro l on ls.idLibro = l.idlibro
        left join asignatura a on l.asignatura_idasignatura = a.idasignatura
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
    public function getCodigosIndividuales($request)
    {
        try {
            // Obtener los parámetros de la solicitud
            $periodo        = $request->periodo_id;
            $institucion    = $request->institucion_id;
            $IdVerificacion = $request->IdVerificacion;
            $verif          = "verif" . $request->verificacion_id;
            $liquidados     = $request->liquidados;
            $porInstitucion = $request->porInstitucion;
            // Si $verif tiene un valor, validamos que la columna exista
            if ($IdVerificacion && !Schema::hasColumn('codigoslibros', $verif)) {
                throw new \Exception("La columna {$verif} no existe en la tabla codigoslibros.");
            }

            // Consulta con Eloquent
            $detalles = CodigosLibros::from('codigoslibros as c') // Alias aplicado aquí
                ->select([
                    'c.codigo AS codigo_libro',
                    'c.serie',
                    'c.plus',
                    'c.libro_idlibro',
                    'c.estado_liquidacion',
                    'c.estado',
                    'c.bc_estado',
                    'c.venta_estado',
                    'c.liquidado_regalado',
                    'c.bc_institucion',
                    'c.contrato',
                    'c.venta_lista_institucion',
                    'c.quitar_de_reporte',
                    'c.combo',
                    'c.codigo_combo',
                    'c.codigo_proforma',
                    'c.proforma_empresa',
                    'c.porcentaje_personalizado_regalado',
                    'ls.year',
                    'ls.id_libro_plus',
                    DB::raw("
                        CASE
                            WHEN c.porcentaje_personalizado_regalado = 0 THEN
                                (SELECT v.ven_desc_por FROM f_venta v
                                WHERE v.ven_codigo = c.codigo_proforma
                                AND v.id_empresa = c.proforma_empresa
                                LIMIT 1)
                            WHEN c.porcentaje_personalizado_regalado = 1 THEN 100
                            WHEN c.porcentaje_personalizado_regalado = 2 THEN
                                (SELECT p.descuento FROM pedidos p
                                WHERE p.contrato_generado = c.contrato
                                LIMIT 1)
                            ELSE NULL
                        END AS descuento
                    "),
                    // DB::raw("(SELECT v.ven_desc_por FROM f_venta v WHERE v.ven_codigo = c.codigo_proforma AND v.id_empresa = c.proforma_empresa LIMIT 1) AS descuento"),
                    DB::raw('
                        CASE
                            WHEN c.verif1 > 0 THEN "verif1"
                            WHEN c.verif2 > 0 THEN "verif2"
                            WHEN c.verif3 > 0 THEN "verif3"
                            WHEN c.verif4 > 0 THEN "verif4"
                            WHEN c.verif5 > 0 THEN "verif5"
                        END AS verificacion
                    '),
                    DB::raw('0 as tipo_codigo'), // Lógica para la nueva columna
                    DB::raw("CASE WHEN c.plus = 1 THEN ls.id_libro_plus ELSE c.libro_idlibro END AS libro_idReal"), // Lógica para la nueva columna
                    DB::raw("CASE WHEN c.plus = 1 THEN l_plus.nombre ELSE l.nombrelibro END AS nombrelibro"), // Lógica para la nueva columna
                    DB::raw("CASE WHEN c.plus = 1 THEN l_plus.codigo_liquidacion ELSE ls.codigo_liquidacion END AS codigo"), // Lógica para la nueva columna
                    DB::raw("CASE WHEN c.plus = 1 THEN l_plus.id_serie ELSE ls.id_serie END AS id_serie"), // Lógica para la nueva columna
                    DB::raw("CASE WHEN c.plus = 1 THEN a_plus.area_idarea ELSE a.area_idarea END AS area_idarea"), // Lógica para la nueva columna
                ])
                ->leftJoin('libros_series as ls', 'ls.idLibro', '=', 'c.libro_idlibro')
                ->leftJoin('libro as l', 'ls.idLibro', '=', 'l.idlibro')
                ->leftJoin('asignatura as a', 'l.asignatura_idasignatura', '=', 'a.idasignatura')
                ->leftJoin('libros_series as l_plus', 'ls.id_libro_plus', '=', 'l_plus.idLibro') // Join adicional para plus
                ->leftJoin('libro as lib_plus', 'ls.id_libro_plus', '=', 'lib_plus.idlibro')
                ->leftJoin('asignatura as a_plus', 'lib_plus.asignatura_idasignatura', '=', 'a_plus.idasignatura')
                ->where('c.bc_periodo', $periodo)
                ->where('c.prueba_diagnostica', '0')
                // ->where('c.bc_estado', '2')
                ->when($liquidados, function ($query) use ($IdVerificacion, $institucion, $verif) {
                    $query->where($verif, $IdVerificacion)
                        ->where(function ($query) use ($institucion) {
                            $query->where('c.bc_institucion', $institucion)
                                ->orWhere('c.venta_lista_institucion', $institucion);
                        });
                })
                ->when($porInstitucion, function ($query) use ($institucion) {
                    $query->where(function ($query) use ($institucion) {
                        $query->where('c.bc_institucion', $institucion)
                            ->orWhere('c.venta_lista_institucion', $institucion);
                    });
                })
                ->get();

            return $detalles;

        } catch (\Exception $e) {
            // Lanza una excepción con el mensaje original para identificar el problema
            throw new \Exception("Error al obtener los códigos individuales: " . $e->getMessage());
        }
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

    //INICIO METODOS JEYSON

    public function getLibrosAsesores_new($periodo,$asesor_id){
        $val_pedido = DB::SELECT("SELECT DISTINCT pv.pvn_id AS id, pv.id_pedido,
        pv.pvn_cantidad AS valor,
        CASE
            WHEN s.id_serie = 6 THEN l.idlibro
            ELSE ar.idarea
        END as id_area,
        s.id_serie,
        CASE
            WHEN s.id_serie = 6 THEN 0
            ELSE ls.year
        END as year,
        ls.year as anio,
        CASE
            WHEN s.id_serie = 6 THEN l.idlibro
            ELSE 0
        END as plan_lector,
        pv.pvn_tipo AS alcance, pv.created_at, pv.updated_at,
        CASE
            WHEN s.id_serie = 6 THEN NULL
            ELSE CONCAT(s.nombre_serie, ' ', ar.nombrearea)
        END as serieArea,
        l.idlibro, l.nombrelibro, p.descuento, p.id_periodo, p.anticipo, p.comision, s.nombre_serie,
        ls.version, asi.idasignatura, ls.codigo_liquidacion, l.descripcionlibro
        FROM pedidos_val_area_new pv
        LEFT JOIN libro l ON  pv.idlibro = l.idlibro
        LEFT JOIN libros_series ls ON pv.idlibro = ls.idLibro
        LEFT JOIN asignatura asi ON l.asignatura_idasignatura = asi.idasignatura
        LEFT JOIN area ar ON asi.area_idarea = ar.idarea
        LEFT JOIN series s ON ls.id_serie = s.id_serie
        LEFT JOIN pedidos p ON pv.id_pedido = p.id_pedido
        WHERE p.id_periodo      = '$periodo'
        AND p.id_asesor         = '$asesor_id'
        AND p.tipo              = '1'
        AND p.estado            = '1'
        AND p.estado_entrega    = '2'
        GROUP BY pv.pvn_id, s.nombre_serie, ls.year, s.id_serie, ls.version, ls.codigo_liquidacion;
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
                    // "id"                => $tr->id,
                    // "id_pedido"         => $tr->id_pedido,
                    "id_area"           => $tr->id_area,
                    "valor"             => $tr->valor,
                    "id_serie"          => $tr->id_serie,
                    "year"              => $tr->year,
                    "plan_lector"       => $tr->plan_lector,
                    "alcance"           => $tr->alcance,
                    // "descuento"         => $tr->descuento,
                    "id_periodo"        => $tr->id_periodo,
                    // "anticipo"          => $tr->anticipo,
                    // "comision"          => $tr->comision,
                    "serieArea"         => $tr->serieArea,
                    "nombre_serie"      => $tr->nombre_serie,
                    // "version"           => $tr->version,
                    "idlibro"           => $tr->idlibro,
                    "nombrelibro"       => $tr->nombrelibro,
                    "codigo"            => $tr->codigo_liquidacion,
                    // "anio"              => $tr->anio,
                    "descripcion"       => $tr->descripcionlibro,
                    "alcance"           => $alcance_id
                ];
            }else{
                //validate que el alcance este cerrado o aprobado
                $query = $this->getAlcanceAbiertoXId($alcance_id);
                if(count($query) > 0){
                    $arreglo[$cont] = (object) [
                        // "id"                => $tr->id,
                        // "id_pedido"         => $tr->id_pedido,
                        "id_area"           => $tr->id_area,
                        "valor"             => $tr->valor,
                        "id_serie"          => $tr->id_serie,
                        "year"              => $tr->year,
                        "plan_lector"       => $tr->plan_lector,
                        "alcance"           => $tr->alcance,
                        // "descuento"         => $tr->descuento,
                        "id_periodo"        => $tr->id_periodo,
                        // "anticipo"          => $tr->anticipo,
                        // "comision"          => $tr->comision,
                        "serieArea"         => $tr->serieArea,
                        "nombre_serie"      => $tr->nombre_serie,
                        // "version"           => $tr->version,
                        "idlibro"           => $tr->idlibro,
                        "nombrelibro"       => $tr->nombrelibro,
                        "codigo"            => $tr->codigo_liquidacion,
                        // "anio"              => $tr->anio,
                        "descripcion"       => $tr->descripcionlibro,
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
        foreach($renderSet as $item){
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
                // "id"                => $item->id,
                // "id_pedido"         => $item->id_pedido,
                "id_area"           => $item->id_area,
                "valor"             => $item->valor,
                "id_serie"          => $item->id_serie,
                // "year"              => $item->year,
                // "anio"              => $item->anio,
                // "version"           => $item->version,
                // "descuento"         => $item->descuento,
                // "anticipo"          => $item->anticipo,
                // "comision"          => $item->comision,
                // "plan_lector"       => $item->plan_lector,
                "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$item->nombrelibro : $item->serieArea,
                "libro_id"          => $item->idlibro,
                "nombrelibro"       => $item->nombrelibro,
                "nombre_serie"      => $item->nombre_serie,
                "precio"            => $pfn_pvp_result,
                "codigo"            => $item->codigo,
                "stock"             => $stock_producto->pro_reservar,
                // "subtotal"          => $item->valor * $valores[0]->precio,
                // "alcance"           => $item->alcance,
                "descripcion"       => $item->descripcion,
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

    public function getCodigosBodega_new($filtro, $periodo,$institucion=0,$asesor_id=0){
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
            $precio = $this->pedidosRepository->getPrecioXLibro_new($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
            $item->precio       = $precio;
            $item->valor        = $item->cantidad;
            // Multiplicar el precio por la cantidad
            $item->precio_total = number_format($precio * $item->cantidad, 2, '.', '');
        }
        return $arrayCodigosActivos;
    }

    //FIN METODOS JEYSON
    public function getAgrupadoCombos($codigos, $getCombos){
        try {
            // Convertir $getCombos a una colección de Laravel si es un array normal
            $getCombos = collect($getCombos);

            // Agrupar por código de combo
            $result = $codigos->groupBy(function ($item) {
                return $item->combo;
            });

            // Mapear los resultados para incluir la información adicional de cada combo
            $result = $result->map(function ($group) use ($getCombos) {
                // Agrupar por 'codigo_combo' y contar las ocurrencias
                $hijos = $group->groupBy('codigo_combo')->map(function ($subGroup, $key) {
                    // Retornar un array de objetos con propiedad 'codigo_combo' y su cantidad
                    return [
                        'codigo_combo' => $key,   // Código del hijo
                        'cantidad' => $subGroup->count()  // Cantidad de ocurrencias de este código de combo
                    ];
                });

                // Buscar la información del combo dentro de $getCombos usando el código del combo
                $comboInfo = $getCombos->firstWhere('codigo_liquidacion', $group->first()->combo);

                // Retornar el formato solicitado con la información adicional del combo
                return(object)[
                    'codigo_libro'              => $group->first()->combo,
                    'combo'                     => $group->first()->combo,   // Nombre del combo
                    'cantidad_items'            => $hijos->count(),       // Cantidad de items dentro del combo,
                    'cantidad_subitems'         => $group->count(),       // Cantidad de items dentro del combo,
                    'hijos'                     => $hijos->values(),         // Los códigos de combo y sus cantidades en formato de array de objetos
                    'codigo'                    => $comboInfo ? $comboInfo->codigo_liquidacion : null,
                    'libro_idReal'              => $comboInfo ? $comboInfo->idLibro : null,
                    'libro_idlibro'             => $comboInfo ? $comboInfo->idLibro : null,
                    'area_idarea'               => $comboInfo ? $comboInfo->area_idarea : null,
                    'id_serie'                  => $comboInfo ? $comboInfo->id_serie : null,
                    'year'                      => $comboInfo ? $comboInfo->year : null,
                    'nombrelibro'               => $comboInfo ? $comboInfo->nombre : null,
                    'codigos_combos'            => $comboInfo ? $comboInfo->codigos_combos : null,
                    'cantidad_combos'           => $comboInfo ? $comboInfo->cantidad_combos : null,
                    'tipo_codigo'               => 1 // 0 = codigo individual; 1 = combo general
                ];
            });

            // Convertir el resultado en un array simple
            return $result->values()->toArray();

        } catch (\Exception $e) {
            // Lanza una excepción personalizada
            throw new \Exception('Error al procesar los combos: ' . $e->getMessage());
        }
    }
    public function save_historicoStockOld($pro_codigo){
        try{

            $producto               = _14Producto::obtenerProducto($pro_codigo);
            if (!$producto) {
                throw new \Exception("No se pudo obtener el producto $pro_codigo");
            }
            $oldValues = [
                'pro_codigo'         => $producto->pro_codigo,
                'pro_reservar'       => $producto->pro_reservar ?? 0,
                'pro_stock'          => $producto->pro_stock ?? 0,
                'pro_stockCalmed'    => $producto->pro_stockCalmed ?? 0,
                'pro_deposito'       => $producto->pro_deposito ?? 0,
                'pro_depositoCalmed' => $producto->pro_depositoCalmed ?? 0,
            ];
            return $oldValues;
        }
        catch(\Exception $e){
            throw new \Exception("Error al guardar el historico stock old producto $pro_codigo");
        }
    }
    public function save_historicoStockNew($pro_codigo){
        try{
            $producto               = _14Producto::obtenerProducto($pro_codigo);
            if (!$producto) {
                throw new \Exception("No se pudo obtener el producto $pro_codigo");
            }
            $newValues = [
                'pro_codigo'         => $producto->pro_codigo,
                'pro_reservar'       => $producto->pro_reservar ?? 0,
                'pro_stock'          => $producto->pro_stock ?? 0,
                'pro_stockCalmed'    => $producto->pro_stockCalmed ?? 0,
                'pro_deposito'       => $producto->pro_deposito ?? 0,
                'pro_depositoCalmed' => $producto->pro_depositoCalmed ?? 0,
            ];
            return $newValues;
        }
        catch(\Exception $e){
            throw new \Exception("Error al guardar el historico stock new producto $pro_codigo");
        }
    }
    public function reporteCombos($periodo){
            // SELECT c.codigo_combo, c.combo, COUNT(*) AS cantidad_codigos
        // FROM codigoslibros c
        // WHERE c.prueba_diagnostica = '0'
        // AND c.codigo_combo IS NOT NULL
        // AND c.bc_periodo = '26'
        // GROUP BY c.codigo_combo, c.combo;
         $query = DB::select("SELECT
            sub.combo AS codigo,
            COUNT(DISTINCT sub.codigo_combo) AS cantidad,
            SUM(sub.cantidad) AS total_codigos,
            pr.codigos_combos,
            pr.pro_nombre AS nombrelibro,
            ls.idLibro,
            v.det_ven_valor_u AS precio
        FROM (
            SELECT c.codigo_combo, c.combo, COUNT(*) AS cantidad
            FROM codigoslibros c
            WHERE c.prueba_diagnostica = '0'
            AND c.codigo_combo IS NOT NULL
            AND c.bc_periodo = '$periodo'
            AND c.estado_liquidacion IN ('0', '1', '2')
            GROUP BY c.codigo_combo, c.combo
        ) AS sub
        LEFT JOIN `1_4_cal_producto` pr ON pr.pro_codigo = sub.combo
        LEFT JOIN libros_series ls ON ls.codigo_liquidacion = pr.pro_codigo
        LEFT JOIN f_detalle_venta v ON v.pro_codigo = pr.pro_codigo
        LEFT JOIN f_venta v2 ON v2.ven_codigo = v.ven_codigo
            AND v.id_empresa = v2.id_empresa
            AND v2.periodo_id = '$periodo'  -- Aseguramos que solo se tomen los precios del periodo específico
        WHERE v2.periodo_id = '$periodo'  -- Filtro adicional para asegurar que se obtienen solo los datos del periodo correcto
        GROUP BY sub.combo, pr.codigos_combos, pr.pro_nombre, ls.idLibro, v.det_ven_valor_u;

        ");

        foreach ($query as $key => $item) {
            // Asegurarse de que cantidad y precio no sean nulos y luego realizar el cálculo y redondear a 2 decimales
            $item->precio_total = round(($item->cantidad ?? 0) * ($item->precio ?? 0), 2);
        }

        return $query;
    }
    public function save_devolucion_codigos_desarmados($arrayCodigos, $id_devolucion)
    {

        try {
            foreach ($arrayCodigos as $item) {
                $detalle = new CodigosLibrosDevolucionDesarmadoSon();
                //valida si el codigo ya existe
                $validate = CodigosLibrosDevolucionDesarmadoSon::where('codigoslibros_devolucion_desarmados_header_id', $id_devolucion)
                    ->where('codigo', $item->codigo)
                    ->first();
                if ($validate) {
                    return "El código {$item->codigo} ya existe en la devolución.";
                }
                $detalle->codigoslibros_devolucion_desarmados_header_id = $id_devolucion;
                $detalle->libro_id                                      = $item->libro_id;
                $detalle->codigo                                        = $item->codigo;
                $detalle->codigo_union                                  = $item->codigo_union;
                $detalle->estado_liquidacion                            = $item->estado_liquidacion;
                $detalle->liquidado_regalado                            = $item->liquidado_regalado;
                $detalle->precio                                        = $item->precio;
                $detalle->estado                                        = $item->estado;
                $detalle->plus                                          = $item->plus;
                $detalle->precio                                        = $item->precio;
                $detalle->combo                                         = $item->combo;
                $detalle->codigo_combo                                  = $item->codigo_combo;
                if (!$detalle->save()) {
                    throw new \Exception("No se pudo guardar el detalle con código: {$item->codigo}");
                }
            }

            return ["success" => true, "message" => "Devolución de códigos desarmados guardada correctamente."];
        } catch (\Exception $e) {
            // Puedes registrar el error si lo necesitas, por ejemplo:
            throw new \Exception("Error al guardar la devolución de códigos desarmados: " . $e->getMessage());
        }
    }

}
