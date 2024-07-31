<?php

namespace App\Http\Controllers\Perseo;

use App\Http\Controllers\Controller;
use App\Models\Ventas;
use App\Repositories\perseo\PerseoConsultasRepository;
use App\Traits\Pedidos\TraitPedidosGeneral;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerseoFacturacionController extends Controller
{
    use TraitPedidosGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $perseoConsultaReposiory;
    public function __construct(PerseoConsultasRepository $perseoConsultasRepository)
    {
        $this->perseoConsultaReposiory = $perseoConsultasRepository;
    }
    //api:get/perseo/facturacion/facturacion
    public function index()
    {
        return "hola mundo";
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
    //api:post/perseo/facturacion/facturas_consulta
    /*
        *Muestra el listado de las facturas, notas de crédito y notas de venta de acuerdo al filtro que se envíe.
        Se puede filtrar por:
        -numero de días ó
        -id de una factura específica.

        @param facturaid: id de la factura
        @param dias: número de días atrás para extraer el historial de las facturas
     */
    public function facturas_consulta(Request $request)
    {
        try{
            $url        = "facturas_consulta";
            $formData   = [
                "facturaid" => "1", //3162
                "dias"      => "100"
            ];
            // $formData   = [
            //     "facturaid" => $request->facturaid,
            //     "dias"      => $request->dias
            // ];
            $process    = $this->tr_PerseoPost($url,$formData);
            return $process;
        }
        catch(\Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //api:post/perseo/facturacion/facturas_crear
    public function facturas_crear(Request $request)
    {
        // $factura     = $request->ven_codigo; //F-C24-SFN-0000004
        $factura        = "F-C24-SFN-0000004";
        $observacion    = "Factura de prueba"; //observacion
        $getFactura = Ventas::where('ven_codigo',$factura)->first();
        //validar que exista la factura
        if(!$getFactura)                        { return ["status" => "0", "message" => "La factura no existe"]; }
        //validar si la factura ya fue enviada a Perseo
        if($getFactura->estadoPerseo == 1)      { return ["status" => "0", "message" => "La factura ya fue enviada a Perseo"]; }
        $detalle = DB::SELECT("SELECT vd.*, (vd.det_ven_cantidad * vd.det_ven_valor_u) AS valorTotal
            FROM f_detalle_venta vd
            WHERE vd.ven_codigo = ?
        ",[$factura]);
        //valor total de la factura - el descuento  - el transporte
        $ven_valor       = $getFactura->ven_valor;
        $ven_descuento  = $getFactura->ven_descuento;
        $totalFactura   = 0;
        foreach( $detalle as $d){ $totalFactura += $d->valorTotal; }
        //con 2 decimales
        $totalFactura = number_format($totalFactura, 2, '.', '');
        //obtener la secuencia
        $arraySecuencias                = $this->perseoConsultaReposiory->facturaSecuencia();
        $getSecuencia                   = $arraySecuencias["secuencias"];
        $secuencia                      = $getSecuencia[0]["numeroactual"];
        //secuencia + 1
        $secuencia += 1;
        //obtener la secuencia
        //vacio seria la primera secuencia
        $format_secuencia               = str_pad($secuencia, 9, '0', STR_PAD_LEFT);
        $secuenciaFinal                 = $format_secuencia;
        $detalles = [];
        foreach($detalle as $d){
            $detalles[] = [
                "centros_costosid"      => 1,
                "almacenesid"           => 1,
                "productosid"           => 3,
                "medidasid"             => 1,
                "cantidaddigitada"      => $d->det_ven_cantidad,
                "cantidadfactor"        => 1,//Valor que se genera si se está trabajando con multimedidas, caso contrario por defecto enviar 1
                "cantidad"              => $d->det_ven_cantidad,//Resultado que se obtiene al multiplicar cantidaddigitada*cantidadfactor. Es la cantidad real que se va a facturar en base a la medida con la que se está trabajando.
                "precio"                => $d->det_ven_valor_u,
                "preciovisible"         => $d->det_ven_valor_u,
                "precioiva"             => $d->det_ven_valor_u,
                "descuento"             => 0,
                "costo"                 => 0,
                "iva"                   => 0,//Porcentaje de IVA que tiene el producto; 12% o 0%
                "descuentovalor"        => 0,
                "servicio"              => false
            ];
        }
        try{
            //transaccion de laravel begin
            DB::beginTransaction();
            $body = [
                "registro" => [
                    [
                        "facturas" => [
                            "facturasid"                    => 1,
                            "secuenciasid"                  => "01",
                            "sri_documentoscodigo"          => "01",
                            "forma_pago_empresaid"          => "01",
                            "forma_pago_sri_codigo"         => "01",
                            "cajasid"                       => 1,
                            "bancosid"                      => 1,
                            "centros_costosid"              => 1,
                            "almacenesid"                   => 1,
                            "facturadoresid"                => 1,
                            "vendedoresid"                  => 3,
                            "clientesid"                    => 493,//consultar
                            "clientes_sucursalesid"         => 0,
                            "tarifasid"                     => 1,
                            "establecimiento"               => "001",
                            "puntoemision"                  => "901",//consultar
                            "secuencial"                    => $secuenciaFinal,
                            "emision"                       => date('Ymd'),
                            "vence"                         => date('Ymd'),
                            "subtotal"                      => $totalFactura,//valor total sin incluir el iva ni el descuento
                            "total_descuento"               => $ven_descuento,//Total del descuento en valor, se obtiene sumando el descuento aplicado a cada precio sin IVA de cada producto.
                            "subtotalconiva"                => $ven_valor,
                            "subtotalsiniva"                => $ven_valor,//Valor total de los productos que no tienen IVA, restado el descuento.
                            "subtotalneto"                  => $ven_valor,//Valor total sin IVA y restando el descuento correspondiente.
                            "total_ice"                     => 0,
                            "total_iva"                     => 0,
                            "propina"                       => 0,
                            "total"                         => $ven_valor,//Valor total de la factura.
                            "totalneto"                     => $ven_valor,//Valor total de la factura.
                            "totalretenidoiva"              => 0,
                            "totalretenidorenta"            => 0,
                            "puntoemisionretencion"         => "",
                            "establecimientoretencion"      => "",
                            "emisionretencion"              => "",
                            "secuenciaretencion"            => "",
                            "observacion"                   => $observacion,
                            "detalles"                      => $detalles
                        ]
                    ]
                ]
            ];
            $url        = "facturas_crear";
            $process    = $this->tr_PerseoPost($url, $body);
            //si existe proccess["facturas"] enviar a perseo
            //actualizar a 1 en la tabla f_venta modelo Ventas campo estadoPerseo a 1
            if(isset($process["facturas"])) {
                $facturasid_nuevo = $process["facturas"][0]["facturasid_nuevo"];
                Ventas::where('ven_codigo',$factura)->update(['estadoPerseo' => 1,"idPerseo" => $facturasid_nuevo]);
            }
            else{
                Ventas::where('ven_codigo',$factura)->update(['estadoPerseo' => 1]);
            }
            //transaccion de laravel commit
            DB::commit();
            return $process;
        }
        catch(\Exception $e){
            //transaccion de laravel rollback
            DB::rollBack();
            return ["status" => "0", "message" => "Ocurrio un eror al intentar mandar la factura a perseo", "error" => $e->getMessage()];
        }
    }
    /*Procedimiento para realizar la autorización de una factura específica. El filtro requerido es el id de la factura que ya debe constar en el sistema central. */
    //"facturasid": *id de la factura a autorizar*,
    //"enviomail":indica si se va a realizar el envío de la factura por correo(Valor booleano)
    //api:post/perseo/facturacion/facturas_autorizar
    public function facturas_autorizar(Request $request)
    {
        try{
            $formData       = [
                "facturasid" => 1,
                "enviomail"  => false
            ];
            // $formData       = [
            //     "facturasid" => $request->facturasid,
            //     "enviomail"  => $request->enviomail
            // ];
            $url            = "facturas_autorizar";
            $process    = $this->tr_PerseoPost($url,$formData);
            return $process;
        }
        catch(\Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
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
