<?php
namespace App\Repositories;
use Illuminate\Support\Facades\DB;
use App\Models\Models\Pagos\VerificacionPago;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\PedidoPagosDetalle;
use App\Models\Pedidos;
use App\Models\Verificacion;
use App\Repositories\pedidos\PedidosRepository;
use App\Traits\Pedidos\TraitPedidosGeneral;

class  PedidosPagosRepository extends BaseRepository
{
    use TraitPedidosGeneral;
    protected $pedidosRepository = null;
    public function __construct(VerificacionPago $modelo, PedidosRepository $pedidosRepository)
    {
        parent::__construct($modelo);
        $this->pedidosRepository = $pedidosRepository;
    }
    public function getPagosXID($verificacion_pago_id){
        $query = DB::SELECT("SELECT pd.* ,
        CONCAT(u.nombres,' ', u.apellidos) AS distribuidor_usuario,
        dt.saldo_actual, tp.tip_pag_nombre
        FROM verificaciones_pagos_detalles pd
        LEFT JOIN distribuidor_temporada dt ON pd.distribuidor_temporada_id = dt.id
        LEFT JOIN pedidos_formas_pago tp ON tp.forma_pago_id = pd.forma_pago_id
        LEFT JOIN usuario u ON pd.idusuario = u.idusuario
        WHERE pd.verificacion_pago_id = ?
        ",[$verificacion_pago_id]);
        return $query;
    }
    public function getPagosxContrato($contrato){
        $pagos = PedidosDocumentosLiq::with([
            'tipoPagos',
            'formaPagos',
            'pedidoPagosHijo',
            'userCierre:idusuario,nombres,apellidos',
            'detallePago',
        ])
        ->where('ven_codigo',$contrato)
        ->where('forma_pago_id','>','0')
        ->OrderBy('doc_codigo','DESC')
        ->get();
        return $pagos;
    }
    public function getPagosSinContrato($institucion,$periodo){
        $pagos = PedidosDocumentosLiq::with([
            'tipoPagos',
            'formaPagos',
            'pedidoPagosHijo',
            'detallePago',
        ])
        ->where('forma_pago_id','>','0')
        ->where('1_4_documento_liq.institucion_id','=',$institucion)
        ->where('1_4_documento_liq.periodo_id',   '=',$periodo)
        ->OrderBy('doc_codigo','DESC')
        ->get();
        return $pagos;
    }
    public function getPagosInstitucion($institucion,$periodo,$tipo=null,$parametro1=null,$parametro2=null,$parametro3=null,$parametro4=null,$ifPrint=null){
        $pagos = PedidosDocumentosLiq::query();
        //null => solo aprobados
        if($tipo == null)  { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('estado','1'); }
        //convenio exepto el que se esta utilizado
        if($tipo == 1)     { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('tipo_pago_id','4')->where('doc_codigo','<>',$parametro1);  }
        //convenios for status
        if($tipo == 2)     { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('tipo_pago_id','4')->where('estado',     '=',$parametro1);  }
        //convenios for status x id convenio
        if($tipo == 3)     { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('estado',     '=',$parametro1)->where('pedidos_convenios_id','=',$parametro2);  }
        //dinamico diferente 2 campo
        if($tipo == 4)     { $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('doc_codigo', '<>',$parametro1)->where($parametro2,'=',$parametro3);  }
        //dinamico igual 2 campos
        if($tipo == 5)     { $pagos->where($parametro1, '=',$parametro2)->where($parametro3,'=',$parametro4);  }
        //los pagos de tipo y estado
        if($tipo == 6)     {
            $pagos->porInstitucionYPeriodo($institucion,$periodo)->where('tipo_pago_id',$parametro1)->where('estado',$parametro2);
         }
        if($ifPrint == 1){ return $pagos;}
        $resultado = $pagos->get();
        return $resultado;
    }
    public function saveDocumentosLiq($request){
        $tipoPago = 0;
        if($request->id > 0){
            $documento                                                              = PedidosDocumentosLiq::findOrFail($request->id);
            $tipoPago                                                               = $documento->tipo_pago_id;
        }else{
            $documento                                                              = new PedidosDocumentosLiq();
        }
        $documento->doc_fecha                                                       = $request->doc_fecha == null || $request->doc_fecha == "null" ? null : $request->doc_fecha;
        $documento->unicoEvidencia                                                  = $request->unicoEvidencia;
        $documento->doc_valor                                                       = $request->doc_valor;
        $documento->doc_numero                                                      = $request->doc_numero      == null || $request->doc_numero == 'null'? null : $request->doc_numero;
        $documento->doc_nombre                                                      = $request->doc_nombre      == null || $request->doc_nombre == 'null'? null : $request->doc_nombre;
        $documento->doc_apellidos                                                   = $request->doc_apellidos   == null || $request->doc_apellidos == 'null'? null : $request->doc_apellidos;
        $documento->doc_ruc                                                         = $request->doc_ruc         == null || $request->doc_ruc == 'null'? null : $request->doc_ruc;
        $documento->doc_cuenta                                                      = $request->doc_cuenta      == null || $request->doc_cuenta == 'null'? null : $request->doc_cuenta;
        $documento->doc_institucion                                                 = $request->doc_institucion == null || $request->doc_institucion == 'null'? null : $request->doc_institucion;
        $documento->doc_ci                                                          = $request->tipo_pago_id;
        $documento->doc_tipo                                                        = $request->doc_tipo        == null || $request->doc_tipo == 'null'? null : $request->doc_tipo;
        $documento->doc_observacion                                                 = $request->doc_observacion == null || $request->doc_observacion == 'null'? null : $request->doc_observacion;
        $documento->ven_codigo                                                      = $request->ven_codigo      == null || $request->ven_codigo == "null" ? null : $request->ven_codigo;
        $documento->user_created                                                    = $request->user_created;
        $documento->distribuidor_temporada_id                                       = $request->distribuidor_temporada_id == null || $request->distribuidor_temporada_id == "null" ? null : $request->distribuidor_temporada_id ;
        $documento->forma_pago_id                                                   = $request->forma_pago_id;
        $documento->tipo_pago_id                                                    = $request->tipo_pago_id;
        $documento->calculo                                                         = $request->calculo;
        if($request->institucion_id)                                                { $documento->institucion_id = $request->institucion_id; }
        if($request->periodo_id)                                                    { $documento->periodo_id     = $request->periodo_id; }
        if($request->id_pedido)                                                     { $documento->id_pedido      = $request->id_pedido; }
        if(isset($request->estado))                                                 { $documento->estado         = $request->estado; }
        $documento->ifAntAprobado                                                   = $request->ifAntAprobado;
        if($request->tipo_pago_id == 7  || $request->tipo_pago_id == 2)             { $documento->campo_dinamico = $request->campo_dinamico; }
        if(isset($request->mostrar_reporte))                                        { $documento->mostrar_reporte = $request->mostrar_reporte; }
        $documento->save();
        $nuevodocumento                                                             = PedidosDocumentosLiq::findOrFail($documento->doc_codigo);
        if($request->tipo_pago_id == 6)                                             {  $this->updateDeuda($request->id_pedido); }
        //deuda con metodo de pago anterior
        if ($request->tipo_pago_id == 1 && $request->doc_observacion !== null) {
            $doc_observacion = strtolower($request->doc_observacion);
            if (strpos($doc_observacion, 'deuda') !== false) {
                $this->updateDeudaMetodoAnterior($request->id_pedido);
            }
        }
        //deuda proxima
        if($request->tipo_pago_id == 3 || $tipoPago == 3) { $this->updateDeudaProxima($request->id_pedido); }
        // guardar en detalle de pedido si el pago es de tipo anticipo, anticipo pedido, convenio
        if($request->tipo_pago_id == 1 || $request->tipo_pago_id == 2 || $request->tipo_pago_id == 4){
            $this->saveDetallePago($request,$nuevodocumento);
        }
        return $nuevodocumento;
    }

    public function saveDetallePago($request,$documentoLiq){
        $doc_codigo     = $documentoLiq->doc_codigo;
        $user_created   = $request->user_created;
        $id             = 0;
        $descripcion    = null;
        // anticipo del pedido
        if($request->tipo_pago_id == 1 && $request->ifAntAprobado == 1){ $descripcion = "Anticipo del pedido"; }
        // pago anticipo
        if($request->tipo_pago_id == 1 && $request->ifAntAprobado == 0){ $descripcion = "Pago anticipo"; }
        // pago tipo liquidacion
        if($request->tipo_pago_id == 2){ $descripcion = "Pago de liquidacion"; }
        // pago de convenio
        if($request->tipo_pago_id == 4){ $descripcion = "Pago de convenio"; }
        //ver si hay un pago
        $validateExistsDetallePago = DB::SELECT("SELECT * FROM pedidos_pagos_detalles p
        WHERE p.id_pago = '$doc_codigo'
        LIMIT 1
        ");
        if(count($validateExistsDetallePago) > 0){
            $id = $validateExistsDetallePago[0]->id_pedido_pago_detalle;
        }else{
            $id = 0;
        }
        $detalle = new PedidoPagosDetalle();

        if($id == 0){
            $detalle                = new PedidoPagosDetalle();
            $detalle->id_pago       = $doc_codigo;
            $detalle->user_created  = $user_created;
        } else {
            $detalle                = PedidoPagosDetalle::findOrFail($id);
            return "Ya existe un detalle de pago";
        }

        // En ambos casos se actualizan estos campos si están presentes
        $detalle->valor         = $request->doc_valor;
        $detalle->descripcion   = $descripcion;

        $detalle->save();

    }

    public function updateDeudaMetodoAnterior($id_pedido)
    {
        $deudas = PedidosDocumentosLiq::where('id_pedido', $id_pedido)->where('tipo_pago_id', 1)->where('doc_observacion','like','%deuda%')->where('estado','1')->get();
        $sumaDeudas = 0;
        foreach ($deudas as $deuda) { $sumaDeudas += $deuda->doc_valor; }
        $pedido                         = Pedidos::findOrFail($id_pedido);
        $pedido->deuda                  = $sumaDeudas;
        $pedido->save();

    }
    public function updateDeuda($id_pedido)
    {
        $deudas = PedidosDocumentosLiq::where('id_pedido', $id_pedido)->where('tipo_pago_id', 6)->where('estado','1')->get();
        // if (count($deudas) > 0) {
            $sumaDeudas = 0;
            foreach ($deudas as $deuda) { $sumaDeudas += $deuda->doc_valor; }
            $pedido                         = Pedidos::findOrFail($id_pedido);
            $pedido->deuda                  = $sumaDeudas;
            //para que no vuelva a crear automaticamente la deuda
            $pedido->anticipoDeudaIngresada = 1;
            $pedido->save();
        // }
    }
    public function updateDeudaProxima($id_pedido)
    {
        $deudas  = $this->obtenerDeudasProximas($id_pedido);
        // if (count($deudas) > 0) {
            $sumaDeudas = 0;
            foreach ($deudas as $deuda) { $sumaDeudas += abs($deuda->doc_valor); }
            $pedido                         = Pedidos::findOrFail($id_pedido);
            $pedido->totalDeudaProxima      = $sumaDeudas;
            $pedido->save();
        // }
    }
    public function updateVentaDirecta($ven_codigo)
    {
        if($ven_codigo == null || $ven_codigo == "") { return; }
         //actualizar verificaciones con los cobros de venta directa ingresados
         $query = Verificacion::Where('contrato',$ven_codigo)->Where('cobro_venta_directa','2')->Where('tipoPago','2')->Where('cobro_venta_directa_ingresada','0')
         ->update([
             'cobro_venta_directa_ingresada' => 1
         ]);
    }
    //api:get>>/pedigo_Pagos?getVentaRealXAsesor=1&idAsesor=1&idPeriodo=1
    public function getVentaRealXAsesor($idusuario,$periodo){
        $query = DB::SELECT("SELECT
            p.id_pedido,
            p.id_institucion,
            p.tipo_venta,
            p.id_periodo,
            p.contrato_generado,
            p.TotalVentaReal,
            p.total_venta,

            -- Total Venta Alcance por pedido
            (
                SELECT SUM(pp.venta_bruta)
                FROM pedidos_alcance pp
                WHERE pp.estado_alcance = '1'
                AND pp.id_pedido = p.id_pedido
            ) AS totalVentaAlcance,

            -- total_venta + totalVentaAlcance
            (
                p.total_venta +
                COALESCE((
                SELECT SUM(pp.venta_bruta)
                FROM pedidos_alcance pp
                WHERE pp.estado_alcance = '1'
                    AND pp.id_pedido = p.id_pedido
                ), 0)
            ) AS total_venta_con_alcance

            FROM pedidos p
            WHERE p.id_asesor = ?
            AND p.tipo = '0'
            AND p.estado = '1'
            AND p.id_periodo = ?
            AND p.contrato_generado IS NOT NULL;

        ",[$idusuario,$periodo]);
        return $query;
    }

    public function getVentaPedido($periodo_id,$tipo_venta=null){
         $getPedidos = Pedidos::where('id_periodo', $periodo_id)
        ->where('estado', '1')
        ->where('tipo', '0')
        ->whereNotNull('contrato_generado')
        ->when($tipo_venta == 1, function ($query) {
            $query->where('tipo_venta', '1');
        })
        ->when($tipo_venta == 2, function ($query) {
            $query->where('tipo_venta', '2');
        })
        ->get();

        $arrayDetalles = [];

        // 3. Recorrer los pedidos y obtener los detalles de cada uno
        foreach ($getPedidos as $key => $item10) {
            $pedido = $item10->id_pedido;
            if($periodo_id <= $this->tr_periodoPedido){
              $libroSolicitados = $this->pedidosRepository->obtenerLibroxPedidoTodo($pedido);
            }else{
              $libroSolicitados = $this->pedidosRepository->obtenerLibroxPedidoTodo_new($pedido);
            }
            $arrayDetalles[$key] = $libroSolicitados;
        }

        $agrupado = [];
        $arrayDetalles = collect($arrayDetalles)->flatten(10);

        // 4. Agrupar los datos por código de liquidación
        foreach ($arrayDetalles as $detalle) {
            $codigo_liquidacion = $detalle->codigo_liquidacion;

            if (isset($agrupado[$codigo_liquidacion])) {
                $agrupado[$codigo_liquidacion]['valor'] += $detalle->valor;
                $agrupado[$codigo_liquidacion]['total'] += $detalle->valor * $detalle->precio;

                if (empty($agrupado[$codigo_liquidacion]['nombrelibro'])) {
                    $agrupado[$codigo_liquidacion]['nombrelibro'] = $detalle->nombrelibro;
                }
            } else {
                $agrupado[$codigo_liquidacion] = [
                    'codigo_liquidacion' => $codigo_liquidacion,
                    'valor' => $detalle->valor,
                    'nombrelibro' => $detalle->nombrelibro,
                    'precio' => $detalle->precio,
                    'total' => $detalle->valor * $detalle->precio,
                ];
            }
        }

        // 5. Calcular el totalPedido
        $totalPedido = 0;
        foreach ($agrupado as $item) {
            $totalPedido += $item['total'];
        }

        // 6. Retornar los datos agrupados con el total general
        return [
            'data' => array_values($agrupado),
            'totalPedido' => number_format($totalPedido, 2, '.', '')
        ];
    }
    //api:get/pedigo_Pagos?getVentaTotalListaDirecta=1&idPeriodo=22
   public function getVentaTotalListaDirecta($request)
    {
         // 1. Validar que el periodo_id esté presente
        $periodo_id = $request->idPeriodo;

        if (!$periodo_id) {
            return ["status" => "0", "message" => "Falta el periodo_id"];
        }

        $totalVentaPedidos = (float) $this->getVentaPedido($periodo_id, null)['totalPedido'];
        $ventaDirectaPedidos = (float) $this->getVentaPedido($periodo_id, 1)['totalPedido'];
        $ventaListaPedidos = (float) $this->getVentaPedido($periodo_id, 2)['totalPedido'];



        // Consulta para pedidos
        $ventas = DB::select("SELECT
                ROUND(SUM(TotalVentaDirecta), 2) AS TotalVentaDirecta,
                ROUND(SUM(TotalVentaLista), 2) AS TotalVentaLista,
                ROUND(SUM(SinVerificacionesDirecta), 2) AS SinVerificacionesDirecta,
                ROUND(SUM(SinVerificacionesLista), 2) AS SinVerificacionesLista,
                ROUND(SUM(totalVentaBruta), 2) AS TotalVentaBruta,
                ROUND(SUM(total_ventaSinVerificaciones), 2) AS TotalVentaSinVerificaciones
                -- ROUND(SUM(totalVentaPedidos), 2) AS TotalVentaPedidos,
                -- ROUND(SUM(totalVentaPedidosVentaDirecta), 2) AS TotalVentaPedidosVentaDirecta,
                -- ROUND(SUM(totalVentaPedidosVentaLista), 2) AS TotalVentaPedidosVentaLista
            FROM (
                SELECT
                    p.id_pedido,
                    p.contrato_generado,
                    CASE
                        WHEN p.TotalVentaReal > 0 THEN p.TotalVentaReal
                        ELSE 0
                    END AS totalVentaBruta,
                    CASE
                        WHEN p.total_venta > 0 AND p.TotalVentaReal = 0 THEN p.total_venta
                        ELSE 0
                    END AS total_ventaSinVerificaciones,
                    CASE
                        WHEN p.TotalVentaReal > 0 AND p.tipo_venta = 1 THEN p.TotalVentaReal
                        ELSE 0
                    END AS TotalVentaDirecta,
                    CASE
                        WHEN p.TotalVentaReal > 0 AND p.tipo_venta = 2 THEN p.TotalVentaReal
                        ELSE 0
                    END AS TotalVentaLista,
                    CASE
                        WHEN p.TotalVentaReal = 0 AND p.tipo_venta = 1 THEN p.total_venta
                        ELSE 0
                    END AS SinVerificacionesDirecta,
                    CASE
                        WHEN p.TotalVentaReal = 0 AND p.tipo_venta = 2 THEN p.total_venta
                        ELSE 0
                    END AS SinVerificacionesLista
                    -- CASE
                    --     WHEN p.total_venta > 0 THEN p.total_venta
                    --     ELSE 0
                    -- END AS totalVentaPedidos,
                    -- CASE
                    --     WHEN p.total_venta > 0 AND p.tipo_venta = 1 THEN p.total_venta
                    --     ELSE 0
                    -- END AS totalVentaPedidosVentaDirecta,
                    -- CASE
                    --     WHEN p.total_venta > 0 AND p.tipo_venta = 2 THEN p.total_venta
                    --     ELSE 0
                    -- END AS totalVentaPedidosVentaLista
                FROM pedidos p
                LEFT JOIN usuario u ON u.idusuario = p.id_asesor
                WHERE p.tipo = '0'
                AND p.estado = '1'
                AND p.id_periodo = ?
                AND p.contrato_generado IS NOT NULL
            ) AS subquery
        ", [$request->idPeriodo]);

        $ventas = collect($ventas)->first();

        // Consulta para alcances
        // $alcances = DB::select("
        //     SELECT
        //         ROUND(SUM(totalVentaPedidos), 2) AS TotalVentaPedidos,
        //         ROUND(SUM(totalVentaPedidosVentaDirecta), 2) AS TotalVentaPedidosVentaDirecta,
        //         ROUND(SUM(totalVentaPedidosVentaLista), 2) AS TotalVentaPedidosVentaLista
        //     FROM (
        //         SELECT
        //             CASE
        //                 WHEN a.venta_bruta > 0 THEN a.venta_bruta
        //                 ELSE 0
        //             END AS totalVentaPedidos,
        //             CASE
        //                 WHEN a.venta_bruta > 0 AND p.tipo_venta = 1 THEN a.venta_bruta
        //                 ELSE 0
        //             END AS totalVentaPedidosVentaDirecta,
        //             CASE
        //                 WHEN a.venta_bruta > 0 AND p.tipo_venta = 2 THEN a.venta_bruta
        //                 ELSE 0
        //             END AS totalVentaPedidosVentaLista
        //         FROM pedidos_alcance a
        //         LEFT JOIN pedidos p ON p.id_pedido = a.id_pedido
        //         WHERE p.id_periodo = ?
        //         AND p.contrato_generado IS NOT NULL
        //         AND p.estado = '1'
        //         AND p.tipo = '0'
        //         AND a.estado_alcance = '1'
        //     ) AS subquery
        // ", [$request->idPeriodo]);

        // $alcances = collect($alcances)->first();

        // Suma de los totales pedidos (ventas + alcances)
        $totalCombinado = [
            'TotalVentaPedidos' => $totalVentaPedidos,
            'TotalVentaPedidosVentaDirecta' => $ventaDirectaPedidos,
            'TotalVentaPedidosVentaLista' => $ventaListaPedidos,
        ];

        return [
            "ventas" => $ventas,
            "totalCombinado" => $totalCombinado,
        ];
    }




    //api:get/pedigo_Pagos?getVentaTotalListaDirectaAsesor=1&idPeriodo=22
    public function getVentaTotalListaDirectaAsesor($request){
        $query = DB::select("
        SELECT
            p.id_asesor,
            CONCAT(u.nombres, ' ', u.apellidos) AS Asesor,
            ROUND(SUM(CASE WHEN p.TotalVentaReal > 0 AND p.tipo_venta = 1 THEN p.TotalVentaReal ELSE 0 END), 2) AS TotalVentaDirecta,
            ROUND(SUM(CASE WHEN p.TotalVentaReal > 0 AND p.tipo_venta = 2 THEN p.TotalVentaReal ELSE 0 END), 2) AS TotalVentaLista,
            ROUND(SUM(CASE WHEN p.TotalVentaReal = 0 AND p.tipo_venta = 1 THEN p.total_venta ELSE 0 END), 2) AS SinVerificacionesDirecta,
            ROUND(SUM(CASE WHEN p.TotalVentaReal = 0 AND p.tipo_venta = 2 THEN p.total_venta ELSE 0 END), 2) AS SinVerificacionesLista,
            ROUND(SUM(CASE WHEN p.TotalVentaReal > 0 THEN p.TotalVentaReal ELSE 0 END), 2) AS TotalVentaBruta,
            ROUND(SUM(CASE WHEN p.TotalVentaReal = 0 AND p.total_venta > 0 THEN p.total_venta ELSE 0 END), 2) AS TotalVentaSinVerificaciones
        FROM
            pedidos p
        LEFT JOIN
            usuario u ON u.idusuario = p.id_asesor
        WHERE
            p.tipo = '0'
            AND p.estado = '1'
            AND p.id_periodo = ?
            AND p.contrato_generado IS NOT NULL
        GROUP BY
           p.id_asesor, u.nombres, u.apellidos
        ORDER BY
            Asesor ASC;
    ", [$request->idPeriodo]);

    return $query;

    }
    //api:get/pedigo_Pagos?getTotalDocumentosLiq=1&idPeriodo=24
    public function getTotalDocumentosLiq($request){
        $periodo = $request->idPeriodo;

        $query = DB::SELECT("
            SELECT
                ROUND(SUM(CASE WHEN l.tipo_pago_id = 1 AND l.ifAntAprobado = '0' THEN l.doc_valor ELSE 0 END), 2) AS totalAnticipos,
                ROUND(SUM(CASE WHEN l.tipo_pago_id = 2 THEN l.doc_valor ELSE 0 END), 2) AS totalLiquidaciones,
                ROUND(SUM(CASE WHEN l.tipo_pago_id = 7 THEN l.doc_valor ELSE 0 END), 2) AS totalOtrosValores,
                (SELECT SUM(p.anticipo_global) FROM pedidos_convenios p
                 WHERE p.periodo_id = l.periodo_id
                 AND p.estado <> '2') AS totalConvenio,
                (SELECT SUM(p.anticipo_aprobado) FROM pedidos p
                 WHERE p.id_periodo = l.periodo_id
                 AND p.estado = '1') AS totalAnticipoAprobado
            FROM `1_4_documento_liq` l
            WHERE l.periodo_id = ?
            AND l.estado = '1'
        ", [$periodo]);

        return $query;
    }
    //api/get>>pedigo_Pagos?updateVentaReal=1&idAsesor=1&idPeriodo=1
    public function updateVentaReal($request){
        $query      = $this->getVentaRealXAsesor($request->idAsesor,$request->idPeriodo);
        $contador   = 0;
        foreach($query as $item){
            $contrato           = $item->contrato_generado;
            $ventaReal          = 0;
            $verificaciones     = $this->getVerificaciones($contrato);
            foreach($verificaciones as $key2 => $item2){ $ventaReal = $ventaReal + $item2->venta_real; }
            $pedido = Pedidos::findOrFail($item->id_pedido);
            $pedido->TotalVentaReal = $ventaReal;
            $pedido->save();
            $contador++;
        }
        return "Se actualizaron ".$contador." contratos";
    }
    public function obtenerDeudasProximas($id_pedido){
        $deudas     = PedidosDocumentosLiq::where('id_pedido', $id_pedido)->where('tipo_pago_id', 3)->where('estado','1')->get();
        return $deudas;
        // $valor      = 0;
        // foreach ($deudas as $deuda) { $valor += abs($deuda->doc_valor); }
        // return $valor;
    }
    public function getSubPagos($doc_codigo){
        $query = DB::SELECT("SELECT d.*,
        CONCAT(u.nombres , ' ', u.apellidos) AS usuario_creador,
        CONCAT(ue.nombres , ' ', ue.apellidos) AS usuario_editor
        FROM pedidos_pagos_detalles d
        LEFT JOIN usuario u ON u.idusuario = d.user_created
        LEFT JOIN usuario ue ON ue.idusuario = d.user_edit
        WHERE d.id_pago = ?
        ORDER BY d.id_pedido_pago_detalle DESC
        ",
        [$doc_codigo]);
        return $query;
    }
}
?>
