<?php

namespace App\Traits\Pedidos;

use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\Pedidos;
use App\Models\PedidoValArea;
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
    public $ipPerseo                    = "http://45.184.225.106:8181/api/";
    public $gl_perseoProduccion         = 0;
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
            OR o.estado_libros_obsequios  = "6"
            )
        ) as contadorHijosDocentesAbiertosEnviados,
        (
            SELECT  COUNT(o.id) FROM p_libros_obsequios o
            WHERE o.id_pedido = p.id_pedido
            AND o.estado_libros_obsequios = "5"
        ) as contadorHijosDocentesAbiertosAprobados,
        (
            SELECT  COUNT(o.id) FROM p_libros_obsequios o
            WHERE o.id_pedido = p.id_pedido
            AND o.estado_libros_obsequios = "8"
        ) as contadorObsequiosAbiertosEnviados,

        (
            SELECT COUNT(l.doc_codigo) AS contadorPendientesConvenio
            FROM 1_4_documento_liq l
            WHERE l.tipo_pago_id = "4"
            AND l.estado ="0"
            AND l.id_pedido = p.id_pedido
        ) AS contadorPendientesConvenio,
        (
            SELECT COUNT(l.doc_codigo) AS contadorPendientesAnticipos
            FROM 1_4_documento_liq l
            WHERE l.tipo_pago_id = "1"
            AND l.estado ="0"
            AND l.ifAntAprobado = "1"
            AND l.id_pedido = p.id_pedido
        ) AS contadorPendientesAnticipos,
        pe.periodoescolar as periodo,pe.codigo_contrato,
        CONCAT(uf.apellidos, " ",uf.nombres) as facturador,
        i.region_idregion as region,uf.cod_usuario,
        ph.fecha_generar_contrato,
        (p.TotalVentaReal - ((p.TotalVentaReal * p.descuento)/100)) AS ven_neta,
        (p.TotalVentaReal * p.descuento)/100 as valorDescuento,
        ps.id_grupo_finaliza, des.ca_descripcion as despacho,
        CONCAT(editComsion.nombres, " ", editComsion.apellidos) AS asesor_editComision
        '))
        ->leftjoin('usuario as u',          'p.id_asesor',          'u.idusuario')
        ->leftjoin('usuario as uf',         'p.id_usuario_verif',   'uf.idusuario')
        ->leftjoin('institucion as i',      'p.id_institucion',     'i.idInstitucion')
        ->leftjoin('ciudad as c',           'i.ciudad_id',          'c.idciudad')
        ->leftjoin('periodoescolar as pe',  'pe.idperiodoescolar',  'p.id_periodo')
        ->leftjoin('pedidos_historico as ph','p.id_pedido',         'ph.id_pedido')
        ->leftjoin('pedidos_solicitudes_gerencia as ps','p.id_solicitud_gerencia_comision','ps.id')
        ->leftJoin('usuario as editComsion',   'ps.user_finaliza',     'editComsion.idusuario')
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
    public function tr_getAsesoresPedidosXPeriodo($periodo){
        $query = DB::SELECT("SELECT DISTINCT p.id_asesor,
        CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) AS asesor
        FROM pedidos p
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        WHERE p.estado = '1'
        AND p.id_periodo = '$periodo'
        AND p.contrato_generado IS NOT NULL
        ORDER BY u.nombres ASC
        ");
        return $query;
    }
     //asesores diferentes a actas
     public function tr_getAsesoresFacturacionXPeriodo($id_periodo){
        $query = DB::SELECT("SELECT DISTINCT i.asesor_id, CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) AS asesor
        FROM f_venta f
        LEFT JOIN institucion i ON f.institucion_id = i.idInstitucion
        LEFT JOIN usuario u ON i.asesor_id = u.idusuario
        WHERE f.est_ven_codigo <> '3'
        AND f.periodo_id = ?
        AND f.idtipodoc <> '2'
        ORDER BY u.nombres ASC
        ",[$id_periodo]);
        return $query;
    }
    public function tr_getAsesoresFacturadoXPeriodo($id_periodo){
        $query = DB::SELECT("SELECT DISTINCT i.asesor_id, CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) AS asesor
        FROM f_venta_agrupado f
        LEFT JOIN institucion i ON f.institucion_id = i.idInstitucion
        LEFT JOIN usuario u ON i.asesor_id = u.idusuario
        AND f.periodo_id = ?
        ORDER BY u.nombres ASC
        ",[$id_periodo]);
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
    public function tr_puntosVentaDespachadosFacturacion($id_periodo){
        $query = DB::SELECT("SELECT DISTINCT f.institucion_id , i.nombreInstitucion
        FROM f_venta f
        LEFT JOIN institucion i ON f.institucion_id = i.idInstitucion
        WHERE i.punto_venta = '1'
        AND f.est_ven_codigo <> '3'
        AND f.periodo_id = ?
        ORDER BY f.institucion_id ASC
        ",[$id_periodo]);
        return $query;
    }
    //instituciones diferentes a actas
    public function tr_InstitucionesDespachadosFacturacionAsesor($id_periodo,$id_asesor){
        $query = DB::SELECT("SELECT DISTINCT f.institucion_id , i.nombreInstitucion
        FROM f_venta f
        LEFT JOIN institucion i ON f.institucion_id = i.idInstitucion
        WHERE f.est_ven_codigo <> '3'
        AND f.periodo_id = ?
        AND f.idtipodoc <> '2'
        AND i.asesor_id = ?
        ORDER BY f.institucion_id ASC
        ",[$id_periodo,$id_asesor]);
        return $query;
    }
    public function tr_InstitucionesDespachadosFacturadoAsesor($id_periodo,$id_asesor){
        $query = DB::SELECT("SELECT DISTINCT f.institucion_id , i.nombreInstitucion
        FROM f_venta_agrupado f
        LEFT JOIN institucion i ON f.institucion_id = i.idInstitucion
        WHERE f.periodo_id = ?
        AND i.asesor_id = ?
        ORDER BY f.institucion_id ASC
        ",[$id_periodo,$id_asesor]);
        return $query;
    }
    public function tr_getPreproformas($ca_codigo_agrupado){
        $query = DB::SELECT("SELECT DISTINCT fp.prof_id, fp.emp_id, fp.prof_estado
        FROM  f_proforma fp
        WHERE fp.idPuntoventa = '$ca_codigo_agrupado'
        ORDER BY fp.created_at DESC
       ");
        return $query;
    }
    public function tr_getPreproformasInstitucion($ca_codigo_agrupado){
        $query = DB::SELECT("SELECT DISTINCT i.nombreInstitucion, fp.id_ins_depacho, z.zn_nombre
        FROM  f_proforma fp
        LEFT JOIN institucion i ON fp.id_ins_depacho = i.idInstitucion
        LEFT JOIN i_zona z ON i.zona_id = z.idzona
        WHERE fp.idPuntoventa = '$ca_codigo_agrupado'
        ORDER BY fp.created_at DESC
       ");
        return $query;
    }
    public function tr_getDocumentos($ca_codigo_agrupado){
        $query = DB::SELECT("SELECT DISTINCT fv.ven_codigo FROM f_venta fv
        INNER JOIN f_proforma fpr ON fpr.prof_id = fv.ven_idproforma
        WHERE fpr.idPuntoventa = '$ca_codigo_agrupado'
        AND fv.est_ven_codigo <> 3
        ");
        return $query;
    }
    public function tr_getAgrupado($ca_codigo_agrupado){
        $query = DB::SELECT("SELECT *
        FROM  f_contratos_agrupados fp
        WHERE fp.ca_codigo_agrupado = '$ca_codigo_agrupado'
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
        if($tipo_venta == 1 || $tipo_venta == 2){
            $query = DB::SELECT("SELECT p.id_pedido,p.contrato_generado,p.id_asesor,
            CONCAT(u.nombres,' ',u.apellidos) as asesor, i.nombreInstitucion,c.nombre as ciudad
            FROM pedidos p
            LEFT JOIN usuario u ON p.id_asesor = u.idusuario
            LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            WHERE p.tipo_venta = '$tipo_venta'
            AND p.estado = '1'
            AND p.id_periodo = '$id_periodo'
            AND p.contrato_generado IS NOT NULL
            AND p.id_asesor = '$asesor'
            ORDER BY p.id_pedido DESC
            ");
        }else{
            $query = DB::SELECT("SELECT p.id_pedido,p.contrato_generado,p.id_asesor,
            CONCAT(u.nombres,' ',u.apellidos) as asesor, i.nombreInstitucion,c.nombre as ciudad
            FROM pedidos p
            LEFT JOIN usuario u ON p.id_asesor = u.idusuario
            LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            WHERE p.estado = '1'
            AND p.id_periodo = '$id_periodo'
            AND p.contrato_generado IS NOT NULL
            AND p.id_asesor = '$asesor'
            ORDER BY p.id_pedido DESC
            ");
        }
       
       return $query;
    }
    public function tr_getInstitucionesPeriodo($id_periodo){
        $query = DB::SELECT("SELECT p.id_pedido,p.contrato_generado,p.id_asesor,
        CONCAT(u.nombres,' ',u.apellidos) as asesor, i.nombreInstitucion,c.nombre as ciudad
        FROM pedidos p
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        WHERE p.estado = '1'
        AND p.tipo = '0'
        AND p.id_periodo = '$id_periodo'
        ORDER BY p.id_pedido DESC
        ");
       return $query;
    }
    public function tr_getPuntosVentasDespachos($periodo){
        $query = DB::SELECT("SELECT DISTINCT c.venta_lista_institucion, i.nombreInstitucion, venta_lista_institucion as institucion_id
        FROM codigoslibros c
        LEFT JOIN institucion i ON i.idInstitucion = c.venta_lista_institucion
        WHERE c.bc_periodo = ?
        AND c.venta_lista_institucion  > 0
        AND c.estado_liquidacion <> '3'
        AND c.estado_liquidacion <> '4'
        ORDER BY i.nombreInstitucion
        "
        ,[ $periodo ]);
        return $query;
    }
    public function tr_getPuntosVentasDirectasDespachos($periodo){
        $query = DB::SELECT("SELECT DISTINCT i.nombreInstitucion, bc_institucion as institucion_id
        FROM codigoslibros c
        LEFT JOIN institucion i ON i.idInstitucion = c.bc_institucion
        WHERE c.bc_periodo = ?
        AND c.bc_institucion  > 0
        AND c.estado_liquidacion <> '3'
        AND c.estado_liquidacion <> '4'
        AND ( c.venta_estado = '0' OR c.venta_estado = '1')
        ORDER BY i.nombreInstitucion
        "
        ,[ $periodo ]);
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
    public function tr_getPuntosVentaRegion($busqueda,$region,$id_periodo){
        $query = DB::SELECT("SELECT i.idInstitucion,
            i.nombreInstitucion,
            i.ruc,
            i.email,
            i.telefonoInstitucion,
            i.direccionInstitucion,
            c.nombre AS ciudad,
            MAX(CASE
                WHEN p.id_institucion IS NOT NULL THEN 1
                ELSE 0
            END) AS validate_pedidos,
        CONCAT(u.nombres,' ',u.apellidos) as representante
        FROM institucion i
        LEFT JOIN usuario u ON i.asesor_id=u.idusuario
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN pedidos p ON p.id_institucion = i.idInstitucion and p.id_periodo = $id_periodo
        WHERE i.nombreInstitucion LIKE '%$busqueda%'
        AND i.region_idregion = '$region'
        AND i.estado_idEstado = '1'
        GROUP BY
            i.idInstitucion, i.nombreInstitucion, i.ruc, i.email, i.telefonoInstitucion, i.direccionInstitucion, c.nombre
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
    public function tr_institucionesAsesorPedidos($id_periodo,$id_asesor){
        $query = DB::SELECT("SELECT DISTINCT p.id_pedido, p.id_asesor,
        CONCAT(u.nombres, ' ',u.apellidos) AS asesor,p.id_institucion,
        i.nombreInstitucion
        FROM pedidos p
        LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        WHERE p.id_periodo = ?
        AND p.id_asesor = ?
        AND p.estado = '1'
        AND p.contrato_generado IS NOT NULL
        ORDER BY i.nombreInstitucion
        ",[$id_periodo,$id_asesor]);
        return $query;
    }
    public function tr_getLibrosAsesores($periodo,$asesor_id,$request){
        $escuela_pedido = null;
        $guiasAsesor    = null;
        if (isset($request->escuela_pedido) && !empty($request->escuela_pedido)) { $escuela_pedido  = $request->escuela_pedido; }
        if (isset($request->guiasAsesor)    && !empty($request->guiasAsesor))    { $guiasAsesor     = $request->guiasAsesor; }
        // return $request->escuela_pedido;
        $val_pedido = PedidoValArea::select(
            'pv.valor',
            'pv.id_area',
            'pv.tipo_val',
            'pv.id_serie',
            'pv.year',
            'pv.plan_lector',
            'pv.alcance',
            'p.id_periodo',
            DB::raw("CONCAT(se.nombre_serie, ' ', ar.nombrearea) as serieArea"),
            'se.nombre_serie',
            'p.id_asesor',
            DB::raw("CONCAT(u.nombres, ' ', u.apellidos) as asesor")
        )
        ->from('pedidos_val_area as pv')
        ->leftJoin('area as ar', 'pv.id_area', '=', 'ar.idarea')
        ->leftJoin('series as se', 'pv.id_serie', '=', 'se.id_serie')
        ->leftJoin('pedidos as p', 'pv.id_pedido', '=', 'p.id_pedido')
        ->leftJoin('usuario as u', 'p.id_asesor', '=', 'u.idusuario')
        ->where('p.id_periodo', $periodo)
        ->where('p.id_asesor', $asesor_id)
        ->where('p.estado', '1')

        ->when($escuela_pedido,function($query,$escuela_pedido){
            $query->where('p.id_institucion', $escuela_pedido)
            ->where('p.tipo', '0')
            ->where('contrato_generado', '!=', null);
        })
        ->when($guiasAsesor,function($query){
            $query->where('p.tipo', '1')
            ->where('p.estado_entrega', '2');
        })
        ->distinct()
        ->groupBy('pv.id')
        ->get();
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
                "cantidad"          => $item->valor,
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
                $grouped[$codigo]->cantidad += $item->cantidad;
            }
        }

        // Convertir el array asociativo de nuevo a un array indexado
        $result = array_values($grouped);
        //subtotal
        foreach($result as $key => $item){
            $result[$key]->precio_total = $item->cantidad * $item->precio;
            //precio_total 2 decimales
            $result[$key]->precio_total = number_format($result[$key]->precio_total, 2, '.', '');
        }
        return $result;
    }
    public function tr_metodoFacturacion($request){
        $periodo                = $request->periodo ?? 0;
        $empresa                = $request->empresa ?? 0;
        $variasInstituciones    = $request->variasInstituciones ?? 0;
        $getInstitucionesId     = $request->getInstitucionesId ?? [];
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
            DB::raw('SUM(v.det_ven_cantidad) - SUM(v.det_ven_dev) as cantidadTotal')
        )
        ->where('d.periodo_id', $periodo)
        ->when($empresa > 0, function ($query) use ($empresa) {
            $query->where('d.id_empresa', '=', $empresa)
            ->where('v.id_empresa', '=', $empresa);
        })
        //when y wherein de getInstitucionesId
        ->when($variasInstituciones > 0, function ($query) use ($getInstitucionesId) {
            $query->whereIn('d.institucion_id', $getInstitucionesId)
            ->where('idtipodoc','<>','2');
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
            $item->precio_total = number_format($precio * $item->cantidadTotal, 2, '.', '');
        }
        return $result;
    }
    public function tr_metodoFacturado($request){
        $periodo                = $request->periodo ?? 0;
        $empresa                = $request->empresa ?? 0;
        $variasInstituciones    = $request->variasInstituciones ?? 0;
        $getInstitucionesId     = $request->getInstitucionesId ?? [];
        $tipo                   = $request->tipo ?? 0;

        $condiciones = [
            0 => [],
            1 => ['d.estadoPerseo' => 0],
            2 => ['d.estadoPerseo' => 1],
        ];

        $query = DB::table('f_detalle_venta_agrupado as v')
            ->leftJoin('f_venta_agrupado as d', function($join) {
                $join->on('v.id_factura', '=', 'd.id_factura')
                    ->on('v.id_empresa', '=', 'd.id_empresa');
            })
            ->leftJoin('1_4_cal_producto as p', 'v.pro_codigo', '=', 'p.pro_codigo')
            ->leftJoin('libros_series as ls', 'ls.codigo_liquidacion', '=', 'p.pro_codigo')
            ->leftJoin('libro as l', 'l.idlibro', '=', 'ls.idLibro')
            ->leftJoin('asignatura as a', 'a.idasignatura', '=', 'l.asignatura_idasignatura')
            ->select(
                'v.pro_codigo as codigo',
                'ls.nombre as nombrelibro',
                'ls.idLibro as libro_idlibro',
                'ls.year',
                'ls.id_serie',
                'a.area_idarea',
                'p.codigos_combos',
                'p.ifcombo',
                DB::raw('SUM(v.det_ven_cantidad) as cantidad'),
            )
            ->where('d.periodo_id', $periodo)
            ->when($empresa > 0, function ($query) use ($empresa) {
                $query->where('d.id_empresa', '=', $empresa)
                    ->where('v.id_empresa', '=', $empresa);
            })
            ->when($variasInstituciones > 0, function ($query) use ($getInstitucionesId) {
                $query->whereIn('d.institucion_id', $getInstitucionesId);
            })
            ->where($condiciones[$tipo])
            ->groupBy('v.pro_codigo', 'ls.nombre', 'ls.idLibro', 'ls.year', 'ls.id_serie', 'a.area_idarea', 'p.codigos_combos')
            ->orderBy('ls.nombre', 'desc')
            ->get();

        // Procesar los resultados para obtener el precio y multiplicar por la cantidad
        foreach ($query as $item) {
            // Obtener el precio del libro usando el repositorio
            $precio             = $this->pedidosRepository->getPrecioXLibro($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
            $item->precio       = $precio;
            // Multiplicar el precio por la cantidad
            $item->precio_total = number_format($precio * $item->cantidad, 2, '.', '');
        }

        return $query;
    }

    public function tr_institucionesVentasPeriodo($id_periodo){
        $query = DB::SELECT("SELECT DISTINCT i.idInstitucion AS id_institucion, i.nombreInstitucion
            FROM  f_venta fv 
            INNER JOIN institucion i ON i.idInstitucion = fv.institucion_id
            WHERE fv.periodo_id = ?
            AND fv.est_ven_codigo <> 3
            AND fv.idtipodoc IN (1, 2, 3, 4)
        ",[$id_periodo]);
        return $query;
    }
    
    //asesores que tiene Ventas
    public function getAsesoresVentasPeriodo($id_periodo){
        $query = DB::SELECT("SELECT DISTINCT u.idusuario AS id_asesor, 
            CONCAT(u.nombres, ' ', u.apellidos) AS asesor
            FROM f_venta fv
            INNER JOIN institucion i ON i.idInstitucion = fv.institucion_id
            INNER JOIN usuario u ON i.asesor_id = u.idusuario
            WHERE fv.periodo_id = ?
            AND fv.est_ven_codigo <> 3
            AND fv.institucion_id IS NOT NULL 
            AND fv.idtipodoc IN (1, 2, 3, 4);
        ",[$id_periodo]);
        return $query;
    }

    //TRAIT JEYSON INICIO
    public function tr_get_val_pedidoInfo($pedido){
        try{
            $val_pedido = DB::SELECT("SELECT DISTINCT pv.*,
            p.descuento, p.id_periodo,
            p.anticipo, p.comision, CONCAT(se.nombre_serie,' ',ar.nombrearea) as serieArea,
            se.nombre_serie,p.fecha_aprobado_facturacion
            FROM pedidos_val_area pv
            left join area ar ON  pv.id_area = ar.idarea
            left join series se ON pv.id_serie = se.id_serie
            INNER JOIN pedidos p ON pv.id_pedido = p.id_pedido
            WHERE pv.id_pedido = '$pedido'
            AND pv.alcance = '0'
            GROUP BY pv.id;
            ");
            $datos = [];
            foreach($val_pedido as $key => $item){
                $valores = [];
                //plan lector
                if($item->plan_lector > 0 ){
                    $getPlanlector = DB::SELECT("SELECT l.nombrelibro,l.idlibro,l.asignatura_idasignatura,
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
                    WHERE l.idlibro = '$item->plan_lector'
                    ");
                    $valores = $getPlanlector;
                }else{
                    $getLibros = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro,l.asignatura_idasignatura,
                    (
                        SELECT f.pvp AS precio
                        FROM pedidos_formato f
                        WHERE f.id_serie = ls.id_serie
                        AND f.id_area = a.area_idarea
                        AND f.id_periodo = '$item->id_periodo'
                    )as precio
                    FROM libros_series ls
                    LEFT JOIN libro l ON ls.idLibro = l.idlibro
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
                $datos[$key] = [
                    "id"                => $item->id,
                    "id_pedido"         => $item->id_pedido,
                    "valor"             => $item->valor,
                    "id_area"           => $item->id_area,
                    "tipo_val"          => $item->tipo_val,
                    "id_serie"          => $item->id_serie,
                    "year"              => $item->year,
                    "anio"              => $valores[0]->year,
                    "version"           => $valores[0]->version,
                    "created_at"        => $item->created_at,
                    "updated_at"        => $item->updated_at,
                    "descuento"         => $item->descuento,
                    "anticipo"          => $item->anticipo,
                    "comision"          => $item->comision,
                    "plan_lector"       => $item->plan_lector,
                    "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$valores[0]->nombrelibro : $item->serieArea,
                    "idlibro"           => $valores[0]->idlibro,
                    "nombrelibro"       => $valores[0]->nombrelibro,
                    "precio"            => $valores[0]->precio,
                    "idasignatura"      => $valores[0]->asignatura_idasignatura,
                    "subtotal"          => $item->valor * $valores[0]->precio,
                    "codigo_liquidacion"=> $valores[0]->codigo_liquidacion,
                    "fecha_aprobado_facturacion" => $item->fecha_aprobado_facturacion,
                    "cantidad_pendiente" => $item->cantidad_pendiente,
                    "cantidad_pendiente_especifico" => $item->cantidad_pendiente_especifico,
                ];
            }
            return $datos;
        }
        catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
    }

    public function tr_get_val_pedidoInfo_new($pedido){
        try{
            $val_pedido = DB::SELECT("SELECT DISTINCT pv.pvn_id AS id,
                                pv.id_pedido,
                                pv.pvn_cantidad AS valor,
                                ar.idarea AS id_area,
                                s.id_serie,
                                ls.year,
                                pv.pvn_tipo,
                                pv.created_at,
                                pv.updated_at,
                                l.idlibro,
                                p.descuento,
                                p.id_periodo,
                                p.anticipo,
                                p.comision,
                                l.nombrelibro as serieArea,
                                s.nombre_serie,
                                ls.version,
                                asi.idasignatura,
                                ls.codigo_liquidacion,
                                p.fecha_aprobado_facturacion,
                                pv.cantidad_pendiente,
                                pv.cantidad_pendiente_especifico
                FROM pedidos_val_area_new pv
                LEFT JOIN libro l ON  pv.idlibro = l.idlibro
                LEFT JOIN libros_series ls ON pv.idlibro = ls.idLibro
                LEFT JOIN asignatura asi ON l.asignatura_idasignatura = asi.idasignatura
                LEFT JOIN area ar ON asi.area_idarea = ar.idarea
                LEFT JOIN series s ON ls.id_serie = s.id_serie
                INNER JOIN pedidos p ON pv.id_pedido = p.id_pedido
                WHERE pv.id_pedido = '$pedido'
                AND pv.pvn_tipo = '0'
                GROUP BY pv.pvn_id, s.nombre_serie, ls.year, s.id_serie, ls.version, ls.codigo_liquidacion;
            ");
            $final_result = [];
            foreach ($val_pedido as $item) {
                $var_planlector = '';
                $var_year = '';
                $var_idarea = '';
                // Busca el pfn_pvp correcto basado en el id_periodo
                $pfn_pvp_result = (float) DB::table('pedidos_formato_new')
                ->where('idperiodoescolar', $item->id_periodo)
                ->where('idlibro', $item->idlibro)
                ->value('pfn_pvp');
                //Asigna id de libro si es plan lector
                if($item->id_serie == 6){
                    $var_planlector = $item->idlibro;
                    $var_year = 0;
                    $var_idarea = $item->idlibro;
                }else if($item->id_serie <> 6){
                    $var_planlector = 0;
                    $var_year = $item->year;
                    $var_idarea = $item->id_area;
                }

                // Construye el array final
                $final_result[] = [
                    "id"                => $item->id,
                    "id_pedido"         => $item->id_pedido,
                    "valor"             => $item->valor,
                    "id_area"           => $var_idarea,
                    "id_serie"          => $item->id_serie,
                    "year"              => $var_year,
                    "anio"              => $item->year,
                    "version"           => $item->version,
                    "pvn_tipo"          => $item->pvn_tipo,
                    "created_at"        => $item->created_at,
                    "updated_at"        => $item->updated_at,
                    "descuento"         => $item->descuento,
                    "anticipo"          => $item->anticipo,
                    "comision"          => $item->comision,
                    "plan_lector"       => $var_planlector,
                    "id_periodo"        => $item->id_periodo,
                    "serieArea"         => $item->serieArea,
                    "idlibro"           => $item->idlibro,
                    "nombrelibro"       => $item->serieArea,
                    "nombre_serie"      => $item->nombre_serie,
                    "precio"            => $pfn_pvp_result,  // Añade el pfn_pvp correcto
                    "idasignatura"      => $item->idasignatura,
                    "subtotal"          => $item->valor * $pfn_pvp_result,  // Añade el pfn_pvp correcto
                    "codigo_liquidacion"=> $item->codigo_liquidacion,
                    "fecha_aprobado_facturacion" => $item->fecha_aprobado_facturacion,
                    "cantidad_pendiente" => $item->cantidad_pendiente,
                    "cantidad_pendiente_especifico" => $item->cantidad_pendiente_especifico,
                ];
            }
            return $final_result;
        }
        catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
    }

    public function tr_metodoFacturacion_new($request){
        $periodo                = $request->periodo ?? 0;
        $empresa                = $request->empresa ?? 0;
        $variasInstituciones    = $request->variasInstituciones ?? 0;
        $getInstitucionesId     = $request->getInstitucionesId ?? [];
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
            DB::raw('SUM(v.det_ven_cantidad) - SUM(v.det_ven_dev) as cantidadTotal')
        )
        ->where('d.periodo_id', $periodo)
        ->when($empresa > 0, function ($query) use ($empresa) {
            $query->where('d.id_empresa', '=', $empresa)
            ->where('v.id_empresa', '=', $empresa);
        })
        //when y wherein de getInstitucionesId
        ->when($variasInstituciones > 0, function ($query) use ($getInstitucionesId) {
            $query->whereIn('d.institucion_id', $getInstitucionesId)
            ->where('idtipodoc','<>','2');
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
            $item->precio_total = number_format($precio * $item->cantidadTotal, 2, '.', '');
        }
        return $result;
    }

    public function tr_metodoFacturado_new($request){
        $periodo                = $request->periodo ?? 0;
        $empresa                = $request->empresa ?? 0;
        $variasInstituciones    = $request->variasInstituciones ?? 0;
        $getInstitucionesId     = $request->getInstitucionesId ?? [];
        $tipo                   = $request->tipo ?? 0;

        $condiciones = [
            0 => [],
            1 => ['d.estadoPerseo' => 0],
            2 => ['d.estadoPerseo' => 1],
        ];

        $query = DB::table('f_detalle_venta_agrupado as v')
            ->leftJoin('f_venta_agrupado as d', function($join) {
                $join->on('v.id_factura', '=', 'd.id_factura')
                    ->on('v.id_empresa', '=', 'd.id_empresa');
            })
            ->leftJoin('1_4_cal_producto as p', 'v.pro_codigo', '=', 'p.pro_codigo')
            ->leftJoin('libros_series as ls', 'ls.codigo_liquidacion', '=', 'p.pro_codigo')
            ->leftJoin('libro as l', 'l.idlibro', '=', 'ls.idLibro')
            ->leftJoin('asignatura as a', 'a.idasignatura', '=', 'l.asignatura_idasignatura')
            ->select(
                'v.pro_codigo as codigo',
                'ls.nombre as nombrelibro',
                'ls.idLibro as libro_idlibro',
                'ls.year',
                'ls.id_serie',
                'a.area_idarea',
                'p.codigos_combos',
                'p.ifcombo',
                DB::raw('SUM(v.det_ven_cantidad) as cantidad'),
            )
            ->where('d.periodo_id', $periodo)
            ->when($empresa > 0, function ($query) use ($empresa) {
                $query->where('d.id_empresa', '=', $empresa)
                    ->where('v.id_empresa', '=', $empresa);
            })
            ->when($variasInstituciones > 0, function ($query) use ($getInstitucionesId) {
                $query->whereIn('d.institucion_id', $getInstitucionesId);
            })
            ->where($condiciones[$tipo])
            ->groupBy('v.pro_codigo', 'ls.nombre', 'ls.idLibro', 'ls.year', 'ls.id_serie', 'a.area_idarea', 'p.codigos_combos')
            ->orderBy('ls.nombre', 'desc')
            ->get();

        // Procesar los resultados para obtener el precio y multiplicar por la cantidad
        foreach ($query as $item) {
            // Obtener el precio del libro usando el repositorio
            $precio             = $this->pedidosRepository->getPrecioXLibro_new($item->id_serie, $item->libro_idlibro, $item->area_idarea, $periodo, $item->year);
            $item->precio       = $precio;
            // Multiplicar el precio por la cantidad
            $item->precio_total = number_format($precio * $item->cantidad, 2, '.', '');
        }

        return $query;
    }
    //TRAIT JEYSON FIN
}
