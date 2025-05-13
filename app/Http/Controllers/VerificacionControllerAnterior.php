<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionHeader;
use App\Models\HistoricoCodigos;
use App\Models\Pedidos;
use App\Models\Temporada;
use App\Models\TemporadaVerificacionHistorico;
use App\Models\Verificacion;
use App\Models\VerificacionHasInstitucion;
use App\Models\VerificacionHistoricoCambios;
use App\Models\NotificacionGeneral;
use App\Models\Usuario;
use App\Repositories\Codigos\CodigosRepository;
use App\Repositories\pedidos\NotificacionRepository;
use App\Repositories\pedidos\VerificacionRepository;
use App\Traits\Pedidos\TraitPedidosGeneral;
use App\Traits\Verificacion\TraitVerificacionGeneral;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Cache;
use Pusher\Pusher;

class VerificacionControllerAnterior extends Controller
{

    use TraitPedidosGeneral;
    use TraitVerificacionGeneral;
    public $verificacionRepository;
    protected $codigoRepository;
    protected $NotificacionRepository;
     //contructor
     public function __construct(VerificacionRepository $verificacionRepository, CodigosRepository $codigoRepository, NotificacionRepository $NotificacionRepository)
     {
        $this->verificacionRepository   = $verificacionRepository;
        $this->codigoRepository         = $codigoRepository;
        $this->NotificacionRepository   = $NotificacionRepository;
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

           $archivosRevison = $this->obtenerArchivosRevision($item->id);
           $archivosVerificaciones = $this->obtenerArchivosVerificaciones($item->id);
           $archivosFacturas = $this->obtenerArchivosFacturas($item->id);
           $observacion = $this->obtenerObservacion($item->id);

           $datos [$contador] = (Object)[
            "id"                            => $item->id,
            "num_verificacion"              => $item->num_verificacion,
            "fecha_inicio"                  => $item->fecha_inicio,
            "fecha_fin"                     => $item->fecha_fin,
            "estado"                        => $item->estado,
            "estado_revision"               => $item->estado_revision,
            "contrato"                      => $item->contrato,
            "nuevo"                         => $item->nuevo,
            "file_evidencia"                => $archivosVerificaciones,
            "observacion"                   => $observacion,
            "valor_liquidacion"             => $item->valor_liquidacion,
            // "fecha_subir_evidencia"         => $item->fecha_subir_evidencia,
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
            // "fecha_subir_factura"           => $item->fecha_subir_factura,
            "file_factura"                  => $archivosFacturas,
            "files_revision"                => $archivosRevison,
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
         //para traer todos los codigos new
         if($request->getAllCodigosXContrato_new){
            return $this->getAllCodigosXContrato_new($request);
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
            $plus                   = $request->plus;
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
                        end) as verificacion
                    FROM codigoslibros ci
                    WHERE ci.codigo = c.codigo
                ) AS verificacion,
                c.verif1,c.verif2,c.verif3,c.verif4,c.verif5,
                c.quitar_de_reporte
            '))
             ->where($columnaVerificacion, $verificacion_id)
             ->where('contrato', $request->contrato)
             ->where('prueba_diagnostica', '0')
             ->where('libro_idlibro', $request->libro_id)
             ->where('estado_liquidacion', '<>', '3')
             ->where('plus', $plus)
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
     //api:get/n_verificacion?getAllCodigosXContrato=1&periodo_id=25&institucion_id=1611&porInstitucion=1&sinPrecio=1
     public function getAllCodigosXContrato($request){
        try{
            //limpiar cache
            Cache::flush();
            $institucion_id         = $request->institucion_id;
            $periodo                = $request->periodo_id;
            $IdVerificacion         = $request->IdVerificacion;
            $contrato               = $request->contrato;
            $detalles               = $this->codigoRepository->getCodigosIndividuales($request);
            $getCombos              = $this->codigoRepository->getCombos();
            $sinPrecio              = $request->sinPrecio;
            //formData filtrar del detalles los codigos que tenga combo diferente de null y codigo_combo diferente de null
            $formData               = collect($detalles)->filter(function ($item) {
                return $item->combo != null && $item->codigo_combo != null && ($item->quitar_de_reporte == 0 || $item->quitar_de_reporte == null);
            })->values();
            $getAgrupadoCombos      = $this->codigoRepository->getAgrupadoCombos($formData,$getCombos);
            //unir $detalles con $getAgrupadoCombos
            $detalles               = collect($detalles)->merge($getAgrupadoCombos);
            if($sinPrecio){
                $id_pedido = 0;
                $getPedigo = DB::SELECT("SELECT * FROM pedidos p
                WHERE p.id_institucion = '$institucion_id'
                AND p.id_periodo = '$periodo'
                AND p.estado = '1'");
                if(count($getPedigo) > 0){
                    $id_pedido = $getPedigo[0]->id_pedido;
                }
                foreach($detalles as $item){
                    $item->id_pedido = $id_pedido;
                }
                return $detalles;
            }
            $datos                  = [];
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
                    AND f.id_libro      = '$item->libro_idReal'
                    AND f.id_periodo    = '$periodo'");
                }else{
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
                    "estado_liquidacion"        => $item->estado_liquidacion ?? null,
                    "estado"                    => $item->estado ?? null,
                    "bc_estado"                 => $item->bc_estado?? null,
                    "venta_estado"              => $item->venta_estado?? null,
                    "liquidado_regalado"        => $item->liquidado_regalado?? null,
                    "bc_institucion"            => $item->bc_institucion?? null,
                    "contrato"                  => $item->contrato?? null,
                    "venta_lista_institucion"   => $item->venta_lista_institucion?? null,
                    "plus"                      => $item->plus?? null,
                    'quitar_de_reporte'         => $item->quitar_de_reporte?? null,
                    'combo'                     => $item->combo?? null,
                    'codigo_combo'              => $item->codigo_combo?? null,
                    'tipo_codigo'               => $item->tipo_codigo,
                    'codigos_combos'            => $item->codigos_combos?? null,
                    'cantidad_combos'           => $item->cantidad_combos?? null,
                    'hijos'                     => $item->hijos?? null,
                    'cantidad_items'            => $item->cantidad_items?? null,
                    'cantidad_subitems'         => $item->cantidad_subitems?? null,
                ];
                $contador++;
            }
            return $datos;
        }
        catch(\Exception $e){
            return ["status"=>"0","message"=> $e->getMessage(), "error" => $e->getLine() ];
        }
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
       c.verif5
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
    // public function solicitarVerificacion(Request $request){
    //     $fechaActual = null;
    //     $fechaActual = date('Y-m-d H:i:s');
    //     $user_created  = $request->user_created;
    //     $contrato      = $request->contrato;
    //     $pedidos = Pedidos::where('contrato_generado', $contrato)->get();
    //     if(count($pedidos) > 0){
    //         $periodo = $pedidos[0]->id_periodo;
    //         $id_pedido = $pedidos[0]->id;
    //     }else{
    //         return ["status"=>"0","message"=>"No existe el pedido"];
    //     }
    //     DB::UPDATE("UPDATE pedidos SET estado_verificacion = '1' , fecha_solicita_verificacion = '$fechaActual' WHERE contrato_generado = '$request->contrato'");
    //     //registrar trazabilidad
    //     //validar que no este registrado
    //     $query = DB::SELECT("SELECT * FROM temporadas_verificacion_historico th
    //     WHERE th.contrato = '$request->contrato'
    //     AND th.estado = '1'");
    //     if(empty($query)){
    //         $trazabilidad = new TemporadaVerificacionHistorico();
    //         $trazabilidad->contrato                     = $contrato;
    //         $trazabilidad->fecha_solicita_verificacion  = $fechaActual;
    //         $trazabilidad->estado                       = 1;
    //         $trazabilidad->save();
    //     }
    //     //notificacion
    //     $formData = (Object)[
    //         'nombre'        => 'Solicitud de Verificación',
    //         'descripcion'   => '',
    //         'tipo'          => '1',
    //         'user_created'  => $user_created,
    //         'id_periodo'    => $periodo,
    //         'id_padre'      => $id_pedido,
    //     ];
    //     $this->verificacionRepository->save_notificacion($formData);
    // }

    public function solicitarVerificacion(Request $request){
        DB::beginTransaction(); // Inicia la transacción
        try {
            $fechaActual    = date('Y-m-d H:i:s');
            $user_created   = $request->user_created;
            $contrato       = $request->contrato;
            $id_pedido      = 0;
            $getUser = Usuario::find($request->user_created);
        
            // Obtener los pedidos
            $pedidos = Pedidos::where('contrato_generado', $contrato)->get();
            
            if (count($pedidos) > 0) {
                $periodo = $pedidos[0]->id_periodo;
                $id_pedido = $pedidos[0]->id_pedido;
            } else {
                return ["status" => "0", "message" => "No existe el pedido"];
            }
            
            // Actualizar el estado de verificación
            DB::update("UPDATE pedidos SET estado_verificacion = '1', fecha_solicita_verificacion = ? WHERE contrato_generado = ?", [$fechaActual, $contrato]);
            
            // Verificar si ya está registrada la trazabilidad
            $query = DB::select("SELECT * FROM temporadas_verificacion_historico th WHERE th.contrato = ? AND th.estado = '1'", [$contrato]);
            
            if (empty($query)) {
                // Crear nueva trazabilidad
                $trazabilidad = new TemporadaVerificacionHistorico();
                $trazabilidad->contrato = $contrato;
                $trazabilidad->fecha_solicita_verificacion = $fechaActual;
                $trazabilidad->estado = 1;
                $trazabilidad->save();
            }
            
            // Registrar notificación
            $formData = (Object)[
                'nombre'        => 'Solicitud de Verificación',
                'descripcion'   => '',
                'tipo'          => '1',
                'user_created'  => $user_created,
                'id_periodo'    => $periodo,
                'id_padre'      => $id_pedido,
            ];
            $notificacion = $this->verificacionRepository->save_notificacion($formData);
            $channel = 'admin.notifications_verificaciones';
            $event = 'NewNotification';
            $data = [
                'message' => 'Nueva notificación',
            ];
            // notificacion en pusher
            $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);

            DB::commit(); // Confirmar transacción si todo ha ido bien
            return ["status" => "1", "message" => "Solicitud de Verificación guardada correctamente"];
        } catch (\Exception $e) {
            DB::rollBack(); // Deshacer cambios si algo falla
            return ["status" => "0", "message" => "Error: " . $e->getMessage()];
        }
    }

