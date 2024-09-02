<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use App\Models\HistoricoCodigos;
use App\Models\Pedidos;
use App\Models\Temporada;
use App\Models\TemporadaVerificacionHistorico;
use App\Models\Verificacion;
use App\Models\VerificacionHasInstitucion;
use App\Models\VerificacionHistoricoCambios;
use App\Repositories\pedidos\VerificacionRepository;
use App\Traits\Pedidos\TraitPedidosGeneral;
use App\Traits\Verificacion\TraitVerificacionGeneral;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Cache;

class VerificacionControllerAnterior extends Controller
{

    use TraitPedidosGeneral;
    use TraitVerificacionGeneral;
    public $verificacionRepository;
    //contructor
    public function __construct(VerificacionRepository $verificacionRepository)
    {
        $this->verificacionRepository = $verificacionRepository;
    }
    //PARA TRAER EL CONTRATO POR NUMERO DE VERIFICACION
    public function liquidacionVerificacionNumero($contrato,$numero){
        if($numero == "regalados"){
            return $this->CodigosRegalos($contrato);
        }
        //validar si el contrato esta activo
        $validarContrato = DB::select("SELECT t.*
        FROM temporadas t
        WHERE t.contrato = '$contrato'
        and t.estado = '0'
        ");
        if(count($validarContrato) > 0){
            return ["status"=>"0", "message" => "El contrato esta inactivo"];
        }
            $traerInformacion = DB::select(" SELECT   vt.verificacion_id as numero_verificacion,vt.contrato,vt.codigo,vt.cantidad,
            vt.nombre_libro, v.fecha_inicio, v.fecha_fin, vt.contrato
            FROM verificaciones v
            LEFT JOIN verificaciones_has_temporadas vt ON v.num_verificacion = vt.verificacion_id
            WHERE v.num_verificacion ='$numero'
            AND v.contrato = '$contrato'
            AND vt.verificacion_id = '$numero'
            AND vt.contrato = '$contrato'
            and vt.estado = '1'
            and v.nuevo = '1'
            and vt.nuevo = '1'
            ORDER BY vt.verificacion_id desc
            ");

            if(empty($traerInformacion)){
                return ["status"=>"0","message"=>"No se encontro datos para este  contrato"];
            }else{
                return $traerInformacion;
            }
    }
    //para codigo regalados
    public function CodigosRegalos($contrato){
        $regalados = DB::SELECT("SELECT ls.codigo_liquidacion AS codigo,  COUNT(ls.codigo_liquidacion) AS cantidad, c.serie,
        c.libro_idlibro,ls.nombre as nombrelibro
            FROM codigoslibros c
            LEFT JOIN usuario u ON c.idusuario = u.idusuario
            LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
            WHERE c.estado_liquidacion = '2'
               AND c.contrato = '$contrato'
               AND c.prueba_diagnostica    = '0'
           AND ls.idLibro = c.libro_idlibro
           GROUP BY ls.codigo_liquidacion,ls.nombre, c.serie,c.libro_idlibro
        ");
        if(empty($regalados)){
            return ["status" => "0","message" => "No hay codigos regalados"];
        }else{
            return $regalados;
        }

    }
    public function getCodigosGrupalLiquidar($institucion,$periodo){
        $data = DB::SELECT("SELECT ls.codigo_liquidacion AS codigo,  COUNT(ls.codigo_liquidacion) AS cantidad, c.serie,
        c.libro_idlibro,ls.nombre as nombrelibro
            FROM codigoslibros c
            LEFT JOIN usuario u ON c.idusuario = u.idusuario
            LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
            WHERE c.bc_estado = '2'
               AND c.estado <> 2
               and c.estado_liquidacion = '1'
               AND c.bc_periodo  = '$periodo'
               AND c.bc_institucion = '$institucion'
               AND c.prueba_diagnostica = '0'
           AND ls.idLibro = c.libro_idlibro
           GROUP BY ls.codigo_liquidacion,ls.nombre, c.serie,c.libro_idlibro
        ");
        return $data;
    }
    public function getCodigosIndividualLiquidar($institucion,$periodo){
        $traerCodigosIndividual = DB::SELECT("SELECT c.codigo, ls.codigo_liquidacion,   c.serie,
            c.libro_idlibro,c.libro as nombrelibro
        FROM codigoslibros c
        LEFT JOIN usuario u ON c.idusuario = u.idusuario
        LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
        WHERE c.bc_estado = '2'
        AND c.estado <> 2
        and c.estado_liquidacion = '1'
        AND c.bc_periodo  = '$periodo'
        AND c.bc_institucion = '$institucion'
        AND ls.idLibro = c.libro_idlibro
        LIMIT 2000
        ");
        return $traerCodigosIndividual;
    }
    public function getRegaladosXLiquidar($institucion,$periodo){
        $query = DB::SELECT("SELECT c.codigo
        FROM codigoslibros c
        WHERE  c.estado_liquidacion = '2'
        AND (c.bc_institucion       = '$institucion' OR c.venta_lista_institucion = '$institucion')
        AND c.prueba_diagnostica    = '0'
        AND c.bc_periodo            = '$periodo'
        AND c.liquidado_regalado    = '0'
        ");
        return $query;
    }
    public function getVerificacionXcontrato($contrato){
        $query = DB::SELECT("SELECT
            v.* FROM verificaciones v
            WHERE v.contrato = '$contrato'
            AND v.nuevo = '1'
            ORDER BY v.id DESC
        ");
        $datos = [];
        $contador = 0;
        foreach($query as $key => $item){
           $datos [$contador] = (Object)[
            "id"                            => $item->id,
            "num_verificacion"              => $item->num_verificacion,
            "fecha_inicio"                  => $item->fecha_inicio,
            "fecha_fin"                     => $item->fecha_fin,
            "estado"                        => $item->estado,
            "contrato"                      => $item->contrato,
            "nuevo"                         => $item->nuevo,
            "file_evidencia"                => $item->file_evidencia,
            "observacion"                   => $item->observacion,
            "valor_liquidacion"             => $item->valor_liquidacion,
            "fecha_subir_evidencia"         => $item->fecha_subir_evidencia,
            "cobro_venta_directa"           => $item->cobro_venta_directa,
            "tipoPago"                      => $item->tipoPago,
            "personalizado"                 => $item->personalizado,
            "totalDescuento"                => $item->totalDescuento == null ? 0 : $item->totalDescuento,
            "abonado"                       => $item->abonado,
            "estado_pago"                   => $item->estado_pago,
            "venta_real"                    => $item->venta_real,
            "venta_real_regalados"          => $item->venta_real_regalados,
            "valor_comision"                => $item->valor_comision,
            "totalCobroVentaDirecta"        => $item->totalCobroVentaDirecta,
            "TotalConvenio"                 => $item->TotalConvenio,
            "permiso_anticipo_deuda"        => $item->permiso_anticipo_deuda,
            "permiso_convenio"              => $item->permiso_convenio,
            "permiso_cobro_venta_directa"   => $item->permiso_cobro_venta_directa,
            "fecha_subir_factura"           => $item->fecha_subir_factura,
            "file_factura"                  => $item->file_factura,
           ];
           $contador++;
        }
        return $datos;
    }
    public function getDocumentosLiqSinContratos($institucion,$periodo){
        $pagos = $this->getPagosXInstitucionXPeriodo($institucion,$periodo);
        $datos = [];
        $contador = 0;
           $datos [$contador] = (Object)[
            // "totalPagado"                   => $item->totalPagado,
            "Pcliente"                      => $pagos["Pcliente"],
            "PProlipaAumentar"              => $pagos["PProlipaAumentar"],
            "PProlipaDisminuir"             => $pagos["PProlipaDisminuir"],
            "estado"                        => 0,
           ];
           $contador++;

        return $datos;
    }
    //api:get/n_verificacion?getHistoricoVerificacionesCambios=1&id_verificacion=1873
    public function getHistoricoVerificacionesCambios($request){
        $id_verificacion = $request->id_verificacion;
        $query = VerificacionHistoricoCambios::where('verificacion_id', $id_verificacion)
            ->leftJoin('usuario as u', 'u.idusuario', '=', 'verificaciones_historico_cambios.user_created')
            ->select('verificaciones_historico_cambios.*', DB::raw('CONCAT(u.nombres, " ", u.apellidos) as editor'))
            ->orderBy('verificaciones_historico_cambios.id', 'desc')
            ->get();

        return $query;
    }
    public function saveVerificacion($num_verificacion,$contrato){
        $fechaActual  = date('Y-m-d');
        $verificacion =  new Verificacion;
        $verificacion->num_verificacion = $num_verificacion;
        $verificacion->fecha_inicio     = $fechaActual;
        $verificacion->contrato         = $contrato;
        $verificacion->nuevo            = "1";
        $verificacion->estado           = 1;
        $verificacion->save();
        return $verificacion;
    }
    public function guardarLiquidacion($data,$traerNumeroVerificacionInicial,$traerContrato){
        //Ingresar la liquidacion
        foreach($data as $item){
            VerificacionHasInstitucion::create([
                'verificacion_id' => $traerNumeroVerificacionInicial,
                'contrato' => $traerContrato,
                'codigo' => $item->codigo,
                'cantidad' => $item->cantidad,
                'nombre_libro' => $item->nombrelibro,
                'estado' => '1',
                'nuevo' => '1'
            ]);

        }
     }
     public function guardarLiquidacionCodigosDemasiados($data,$traerNumeroVerificacionInicial,$traerContrato){
        //Ingresar la liquidacion
        foreach($data as $item){

            //validar si es el mismo verificacion_id, contrato, codigo trear la cantidad y sumar la cantidad
            $query  = VerificacionHasInstitucion::where('verificacion_id',$traerNumeroVerificacionInicial)
            ->where('contrato',$traerContrato)
            ->where('codigo',$item->codigo)
            ->get();
            if(count($query) > 0){
                $cantidad = $query[0]->cantidad + $item->cantidad;
                VerificacionHasInstitucion::where('verificacion_id',$traerNumeroVerificacionInicial)
                ->where('contrato',$traerContrato)
                ->where('codigo',$item->codigo)
                ->update([
                    'cantidad' => $cantidad
                ]);
            }else{
                VerificacionHasInstitucion::create([
                    'verificacion_id' => $traerNumeroVerificacionInicial,
                    'contrato' => $traerContrato,
                    'codigo' => $item->codigo,
                    'cantidad' => $item->cantidad,
                    'nombre_libro' => $item->nombrelibro,
                    'estado' => '1',
                    'nuevo' => '1'
                ]);
            }
        }
     }
     public function updateCodigoIndividualInicial($traerNumeroVerificacionInicialId,$traerCodigosIndividual,$contrato,$num_verificacion,$periodo,$institucion,$observacion,$user_created = 0){
        $columnaVerificacion = "verif".$num_verificacion;
        //PARA RECORRER Y IR ACTUALIZANDO A CADA CODIGO LA VERIFICACION
        $datos =[];
        $mensaje = "";
        if($observacion == "regalado"){
            $mensaje = "liquidacion regalado";
            $datos =  [
                $columnaVerificacion =>  $traerNumeroVerificacionInicialId,
                'liquidado_regalado' => "1",
                'contrato' => $contrato,
            ];
        }else{
            $mensaje = "liquidacion";
            $datos = [
                $columnaVerificacion =>  $traerNumeroVerificacionInicialId,
                'estado_liquidacion' => "0",
                'contrato'           => $contrato,
            ];
        }
        foreach($traerCodigosIndividual as $item){
           $ingresar =  DB::table('codigoslibros')
            ->where('codigo', $item->codigo)
            ->update($datos);
            if($ingresar){
                $historico                  = new HistoricoCodigos();
                $historico->id_usuario      = "0";
                $historico->idInstitucion   = $user_created;
                $historico->codigo_libro    = $item->codigo;
                $historico->usuario_editor  = $institucion;
                $historico->id_periodo      = $periodo;
                $historico->observacion     = $mensaje;
                $historico->contrato_actual = $contrato;
                $historico->save();
            }

        }
     }
     //API:GET/n_verificacion
     public function index(Request $request)
     {
         set_time_limit(60000);
         ini_set('max_execution_time', 60000);
         //PARA VER EL CONTRATO POR CODIGO
         if($request->id){
             $buscarContrato = DB::select("SELECT
             v.* from verificaciones v
             WHERE v.id = '$request->id'
             ");
              if(empty($buscarContrato)){
                 return ["status"=>"0","message"=>"No se encontro datos para este contrato"];
             }else{
                 return $buscarContrato;
             }
         }
         //PARA VER LA INFORMACION DE LAS VERIFICACIONES DEL CONTRATO
         if($request->informacion){
            $verificaciones = $this->getVerificacionXcontrato($request->contrato);
            $institucion = DB::SELECT("SELECT t.*, i.nombreInstitucion
            FROM temporadas t
            LEFT JOIN institucion i ON i.idInstitucion = t.idInstitucion
            WHERE t.contrato = '$request->contrato'
            ");
            return ["verificaciones" => $verificaciones, "institucion" => $institucion];
         }
         //para ver el historico de contrato liquidacion
         if($request->historico){
            return $this->historicoContrato($request);
         }
         //para calcular la venta real
         if($request->getVentaRealXVerificacion){
            return $this->getVentaRealXVerificacion($request);
         }
         //para traer todos los codigos
         if($request->getAllCodigosXContrato){
            return $this->getAllCodigosXContrato($request);
         }
          //para calcular la venta real x Tipo de venta
         if($request->getVentaRealXVerificacionXTipoVenta){
            return $this->obtenerVentaRealXVerificacionXTipoVenta($request);
         }
         //para traer todos los codigos individuales por contrato
            if($request->getAllCodigosIndividualesContrato){
                return $this->getAllCodigosIndividualesContrato($request);
            }
         //para traer los detalle de cada verificacion
         if($request->detalles){
            Cache::flush();
            $periodo        = $request->periodo_id;
            $institucion    = $request->institucion_id;
            $verif          = "verif".$request->verificacion_id;
            $IdVerificacion = $request->IdVerificacion;
            $contrato       = $request->contrato;
            $detalles = DB::SELECT("SELECT ls.codigo_liquidacion AS codigo,  COUNT(ls.codigo_liquidacion) AS cantidad, c.serie,
                c.libro_idlibro,l.nombrelibro as nombrelibro,ls.id_serie,a.area_idarea
                FROM codigoslibros c
                LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
                LEFT JOIN libro l ON ls.idLibro = l.idlibro
                LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
                WHERE  (c.estado_liquidacion = '0' OR c.estado_liquidacion = '2')
                AND c.bc_periodo            = ?
                AND (c.bc_institucion       = '$institucion' OR c.venta_lista_institucion = '$institucion')
                AND c.prueba_diagnostica    = '0'
                AND `$verif`                = '$IdVerificacion'
                -- AND c.contrato              = '$contrato'
                GROUP BY ls.codigo_liquidacion,ls.nombre, c.serie,c.libro_idlibro
                ",
                [$periodo]
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
         //para ver los codigos de cada libro
         if($request->verCodigos){
            $verificacion_id        = 0;
            $columnaVerificacion    = 0;
            if($request->buscarIdVerificacion){
                $getId = DB::SELECT("SELECT * FROM verificaciones v
                WHERE v.contrato = '$request->contrato'
                AND v.nuevo = '1'
                AND v.num_verificacion = '$request->num_verificacion'
                ");
                $verificacion_id = $getId[0]->id;
            }else{
                $verificacion_id =  $request->verificacion_id;
            }
            $columnaVerificacion = "verif".$request->num_verificacion;
             $codigos = DB::table('codigoslibros as c')
            //  ->select('codigo','estado_liquidacion','venta_estado','factura')
            ->select(DB::RAW('
                c.codigo,c.estado_liquidacion,c.venta_estado,c.factura,c.contrato,
                c.libro_idlibro, c.libro as nombre_libro,
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
                c.verif1,c.verif2,c.verif3,c.verif4,c.verif5,c.verif6,c.verif7,c.verif8,c.verif9,c.verif10
            '))
             ->where($columnaVerificacion, $verificacion_id)
             ->where('contrato', $request->contrato)
             ->where('prueba_diagnostica', '0')
             ->where('libro_idlibro', $request->libro_id)
             ->where('estado_liquidacion', '<>', '3')
             ->get();
             return $codigos;

        }
        if($request->getDescuentosVerificacion){
            return $this->getDescuentosVerificacion($request);
        }
        //n_verificacion?getVerificacionXcontrato
        //obtener las verificacioes
        if($request->getVerificacionXcontrato){
            return $this->getVerificacionXcontrato($request->contrato);
        }
        //obtener documentos pagos
        if($request->getDocumentosLiqSinContratos){
            return $this->getDocumentosLiqSinContratos($request->institucion_id,$request->periodo_id);
        }
        //obtener el historico changes de verificaciones
        if($request->getHistoricoVerificacionesCambios){
            return $this->getHistoricoVerificacionesCambios($request);
        }
     }
     public function getAllCodigosXContrato($request){
        //limpiar cache
        Cache::flush();
        $periodo        = $request->periodo_id;
        $institucion    = $request->institucion_id;
        $verif          = "verif".$request->verificacion_id;
        $IdVerificacion = $request->IdVerificacion;
        $contrato       = $request->contrato;
        $detalles = DB::SELECT("SELECT  ls.codigo_liquidacion AS codigo, c.codigo as codigo_libro, c.serie,
            c.libro_idlibro,l.nombrelibro as nombrelibro,ls.id_serie,a.area_idarea,c.estado_liquidacion,
            c.estado,c.bc_estado,c.venta_estado,c.liquidado_regalado,c.bc_institucion,c.contrato,c.venta_lista_institucion
            FROM codigoslibros c
            LEFT JOIN  libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
            WHERE c.bc_periodo          = ?
            AND c.prueba_diagnostica    = '0'
            AND `$verif`                = '$IdVerificacion'
            AND (c.bc_institucion       = '$institucion' OR c.venta_lista_institucion = '$institucion')
            -- AND c.contrato           = '$contrato'
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
                "codigo_libro"              => $item->codigo_libro,
                "IdVerificacion"            => $IdVerificacion,
                "verificacion_id"           => $request->verificacion_id,
                "contrato"                  => $contrato,
                "codigo"                    => $item->codigo,
                "nombre_libro"              => $item->nombrelibro,
                "libro_id"                  => $item->libro_idlibro,
                "libro_idlibro"             => $item->libro_idlibro,
                "id_serie"                  => $item->id_serie,
                "id_periodo"                => $periodo,
                "precio"                    => $precio,
                "estado_liquidacion"        => $item->estado_liquidacion,
                "estado"                    => $item->estado,
                "bc_estado"                 => $item->bc_estado,
                "venta_estado"              => $item->venta_estado,
                "liquidado_regalado"        => $item->liquidado_regalado,
                "bc_institucion"            => $item->bc_institucion,
                "contrato"                  => $item->contrato,
                "venta_lista_institucion"   => $item->venta_lista_institucion
            ];
            $contador++;
        }
        return $datos;
     }
     public function getVentaRealXVerificacion($request){
        $periodo        = $request->periodo_id;
        $institucion    = $request->institucion_id;
        $verif          = "verif".$request->verificacion_id;
        $IdVerificacion = $request->IdVerificacion;
        $contrato       = $request->contrato;
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
     //n_verificacion?getDescuentosVerificacion=yes&num_verificacion_id=1&contrato=C-C23-0000006-OT&periodo_id=22;
     public function getDescuentosVerificacion($request){
        $num_verificacion_id = $request->num_verificacion_id;
        $contrato            = $request->contrato;
        $periodo             = $request->periodo_id;
        $query = $this->FormatoLibrosLiquidados($num_verificacion_id,$contrato,$periodo);
        return $query;
     }
     //para cambiar la data de la liquidacion a la nueva
     public function changeLiquidacion(Request $request){

        if($request->datosTranspasar){
            $datos = DB::SELECT("SELECT * FROM verificaciones_has_temporadas vt
            WHERE vt.contrato = '$request->contrato'
            AND vt.nuevo = '$request->nuevo'
            AND vt.verificacion_id = '$request->num_verificacion'
            ");
            return $datos;
        }
        else{
            $change = DB::SELECT("SELECT * FROM verificaciones
            WHERE contrato = '$request->contrato'

            ");
            return $change;
        }


     }
    public function historicoContrato($request){
       $formato = DB::SELECT("SELECT DISTINCT
       vl.codigo,l.nombrelibro as nombre_libro,ls.idLibro AS libro_id,
       (
        SELECT MAX(t.verificacion_id)  AS maximaVerificacion
        FROM verificaciones_has_temporadas t
        WHERE t.contrato = '$request->contrato'
       ) AS maximaVerificacion
       FROM verificaciones_has_temporadas vl
       LEFT JOIN libros_series ls ON vl.codigo = ls.codigo_liquidacion
       LEFT JOIN libro l ON l.idlibro = ls.idLibro
       WHERE vl.contrato = ?
       AND vl.nuevo = '1'
       AND vl.estado = '1'
       ",[$request->contrato]);
       $codigos = DB::SELECT("SELECT c.codigo, c.libro_idlibro AS libro_id,l.nombrelibro AS nombre_libro,
       c.verif1,
       c.verif2,
       c.verif3,
       c.verif4,
       c.verif5,
       c.verif6,
       c.verif7,
       c.verif8,
       c.verif7,
       c.verif9,
       c.verif10
       FROM codigoslibros c
       LEFT JOIN libro l ON c.libro_idlibro = l.idlibro
       WHERE c.bc_institucion   = '$request->institucion_id'
       AND c.bc_periodo         = '$request->periodo_id'
       AND c.bc_estado          = '2'
       AND c.estado_liquidacion = '0'
       AND c.estado <> '2'
       AND c.prueba_diagnostica = '0'
       ");
       return ["formato" => $formato, "codigos" => $codigos];
    //    return $codigos;
    //    if(empty($codigos)){
    //     return 0;
    //    }else{
    //         $data = [];
    //         foreach($codigos as $key => $item){
    //             $codigo = DB::SELECT("SELECT id_verificacion_inst,verificacion_id,codigo,cantidad
    //             FROM verificaciones_has_temporadas
    //             WHERE contrato = '$contrato'
    //             AND nuevo = '1'
    //             AND codigo = '$item->codigo'
    //             ORDER BY verificacion_id ASC
    //             ");
    //             $data = $codigo;
    //             $cantidad = count($codigo);
    //             foreach($codigo as $k => $tr){
    //                 if($cantidad == 1){
    //                     $data2[$key] =[
    //                         "codigo"                            => $data[0]->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "total"                             => $data[0]->cantidad
    //                     ];
    //                 }
    //                 if($cantidad == 2){
    //                     $suma2 = $data[0]->cantidad+$data[1]->cantidad;
    //                     $data2[$key] =[
    //                         "codigo" => $item->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "verif".$data[1]->verificacion_id   => $data[1]->cantidad,
    //                         "total"                             => $suma2
    //                     ];
    //                 }
    //                 if($cantidad == 3){
    //                     $suma3 = $data[0]->cantidad+$data[1]->cantidad+$data[2]->cantidad;
    //                     $data2[$key] =[
    //                         "codigo" => $item->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "verif".$data[1]->verificacion_id   => $data[1]->cantidad,
    //                         "verif".$data[2]->verificacion_id   => $data[2]->cantidad,
    //                         "total"                             => $suma3
    //                     ];
    //                 }
    //                 if($cantidad == 4){
    //                     $suma4 = $data[0]->cantidad+$data[1]->cantidad+$data[2]->cantidad+$data[3]->cantidad;
    //                     $data2[$key] =[
    //                         "codigo" => $item->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "verif".$data[1]->verificacion_id   => $data[1]->cantidad,
    //                         "verif".$data[2]->verificacion_id   => $data[2]->cantidad,
    //                         "verif".$data[3]->verificacion_id   => $data[3]->cantidad,
    //                         "total"                             => $suma4
    //                     ];
    //                 }
    //                 if($cantidad == 5){
    //                     $suma5 = $data[0]->cantidad+$data[1]->cantidad+$data[2]->cantidad+$data[3]->cantidad+$data[4]->cantidad;
    //                     $data2[$key] =[
    //                         "codigo" => $item->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "verif".$data[1]->verificacion_id   => $data[1]->cantidad,
    //                         "verif".$data[2]->verificacion_id   => $data[2]->cantidad,
    //                         "verif".$data[3]->verificacion_id   => $data[3]->cantidad,
    //                         "verif".$data[4]->verificacion_id   => $data[4]->cantidad,
    //                         "total"                             => $suma5
    //                     ];
    //                 }
    //                 if($cantidad == 6){
    //                     $suma6 = $data[0]->cantidad+$data[1]->cantidad+$data[2]->cantidad+$data[3]->cantidad+$data[4]->cantidad+$data[5]->cantidad;
    //                     $data2[$key] =[
    //                         "codigo" => $item->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "verif".$data[1]->verificacion_id   => $data[1]->cantidad,
    //                         "verif".$data[2]->verificacion_id   => $data[2]->cantidad,
    //                         "verif".$data[3]->verificacion_id   => $data[3]->cantidad,
    //                         "verif".$data[4]->verificacion_id   => $data[4]->cantidad,
    //                         "verif".$data[5]->verificacion_id   => $data[5]->cantidad,
    //                         "total"                             => $suma6
    //                     ];
    //                 }
    //                 if($cantidad == 7){
    //                     $suma7 = $data[0]->cantidad+$data[1]->cantidad+$data[2]->cantidad+$data[3]->cantidad+$data[4]->cantidad+$data[5]->cantidad+$data[6]->cantidad;
    //                     $data2[$key] =[
    //                         "codigo" => $item->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "verif".$data[1]->verificacion_id   => $data[1]->cantidad,
    //                         "verif".$data[2]->verificacion_id   => $data[2]->cantidad,
    //                         "verif".$data[3]->verificacion_id   => $data[3]->cantidad,
    //                         "verif".$data[4]->verificacion_id   => $data[4]->cantidad,
    //                         "verif".$data[5]->verificacion_id   => $data[5]->cantidad,
    //                         "verif".$data[6]->verificacion_id   => $data[6]->cantidad,
    //                         "total"                             => $suma7
    //                     ];
    //                 }
    //                 if($cantidad == 8){
    //                     $suma8 = $data[0]->cantidad+$data[1]->cantidad+$data[2]->cantidad+$data[3]->cantidad+$data[4]->cantidad+$data[5]->cantidad+$data[6]->cantidad+$data[7]->cantidad;
    //                     $data2[$key] =[
    //                         "codigo" => $item->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "verif".$data[1]->verificacion_id   => $data[1]->cantidad,
    //                         "verif".$data[2]->verificacion_id   => $data[2]->cantidad,
    //                         "verif".$data[3]->verificacion_id   => $data[3]->cantidad,
    //                         "verif".$data[4]->verificacion_id   => $data[4]->cantidad,
    //                         "verif".$data[5]->verificacion_id   => $data[5]->cantidad,
    //                         "verif".$data[6]->verificacion_id   => $data[6]->cantidad,
    //                         "verif".$data[7]->verificacion_id   => $data[7]->cantidad,
    //                         "total"                             => $suma8
    //                     ];
    //                 }
    //                 if($cantidad == 9){
    //                     $suma9 = $data[0]->cantidad+$data[1]->cantidad+$data[2]->cantidad+$data[3]->cantidad+$data[4]->cantidad+$data[5]->cantidad+$data[6]->cantidad+$data[7]->cantidad+$data[8]->cantidad;
    //                     $data2[$key] =[
    //                         "codigo" => $item->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "verif".$data[1]->verificacion_id   => $data[1]->cantidad,
    //                         "verif".$data[2]->verificacion_id   => $data[2]->cantidad,
    //                         "verif".$data[3]->verificacion_id   => $data[3]->cantidad,
    //                         "verif".$data[4]->verificacion_id   => $data[4]->cantidad,
    //                         "verif".$data[5]->verificacion_id   => $data[5]->cantidad,
    //                         "verif".$data[6]->verificacion_id   => $data[6]->cantidad,
    //                         "verif".$data[7]->verificacion_id   => $data[7]->cantidad,
    //                         "verif".$data[8]->verificacion_id   => $data[8]->cantidad,
    //                         "total"                             => $suma9
    //                     ];
    //                 }
    //                 if($cantidad == 10){
    //                     $suma10 = $data[0]->cantidad+$data[1]->cantidad+$data[2]->cantidad+$data[3]->cantidad+$data[4]->cantidad+$data[5]->cantidad+$data[6]->cantidad+$data[7]->cantidad+$data[8]->cantidad+$data[9]->cantidad;
    //                     $data2[$key] =[
    //                         "codigo" => $item->codigo,
    //                         "libro_id"                          => $item->libro_id,
    //                         "nombre_libro"                      => $item->nombre_libro,
    //                         "verif".$data[0]->verificacion_id   => $data[0]->cantidad,
    //                         "verif".$data[1]->verificacion_id   => $data[1]->cantidad,
    //                         "verif".$data[2]->verificacion_id   => $data[2]->cantidad,
    //                         "verif".$data[3]->verificacion_id   => $data[3]->cantidad,
    //                         "verif".$data[4]->verificacion_id   => $data[4]->cantidad,
    //                         "verif".$data[5]->verificacion_id   => $data[5]->cantidad,
    //                         "verif".$data[6]->verificacion_id   => $data[6]->cantidad,
    //                         "verif".$data[7]->verificacion_id   => $data[7]->cantidad,
    //                         "verif".$data[8]->verificacion_id   => $data[8]->cantidad,
    //                         "verif".$data[9]->verificacion_id   => $data[9]->cantidad,
    //                         "total"                             => $suma10
    //                     ];
    //                 }
    //             }
    //         }
    //         return $data2;
        //}
    }
    //para crear una verificacion
    public function crearVerificacion(Request $request){
        //obtener la fecha actual
         $fechaActual  = date('Y-m-d');
        $vericacionContrato = DB::select("SELECT
        * FROM verificaciones
        WHERE contrato = '$request->contrato'
        AND nuevo = '1'
        ORDER BY id DESC
        ");
        //Si existe una verificacion
        if(count($vericacionContrato) >0){
              //obtener el numero de verificacion en el que se quedo el contrato
            $traerNumeroVerificacion =  $vericacionContrato[0]->num_verificacion;
            $traeridVerificacion     =  $vericacionContrato[0]->id;
           //Actualizo a estado 0 la verificacion anterior
           DB::table('verificaciones')
           ->where('id', $traeridVerificacion)
           ->update([
               'fecha_fin' => $fechaActual,
               'estado' => "0"
           ]);
           //  Para generar una verficacion y que quede abierta
           $verificacion =  new Verificacion;
           $verificacion->num_verificacion = $traerNumeroVerificacion+1;
           $verificacion->fecha_inicio = $fechaActual;
           $verificacion->contrato = $request->contrato;
           $verificacion->nuevo = '1';
           $verificacion->save();
        //si no existe una verificacion
        }else{
            $verificacion =  new Verificacion;
            $verificacion->num_verificacion = 1;
            $verificacion->fecha_inicio = $fechaActual;
            $verificacion->fecha_fin = $fechaActual;
            $verificacion->contrato = $request->contrato;
            $verificacion->estado = "0";
            $verificacion->nuevo = '1';
            $verificacion->save();

            $traerNumeroVerificacionInicial =  $verificacion->num_verificacion;
            //Para generar la siguiente verificacion y quede abierta
            $verificacion =  new Verificacion;
            $verificacion->num_verificacion = $traerNumeroVerificacionInicial+1;
            $verificacion->fecha_inicio = $fechaActual;
            $verificacion->contrato = $request->contrato;
            $verificacion->nuevo = "1";
            $verificacion->save();
        }

           if($verificacion){
               return ["status"=>"1","message"=>"Se agrego correctamente una nueva verificacion"];
           }else{
               return ["status"=>"1","message"=>"No se pudo agregar una nueva verificacion"];
           }
    }

    //para guardar los cambios  para cambiar de liquidacion de la  anterior a la nueva
    public function guardarChangeLiquidacion(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //change a estado nuevo = 1
            $this->actualizarLiquidacion($request->contrato,$request->num_verificacion_anterior,$request->num_verificacion_nueva,$request->nuevo);
        //actualizar cada codigo al actual id de liquidacion
            $buscarInstitucion= DB::select("SELECT idInstitucion,id_periodo
                FROM temporadas
                where contrato = '$request->contrato'
            ");
            if(empty($buscarInstitucion)){
                return ["status"=>"0","message"=>"No se encontro la institucion o no tiene periodo"];
            }
            $periodo = $buscarInstitucion[0]->id_periodo;
            if($periodo == ""){
                return ["status"=>"0","message"=>"Ingrese un periodo al contrato por favor"];
            }
            $institucion = $buscarInstitucion[0]->idInstitucion;
            $num_verificacion = 'verif'.$request->num_verificacion_anterior;
            $num_verificacionNueva = 'verif'.$request->num_verificacion_nueva;

        //guardar en el historico
            $this->guardarHistoricoLiquidacion($periodo,$request->contrato,$num_verificacion,$request->id_verificacion_anterior,$request->id_verificacion_nuevo,$request->usuario_editor,$institucion);

              //actualizar cada codigo a la nueva liquidacion
            DB::table('codigoslibros')
            ->where('id_periodo', $periodo)
            ->where('contrato', $request->contrato)
            ->where($num_verificacion, $request->id_verificacion_anterior)
            ->update([
                $num_verificacionNueva => $request->id_verificacion_nuevo,
            ]);

            if($num_verificacion == $num_verificacionNueva){

            }
            //vaciar la verificacion anterior
            else{
                DB::table('codigoslibros')
                ->where('id_periodo', $periodo)
                ->where('contrato', $request->contrato)
                ->where($num_verificacion, $request->id_verificacion_anterior)
                ->update([
                    $num_verificacion => '',
                ]);
            }
    }

    public function actualizarLiquidacion($contrato,$num_verificacion_anterior,$num_verificacion_nueva,$nuevo){
        DB::table('verificaciones_has_temporadas')
        ->where('contrato', $contrato)
        ->where('verificacion_id', $num_verificacion_anterior)
        ->where('nuevo', $nuevo)
        ->update([
            'nuevo' => '1',
            'verificacion_id' => $num_verificacion_nueva
        ]);
    }

    public function guardarHistoricoLiquidacion($periodo,$contrato,$num_verificacion,$id_verificacion_anterior,$id_verificacion_nuevo,$usuario_editor,$institucion){
        $codigos = DB::table('codigoslibros')
        ->where('id_periodo', $periodo)
        ->where('contrato', $contrato)
        ->where($num_verificacion, $id_verificacion_anterior)
        ->get();

        $mensaje = "Se cambio el id de la verificacion de ".$id_verificacion_anterior." a el id de verificacion ".$id_verificacion_nuevo;
        foreach($codigos as $key => $item){
            $historico = new HistoricoCodigos();
            $historico->id_usuario = $item->idusuario;
            $historico->codigo_libro = $item->codigo;
            $historico->idInstitucion = $usuario_editor;
            $historico->usuario_editor = $institucion;
            $historico->id_periodo = $periodo;
            $historico->observacion = $mensaje;
            $historico->contrato_actual = $contrato;
            $historico->save();
        }
    }
    //SOLICITAR VERIFICACION
    //api:post/solicitarVerificacion
    public function solicitarVerificacion(Request $request){
        $fechaActual = null;
        $fechaActual = date('Y-m-d H:i:s');
        DB::UPDATE("UPDATE pedidos SET estado_verificacion = '1' , fecha_solicita_verificacion = '$fechaActual' WHERE contrato_generado = '$request->contrato'");
        //registrar trazabilidad
        //validar que no este registrado
        $query = DB::SELECT("SELECT * FROM temporadas_verificacion_historico th
        WHERE th.contrato = '$request->contrato'
        AND th.estado = '1'");
        if(empty($query)){
            $trazabilidad = new TemporadaVerificacionHistorico();
            $trazabilidad->contrato                     = $request->contrato;
            $trazabilidad->fecha_solicita_verificacion  = $fechaActual;
            $trazabilidad->estado                       = 1;
            $trazabilidad->save();
        }
    }
    //api:get/notificacionesVerificaciones
    public function notificacionesVerificaciones(Request $request){
        $idFacturador = $request->input("idFacturador",0);
        $query = DB::table('pedidos as p')
            ->select(
                DB::raw("CONCAT(u.nombres, ' ', u.apellidos) AS asesor"),
                'p.id_asesor',
                'i.nombreInstitucion',
                'pe.region_idregion',
                'c.nombre AS ciudad',
                'p.contrato_generado',
                'p.fecha_solicita_verificacion',
                'p.tipo_venta'
            )
            ->leftJoin('periodoescolar as pe', 'p.id_periodo', '=', 'pe.idperiodoescolar')
            ->leftJoin('usuario as u', 'p.id_asesor', '=', 'u.idusuario')
            ->leftJoin('institucion as i', 'p.id_institucion', '=', 'i.idInstitucion')
            ->leftJoin('ciudad as c', 'i.ciudad_id', '=', 'c.idciudad')
            ->where('p.estado', '1')
            ->where('p.estado_verificacion', '1')
            ->when($idFacturador, function ($query) use ($idFacturador) {
                $query->whereExists(function ($subquery) use ($idFacturador){
                    $subquery->select(DB::raw(1))
                        ->from('pedidos_asesores_facturador')
                        ->whereColumn('pedidos_asesores_facturador.id_asesor', 'p.id_asesor')
                        ->where('pedidos_asesores_facturador.id_facturador', $idFacturador);
                });
            })
            ->orderBy('p.fecha_solicita_verificacion', 'desc')
            ->get();

        return $query;
        // $query = DB::SELECT("SELECT
        //     CONCAT(u.nombres,' ',u.apellidos) as asesor,
        //     i.nombreInstitucion,
        //     pe.region_idregion, c.nombre AS ciudad,
        //     p.contrato_generado,p.fecha_solicita_verificacion,p.tipo_venta
        //     FROM pedidos p
        //     LEFT JOIN periodoescolar pe ON p.id_periodo = pe.idperiodoescolar
        //     LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        //     LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
        //     LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        //     WHERE p.estado = '1'
        //     AND p.estado_verificacion ='1'
        //     order by p.fecha_solicita_verificacion desc
        // ");
        return $query;
    }
    //api para traer la trazabilidad de las verificaciones
    //api:get/getTrazabilidadVerificacion
    public function getTrazabilidadVerificacion(Request $request){
        $query = DB::SELECT("SELECT th.*,
            CONCAT(u.nombres,' ', u.apellidos) AS asesor, i.nombreInstitucion,
            c.nombre AS ciudad
            FROM temporadas_verificacion_historico th
            LEFT JOIN temporadas t ON th.contrato = t.contrato
            LEFT JOIN usuario u ON t.id_asesor = u.idusuario
            LEFT JOIN institucion i ON t.idInstitucion = i.idInstitucion
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            WHERE th.contrato = '$request->contrato'
            AND t.estado = '1'
        ");
        return $query;
    }
    //api para traer todo el historico de las solicitudes de verificaciones
    //api:get/getHistoricoVerificaciones
    public function getHistoricoVerificaciones(Request $request){
        $query = DB::SELECT("SELECT th.*,
            CONCAT(u.nombres,' ', u.apellidos) AS asesor, i.nombreInstitucion,
            c.nombre AS ciudad
            FROM temporadas_verificacion_historico th
            LEFT JOIN temporadas t ON th.contrato = t.contrato
            LEFT JOIN usuario u ON t.id_asesor = u.idusuario
            LEFT JOIN institucion i ON t.idInstitucion = i.idInstitucion
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            ORDER BY th.id DESC
        ");
        return $query;
    }
    public function tipoPago($id_institucion,$id_periodo,$tipo){
        $query = DB::SELECT("SELECT * FROM 1_4_documento_liq l
        WHERE l.institucion_id  = ?
        AND l.periodo_id = ?
        AND l.tipo_pago_id = ?
        AND l.estado  = 1
        ",
        [$id_institucion,$id_periodo,$tipo]);
        return $query;
    }
    public function tipoPagoReporte($id_institucion,$id_periodo,$tipo){
        $query = DB::SELECT("SELECT * FROM 1_4_documento_liq l
        WHERE l.institucion_id  = ?
        AND l.periodo_id = ?
        AND l.tipo_pago_id = ?
        AND l.mostrar_reporte = '1'
        AND l.estado  = 1
        ",
        [$id_institucion,$id_periodo,$tipo]);
        return $query;
    }
    //api:get/getVerificacionXId/{id}
    public function getVerificacionXId($id){
        $query = DB::SELECT("SELECT v.*, p.id_institucion, p.id_periodo
        FROM verificaciones v
        LEFT JOIN pedidos p ON v.contrato = p.contrato_generado
        WHERE v.id = '$id'
        ");
        if(empty($query)){
            return $query;
        }
        $datos              = [];
        $id_institucion     = $query[0]->id_institucion;
        $id_periodo         = $query[0]->id_periodo;
        $valorOtrosValores  = 0;
        $sumaDevolucionEsc  = 0;
        $campo_dinamico     = "";
        //Pagos Tipo “devolucion escuela” 5
        $devolucionEscuela  = $this->tipoPago($id_institucion,$id_periodo,5);
        //sumar los valores de devolucion escuela
        foreach($devolucionEscuela as $key => $item){
            $sumaDevolucionEsc  = $sumaDevolucionEsc + $item->doc_valor;
        }
        //Pago Tipo “otros valores para cancelar”
        $otrosValores       = $this->tipoPago($id_institucion,$id_periodo,7);
        //Pago Tipo liquidacion con opcion imprimir en el reporte
        $valoresLiquidadosReporte = $this->tipoPagoReporte($id_institucion,$id_periodo,2);
        //sumar los valores de otros valores
        foreach($otrosValores as $key => $item){
            $campo_dinamico     = $item->campo_dinamico;
            $valorOtrosValores  = $valorOtrosValores + $item->doc_valor;
        }
        foreach($query as $key => $item){
            //restar con comision
            $descuentos = DB::SELECT("SELECT *
            FROM verificaciones_descuentos d
            WHERE d.verificacion_id    = ?
            AND d.estado               = '1'
            AND d.restar               = '0'
            ",[$item->id]);
            //restar a venta
            $descuentosVenta = DB::SELECT("SELECT *
            FROM verificaciones_descuentos d
            WHERE d.verificacion_id    = ?
            AND d.estado               = '1'
            AND d.restar               = '1'
            ",[$item->id]);

            $datos[$key] = [
                "id"                            => $item->id,
                "num_verificacion"              => $item->num_verificacion,
                "fecha_inicio"                  => $item->fecha_inicio,
                "fecha_fin"                     => $item->fecha_fin,
                "estado"                        => $item->estado,
                "contrato"                      => $item->contrato,
                "nuevo"                         => $item->nuevo,
                "file_evidencia"                => $item->file_evidencia,
                "observacion"                   => $item->observacion,
                "valor_liquidacion"             => $item->valor_liquidacion,
                "fecha_subir_evidencia"         => $item->fecha_subir_evidencia,
                "cobro_venta_directa"           => $item->cobro_venta_directa,
                "tipoPago"                      => $item->tipoPago,
                "personalizado"                 => $item->personalizado,
                "totalDescuento"                => $item->totalDescuento,
                "totalDescuentoVenta"           => $item->totalDescuentoVenta,
                "abonado"                       => $item->abonado,
                "estado_pago"                   => $item->estado_pago,
                "venta_real"                    => $item->venta_real,
                "venta_real_regalados"          => $item->venta_real_regalados,
                "permiso_anticipo_deuda"        => $item->permiso_anticipo_deuda,
                "permiso_convenio"              => $item->permiso_convenio,
                "permiso_cobro_venta_directa"   => $item->permiso_cobro_venta_directa,
                "descuentos"                    => $descuentos,
                "descuentosVenta"               => $descuentosVenta,
                "otroValoresCancelar"           => $valorOtrosValores,
                "campo_dinamico"                => $campo_dinamico,
                "devolucionEscuela"             => $sumaDevolucionEsc,
                "totalVenta"                    => $item->totalVenta,
                "valoresLiquidadosReporte"      => $valoresLiquidadosReporte
            ];
        }
        return $datos;
    }
    //api:post/saveDatosVerificacion
    public function saveDatosVerificacion(Request $request){
        $verificacion =  Verificacion::findOrFail($request->id);
        $observacion = "";
        if($request->observacion == null || $request->observacion == "null"){
            $observacion  = null;
        }else{
            $observacion = $request->observacion;
        }
        $verificacion->observacion = $observacion;
        $verificacion->save();
        if($verificacion){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
        }
    }
    /***LIQUIDAR EN SISTEMA DE FACUTRACION */
    //api:Get/nliquidacion/liquidar/{contrato}
    public function liquidarFacturacion($contrato){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //validar si el contrato esta activo
        $validarContrato = DB::select("SELECT t.*
            FROM temporadas t
            WHERE t.contrato = ?
        ",[$contrato]);
        if(empty($validarContrato)){
            return ["status" => "0", "message" => "No existe el contrato"];
        }
        $estado = $validarContrato[0]->estado;
        if($estado == '0'){
            return ["status" => "0", "message" => "El contrato esta inactivo"];
        }
        //almacenar el id de la institucion
        $institucion    = $validarContrato[0]->idInstitucion;
        //almancenar el periodo
        $periodo        =  $validarContrato[0]->id_periodo;
        //traer temporadas
        $temporadas     = $validarContrato;
        //validar que el contrato este en pedidos
        $query = DB::SELECT("SELECT * FROM pedidos p
        WHERE p.contrato_generado = ?
        AND p.estado = '1'
        ",[$contrato]);
        $id_pedido = $query[0]->id_pedido;
        //validar que el pedido no tenga alcaces abiertos o activos
        $query2 = DB::SELECT("SELECT * FROM pedidos_alcance pa
        WHERE pa.id_pedido = ?
        AND pa.estado_alcance = '0'
        ",[$id_pedido]);
        if(count($query2) > 0){
            return ["status"=>"0", "message" => "El contrato tiene alcances abiertos"];
        }
        if($periodo ==  null || $periodo == 0 || $periodo == ""){
            return ["status"=>"0", "message" => "El contrato no tiene asignado a un período"];
         }else{
           //traigo la liquidacion actual por cantidad GRUPAL
           $data                   = $this->getCodigosGrupalLiquidar($institucion,$periodo);
           //INVIVIDUAL VERSION 1
           //traigo la liquidacion  con los codigos invidivuales
           $traerCodigosIndividual = $this->getCodigosIndividualLiquidar($institucion,$periodo);
           //TRAER LOS CODIGOS REGALADOS
           $arregloRegalados       = $this->getRegaladosXLiquidar($institucion,$periodo);
           //SI TODO HA SALIDO BIEN TRAEMOS LA DATA
            //SI TODO HA SALIDO BIEN TRAEMOS LA DATA
            if(count($data) >0){
                //====PROCESO GUARDAR EN FACTURACION=======
                //obtener la fecha actual
                $fechaActual  = date('Y-m-d');
                //verificar si es el primer contrato
                $vericacionContrato = $this->getVerificacionXcontrato($contrato);
                //======PARA REALIZAR LA VERIFICACION EN CASO QUE EL CONTRATO YA TENGA VERIFICACIONES====
                if(count($vericacionContrato) > 0){
                    //obtener el numero de verificacion en el que se quedo el contrato
                    $traerNumeroVerificacion =  $vericacionContrato[0]->num_verificacion;
                    $traeridVerificacion     =  $vericacionContrato[0]->id;
                    //Para guardar la verificacion si  existe el contrato
                    //SI EXCEDE LAS 10 VERIFICACIONES
                    $finVerificacion="no";
                    if($traerNumeroVerificacion >10){
                        $finVerificacion = "yes";
                    }
                    else{
                        //OBTENER LA CANTIDAD DE LA VERIFICACION ACTUAL
                        $this->updateCodigoIndividualInicial($traeridVerificacion,$traerCodigosIndividual,$contrato,$traerNumeroVerificacion,$periodo,$institucion,"liquidacion");
                        //ACTUALIZAR EN LOS CODIGOS REGALADOS LOS DATOS DE VERIFICACION
                        if(count($arregloRegalados) > 0) {
                            $this->updateCodigoIndividualInicial($traeridVerificacion,$arregloRegalados,$contrato,$traerNumeroVerificacion,$periodo,$institucion,"regalado");
                        }
                        //Ingresar la liquidacion en la base
                        $this->guardarLiquidacion($data,$traerNumeroVerificacion,$contrato);
                        //Actualizo a estado 0 la verificacion anterior
                        DB::table('verificaciones')
                        ->where('id', $traeridVerificacion)
                        ->update([
                            'fecha_fin' => $fechaActual,
                            'estado' => "0"
                        ]);
                        //  Para generar una verficacion y que quede abierta
                        $this->saveVerificacion($traerNumeroVerificacion+1,$contrato);
                    }
                }else{
                    //=====PARA GUARDAR LA VERIFICACION SI EL CONTRATO AUN NO TIENE VERIFICACIONES======
                    //para indicar que aun no existe el fin de la verificacion
                    $finVerificacion = "0";
                    //Para guardar la primera verificacion en la tabla
                    $verificacion =  new Verificacion;
                    $verificacion->num_verificacion = 1;
                    $verificacion->fecha_inicio     = $fechaActual;
                    $verificacion->fecha_fin        = $fechaActual;
                    $verificacion->contrato         = $contrato;
                    $verificacion->estado           = "0";
                    $verificacion->nuevo            = '1';
                    $verificacion->save();
                    //Obtener Verificacion actual
                    $encontrarVerificacionContratoInicial = $this->getVerificacionXcontrato($contrato);
                    //obtener el numero de verificacion en el que se quedo el contrato
                    $traerNumeroVerificacionInicial     =  $encontrarVerificacionContratoInicial[0]->num_verificacion;
                    //obtener la clave primaria de la verificacion actual
                    $traerNumeroVerificacionInicialId   = $encontrarVerificacionContratoInicial[0]->id;
                    //Actualizar cada codigo de la verificacion
                    $this->updateCodigoIndividualInicial($traerNumeroVerificacionInicialId,$traerCodigosIndividual,$contrato,$traerNumeroVerificacionInicial,$periodo,$institucion,"liquidacion");
                    //ACTUALIZAR EN LOS CODIGOS REGALADOS LOS DATOS DE VERIFICACION
                    if(count($arregloRegalados) > 0) {
                        $this->updateCodigoIndividualInicial($traerNumeroVerificacionInicialId,$arregloRegalados,$contrato,$traerNumeroVerificacionInicial,$periodo,$institucion,"regalado");
                    }
                    //Ingresar la liquidacion en la base
                    $this->guardarLiquidacion($data,$traerNumeroVerificacionInicial,$contrato);
                    //Para generar la siguiente verificacion y quede abierta
                    $this->saveVerificacion($traerNumeroVerificacionInicial+1,$contrato);
                    //COLOCAR EL CAMPO datos_verificacion_por_ingresar EN ESTADO 1 PARA QUE SE EJECUTE Y SE GUARDE LOS VALORES
                    $this->updateDatosVerificacionPorIngresar($contrato,1);
                }
                if($finVerificacion =="yes"){
                    return [
                        "verificaciones"=>"Ha alzancado el limite de verificaciones permitidas",
                        'temporada'=>$temporadas,
                        'codigos_libros' => $data
                    ];
                }else{
                    $fecha2 = date('Y-m-d H:i:s');
                    DB::UPDATE("UPDATE pedidos SET estado_verificacion = '0' , fecha_solicita_verificacion = null WHERE contrato_generado = '$contrato'");
                    DB::UPDATE("UPDATE temporadas_verificacion_historico SET estado = '2', fecha_realiza_verificacion = '$fecha2' WHERE contrato = '$contrato' AND estado = '1'");
                    return ['temporada'=>$temporadas,'codigos_libros' => $data];
                }
            }else{
                return ["status"=>"0", "message" => "No existe NUEVOS VALORES para guardar la verificación"];
            }
        }
    }


     /***LIQUIDAR EN SISTEMA DE FACTURACION VERSION */
    //api:Get/liquidarFacturacionVersion2/{contrato}
    public function liquidarFacturacionVersion2($contrato,$user_created){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $idVerificacionActual           = 0;
        $estadoVerificacion             = 0;
        $num_verificacionaActual        = 0;
        ///====VALIDAR SI LA LIQUIDACION SE ENCUENTRA EN PROCESO
        $verificacion   = $this->getVerificacionXcontrato($contrato);
        //si ya existe verificaciones
        if(count($verificacion) > 0) {
            $idVerificacionActual       = $verificacion[0]->id;
            $estadoVerificacion         = $verificacion[0]->estado;
            $num_verificacionaActual    = $verificacion[0]->num_verificacion;
        }
        //si no existe lo creo
        else{
            $saveV                      = $this->saveVerificacion(1,$contrato);
            $idVerificacionActual       = $saveV->id;
            $estadoVerificacion         = $saveV->estado;
            $num_verificacionaActual    = $saveV->num_verificacion;
        }
        ///================VALIDACIONES======================
            //validar si el contrato esta activo
            $validarContrato = Temporada::where('contrato',$contrato)->get();
            if(empty($validarContrato)){ return ["status" => "0", "message" => "No existe el contrato"]; }
            $estado = $validarContrato[0]->estado;
            if($estado == '0')         { return ["status" => "0", "message" => "El contrato esta inactivo"]; }
            //almacenar el id de la institucion
            $institucion    = $validarContrato[0]->idInstitucion;
            //almancenar el periodo
            $periodo        =  $validarContrato[0]->id_periodo;
            //validar que el contrato este en pedidos
            $pedido = Pedidos::where('contrato_generado',$contrato)->get();
            if(count($pedido) == 0){ return ["status"=>"0","message"=>"No existe el contrato"]; }
            $id_pedido      = $pedido[0]->id_pedido;
            //validar que el pedido no tenga alcaces abiertos o activos
            $query2 = DB::SELECT("SELECT * FROM pedidos_alcance pa WHERE pa.id_pedido = ? AND pa.estado_alcance = '0'",[$id_pedido]);
            if(count($query2) > 0){ return ["status"=>"0", "message" => "El contrato tiene alcances abiertos"]; }
        if($periodo ==  null || $periodo == 0 || $periodo == ""){ return ["status"=>"0", "message" => "El contrato no tiene asignado a un período"]; }
        //======================FIN VALIDACIONES=========================================================
        else{
            //estadoVerificacion: 0 => ya realizada; 1 =>  la verificacion abierta; 2 => pendientes
            //Verificacion abierta
            if($estadoVerificacion == 1)    { return $this->generateLiquidacionAntes2000($institucion,$periodo,$contrato,$idVerificacionActual,$num_verificacionaActual,$user_created); }
            //Verificacion pendiente
            if($estadoVerificacion == 2)    { return $this->generateLiquidacionDespues2000($institucion,$periodo,$contrato,$idVerificacionActual,$num_verificacionaActual,$user_created); }
        }
    }
    public function generateLiquidacionAntes2000($institucion,$periodo,$contrato,$idVerificacionActual,$num_verificacionaActual,$user_created){
        //traigo la liquidacion actual por cantidad GRUPAL
        $data                   = $this->getCodigosGrupalLiquidar($institucion,$periodo);
        //INVIVIDUAL VERSION 1
        //traigo la liquidacion  con los codigos invidivuales con limite de 2000
        $traerCodigosIndividual = $this->getCodigosIndividualLiquidar($institucion,$periodo);
        //TRAER LOS CODIGOS REGALADOS
        $arregloRegalados       = $this->getRegaladosXLiquidar($institucion,$periodo);
        //SI TODO HA SALIDO BIEN TRAEMOS LA DATA
        if(count($data) >0){
            //====PROCESO GUARDAR EN FACTURACION=======
            //obtener la fecha actual
            $fechaActual  = date('Y-m-d');
            //======PARA REALIZAR LA VERIFICACION EN CASO QUE EL CONTRATO YA TENGA VERIFICACIONES====
            $traerNumeroVerificacion = $num_verificacionaActual;
            $traeridVerificacion     = $idVerificacionActual;
            //Para guardar la verificacion si  existe el contrato
            //SI EXCEDE LAS 10 VERIFICACIONES
            $finVerificacion         = "no";
            if($traerNumeroVerificacion > 10){ $finVerificacion = "yes"; }
            if($finVerificacion =="yes"){
                return ["status"=>"0", "message" => "Ha alzancado el limite de verificaciones permitidas"];
            }
            else{
                //OBTENER LA CANTIDAD DE LA VERIFICACION ACTUAL
                $this->updateCodigoIndividualInicial($traeridVerificacion,$traerCodigosIndividual,$contrato,$traerNumeroVerificacion,$periodo,$institucion,"liquidacion",$user_created);
                //ACTUALIZAR EN LOS CODIGOS REGALADOS LOS DATOS DE VERIFICACION
                if(count($arregloRegalados) > 0) {
                    $this->updateCodigoIndividualInicial($traeridVerificacion,$arregloRegalados,$contrato,$traerNumeroVerificacion,$periodo,$institucion,"regalado",$user_created);
                }
                //Ingresar la liquidacion en la base
                $this->guardarLiquidacionCodigosDemasiados($data,$traerNumeroVerificacion,$contrato);
                //consultar si todavia existe codigos individual
                $codigosFaltante = $this->getCodigosIndividualLiquidar($institucion,$periodo);
                //de lo contrario coloco en estado 2 la verificacion como pendiente para liquidar en 2000 en 2000
                if(count($codigosFaltante) > 0){
                    DB::table('verificaciones')
                    ->where('id', $traeridVerificacion)
                    ->update([
                        'estado' => "2"
                    ]);
                }
                else{
                    //Actualizo a estado 0 la verificacion anterior para cerrar
                    DB::table('verificaciones')
                    ->where('id', $traeridVerificacion)
                    ->update([
                        'fecha_fin' => $fechaActual,
                        'estado' => "0"
                    ]);
                    $fecha2 = date('Y-m-d H:i:s');
                    DB::UPDATE("UPDATE pedidos SET estado_verificacion = '0' , fecha_solicita_verificacion = null WHERE contrato_generado = '$contrato'");
                    DB::UPDATE("UPDATE temporadas_verificacion_historico SET estado = '2', fecha_realiza_verificacion = '$fecha2' WHERE contrato = '$contrato' AND estado = '1'");
                    //  Para generar una verficacion y que quede abierta
                    $this->saveVerificacion($traerNumeroVerificacion+1,$contrato);
                }
                return ['guardados' => count($traerCodigosIndividual), "faltante" => count($codigosFaltante) ];
            }
        }else{
            return ["status"=>"1", "message" => "Se guardo correctamente ya no mas libros por liquidar"];
        }
    }
    public function generateLiquidacionDespues2000($institucion,$periodo,$contrato,$idVerificacionActual,$num_verificacionaActual,$user_created){
        //obtener la fecha actual
        $fechaActual                = date('Y-m-d');
        $traerNumeroVerificacion    = $num_verificacionaActual;
        $traeridVerificacion        = $idVerificacionActual;
        //traigo la liquidacion  con los codigos invidivuales
        $traerCodigosIndividual     = $this->getCodigosIndividualLiquidar($institucion,$periodo);
        //OBTENER LA CANTIDAD DE LA VERIFICACION ACTUAL
        $this->updateCodigoIndividualInicial($traeridVerificacion,$traerCodigosIndividual,$contrato,$traerNumeroVerificacion,$periodo,$institucion,"liquidacion",$user_created);
        //consultar si todavia existe codigos individual
        $codigosFaltante = $this->getCodigosIndividualLiquidar($institucion,$periodo);
        //de lo contrario coloco en estado 2 la verificacion como pendiente para liquidar en 2000 en 2000
        if(count($codigosFaltante) > 0){
            DB::table('verificaciones')
            ->where('id', $traeridVerificacion)
            ->update([
                'estado' => "2"
            ]);
            return ['guardados' => count($traerCodigosIndividual), "faltante" => count($codigosFaltante) ];
        }
        else{
            //Actualizo a estado 0 la verificacion anterior para cerrar
            DB::table('verificaciones')
            ->where('id', $traeridVerificacion)
            ->update([
                'fecha_fin' => $fechaActual,
                'estado' => "0"
            ]);
            $fecha2 = date('Y-m-d H:i:s');
            DB::UPDATE("UPDATE pedidos SET estado_verificacion = '0' , fecha_solicita_verificacion = null WHERE contrato_generado = '$contrato'");
            DB::UPDATE("UPDATE temporadas_verificacion_historico SET estado = '2', fecha_realiza_verificacion = '$fecha2' WHERE contrato = '$contrato' AND estado = '1'");
            //  Para generar una verficacion y que quede abierta
            $this->saveVerificacion($traerNumeroVerificacion+1,$contrato);
            //COLOCAR EL CAMPO datos_verificacion_por_ingresar EN ESTADO 1 PARA QUE SE EJECUTE Y SE GUARDE LOS VALORES
            $this->updateDatosVerificacionPorIngresar($contrato,1);
        }
        return ['guardados' => count($traerCodigosIndividual), "faltante" => count($codigosFaltante) ];
    }
    //api:get/getcodigosLiquidar/{contrato}
    public function getcodigosLiquidar($contrato){
        $pedido = Pedidos::where('contrato_generado',$contrato)->get();
        if(count($pedido) == 0){ return ["status"=>"0","message"=>"No existe el contrato"]; }
        $id_periodo         = $pedido[0]->id_periodo;
        $id_institucion     = $pedido[0]->id_institucion;
        $pedido = Pedidos::where('contrato_generado', $contrato)->get();
        if ($pedido->isEmpty()) { return ["status" => "0", "message" => "No existe el contrato"]; }
        $id_periodo         = $pedido[0]->id_periodo;
        $id_institucion     = $pedido[0]->id_institucion;
        $data               = [];
        CodigosLibros::select('codigo', 'estado_liquidacion')
            ->where('bc_institucion', $id_institucion)
            ->where('bc_periodo', $id_periodo)
            ->where('estado_liquidacion','<>','0')
            ->chunk(1000, function ($chunk) use (&$data) { foreach ($chunk as $item) { $data[] = $item; } });
        return $data;
    }
    //API:GET/n_verificacion?getAllCodigosIndividualesContrato=1&contrato=C-C23-0000053-LJ&num_verificacion=1&verificacion_id=2157&devueltos=0
    public function getAllCodigosIndividualesContrato(Request $request){
        $verif          = "verif".$request->num_verificacion;
        $verificacion_id = $request->verificacion_id;
        $contrato       = $request->contrato;
        $query          = $this->verificacionRepository->getAllCodigosIndividualesContrato($contrato,$verif,$verificacion_id);
        //si $query es length mayor a 0 ; si devueltos es 1 excluyo la propiedad liquidacion donde tenga el valor codigo devuelto
        $query          = collect($query);
        if($request->devueltos == 1){
            $query = $query->where('liquidacion','<>','codigo devuelto')->values();
        }
        return $query;
    }
}
