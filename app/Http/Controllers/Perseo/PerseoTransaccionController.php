<?php

namespace App\Http\Controllers\Perseo;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\Ventas;
use App\Models\VentasF;
use App\Models\CuentaBancaria;
use App\Repositories\perseo\PerseoConsultasRepository;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerseoTransaccionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    use TraitPedidosGeneral;
    public $perseoConsultaReposiory;
    protected $perseoProduccion; // Definida aquí
    public function __construct(PerseoConsultasRepository $perseoConsultasRepository)
    {
        $this->perseoConsultaReposiory = $perseoConsultasRepository;
        $this->perseoProduccion = $this->gl_perseoProduccion; // Asignar en el constructor
    }
    /*
        Procedimiento para insertar una nueva proforma en el sistema contable de acuerdo con los datos que se envía en formato JSON.
    */
    //api:post/perseo/transaccion/proformas_crear
    public function proformas_crear(Request $request)
    {
        try {
            DB::beginTransaction();
            $factura        = $request->ven_codigo; //F-S23-ER-0000073
            $empresa        = $request->id_empresa;
            //$factura        = "PF-C23-MFACT-0000095";
            $concepto       = "Proforma";
            $observacion    = "Proforma"; //observacion
            $getFactura     = Ventas::where('ven_codigo',$factura)->where('id_empresa',$empresa)->first();
            //validar que exista la factura
            if(!$getFactura)                        { return ["status" => "0", "message" => "La factura no existe"]; }
            //validar si la factura ya fue enviada a Perseo
            if($getFactura->estadoPerseo == 1)      { return ["status" => "0", "message" => "La factura ya fue enviada a Perseo"]; }
            //valor total de la factura - el descuento  - el transporte
            $ven_valor       = $getFactura->ven_valor;
            $ven_descuento   = $getFactura->ven_descuento;
            $id_empresa      = $getFactura->id_empresa;
            $clientesidPerseo = $getFactura->clientesidPerseo;
            $discount        = $getFactura->ven_desc_por;
            $totalFactura    = 0;
            $detalle         = [];
            //prolipa
            // if($id_empresa == 1){
            //     $productoBuscar = '';
            //     if($this->perseoProduccion == 0) { $productoBuscar = 'id_perseo_prolipa'; }
            //     else                             { $productoBuscar = 'id_perseo_prolipa_produccion'; }
            //     $detalle = DB::SELECT("SELECT vd.*, (vd.det_ven_cantidad * vd.det_ven_valor_u) AS valorTotal,`$productoBuscar` as idPerseoProducto
            //         FROM f_detalle_venta vd
            //         LEFT JOIN 1_4_cal_producto p ON vd.pro_codigo = p.pro_codigo
            //         WHERE vd.ven_codigo = ?
            //     ",[$factura]);
            // }
            // //calmed
            // if($id_empresa == 3){
            //     $productoBuscar = '';
            //     if($this->perseoProduccion == 0) { $productoBuscar = 'id_perseo_calmed'; }
            //     else                             { $productoBuscar = 'id_perseo_calmed_produccion'; }
            //     $detalle = DB::SELECT("SELECT vd.*, (vd.det_ven_cantidad * vd.det_ven_valor_u) AS valorTotal,`$productoBuscar as idPerseoProducto
            //         FROM f_detalle_venta vd
            //         LEFT JOIN 1_4_cal_producto p ON vd.pro_codigo = p.pro_codigo
            //         WHERE vd.ven_codigo = ?
            //     ",[$factura]);
            // }
              // Definir el nombre de la columna según la empresa
            if ($id_empresa == 1) {
                $productoBuscar = $this->perseoProduccion == 0 ? 'id_perseo_prolipa' : 'id_perseo_prolipa_produccion';
            } elseif ($id_empresa == 3) {
                $productoBuscar = $this->perseoProduccion == 0 ? 'id_perseo_calmed' : 'id_perseo_calmed_produccion';
            } else {
                throw new \Exception('ID de empresa no válido');
            }
            // Construir la consulta usando el query builder
            $detalle = DB::table('f_detalle_venta as vd')
                ->select('vd.*', DB::raw('(vd.det_ven_cantidad * vd.det_ven_valor_u) AS valorTotal'), DB::raw("`$productoBuscar` AS idPerseoProducto"))
                ->leftJoin('1_4_cal_producto as p', 'vd.pro_codigo', '=', 'p.pro_codigo')
                ->where('vd.ven_codigo', $factura)
                ->where('vd.id_empresa','=',$empresa)
                ->get();
            if(empty($detalle)) { return ["status" => "0", "message" => "La factura no tiene detalle"]; }
            foreach( $detalle as $d){ $totalFactura += $d->valorTotal; }
            //con 2 decimales
            $totalFactura   = number_format($totalFactura, 2, '.', '');
            $detalles = [];
            foreach($detalle as $d){
                $pro_codigo = $d->pro_codigo;
                $id_perseo = $d->idPerseoProducto;
                if($id_perseo == 0 || $id_perseo == null || $id_perseo == ""){
                    return ["status" => "0", "message" => "El codigo $pro_codigo no se encuentra en perseo"];
                }
                $detalles[] = [
                    "proformasid"               => 1,
                    "centros_costosid"          => 1,
                    "productosid"               => $d->idPerseoProducto,
                    "medidasid"                 => 1,
                    "almacenesid"               => 1,
                    "cantidaddigitada"          => $d->det_ven_cantidad,//Cantidad del producto pedido
                    "cantidad"                  => $d->det_ven_cantidad,//Resultado que se obtiene al multiplicar cantidaddigitada*cantidadfactor. Es la cantidad real que se va a utilizar en base a la medida con la que se está trabajando.
                    "cantidadfactor"            => 1,
                    "precio"                    => $d->det_ven_valor_u,
                    "preciovisible"             => $d->det_ven_valor_u,
                    "iva"                       => 0,
                    "precioiva"                 => $d->det_ven_valor_u,
                    "descuento"                 => $discount //consulta //Porcentaje de descuento que se va a aplicar a cada producto.
                ];
            }
            $formData = [
                "registro" => [
                    [
                        "proformas" => [
                            "proformasid"                   => 1,
                            "emision"                       => date('Ymd'),
                            "proformas_codigo"              => "P000000001",
                            "forma_pago_empresaid"          => 1,
                            "facturadoresid"                => 1,
                            "clientesid"                    => $clientesidPerseo,
                            "almacenesid"                   => 1,
                            "centros_costosid"              => 1,
                            "vendedoresid"                  => 3,
                            "tarifasid"                     => 1,
                            "concepto"                      => $concepto,
                            "origen"                        => "0",//Parametros utilizado si la proforma ya esta transformada en factura
                            "documentosid"                  => 0,//Guarda el id de la factura a la que fue transformada la proforma
                            "observacion"                   => $observacion,
                            "subtotalsiniva"                => $ven_valor,//Valor sin incluir el IVA total de los productos que no apliquen IVA.
                            "subtotalconiva"                => $ven_valor,//Valor sin incluir el IVA total de los productos que apliquen IVA.
                            "total_descuento"               => $ven_descuento,//Valor total del descuento correspondiente, se obtiene sumando el descuento aplicado a cada precio sin IVA de cada producto.
                            "subtotalneto"                  => $ven_valor,//Valor total sin incluir IVA pero restando el descuento correspondiente.
                            "total_iva"                     => 0,//Valor total del IVA.
                            "total"                         => $ven_valor,//Valor total de la factura.
                            "empresaid"                     => 1,//Valores adicionales, utilizados para la auditoria
                            "usuarioid"                     => 3,//Valores adicionales, utilizados para la auditoria
                            "usuariocreacion"               => "IMOVIL",
                            "fechacreacion"                 => date('Y-m-d H:i:s'),
                            "detalles"                      => $detalles
                        ]
                    ]
                ]
            ];
            $url        = "proformas_crear";
            $process    = $this->tr_PerseoPost($url, $formData,$id_empresa);
            //si existe proccess["proformas"] enviar a perseo
            //actualizar a 1 en la tabla f_venta modelo Ventas campo estadoPerseo a 1
            if(isset($process["proformas"])) {
                $proformasid_nuevo   = $process["proformas"][0]["proformasid_nuevo"];
                $proformas_codigo    = $process["proformas"][0]["proformas_codigo"];
                Ventas::where('ven_codigo',$factura)->where('id_empresa',$empresa)->update(['estadoPerseo' => 1,"idPerseo" => $proformasid_nuevo, "proformas_codigo" => $proformas_codigo, "fecha_envio_perseo" => date('Y-m-d H:i:s') ]);
            }
            else{
                Ventas::where('ven_codigo',$factura)->where('id_empresa',$empresa)->update(['estadoPerseo' => 0 ]);
            }
            //transaccion de laravel commit
            DB::commit();
            return $process;
        } catch (\Exception $e) {
            DB::rollBack();
            return ["status" => "0", "message" => "Ocurrio un eror al intentar mandar la proforma a perseo", "error" => $e->getMessage()];
        }
    }
    /*
        Procedimiento para insertar un nuevo pedido en el sistema contable de acuerdo con los datos que se envía en formato JSON.
    */
    //api:post/perseo/transaccion/pedidos_crear
    public function pedidos_crear(Request $request)
    {
        try {
            DB::beginTransaction();
            $factura        = $request->ven_codigo; //F-C23-ER-0000076
            // $factura        = "F-C23-ER-0000076";
            $empresa        = $request->id_empresa;
            $observacion    = "Pedido"; //observacion
            $concepto       = "Pedido";
            $getFactura = VentasF::where('id_factura',$factura)->where('id_empresa',$empresa)->first();
            //validar que exista la factura
            if(!$getFactura)                        { return ["status" => "0", "message" => "La factura no existe"]; }
            //validar si la factura ya fue enviada a Perseo
            if($getFactura->estadoPerseo == 1)      { return ["status" => "0", "message" => "La factura ya fue enviada a Perseo"]; }
            //valor total de la factura - el descuento  - el transporte
            $ven_valor       = $getFactura->ven_valor;
            $ven_descuento   = $getFactura->ven_descuento;
            $id_empresa      = $getFactura->id_empresa;
            $clientesidPerseo = $getFactura->clientesidPerseo;
            $discount         = $getFactura->ven_desc_por;
            $totalFactura    = 0;
            $detalle         = [];

            if ($id_empresa == 1) {
                $productoBuscar = $this->perseoProduccion == 0 ? 'id_perseo_prolipa' : 'id_perseo_prolipa_produccion';
            } elseif ($id_empresa == 3) {
                $productoBuscar = $this->perseoProduccion == 0 ? 'id_perseo_calmed' : 'id_perseo_calmed_produccion';
            } else {
                throw new \Exception('ID de empresa no válido');
            }

            // Construir la consulta usando el query builder
            $detalle = DB::table('f_detalle_venta_agrupado as vd')
            ->select('vd.*', DB::raw('(vd.det_ven_cantidad * vd.det_ven_valor_u) AS valorTotal'), DB::raw("`$productoBuscar` AS idPerseoProducto"))
            ->leftJoin('1_4_cal_producto as p', 'vd.pro_codigo', '=', 'p.pro_codigo')
            ->where('vd.id_factura', $factura)
            ->where('id_empresa',$empresa)
            ->get();
            foreach( $detalle as $d){ $totalFactura += $d->valorTotal; }
            //con 2 decimales
            $totalFactura   = number_format($totalFactura, 2, '.', '');
            $detalles = [];
            foreach($detalle as $d){
                $pro_codigo = $d->pro_codigo;
                $id_perseo = $d->idPerseoProducto;
                if($id_perseo == 0 || $id_perseo == null || $id_perseo == ""){
                    return ["status" => "0", "message" => "El codigo $pro_codigo no se encuentra en perseo"];
                }
                $detalles[] = [
                    "pedidosid"                 => 1,
                    "centros_costosid"          => 1,
                    "productosid"               => $d->idPerseoProducto,
                    "medidasid"                 => 1,
                    "almacenesid"               => 1,
                    "cantidaddigitada"          => $d->det_ven_cantidad,//Cantidad del producto pedido
                    "cantidad"                  => $d->det_ven_cantidad,//Resultado que se obtiene al multiplicar cantidaddigitada*cantidadfactor. Es la cantidad real que se va a utilizar en base a la medida con la que se está trabajando.
                    "cantidadfactor"            => 1,
                    "precio"                    => $d->det_ven_valor_u,
                    "preciovisible"             => $d->det_ven_valor_u,
                    "iva"                       => 0,
                    "precioiva"                 => $d->det_ven_valor_u,
                    "descuento"                 => $discount //consulta //Porcentaje de descuento que se va a aplicar a cada producto.
                ];
            }
            $formData = [
                "registro" => [
                    [
                        "pedidos" => [
                            "pedidosid"                   => 1,
                            "emision"                     => date('Ymd'),
                            "pedidos_codigo"              => "P000000001",//Consulta
                            "forma_pago_empresaid"        => "01",
                            "facturadoresid"              => 1,
                            "clientesid"                  => $clientesidPerseo,
                            "almacenesid"                 => 1,
                            "centros_costosid"            => 1,
                            "vendedoresid"                => 3,
                            "tarifasid"                   => 1,
                            "concepto"                    => $concepto,
                            "origen"                      => "0",//Parametros utilizado si el pedido ya esta transformado en factura
                            "documentosid"                => 0,//Guarda el id de la factura a la que fue transformado el pedido
                            "observacion"                 => $observacion,//Almacena una informaciòn adicional mas detalla, a diferencia del concepto, esta opción puede contener mas caracteres
                            "subtotalsiniva"              => $ven_valor,//Valor sin incluir el IVA total de los productos que no apliquen IVA.
                            "subtotalconiva"              => $ven_valor,//Valor sin incluir el IVA total de los productos que apliquen IVA.
                            "total_descuento"             => $ven_descuento,//Valor total del descuento correspondiente, se obtiene sumando el descuento aplicado a cada precio sin IVA de cada producto.
                            "subtotalneto"                => $ven_valor,//Valor total sin incluir IVA pero restando el descuento correspondiente.
                            "total_iva"                   => 0,
                            "total"                       => $ven_valor,//Valor total de la facture.
                            "empresaid"                   => 1,//consulta
                            "usuarioid"                   => 3,//consulta
                            "usuariocreacion"             => "IMOVIL",//consulta  //Nombre corto del usuario que realizo el proceso
                            //fechacreacion de tipo datetime
                            "fechacreacion"               => date('Y-m-d H:i:s'),
                            "detalles"                    => $detalles
                        ]
                    ]
                ]
            ];
            $url        = "pedidos_crear";
            $process    = $this->tr_PerseoPost($url, $formData,$id_empresa);
             //si existe proccess["facturas"] enviar a perseo
            //actualizar a 1 en la tabla f_venta modelo Ventas campo estadoPerseo a 1

            if(isset($process["pedidos"])) {
                $pedidoCodigo_nuevo = $process["pedidos"][0]["pedidos_codigo"];
                VentasF::where('id_factura',$factura)->where('id_empresa',$empresa)->update(['estadoPerseo' => 1,"pedido_codigo" => $pedidoCodigo_nuevo, "fecha_envio_perseo" => date('Y-m-d H:i:s') ]);
            }
            else{
                VentasF::where('id_factura',$factura)->where('id_empresa',$empresa)->update(['estadoPerseo' => 0 ]);
            }
            //transaccion de laravel commit
            DB::commit();
            return $process;
        } catch (\Exception $e) {
            //transaccion de laravel rollback
            DB::rollBack();
            return ["status" => "0", "message" => "Ocurrio un eror al intentar mandar el pedido a perseo", "error" => $e->getMessage()];
        }
    }
    /*
        Procedimiento para insertar una nueva entrega por facturar en el sistema contable de acuerdo con los datos que se envía en formato JSON.
    */
    //api:post/perseo/transaccion/entregas_crear
    public function entregas_crear(Request $request)
    {
        try {
            $formData = [
                "registro" => [
                    [
                        "entregas" => [
                            "entregasid" => 2,
                            "emision" => "20210831",
                            "entregas_codigo" => "E000000002",
                            "forma_pago_empresaid" => 1,
                            "facturadoresid" => 1,
                            "clientesid" => 5,
                            "almacenesid" => 1,
                            "centros_costosid" => 1,
                            "vendedoresid" => 3,
                            "tarifasid" => 1,
                            "concepto" => "",
                            "origen" => "",
                            "documentosid" => 0,
                            "observacion" => "",
                            "subtotalsiniva" => 100,
                            "subtotalconiva" => 160.714286,
                            "total_descuento" => 17.857142,
                            "subtotalneto" => 260.7143,
                            "total_iva" => 19.286,
                            "total" => 280,
                            "empresaid" => 4,
                            "usuarioid" => 2,
                            "usuariocreacion" => "PERSEO",
                            "fechacreacion" => "2021-08-31T14:38:08.582",
                            "detalles" => [
                                [
                                    "centros_costosid" => 1,
                                    "productosid" => 2,
                                    "medidasid" => 1,
                                    "almacenesid" => 1,
                                    "cantidaddigitada" => 1,
                                    "cantidad" => 1,
                                    "cantidadfactor" => 1,
                                    "precio" => 100,
                                    "preciovisible" => 100,
                                    "iva" => 0,
                                    "precioiva" => 100,
                                    "descuento" => 0,
                                    "costo" => 1.87,
                                    "servicio" => false
                                ],
                            ]
                        ]
                    ]
                ]
            ];
            // $formData = [
            //     "registro" => [
            //         [
            //             "entregas" => [
            //                 "entregasid"                   => $request->entregasid,
            //                 "emision"                      => $request->emision,
            //                 "entregas_codigo"              => $request->entregas_codigo,
            //                 "forma_pago_empresaid"         => $request->forma_pago_empresaid,
            //                 "facturadoresid"               => $request->facturadoresid,
            //                 "clientesid"                   => $request->clientesid,
            //                 "almacenesid"                  => $request->almacenesid,
            //                 "centros_costosid"             => $request->centros_costosid,
            //                 "vendedoresid"                 => $request->vendedoresid,
            //                 "tarifasid"                    => $request->tarifasid,
            //                 "concepto"                     => $request->concepto,
            //                 "origen"                       => $request->origen,
            //                 "documentosid"                 => $request->documentosid,
            //                 "observacion"                  => $request->observacion,
            //                 "subtotalsiniva"               => $request->subtotalsiniva,
            //                 "subtotalconiva"               => $request->subtotalconiva,
            //                 "total_descuento"              => $request->total_descuento,
            //                 "subtotalneto"                 => $request->subtotalneto,
            //                 "total_iva"                    => $request->total_iva,
            //                 "total"                        => $request->total,
            //                 "empresaid"                    => $request->empresaid,
            //                 "usuarioid"                    => $request->usuarioid,
            //                 "usuariocreacion"              => $request->usuariocreacion,
            //                 "fechacreacion"                => $request->fechacreacion,
            //                 "detalles"                     => json_decode($request->detalles)
            //             ]
            //         ]
            //     ]
            // ];
            $url        = "entregas_crear";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Procedimiento para insertar un nuevo cobro en el sistema contable de acuerdo con los datos que se envía en formato JSON.
    */
    //api:post/perseo/transaccion/cobros_crear
    public function cobros_crear(Request $request)
    {
        try {
            //transaccion de laravel
            DB::beginTransaction();
            $abono_id = $request->abono_id;
            $usuarioCreador = $request->usuarioCreador;
            // $abono_id = 49;
            $getAbono = Abono::where('abono_id',$abono_id)->first();
            $getBanco = CuentaBancaria::where('cue_pag_codigo',$getAbono->abono_cuenta)->first();
            //si no existe el abono
            if(!$getAbono) { return ["status" => "0", "message" => "El abono no existe"]; }
            $importe = 0;
            $tipoPago = 0;
            $tipoPago = 0;
            $banco =  $getBanco->ban_codigo;
            // return $banco;
            $fecha = Carbon::parse($getAbono->ban_codigo)->format('Ymd');
            $cliente = $getAbono->idClientePerseo;
            $empresa = $getAbono->abono_empresa;
            $documento = $getAbono->abono_documento;
            if($getAbono->abono_facturas == 0){
                $importe = $getAbono->abono_notas;
            }else{
                $importe = $getAbono->abono_facturas;
            }
            if($getAbono->abono_tipo === 0 ){
                $tipoPago = 5;
            }else if($getAbono->abono_tipo === 2){
                $tipoPago = 5;
                $banco = $getAbono->abono_cheque_banco;
            }else if($getAbono->abono_tipo === 1){
                $tipoPago = 6;
            }
            $observacion = $getAbono->abono_concepto;
            $detalles    = [];
            $detalles[0] = [
                "bancoid"           => $banco,
                "cajasid"           => 1,
                "comprobante"       => $documento,//Consultar//Numero de comprobante que identifique el cobro realizado.
                "importe"           => $importe,
                "documentosid"      => 0,//Id de la facture que se está afectando en el cobro.
                "formapago"         => $tipoPago,
                "saldo"             => 0,//Consulta,
                "fechaemision"      => $fecha,
                "fecharecepcion"    => $fecha,
                "fechavence"        => $fecha,
                "secuencia"         => "000000001"//Consultar
            ];
            $formData = [
                "registros" => [
                    [
                        "cobros" => [
                            "cobrosid"                 => 1,
                            "clientesid"               => $cliente,//cliente del abono //Corresponde al id del cliente. Vea referencia del procedimiento consultar_clientes, para extraer la información necesaria.
                            "cobroscodigo"             => "CB00000001",//Código único del Sistema, se genera automáticamente.
                            "cobradoresid"             => 3,//Id del cobrador
                            "tipo"                     => "AB",
                            "movimientos_conceptosid"  => 3,//	Indica el grupo de transacción a la que corresponde, valor predeterminado: 3
                            "forma_pago_empresaid"     => $tipoPago,
                            "concepto"                 => $observacion,
                            "reciboId"                 => 0, //Corresponde al id del recibo personalizado si se ha configurado en el agente de venta. Valor predeterminado: 0
                            "fechaemision"             => $fecha,
                            "fecharecepcion"           => $fecha,
                            "fechavencimiento"         => $fecha,
                            "importe"                  => $importe,
                            "cajasid"                  => 1,//Id de la caja que va afectar esta factura
                            "bancosid"                  => $banco,
                            "usuariocreacion"          => $usuarioCreador,//mando del front
                            "usuarioid"                => 3,
                            "detalles"                 => $detalles,
                        ]
                    ]
                ]
            ];
            // return $formData;
            $url        = "cobros_crear";
            $process    = $this->tr_PerseoPost($url, $formData,$empresa);
            //si existe proccess["cobros"] guardo
            if(isset($process["cobros"])) {
                $cobrosid_nuevo = $process["cobros"][0]["codigo_nuevo"];
                Abono::where('abono_id',$abono_id)->update(['estadoPerseo' => 1,"idPerseo" => $cobrosid_nuevo]);
            }
            else{
                Abono::where('abono_id',$abono_id)->update(['estadoPerseo' => 0]);
            }
            //transaccion de laravel commit
            DB::commit();
            return [$formData, $process];
        } catch (\Exception $e) {
            //transaccion de laravel rollback
            DB::rollBack();
            return ["status" => "0", "message" => "Ocurrio un eror al intentar mandar el cobro a perseo", "error" => $e->getMessage()];
        }
    }
    //api:get/perseo/transaccion/transaccion
    public function index()
    {
        //
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
        //
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
}