    //api:get/notificacionesVerificaciones
    public function notificacionesVerificaciones(Request $request)
    {
        $idFacturador = $request->input("idFacturador", 0);
        // Consulta secundaria
        $notificacionFactura = DB::table('notificaciones_general')
            // ->where('tipo', '=', '0')
            ->where('estado', '=', '0')
            ->select('notificaciones_general.*', 'created_at as fecha_solicita')
            ->get()
            ->map(function ($item) use ($request,$idFacturador) {
                //SUBIR FACTURA
                if ($item->tipo == '0' || $item->tipo == '3') {
                    $padre = Verificacion::from('verificaciones as v')
                        ->where('v.id', $item->id_padre)
                        ->leftJoin('pedidos as p', 'p.contrato_generado', '=', 'v.contrato')
                        ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'p.id_institucion')
                        ->leftJoin('usuario as u', 'u.idusuario', '=', 'i.asesor_id')
                        ->leftJoin('periodoescolar as pe', 'pe.idperiodoescolar', '=', 'p.id_periodo')
                        ->leftJoin('ciudad as c', 'c.idciudad', '=', 'i.ciudad_id')
                        ->select(
                            'v.*',
                            'p.id_asesor',
                            'i.nombreInstitucion',
                            DB::raw('CONCAT(u.nombres, " ", u.apellidos) as asesor'),
                            'c.nombre as ciudad',
                            'pe.region_idregion'
                        )
                        ->first();

                    // Asignamos los valores al item
                    $item->contrato_generado    = $padre ? $padre->contrato : null;
                    $item->asesor               = $padre ? $padre->asesor : null;
                    $item->ciudad               = $padre ? $padre->ciudad : null;
                    $item->region_idregion      = $padre ? $padre->region_idregion : null;
                    $item->id_asesor            = $padre ? $padre->id_asesor : null;
                    $item->id_institucion       = $padre ? $padre->id_institucion : null;
                    $item->nombreInstitucion    = $padre ? $padre->nombreInstitucion : null;
                }
                //SOLICITAR VERIFICACION
                if ($item->tipo == '1') {
                    $padre = Pedidos::from('pedidos as p')
                        ->where('p.id_pedido', $item->id_padre)
                        ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'p.id_institucion')
                        ->leftJoin('usuario as u', 'u.idusuario', '=', 'i.asesor_id')
                        ->leftJoin('periodoescolar as pe', 'pe.idperiodoescolar', '=', 'p.id_periodo')
                        ->leftJoin('ciudad as c', 'c.idciudad', '=', 'i.ciudad_id')
                        ->select(
                            'p.contrato_generado',
                            'p.tipo_venta_descr',
                            'p.id_institucion',
                            'p.id_asesor',
                            'i.nombreInstitucion',
                            DB::raw('CONCAT(u.nombres, " ", u.apellidos) as asesor'),
                            'c.nombre as ciudad',
                            'pe.region_idregion'
                        )
                        ->first();

                    // Asignamos los valores al item
                    $item->contrato_generado    = $padre ? $padre->contrato_generado : null;
                    $item->asesor               = $padre ? $padre->asesor : null;
                    $item->ciudad               = $padre ? $padre->ciudad : null;
                    $item->region_idregion      = $padre ? $padre->region_idregion : null;
                    $item->id_asesor            = $padre ? $padre->id_asesor : null;
                    $item->id_institucion       = $padre ? $padre->id_institucion : null;
                    $item->nombreInstitucion    = $padre ? $padre->nombreInstitucion : null;
                    $item->tipo_venta_descr     = $padre ? $padre->tipo_venta_descr : null;
                    // Validación de facturador
                    if ($idFacturador) {
                        $validacionFacturador = DB::table('pedidos_asesores_facturador')
                            ->where('id_facturador', $idFacturador)
                            ->where('id_asesor', $padre->id_asesor)
                            ->exists();

                        // Si no existe el registro, devolvemos null para no incluirlo en el array final
                        if (!$validacionFacturador) {
                            return null;  // Devolver null aquí eliminará el item en el siguiente filtro
                        }
                    }
                }
                //Subida evidencia verificacion
                if($item->tipo == '2' ){
                    if($request->idAsesor){
                        $padre = Verificacion::from('verificaciones as v')
                        ->where('v.id', $item->id_padre)
                        ->where('i.asesor_id', $request->idAsesor)
                        ->leftJoin('pedidos as p', 'p.contrato_generado', '=', 'v.contrato')
                        ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'p.id_institucion')
                        ->leftJoin('usuario as u', 'u.idusuario', '=', 'i.asesor_id')
                        ->leftJoin('periodoescolar as pe', 'pe.idperiodoescolar', '=', 'p.id_periodo')
                        ->leftJoin('ciudad as c', 'c.idciudad', '=', 'i.ciudad_id')
                        ->select(
                            'v.*',
                            'p.id_asesor',
                            'i.nombreInstitucion',
                            DB::raw('CONCAT(u.nombres, " ", u.apellidos) as asesor'),
                            'c.nombre as ciudad',
                            'pe.region_idregion'
                        )
                        ->first();
                    }else{
                        $padre = Verificacion::from('verificaciones as v')
                        ->where('v.id', $item->id_padre)
                        ->leftJoin('pedidos as p', 'p.contrato_generado', '=', 'v.contrato')
                        ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'p.id_institucion')
                        ->leftJoin('usuario as u', 'u.idusuario', '=', 'i.asesor_id')
                        ->leftJoin('periodoescolar as pe', 'pe.idperiodoescolar', '=', 'p.id_periodo')
                        ->leftJoin('ciudad as c', 'c.idciudad', '=', 'i.ciudad_id')
                        ->select(
                            'v.*',
                            'p.id_asesor',
                            'i.nombreInstitucion',
                            DB::raw('CONCAT(u.nombres, " ", u.apellidos) as asesor'),
                            'c.nombre as ciudad',
                            'pe.region_idregion'
                        )
                        ->first();
                    }

