<?php

namespace App\Http\Controllers;

use App\Models\_14ClienteInstitucion;
use DB;
use App\Http\Controllers\Controller;
use App\Models\Pedidos;
use App\Models\Temporada;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;

class _14ClienteInstitucionController extends Controller
{
    use TraitPedidosGeneral;
    public function Get_Cliente_Institucionx3yearanterior(Request $request){
        $numeroStr = substr($request->ven_codigo, 0); // Comienzas desde el tercer carácter para omitir "C-C" o "C-S"
        $numero = intval($numeroStr);

        $codigoAnteriorCosta1 = "C-C" . ($numero - 1);
        $codigoAnteriorCosta2 = "C-C" . ($numero - 2);
        $codigoAnteriorCosta3 = "C-C" . ($numero - 3);
        $codigoAnteriorSierra1 = "C-S" . ($numero - 1);
        $codigoAnteriorSierra2 = "C-S" . ($numero - 2);
        $codigoAnteriorSierra3 = "C-S" . ($numero - 3);

        $query = DB::SELECT("SELECT dq.ven_codigo, i.idInstitucion, i.direccionInstitucion, dq.estado_contrato, dq.doc_valor, dq.doc_ci, dq.doc_numero,
            dq.doc_codigo, dq.doc_numero, dq.doc_observacion, dq.doc_fecha,dq.tipo_pago_id,dq.forma_pago_id,dq.periodo_id,
            fp.tip_pag_nombre as forma_pago_nombre,tp.nombre as tipo_pago_nombre,
            CASE
            WHEN dq.ven_codigo LIKE '%C-C%' THEN CONCAT('C', SUBSTRING(dq.ven_codigo, LOCATE('C', dq.ven_codigo) + 3, 2))
            WHEN dq.ven_codigo LIKE '%C-S%' THEN CONCAT('S', SUBSTRING(dq.ven_codigo, LOCATE('C', dq.ven_codigo) + 3, 2))
            END AS PERIODO
            FROM 1_4_documento_liq dq
            LEFT JOIN institucion i ON dq.institucion_id = i.idInstitucion
            LEFT JOIN pedidos_tipo_pagos tp ON tp.id = dq.tipo_pago_id
            LEFT JOIN pedidos_formas_pago fp ON fp.tip_pag_codigo = dq.forma_pago_id
            WHERE dq.estado = 1
            AND (i.idInstitucion = '$request->id_institucion')
            AND (dq.ven_codigo LIKE '%$codigoAnteriorCosta1%' OR dq.ven_codigo LIKE '%$codigoAnteriorCosta2%' OR dq.ven_codigo LIKE '%$codigoAnteriorCosta3%'
            OR dq.ven_codigo LIKE '%$codigoAnteriorSierra1%' OR dq.ven_codigo LIKE '%$codigoAnteriorSierra2%' OR dq.ven_codigo LIKE '%$codigoAnteriorSierra3%')
            AND (dq.estado_contrato <> 3 AND dq.estado_contrato <> 0)
            AND (dq.doc_ci LIKE '%ANT%' OR dq.doc_ci LIKE '%LIQ%' OR dq.doc_ci > 0)
        ORDER BY PERIODO ASC");
        $datos = [];
        $contador = 0;
        foreach($query as $key => $item){
            //query en pedidos
            $ventaReal      = 0;
            $comisionReal   = 0;
            $ven_convertido = null;
            $query  = Pedidos::Where('contrato_generado',$item->ven_codigo)->get();
            //prolipa
            if(count($query) > 0){
                $pedido             = $this->getPedido(0,$query[0]->id_pedido);
                $comisionReal       = $pedido[0]->descuento;
                $verificaciones     = $this->getVerificaciones($item->ven_codigo);
                foreach($verificaciones as $key => $item2){
                    $ventaReal      = $ventaReal + $item2->venta_real;
                }
            }else{
                $query2         = DB::SELECT("SELECT * FROM 1_4_venta WHERE ven_codigo = '$item->ven_codigo'");
                if(!empty($query2)){
                    $ventaReal      = $query2[0]->ven_valor;
                    $comisionReal   = $query2[0]->ven_descuento;
                    $ven_convertido = $query2[0]->ven_convertido;
                }
            }
            $datos[$contador] = [
                "ven_codigo"            => $item->ven_codigo,
                "idInstitucion"         => $item->idInstitucion,
                "direccionInstitucion"  => $item->direccionInstitucion,
                "estado_contrato"       => $item->estado_contrato,
                "doc_valor"             => $item->doc_valor,
                "doc_ci"                => $item->doc_ci,
                "doc_numero"            => $item->doc_numero,
                "doc_codigo"            => $item->doc_codigo,
                "doc_observacion"       => $item->doc_observacion,
                "doc_fecha"             => $item->doc_fecha,
                "periodo"               => $item->PERIODO,
                "venValor"              => $ventaReal,
                "venDescuento"          => $comisionReal,
                "ven_convertido"        => $ven_convertido,
                "tipo_pago_id"          => $item->tipo_pago_id,
                "forma_pago_id"         => $item->forma_pago_id,
                "periodo_id"            => $item->periodo_id,
                "forma_pago_nombre"     => $item->forma_pago_nombre,
                "tipo_pago_nombre"      => $item->tipo_pago_nombre,
            ];
            $contador++;
        }

        return $datos;
    }

    public function Get_Cliente_Institucionx3pedidosanteriores(Request $request){
        $query = DB::SELECT("SELECT dq.ven_codigo, i.idInstitucion, i.direccionInstitucion, dq.estado_contrato, dq.doc_valor, dq.doc_ci, dq.doc_numero,
        dq.doc_codigo, dq.doc_numero, dq.doc_observacion, dq.doc_fecha,
        CASE
        WHEN dq.ven_codigo LIKE '%C-C%' THEN CONCAT('C', SUBSTRING(dq.ven_codigo, LOCATE('C', dq.ven_codigo) + 3, 2))
        WHEN dq.ven_codigo LIKE '%C-S%' THEN CONCAT('S', SUBSTRING(dq.ven_codigo, LOCATE('C', dq.ven_codigo) + 3, 2))
        END AS PERIODO
        FROM 1_4_documento_liq dq
        LEFT JOIN institucion i ON dq.institucion_id = i.idInstitucion
        WHERE dq.estado = 1
        AND (i.idInstitucion = '$request->id_institucion')
        AND (dq.estado_contrato <> 3 AND dq.estado_contrato <> '0')
        AND (dq.doc_ci LIKE '%ANT%' OR dq.doc_ci LIKE '%LIQ%')
        ORDER BY dq.ven_codigo DESC");
        //return $query;
        //defino un array para almacenar solo los ven_codigo
        $contrato = [];
        foreach ($query as $key => $item) {
           $contrato[$key] = [
            'ven_codigo' => $item->ven_codigo
           ];
        }
        //aqui me va a retornar solo 1 ven_codigo asi hayan dos repetidos del mismo periodo
        $resultado = array_unique($contrato, SORT_REGULAR);
        $renderSet = array_values($resultado);
        //aqui me hace el conteo de cuantos ven_codigo me retorna
        $renderSetcontar = count($resultado);
        //aqui valido si me trae 1, seteo en 00 las dos variables que restan, si me trae 2 seteo en 00 la una variable que me queda, 3 ó mas de 3 ya cojo todos los valores que me trae del JSON UNICAMENTES LOS 3 ULTIMOS
        if($renderSetcontar == 0){
            $valor1 = ['ven_codigo' => 'C-C00'];
            $valor2 = ['ven_codigo' => 'C-C00'];
            $valor3 = ['ven_codigo' => 'C-C00'];
        } else if($renderSetcontar == 1){
            $valor1 = $renderSet[0];
            $valor2 = ['ven_codigo' => 'C-C00'];
            $valor3 = ['ven_codigo' => 'C-C00'];
        } else if($renderSetcontar == 2){
            $valor1 = $renderSet[0];
            $valor2 = $renderSet[1];
            $valor3 = ['ven_codigo' => 'C-C00'];
        }else if($renderSetcontar >= 3){
            $valor1 = $renderSet[0];
            $valor2 = $renderSet[1];
            $valor3 = $renderSet[2];
        }
        //aqui cojo el valor de la varible que necesito
        $ven_codigo1 = $valor1["ven_codigo"];
        $ven_codigo2 = $valor2["ven_codigo"];
        $ven_codigo3 = $valor3["ven_codigo"];
        //aqui saca solo el numero del contrato que necesito desde la posicion 3 y solo recoge 2 valores
        $numero_deseado1 = substr($ven_codigo1, 3, 2);
        $numero_deseado2 = substr($ven_codigo2, 3, 2);
        $numero_deseado3 = substr($ven_codigo3, 3, 2);
        //estas son las variables concatenadas con los numeros del contrato de los 3 ultimos pedidos las cuales voy a mandar a buscar en mi consulta $query2 los ven_codigo encontrados para los pedidos
        $codigoAnteriorCosta1 = "C-C" . ($numero_deseado1);
        $codigoAnteriorCosta2 = "C-C" . ($numero_deseado2);
        $codigoAnteriorCosta3 = "C-C" . ($numero_deseado3);
        $codigoAnteriorSierra1 = "C-S" . ($numero_deseado1);
        $codigoAnteriorSierra2 = "C-S" . ($numero_deseado2);
        $codigoAnteriorSierra3 = "C-S" . ($numero_deseado3);

        $query2 = DB::SELECT("SELECT dq.ven_codigo, i.idInstitucion, i.direccionInstitucion, dq.estado_contrato, dq.doc_valor, dq.doc_ci, dq.doc_numero,
        dq.doc_codigo, dq.doc_numero, dq.doc_observacion, dq.doc_fecha,
        CASE
         WHEN dq.ven_codigo LIKE '%C-C%' THEN CONCAT('C', SUBSTRING(dq.ven_codigo, LOCATE('C', dq.ven_codigo) + 3, 2))
         WHEN dq.ven_codigo LIKE '%C-S%' THEN CONCAT('S', SUBSTRING(dq.ven_codigo, LOCATE('C', dq.ven_codigo) + 3, 2))
        END AS PERIODO
        FROM 1_4_documento_liq dq
        LEFT JOIN institucion i ON dq.institucion_id = i.idInstitucion
        WHERE dq.estado = 1
        AND (i.idInstitucion = '$request->id_institucion')
        AND (dq.ven_codigo LIKE '%$codigoAnteriorCosta1%' OR dq.ven_codigo LIKE '%$codigoAnteriorCosta2%' OR dq.ven_codigo LIKE '%$codigoAnteriorCosta3%'
        OR dq.ven_codigo LIKE '%$codigoAnteriorSierra1%' OR dq.ven_codigo LIKE '%$codigoAnteriorSierra2%' OR dq.ven_codigo LIKE '%$codigoAnteriorSierra3%')
        AND (dq.estado_contrato <> 3 AND dq.estado_contrato <> 0)
        AND (dq.doc_ci LIKE '%ANT%' OR dq.doc_ci LIKE '%LIQ%')
        ORDER BY PERIODO DESC");

        return $query2;
    }

    public function Getinsert_id_24_23(Request $request){
        $query = DB::SELECT("SELECT *, t.idInstitucion
        FROM 1_4_documento_liq dq
        LEFT JOIN temporadas t ON dq.ven_codigo = t.contrato
        WHERE dq.ven_codigo LIKE '%C-C24%' OR dq.ven_codigo LIKE '%C-C23%' OR dq.ven_codigo LIKE '%C-S23%'
        OR dq.ven_codigo LIKE '%C-S24%'
        AND t.estado = 1
        ");
        // return $query;
        $contador = 0;
        foreach($query as $key => $item){
            DB::table('1_4_documento_liq')
            ->where('doc_codigo', $item->doc_codigo)
            ->update([
                'institucion_id' => $item->idInstitucion
            ]);
            $contador++;
        }
        return "Se guardo $contador veces :v";
    }

    public function Getinsert_id_13_AL_22(Request $request){
        $query = DB::SELECT("SELECT *, i.idInstitucion FROM 1_4_documento_liq dq
        LEFT JOIN 1_4_venta v ON dq.ven_codigo = v.ven_codigo
        LEFT JOIN 1_4_cliente_institucion ci ON v.cli_ins_codigo = ci.cli_ins_codigo
        LEFT JOIN institucion i ON ci.ins_codigo = i.codigo_institucion_milton
        WHERE dq.ven_codigo LIKE '%C-C13%'
        OR dq.ven_codigo LIKE '%C-C14%'
        OR dq.ven_codigo LIKE '%C-C15%'
        OR dq.ven_codigo LIKE '%C-C16%'
        OR dq.ven_codigo LIKE '%C-C17%'
        OR dq.ven_codigo LIKE '%C-C18%'
        OR dq.ven_codigo LIKE '%C-C19%'
        OR dq.ven_codigo LIKE '%C-C20%'
        OR dq.ven_codigo LIKE '%C-C21%'
        OR dq.ven_codigo LIKE '%C-C22%'
        OR dq.ven_codigo LIKE '%C-S13%'
        OR dq.ven_codigo LIKE '%C-S14%'
        OR dq.ven_codigo LIKE '%C-S15%'
        OR dq.ven_codigo LIKE '%C-S16%'
        OR dq.ven_codigo LIKE '%C-S17%'
        OR dq.ven_codigo LIKE '%C-S18%'
        OR dq.ven_codigo LIKE '%C-S19%'
        OR dq.ven_codigo LIKE '%C-S20%'
        OR dq.ven_codigo LIKE '%C-S21%'
        OR dq.ven_codigo LIKE '%C-S22%'
        ");
        $contador = 0;
        foreach($query as $key => $item){
            DB::table('1_4_documento_liq')
            ->where('doc_codigo', $item->doc_codigo)
            ->update([
                'institucion_id' => $item->idInstitucion
            ]);
            $contador++;
        }
        return "Se guardo $contador veces :v";
    }

    public function Getinsert_estadocontrato(Request $request){
        $query = DB::SELECT("SELECT *, v.est_ven_codigo FROM 1_4_documento_liq dq
        LEFT JOIN 1_4_venta v ON dq.ven_codigo = v.ven_codigo
        WHERE (v.ven_codigo LIKE '%C-C13%'
        OR v.ven_codigo LIKE '%C-C14%'
        OR v.ven_codigo LIKE '%C-C15%'
        OR v.ven_codigo LIKE '%C-C16%'
        OR v.ven_codigo LIKE '%C-C17%'
        OR v.ven_codigo LIKE '%C-C18%'
        OR v.ven_codigo LIKE '%C-C19%'
        OR v.ven_codigo LIKE '%C-C20%'
        OR v.ven_codigo LIKE '%C-C21%'
        OR v.ven_codigo LIKE '%C-C22%'
        OR v.ven_codigo LIKE '%C-C23%'
        OR v.ven_codigo LIKE '%C-S13%'
        OR v.ven_codigo LIKE '%C-S14%'
        OR v.ven_codigo LIKE '%C-S15%'
        OR v.ven_codigo LIKE '%C-S16%'
        OR v.ven_codigo LIKE '%C-S17%'
        OR v.ven_codigo LIKE '%C-S18%'
        OR v.ven_codigo LIKE '%C-S19%'
        OR v.ven_codigo LIKE '%C-S20%'
        OR v.ven_codigo LIKE '%C-S21%'
        OR v.ven_codigo LIKE '%C-S22%'
        OR v.ven_codigo LIKE '%C-S23%')
       ");
        $contador = 0;
        foreach($query as $key => $item){
            DB::table('1_4_documento_liq')
            ->where('doc_codigo', $item->doc_codigo)
            ->update([
                'estado_contrato' => $item->est_ven_codigo
            ]);
            $contador++;
        }
        return "Se guardo $contador veces :v";
    }

    public function Getinsert_telefono_institucion(Request $request){
        $query = DB::SELECT("SELECT i.codigo_institucion_milton, fi.ins_codigo, i.telefonoInstitucion, fi.ins_telefono, i.* FROM institucion i
        LEFT JOIN 1_4_institucion fi ON i.codigo_institucion_milton = fi.ins_codigo
        WHERE (i.telefonoInstitucion IS NULL || i.telefonoInstitucion = '0') AND fi.ins_codigo != '22507';
       ");
        $contador = 0;
        foreach($query as $key => $item){
         
            DB::table('institucion')
            ->where('idInstitucion', $item->idInstitucion)
            ->update([
                'telefonoInstitucion' => $item->ins_telefono
            ]);
            $contador++;
        }
        return "Se guardo $contador veces :v";
    }

    public function Getinsert_ruc_institucion(Request $request){
        $query = DB::SELECT("SELECT i.codigo_institucion_milton, fi.ins_codigo, i.ruc, fi.ins_ruc, i.* FROM institucion i
        LEFT JOIN 1_4_institucion fi ON i.codigo_institucion_milton = fi.ins_codigo
        WHERE (i.ruc IS NULL || i.ruc = '0') AND fi.ins_codigo != 22507 AND fi.ins_ruc NOT LIKE '%nd%';
       ");
        $contador = 0;
        foreach($query as $key => $item){
            DB::table('institucion')
            ->where('idInstitucion', $item->idInstitucion)
            ->update([
                'ruc' => $item->ins_ruc
            ]);
            $contador++;
        }
        return "Se guardo $contador veces :v";
    }

    public function Getinsert_email_institucion(Request $request){
        $query = DB::SELECT("SELECT i.codigo_institucion_milton, fi.ins_codigo, i.email, fi.ins_mail, i.* FROM institucion i
        LEFT JOIN 1_4_institucion fi ON i.codigo_institucion_milton = fi.ins_codigo
        WHERE (i.email IS NULL || i.email = '0') AND fi.ins_codigo != '22507';
       ");
        $contador = 0;
        foreach($query as $key => $item){
            DB::table('institucion')
            ->where('idInstitucion', $item->idInstitucion)
            ->update([
                'email' => $item->ins_mail
            ]);
            $contador++;
        }
        return "Se guardo $contador veces :v";
    }
}
