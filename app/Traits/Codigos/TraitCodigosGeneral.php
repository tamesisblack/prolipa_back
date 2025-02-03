<?php
namespace App\Traits\Codigos;

use App\Models\CodigosLibrosDevolucionSon;
use DB;
use App\Models\HistoricoCodigos;
trait TraitCodigosGeneral{
    public function makeid($longitud){
        $characters = ['A','B','C','D','E','F','G','H','K','M','N','P','R','S','T','U','V','W','X','Y','Z','2','3','4','5','6','7','8','9'];
        shuffle($characters);
        $charactersLength = count($characters);
        $randomString = '';
        for ($i = 0; $i < $longitud; $i++) {
            $pos_rand = rand(0, ($charactersLength-1));
            $randomString .= $characters[$pos_rand];
        }
        return $randomString;
    }
    public function makeidNumbers($longitud){
        $characters = ['0','2','3','4','5','6','7','8','9'];
        shuffle($characters);
        $charactersLength = count($characters);
        $randomString = '';
        for ($i = 0; $i < $longitud; $i++) {
            $pos_rand = rand(0, ($charactersLength-1));
            $randomString .= $characters[$pos_rand];
        }
        return $randomString;
    }
    public function getCodigosVerificaciones($codigo){
        $consulta = DB::SELECT("SELECT
        c.venta_lista_institucion,
        c.codigos_barras,c.anio,c.serie, c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,contador,c.serie,
        c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,c.contrato,c.libro as book,c.libro_idlibro,
        CONCAT(u.nombres, ' ', u.apellidos) as estudiante, CONCAT(ucr.nombres, ' ', ucr.apellidos) as creador,
         u.email,u.cedula, ib.nombreInstitucion as institucion_barras,c.created_at,
        i.nombreInstitucion, p.periodoescolar as periodo,pb.periodoescolar as periodo_barras,c.bc_fecha_ingreso,
        c.verif1,c.verif2,c.verif3,c.verif4,c.verif5,c.verif6,c.verif7,c.verif8,c.verif9,c.verif10,
        IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
        (case when (c.estado_liquidacion = '0') then 'liquidado'
            when (c.estado_liquidacion = '1') then 'sin liquidar'
            when (c.estado_liquidacion = '2' AND c.liquidado_regalado = '0') then 'Regalado sin liquidar'
            when (c.estado_liquidacion = '2' AND c.liquidado_regalado = '1') then 'Regalado liquidado'
            when (c.estado_liquidacion = '3') then 'codigo devuelto'
        end) as liquidacion,
        (case when (c.bc_estado = '2') then 'codigo leido'
        when (c.bc_estado = '1') then 'codigo sin leer'
        end) as barrasEstado,
        (case when (c.codigos_barras = '1') then 'con código de barras'
            when (c.codigos_barras = '0')  then 'sin código de barras'
        end) as status,
        (case when (c.venta_estado = '0') then ''
            when (c.venta_estado = '1') then 'Venta directa'
            when (c.venta_estado = '2') then 'Venta por lista'
        end) as ventaEstado,ib.nombreInstitucion as institucionBarra,
        p.periodoescolar as periodo, pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
        c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
        IF(c.prueba_diagnostica ='1', 'Prueba de diagnóstico','Código normal') as tipoCodigo,
        c.porcentaje_descuento,  c.codigo_paquete,c.fecha_registro_paquete,c.liquidado_regalado,
        c.codigo_proforma,c.proforma_empresa, c.devuelto_proforma, ls.codigo_liquidacion,
        CONCAT(ase.nombres, ' ', ase.apellidos) as asesor, c.combo, c.codigo_combo, c.documento_devolucion, c.plus
        FROM codigoslibros c
        LEFT JOIN usuario u ON c.idusuario = u.idusuario
        LEFT JOIN usuario ucr ON c.idusuario_creador_codigo = ucr.idusuario
        LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
        LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
        LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
        LEFT JOIN periodoescolar p ON c.id_periodo = p.idperiodoescolar
        LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
        LEFT JOIN libros_series as ls ON ls.idLibro = c.libro_idlibro
        LEFT JOIN usuario as ase ON c.asesor_id = ase.idusuario
        WHERE c.codigo LIKE '%$codigo%'");
        return $consulta;
    }
    //conDevolucion => 1 si; 0 no;
    public function getCodigos($codigo,$conDevolucion,$busqueda = 0,$primerParametro=0,$segundoParametro=0){
        $resultado = DB::table('codigoslibros as c')
        ->select(DB::raw('c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
        IF(c.prueba_diagnostica ="1", "Prueba de diagnóstico","Código normal") as tipoCodigo,
        c.porcentaje_descuento,
        c.libro as book,c.serie,c.created_at,
        c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,c.bc_fecha_ingreso,
        c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,
        c.contrato,c.libro, c.venta_lista_institucion,
        CONCAT(u.nombres, " ", u.apellidos) as estudiante, u.email,u.cedula, ib.nombreInstitucion as institucion_barras,
        i.nombreInstitucion, p.periodoescolar as periodo,pb.periodoescolar as periodo_barras,
        IF(c.estado ="2", "bloqueado","activo") as codigoEstado,
        (case when (c.estado_liquidacion = "0") then "liquidado"
            when (c.estado_liquidacion = "1") then "sin liquidar"
            when (c.estado_liquidacion = "2" AND c.liquidado_regalado = "0") then "Regalado sin liquidar"
            when (c.estado_liquidacion = "2" AND c.liquidado_regalado = "1") then "Regalado liquidado"
            when (c.estado_liquidacion = "3") then "Código devuelto"
            when (c.estado_liquidacion = "4") then "Código Guia"
        end) as liquidacion,
        (case when (c.bc_estado = "2") then "codigo leido"
        when (c.bc_estado = "1") then "codigo sin leer"
        end) as barrasEstado,
        (case when (c.codigos_barras = "1") then "con código de barras"
            when (c.codigos_barras = "0")  then "sin código de barras"
        end) as status,
        (case when (c.venta_estado = "0") then ""
            when (c.venta_estado = "1") then "Venta directa"
            when (c.venta_estado = "2") then "Venta por lista"
        end) as ventaEstado,
        (
            SELECT
                (case when (ci.verif1 > "0") then "verif1"
                when (ci.verif2 > 0) then "verif2"
                when (ci.verif3 > 0) then "verif3"
                when (ci.verif4 > 0) then "verif4"
                when (ci.verif5 > 0) then "verif5"
                when (ci.verif6 > 0) then "verif6"
                when (ci.verif7 > 0) then "verif7"
                when (ci.verif8 > 0) then "verif8"
                when (ci.verif9 > 0) then "verif9"
                when (ci.verif10 > 0) then "verif10"
                end) as verificacion
            FROM codigoslibros ci
            WHERE ci.codigo = c.codigo
        ) AS verificacion,
        ib.nombreInstitucion as institucionBarra, i.nombreInstitucion,
        p.periodoescolar as periodo,
        pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
        c.codigo_paquete,c.fecha_registro_paquete,c.liquidado_regalado,c.codigo_proforma,c.proforma_empresa, c.devuelto_proforma,
        ls.codigo_liquidacion, CONCAT(ase.nombres, " ", ase.apellidos) as asesor, c.combo,c.codigo_combo,c.documento_devolucion, c.plus'
        ))
        ->leftJoin('usuario as  u',         'c.idusuario',                  'u.idusuario')
        ->leftJoin('usuario as  ase',       'c.asesor_id',                  'ase.idusuario')
        ->leftJoin('institucion  as ib',    'c.bc_institucion',             'ib.idInstitucion')
        ->leftJoin('institucion as  i',     'u.institucion_idInstitucion',  'i.idInstitucion')
        ->leftJoin('institucion  as ivl',   'c.venta_lista_institucion',    'ivl.idInstitucion')
        ->leftJoin('periodoescolar as  p',  'c.id_periodo',                 'p.idperiodoescolar')
        ->leftJoin('periodoescolar as pb',  'c.bc_periodo',                 'pb.idperiodoescolar')
        ->leftJoin('libros_series as ls',   'ls.idLibro',                   'c.libro_idlibro');
        //por codigo
        if ($busqueda == 0) {  $resultado->where('c.codigo', '=', $codigo); }
        //por like codigo
        if ($busqueda == 1) {  $resultado->where('c.codigo', 'like', '%' . $codigo . '%'); }
        //por contador
        if ($busqueda == 2) {  $resultado->where('c.libro_idlibro', '=', $primerParametro)->where('contador','=',$segundoParametro); }
        //por paquete solo codigos de activacion
        if($busqueda == 3) {  $resultado->where('c.codigo_paquete', '=', $codigo)->where('prueba_diagnostica','0'); }
        //todos los codigos de un paquete
        if($busqueda == 4) {  $resultado->where('c.codigo_paquete', '=', $codigo); }
        //por combo solo codigos de activacion
        if($busqueda == 5) {  $resultado->where('c.codigo_combo', '=', $codigo)->where('prueba_diagnostica','0'); }
        //todos los codigos de un combo
        if($busqueda == 6) {  $resultado->where('c.codigo_combo', '=', $codigo); }
        $consulta = $resultado->get();
        if(empty($consulta)){
            return $consulta;
        }
        $datos = [];
        foreach($consulta as $key => $item){
            $devolucionInstitucion = "";
            //conDevolucion => 1 si; 0 no;
            if($conDevolucion == 1){
                //ULTIMA INSTITUCION
                $query = DB::SELECT("SELECT CONCAT(' Cliente: ', d.cliente  , ' - ',d.fecha_devolucion) AS devolucion
                FROM codigos_devolucion d
                WHERE d.codigo = '$item->codigo'
                AND d.estado = '1'
                ORDER BY d.id DESC
                LIMIT 1");
                if(count($query) > 0){
                $devolucionInstitucion =  $query[0]->devolucion;
                }
            }
            $datos[$key] = (Object)[
                "codigo"                        => $item->codigo,
                "InstitucionLista"              => $item->InstitucionLista,
                "barrasEstado"                  => $item->barrasEstado,
                "bc_estado"                     => $item->bc_estado,
                "bc_fecha_ingreso"              => $item->bc_fecha_ingreso,
                "bc_institucion"                => $item->bc_institucion,
                "bc_periodo"                    => $item->bc_periodo,
                "book"                          => $item->book,
                "cedula"                        => $item->cedula,
                "codigoEstado"                  => $item->codigoEstado,
                "contador"                      => $item->contador,
                "contrato"                      => $item->contrato,
                "created_at"                    => $item->created_at,
                "devolucionInstitucion"         => $devolucionInstitucion,
                "email"                         => $item->email,
                "estado"                        => $item->estado,
                "estado_liquidacion"            => $item->estado_liquidacion,
                "estudiante"                    => $item->estudiante,
                "factura"                       => $item->factura,
                "id_periodo"                    => $item->id_periodo,
                "idusuario"                     => $item->idusuario,
                "institucionBarra"              => $item->institucionBarra,
                "institucion_barras"            => $item->institucion_barras,
                "libro"                         => $item->libro,
                "liquidacion"                   => $item->liquidacion,
                "nombreInstitucion"             => $item->nombreInstitucion,
                "periodo"                       => $item->periodo,
                "periodo_barras"                => $item->periodo_barras,
                "porcentaje_descuento"          => $item->porcentaje_descuento,
                "prueba_diagnostica"            => $item->prueba_diagnostica,
                "serie"                         => $item->serie,
                "status"                        => $item->status,
                "tipoCodigo"                    => $item->tipoCodigo,
                "ventaEstado"                   => $item->ventaEstado,
                "venta_estado"                  => $item->venta_estado,
                "venta_lista_institucion"       => $item->venta_lista_institucion,
                "codigo_union"                  => strtoupper($item->codigo_union),
                "codigo_paquete"                => strtoupper($item->codigo_paquete),
                "fecha_registro_paquete"        => $item->fecha_registro_paquete,
                "verificacion"                  => $item->verificacion,
                "liquidado_regalado"            => $item->liquidado_regalado,
                "codigo_proforma"               => $item->codigo_proforma,
                "proforma_empresa"              => $item->proforma_empresa,
                "codigo_liquidacion"            => $item->codigo_liquidacion,
                "asesor"                        => $item->asesor,
                'combo'                         => $item->combo,
                'codigo_combo'                  => $item->codigo_combo,
                'documento_devolucion'          => $item->documento_devolucion,
                'devuelto_proforma'             => $item->devuelto_proforma,
                'plus'                          => $item->plus
            ];
        }
        return $datos;
    }
    public function getCodigosXDocumento($factura){
        $consulta = DB::SELECT("SELECT c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
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
                when (c.estado_liquidacion = '2' AND c.liquidado_regalado = '0') then 'Regalado sin liquidar'
                when (c.estado_liquidacion = '2' AND c.liquidado_regalado = '1') then 'Regalado liquidado'
                when (c.estado_liquidacion = '3') then 'codigo devuelto'
            end) as liquidacion,
            (case when (c.bc_estado = '2') then 'codigo leido'
            when (c.bc_estado = '1') then 'codigo sin leer'
            end) as barrasEstado,
            (case when (c.codigos_barras = '1') then 'con código de barras'
                when (c.codigos_barras = '0')  then 'sin código de barras'
            end) as status,
            (case when (c.venta_estado = '0') then ''
                when (c.venta_estado = '1') then 'Venta directa'
                when (c.venta_estado = '2') then 'Venta por lista'
            end) as ventaEstado,
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
            ) AS verificacion,
            ib.nombreInstitucion as institucionBarra, i.nombreInstitucion,
            p.periodoescolar as periodo,
            pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista,
            c.codigo_paquete,c.fecha_registro_paquete,c.liquidado_regalado
            FROM codigoslibros c
            LEFT JOIN usuario u ON c.idusuario = u.idusuario
            LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
            LEFT JOIN periodoescolar p ON c.id_periodo = p.idperiodoescolar
            LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
            WHERE factura = '$factura'
            AND c.prueba_diagnostica ='0'
        ");
        if(empty($consulta)){
            return $consulta;
        }
        $datos = [];
        foreach($consulta as $key => $item){
            $devolucionInstitucion = "";
            $datos[$key] = (Object)[
                "codigo"                        => $item->codigo,
                "InstitucionLista"              => $item->InstitucionLista,
                "barrasEstado"                  => $item->barrasEstado,
                "bc_estado"                     => $item->bc_estado,
                "bc_fecha_ingreso"              => $item->bc_fecha_ingreso,
                "bc_institucion"                => $item->bc_institucion,
                "bc_periodo"                    => $item->bc_periodo,
                "book"                          => $item->book,
                "cedula"                        => $item->cedula,
                "codigoEstado"                  => $item->codigoEstado,
                "contador"                      => $item->contador,
                "contrato"                      => $item->contrato,
                "created_at"                    => $item->created_at,
                "devolucionInstitucion"         => $devolucionInstitucion,
                "email"                         => $item->email,
                "estado"                        => $item->estado,
                "estado_liquidacion"            => $item->estado_liquidacion,
                "estudiante"                    => $item->estudiante,
                "factura"                       => $item->factura,
                "id_periodo"                    => $item->id_periodo,
                "idusuario"                     => $item->idusuario,
                "institucionBarra"              => $item->institucionBarra,
                "institucion_barras"            => $item->institucion_barras,
                "libro"                         => $item->libro,
                "liquidacion"                   => $item->liquidacion,
                "nombreInstitucion"             => $item->nombreInstitucion,
                "periodo"                       => $item->periodo,
                "periodo_barras"                => $item->periodo_barras,
                "porcentaje_descuento"          => $item->porcentaje_descuento,
                "prueba_diagnostica"            => $item->prueba_diagnostica,
                "serie"                         => $item->serie,
                "status"                        => $item->status,
                "tipoCodigo"                    => $item->tipoCodigo,
                "ventaEstado"                   => $item->ventaEstado,
                "venta_estado"                  => $item->venta_estado,
                "venta_lista_institucion"       => $item->venta_lista_institucion,
                "codigo_union"                  => $item->codigo_union,
                "codigo_paquete"                => $item->codigo_paquete,
                "fecha_registro_paquete"        => $item->fecha_registro_paquete,
                "verificacion"                  => $item->verificacion,
                "liquidado_regalado"            => $item->liquidado_regalado
            ];
        }
        return $datos;
    }
    public function obtenerFacturasLike($factura){
        $query = DB::SELECT("SELECT distinct factura FROM codigoslibros c
        WHERE c.factura LIKE '%$factura%'
        ");
        return $query;
    }
    public function obtenerPreFacturasLike($factura){
        $query = DB::SELECT("SELECT distinct factura FROM codigoslibros c
        WHERE c.codigo_proforma LIKE '%$factura%'
        ");
        return $query;
    }
    public function GuardarEnHistorico ($id_usuario,$institucion_id,$periodo_id,$codigo,$usuario_editor,$comentario,$old_values,$new_values,$devueltos_liquidados=null,$verificacion_liquidada=null){
        $historico = new HistoricoCodigos();
        $historico->id_usuario              =  $id_usuario;
        $historico->usuario_editor          =  $institucion_id;
        $historico->id_periodo              =  $periodo_id;
        $historico->codigo_libro            =  $codigo;
        $historico->idInstitucion           =  $usuario_editor;
        $historico->observacion             =  $comentario;
        $historico->old_values              =  $old_values;
        $historico->new_values              =  $new_values;
        $historico->devueltos_liquidados    = $devueltos_liquidados;
        $historico->verificacion_liquidada  = $verificacion_liquidada;
        $historico->save();
        return "Guardado en historico";
    }
    public function GuardarEnHistoricoTablaSon ($id_usuario,$institucion_id,$periodo_id,$codigo,$usuario_editor,$comentario,$old_values,$new_values,$devueltos_liquidados=null,$verificacion_liquidada=null){
        $historico = new HistoricoCodigos();
        $historico->id_usuario              =  $id_usuario;
        $historico->usuario_editor          =  $institucion_id;
        $historico->id_periodo              =  $periodo_id;
        $historico->codigo_libro            =  $codigo;
        $historico->idInstitucion           =  $usuario_editor;
        $historico->observacion             =  $comentario;
        $historico->old_values              =  $old_values;
        $historico->new_values              =  $new_values;
        $historico->devueltos_liquidados    = $devueltos_liquidados;
        $historico->verificacion_liquidada  = $verificacion_liquidada;
        $historico->tipo_tabla              = 1;
        $historico->save();
        return "Guardado en historico";
    }
    public function tr_GuardarDevolucionHijos($id_devolucion,$codigo,$pro_codigo,$id_cliente,$combo,$factura,$documento,$id_empresa,$tipo_venta,$id_periodo,$prueba_diagnostico,$codigo_union,$id_libro,$codigo_paquete,$estado_liquidacion,$regalado_liquidado,$precio,$tipo_importacion,$estado_codigo,$codigo_combo){
        //validar que si el codigo ya existe no guardar
        $validate = CodigosLibrosDevolucionSon::where('codigo',$codigo)->where('id_cliente',$id_cliente)->where('codigoslibros_devolucion_id',$id_devolucion)->first();
        if($validate){
            return "Codigo ya existe";
        }else{
            $devolucionH                                = new CodigosLibrosDevolucionSon();
            $devolucionH->codigoslibros_devolucion_id   = $id_devolucion;
            $devolucionH->codigo                        = $codigo;
            $devolucionH->pro_codigo                    = $pro_codigo;
            $devolucionH->id_cliente                    = $id_cliente;
            $devolucionH->combo                         = $combo;
            $devolucionH->factura                       = $factura;
            $devolucionH->documento                     = $documento;
            $devolucionH->id_empresa                    = $id_empresa;
            $devolucionH->tipo_venta                    = $tipo_venta;
            $devolucionH->id_periodo                    = $id_periodo;
            $devolucionH->prueba_diagnostico            = $prueba_diagnostico;
            $devolucionH->codigo_union                  = $codigo_union;
            $devolucionH->codigo_paquete                = $codigo_paquete;
            $devolucionH->id_libro                      = $id_libro;
            $devolucionH->documento_estado_liquidacion  = $estado_liquidacion;
            $devolucionH->documento_regalado_liquidado  = $regalado_liquidado;
            $devolucionH->precio                        = $precio;
            $devolucionH->tipo_importacion              = $tipo_importacion;
            $devolucionH->estado_codigo                 = $estado_codigo;
            $devolucionH->codigo_combo                  = $codigo_combo;
            $devolucionH->save();
            return "Guardado en devolucion hijo";
        }
    }
    public function tr_updateDevolucionHijos($codigo,$estado,$id_devolucion){
        $getCodigo = CodigosLibrosDevolucionSon::where('codigo',$codigo)
        ->where('codigoslibros_devolucion_id',$id_devolucion)
        ->first();
        if($getCodigo){
            $getCodigo->estado = $estado;
            $getCodigo->save();
            return "Actualizado";
        }
        return "No se encontro el codigo";
    }
    public function PeriodoInstitucion($institucion){
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo ,
            periodoescolar AS descripcion,region_idregion as region,estado
            FROM periodoescolar
            WHERE idperiodoescolar = (
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$institucion'))
        ");
        return $periodoInstitucion;
    }
}
?>
