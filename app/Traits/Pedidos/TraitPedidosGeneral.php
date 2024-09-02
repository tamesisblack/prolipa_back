<?php

namespace App\Traits\Pedidos;

use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\Pedidos;
use DB;
use Illuminate\Support\Facades\Http;
trait TraitPedidosGeneral
{
    //=====PERSEO=======
    public $api_keyProlipaProduction    = "RfVaC9hIMhn49J4jSq2_I7GbWYHrlGRtitYwIuTepQg-";
    public $api_keyCalmedProduction     = "RfVaC9hIMhn49J4jSq2_IzB1.iNzqGb9M38jmd1DfQs-";
    public $api_keyProlipaLocal         = "RfVaC9hIMhn49J4jSq2_I_.QLazmDGrbZQ8o8ePUEcU-";
    public $api_keyCalmedLocal          = "RfVaC9hIMhn49J4jSq2_I91geWPRm0IWEft2beVW9NI-";
    //=====END PERSEO=======
    //=====SOLINFA==========
    public $api_KeyGONZALEZ             = "RfVaC9hIMhn49J4jSq2_Iw3h5qF1Dg0ecy.kFTzdqnA-";
    public $api_KeyCOBACANGO            = "RfVaC9hIMhn49J4jSq2_I6jl_oMHwM8TrJbBo8ztdHA-";
    //=====END SOLINFA======
    public $ipProlipa                   = "http://186.4.218.168:9095/api/";
    public $ipPerseo                    = "http://190.12.43.171:8181/api/";
    public $gl_perseoProduccion         = 1;
    // public $ipLocal        = "http://localhost:5000/api/";
    public function FacturacionGet($endpoint)
    {
        $dato = Http::get($this->ipProlipa.$endpoint);
        return $JsonContrato = json_decode($dato, true);
    }
    public function FacturacionPost($endpoint,$data){
        $dato = Http::post($this->ipProlipa.$endpoint,$data);
        return $JsonContrato = json_decode($dato, true);
    }
    //===PERSEO PROLIPA===
    public function tr_PerseoPost($endpoint,$data,$empresa=1){
        //empresa 1 => prolipa; 3 => calmed
        $dato = [];
        if ($this->gl_perseoProduccion == 1) {
            //agregar la api key al array de data
            if($empresa == 1){ $data['api_key'] = $this->api_keyProlipaProduction; }
            if($empresa == 3){ $data['api_key'] = $this->api_keyCalmedProduction;  }
            $dato = Http::post($this->ipPerseo.$endpoint,$data);

        } else {
           //agregar la api key al array de data
            if($empresa == 1){ $data['api_key'] = $this->api_keyProlipaLocal; }
            if($empresa == 3){ $data['api_key'] = $this->api_keyCalmedLocal;  }
            $dato = Http::post($this->ipPerseo.$endpoint,$data);
        }
        return $jsonData = json_decode($dato, true);
    }
    //==SOLINFA===
    public function tr_SolinfaPost($endpoint,$data,$empresa=1){
        //empresa 1 => GONZALEZ GUAMAN WELLINGTON MAURICIO ; 2 => COBACANGO TOAPANTA CESAR BAYARDO
        $dato = [];
        //agregar la api key al array de data
        if($empresa == 1){ $data['api_key'] = $this->api_KeyGONZALEZ; }
        if($empresa == 2){ $data['api_key'] = $this->api_KeyCOBACANGO;  }
        $dato = Http::post($this->ipPerseo.$endpoint,$data);
        return $jsonData = json_decode($dato, true);
    }

