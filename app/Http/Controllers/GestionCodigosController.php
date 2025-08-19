<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\CodigosLibros;
use App\Models\HistoricoCodigos;
use App\Traits\Codigos\TraitCodigosGeneral;

class GestionCodigosController extends Controller
{
    use TraitCodigosGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $consulta = $this->getCodigosVerificaciones($request->codigo);
        if(empty($consulta)){
            return ["status" => "0","message" => "No se encontro codigos"];
        }else{
            return $consulta;
        }
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
        //editar
        if($request->editar == "1"){
            $codigo     = CodigosLibros::findOrFail($request->codigo);
            $old_values = CodigosLibros::findOrFail($request->codigo);
            $comentario = $request->comentario;
        }
        //guardar
        else{
            $comentario = $request->comentario;
            $old_values = "";
            //validar si existe codigo
            $validate = DB::SELECT("SELECT * FROM codigoslibros WHERE codigo = '$request->codigo'");
            if(empty($validate)){
            }else{
                return  ["status" => "3", "message" => "El codigo ya existe"];
            }
            $codigo                             = new CodigosLibros();
            $codigo->codigo                     = $request->codigo;
            $codigo->idusuario_creador_codigo   = $request->user_created;
        }
            $codigo->serie                      = $request->serie;
            $codigo->libro                      = $request->libro;
            $codigo->anio                       = $request->anio;
            $codigo->idusuario                  = $request->idusuario;
            $codigo->libro_idlibro              = $request->libro_idlibro;
            $codigo->estado                     = $request->estado;
            $codigo->id_periodo                 = $request->id_periodo;
            $codigo->contrato                   = $request->contrato;
            $codigo->venta_lista_institucion    = $request->venta_lista_institucion;
            $codigo->verif1                     = $request->verif1 == null || $request->verif1 == "null" ? null : $request->verif1;
            $codigo->verif2                     = $request->verif2 == null || $request->verif2 == "null" ? null : $request->verif2;
            $codigo->verif3                     = $request->verif3 == null || $request->verif3 == "null" ? null : $request->verif3;
            $codigo->verif4                     = $request->verif4 == null || $request->verif4 == "null" ? null : $request->verif4;
            $codigo->verif5                     = $request->verif5 == null || $request->verif5 == "null" ? null : $request->verif5;
            $codigo->estado_liquidacion         = $request->estado_liquidacion;
            $codigo->bc_estado                  = $request->bc_estado;
            $codigo->bc_institucion             = $request->bc_institucion;
            $codigo->bc_periodo                 = $request->bc_periodo;
            $codigo->bc_fecha_ingreso           = $request->bc_fecha_ingreso;
            $codigo->venta_estado               = $request->venta_estado;
            $codigo->contador                   = $request->contador;
            $codigo->codigo_union               = $request->codigo_union == null || $request->codigo_union == "null" ? null: $request->codigo_union;
            $codigo->liquidado_regalado         = $request->liquidado_regalado;
            $codigo->prueba_diagnostica         = $request->prueba_diagnostica;
            $codigo->save();
            if($codigo){
             //Guardar en el historico
            $this->GuardarEnHistorico($request->user_created,$request->institucion_id,$request->periodo_id,$request->codigo,$request->user_created,$comentario,$old_values,$codigo);
                return ["status" => "1", "message" => "Se guardo correctamente"];
            }else{
                return ["status" => "0", "message" => "No se pudo guardar"];
            }
    }
    public function guardarCodigoParametros(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos    = json_decode($request->data_codigos);
        $withCodigoUnion = $request->withCodigoUnion;
        $contador   = 0;
        $arrayProblemas = [];
        // transaccion
        try {
            DB::beginTransaction();

            //editar
            foreach($codigos as $key => $item){
                $codigo     = CodigosLibros::findOrFail($item->codigo);
                $old_values = clone $codigo;
                $comentario = $request->comentario;
                $codigo_union = $codigo->codigo_union;
                $bc_periodo  = $codigo->bc_periodo;
                //update
                if($request->chkIdusuario                   == '1') $codigo->idusuario                  = $request->idusuario;
                if($request->chkBc_Estado                   == '1') $codigo->estado                     = $request->estado;
                if($request->chkId_periodo                  == '1') $codigo->id_periodo                 = $request->id_periodo;
                if($request->chkContrato                    == '1') $codigo->contrato                   = $request->contrato;
                if($request->chkBc_Venta_lista_institucion  == '1') $codigo->venta_lista_institucion    = $request->venta_lista_institucion;
                if($request->chkBc_Verif1                   == '1') $codigo->verif1                     = $request->verif1;
                if($request->chkBc_Verif2                   == '1') $codigo->verif2                     = $request->verif2;
                if($request->chkBc_Verif3                   == '1') $codigo->verif3                     = $request->verif3;
                if($request->chkBc_Verif4                   == '1') $codigo->verif4                     = $request->verif4;
                if($request->chkBc_Verif5                   == '1') $codigo->verif5                     = $request->verif5;
                if($request->chkEstado_liquidacion          == '1') $codigo->estado_liquidacion         = $request->estado_liquidacion;
                if($request->chk_estado                     == '1') $codigo->bc_estado                  = $request->bc_estado;
                if($request->chkBc_Bc_institucion           == '1') $codigo->bc_institucion             = $request->bc_institucion;
                if($request->chkBc_Bc_periodo               == '1') $codigo->bc_periodo                 = $request->bc_periodo;
                if($request->chkBc_Venta_estado             == '1') $codigo->venta_estado               = $request->venta_estado;
                if($request->chkBc_codigo_union             == '1') $codigo->codigo_union               = $request->codigo_union;
                if($request->chkBc_liquidado_regalado       == '1') $codigo->liquidado_regalado         = $request->liquidado_regalado;
                if($request->chkBc_codigo_proforma          == '1') $codigo->codigo_proforma            = $request->codigo_proforma ;
                if($request->chkBc_proforma_empresa         == '1') $codigo->proforma_empresa           = $request->proforma_empresa;
                if($request->chkCombo                       == '1') $codigo->combo                      = $request->combo;
                if($request->chkCodigo_combo                == '1') $codigo->codigo_combo               = $request->codigo_combo;
                if($request->chkFactura                     == '1') $codigo->factura                    = $request->factura;
                // validacion codigo_combo
                if($request->chkCodigo_combo                == '1'){
                    //validar que el combo exista en la codigos_combos columna codigo
                    $validarCombo = DB::SELECT("SELECT * FROM codigos_combos WHERE codigo = '$request->codigo_combo'");
                    if(empty($validarCombo)){
                        $arrayProblemas[] = [
                            "codigo" => $item->codigo,
                            "message" => "El codigo combo no existe en la tabla codigos_combos"
                        ];
                        continue;
                    }
                }
                $codigo->save();
                if($codigo){
                    //Guardar en el historico
                    $this->GuardarEnHistorico($request->user_created,$request->institucion_id,$bc_periodo,$item->codigo,$request->user_created,$comentario,$old_values,json_encode($codigo->getAttributes()));
                    //actualizar codigo_union si la opcion esta seleccionada
                    if($withCodigoUnion == "1"){
                        $this->actualizarCodigoUnion($codigo_union,$request);
                        $contador++;
                    }else{
                        $contador++;
                    }
                }
            }
            // Commit the transaction
            DB::commit();
            return [
                "contador" => $contador,
                "arrayProblemas" => $arrayProblemas,
            ];

        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollback();
            return [
                "status" => "0",
                "message" => "Error al guardar los codigos: " . $e->getMessage(),
            ];
        }
    }

    public function actualizarCodigoUnion($codigo_union, $request)
    {
        $codigoActualizar = CodigosLibros::findOrFail($codigo_union);
         // 👉 Clonar antes de hacer cambios para conservar los valores originales
        $old_values = clone $codigoActualizar;

        if($request->chkIdusuario                   == '1') $codigoActualizar->idusuario                  = $request->idusuario;
        if($request->chkBc_Estado                   == '1') $codigoActualizar->estado                     = $request->estado;
        if($request->chkId_periodo                  == '1') $codigoActualizar->id_periodo                 = $request->id_periodo;
        if($request->chkContrato                    == '1') $codigoActualizar->contrato                   = $request->contrato;
        if($request->chkBc_Venta_lista_institucion  == '1') $codigoActualizar->venta_lista_institucion    = $request->venta_lista_institucion;
        if($request->chkBc_Verif1                   == '1') $codigoActualizar->verif1                     = $request->verif1;
        if($request->chkBc_Verif2                   == '1') $codigoActualizar->verif2                     = $request->verif2;
        if($request->chkBc_Verif3                   == '1') $codigoActualizar->verif3                     = $request->verif3;
        if($request->chkBc_Verif4                   == '1') $codigoActualizar->verif4                     = $request->verif4;
        if($request->chkBc_Verif5                   == '1') $codigoActualizar->verif5                     = $request->verif5;
        if($request->chkEstado_liquidacion          == '1') $codigoActualizar->estado_liquidacion         = $request->estado_liquidacion;
        if($request->chk_estado                     == '1') $codigoActualizar->bc_estado                  = $request->bc_estado;
        if($request->chkBc_Bc_institucion           == '1') $codigoActualizar->bc_institucion             = $request->bc_institucion;
        if($request->chkBc_Bc_periodo               == '1') $codigoActualizar->bc_periodo                 = $request->bc_periodo;
        if($request->chkBc_Venta_estado             == '1') $codigoActualizar->venta_estado               = $request->venta_estado;
        if($request->chkBc_liquidado_regalado       == '1') $codigoActualizar->liquidado_regalado         = $request->liquidado_regalado;
        if($request->chkBc_codigo_proforma          == '1') $codigoActualizar->codigo_proforma            = $request->codigo_proforma;
        if($request->chkBc_proforma_empresa         == '1') $codigoActualizar->proforma_empresa           = $request->proforma_empresa;
        if($request->chkCombo                       == '1') $codigoActualizar->combo                      = $request->combo;
        if($request->chkCodigo_combo                == '1') $codigoActualizar->codigo_combo               = $request->codigo_combo;
        if($request->chkFactura                     == '1') $codigoActualizar->factura                    = $request->factura;

        $codigoActualizar->save();

        // Guardar en el histórico
        $this->GuardarEnHistorico(
            $request->user_created,
            $request->institucion_id,
            $codigoActualizar->bc_periodo,
            $codigoActualizar->codigo,
            $request->user_created,
            $request->comentario,
            $old_values,
            json_encode($codigoActualizar->getAttributes())
        );
    }

    //api:get>/traerCodigosParametros
    public function traerCodigosParametros(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //CODIGOS REGALADOS
        if($request->tipo == "regalado"){
           $codigos = DB::SELECT("SELECT c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
           IF(c.prueba_diagnostica ='1', 'Prueba de diagnóstico','Código normal') as tipoCodigo,
           c.porcentaje_descuento,
           c.libro as book,c.serie,c.created_at,
           c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,c.bc_fecha_ingreso,
           c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,
           c.contrato,c.libro, c.venta_lista_institucion,
           CONCAT(u.nombres, ' ', u.apellidos) as estudiante, u.email,u.cedula, ib.nombreInstitucion as institucion_barras,
           i.nombreInstitucion, p.periodoescolar as periodo,pb.periodoescolar as periodo_barras,
           IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
           (case when (c.estado_liquidacion = '0') then 'liquidado'
               when (c.estado_liquidacion = '1') then 'sin liquidar'
               when (c.estado_liquidacion = '2') then 'codigo regalado'
               when (c.estado_liquidacion = '3') then 'codigo devuelto'
           end) as liquidacion,
           (case when (c.bc_estado = '2') then 'codigo leido'
           when (c.bc_estado = '1') then 'codigo sin leer'
           end) as barrasEstado,
           (case when (c.venta_estado = '0') then ''
               when (c.venta_estado = '1') then 'Venta directa'
               when (c.venta_estado = '2') then 'Venta por lista'
           end) as ventaEstado,
           ib.nombreInstitucion as institucionBarra, i.nombreInstitucion,
           p.periodoescolar as periodo,
           pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
           c.codigo_paquete,c.fecha_registro_paquete
           FROM codigoslibros c
           LEFT JOIN usuario u ON c.idusuario = u.idusuario
            LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
            LEFT JOIN periodoescolar p ON c.id_periodo = p.idperiodoescolar
            LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
           WHERE
                (
                c.bc_institucion = '$request->institucion_id'
                OR venta_lista_institucion = '$request->institucion_id'
                )
            AND c.bc_periodo = '$request->periodo_id'
            AND
                (
                c.estado = '1' OR c.estado = '0' OR c.estado = '' OR c.estado IS NULL
                )
            AND c.estado_liquidacion = '2'
            LIMIT 2000
            ");
            return $codigos;
        }
        //CODIGOS LIQUIDADOS
        if($request->tipo == "liquidados"){
            $codigos = DB::SELECT("SELECT c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
            IF(c.prueba_diagnostica ='1', 'Prueba de diagnóstico','Código normal') as tipoCodigo,
            c.porcentaje_descuento,
            c.libro as book,c.serie,c.created_at,
            c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,c.bc_fecha_ingreso,
            c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,
            c.contrato,c.libro, c.venta_lista_institucion,
            CONCAT(u.nombres, ' ', u.apellidos) as estudiante, u.email,u.cedula, ib.nombreInstitucion as institucion_barras,
            i.nombreInstitucion, p.periodoescolar as periodo,pb.periodoescolar as periodo_barras,
            IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
            (case when (c.estado_liquidacion = '0') then 'liquidado'
                when (c.estado_liquidacion = '1') then 'sin liquidar'
                when (c.estado_liquidacion = '2') then 'codigo regalado'
                when (c.estado_liquidacion = '3') then 'codigo devuelto'
            end) as liquidacion,
            (case when (c.bc_estado = '2') then 'codigo leido'
            when (c.bc_estado = '1') then 'codigo sin leer'
            end) as barrasEstado,
            (case when (c.venta_estado = '0') then ''
                when (c.venta_estado = '1') then 'Venta directa'
                when (c.venta_estado = '2') then 'Venta por lista'
            end) as ventaEstado,
            ib.nombreInstitucion as institucionBarra, i.nombreInstitucion,
            p.periodoescolar as periodo,
            pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
            c.codigo_paquete,c.fecha_registro_paquete
            FROM codigoslibros c
            LEFT JOIN usuario u ON c.idusuario = u.idusuario
            LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
            LEFT JOIN periodoescolar p ON c.id_periodo = p.idperiodoescolar
            LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
            WHERE
                (
                c.bc_institucion = '$request->institucion_id'
                OR venta_lista_institucion = '$request->institucion_id'
                )
             AND c.bc_periodo = '$request->periodo_id'
             AND
                (
                c.estado = '1' OR c.estado = '0' OR c.estado = '' OR c.estado IS NULL
                )
            AND c.estado_liquidacion = '0'
            LIMIT 2000
            ");
            return $codigos;
        }
        //CODIGOS DEVUELTOS
        if($request->tipo == "devueltos"){
            $codigos = DB::SELECT("SELECT c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
            IF(c.prueba_diagnostica ='1', 'Prueba de diagnóstico','Código normal') as tipoCodigo,
            c.porcentaje_descuento,
            c.libro as book,c.serie,c.created_at,
            c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,c.bc_fecha_ingreso,
            c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,
            c.contrato,c.libro, c.venta_lista_institucion,
            CONCAT(u.nombres, ' ', u.apellidos) as estudiante, u.email,u.cedula, ib.nombreInstitucion as institucion_barras,
            i.nombreInstitucion, p.periodoescolar as periodo,pb.periodoescolar as periodo_barras,
            IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
            (case when (c.estado_liquidacion = '0') then 'liquidado'
                when (c.estado_liquidacion = '1') then 'sin liquidar'
                when (c.estado_liquidacion = '2') then 'codigo regalado'
                when (c.estado_liquidacion = '3') then 'codigo devuelto'
            end) as liquidacion,
            (case when (c.bc_estado = '2') then 'codigo leido'
            when (c.bc_estado = '1') then 'codigo sin leer'
            end) as barrasEstado,
            (case when (c.venta_estado = '0') then ''
                when (c.venta_estado = '1') then 'Venta directa'
                when (c.venta_estado = '2') then 'Venta por lista'
            end) as ventaEstado,
            ib.nombreInstitucion as institucionBarra, i.nombreInstitucion,
            p.periodoescolar as periodo,
            pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
            c.codigo_paquete,c.fecha_registro_paquete
            FROM codigoslibros c
            LEFT JOIN usuario u ON c.idusuario = u.idusuario
            LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
            LEFT JOIN periodoescolar p ON c.id_periodo = p.idperiodoescolar
            LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
            WHERE
                (
                c.bc_institucion = '$request->institucion_id'
                OR venta_lista_institucion = '$request->institucion_id'
                )
                AND c.bc_periodo = '$request->periodo_id'
                AND c.estado_liquidacion = '3'
                LIMIT 2000
            ");
            return $codigos;
        }
        //CODIGOS LEIDOS
        if($request->tipo == "leidos"){
        $codigos = DB::SELECT("SELECT c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
        IF(c.prueba_diagnostica ='1', 'Prueba de diagnóstico','Código normal') as tipoCodigo,
        c.porcentaje_descuento,
        c.libro as book,c.serie,c.created_at,
        c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,c.bc_fecha_ingreso,
        c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,
        c.contrato,c.libro, c.venta_lista_institucion,
        CONCAT(u.nombres, ' ', u.apellidos) as estudiante, u.email,u.cedula, ib.nombreInstitucion as institucion_barras,
        i.nombreInstitucion, p.periodoescolar as periodo,pb.periodoescolar as periodo_barras,
        IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
        (case when (c.estado_liquidacion = '0') then 'liquidado'
            when (c.estado_liquidacion = '1') then 'sin liquidar'
            when (c.estado_liquidacion = '2') then 'codigo regalado'
            when (c.estado_liquidacion = '3') then 'codigo devuelto'
        end) as liquidacion,
        (case when (c.bc_estado = '2') then 'codigo leido'
        when (c.bc_estado = '1') then 'codigo sin leer'
        end) as barrasEstado,
        (case when (c.venta_estado = '0') then ''
            when (c.venta_estado = '1') then 'Venta directa'
            when (c.venta_estado = '2') then 'Venta por lista'
        end) as ventaEstado,
        ib.nombreInstitucion as institucionBarra, i.nombreInstitucion,
        p.periodoescolar as periodo,
        pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
        c.codigo_paquete,c.fecha_registro_paquete
            FROM codigoslibros c
            LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
            LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
            LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
            WHERE
                (
                c.bc_institucion = '$request->institucion_id'
                OR venta_lista_institucion = '$request->institucion_id'
                )
                AND c.bc_periodo = '$request->periodo_id'
                AND
                (
                c.estado = '1' OR c.estado = '0' OR c.estado = '' OR c.estado IS NULL
                )
            AND c.estado_liquidacion = '1'
            AND c.bc_estado          = '2'
            LIMIT 2000
            ");
            return $codigos;
        }
        //CODIGOS NO LEIDOS
        if($request->tipo == "no_leidos"){
        $codigos = DB::SELECT("SELECT c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
            IF(c.prueba_diagnostica ='1', 'Prueba de diagnóstico','Código normal') as tipoCodigo,
            c.porcentaje_descuento,
            c.libro as book,c.serie,c.created_at,
            c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,c.bc_fecha_ingreso,
            c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,
            c.contrato,c.libro, c.venta_lista_institucion,
            CONCAT(u.nombres, ' ', u.apellidos) as estudiante, u.email,u.cedula, ib.nombreInstitucion as institucion_barras,
            i.nombreInstitucion, p.periodoescolar as periodo,pb.periodoescolar as periodo_barras,
            IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
            (case when (c.estado_liquidacion = '0') then 'liquidado'
                when (c.estado_liquidacion = '1') then 'sin liquidar'
                when (c.estado_liquidacion = '2') then 'codigo regalado'
                when (c.estado_liquidacion = '3') then 'codigo devuelto'
            end) as liquidacion,
            (case when (c.bc_estado = '2') then 'codigo leido'
            when (c.bc_estado = '1') then 'codigo sin leer'
            end) as barrasEstado,
            (case when (c.venta_estado = '0') then ''
                when (c.venta_estado = '1') then 'Venta directa'
                when (c.venta_estado = '2') then 'Venta por lista'
            end) as ventaEstado,
            ib.nombreInstitucion as institucionBarra, i.nombreInstitucion,
            p.periodoescolar as periodo,
            pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
            c.codigo_paquete,c.fecha_registro_paquete
            FROM codigoslibros c
            LEFT JOIN usuario u ON c.idusuario = u.idusuario
            LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
            LEFT JOIN periodoescolar p ON c.id_periodo = p.idperiodoescolar
            LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
            WHERE
                (
                c.bc_institucion = '$request->institucion_id'
                OR venta_lista_institucion = '$request->institucion_id'
                )
                AND c.bc_periodo = '$request->periodo_id'
                AND
                (
                c.estado = '1' OR c.estado = '0' OR c.estado = '' OR c.estado IS NULL
                )
            AND c.estado_liquidacion = '1'
            AND c.bc_estado          = '1'
            LIMIT 2000
            ");
            return $codigos;
        }
        //TODOS
        if($request->tipo == "todos"){
        $codigos = DB::SELECT("SELECT c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
            IF(c.prueba_diagnostica ='1', 'Prueba de diagnóstico','Código normal') as tipoCodigo,
            c.porcentaje_descuento,
            c.libro as book,c.serie,c.created_at,
            c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,c.bc_fecha_ingreso,
            c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,
            c.contrato,c.libro, c.venta_lista_institucion,
            CONCAT(u.nombres, ' ', u.apellidos) as estudiante, u.email,u.cedula, ib.nombreInstitucion as institucion_barras,
            i.nombreInstitucion, p.periodoescolar as periodo,pb.periodoescolar as periodo_barras,
            IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
            (case when (c.estado_liquidacion = '0') then 'liquidado'
                when (c.estado_liquidacion = '1') then 'sin liquidar'
                when (c.estado_liquidacion = '2') then 'codigo regalado'
                when (c.estado_liquidacion = '3') then 'codigo devuelto'
            end) as liquidacion,
            (case when (c.bc_estado = '2') then 'codigo leido'
            when (c.bc_estado = '1') then 'codigo sin leer'
            end) as barrasEstado,
            (case when (c.venta_estado = '0') then ''
                when (c.venta_estado = '1') then 'Venta directa'
                when (c.venta_estado = '2') then 'Venta por lista'
            end) as ventaEstado,
            ib.nombreInstitucion as institucionBarra, i.nombreInstitucion,
            p.periodoescolar as periodo,
            pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
            c.codigo_paquete,c.fecha_registro_paquete
            FROM codigoslibros c
            LEFT JOIN usuario u ON c.idusuario = u.idusuario
            LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
            LEFT JOIN periodoescolar p ON c.id_periodo = p.idperiodoescolar
            LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
            WHERE
                (
                c.bc_institucion = '$request->institucion_id'
                OR venta_lista_institucion = '$request->institucion_id'
                )
                AND c.bc_periodo = '$request->periodo_id'
                LIMIT 2000
            ");
            return $codigos;
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //gestionCodigos/{codigo}
    public function show($codigo)
    {
        $query     = CodigosLibros::Where('codigo','=',$codigo)->get();
        if(count($query) > 0){
            return $query[0];
        }else{
            return 0;
        }
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

    public function eliminarCode(Request $request){
        $old_values = CodigosLibros::findOrFail($request->codigo);
        $eliminar = DB::DELETE("DELETE FROM codigoslibros WHERE codigo = '$request->codigo'
        AND estado_liquidacion <> '0'
        ");
        if($eliminar){
            $usuario_editor = $request->idusuario;
            $comentario     = "Se elimino el codigo de la base de datos";
            //Guardar en el historico
            $this->GuardarEnHistorico($usuario_editor,$request->institucion_id,$request->periodo_id,$request->codigo,$usuario_editor,$comentario,$old_values,"");
            return ["status" => "1" ,"message" => "Se elimino correctamente"];
        }else{
            return ["status" => "0" ,"message" => "No se pudo eliminar el codigo puede que este liquidado"];
        }
    }
    public function destroy($id)
    {

    }
    public function guardarDescuentoCodigos(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //variables
        $valor = $request->valor;
        $codigos = json_decode($request->codigos);
        foreach($codigos as $key => $item){
            $codigo = CodigosLibros::findOrFail($item->codigo);
            $codigo->porcentaje_descuento   = $valor;
            $codigo->save();
        }
        return ["status" => "1", "message" => "Se guardo correctamente"];
    }
}
