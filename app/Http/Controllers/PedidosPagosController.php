<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Models\Distribuidor\DistribuidorTemporada;
use App\Models\Models\Pagos\DistribuidorHistorico;
use App\Models\Models\Pagos\PedidosPagosHijo;
use App\Models\Models\Pagos\VerificacionHistorico;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\Models\Verificacion\VerificacionDescuento;
use App\Models\PedidoConvenio;
use App\Models\PedidoConvenioDetalle;
use App\Models\PedidoHistoricoCambios;
use App\Models\Pedidos;
use App\Models\Temporada;
use App\Models\Verificacion;
use App\Repositories\pedidos\ConvenioRepository;
use App\Repositories\pedidos\VerificacionRepository;
use App\Repositories\PedidosPagosRepository;
use App\Traits\Pedidos\TraitPagosGeneral;
use App\Traits\Pedidos\TraitPedidosGeneral;
use DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class PedidosPagosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $pagoRepository;
    private $verificacionRepository;
    private $convenioRepository;
    public function __construct(PedidosPagosRepository $repositorio,VerificacionRepository $verificacionRepository,ConvenioRepository $convenioRepository)
    {
     $this->pagoRepository          = $repositorio;
     $this->verificacionRepository  = $verificacionRepository;
     $this->convenioRepository      = $convenioRepository;
    }
    //traits
    use TraitPagosGeneral;
    use TraitPedidosGeneral;
    //api:get/pedigo_Pagos
    public function index(Request $request)
    {
        //Para traer el listado de Tipos de pagos
        if($request->getTiposPagos)                 { return $this->obtenerTiposPagos(); }
        //traer los tipos de pagos facturacion
        if($request->getFormasPago)                 { return $this->obtenerFormasPagos(); }
        //Para traer el listado de pagos con contratos
        if($request->ListadoListaPagos)             { return $this->ListadoListaPagos($request); }
        //Para traer el listado de pagos sin contratos
        if($request->ListadoListaPagosSinContrato)  { return $this->ListadoListaPagosSinContrato($request); }
        //para traer los valores de los pagos X ID
        if($request->listadoPagos)                  { return $this->listadoPagos($request); }
        //validar si no hay un pago pendiente por aprobar
        if($request->validatePagoAbierto)           { return $this->validatePagoAbierto($request->contrato); }
        //generar registros de anticipos y deudas
        if($request->generateAnticiposDeuda)        { return $this->generateAnticiposDeuda($request->id_pedido); }
        ///===METODOS VARIAS EVIDENCIAS===
        if($request->getVariasEvidencias)           { return $this->getVariasEvidencias($request->idPago); }
        //Actualizar registro de varias evidencias
        if($request->updateValuesVariasEvidencias)  { return $this->updateValuesVariasEvidencias($request->idPago); }
        //Venta real por asesor
        if($request->getVentaRealXAsesor)           { return $this->pagoRepository->getVentaRealXAsesor($request->idAsesor,$request->idPeriodo); }
        //venta total directa y lista
        if($request->getVentaTotalListaDirecta)     { return $this->pagoRepository->getVentaTotalListaDirecta($request); }
        //venta total directa y lista asesores
        if($request->getVentaTotalListaDirectaAsesor)     { return $this->pagoRepository->getVentaTotalListaDirectaAsesor($request); }
        //venta total de documentos liq
        if($request->getTotalDocumentosLiq)         { return $this->pagoRepository->getTotalDocumentosLiq($request); }
        //actualizar venta real
        if($request->updateVentaReal)               { return $this->pagoRepository->updateVentaReal($request); }
        //===ANTICIPO APROBADO====
        //aprobar pago cuando tenga anticipo aprobado
        // if($request->approveAnticipoPedidoPago)     { return $this->aprobarAnticipoPedidoPago($request->id_pedido,$request->valor); }
    }
    public function ListadoListaPagos($request){
        $query = $this->pagoRepository->getPagosxContrato($request->contrato);
        return $query;
    }
    public function ListadoListaPagosSinContrato($request){
        $query = $this->pagoRepository->getPagosSinContrato($request->institucion_id,$request->periodo_id);
        return $query;
    }
    public function listadoPagos($request){
        $query = $this->pagoRepository->getPagosXID($request->verificacion_pago_id);
        return $query;
    }
    //api:get/pedigo_Pagos?generateAnticiposDeuda=yes&id_pedido=10
    public function generateAnticiposDeuda($id_pedido){
        $fecha = date("Y-m-d H:i:s");
        $pedido = Pedidos::where('id_pedido',$id_pedido)->get();
        if(count($pedido) == 0) { return; }
        $setAnticipo                    = 0;
        //VERIFICAR QUE YA ESTE INGRESADO EN LA TABLA DOCUMENTOS
        $anticipoDeudaIngresada         = $pedido[0]->anticipoDeudaIngresada;
        $id_pedido                      = $pedido[0]->id_pedido;
        $anticipo_aprobado              = $pedido[0]->anticipo_aprobado;
        $anticipo                       = $pedido[0]->anticipo;
        $anticipoAsesor                 = $pedido[0]->anticipoAsesor;
        $ifanticipo                     = $pedido[0]->ifanticipo;
        $ifagregado_anticipo_aprobado   = $pedido[0]->ifagregado_anticipo_aprobado;
       //si no esta aprobado
        if($ifagregado_anticipo_aprobado == 0) {
            //si el anticipo deseado por el asesor es mayor a 0 coloco eso
            if($anticipoAsesor > 0){   $setAnticipo = $anticipoAsesor; }
            else{ $setAnticipo = $anticipo; }
        }
        else { $setAnticipo =  $anticipo_aprobado; }
        $contrato               = $pedido[0]->contrato_generado;
        $periodo                = $pedido[0]->id_periodo;
        $institucion            = $pedido[0]->id_institucion;
        //send data
        $data =  [
            "id"                                  => "0",
            "unicoEvidencia"                      => 0,
            "doc_numero"                          => null,
            "doc_nombre"                          => null,
            "doc_apellidos"                       => null,
            "doc_ruc"                             => null,
            "doc_cuenta"                          => null,
            "doc_institucion"                     => null,
            "doc_tipo"                            => null,
            "ven_codigo"                          => $contrato,
            "user_created"                        => 0,
            "distribuidor_temporada_id"           => null,
            "calculo"                             => 0,
            "doc_fecha"                           => $fecha,
            'institucion_id'                      => $institucion,
            'periodo_id'                          => $periodo,
            'id_pedido'                           => $id_pedido,
        ];
        //====ANTICIPO=======
        //create anticipo aprobado en documentos liq
        if($anticipo > 0 && $ifanticipo == 1){
            if($setAnticipo){
                 //validar si ya exsite un registro de pago con anticipo aprobado no se cree
                $query = $this->pagoRepository->getPagosInstitucion(0,0,5,'id_pedido',$id_pedido,'ifAntAprobado',1,null);
                if(count($query) > 0) {
                    //vamos actualizar el anticipo hasta que tenga contrato
                    // if($contrato == "" || $contrato == "null" || $contrato == null){
                        $query = $this->pagoRepository->getPagosInstitucion(0,0,5,'id_pedido',$id_pedido,'ifAntAprobado',1,1)
                        ->update([
                            "doc_valor"                         => $setAnticipo,
                        ]);
                    //}
                }else{
                    //si no hay convenio genero el anticipo
                    $setData = [
                        "doc_valor"                         => $setAnticipo,
                        "doc_ci"                            => 1,
                        "tipo_pago_id"                      => 1,
                        "ifAntAprobado"                     => 1,
                        "forma_pago_id"                     => 1,
                        "doc_observacion"                   => "Anticipo Del pedido",
                    ];
                    $result = array_merge($data,$setData);
                    $request = (Object) $result;
                    $this->pagoRepository->saveDocumentosLiq($request);
                }
            }
        }
        //====DEUDA=====
        if($anticipoDeudaIngresada == 0){
            $deuda          = $pedido[0]->deuda;
            $periodo_deuda  = $pedido[0]->periodo_deuda;
            //create deuda
            if($deuda > 0){
                $setData =  [
                    "doc_valor"                         => $deuda,
                    "doc_ci"                            => 1,
                   "tipo_pago_id"                       => 6,
                   "forma_pago_id"                      => 1,
                   "doc_observacion"                    => $periodo_deuda,
                   "ifAntAprobado"                      => 0,
                 ];
                 $result = array_merge($data,$setData);
                 $request = (Object) $result;
                 $this->pagoRepository->saveDocumentosLiq($request);
                 $setPedido = Pedidos::findOrFail($id_pedido);
                 $setPedido->anticipoDeudaIngresada = 1;
                 $setPedido->save();
            }
        }
        //====CONVENIO====
        $getConvenio = $this->obtenerConvenioInstitucionPeriodo($institucion,$periodo);
        if(count($getConvenio) > 0){
            $idConvenio     = $getConvenio[0]->id;
            $global         = $getConvenio[0]->anticipo_global;
            if($global > 1000){
               $this->convenioRepository->registrarConvenioHijo($id_pedido,$idConvenio,$contrato,$institucion,$periodo);
            }
        }
        //====CON CONTRATO=======
        if($contrato == "" || $contrato == "null" || $contrato == null){ }
        else{
            //CUPONES
            $this->setCuponesContrato($data,$contrato);
            //VENTA DIRECTA
            $this->setVentaDirecta($data,$contrato);
            //TOTAL COMISION
            //return $this->setTotalComision($contrato,$id_pedido,$institucion,$periodo);
        }
        return  ["status" => "1", "message" => "Se guardo correctamente"];
    }
    public function getVariasEvidencias($id){
        $hijos = PedidosPagosHijo::Where('documentos_liq_id',$id)->get();
        return $hijos;
    }
    public function setCuponesContrato($data,$contrato){
        $query = DB::SELECT("SELECT * FROM verificaciones_descuentos d
        WHERE d.contrato = ?
        AND d.restar = '0'
        AND d.estado = '1'
        AND d.ingreso_documento_liq = '0'
        ",[$contrato]);
        foreach($query as $key => $item){
            $setData =  [
                "doc_valor"                         => $item->total_descuento,
                "doc_ci"                            => 1,
               "tipo_pago_id"                       => 1,
               "forma_pago_id"                      => 11,
               "doc_observacion"                    => $item->nombre_descuento,
               "ifAntAprobado"                      => 0,
             ];
             $result = array_merge($data,$setData);
             $request = (Object) $result;
            $this->pagoRepository->saveDocumentosLiq($request);
            $query = VerificacionDescuento::Where('id',$item->id)
            ->update([
                'ingreso_documento_liq' => 1
            ]);
        }
    }
    public function setVentaDirecta($data,$contrato){
        //validar que todavia no este aprobado
        $validate2 = DB::SELECT("SELECT * FROM 1_4_documento_liq l
        WHERE l.ven_codigo = ?
        AND l.forma_pago_id = '12'
        AND l.estado = '1'
        ",[$contrato]);
        //si ya existe un registro de venta directa aprobado no se registra
        if(count($validate2) > 0) { return; }
        $validate = DB::SELECT("SELECT * FROM 1_4_documento_liq l
        WHERE l.ven_codigo = ?
        AND l.forma_pago_id = '12'
        AND l.estado = '0'
        ",[$contrato]);
        //traer las verificaciones con  los cobros de venta directa
        //cobro_venta_directa_ingresada = 0 no ingresada ; 1 ingresada
        if(count($validate) > 0) { return; }
        $query = DB::SELECT("SELECT * FROM verificaciones v
        WHERE v.contrato = ?
        AND v.cobro_venta_directa = '2'
        AND v.tipoPago = '2'
        AND v.cobro_venta_directa_ingresada = '0'
        ",[$contrato]);
        //si no hay cobros de venta directa no hago nada
        if(count($query) == 0) { return; }
        $totalCobroVentaDirecta = 0;
        //si hay cobros de venta directa los guardo
        foreach($query as $key => $item){
           $totalCobroVentaDirecta = $totalCobroVentaDirecta + $item->totalCobroVentaDirecta;
        }
        $setData =  [
            "doc_valor"                         => $totalCobroVentaDirecta,
            "doc_ci"                            => 1,
           "tipo_pago_id"                       => 1,
           "forma_pago_id"                      => 12,
           "doc_observacion"                    => "Cobro de Venta directa",
           "ifAntAprobado"                      => 0,
         ];
         $result = array_merge($data,$setData);
         $request = (Object) $result;
        $this->pagoRepository->saveDocumentosLiq($request);
        //actualizar verificaciones con los cobros de venta directa ingresados
        $this->pagoRepository->updateVentaDirecta($contrato);
    }
    //guardar total comision
    // public function setTotalComision($contrato,$id_pedido,$institucion,$periodo){
    //     $query = DB::SELECT("SELECT
    //     u.nombres, u.apellidos,u.cedula,
    //     b.* FROM pedidos_beneficiarios b
    //     LEFT JOIN usuario u ON b.id_usuario = u.idusuario
    //     WHERE b.id_pedido = ?
    //     AND b.comision_real > 0
    //     ",[$id_pedido]);
    //     if(count($query) == 0) { return; }
    //     foreach($query as $key => $item){
    //         $getBeneficiarioPago = PedidosDocumentosLiq::Where('beneficiario_id',$item->id_beneficiario_pedido)->get();
    //         //si ya existe un registro de comision edito el valor
    //         if(count($getBeneficiarioPago) > 0){
    //             //si esta aprobado ya no edito
    //             if($getBeneficiarioPago[0]->estado == 1) { return; }
    //             // return $getBeneficiarioPago;
    //             $hijoConvenio = PedidosDocumentosLiq::findOrFail($getBeneficiarioPago[0]->doc_codigo);
    //         }
    //         //si no existe un registro de comision lo creo
    //         else{
    //             $hijoConvenio                           = new PedidosDocumentosLiq();
    //             $hijoConvenio->doc_observacion          = "Total Comisión de venta " . $item->porcentaje_real . "%";
    //             $hijoConvenio->doc_nombre               = $item->nombres;
    //             $hijoConvenio->doc_apellidos            = $item->apellidos;
    //             $hijoConvenio->doc_cuenta               = $item->num_cuenta;
    //             $hijoConvenio->doc_institucion          = $item->banco;
    //             $hijoConvenio->doc_tipo                 = $item->tipo_cuenta;
    //             $hijoConvenio->periodo_id               = $periodo;
    //             $hijoConvenio->institucion_id           = $institucion;
    //             $hijoConvenio->tipo_pago_id             = 1;
    //             $hijoConvenio->forma_pago_id            = 1;
    //             $hijoConvenio->beneficiario_id          = $item->id_beneficiario_pedido;
    //             $hijoConvenio->id_pedido                = $id_pedido;
    //             $hijoConvenio->doc_ruc                  = $item->cedula;
    //         }
    //         $hijoConvenio->doc_fecha                    = date("Y-m-d H:i:s");
    //         $hijoConvenio->ven_codigo                   = $contrato;
    //         $hijoConvenio->doc_valor                    = $item->comision_real;
    //         $hijoConvenio->doc_ruc                  = $item->cedula;
    //         $hijoConvenio->save();
    //     }
    // }
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
    //api:post/pedigo_Pagos
    public function store(Request $request)
    {
        if($request->saveValorPago){
            return $this->saveValorPago($request);
        }
        if($request->aprobarPagoVerificacion){
            return $this->aprobarPagoVerificacion($request);
        }
        //metodos git card varios pagos
        if($request->saveVariasEvidencias){
            return $this->saveVariasEvidencias($request);
        }
        if($request->reabrirPagos){
            return $this->reabrirPagos($request);
        }
        //guardar deuda proximo automatico
        if($request->RegistroDeudaAutomatica){
            return $this->RegistroDeudaAutomatica($request);
        }
    }
    public function saveValorPago($request){
        $tipo_pago_id   = $request->tipo_pago_id;
        $institucion_id = $request->institucion_id;
        $periodo_id     = $request->periodo_id;
        $doc_codigo     = $request->id;
        $ifAntAprobado  = $request->ifAntAprobado;
        $user_created   = $request->user_created;
        $mensaje        = "";
        //validacion que solo puede existir un unico pago por convenio
        if($tipo_pago_id == 4){
            $query = $this->pagoRepository->getPagosInstitucion($institucion_id,$periodo_id,1,$doc_codigo);
            if(count($query) > 0)   { return ["status" => "0", "message" => "Ya existe un pago de convenio solo puede existir uno"]; }
        }
        //validacion que solo puede existir un unico pago por anticipo del pedido
        if($tipo_pago_id == 1)
        {
            if($ifAntAprobado == 1){
                $query = $this->pagoRepository->getPagosInstitucion($institucion_id,$periodo_id,4,$doc_codigo,'ifAntAprobado','1');
                if(count($query) > 0)   { return ["status" => "0", "message" => "Ya existe un anticipo del pedido registrado"]; }
            }
        }
        //Pago Tipo “otros valores para cancelar”
        /*
            •	Este pago aparecer en el reporte
            •	Solo puede haber un solo pago de otro valor para cancelar
        */
        if($tipo_pago_id == 7){
            $query = $this->pagoRepository->getPagosInstitucion($institucion_id,$periodo_id,6,7,1);
            if(count($query) > 0)   { return ["status" => "0", "message" => "Ya existe un pago de otros valores por cancelar, solo puede existir uno"]; }
        }
        if($request->id > 0){
            $mensaje = "Se edito el pago";
            $info = PedidosDocumentosLiq::findOrFail($request->id);
            $Estado = $info->estado;
            if($request->permisoPago){
                //si el root actualiza el pago de la deuda anterior
            }
            else{
                if($Estado == 1)        { return ["status" => "0", "message" => "El pago ya ha sido aprobado no se puede modificar"]; }
                if($Estado == 2)        { return ["status" => "0", "message" => "El pago ya ha sido rechazado no se puede modificar"]; }
            }
            //El pago se guarda en historico
            $this->verificaciones_historico($info->ven_codigo,$user_created,$mensaje,0,$periodo_id,$institucion_id,$info->id_pedido,$info);
        }
        return $this->pagoRepository->saveDocumentosLiq($request);
    }

    public function aprobarPagoVerificacion($request){
        //limpiar cache
        Cache::flush();
        $contrato               = $request->contrato;
        $user_created           = $request->user_created;
        $periodo_id             = $request->periodo_id;
        $institucion_id         = $request->institucion_id;
        $id_pedido              = $request->id_pedido;
        $valor                  = $request->valor;
        $tipo_pago_id           = $request->tipo_pago_id;
        //Pago Tipo “otros valores para cancelar”
        /*
            •	Este pago aparecer en el reporte
            •	Solo puede haber un solo pago de otro valor para cancelar
        */
        if($tipo_pago_id == 7){
            $query = $this->pagoRepository->getPagosInstitucion($institucion_id,$periodo_id,6,7,1);
            if(count($query) > 0)   { return ["status" => "0", "message" => "Ya existe un pago aprobado de tipo otro valores para cancelar solo puede haber uno"]; }
        }
        // $tipo_pago           = $request->tipo_pago;
        $observacion            = "Aprobación de pago";
        $doc_codigo             = $request->doc_codigo;
        $info                   = PedidosDocumentosLiq::findOrFail($doc_codigo);
        //0 => sin pagar ; 1 => pagado
        $EstadoPago             = $info->estado;
        if($EstadoPago == 1) { return ["status" => "0", "message" => "El pago ya ha sido aprobado anteriormente"]; }
        if($EstadoPago == 2) { return ["status" => "0", "message" => "El pago ha sido desactivado anteriormente"]; }
        //MARCAR LA SOLICITUD DE PAGO COMO PAGADO
        $info->estado = 1;
        $info->fecha_cierre = date('Y-m-d H:i:s');
        $info->user_cierre  = $request->user_created;
        $info->save();
        if($info){
            //GUARDAR EN HISTORICO EL PAGO
            $this->verificaciones_historico($contrato,$user_created,$observacion,$valor,$periodo_id,$institucion_id,$id_pedido,$info);
            //SI EL PAGO ES DEUDA DE FORMA ANTERIOR ANTICIPO Y OBSERVACION DEUDA
            //si el tipo_pago_id  es 1 y contiene la palabra deuda en el doc_observacion
            if ($info->tipo_pago_id == 1 && $info->doc_observacion !== null) {
                $doc_observacion = strtolower($info->doc_observacion);
                if (strpos($doc_observacion, 'deuda') !== false) {
                    $this->pagoRepository->updateDeudaMetodoAnterior($id_pedido);
                }
            }
            //SI EL PAGO ES UNA DEUDA ANTERIOR SUMO LAS DEUDAS
            if($request->tipo_pago_id == 6)  { $this->pagoRepository->updateDeuda($request->id_pedido); }
            //SI EL PAGO ES UNA DEUDA PROXIMA SUMO LAS DEUDAS PROXIMAS
            if($request->tipo_pago_id == 3)  { $this->pagoRepository->updateDeudaProxima($request->id_pedido); }
            //actualizar movimientos convenio
            if ($info->tipo_pago_id == 4) {
                $old_values = PedidosDocumentosLiq::findOrFail($request->doc_codigo);
                $convenio   = PedidoConvenio::where('id', $old_values->pedidos_convenios_id)->first();
                //si no existe el convenio no se puede guardar
                if(!$convenio) { return ["status" => "0", "message" => "No existe convenio activo para crear el pago"]; }
                //si el convenio ya esta cerrado donde esta el pago de convenio no se pueda reabrir
                $estadoConvenio = $convenio->estado;
                if($estadoConvenio == 2){
                    return ["status" => "0", "message" => "No se puede reabrir el pago porque el convenio ya esta cerrado"];
                }
                $convenio = PedidoConvenio::where('id', $info->pedidos_convenios_id)->first();
                if ($convenio) { $this->convenioRepository->saveMovimientosConvenio($info->pedidos_convenios_id); }
            }
            if($info) { return ["status" => "1", "message" => "Se guardo correctamente"]; }
        }
    }
    public function saveVariasEvidencias($request){
        if($request->id > 0){ $plataforma = PedidosPagosHijo::find($request->id); }
        else                { $plataforma = new PedidosPagosHijo(); }
        $plataforma->valor                = $request->valor;
        $plataforma->codigo               = $request->codigo;
        $plataforma->documentos_liq_id    = $request->documentos_liq_id;
        $plataforma->user_created         = $request->user_created;
        $plataforma->save();
        //sumar los valores de las git cards en el registro padre
        $this->updateValuesVariasEvidencias($request->documentos_liq_id);
        return $plataforma;
    }
    public function updateValuesVariasEvidencias($documentos_liq_id){
        $query = PedidosPagosHijo::Where('documentos_liq_id',$documentos_liq_id)->get();
        $suma  = 0;
        if(count($query) > 0){
            foreach($query as $key => $item){
                $suma = $suma + $item->valor;
            }
            $padre = PedidosDocumentosLiq::findOrFail($documentos_liq_id);
            $padre->doc_valor = $suma;
            $padre->save();
        }

    }
    public function reabrirPagos($request){
        try{
            $user_created = $request->user_created;
            $old_values = PedidosDocumentosLiq::findOrFail($request->doc_codigo);
            $query = PedidosDocumentosLiq::Where('doc_codigo',$request->doc_codigo)
            ->update([
                "estado" => 0
            ]);
            //save en historico
            $observacion = "Reapertura de pago";
            $this->verificaciones_historico($old_values->ven_codigo,$user_created,$observacion,0,$old_values->periodo_id,$old_values->institucion_id,$old_values->id_pedido,$old_values);
            //si son deudas anteriores  cuando se reabren se cambio a estado 0 de  pendiente por lo tanto vuelvo a sumar las deudas anteriores activas
            if($old_values->tipo_pago_id == 6)  { $this->pagoRepository->updateDeuda($old_values->id_pedido); }
            //si el tipo_pago_id  es 1 y contiene la palabra deuda en el doc_observacion
            if ($old_values->tipo_pago_id == 1 && $old_values->doc_observacion !== null) {
                $doc_observacion = strtolower($old_values->doc_observacion);
                if (strpos($doc_observacion, 'deuda') !== false) { $this->pagoRepository->updateDeudaMetodoAnterior($old_values->id_pedido); }
            }
            //si son deudas proximas  cuando se reabren se cambio a estado 0 de  pendiente por lo tanto vuelvo a sumar las deudas proximas activas
            if($old_values->tipo_pago_id == 3)  { $this->pagoRepository->updateDeudaProxima($old_values->id_pedido); }
            //actualizar movimientos convenio
            if ($old_values->tipo_pago_id == 4) {
                $convenio = PedidoConvenio::where('id', $old_values->pedidos_convenios_id)->first();
                //si el convenio ya esta cerrado donde esta el pago de convenio no se pueda reabrir
                $estadoConvenio = $convenio->estado;
                if($estadoConvenio == 2){
                    return ["status" => "0", "message" => "No se puede reabrir el pago porque el convenio ya esta cerrado"];
                }
                if ($convenio) { $this->convenioRepository->saveMovimientosConvenio($old_values->pedidos_convenios_id); }
            }
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }catch(\Exception $ex){
            return ["status" => "0", "message" => "No se pudo guardar", "error" =>"error: ".$ex];
        }
    }
    //API:POST/pedigo_Pagos/RegistroDeudaAutomatica
    public function RegistroDeudaAutomatica($request){
        $fecha                                    = date("Y-m-d H:i:s");
        $contrato                                 = $request->contrato;
        $institucion                              = $request->institucion_id;
        $periodo                                  = $request->periodo_id;
        $id_pedido                                = $request->id_pedido;
        $doc_valor                                = $request->doc_valor;
        $valor                                    = 0;
        $getId                                    = 0;
        $query = PedidosDocumentosLiq::Where('id_pedido',$id_pedido)->where('tipo_pago_id','3')->get();
        if(count($query) > 0) {                  $getId = $query[0]->doc_codigo;}
        else                                    { $getId = 0; }
        $data =  [
            "id"                                  => $getId,
            "unicoEvidencia"                      => 0,
            "doc_numero"                          => null,
            "doc_nombre"                          => null,
            "doc_apellidos"                       => null,
            "doc_ruc"                             => null,
            "doc_cuenta"                          => null,
            "doc_institucion"                     => null,
            "doc_tipo"                            => null,
            "ven_codigo"                          => $contrato,
            "user_created"                        => 0,
            "distribuidor_temporada_id"           => null,
            "calculo"                             => 0,
            "doc_fecha"                           => $fecha,
            'institucion_id'                      => $institucion,
            'periodo_id'                          => $periodo,
            'id_pedido'                           => $id_pedido,
        ];
        //si el valor es mayor a  guardo con cero
        $setData =  [
            "doc_valor"                         => $doc_valor,
            "doc_ci"                            => 3,
           "tipo_pago_id"                       => 3,
           "forma_pago_id"                      => 1,
           "doc_observacion"                    => "Deuda próxima",
           "ifAntAprobado"                      => 0,
           "estado"                             => 1
        ];
        $result  = array_merge($data,$setData);
        $request = (Object) $result;
        $this->pagoRepository->saveDocumentosLiq($request);
        //SUMAR TODAS LAS DEUDAS PROXIMAS
        $deuda = $this->pagoRepository->obtenerDeudasProximas($id_pedido);
        $valor = 0;
        foreach($deuda as $key => $item){
            $valor = $valor + $item->doc_valor;
        }
        return $valor;
    }
    public function DescontarDistribuidor($request){
        $contrato               = $request->contrato;
        $user_created           = $request->user_created;
        $verificacion_pago_id   = $request->verificacion_pago_id;
        $periodo_id             = $request->periodo_id;
        $query = $this->pagoRepository->getPagosXID($verificacion_pago_id);
        foreach($query as $key => $item){
            $saldo_anterior  = $item->saldo_actual;
            $saldo_nuevo     = $item->saldo_actual - $item->valor;
            $distribuidor_id = $item->distribuidor_temporada_id;
            $distribuidorT = DistribuidorTemporada::findOrFail($item->distribuidor_temporada_id);
            $distribuidorT->saldo_actual = $saldo_nuevo;
            $distribuidorT->save();
            if($distribuidorT){
                //HISTORICO DISTRIBUIDOR
                $this->saveHistoricoDistribuidor($distribuidor_id,$periodo_id,$saldo_anterior,$saldo_nuevo,$contrato,$user_created);
            }
        }
    }
    public function saveHistoricoDistribuidor($distribuidor_id,$periodo_id,$saldo_anterior,$saldo_nuevo,$contrato,$user_created){
        $historico = new DistribuidorHistorico();
        $historico->distribuidor_id = $distribuidor_id;
        $historico->periodo_id      = $periodo_id;
        $historico->saldo_anterior  = $saldo_anterior;
        $historico->saldo_actual    = $saldo_nuevo;
        $historico->contrato        = $contrato;
        $historico->user_created    = $user_created;
        $historico->save();
    }
    public function verificaciones_historico($contrato,$user_created,$observacion,$valor_abonado,$periodo_id,$institucion_id,$id_pedido,$old_values){
        $historico = new VerificacionHistorico();
        $historico->contrato            = $contrato == null || $contrato == "null" ? null : $contrato;
        $historico->user_created        = $user_created;
        $historico->observacion         = $observacion;
        $historico->valor_abonado       = $valor_abonado;
        $historico->periodo_id          = $periodo_id;
        $historico->institucion_id      = $institucion_id;
        $historico->id_pedido           = $id_pedido;
        $historico->old_values          = $old_values;
        $historico->save();
    }
       //API:POST/editarDocumentoLiq
       public function editarDocumentoLiq(Request $request){
        $documento = PedidosDocumentosLiq::findOrFail($request->doc_codigo);
        $documento->doc_valor       = $request->doc_valor;
        $documento->doc_ci          = $request->tipo_pago_id;
        $documento->forma_pago_id   = $request->forma_pago_id;
        $documento->tipo_pago_id    = $request->tipo_pago_id;
        $documento->doc_numero      = $request->doc_numero;
        $documento->doc_observacion = $request->doc_observacion == null || $request->doc_observacion == 'null'? null : $request->doc_observacion;
        $documento->save();
        if($documento){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0","message" => "No se pudo guardar"];
        }
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
    public function eliminarPagos(Request $request){
        if($request->eliminarPagoAnticipo){ return $this->eliminarPagoAnticipo($request);  }
        if($request->eliminarPagoDeuda)   { return $this->eliminarPagoDeuda($request); }
        if($request->eliminarPagoNormal)  { return $this->eliminarPagoNormal($request); }
    }
    public function eliminarPagoAnticipo($request){
        $id_pedido      = $request->id_pedido;
        $user_created   = $request->user_created;
        $mensaje        = "Eliminación de anticipo del pedido";
        $documento      = PedidosDocumentosLiq::findOrFail($request->doc_codigo);
        $documento->delete();
        ///Cuando elimino el anticipo del pedido dejo el anticipo aprobado en cero
        Pedidos::Where('id_pedido',$id_pedido)
        ->update([
            "anticipo_aprobado" => 0
        ]);
        //guarar en historico
        $this->verificaciones_historico($documento->ven_codigo,$user_created,$mensaje,0,$documento->periodo_id,$documento->institucion_id,$documento->id_pedido,$documento);
        return $documento;
    }
    public function eliminarPagoDeuda($request){
        $id_pedido      = $request->id_pedido;
        $doc_valor      = $request->doc_valor;
        $user_created   = $request->user_created;
        $mensaje        = "Eliminación de deuda";
        $documento = PedidosDocumentosLiq::findOrFail($request->doc_codigo);
        $documento->delete();
        //update pedido
        $pedido = Pedidos::findOrfail($id_pedido);
        //si la deuda de documentos liq es la misma que el valor de la deuda de pedidos vacio la deuda del pedido
        $deuda = $pedido->deuda;
        if($deuda == $doc_valor){
            Pedidos::Where('id_pedido',$id_pedido)
            ->update([
                "deuda" => 0
            ]);
        }
        //guarar en historico
        $this->verificaciones_historico($documento->ven_codigo,$user_created,$mensaje,0,$documento->periodo_id,$documento->institucion_id,$documento->id_pedido,$documento);
        return $documento;
    }
    public function eliminarPagoNormal($request){
        $user_created   = $request->user_created;
        $ven_codigo     = $request->ven_codigo;
        $periodo_id     = $request->periodo_id;
        $institucion_id = $request->institucion_id;
        $old_values     = $request->old_values;
        $id_pedido      = $request->id_pedido;
        $mensaje        = "Eliminación de pago";
        //guarar en historico
        $this->verificaciones_historico($ven_codigo,$user_created,$mensaje,0,$periodo_id,$institucion_id,$id_pedido,$old_values);
        //si el tipo_pago_id  es 1 y contiene la palabra deuda en el doc_observacion
        if ($request->tipo_pago_id == 1 && $request->doc_observacion !== null) {
            $doc_observacion = strtolower($request->doc_observacion);
            if (strpos($doc_observacion, 'deuda') !== false) {
                $this->pagoRepository->updateDeudaMetodoAnterior($id_pedido);
            }
        }
        //si es deuda anterior vuelvo a sumar las deudas
        if($request->tipo_pago_id == 6)  { $this->pagoRepository->updateDeuda($id_pedido); }
        //si es deuda proxima vuelvo a sumar las deudas proximas
        if($request->tipo_pago_id == 3)  { $this->pagoRepository->updateDeudaProxima($id_pedido); }
        //desactivar para que no se genere automaticamente las venta directas
        if($request->forma_pago_id == 12) { $this->pagoRepository->updateVentaDirecta($ven_codigo); }
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

//MANUAL DISTRIBUIDOR
//======== PROCESO   REGRESAR UN VALOR DESPUES DE AVER APROBADO=============================

//COLOQUE EL CAMPO "ESTADO" A CERO COMO NO PAGADO (TABLA VERIFICACIONES_PAGO CAMPO ESTADO)
//EL ABONO EN VERIFICACION DEL CONTRATO REGRESELO A LO ANTERIOR(TABLA VERIFICACIONES)
//EL VALOR ACTUAL DEL DISTRIBUIDOR REGRESELO A LO ANTERIOR(TABLA DISTRIBUIDOR_TEMPORADA)
//ELIMINE DEL HISTORICO (ELIMINE DISTRIBUIDOR HISTORICO)
//ELIMINE DEL HISTORICO (VERIFICACIONES_HISTORICO)
/******FIN PROCESO */
