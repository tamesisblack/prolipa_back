<?php

namespace App\Traits\Verificacion;
use DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
trait TraitVerificacionGeneral
{
    public function ObtenerRegalados($institucion,$periodo,$num_verificacion,$idverificacion){
        $getnumVerificacion = "verif".$num_verificacion;
        $query = DB::SELECT("SELECT ls.codigo_liquidacion AS codigo,  COUNT(ls.codigo_liquidacion) AS cantidad,
            c.serie,
            c.libro_idlibro,ls.nombre as nombrelibro,ls.id_serie,a.area_idarea,ls.year
            FROM codigoslibros c
            LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
            WHERE  c.estado_liquidacion = '2'
            AND c.bc_periodo            = ?
            AND( c.bc_institucion       = '$institucion' OR c.venta_lista_institucion = '$institucion')
            AND c.prueba_diagnostica    = '0'
            AND `$getnumVerificacion`   = ?
            GROUP BY ls.codigo_liquidacion,ls.nombre, c.serie,c.libro_idlibro,ls.id_libro_serie
        ",[$periodo,$idverificacion]);
        $datos = [];
        $contador = 0;
        foreach($query as $key => $item){
            //plan lector
            $precio = 0;
            $query = [];
            if($item->id_serie == 6){
                $query = DB::SELECT("SELECT f.pvp AS precio
                FROM pedidos_formato f
                WHERE f.id_serie    = '6'
                AND f.id_area       = '69'
                AND f.id_libro      = '$item->libro_idlibro'
                AND f.id_periodo    = '$periodo'");
            }else{
                // $query = DB::SELECT("SELECT f.pvp AS precio
                // FROM pedidos_formato f
                // WHERE f.id_serie    = '$item->id_serie'
                // AND f.id_area       = '$item->area_idarea'
                // AND f.id_periodo    = '$periodo'
                // ");
                $query = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro,
                (
                    SELECT f.pvp AS precio
                    FROM pedidos_formato f
                    WHERE f.id_serie = ls.id_serie
                    AND f.id_area = a.area_idarea
                    AND f.id_periodo = '$periodo'
                )as precio
                FROM libros_series ls
                LEFT JOIN libro l ON ls.idLibro = l.idlibro
                LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                WHERE ls.id_serie = '$item->id_serie'
                AND a.area_idarea  = '$item->area_idarea'
                AND l.Estado_idEstado = '1'
                AND a.estado = '1'
                AND ls.year = '$item->year'
                LIMIT 1
                ");
            }
            if(count($query) > 0){
                $precio = $query[0]->precio;
            }
            $datos[$contador] = (Object) [
                "codigo"            => $item->codigo,
                "cantidad"          => $item->cantidad,
                "serie"             => $item->serie,
                "libro_idlibro"     => $item->libro_idlibro,
                "nombre_libro"      => $item->nombrelibro,
                "id_serie"          => $item->id_serie,
                "area_idarea"       => $item->area_idarea,
                "precio"            => $precio,
                "valor"             => $item->cantidad * $precio
            ];
            $contador++;
        }
        return $datos;
    }
    public function obtenerAllRegalados($institucion,$periodo){
        $regalados = DB::SELECT("SELECT c.codigo, c.bc_fecha_ingreso, ls.codigo_liquidacion, l.nombrelibro,
            (
                SELECT
                    (case when (ci.verif1 > '0') then 'verif1'
                    when (ci.verif2 > 0) then 'verif2'
                    when (ci.verif3 > 0) then 'verif3'
                    when (ci.verif4 > 0) then 'verif4'
                    when (ci.verif5 > 0) then 'verif5'
                    when (ci.verif6 > 0) then 'verif6'
                    when (ci.verif7 > 0) then 'verif7'
                    when (ci.verif8 > 0) then 'verif8'
                    when (ci.verif9 > 0) then 'verif9'
                    when (ci.verif10 > 0) then 'verif10'
                    end) as verificacion
                FROM codigoslibros ci
                WHERE ci.codigo = c.codigo
            ) AS verificacionAsignada, c.quitar_de_reporte
            FROM codigoslibros c
            LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            WHERE
            (c.bc_institucion = '$institucion'  OR c.venta_lista_institucion = '$institucion')
            AND c.bc_periodo = '$periodo'
            AND c.estado_liquidacion = '2'
            AND c.prueba_diagnostica = '0'
            ");
        return $regalados;
    }
    public function obtenerAllRegaladosXVerificacion($institucion,$periodo,$num_verificacion,$verificacion_id){
        $verif          = "verif".$num_verificacion;
        $regalados = DB::SELECT("SELECT c.codigo, c.bc_fecha_ingreso, ls.codigo_liquidacion, l.nombrelibro,
            (
                SELECT
                    (case when (ci.verif1 > '0') then 'verif1'
                    when (ci.verif2 > 0) then 'verif2'
                    when (ci.verif3 > 0) then 'verif3'
                    when (ci.verif4 > 0) then 'verif4'
                    when (ci.verif5 > 0) then 'verif5'
                    when (ci.verif6 > 0) then 'verif6'
                    when (ci.verif7 > 0) then 'verif7'
                    when (ci.verif8 > 0) then 'verif8'
                    when (ci.verif9 > 0) then 'verif9'
                    when (ci.verif10 > 0) then 'verif10'
                    end) as verificacion
                FROM codigoslibros ci
                WHERE ci.codigo = c.codigo
            ) AS verificacionAsignada
            FROM codigoslibros c
            LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            WHERE
            (c.bc_institucion = '$institucion'  OR c.venta_lista_institucion = '$institucion')
            AND c.bc_periodo = '$periodo'
            AND c.estado_liquidacion = '2'
            AND c.prueba_diagnostica = '0'
            AND `$verif`                = '$verificacion_id'
            ");
        return $regalados;
    }
    public function obtenerVentaRealXVerificacionXTipoVenta($request){
        $periodo        = $request->periodo_id;
        $institucion    = $request->institucion_id;
        $verif          = "verif".$request->verificacion_id;
        $IdVerificacion = $request->IdVerificacion;
        $contrato       = $request->contrato;
        $tipoVenta      = $request->TipoVenta;
        $detalles = DB::SELECT("SELECT ls.codigo_liquidacion AS codigo,  COUNT(ls.codigo_liquidacion) AS cantidad, c.serie,
            c.libro_idlibro,l.nombrelibro as nombrelibro,ls.id_serie,a.area_idarea
            FROM codigoslibros c
            LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
            WHERE  c.estado_liquidacion = '0'
            AND c.bc_periodo            = ?
            AND c.bc_institucion        = ?
            AND c.prueba_diagnostica    = '0'
            AND `$verif`                = '$IdVerificacion'
            AND c.contrato              = '$contrato'
            AND c.venta_estado          = '$tipoVenta'
            GROUP BY ls.codigo_liquidacion,ls.nombre, c.serie,c.libro_idlibro
            ",
            [$periodo,$institucion]
        );
        $datos = [];
        $contador = 0;
        foreach($detalles as $key => $item){
            //plan lector
            $precio = 0;
            $query = [];
            if($item->id_serie == 6){
                $query = DB::SELECT("SELECT f.pvp AS precio
                FROM pedidos_formato f
                WHERE f.id_serie    = '6'
                AND f.id_area       = '69'
                AND f.id_libro      = '$item->libro_idlibro'
                AND f.id_periodo    = '$periodo'");
            }else{
                $query = DB::SELECT("SELECT f.pvp AS precio
                FROM pedidos_formato f
                WHERE f.id_serie    = '$item->id_serie'
                AND f.id_area       = '$item->area_idarea'
                AND f.id_periodo    = '$periodo'
                ");
            }
            if(count($query) > 0){
                $precio = $query[0]->precio;
            }
            $datos[$contador] = [
                "IdVerificacion"        => $IdVerificacion,
                "verificacion_id"       => $request->verificacion_id,
                "contrato"              => $contrato,
                "codigo"                => $item->codigo,
                "cantidad"              => $item->cantidad,
                "nombre_libro"          => $item->nombrelibro,
                "libro_id"              => $item->libro_idlibro,
                "id_serie"              => $item->id_serie,
                "id_periodo"            => $periodo,
                "precio"                => $precio,
                "valor"                 => $item->cantidad * $precio
            ];
            $contador++;
        }
        return $datos;
     }
    public function FormatoLibrosLiquidados($num_verificacion_id,$contrato,$periodo){
        $contador            = 0;
        $datos               = [];
        $detalles = DB::SELECT("SELECT vl.* ,ls.idLibro AS libro_id,
        ls.id_serie,t.id_periodo,a.area_idarea
        FROM verificaciones_has_temporadas vl
        LEFT JOIN libros_series ls ON vl.codigo = ls.codigo_liquidacion
        LEFT JOIN libro l ON ls.idLibro = l.idlibro
        LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
        LEFT JOIN temporadas t ON vl.contrato = t.contrato
        WHERE vl.verificacion_id = '$num_verificacion_id'
        AND vl.contrato          = '$contrato'
        AND vl.estado            = '1'
        AND nuevo                = '1'
        ",[$num_verificacion_id,$contrato]);
        foreach($detalles as $key => $item){
            //plan lector
            $precio = 0;
            $query = [];
            if($item->id_serie == 6){
                $query = DB::SELECT("SELECT f.pvp AS precio
                FROM pedidos_formato f
                WHERE f.id_serie    = '6'
                AND f.id_area       = '69'
                AND f.id_libro      = '$item->libro_id'
                AND f.id_periodo    = '$periodo'");
            }else{
                $query = DB::SELECT("SELECT f.pvp AS precio
                FROM pedidos_formato f
                WHERE f.id_serie    = '$item->id_serie'
                AND f.id_area       = '$item->area_idarea'
                AND f.id_periodo    = '$periodo'
                ");
            }
            if(count($query) > 0){
                $precio = $query[0]->precio;
            }
            $datos[$contador] = (Object)[
                "id_verificacion_inst"  => $item->id_verificacion_inst,
                "verificacion_id"       => $item->verificacion_id,
                "contrato"              => $contrato,
                "codigo"                => $item->codigo,
                "cantidad"              => $item->cantidad,
                "nombre_libro"          => $item->nombre_libro,
                "libro_id"              => $item->libro_id,
                "id_serie"              => $item->id_serie,
                "id_periodo"            => $periodo,
                "precio"                => $precio,
                "valor"                 => $item->cantidad * $precio,
                "descripcion"           => $item->descripcion,
                "cantidad_descontar"    => $item->cantidad_descontar,
                "porcentaje_descuento"  => $item->porcentaje_descuento,
                "total_descontar"       => $item->total_descontar,
                "tipo_calculo"          => $item->tipo_calculo,
            ];
            $contador++;
        }
        return $datos;
    }
    public function getPagosXInstitucionXPeriodo($institucion,$periodo){
        $query = DB::SELECT("SELECT lq.*
        FROM 1_4_documento_liq lq
        WHERE lq.institucion_id = '$institucion'
        AND lq.periodo_id       = '$periodo'
        AND (lq.doc_ci like '%ANT%' OR lq.doc_ci like '%LIQ%')
        AND lq.forma_pago_id > 0
        AND lq.estado = '1'
        ");
        $totalClientes          = 0;
        $totalProlipaAumentar   = 0;
        $totalProlipaDisminuir  = 0;
        foreach($query as $key => $item){
            if($item->tipo_pago_id == 2){
                //aumentar
                if($item->calculo == 1){
                    $totalProlipaAumentar  = $totalProlipaAumentar + $item->doc_valor;
                }
                //disminuir
                else{
                    $totalProlipaDisminuir = $totalProlipaDisminuir + $item->doc_valor;
                }
            }else{
                $totalClientes = $totalClientes + $item->doc_valor;
            }
        }
        return ["Pcliente"=>$totalClientes,"PProlipaAumentar"=>$totalProlipaAumentar,"PProlipaDisminuir" => $totalProlipaDisminuir];
    }

    //INICIO METODOS JEYSON
    public function obtenerVentaRealXVerificacionXTipoVenta_new($request){
        $periodo        = $request->periodo_id;
        $institucion    = $request->institucion_id;
        $verif          = "verif".$request->verificacion_id;
        $IdVerificacion = $request->IdVerificacion;
        $contrato       = $request->contrato;
        $tipoVenta      = $request->TipoVenta;
        $detalles = DB::SELECT("SELECT ls.codigo_liquidacion AS codigo,  COUNT(ls.codigo_liquidacion) AS cantidad, c.serie,
            c.libro_idlibro,l.nombrelibro as nombrelibro,ls.id_serie,a.area_idarea
            FROM codigoslibros c
            LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
            WHERE  c.estado_liquidacion = '0'
            AND c.bc_periodo            = ?
            AND c.bc_institucion        = ?
            AND c.prueba_diagnostica    = '0'
            AND `$verif`                = '$IdVerificacion'
            AND c.contrato              = '$contrato'
            AND c.venta_estado          = '$tipoVenta'
            GROUP BY ls.codigo_liquidacion,ls.nombre, c.serie,c.libro_idlibro
            ",
            [$periodo,$institucion]
        );
        $datos = [];
        $contador = 0;
        foreach($detalles as $key => $item){
            $pfn_pvp_result = (float) DB::table('pedidos_formato_new')
            ->where('idperiodoescolar', $periodo)
            ->where('idlibro', $item->libro_idlibro)
            ->value('pfn_pvp');
            $datos[$contador] = [
                "IdVerificacion"        => $IdVerificacion,
                "verificacion_id"       => $request->verificacion_id,
                "contrato"              => $contrato,
                "codigo"                => $item->codigo,
                "cantidad"              => $item->cantidad,
                "nombre_libro"          => $item->nombrelibro,
                "libro_id"              => $item->libro_idlibro,
                "id_serie"              => $item->id_serie,
                "id_periodo"            => $periodo,
                "precio"                => $pfn_pvp_result,
                "valor"                 => $item->cantidad * $pfn_pvp_result
            ];
            $contador++;
        }
        return $datos;
     }
    //FIN METODOS JEYSON
}