                    // Asignamos los valores al item
                    $item->contrato_generado    = $padre ? $padre->contrato : null;
                    $item->asesor               = $padre ? $padre->asesor : null;
                    $item->ciudad               = $padre ? $padre->ciudad : null;
                    $item->region_idregion      = $padre ? $padre->region_idregion : null;
                    $item->id_asesor            = $padre ? $padre->id_asesor : null;
                    $item->id_institucion       = $padre ? $padre->id_institucion : null;
                    $item->nombreInstitucion    = $padre ? $padre->nombreInstitucion : null;
                }
                if ($item->tipo == '4') {
                    $padre = DB::TABLE('f_proforma as p')
                    ->where('p.id', $item->id_padre)
                    ->leftJoin('usuario as u', 'u.idusuario', '=', 'p.usuario_solicitud')
                    ->leftJoin('f_contratos_agrupados as fc', 'fc.ca_codigo_agrupado', '=', 'p.idPuntoventa')
                    ->leftJoin('periodoescolar as pe', 'pe.idperiodoescolar', '=', 'fc.id_periodo')
                    ->select(
                        'p.*',
                        'p.usuario_solicitud as id_asesor',
                        'fc.ca_descripcion',
                        DB::raw('CONCAT(u.nombres, " ", u.apellidos) as asesor')
                        ,'pe.periodoescolar'
                    )
                    ->first();

                    // Asignamos los valores al item
                    $item->asesor               = $padre ? $padre->asesor : null;
                    $item->id_asesor            = $padre ? $padre->id_asesor : null;
                    $item->descripcion          = $padre ? $padre->ca_descripcion : null;
                    $item->agrupado             = $padre ? $padre->idPuntoventa : null;
                    $item->periodo              = $padre ? $padre->periodoescolar : null;
                }
                if($item->tipo == '5'){
                    $padre = CodigosLibrosDevolucionHeader::from('codigoslibros_devolucion_header as v')
                    ->where('v.id', $item->id_padre)
                    ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'v.id_cliente')
                    ->leftJoin('usuario as u', 'u.idusuario', '=', 'i.asesor_id')
                    ->leftJoin('periodoescolar as pe', 'pe.idperiodoescolar', '=', 'v.periodo_id')
                    ->leftJoin('ciudad as c', 'c.idciudad', '=', 'i.ciudad_id')
                    ->select(
                        'v.*',
                        'i.idInstitucion',
                        'i.nombreInstitucion',
                        DB::raw('CONCAT(u.nombres, " ", u.apellidos) as asesor'),
                        'c.nombre as ciudad',
                        'pe.region_idregion'
                    )
                    ->first();

                    // Asignamos los valores al item
                    $item->contrato_generado    = $padre ? $padre->contrato : null;
                    $item->asesor               = $padre ? $padre->asesor : null;
                    $item->ciudad               = $padre ? $padre->ciudad : null;
                    $item->region_idregion      = $padre ? $padre->region_idregion : null;
                    $item->id_asesor            = 0;
                    $item->id_institucion       = $padre ? $padre->idInstitucion : null;
                    $item->nombreInstitucion    = $padre ? $padre->nombreInstitucion : null;
                }
                return $item;
            })
            ->toArray(); // Convertimos el resultado a array

        // ordenamos por fecha_solicita
        $resultado = collect($notificacionFactura)
            ->sortByDesc('fecha_solicita') // Ordenamos por fecha_solicita de forma descendente
            ->values()
            ->all(); // Convertimos de nuevo a array simple

        return response()->json($resultado);
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
            c.nombre AS ciudad, p.id_pedido
            FROM temporadas_verificacion_historico th
            LEFT JOIN temporadas t ON th.contrato = t.contrato
            LEFT JOIN usuario u ON t.id_asesor = u.idusuario
            LEFT JOIN institucion i ON t.idInstitucion = i.idInstitucion
            LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
            LEFT JOIN pedidos p ON th.contrato = p.contrato_generado
            ORDER BY th.id DESC
        ");
        return $query;
    }
    

    public function getVerificacionesRevision($request)
    {
        $periodo = $request->periodo;
    
        // Realizar la consulta principal para obtener la información
        $verificaciones = DB::table('verificaciones as v')
            ->select(
                'v.num_verificacion',
                'v.id',
                'v.contrato',
                'v.fecha_subir_evidencia',
                'v.file_evidencia',
                'i.nombreInstitucion',
                'v.fecha_subir_factura',
                'v.file_factura',
                'v.ifnotificado',
                'p.id_periodo',
                'v.ifaprobadoGerencia',
                'v.fecha_aprobacionGerencia',
                'p.id_pedido',
                'p.valor_aprobo_gerencia_verificacion',
                'v.valor_liquidacion',
                DB::raw('CONCAT(u.nombres, " ", u.apellidos) as asesor'),
                'v.estado_revision'
            )
            ->join('pedidos as p', 'p.contrato_generado', '=', 'v.contrato')
            ->join('institucion as i', 'i.idInstitucion', '=', 'p.id_institucion')
            ->join('usuario as u', 'u.idusuario', '=', 'i.asesor_id')
            ->leftJoin('temporadas_verificacion_historico as h', 'h.contrato', '=', 'v.contrato')
            ->where('p.id_periodo', $periodo)
            ->where('p.tipo', 0)
            ->groupBy(
                'v.num_verificacion',
                'v.id',
                'v.contrato',
                'v.fecha_subir_evidencia',
                'v.file_evidencia',
                'i.nombreInstitucion',
                'v.fecha_subir_factura',
                'v.file_factura',
                'v.ifnotificado',
                'p.id_periodo',
                'v.ifaprobadoGerencia',
                'v.fecha_aprobacionGerencia',
                'p.id_pedido',
                'p.valor_aprobo_gerencia_verificacion',
                'v.valor_liquidacion',
                'u.nombres',
                'u.apellidos',
                'v.estado_revision',
            )
            ->orderByDesc('v.id')
            ->orderBy('v.contrato')
            ->get();
    
        // Asignar solicitudes a las verificaciones
        foreach ($verificaciones as $key => $value) {
            // Buscamos la solicitud correspondiente
            $solicitudes = DB::table('temporadas_verificacion_historico')
                ->where('contrato', $value->contrato)
                ->get();
    
            $posicionArray = $value->num_verificacion - 1; // Número de verificación - 1 para índice de array
            $value->solicitudes = isset($solicitudes[$posicionArray]) ? [$solicitudes[$posicionArray]] : [];
        }
    
        // Agrupar por contrato y aplicar filtros
        $verificacionesAgrupadas = $verificaciones->groupBy('contrato')->map(function ($items, $contrato) {
            $ifaprobadoGerenciaPadre = $items->contains('ifaprobadoGerencia', 0) ? 0 : 1;
    
            // Obtener la fecha más reciente de la aprobación general
            $fechaAprobadoGeneral = $items->filter(function ($item) {
                return !is_null($item->fecha_aprobacionGerencia);
            })->max('fecha_aprobacionGerencia');
    
            // Filtrar las verificaciones que tienen solicitudes
            $verificacionesFiltradas = $items->filter(function ($item) {
                return !empty($item->solicitudes);
            })->sortBy('num_verificacion');
    
            // Contadores para las solicitudes revisadas y no revisadas
            $totalSolicitudesRevisadas = $verificacionesFiltradas->filter(function ($item) {
                return $item->estado_revision == 1;
            })->count();
    
            $totalSolicitudesNoRevisadas = $verificacionesFiltradas->filter(function ($item) {
                return $item->estado_revision == 0;
            })->count();
    
            // Agregar los contadores a la respuesta
            return [
                'contrato' => $contrato,
                'nombreInstitucion' => $items->first()->nombreInstitucion,
                'asesor' => $items->first()->asesor,
                'id_pedido' => $items->first()->id_pedido,
                'id_periodo' => $items->first()->id_periodo,
                'totalContratos' => $items->count(), // Contador de contratos
                'totalSolicitudesRevisadas' => $totalSolicitudesRevisadas, // Contador de solicitudes revisadas
                'totalSolicitudesNoRevisadas' => $totalSolicitudesNoRevisadas, // Contador de solicitudes no revisadas
                'verificaciones' => $verificacionesFiltradas->map(function ($item) {
                    return [
                        'num_verificacion' => $item->num_verificacion,
                        'id' => $item->id,
                        'fecha_subir_evidencia' => $item->fecha_subir_evidencia,
                        'file_evidencia' => $item->file_evidencia,
                        'fecha_subir_factura' => $item->fecha_subir_factura,
                        'file_factura' => $item->file_factura,
                        'ifnotificado' => $item->ifnotificado,
                        'id_pedido' => $item->id_pedido,
                        'solicitudes' => $item->solicitudes,
                        'estado_revision' => $item->estado_revision,
                    ];
                })->values(), // Reindexamos el array
            ];
        })->values();
    
        // Retornar la respuesta en formato JSON
        return response()->json($verificacionesAgrupadas);
    }

    
    public function getVerificacionesRevisionCount()
    {
        $papa = DB::SELECT("SELECT DISTINCT t.id_periodo FROM verificaciones v
            LEFT JOIN temporadas t ON v.contrato = t.contrato
            WHERE v.estado = 0
            AND v.estado_revision = 1
            ORDER BY t.id_periodo DESC
        ");
    
        foreach ($papa as $item) {
            $periodo = DB::table('periodoescolar')
                ->select('descripcion', 'idperiodoescolar')
                ->where('idperiodoescolar', $item->id_periodo)
                ->first();
            
            if ($periodo) {
                $item->descripcion = $periodo->descripcion;
                $item->idperiodoescolar = $periodo->idperiodoescolar;
            }
    
            $countNoRevisadas =  DB::SELECT("SELECT DISTINCT v.contrato FROM verificaciones v
            LEFT JOIN temporadas t ON v.contrato = t.contrato
            WHERE v.estado = 0
            AND v.estado_revision = 0
            AND t.id_periodo = $item->id_periodo
            ;
            ");
    
            $countRevisadas = DB::SELECT("SELECT DISTINCT v.contrato FROM verificaciones v
            LEFT JOIN temporadas t ON v.contrato = t.contrato
            WHERE v.estado = 0
            AND v.estado_revision = 1
            AND t.id_periodo = $item->id_periodo
        ;
        ");
    
            $item->totalVerificacionesNoRevisadas = count($countNoRevisadas);
            $item->totalVerificacionesRevisadas = count($countRevisadas);
        }
    
        return $papa;
    }
    
    
    private function obtenerArchivosRevision($idVerificacion) {
        return DB::table('evidencia_global_files as e')
            ->join('usuario as u', 'u.idusuario', '=', 'e.user_created')
            ->select('e.egf_id', 'e.egf_archivo', 'e.egf_url', 'e.egf_tamano', 'e.created_at', 'e.updated_at'
            , DB::raw('CONCAT(u.nombres, " ", u.apellidos) as creador'))
            ->where('egft_id', 2) // Tipo de archivo
            ->where('egf_referencia', $idVerificacion) // Relación con la verificación
            ->get();
    }
    private function obtenerArchivosVerificaciones($idVerificacion) {
        $datos = DB::table('evidencia_global_files as e')
            ->leftjoin('usuario as u', 'u.idusuario', '=', 'e.user_created')
            ->select('e.egf_id', 'e.egf_archivo', 'e.egf_url', 'e.egf_tamano', 'e.created_at', 'e.updated_at'
            , DB::raw('CONCAT(u.nombres, " ", u.apellidos) as creador'))
            ->where('egft_id', 3) // Tipo de archivo
            ->where('egf_referencia', $idVerificacion) // Relación con la verificación
            ->get()
            ->toArray(); // Convertimos la colección a array
    
        // Si `$archivo` tiene valor, agregamos un nuevo elemento
        // if ($archivo) {
        //     $nuevoDato = [
        //         'egf_id' => null,  // Debe ser null
        //         'egf_archivo' => $archivo,
        //         'egf_url' => 'archivos/verificaciones/evidencia/',
        //         'egf_tamano' => null, // Debe ser null
        //         'created_at' => $fecha,
        //         'updated_at' => $fecha,
        //         'creador' => null
        //     ];
    
        //     // Fusionamos los datos existentes con el nuevo
        //     $datos = array_merge($datos, [$nuevoDato]);
        // }
    
        return $datos;
    }
    
    private function obtenerArchivosFacturas($idVerificacion) {
        $datos = DB::table('evidencia_global_files as e')
        ->leftjoin('usuario as u', 'u.idusuario', '=', 'e.user_created')
        ->select('e.egf_id', 'e.egf_archivo', 'e.egf_url', 'e.egf_tamano', 'e.created_at', 'e.updated_at'
        , DB::raw('CONCAT(u.nombres, " ", u.apellidos) as creador'))
        ->where('egft_id', 4) // Tipo de archivo
        ->where('egf_referencia', $idVerificacion) // Relación con la verificación
        ->get()
        ->toArray(); // Convertimos la colección a array

        // // Si `$archivo` tiene valor, agregamos un nuevo elemento
        // if ($archivo) {
        //     $nuevoDato = [
        //         'egf_id' => null,  // Debe ser null
        //         'egf_archivo' => $archivo,
        //         'egf_url' => 'archivos/verificaciones/evidencia/',
        //         'egf_tamano' => null, // Debe ser null
        //         'created_at' => $fecha,
        //         'updated_at' => $fecha,
        //         'creador' => null
        //     ];

        //     // Fusionamos los datos existentes con el nuevo
        //     $datos = array_merge($datos, [$nuevoDato]);
        // }

        return $datos;
    }

    public function obtenerObservacion($id) {
        $datos = DB::table('observaciones as o')
            ->leftjoin('usuario as u', 'u.idusuario', '=', 'o.user_created')
            ->leftjoin('usuario as a', 'a.idusuario', '=', 'o.user_updated')
            ->select(
                'o.*', 
                DB::raw('CONCAT(u.nombres, " ", u.apellidos) as creador'), 
                DB::raw('CONCAT(a.nombres, " ", a.apellidos) as modificador')
            )
            ->where('o.identificador', $id)
            ->where('o.tipo_observacion', 0)
            ->get();
    
        return $datos;
    }
    
    

    public function getArchivosRevision(Request $request) {
        // Validar que el parámetro `idVerificacion` esté presente en la solicitud
        $idVerificacion = $request->query('idVerificacion'); 
    
        if (!$idVerificacion) {
            return response()->json(["error" => "ID de verificación no proporcionado"], 400);
        }
    
        if($request->tipo == 'factura'){
            $archivos = $this->obtenerArchivosFacturas($idVerificacion);
        }
        if($request->tipo == 'verificacion'){
            $archivos = $this->obtenerArchivosVerificaciones($idVerificacion);
        }
        if($request->tipo == 'revision'){
            $archivos = $this->obtenerArchivosRevision($idVerificacion);
        }
    
        return response()->json($archivos);
    }
    
    //api:post/saveObservacionRevision
    public function saveObservacionRevision(Request $request) {
        // Verificar si el ID de la verificación existe
        $verificacion = Verificacion::find($request->id);
        if (!$verificacion) {
            return ["status" => "0", "message" => "Verificación no encontrada"];
        }
    
        // Preparar la observación
        $observacion_revision = ($request->observacion == null || $request->observacion == "null") ? null : $request->observacion;
    
        // Iniciar una transacción para asegurar que los cambios sean atómicos
        DB::beginTransaction();
        try {
            // Actualizar el campo en el modelo
            $verificacion->observacion_revision = $observacion_revision;
            $verificacion->save();
            
            if ($request->contrato) {
                // Usar consultas preparadas para evitar SQL Injection
                $dato = DB::table('verificaciones as v')
                    ->join('temporadas as t', 'v.contrato', '=', 't.contrato')
                    ->select('t.idInstitucion', 't.id_periodo')
                    ->where('v.estado', 0)
                    ->where('v.estado_revision', 1)
                    ->where('v.id', $request->identificador)
                    ->where('v.contrato', $request->contrato)
                    ->first();  // Usamos first() para obtener un solo registro
    
                // Verificar si se obtuvo el dato
                if (!$dato) {
                    return ["status" => "0", "message" => "No se encontró la información de temporada"];
                }
    
                // Insertar datos en la tabla 'novedades_institucion'
                $ingreso = DB::table('novedades_institucion')->insertGetId([
                    'idInstitucion' => $dato->idInstitucion,
                    'id_periodo'    => $dato->id_periodo,
                    'id_editor'     => $request->usuario,
                    'novedades'     => $observacion_revision,
                    'estado'        => '0',
                ]);
            }
    
            // Si la actualización fue exitosa, commit
            DB::commit();
    
            return ["status" => "1", "message" => "Se guardó correctamente"];
        } catch (\Exception $e) {
            // Si algo sale mal, rollback
            DB::rollback();
            return ["status" => "0", "message" => "No se pudo guardar. Error: " . $e->getMessage()];
        }
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

            $archivosRevison = $this->obtenerArchivosRevision($item->id);
            $archivosVerificaciones = $this->obtenerArchivosVerificaciones($item->id);
            $archivosFacturas = $this->obtenerArchivosFacturas($item->id);
            $observacion = $this->obtenerObservacion($item->id);

            $datos[$key] = [
                "id"                                        => $item->id,
                "num_verificacion"                          => $item->num_verificacion,
                "fecha_inicio"                              => $item->fecha_inicio,
                "fecha_fin"                                 => $item->fecha_fin,
                "estado"                                    => $item->estado,
                "estado_revision"                           => $item->estado_revision,
                "contrato"                                  => $item->contrato,
                "nuevo"                                     => $item->nuevo,
                "file_evidencia"                            => $archivosVerificaciones,
                "observacion"                               => $observacion,
                "valor_liquidacion"                         => $item->valor_liquidacion,
                "fecha_subir_evidencia"                     => $item->fecha_subir_evidencia,
                "cobro_venta_directa"                       => $item->cobro_venta_directa,
                "tipoPago"                                  => $item->tipoPago,
                "personalizado"                             => $item->personalizado,
                "totalDescuento"                            => $item->totalDescuento,
                "totalDescuentoVenta"                       => $item->totalDescuentoVenta,
                "abonado"                                   => $item->abonado,
                "estado_pago"                               => $item->estado_pago,
                "venta_real"                                => $item->venta_real,
                "venta_real_regalados"                      => $item->venta_real_regalados,
                "permiso_anticipo_deuda"                    => $item->permiso_anticipo_deuda,
                "permiso_convenio"                          => $item->permiso_convenio,
                "permiso_cobro_venta_directa"               => $item->permiso_cobro_venta_directa,
                "permiso_acta_obsequios_100_descuento"      => $item->permiso_acta_obsequios_100_descuento,
                "permiso_acta_obsequios_otros_descuentos"   => $item->permiso_acta_obsequios_otros_descuentos,
                "descuentos"                                => $descuentos,
                "descuentosVenta"                           => $descuentosVenta,
                "otroValoresCancelar"                       => $valorOtrosValores,
                "campo_dinamico"                            => $campo_dinamico,
                "devolucionEscuela"                         => $sumaDevolucionEsc,
                "totalVenta"                                => $item->totalVenta,
                "valoresLiquidadosReporte"                  => $valoresLiquidadosReporte,
                "file_factura"                              => $archivosFacturas,
                "files_revision"                            => $archivosRevison,
            ];
        }
        return $datos;
    }
    //api:post/saveDatosVerificacion
    // public function saveDatosVerificacion(Request $request){
    //     $verificacion =  Verificacion::findOrFail($request->id);
    //     $observacion = "";
    //     if($request->observacion == null || $request->observacion == "null"){
    //         $observacion  = null;
    //     }else{
    //         $observacion = $request->observacion;
    //     }
    //     $verificacion->observacion = $observacion;
    //     $verificacion->save();
    //     if($verificacion){
    //         return ["status" => "1", "message" => "Se guardo correctamente"];
    //     }else{
    //         return ["status" => "0", "message" => "No se pudo guardar"];
    //     }
    // }
    public function guardarObservacion(Request $request)
    {
        // Validar si se proporciona la observación
        if (!$request->has('observacion') || !$request->has('identificador')) {
            return response()->json(["status" => "0", "message" => "Datos faltantes"]);
        }

        $observacion = json_decode($request->observacion);
        $identificador = $request->identificador;
        $tipo_observacion = $request->tipo_observacion ?? 0;
        $usuario = $request->usuario;

        // Iniciar una transacción
        DB::beginTransaction();

        try {
            $ingreso = null; // Inicializar $ingreso como null

            // Si se proporciona un contrato, procesar la inserción en 'novedades_institucion'
            if ($request->contrato) {
                // Usar consultas preparadas para evitar SQL Injection
                $dato = DB::table('verificaciones as v')
                    ->join('temporadas as t', 'v.contrato', '=', 't.contrato')
                    ->select('t.idInstitucion', 't.id_periodo')
                    ->where('v.estado', 0)
                    ->where('v.id', $identificador)
                    ->where('v.contrato', $request->contrato)
                    ->first();  // Usamos first() para obtener un solo registro

                // Verificar si se obtuvo el dato
                if (!$dato) {
                    DB::rollBack(); // Revertir la transacción
                    return response()->json(["status" => "0", "message" => "No se encontró la información de temporada"]);
                }

                // Insertar datos en la tabla 'novedades_institucion'
                $ingreso = DB::table('novedades_institucion')->insertGetId([
                    'idInstitucion'  => $dato->idInstitucion,
                    'id_periodo'     => $dato->id_periodo,
                    'id_editor'      => $usuario,
                    'novedades'      => $observacion->observacion, // Insertar solo el contenido de la observación
                    'estado'         => '0',
                ]);

                // Verificar si la inserción en 'novedades_institucion' fue exitosa
                if (!$ingreso) {
                    DB::rollBack(); // Revertir la transacción
                    return response()->json(["status" => "0", "message" => "Error al guardar la novedad"]);
                }
            } else {
                // Si no se proporciona un contrato, no se puede continuar
                DB::rollBack();
                return response()->json(["status" => "0", "message" => "El contrato es obligatorio"]);
            }

            // Preparar los datos para la inserción en 'observaciones'
            $datosObservacion = [
                'observacion' => $observacion->observacion,
                'identificador' => $identificador,
                'tipo_observacion' => $tipo_observacion,
                'user_created' => $usuario,
                'created_at' => now(),
                'updated_at' => now(),
                'novedad_id' => $ingreso, // $ingreso siempre estará definido aquí
            ];

            // Insertar la nueva observación
            $observacionCreada = DB::table('observaciones')->insertGetId($datosObservacion);

            // Confirmar la transacción
            DB::commit();

            return response()->json(["status" => "1", "message" => "Observación guardada correctamente"]);

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            // Log del error (opcional)
            Log::error('Error al guardar la observación: ' . $e->getMessage());

            return response()->json(["status" => "0", "message" => "Error al guardar la observación"]);
        }
    }

    public function eliminarObservacion(Request $request)
    {
        // Validar si el 'id' está presente en el request
        if (!$request->has('id')) {
            return response()->json(["status" => "0", "message" => "ID no proporcionado"]);
        }
    
        // Iniciar una transacción
        DB::beginTransaction();
    
        try {
            // Obtener el registro de la observación para encontrar el novedad_id
            $observacion = DB::table('observaciones')
                ->where('id', $request->id)
                ->first();
    
            // Verificar si la observación existe
            if (!$observacion) {
                throw new \Exception("No se encontró la observación con el ID proporcionado");
            }
    
            // Obtener el novedad_id de la observación
            $novedad_id = $observacion->novedad_id;
    
            // 1. Eliminar la observación de la tabla 'observaciones'
            DB::table('observaciones')->where('id', $request->id)->delete();
    
            // 2. Eliminar la novedad correspondiente en la tabla 'novedades_institucion'
            if ($novedad_id) {
                DB::table('novedades_institucion')->where('id', $novedad_id)->delete();
            }
    
            // Confirmar la transacción
            DB::commit();
    
            return response()->json(["status" => "1", "message" => "Observación y novedad eliminadas correctamente"]);
    
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
    
            // Log del error (opcional)
            Log::error('Error al eliminar la observación: ' . $e->getMessage());
    
            return response()->json(["status" => "0", "message" => $e->getMessage()]);
        }
    }

    public function actualizarObservacion(Request $request)
    {
        // Validar si los parámetros necesarios están presentes
        if (!$request->has('id') || !$request->has('observacion') || !$request->has('novedad_id')) {
            return response()->json(["status" => "0", "message" => "Datos faltantes: ID, Observación o Novedad ID no proporcionados"]);
        }
    
        // Obtener los datos del request
        $id = $request->id;
        $observacion = $request->observacion;
        $usuario = $request->usuario;
        $novedad_id = $request->novedad_id;
    
        // Iniciar una transacción
        DB::beginTransaction();
    
        try {
            // 1. Actualizar la observación en la tabla 'observaciones'
            $updatedObservacion = DB::table('observaciones')
                ->where('id', $id)
                ->update([
                    'observacion' => $observacion,
                    'user_updated' => $usuario,
                    'updated_at' => now(),
                ]);
    
            // Verificar si la actualización en 'observaciones' fue exitosa
            if (!$updatedObservacion) {
                throw new \Exception("Error al actualizar la observación en la tabla 'observaciones'");
            }
    
            // 2. Obtener la novedad por el ID proporcionado
            $novedad = DB::table('novedades_institucion')
                ->where('id', $novedad_id)
                ->first();
    
            // Verificar si se encontró la novedad
            if (!$novedad) {
                throw new \Exception("No se encontró la novedad con el ID proporcionado");
            }
    
            // 3. Actualizar la novedad en la tabla 'novedades_institucion'
            $updatedNovedad = DB::table('novedades_institucion')
                ->where('id', $novedad_id)
                ->update([
                    'novedades' => $observacion, // Actualizar el campo 'novedades' con la nueva observación
                    'id_editor' => $usuario, // Actualizar el campo 'novedades' con la nueva observación
                    'updated_at' => now(),
                ]);
    
            // Verificar si la actualización en 'novedades_institucion' fue exitosa
            if (!$updatedNovedad) {
                throw new \Exception("Error al actualizar la novedad en la tabla 'novedades_institucion'");
            }
    
            // Confirmar la transacción si todo fue exitoso
            DB::commit();
    
            return response()->json(["status" => "1", "message" => "Observación y novedad actualizadas correctamente"]);
    
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
    
            // Log del error (opcional)
            Log::error('Error en actualizarObservacion: ' . $e->getMessage());
    
            return response()->json(["status" => "0", "message" => $e->getMessage()]);
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
        // $query2 = DB::SELECT("SELECT * FROM pedidos_alcance pa
        // WHERE pa.id_pedido = ?
        // AND pa.estado_alcance = '0'
        // ",[$id_pedido]);
        // if(count($query2) > 0){
        //     return ["status"=>"0", "message" => "El contrato tiene alcances abiertos"];
        // }
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
        try{
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            //transaccion
            DB::beginTransaction();
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
                $id_pedido = $pedido[0]->id_pedido;
                if(count($pedido) == 0){ return ["status"=>"0","message"=>"No existe el contrato"]; }
                //validar que el pedido no tenga alcaces abiertos o activos
                // $query2 = DB::SELECT("SELECT * FROM pedidos_alcance pa WHERE pa.id_pedido = ? AND pa.estado_alcance = '0'",[$id_pedido]);
                // if(count($query2) > 0){ return ["status"=>"0", "message" => "El contrato tiene alcances abiertos"]; }
            if($periodo ==  null || $periodo == 0 || $periodo == ""){ return ["status"=>"0", "message" => "El contrato no tiene asignado a un período"]; }
            //======================FIN VALIDACIONES=========================================================
            else{
                DB::commit();
                // //estadoVerificacion: 0 => ya realizada; 1 =>  la verificacion abierta; 2 => pendientes
                if ($estadoVerificacion == 1) {
                    $result = $this->generateLiquidacionAntes2000($institucion, $periodo, $contrato, $idVerificacionActual, $num_verificacionaActual, $user_created, $id_pedido);
                    // Si la liquidación fue exitosa (status 1), confirmar la transacción
                    if ($result['status'] == 1) {
                        DB::commit();  // Confirmar transacción si la liquidación fue exitosa
                        return $result;  // Retornar el resultado exitoso
                    }
                }
                if ($estadoVerificacion == 2) {
                    $result = $this->generateLiquidacionDespues2000($institucion, $periodo, $contrato, $idVerificacionActual, $num_verificacionaActual, $user_created);
                    // Si la liquidación fue exitosa (status 1), confirmar la transacción
                    if ($result['status'] == 1) {
                        DB::commit();  // Confirmar transacción si la liquidación fue exitosa
                        return $result;  // Retornar el resultado exitoso
                    }
                }
                return ["status" => "0", "message" => "El estado de la verificación no es válido para realizar la liquidación"];
            }
           
        }
        catch(\Exception $e){
            DB::rollBack(); // Deshacer los cambios si ocurre un error
            return ["status"=>"0","message"=>$e->getMessage(), "line" => $e->getLine()];
        }
    }
  
    public function generateLiquidacionAntes2000($institucion, $periodo, $contrato, $idVerificacionActual, $num_verificacionaActual, $user_created, $id_pedido){
        DB::beginTransaction();  // Iniciar transacción
    
        try {
            //traigo la liquidacion actual por cantidad GRUPAL
            $data = $this->getCodigosGrupalLiquidar($institucion, $periodo);
    
            //INVIVIDUAL VERSION 1
            //traigo la liquidacion con los codigos invidivuales con limite de 2000
            $traerCodigosIndividual = $this->getCodigosIndividualLiquidar($institucion, $periodo);
    
            //TRAER LOS CODIGOS REGALADOS
            $arregloRegalados = $this->getRegaladosXLiquidar($institucion, $periodo);
    
            //SI TODO HA SALIDO BIEN TRAEMOS LA DATA
            if (count($data) > 0) {
                //====PROCESO GUARDAR EN FACTURACION=======
                //obtener la fecha actual
                $fechaActual = date('Y-m-d');
    
                //======PARA REALIZAR LA VERIFICACION EN CASO QUE EL CONTRATO YA TENGA VERIFICACIONES====
                $traerNumeroVerificacion = $num_verificacionaActual;
                $traeridVerificacion = $idVerificacionActual;
    
                //Para guardar la verificacion si existe el contrato
                //SI EXCEDE LAS 10 VERIFICACIONES
                $finVerificacion = "no";
                if ($traerNumeroVerificacion > 10) { $finVerificacion = "yes"; }
    
                if ($finVerificacion == "yes") {
                    DB::rollBack(); // Revertir transacción en caso de error
                    return ["status" => "0", "message" => "Ha alcanzado el límite de verificaciones permitidas"];
                } else {
                    //OBTENER LA CANTIDAD DE LA VERIFICACION ACTUAL
                    $this->updateCodigoIndividualInicial($traeridVerificacion, $traerCodigosIndividual, $contrato, $traerNumeroVerificacion, $periodo, $institucion, "liquidacion", $user_created);
    
                    //ACTUALIZAR EN LOS CODIGOS REGALADOS LOS DATOS DE VERIFICACION
                    if (count($arregloRegalados) > 0) {
                        $this->updateCodigoIndividualInicial($traeridVerificacion, $arregloRegalados, $contrato, $traerNumeroVerificacion, $periodo, $institucion, "regalado", $user_created);
                    }
    
                    //Ingresar la liquidacion en la base
                    $this->guardarLiquidacionCodigosDemasiados($data, $traerNumeroVerificacion, $contrato);
    
                    //consultar si todavía existe codigos individuales
                    $codigosFaltante = $this->getCodigosIndividualLiquidar($institucion, $periodo);
    
                    //si no hay códigos faltantes, actualizar el estado de la verificación
                    if (count($codigosFaltante) > 0) {
                        DB::table('verificaciones')
                            ->where('id', $traeridVerificacion)
                            ->update(['estado' => "2"]); // Cambiar estado a "pendiente"
                    } else {
                        //Actualizar la verificación a estado 0 para cerrarla
                        DB::table('verificaciones')
                            ->where('id', $traeridVerificacion)
                            ->update([
                                'fecha_fin' => $fechaActual,
                                'estado' => "0"
                            ]);
    
                        $fecha2 = date('Y-m-d H:i:s');
                        DB::UPDATE("UPDATE pedidos SET estado_verificacion = '0' , fecha_solicita_verificacion = null, permitir_editar_despues_contrato = 0 WHERE contrato_generado = '$contrato'");
                        DB::UPDATE("UPDATE temporadas_verificacion_historico SET estado = '2', fecha_realiza_verificacion = '$fecha2' , id_verificacion = '$traeridVerificacion', usuario_verificacion = '$user_created' WHERE contrato = '$contrato' AND estado = '1'");
    
                        //  Para generar una nueva verificación y que quede abierta
                        $this->saveVerificacion($traerNumeroVerificacion + 1, $contrato);
                        
                        // dejar notificacion en cerrada
                       $this->verificacionRepository->cerrarNotificacion($id_pedido,1);
                       // Pusher
                       $channel = 'admin.notifications_verificaciones';
                       $event = 'NewNotification';
                       $data = [
                           'message' => 'Nueva notificación',
                       ];
                       // notificacion en pusher
                       $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);
                    }
    
                    // Confirmar los cambios realizados en la transacción
                    DB::commit();
    
                    return ['status' => 1, 'guardados' => count($traerCodigosIndividual), "faltante" => count($codigosFaltante)];
                }
            } else {
                DB::rollBack(); // Revertir transacción si no hay datos
                return ["status" => "2", "message" => "No hay datos para liquidar"];
                // return ["status" => "2", "message" => "Se guardó correctamente ya no más libros por liquidar"];
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir la transacción en caso de cualquier error
            return ["status" => "0", "message" => $e->getMessage()];
        }
    }
    
 
    public function generateLiquidacionDespues2000($institucion, $periodo, $contrato, $idVerificacionActual, $num_verificacionaActual, $user_created){
        DB::beginTransaction();  // Iniciar transacción
    
        try {
            // Obtener la fecha actual
            $fechaActual = date('Y-m-d');
            $traerNumeroVerificacion = $num_verificacionaActual;
            $traeridVerificacion = $idVerificacionActual;
    
            // Traer la liquidación con los códigos individuales
            $traerCodigosIndividual = $this->getCodigosIndividualLiquidar($institucion, $periodo);
    
            // Obtener la cantidad de la verificación actual
            $this->updateCodigoIndividualInicial($traeridVerificacion, $traerCodigosIndividual, $contrato, $traerNumeroVerificacion, $periodo, $institucion, "liquidacion", $user_created);
    
            // Consultar si todavía existe códigos individuales
            $codigosFaltante = $this->getCodigosIndividualLiquidar($institucion, $periodo);
    
            // Si existen códigos faltantes, actualizar el estado de la verificación a pendiente
            if (count($codigosFaltante) > 0) {
                DB::table('verificaciones')
                    ->where('id', $traeridVerificacion)
                    ->update([
                        'estado' => "2"  // Estado 2: Pendiente
                    ]);
                
                // Confirmar transacción y devolver respuesta
                DB::commit();
                return ['status' => 1, 'guardados' => count($traerCodigosIndividual), "faltante" => count($codigosFaltante)];
            } else {
                // Actualizar la verificación anterior a estado 0 para cerrarla
                DB::table('verificaciones')
                    ->where('id', $traeridVerificacion)
                    ->update([
                        'fecha_fin' => $fechaActual,
                        'estado' => "0"  // Estado 0: Cerrada
                    ]);
    
                // Actualizar los registros en las tablas relacionadas
                $fecha2 = date('Y-m-d H:i:s');
                DB::UPDATE("UPDATE pedidos SET estado_verificacion = '0', fecha_solicita_verificacion = null WHERE contrato_generado = '$contrato'");
                DB::UPDATE("UPDATE temporadas_verificacion_historico SET estado = '2', fecha_realiza_verificacion = '$fecha2' WHERE contrato = '$contrato' AND estado = '1'");
    
                // Para generar una nueva verificación y que quede abierta
                $this->saveVerificacion($traerNumeroVerificacion + 1, $contrato);
    
                // Confirmar transacción después de todas las operaciones
                DB::commit();
    
                return ['status' => 1, 'guardados' => count($traerCodigosIndividual), "faltante" => count($codigosFaltante)];
            }
        } catch (\Exception $e) {
            // En caso de error, revertir la transacción
            DB::rollBack();
            return ["status" => "0", "message" => $e->getMessage()];
        }
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

    //INICIO METODOS JEYSON
    public function getAllCodigosXContrato_new($request){
        //limpiar cache
        Cache::flush();
        try{
            $periodo                = $request->periodo_id;
            $IdVerificacion         = $request->IdVerificacion;
            $contrato               = $request->contrato;
            $detalles               = $this->codigoRepository->getCodigosIndividuales($request);
            $getCombos              = $this->codigoRepository->getCombos();
            $sinPrecio              = $request->sinPrecio;
            //formData filtrar del detalles los codigos que tenga combo diferente de null y codigo_combo diferente de null
            $formData               = collect($detalles)->filter(function ($item) {
                return $item->combo != null && $item->codigo_combo != null && ($item->quitar_de_reporte == 0 || $item->quitar_de_reporte == null);
            })->values();
            $getAgrupadoCombos      = $this->codigoRepository->getAgrupadoCombos($formData,$getCombos);
            //unir $detalles con $getAgrupadoCombos
            $detalles               = collect($detalles)->merge($getAgrupadoCombos);
            if($sinPrecio){
                return $detalles;
            }
            
            $datos = [];
            $contador = 0;
            foreach($detalles as $key => $item){
                // Busca el pfn_pvp correcto basado en el id_periodo
                $pfn_pvp_result = (float) DB::table('pedidos_formato_new')
                ->where('idperiodoescolar', $periodo)
                ->where('idlibro', $item->libro_idReal)
                ->value('pfn_pvp');
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
                    "precio"                    => $pfn_pvp_result,
                    "estado_liquidacion"        => $item->estado_liquidacion ?? null,
                    "estado"                    => $item->estado ?? null,
                    "bc_estado"                 => $item->bc_estado?? null,
                    "venta_estado"              => $item->venta_estado?? null,
                    "liquidado_regalado"        => $item->liquidado_regalado?? null,
                    "bc_institucion"            => $item->bc_institucion?? null,
                    "contrato"                  => $item->contrato?? null,
                    "venta_lista_institucion"   => $item->venta_lista_institucion?? null,
                    "plus"                      => $item->plus?? null,
                    'quitar_de_reporte'         => $item->quitar_de_reporte?? null,
                    'combo'                     => $item->combo?? null,
                    'codigo_combo'              => $item->codigo_combo?? null,
                    'tipo_codigo'               => $item->tipo_codigo,
                    'codigos_combos'            => $item->codigos_combos?? null,
                    'cantidad_combos'           => $item->cantidad_combos?? null,
                    'hijos'                     => $item->hijos?? null,
                    'cantidad_items'            => $item->cantidad_items?? null,
                    'cantidad_subitems'         => $item->cantidad_subitems?? null,
                ];
                $contador++;
            }
            return $datos;
        }catch(\Exception $e){
            return ["status"=>"0","message"=> $e->getMessage()];
        }

     }
    //FIN METODOS JEYSON
    //api:get/metodosGetVerificaciones
    public function metodosGetVerificaciones(Request $request){
       if($request->getVerificaciones){
           return $this->getVerificaciones($request);
       }
       if($request->getReporteLiquidadosPorLibros){
           return $this->getReporteLiquidadosPorLibros($request);
       }
       if($request->getReportePorAnio){
           return $this->getReportePorAnio($request);
       }
       if($request->getVerificacionesRevision){
            return $this->getVerificacionesRevision($request);
       }
       if($request->getVerificacionesRevisionCount){
            return $this->getVerificacionesRevisionCount($request);
       }
    }
    // api:get/metodosGetVerificaciones?getVerificaciones=1&id_asesor=4179&pendientesNot=1&soloLongitud=1
    public function getVerificaciones($request){
        $id_asesor      = $request->id_asesor;
        $pendientesNot  = $request->pendientesNot;
        $soloLongitud   = $request->soloLongitud;
        $id_periodo     = $request->id_periodo;
        $sinSolicitud   = $request->sinSolicitud;
        $conValores     = $request->conValores;

        $verificaciones = DB::table('verificaciones as v')
        ->select(
            'v.num_verificacion',
            'v.id',
            'v.contrato',
            'v.fecha_subir_evidencia',
            'v.file_evidencia',
            'i.nombreInstitucion',
            'v.fecha_subir_factura',
            'v.file_factura',
            'v.ifnotificado',
            'p.id_periodo',
            'v.ifaprobadoGerencia',
            'v.fecha_aprobacionGerencia',
            'p.id_pedido',
            'p.valor_aprobo_gerencia_verificacion',
            'v.valor_liquidacion',
            DB::raw('CONCAT(u.nombres, " ", u.apellidos) as asesor'),
            DB::raw('
                (SELECT ROUND(SUM(vi.valor_liquidacion), 2)
                FROM verificaciones vi
                WHERE vi.contrato = v.contrato
                AND vi.estado = 0)
                 AS valorLiquidaciones
            '),
            DB::raw('(SELECT COALESCE(ROUND(SUM(l.doc_valor), 2), 0) AS totalPagos
            FROM 1_4_documento_liq l
            WHERE l.id_pedido = p.id_pedido
            AND l.estado = 1
            AND l.tipo_pago_id <> 3
            ) AS totalPagos'),
            DB::raw('
                (SELECT COALESCE(ROUND(SUM(vi.valor_comision), 2), 0)
                FROM verificaciones vi
                WHERE vi.contrato = v.contrato
                AND vi.estado = 0)
                AS valorTotalComision
            '),
            DB::raw('(
                SELECT count(l.doc_valor) AS pagosPorCerrarse
                FROM `1_4_documento_liq` l
                WHERE l.id_pedido = p.id_pedido
                AND l.estado = 0
                AND l.tipo_pago_id <> 3
            ) AS pagosPorCerrarse')
        )
        ->leftJoin('pedidos as p', 'p.contrato_generado', '=', 'v.contrato')
        ->leftJoin('institucion as i', 'i.idInstitucion', '=', 'p.id_institucion')
        ->leftJoin('usuario as u','u.idusuario','=','i.asesor_id')
        ->whereNotNull('v.file_evidencia')
        ->where('p.estado', '1')
        ->when($id_asesor, function ($query) use ($id_asesor) {
            $query->where('i.asesor_id', $id_asesor);
        })
        ->when($pendientesNot, function ($query) {
            $query->where('v.ifnotificado', '0');
        })
        ->when($id_periodo, function ($query) use ($id_periodo) {
            $query->where('p.id_periodo', $id_periodo);
        })
        ->orderByDesc('v.id')
        ->orderBy('v.contrato')
        ->get();



        //si solo se quiere obtener la longitud de la tabla
        if($soloLongitud){
            return count($verificaciones);
        }
        //si no se quiere obtener las solicitudes de verificación
        // if(!$sinSolicitud){
            //obtener solicitudes de verificación
            foreach ($verificaciones as $key => $value) {
                $arraySolicitudes = [];
                $posicionArray = 0;
                $getNumeroVerificacion = $value->num_verificacion;
                $posicionArray = $getNumeroVerificacion - 1;

                $solicitudes = DB::SELECT("SELECT * FROM temporadas_verificacion_historico h WHERE h.contrato = '$value->contrato'");

                if (isset($solicitudes[$posicionArray])) {
                    $arraySolicitudes[0] = $solicitudes[$posicionArray];
                } else {
                    $arraySolicitudes = [];
                }

                $value->solicitudes = $arraySolicitudes;
            }
        // }
        if($conValores){
            //obtener valores de verificación
            // Agrupar por contrato
            $verificacionesAgrupadas = $verificaciones->groupBy('contrato')->map(function ($items, $contrato) {
                // Verificar si alguna verificación tiene ifaprobadoGerencia en 0
                $ifaprobadoGerenciaPadre = $items->contains('ifaprobadoGerencia', 0) ? 0 : 1;
            
                // Obtener la fecha más reciente de los hijos, ignorando los null
                $fechaAprobadoGeneral = $items->filter(function ($item) {
                    return !is_null($item->fecha_aprobacionGerencia); // Filtra los que no son null
                })->max('fecha_aprobacionGerencia'); // Obtiene la fecha máxima
            
                return [
                    'contrato' => $contrato,
                    'nombreInstitucion' => $items->first()->nombreInstitucion,
                    'asesor' => $items->first()->asesor,
                    'id_pedido' => $items->first()->id_pedido,
                    'totalPagos' => $items->first()->totalPagos,
                    'ifaprobadoGerenciaPadre' => $ifaprobadoGerenciaPadre, // Nuevo campo agregado
                    'valorLiquidaciones' => $items->first()->valorLiquidaciones,
                    'valorTotalComision' => $items->first()->valorTotalComision,
                    'valor_ResultadoPagos' => round(floatval($items->first()->valorTotalComision - $items->first()->totalPagos), 2),
                    'valor_aprobo_gerencia_verificacion' => $items->first()->valor_aprobo_gerencia_verificacion,
                    'fecha_aprobado_general' => $fechaAprobadoGeneral, // Nueva fecha más reciente de los hijos
                    
                    'verificaciones' => $items->sortBy('num_verificacion')->map(function ($item) {
                        return [
                            'num_verificacion' => $item->num_verificacion,
                            'id' => $item->id,
                            'fecha_subir_evidencia' => $item->fecha_subir_evidencia,
                            'file_evidencia' => $item->file_evidencia,
                            'fecha_subir_factura' => $item->fecha_subir_factura,
                            'file_factura' => $item->file_factura,
                            'ifnotificado' => $item->ifnotificado,
                            'ifaprobadoGerencia' => $item->ifaprobadoGerencia,
                            'temporalIfaprobadoGerencia' => $item->ifaprobadoGerencia,
                            'fecha_aprobacionGerencia' => $item->fecha_aprobacionGerencia,
                            'valor_liquidacion' => floatval($item->valor_liquidacion),
                            'id_pedido' => $item->id_pedido,
                            'valorLiquidaciones' => floatval($item->valorLiquidaciones),
                            'pagosPorCerrarse' => $item->pagosPorCerrarse,
                            'solicitudes' => $item->solicitudes,
                        ];
                    })->values(), // Asegura que las claves sean reindexadas
                ];
            })->values();
             
            
            // Retornar respuesta con la estructura deseada
            return response()->json($verificacionesAgrupadas);

        }
        return $verificaciones;
    }
    //api:get/metodosGetVerificaciones?getReporteLiquidadosPorLibros=1&id_periodo=25
    public function getReporteLiquidadosPorLibros($request){
        $id_periodo = $request->input("id_periodo", 0);
        // $query = DB::SELECT("SELECT
        //     i.nombreInstitucion,            -- Nombre de la institución
        //     c.contrato,                     -- Contrato
        //     l.nombrelibro,                  -- Nombre del libro
        //     ls.codigo_liquidacion,          -- Código de liquidación
        //     pp.idlibro,                     -- ID del libro
        //     pp.pfn_pvp,                     -- Precio del libro
        //     c.bc_institucion AS idInstitucion,
        //     COUNT(c.codigo) AS cantidad,    -- Contamos los códigos por libro e institución
        //     (COUNT(c.codigo) * pp.pfn_pvp) AS valortotal  -- Valor total (cantidad de códigos * precio)
        // FROM
        //     codigoslibros c
        // JOIN
        //     institucion i ON c.bc_institucion = i.idInstitucion  -- Relacionamos la institución
        // JOIN
        //     pedidos_formato_new pp ON pp.idlibro = c.libro_idlibro  -- Relacionamos los libros
        // JOIN
        //     libro l ON pp.idlibro = l.idlibro  -- Relacionamos el nombre del libro
        // LEFT JOIN
        //     libros_series ls ON ls.idLibro = l.idlibro  -- Relacionamos las series de libros
        // WHERE
        //     c.bc_periodo = '$id_periodo'  -- Filtro por periodo
        //     AND c.estado_liquidacion IN ('0', '2')  -- Filtro por estado de liquidación
        //     AND c.prueba_diagnostica = '0'  -- Filtro para no incluir prueba diagnóstica
        //     AND pp.idperiodoescolar = '$id_periodo'  -- Filtro por periodo escolar
        //     AND c.contrato IS NOT NULL
        //     AND TRIM(c.contrato) != ''  -- Contrato no vacío
        //     AND c.contrato != '0'  -- Contrato no igual a 0
        // GROUP BY
        //     i.nombreInstitucion, c.contrato, l.nombrelibro, ls.codigo_liquidacion, pp.idlibro, pp.pfn_pvp, c.bc_institucion  -- Agrupamos por institución, contrato, libro y precio
        // ORDER BY
        //     i.nombreInstitucion, l.nombrelibro;

        // ");
        $query = DB::SELECT("SELECT
            i.nombreInstitucion,            -- Nombre de la institución
            c.contrato,                     -- Contrato
            l.nombrelibro,                  -- Nombre del libro
            ls.codigo_liquidacion,          -- Código de liquidación
            c.bc_institucion AS idInstitucion,
            COUNT(c.codigo) AS cantidad     -- Contamos los códigos por libro e institución
            FROM
                codigoslibros c
            JOIN
                institucion i ON c.bc_institucion = i.idInstitucion  -- Relacionamos la institución
            JOIN
                libro l ON c.libro_idlibro = l.idlibro  -- Relacionamos el nombre del libro
            LEFT JOIN
                libros_series ls ON ls.idLibro = l.idlibro  -- Relacionamos las series de libros
            WHERE
                c.bc_periodo = '$id_periodo'  -- Filtro por periodo
                AND c.estado_liquidacion IN ('0', '2')  -- Filtro por estado de liquidación
                AND c.prueba_diagnostica = '0'  -- Filtro para no incluir prueba diagnóstica
                AND c.contrato IS NOT NULL
                AND TRIM(c.contrato) != ''  -- Contrato no vacío
                AND c.contrato != '0'  -- Contrato no igual a 0
            GROUP BY
                i.nombreInstitucion, c.contrato, l.nombrelibro, ls.codigo_liquidacion, c.bc_institucion  -- Agrupamos por institución, contrato, libro y precio
            ORDER BY
                i.nombreInstitucion, l.nombrelibro;
        ");
        return $query;

    }
    //api:get/metodosGetVerificaciones?getReportePorAnio=1&id_periodo=25
    public function getReportePorAnio($request){
        $id_periodo = $request->input("id_periodo", 0);
        if(!$id_periodo){
            return ["status" => "0","message" => "Falta el id_periodo"];
        }
        // $formatoNiveles =  DB::select("SELECT * FROM nivel WHERE orden <> 0 AND orden IS NOT NULL
        // order by orden + 0
        //  ");
        $getLibros      = DB::SELECT("SELECT
            i.nombreInstitucion,            -- Nombre de la institución
            (
                SELECT p2.tipo_venta_descr FROM  pedidos p2
                WHERE p2.contrato_generado = c.contrato
            ) as tipo_venta_descr,
            c.contrato,                     -- Contrato
            l.nombrelibro,                  -- Nombre del libro
            ls.codigo_liquidacion,          -- Código de liquidación
            ls.id_serie,                    -- ID de la serie
            ls.year,                        -- Año
            ls.version,
            CASE 
                WHEN ls.version = 'INICIAL' AND ls.year = 1 THEN 'Inicial 1'
                WHEN ls.version = 'INICIAL' AND ls.year = 2 THEN 'Inicial 2'
                
                WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 1 THEN '1ero Basica'
                    WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 2 THEN '2do Basica'
                    WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 3 THEN '3ero Basica'
                    WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 4 THEN '4to Basica'
                    WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 5 THEN '5to Basica'
                    WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 6 THEN '6to Basica'
                    WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 7 THEN '7mo Basica'
                    WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 8 THEN '8vo Basica'
                    WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 9 THEN '9no Basica'
                    WHEN (ls.version IS NULL OR TRIM(ls.version) = '') AND ls.year = 10 THEN '10mo Basica'
                
                
                WHEN ls.version = 'BGU' AND ls.year = 1 THEN '1ero BGU'
                WHEN ls.version = 'BGU' AND ls.year = 2 THEN '2do BGU'
                WHEN ls.version = 'BGU' AND ls.year = 3 THEN '3ero BGU'
                ELSE n.nombrenivel
            END AS nombrenivel,             -- Nivel con la lógica aplicada
            c.bc_institucion AS idInstitucion,  -- ID de la institución
            COUNT(c.codigo) AS cantidad     -- Contamos los códigos por libro e institución
        FROM
            codigoslibros c
        JOIN
            institucion i ON c.bc_institucion = i.idInstitucion  -- Relacionamos la institución
        JOIN
            libro l ON c.libro_idlibro = l.idlibro  -- Relacionamos el nombre del libro
        LEFT JOIN
            libros_series ls ON ls.idLibro = l.idlibro  -- Relacionamos las series de libros
        LEFT JOIN nivel n ON n.orden = ls.year
        WHERE
            c.bc_periodo = '$id_periodo'  -- Filtro por periodo
            AND c.estado_liquidacion IN ('0', '2')  -- Filtro por estado de liquidación
            AND c.prueba_diagnostica = '0'  -- Filtro para no incluir prueba diagnóstica
            AND c.contrato IS NOT NULL
            AND TRIM(c.contrato) != ''  -- Contrato no vacío
            AND c.contrato != '0'  -- Contrato no igual a 0
            AND ls.id_serie != '6'
            AND c.libro_idlibro <> '945'
            AND c.libro_idlibro <> '946'
            AND c.libro_idlibro <> '947'
            AND c.libro_idlibro <> '948'
            AND c.libro_idlibro <> '949'
            AND c.libro_idlibro <> '950'
            AND c.libro_idlibro <> '951'
            AND c.libro_idlibro <> '952'
            AND c.libro_idlibro <> '953'
        GROUP BY
            i.nombreInstitucion, 
            c.contrato, 
            l.nombrelibro, 
            ls.codigo_liquidacion, 
            ls.id_serie, 
            ls.year, 
            ls.version,
            c.bc_institucion,
            n.nombrenivel
        ORDER BY
            i.nombreInstitucion, l.nombrelibro

        ");
        return $getLibros;
        // return [
        //     "formatoNiveles" => $formatoNiveles,
        //     "getLibros" => $getLibros
        // ];
    }
       
    //api:post/metodosPostVerificaciones
    public function metodosPostVerificaciones(Request $request){
        if($request->guardarNotificacionVerificacion){ return $this->guardarNotificacionVerificacion($request); }
    }
    //api:post/metodosPostVerificaciones?guardarNotificacionVerificacion=1
    public function guardarNotificacionVerificacion($request)
    {
        try {
            // Iniciamos la transacción
            DB::beginTransaction();

            // Decodificamos el array de notificaciones del request
            $arrayNotificaciones    = json_decode($request->data_verificacion);
            $ifnotificado           = $request->ifnotificado;
            $ifaprobadoGerencia     = $request->ifaprobadoGerencia;
            $user_created           = $request->user_created;
            $contador = 0;

            // Iteramos sobre las notificaciones
            foreach ($arrayNotificaciones as $key => $item) {
                $getid = $item->id;

                // Preparando los datos para actualizar
                $updateData = [];

                // Si se envía un valor para ifnotificado, lo actualizamos
                if ($ifnotificado !== null) {
                    $updateData['ifnotificado'] = $ifnotificado;
                }

                // Si se envía un valor para ifaprobadoGerencia, lo actualizamos
                if ($ifaprobadoGerencia !== null) {
                    $updateData['ifaprobadoGerencia'] = $ifaprobadoGerencia;
                    $updateData['fecha_aprobacionGerencia'] = now(); // Asignamos la fecha de aprobación
                    $updateData['user_aprobado_gerencia'] = $user_created; // Asignamos el usuario que hizo la aprobación
                    //actualizar el pedido
                    Pedidos::where('id_pedido', $item->id_pedido)->update(['valor_aprobo_gerencia_verificacion' => $item->valorLiquidaciones]);
                }

                // Si hay datos para actualizar, hacemos la actualización
                if (!empty($updateData)) {
                    Verificacion::where('id', $getid)->update($updateData);
                    $contador++;
                }
            }

            // Si todo fue bien, confirmamos la transacción
            DB::commit();

            // Devolvemos la cantidad de registros guardados
            return [
                "guardados" => $contador,
            ];

        } catch (\Exception $e) {
            // Si ocurre un error, hacemos rollback de la transacción
            DB::rollback();
            return response()->json(["error" => "0", "message" => $e->getMessage()], 200);
        }
    }

    public function eliminarNotificacion(Request $request)
    {
        try {
            $idPadres = $request->id_padre; // El array de ids que recibimos en la solicitud
            $eliminadasAdmin = 0; // Contador para las notificaciones eliminadas
            $eliminadasAsesor = 0; // Contador para las notificaciones eliminadas
            
            foreach ($idPadres as $id) {
                // Inicializar los arrays vacíos dentro del ciclo para cada $idPadres
                $guardarIDsinRevisiones = [];
                $guardarIDsinVerificaciones = [];
                $guardarIDsinFacturas = [];
    
                // Obtener los archivos relacionados con el id_padre
                $archivos = DB::table('evidencia_global_files')->where('egf_referencia', $id)->get();
    
                // Iterar sobre los archivos y organizar por tipo (revisión, verificación, factura)
                foreach ($archivos as $item) {
                    if ($item->egft_id == 2) {
                        $guardarIDsinRevisiones[] = $item->egf_referencia;
                    } elseif ($item->egft_id == 3) {
                        $guardarIDsinVerificaciones[] = $item->egf_referencia;
                    } elseif ($item->egft_id == 4) {
                        $guardarIDsinFacturas[] = $item->egf_referencia;
                    }
                }
    
                // Verificar si no hay archivos de revisión y eliminar notificaciones correspondientes
                if (empty($guardarIDsinRevisiones)) {
                    $notificacion = NotificacionGeneral::where('id_padre', $id)->where('tipo', 3)->where('estado', 0)->first();
                    if ($notificacion) {
                        $notificacion->delete();
                        $eliminadasAdmin++;
                    }
                }
    
                // Verificar si no hay archivos de verificación y eliminar notificaciones correspondientes
                if (empty($guardarIDsinVerificaciones)) {
                    $notificacion = NotificacionGeneral::where('id_padre', $id)->where('tipo', 2)->where('estado', 0)->first();
                    if ($notificacion) {
                        $notificacion->delete();
                        $eliminadasAsesor++;
                    }
                }
    
                // Verificar si no hay archivos de factura y eliminar notificaciones correspondientes
                if (empty($guardarIDsinFacturas)) {
                    $notificacion = NotificacionGeneral::where('id_padre', $id)->where('tipo', 0)->where('estado', 0)->first();
                    if ($notificacion) {
                        $notificacion->delete();
                        $eliminadasAdmin++;
                    }
                }
            }
    
            // Verificar si se eliminaron notificaciones
            if ($eliminadasAdmin > 0 || $eliminadasAsesor > 0) {
                $mensajes = [];

                if ($eliminadasAdmin > 0) {
                    $channel = 'admin.notifications_verificaciones';
                    $event = 'NewNotification';
                    $data = [
                        'message' => 'Nueva notificación',
                    ];
                    $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);
                    $mensajes[] = "$eliminadasAdmin notificación(es) actualizada(s) para Admin.";
                }

                if ($eliminadasAsesor > 0) {
                    $channel = 'asesor.notificacionVerificacion';
                    $event = 'NewNotification';
                    $data = [
                        'message' => 'Nueva notificación',
                    ];
                    $this->NotificacionRepository->notificacionVerificaciones($channel, $event, $data);
                    $mensajes[] = "$eliminadasAsesor notificación(es) actualizada(s) para Asesor.";
                }

                return response()->json(["error" => 0, "message" => implode(" ", $mensajes)], 200);
            } else {
                return response()->json([
                    "error" => 1,
                    "message" => "No se encontraron notificaciones para actualizar",
                    "data" => [$guardarIDsinRevisiones, $guardarIDsinVerificaciones, $guardarIDsinFacturas]
                ], 200);
            }

    
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json(["error" => 1, "message" => $e->getMessage()], 200);
        }
    }
    

}