    public function getPedido($filtro,$parametro1=null,$parametro2=null){
        $resultado = DB::table('pedidos as p')
        ->select(DB::RAW('p.*,
        i.nombreInstitucion,i.zona_id,i.codigo_institucion_milton, c.nombre AS nombre_ciudad,
        CONCAT(u.nombres," ",u.apellidos) as responsable, CONCAT(u.nombres," ",u.apellidos) as asesor, u.cedula as cedula_asesor,u.iniciales,
        ph.estado as historicoEstado,ph.evidencia_cheque,ph.evidencia_pagare,
        IF(p.estado = 2,"Anulado","Activo") AS estadoPedido,
        (SELECT f.id_facturador from pedidos_asesores_facturador
        f where f.id_asesor = p.id_asesor  LIMIT 1) as id_facturador,
        i.ruc,i.nivel,i.tipo_descripcion,i.direccionInstitucion,i.telefonoInstitucion,
        (
            SELECT SUM(pa.venta_bruta) AS contador_alcance
            FROM pedidos_alcance pa
            WHERE pa.id_pedido = p.id_pedido
            AND pa.estado_alcance = "1"
            AND pa.venta_bruta > 0
        ) AS contador_alcance,
        (
            SELECT SUM(pa.total_unidades)  AS alcanceUnidades
            FROM pedidos_alcance pa
            WHERE pa.id_pedido = p.id_pedido
            AND pa.estado_alcance = "1"
            AND pa.venta_bruta > 0
        ) AS alcanceUnidades,
        (SELECT COUNT(*) FROM verificaciones v WHERE v.contrato = p.contrato_generado AND v.nuevo = "1" AND v.estado = "0") as verificaciones,
        (
            SELECT COUNT(a.id) AS contadorAlcanceAbierto
            FROM pedidos_alcance a
            LEFT JOIN pedidos ped ON ped.id_pedido = a.id_pedido
            WHERE  a.id_pedido = p.id_pedido
            AND a.estado_alcance  = "0"
            AND ped.estado = "1"
        ) as contadorAlcanceAbierto,
        (
            SELECT COUNT(a.id) AS contadorAlcanceCerrado
            FROM pedidos_alcance a
            LEFT JOIN pedidos ped ON ped.id_pedido = a.id_pedido
            WHERE  a.id_pedido = p.id_pedido
            AND a.estado_alcance  = "1"
            AND ped.estado = "1"
        ) as contadorAlcanceCerrado,
        (
            SELECT  COUNT(o.id) FROM p_libros_obsequios o
            WHERE o.id_pedido = p.id_pedido
            AND (
            o.estado_libros_obsequios = "0"
            OR o.estado_libros_obsequios  = "3"
            OR o.estado_libros_obsequios  = "4"
            )
        ) as contadorObsequiosAbiertosEnviados,
        pe.periodoescolar as periodo,pe.codigo_contrato,
        CONCAT(uf.apellidos, " ",uf.nombres) as facturador,
        i.region_idregion as region,uf.cod_usuario,
        ph.fecha_generar_contrato,
        (p.TotalVentaReal - ((p.TotalVentaReal * p.descuento)/100)) AS ven_neta,
        (p.TotalVentaReal * p.descuento)/100 as valorDescuento,
        ps.id_grupo_finaliza, des.ca_descripcion as despacho
        '))
        ->leftjoin('usuario as u',          'p.id_asesor',          'u.idusuario')
        ->leftjoin('usuario as uf',         'p.id_usuario_verif',   'uf.idusuario')
        ->leftjoin('institucion as i',      'p.id_institucion',     'i.idInstitucion')
        ->leftjoin('ciudad as c',           'i.ciudad_id',          'c.idciudad')
        ->leftjoin('periodoescolar as pe',  'pe.idperiodoescolar',  'p.id_periodo')
        ->leftjoin('pedidos_historico as ph','p.id_pedido',         'ph.id_pedido')
        ->leftjoin('pedidos_solicitudes_gerencia as ps','p.id_solicitud_gerencia_comision','ps.id')
        ->leftjoin('f_contratos_agrupados as des','p.ca_codigo_agrupado','des.ca_codigo_agrupado')
        ->where('p.tipo','=','0');
        //fitlro por x id
        if($filtro == 0) { $resultado->where('p.id_pedido', '=', $parametro1); }
        //filtro x periodo
        if($filtro == 1) { $resultado->where('p.id_periodo','=',$parametro1)->where('p.estado','<>','0')->OrderBy('p.id_pedido','DESC'); }
        //filtro por asesor
        if($filtro == 2) { $resultado->where('p.id_periodo','=', $parametro1)->where('p.id_asesor','=',$parametro2)->OrderBy('p.id_pedido','DESC'); }
        //filtro facturador no admin
        if($filtro == 3) { $resultado->where('p.id_periodo','=', $parametro1)->where('p.id_asesor','=',$parametro2)->where('p.estado','<>','0')
            ->where(function ($query) {
                $query->where('p.solicitud_gerencia_estado', '0')
                ->orWhere('p.solicitud_gerencia_estado', '2');
            })
            ->where(function ($query) {
                $query->where('p.estado_aprobado_convenio_cerrado', '0')
                ->orWhere('p.estado_aprobado_convenio_cerrado', '2');
            })
            ->OrderBy('p.id_pedido','DESC');
        }
        //filtro x periodo pero el ca_codigo_agrupado es nulo
        if($filtro == 4) { $resultado->where('p.id_periodo','=',$parametro1)->where('p.estado','<>','0')->whereNull('p.ca_codigo_agrupado')->OrderBy('p.id_pedido','DESC'); }
        $consulta = $resultado->get();
        return $consulta;
    }
    public function getVerificaciones($contrato){
        $query = DB::SELECT("SELECT * FROM verificaciones
            WHERE contrato =  '$contrato'
            and nuevo = '1'
            and estado = '0'
        ");
        return $query;
    }
    public function getAllBeneficiarios($id_pedido)
    {
        $query = DB::SELECT("SELECT  b.*,
        CONCAT(u.nombres, ' ',u.apellidos) AS beneficiario,
        u.cedula,u.nombres,u.apellidos,p.descuento,p.total_venta,p.contrato_generado
         FROM pedidos_beneficiarios b
         LEFT JOIN pedidos p ON b.id_pedido = p.id_pedido
         LEFT JOIN usuario u ON  b.id_usuario = u.idusuario
        WHERE b.id_pedido = '$id_pedido'
        ");
        return $query;
    }
    public function obtenerDocumentosLiq($contrato){
        $query = DB::SELECT("SELECT lq.*
        FROM 1_4_documento_liq lq
        WHERE lq.ven_codigo = ?
        AND (lq.doc_ci like '%ANT%' OR lq.doc_ci like '%LIQ%')
        ORDER BY lq.doc_codigo DESC
        ",[$contrato]);
        $datos  = [];
        foreach($query as $key => $item){
            $datos[$key] = [
                "venCodigo"                         => $item->ven_codigo,
                "docCodigo"                         => $item->doc_codigo,
                "docValor"                          => $item->doc_valor,
                "docNumero"                         => $item->doc_numero,
                "docNombre"                         => $item->doc_nombre,
                "docCi"                             => $item->doc_ci,
                "docCuenta"                         => $item->doc_cuenta,
                "docInstitucion"                    => $item->doc_institucion,
                "docTipo"                           => $item->doc_tipo,
                "docObservacion"                    => $item->doc_observacion,
                "docFecha"                          => $item->ven_codigo,
                "estVenCodigo"                      => $item->doc_fecha,
                "verificaciones_pagos_detalles_id"  => $item->verificaciones_pagos_detalles_id
            ];
        }
        return $datos;
    }
    //CONVENIOS
    public function obtenerConvenioInstitucionPeriodo($institucion,$periodo_id){
        $query = DB::SELECT("SELECT * FROM pedidos_convenios c
        WHERE c.institucion_id  = '$institucion'
        AND c.periodo_id        = '$periodo_id'
        AND (c.estado = '0' OR c.estado = '1')
        ");
        return $query;
    }
    public function getConvenioInstitucion($institucion){
        $query = DB::SELECT("SELECT * FROM pedidos_convenios c
        WHERE c.institucion_id = '$institucion'
        AND c.estado = '1'
        ");
        return $query;
    }
    public function updateDatosVerificacionPorIngresar($contrato,$estado){
        $query = Pedidos::Where('contrato_generado','=',$contrato)->update(['datos_verificacion_por_ingresar' => $estado]);
    }
    //asesores que tiene pedidos
    public function getAsesoresPedidos(){
        $query = DB::SELECT("SELECT DISTINCT p.id_asesor,
        CONCAT(u.nombres,' ',u.apellidos) as asesor
        FROM pedidos p
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        WHERE p.estado = '1'
        ORDER BY u.nombres ASC
        ");
        return $query;
    }
    public function tr_getInstitucionesDespacho($id_periodo){
        $query = DB::SELECT("SELECT DISTINCT p.ca_codigo_agrupado, i.ca_descripcion,p.id_periodo,i.ca_id,
        pe.codigo_contrato, i.ca_tipo_pedido,p.descuento
        FROM  pedidos p
        LEFT JOIN f_contratos_agrupados i ON i.ca_codigo_agrupado = p.ca_codigo_agrupado
        LEFT JOIN periodoescolar pe ON pe.idperiodoescolar = p.id_periodo
        WHERE p.ca_codigo_agrupado IS NOT NULL
        AND p.id_periodo = '$id_periodo'
        ORDER BY i.ca_id DESC
       ");
        return $query;
    }
    public function tr_getPreproformas($ca_codigo_agrupado){
        $query = DB::SELECT("SELECT DISTINCT fp.prof_id
        FROM  f_proforma fp
        WHERE fp.idPuntoventa = '$ca_codigo_agrupado'
        ORDER BY fp.created_at DESC
       ");
        return $query;
    }
    public function tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo){
        // consulta sin contrato
        $query = DB::SELECT("SELECT p.id_pedido,p.contrato_generado,
        CONCAT(u.nombres,' ',u.apellidos) as asesor, i.nombreInstitucion,c.nombre as ciudad, p.descuento,
        i.ruc
        FROM pedidos p
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        WHERE p.ca_codigo_agrupado = '$ca_codigo_agrupado'
        AND p.estado = '1'
        AND p.id_periodo = '$id_periodo'
        ORDER BY p.id_pedido DESC
        ");
       return $query;
    }
    public function tr_getInstitucionesVentaXTipoVenta($id_periodo,$tipo_venta){
        $query = DB::SELECT("SELECT p.id_pedido,p.contrato_generado,p.id_asesor,
        CONCAT(u.nombres,' ',u.apellidos) as asesor, i.nombreInstitucion,c.nombre as ciudad
        FROM pedidos p
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        WHERE p.tipo_venta = '$tipo_venta'
        AND p.estado = '1'
        AND p.id_periodo = '$id_periodo'
        AND p.ca_codigo_agrupado IS NULL
        AND p.contrato_generado IS NOT NULL
        ORDER BY p.id_pedido DESC
        ");
       return $query;
    }
    public function tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo,$tipo_venta,$asesor){
        $query = DB::SELECT("SELECT p.id_pedido,p.contrato_generado,p.id_asesor,
        CONCAT(u.nombres,' ',u.apellidos) as asesor, i.nombreInstitucion,c.nombre as ciudad
        FROM pedidos p
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        WHERE p.tipo_venta = '$tipo_venta'
        AND p.estado = '1'
        AND p.id_periodo = '$id_periodo'
        AND p.ca_codigo_agrupado IS NULL
        AND p.contrato_generado IS NOT NULL
        AND p.id_asesor = '$asesor'
        ORDER BY p.id_pedido DESC
        ");
       return $query;
    }
    public function tr_getPuntosVenta($busqueda){
        $query = DB::SELECT("SELECT  i.idInstitucion, i.nombreInstitucion,i.ruc,i.email,i.telefonoInstitucion,
        i.direccionInstitucion,  c.nombre as ciudad
        -- CONCAT(u.nombres,' ',u.apellidos) as representante
        FROM institucion i
        -- LEFT JOIN usuario u ON i.idrepresentante=u.idusuario
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        WHERE i.nombreInstitucion LIKE '%$busqueda%'
        ");
        return $query;
    }
    public function tr_getClientes($busqueda){
        $query = DB::SELECT("SELECT u.idusuario,u.cedula,u.nombres,u.apellidos,u.email,u.telefono,  CONCAT(u.nombres,' ',u.apellidos) as usuario
        FROM usuario u
        WHERE u.cedula LIKE '%$busqueda%'
        ");
        return $query;
    }
    public function tr_getCliente($busqueda){
        $query = DB::SELECT("SELECT u.idusuario,u.cedula,u.nombres,u.apellidos,u.email,u.telefono,
        CONCAT_WS(' ', u.nombres, u.apellidos) AS usuario
        FROM usuario u
        WHERE u.cedula = '$busqueda'
        ");
        return $query;
    }
    public function tr_getDespachoProforma($id_profroma){
        $proforma = DB::SELECT("SELECT p.*,
                i.nombreInstitucion,
                i.direccionInstitucion,
                i.ruc,
                i.telefonoInstitucion,
                i.email,
                CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) AS cliente,
                u.cedula,
                c.nombre AS ciudad
            FROM f_proforma p
            LEFT JOIN institucion i ON p.id_ins_depacho = i.idInstitucion
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            LEFT JOIN usuario u ON p.ven_cliente = u.idusuario
            WHERE p.prof_id = ?
        ",[$id_profroma]);
        return $proforma;
    }
    public function tr_getInformacionAgrupado($ca_codigo_agrupado){
        $query = DB::SELECT("SELECT i.*
        FROM f_contratos_agrupados i
        WHERE i.ca_codigo_agrupado = ?
        ",[$ca_codigo_agrupado]);
        //traer los pedidos que estan en el agrupado
        foreach($query as $key => $item){
            $query2 = DB::SELECT("SELECT *  FROM pedidos p
            WHERE p.ca_codigo_agrupado = ?",[$item->ca_codigo_agrupado]);
            //agregar al array $query
            $query[$key]->pedidos = $query2;
        }
        return $query;
    }
    public function tr_getInformacionInstitucionPerseo($institucion){
        $query = DB::SELECT("SELECT i.*,
            CONCAT(up.nombres, ' ', up.apellidos) AS clienteProlipa, up.cedula as cedulaProlipa, up.email as emailProlipa,
            CONCAT(uc.nombres, ' ', uc.apellidos) AS clienteCalmed, uc.cedula as cedulaCalmed, uc.email as emailCalmed
            FROM institucion i
            LEFT JOIN usuario up ON i.idrepresentante_prolipa = up.idusuario
            LEFT JOIN usuario uc ON i.idrepresentante_calmed = uc.idusuario
            WHERE i.idInstitucion = ?
        ",[$institucion]);
        return $query;
    }
}
