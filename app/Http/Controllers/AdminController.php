<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\tipoJuegos;
use App\Models\J_juegos;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\_14Producto;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionHeader;
use App\Models\CodigosLibrosDevolucionSon;
use App\Models\CuotasPorCobrar;
use App\Models\EstudianteMatriculado;
use App\Models\HistoricoCodigos;
use App\Models\Institucion;
use App\Models\Libro;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\Models\Verificacion\VerificacionDescuento;
use App\Models\Models\Verificacion\VerificacionDescuentoDetalle;
use App\Models\PedidoAlcance;
use App\Models\PedidoAlcanceHistorico;
use App\Models\PedidoConvenio;
use App\Models\PedidoDocumentoDocente;
use App\Models\Pedidos;
use App\Models\RepresentanteEconomico;
use App\Models\RepresentanteLegal;
use App\Models\SeminarioCapacitador;
use App\Models\Temporada;
use App\Models\User;
use App\Models\Usuario;
use App\Models\Verificacion;
use App\Repositories\Codigos\CodigosRepository;
use App\Repositories\Facturacion\DevolucionRepository;
use DB;
use GraphQL\Server\RequestError;
use Mail;
use Illuminate\Support\Facades\Http;
use stdClass;
use App\Traits\Pedidos\TraitPedidosGeneral;
use App\Traits\Codigos\TraitCodigosGeneral;
use App\Traits\Verificacion\TraitVerificacionGeneral;
use PDO;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AdminController extends Controller
{
    use TraitPedidosGeneral;
    use TraitCodigosGeneral;
    use TraitVerificacionGeneral;
    protected $devolucionRepository;
    private $codigosRepository;
    public function __construct( DevolucionRepository  $devolucionRepository,CodigosRepository $codigosRepository)
    {

        $this->devolucionRepository  = $devolucionRepository;
        $this->codigosRepository     = $codigosRepository;
    }
    public function getFilesTest(){
      $query = DB::SELECT("SELECT pd.*,c.convenio_anios
      FROM pedidos_convenios_detalle pd
      LEFT JOIN pedidos_convenios c on  pd.pedido_convenio_institucion = c.id
      where pd.id_pedido > 1
      and pd.estado = '1'
      ");
        foreach($query as $key => $item){
            DB::table('pedidos')
            ->where('id_pedido',$item->id_pedido)
            ->update(["convenio_anios" => $item->convenio_anios,"pedidos_convenios_id" => $item->pedido_convenio_institucion]);
        }
        return "se guardo correctamente";
    }

    // public function datoEscuela(Request $request){
    //      set_time_limit(6000);
    //     ini_set('max_execution_time', 6000);
    //    $buscarUsuario = DB::SELECT("SELECT codl.idusuario

    //    FROM codigoslibros AS codl, usuario AS u, institucion AS its
    //    WHERE its.idInstitucion = 424
    //    AND its.idInstitucion = u.institucion_idInstitucion
    //    AND codl.idusuario = u.idusuario
    //    AND u.cedula <> '000000016'
    //     ORDER BY codl.idusuario DESC
    //    LIMIT 10
    //    ");



    //     $data  = [];
    //     $datos = [];
    //     $libros=[];
    //    foreach($buscarUsuario as $key => $item){
    //         $buscarLibros = DB::SELECT("SELECT  * FROM codigoslibros
    //         WHERE idusuario  = '$item->idusuario'
    //         ORDER BY updated_at DESC
    //         ");

    //         foreach($buscarLibros  as $l => $tr){

    //             $libros[$l] = [
    //                 "codigo" => $tr->codigo
    //             ];


    //             $data[$key] =[
    //                 "usuario" => $item->idusuario,
    //                 "libros" => $libros
    //             ];
    //         }


    //    }
    //    $datos = [
    //        "informacion" => $data
    //    ];
    //    return $datos;
    // }
    public function index()
    {
        $usuarios = DB::select("CALL `prolipa` ();");
        return $usuarios;
    }
    function filtrarPorEdad($persona) {
        return $persona["edad"] == 30;
    }
    public function pruebaApi(Request $request){
        try {
            set_time_limit(6000000);
            ini_set('max_execution_time', 6000000);
            $datos=[];
            //anterior
            $periodo                = $request->periodo_idUno;
            //despues
            $periodo2               = $request->periodo_idDos;
            $codigosContrato        = $request->codigoC;
            $codigoContratoComparar = $request->codigoC2;
            if($codigosContrato == null || $codigoContratoComparar == null){ return ["status" => "0", "message" => "No hay codigo de periodo"]; }
            //obtener los vendedores que tienen pedidos
            $query = DB::SELECT("SELECT DISTINCT p.id_asesor ,
            CONCAT(u.nombres, ' ', u.apellidos) AS asesor, u.cedula,u.iniciales
            FROM pedidos p
            LEFT JOIN usuario u ON p.id_asesor = u.idusuario
            WHERE p.id_asesor <> '68750'
            AND p.id_asesor <> '6698'
            AND u.id_group = '11'
            ");
            $datos = [];
            foreach($query as $keyP => $itemP){
                //asesores
                $teran = ["OT","OAT"];
                $galo  = ["EZ","EZP"];
                //VARIABLES
                $iniciales  = $itemP->iniciales;
                //ASESORES QUE TIENE MAS DE UNA INICIAL
                $valores            = [];
                $valores2           = [];
                $arrayAsesor        = [];
                $JsonDespues        = [];
                $JsonAntes          = [];
                $contratosDespues   = [];
                $arraySinContrato   = [];
                $ventaBrutaActual   = 0;
                $ven_neta_actual    = 0;
                //==========CONTRATOS===================
                if($iniciales == 'OT' || $iniciales == 'EZ'){
                    if($iniciales == 'OT') $arrayAsesor = $teran;
                    if($iniciales == 'EZ') $arrayAsesor = $galo;
                    foreach($arrayAsesor as $key => $item){
                        //PERIODO DESPUES
                        $json      = $this->getContratos($itemP->id_asesor,$item,$periodo2,$codigoContratoComparar);
                        //PERIODO ANTES
                        $json2      = $this->getContratos($itemP->id_asesor,$item,$periodo,$codigosContrato);
                        $valores[$key]  = $json;
                        $valores2[$key] = $json2;
                    }
                    // return array_values($valores2);
                    $JsonDespues         =  array_merge($valores[0], $valores[1]);
                    $JsonAntes           =  array_merge($valores2[0], $valores2[1]);
                }else{
                     //PERIODO DESPUES
                    $JsonDespues        = $this->getContratos($itemP->id_asesor,$iniciales,$periodo2,$codigoContratoComparar);
                     //PERIODO ANTES
                    $JsonAntes          = $this->getContratos($itemP->id_asesor,$iniciales,$periodo,$codigosContrato);
                }
                //quitar duplicar de JsonDespues y $JsonAntes
                $JsonDespues = array_values(array_unique($JsonDespues, SORT_REGULAR));
                $JsonAntes   = array_values(array_unique($JsonAntes, SORT_REGULAR));
                //==========SIN CONTRATOS===================
                $getSinContrato = $this->getSinContratoProlipa($itemP->id_asesor,$periodo);
                if(empty($getSinContrato)){
                    $ventaBrutaActual = 0;
                    $ven_neta_actual  = 0;
                }else{
                    $ventaBrutaActual = $getSinContrato[0]->ventaBrutaActual;
                    $ven_neta_actual  = $getSinContrato[0]->ven_neta_actual;
                }
                $arraySinContrato[0] = [
                    "ventaBrutaActual"      => $ventaBrutaActual == null ? '0' :$ventaBrutaActual,
                    "ven_neta_actual"       => $ven_neta_actual  == null ? '0' :$ven_neta_actual,
                ];
                //SEND ARRAY
                $contratosDespues = [
                    "contratos"             => $JsonDespues,
                    "sin_contratos"         => $arraySinContrato
                ];
                $datos[$keyP] = [
                    "id_asesor"             => $itemP->id_asesor,
                    "asesor"                => $itemP->asesor,
                    "iniciales"             => $itemP->iniciales,
                    "cedula"                => $itemP->cedula,
                    "ContratosDespues"      => $contratosDespues,
                    "ContratosAnterior"     => $JsonAntes,
                 ];
            }//FIN FOR EACH ASESORES
            return $datos;
            } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex->getMessage()];
        }
    }
    public function getContratos($id_asesor,$iniciales,$periodo,$codigoContrato=null){
        if($periodo > 21){  return $this->getContratosAsesorProlipa($id_asesor,$periodo); }
        else             {  return $this->getContratosFueraProlipa($iniciales,$codigoContrato); }
    }
    public function getContratosAsesorProlipa($id_asesor,$periodo){
        $query = DB::SELECT("SELECT p.TotalVentaReal as VEN_VALOR, pe.codigo_contrato as PERIODO,
            (p.TotalVentaReal - ((p.TotalVentaReal * p.descuento)/100)) AS ven_neta,
            p.contrato_generado as contrato,
            NULL AS ven_convertido
        FROM pedidos p
        LEFT JOIN periodoescolar pe ON p.id_periodo = pe.idperiodoescolar
        WHERE p.id_asesor = ?
            AND p.tipo = '0'
            AND p.estado = '1'
            AND p.id_periodo = ?
        ", [$id_asesor, $periodo]);
        return $query;
    }
    public function getContratosFueraProlipa($iniciales,$periodo){
        $query = DB::SELECT("SELECT l.ven_valor as VEN_VALOR,
        ( l.ven_valor - ((l.ven_valor * l.ven_descuento)/100)) AS ven_neta,
        l.ven_codigo,l.ven_convertido,
        SUBSTRING(l.ven_codigo, 3, 3) AS PERIODO
        FROM 1_4_venta l
        WHERE l.ven_d_codigo = ?
        AND l.ven_codigo LIKE CONCAT('%C-', ?, '%')
        AND l.est_ven_codigo <> '3'
        -- AND l.ven_convertido IS NULL
        ", [$iniciales,$periodo]);
        return $query;
    }
    public function getSinContratoProlipa($id_asesor,$periodo){
        $getSinContrato = DB::SELECT("SELECT SUM(p.total_venta)  as ventaBrutaActual,
        SUM(( p.total_venta - ((p.total_venta * p.descuento)/100))) AS ven_neta_actual
        FROM pedidos p
        WHERE p.id_asesor = ?
        AND p.id_periodo = ?
        AND p.contrato_generado IS NULL
        AND p.estado = '1'
        ", [$id_asesor, $periodo]);
        return $getSinContrato;
    }
    public function UpdateCodigo($codigo,$union,$TipoVenta){
        if($TipoVenta == 1){
            return $this->updateCodigoVentaDirecta($codigo,$union);
        }
        if($TipoVenta == 2){
            return $this->updateCodigoVentaLista($codigo,$union);
        }
    }
    public function updateCodigoVentaDirecta($codigo,$union){
        $codigo = DB::table('codigoslibros')
            ->where('codigo', '=', $codigo)
            ->where('estado_liquidacion', '=', '1')
            //(SE QUITARA PARA AHORA EL ESTUDIANTE YA ENVIA LEIDO) ->where('bc_estado', '=', '1')
            ->update([
                'factura'           => 'f001',
                'bc_institucion'    => 981,
                'bc_periodo'        => 22,
                'venta_estado'      => 2,
                'codigo_union'      => $union
            ]);
        return $codigo;
    }
    public function updateCodigoVentaLista($codigo,$union){
        $codigo = DB::table('codigoslibros')
            ->where('codigo', '=', $codigo)
            ->where('estado_liquidacion', '=', '1')
            //(SE QUITARA PARA AHORA EL ESTUDIANTE YA ENVIA LEIDO) ->where('bc_estado', '=', '1')
            ->update([
                'factura'                   => 'f001',
                'venta_lista_institucion'   => 981,
                'bc_periodo'                => 22,
                'venta_estado'              => 2,
                'codigo_union'              => $union
            ]);
        return $codigo;
    }
    public function quitarTildes($texto) {
        $tildes = array(
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U'
        );
        return strtr($texto, $tildes);
    }
    public function get_geolocation($apiKey, $ip, $lang = "en", $fields = "*", $excludes = "") {
        $url = "https://api.ipgeolocation.io/ipgeo?apiKey=".$apiKey."&ip=".$ip."&lang=".$lang."&fields=".$fields."&excludes=".$excludes;
        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_HTTPGET, true);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: '.$_SERVER['HTTP_USER_AGENT']
        ));

        return curl_exec($cURL);
    }
    public function getLibrosAsesores($periodo,$asesor_id){
        $val_pedido = DB::SELECT("SELECT pv.valor,
        pv.id_area, pv.tipo_val, pv.id_serie, pv.year,pv.plan_lector,pv.alcance,
        p.id_periodo,
        CONCAT(se.nombre_serie,' ',ar.nombrearea) as serieArea,
        se.nombre_serie,p.id_asesor, CONCAT(u.nombres,' ',u.apellidos) as asesor
        FROM pedidos_val_area pv
        LEFT JOIN area ar ON  pv.id_area = ar.idarea
        LEFT JOIN series se ON pv.id_serie = se.id_serie
        LEFT JOIN pedidos p ON pv.id_pedido = p.id_pedido
        LEFT JOIN usuario u ON p.id_asesor = u.idusuario
        WHERE p.id_periodo  = '$periodo'
        AND p.id_asesor     = '$asesor_id'
        AND p.tipo        = '1'
        AND p.estado      = '1'
        AND p.estado_entrega = '2'
        GROUP BY pv.id
        ");
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
                // "id_area"           => $item->id_area,
                "valor"             => $item->valor,
                // "id_serie"          => $item->id_serie,
                // "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$valores[0]->nombrelibro : $item->serieArea,
                // "libro_id"          => $valores[0]->idlibro,
                "nombrelibro"       => $valores[0]->nombrelibro,
                // "nombre_serie"      => $item->nombre_serie,
                "precio"            => $valores[0]->precio,
                "codigo"            => $valores[0]->codigo_liquidacion,
                // "stock"             => $valores[0]->pro_reservar,
                // "descripcion"       => $valores[0]->descripcionlibro,
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
                $grouped[$codigo]->valor += $item->valor;
            }
        }

        // Convertir el array asociativo de nuevo a un array indexado
        $result = array_values($grouped);
        //subtotal
        foreach($result as $key => $item){
            $result[$key]->subtotal = $item->valor * $item->precio;
        }
        return $result;
    }
    public function pruebaData(Request $request){
        $query = DB::SELECT("SELECT c.id, c.estado,c.convenio_aprobado,
            (
            SELECT COUNT(l.doc_codigo) AS contadorPendientesConvenio
                FROM 1_4_documento_liq l
                WHERE l.tipo_pago_id = '4'
                AND l.estado ='0'
                AND l.pedidos_convenios_id = c.id
            ) AS contadorConvenios

            FROM  pedidos_convenios  c
            where c.convenio_aprobado = 0
        ");
        return $query;



        // $getCodigos = 'SMLL2-P4P6X5N6X8,
        // PSMLL2-2MW3S3BMY8,
        // SMLL2-U2UD5HCMRD,
        // PSMLL2-YF9BA2HNAF,
        // SMLL2-XDBG7CE3AN,
        // PSMLL2-S3NU2HGKYN,
        // SMM2-AMB4RTE8ME,
        // PSMM2-UFY99MA4S4,
        // SMM2-DX787E5Y4R,
        // PSMM2-SP5M8TX4BD,
        // SMM2-KBWHCUAXBR,
        // PSMM2-H3TDA2FB79,
        // SMCN2-XSS6KZYHEW,
        // PSMCN2-DXAB4VE9PH,
        // SMCN2-YUY6ZTGGN2,
        // PSMCN2-BFXXYDT2KM,
        // SMCN2-ATPH88HZDY,
        // PSMCN2-22XG4KNYY7,
        // SMES2-PURKMWD8V9,
        // PSMES2-VB4NYVW9BK,
        // SMES2-GY8ZTTNSFB,
        // PSMES2-E9Y8STK78G,
        // SMES2-UXMSNZBPDA,
        // PSMES2-WHBHEUE9MN,
        // ';
        // $lineas = explode(",", $getCodigos); // Separar por coma
        // // Recorrer y armar el array
        // foreach ($lineas as $linea) {
        //     $codigoLimpio = trim($linea); // Limpiar espacios o saltos de línea
        //     if (!empty($codigoLimpio)) {
        //         $codigos[] = ['codigo' => $codigoLimpio];
        //     }
        // }

        // // Los combos que quieres añadir, excluyendo el combo 'CMB-5YZAW6'
        // $combos = [
        //     'CMB-W4WWGE',
        //     'CMB-FVEGW4',
        //     'CMB-YFEVEZ',
        // ];
        // $libro = 'CFA2';
        // $getLibrosCombo = _14Producto::findOrFail($libro);
        // if(!$getLibrosCombo){
        //     return ["status" => "0", "message" => "No se encontro el libro $libro"];
        // }
        // $prefixes       = explode(',', $getLibrosCombo->codigos_combos);
        // // Los prefijos (tipos de códigos) que quieres procesar
        // // $prefixes = $request->input('prefixes', ['SEAE2', 'CERP', 'CAMM', 'CUNA']);
        // // Definir la cantidad de códigos por combo
        // $cantidadPorCombo = 8;


        // // Inicializar los arrays para almacenar los resultados
        // $comboOrdenado = [];
        // $combosSinCodigos = [];
        // $codigosProblemas = [];

        // // Se crea un array de colecciones para cada prefijo
        // $filteredByPrefix = [];
        // foreach ($prefixes as $prefix) {
        //     $filteredByPrefix[$prefix] = collect($codigos)->filter(function ($item) use ($prefix) {
        //         return strpos($item['codigo'], $prefix) !== false;
        //     });
        // }

        // // Alternar entre combo y códigos
        // $comboIndex = 0;
        // while ($comboIndex < count($combos)) {
        //     // Verificar si hay suficientes códigos disponibles antes de agregar el combo
        //     $totalDisponibles = collect($filteredByPrefix)->sum(fn($collection) => $collection->count());

        //     if ($totalDisponibles < $cantidadPorCombo) {
        //         // Si no hay suficientes códigos, mover el combo a la lista de problemas y continuar
        //         $combosSinCodigos[] = $combos[$comboIndex];

        //         // Capturar los códigos que no se pudieron usar
        //         foreach ($filteredByPrefix as $prefix => $collection) {
        //             if ($collection->isNotEmpty()) {
        //                 $codigosProblemas = array_merge($codigosProblemas, $collection->all());
        //                 $filteredByPrefix[$prefix] = collect(); // Vaciar la colección para evitar duplicados
        //             }
        //         }

        //         $comboIndex++;
        //         continue;
        //     }

        //     // Añadir un combo primero
        //     $comboOrdenado[] = ['codigo' => $combos[$comboIndex]];
        //     $comboIndex++;

        //     // Añadir códigos
        //     $codesAdded = 0;
        //     while ($codesAdded < $cantidadPorCombo) {
        //         foreach ($prefixes as $prefix) {
        //             if ($filteredByPrefix[$prefix]->isNotEmpty()) {
        //                 // Tomar un código de este prefijo
        //                 $comboOrdenado[] = $filteredByPrefix[$prefix]->shift();
        //                 $codesAdded++;

        //                 if ($codesAdded < $cantidadPorCombo && $filteredByPrefix[$prefix]->isNotEmpty()) {
        //                     $comboOrdenado[] = $filteredByPrefix[$prefix]->shift();
        //                     $codesAdded++;
        //                 }
        //             }

        //             if ($codesAdded >= $cantidadPorCombo) {
        //                 break;
        //             }
        //         }
        //     }
        // }

        // // Retornar el array con la propiedad 'codigo', los combos con problema y los códigos con problema
        // return response()->json([
        //     'combo' => $comboOrdenado,
        //     'combos_sin_codigos' => $combosSinCodigos,
        //     'codigos_problemas' => $codigosProblemas
        // ]);


        // Retorna el array con la propiedad 'codigo'
        return;
        // $clientIP = \Request::getClientIp(true);
        // // $clientIP =  $request->ip();
        // $apiKey = "aba8c348cd6d4d14af6af2294f04d356";
        // // $ip = "186.4.218.168";
        // $ip = $clientIP;
        // $location = $this->get_geolocation($apiKey, $ip);
        // $decodedLocation = json_decode($location, true);

        // echo "<pre>";
        // print_r($decodedLocation);
        // echo "</pre>";

        // return;
        $formData = [
            "api_key" => "RfVaC9hIMhn49J4jSq2_I_.QLazmDGrbZQ8o8ePUEcU-"
        ];
        $data           = Http::post('http://190.12.43.171:8181/api/consulta_provincias',$formData);
        $datos          = json_decode($data, true);
        return $datos;

        // $tracerouteOutput = $this->runSystemCommand('traceroute 190.12.43.171');
        // $telnetOutput = $this->runSystemCommand('telnet 190.12.43.171 443');

        // return response()->json([
        //     'traceroute' => $tracerouteOutput,
        //     'telnet' => $telnetOutput,
        // ]);
    }
    private function runSystemCommand($command)
    {
        $process = new Process(explode(' ', $command));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
    public function saveHistoricoAlcance($id_alcance,$id_pedido,$contrato,$cantidad_anterior,$nueva_cantidad,$user_created,$tipo){
        //vadidate that it's not exists
        $query = DB::SELECT("SELECT * FROM pedidos_alcance_historico h
        WHERE h.alcance_id = '$id_alcance'
        AND h.id_pedido ='$id_pedido'");
        if(empty($query)){
            $historico                      = new PedidoAlcanceHistorico();
            $historico->contrato            = $contrato;
            $historico->id_pedido           = $id_pedido;
            $historico->alcance_id          = $id_alcance;
            $historico->cantidad_anterior   = $cantidad_anterior;
            $historico->nueva_cantidad      = $nueva_cantidad;
            $historico->user_created        = $user_created;
            $historico->tipo                = $tipo;
            $historico->save();
        }
    }
    public function get_val_pedidoInfo_alcance($pedido,$alcance){
        $val_pedido = DB::SELECT("SELECT DISTINCT pv.*,
        p.descuento, p.id_periodo,
        p.anticipo, p.comision, CONCAT(se.nombre_serie,' ',ar.nombrearea) as serieArea,
        se.nombre_serie
        FROM pedidos_val_area pv
        left join area ar ON  pv.id_area = ar.idarea
        left join series se ON pv.id_serie = se.id_serie
        INNER JOIN pedidos p ON pv.id_pedido = p.id_pedido
        WHERE pv.id_pedido = '$pedido'
        AND pv.alcance = '$alcance'
        GROUP BY pv.id;
        ");
        $datos = [];
        foreach($val_pedido as $key => $item){
            $valores = [];
            //plan lector
            if($item->plan_lector > 0 ){
                $getPlanlector = DB::SELECT("SELECT l.nombrelibro,l.idlibro,
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
                $getLibros = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro,
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
                "subtotal"          => $item->valor * $valores[0]->precio,
                "codigo_liquidacion"=> $valores[0]->codigo_liquidacion,
            ];
        }
        return $datos;
    }
    public function traerPeriodo($institucion_id){
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo , periodoescolar AS descripcion FROM periodoescolar WHERE idperiodoescolar = (
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$institucion_id'))
        ");
        if(count($periodoInstitucion)>0){
            return ["status" => "1", "message"=>"correcto","periodo" => $periodoInstitucion];
        }else{
            return ["status" => "0", "message"=>"no hay periodo"];
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
    public function getAlcanceAbiertoXId($id){
        $query = DB::SELECT("SELECT * FROM pedidos_alcance a
        WHERE a.id = '$id'
        AND a.estado_alcance = '1'");
        return $query;
    }

    public function guardarData(Request $request){

        // set_time_limit(6000);
        // ini_set('max_execution_time', 600000);





        // $contadorSolinfaGONZALEZ = 0;
        // $contadorSolinfaCOBACANGO= 0;
        // $resultsGonzales = DB::connection('mysql2')->select('SELECT * FROM product p
        //  WHERE p.id_perseo_gonzales IS NULL
        // limit 90');
        // $resultsCobacango = DB::connection('mysql2')->select('SELECT * FROM product p
        // WHERE p.id_perseo_cobacango IS NULL
        // limit 90');
        // //GONZALES
        // foreach($resultsGonzales as $key => $item){
        //         $formData = [
        //             "productocodigo"=> $item->barcode,
        //         ];
        //         $url                        = "productos_consulta";
        //         $processSolinfa             = $this->tr_SolinfaPost($url, $formData,1);
        //         $getContador                = $this->guardarIdProductoSolinfa($processSolinfa,$item->barcode,"id_perseo_gonzales");
        //         $contadorSolinfaGONZALEZ    = $contadorSolinfaGONZALEZ + $getContador;
        // }
        // //COBACANGO
        // foreach($resultsCobacango as $key => $item){
        //     $formData = [
        //         "productocodigo"=> $item->barcode,
        //     ];
        //     $url                        = "productos_consulta";
        //     $processSolinfa             = $this->tr_SolinfaPost($url, $formData,2);
        //     $getContador                = $this->guardarIdProductoSolinfa($processSolinfa,$item->barcode,"id_perseo_cobacango");
        //     $contadorSolinfaCOBACANGO    = $contadorSolinfaCOBACANGO + $getContador;
        // }
        // return ["contadorSolinfaGONZALEZ" => $contadorSolinfaGONZALEZ, "contadorSolinfaCOBACANGO" => $contadorSolinfaCOBACANGO];


        // try {
        //     $contadorProlipa = 0;
        //     $contadorCalmed  = 0;

        //     // $queryProlipa = DB::SELECT("SELECT * FROM 1_4_cal_producto p
        //     // WHERE p.pro_codigo  = 'IKAD';
        //     // ");
        //     // //PROLIPA
        //     // // $queryProlipa = DB::SELECT("SELECT * FROM 1_4_cal_producto p
        //     // // WHERE p.id_perseo_prolipa_produccion IS NULL
        //     // // LIMIT 90
        //     // // ");
        //     // foreach($queryProlipa as $key => $item){
        //     //     $formData = [
        //     //         "productocodigo"=> $item->pro_codigo,
        //     //     ];
        //     //     $url                = "productos_consulta";
        //     //     $processProlipa     = $this->tr_PerseoPost($url, $formData,1);
        //     //     return $processProlipa;
        //     //     $getContador        = $this->guardarIdProducto($processProlipa,$item->pro_codigo,"id_perseo_prolipa_produccion");
        //     //     //contadorProlipa + getContador
        //     //     $contadorProlipa    = $contadorProlipa + $getContador;
        //     // }
        //     //CALMED
        //     $queryCalmed = DB::SELECT("SELECT * FROM 1_4_cal_producto p
        //     WHERE p.pro_codigo  = 'SMLL2';
        //     ");
        //     // $queryCalmed = DB::SELECT("SELECT * FROM 1_4_cal_producto p
        //     // WHERE p.id_perseo_calmed_produccion IS NULL
        //     // LIMIT 100
        //     // ");
        //     foreach($queryCalmed as $key => $item){
        //         $formData = [
        //             "productocodigo"=> $item->pro_codigo,
        //         ];
        //         $url                = "productos_consulta";
        //         $processCalmed      = $this->tr_PerseoPost($url, $formData,1);
        //         return $processCalmed;
        //         $getContador        = $this->guardarIdProducto($processCalmed,$item->pro_codigo,"id_perseo_calmed_produccion");
        //         //contadorCalmed + getContador
        //         $contadorCalmed     = $contadorCalmed + $getContador;
        //     }
        //     return ["contadorProlipa" => $contadorProlipa,"contadorCalmed" => $contadorCalmed];
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'error' => $e->getMessage()
        //     ], 500);
        // }
    }
    public function guardarIdProducto($process,$pro_codigo,$campoPerseo){
        $contador = 0;
        $datos    = [];
        if(isset($process["productos"])){
            $idPerseo = $process["productos"][0]["productosid"];
            $datos = [ $campoPerseo => $idPerseo ];
            $contador = 1;
        }else{
            $datos = [ $campoPerseo => 0 ];
            $contador = 0;
        }
        DB::table('1_4_cal_producto')
        ->where('pro_codigo',$pro_codigo)
        ->update($datos);
        return $contador;
    }
    public function guardarIdProductoSolinfa($process,$pro_codigo,$campoPerseo){
        $contador = 0;
        $datos    = [];
        if(isset($process["productos"])){
            $idPerseo = $process["productos"][0]["productosid"];
            $datos = [ $campoPerseo => $idPerseo ];
            $contador = 1;
        }else{
            $datos = [ $campoPerseo => 0 ];
            $contador = 0;
        }
        DB::connection('mysql2')
            ->table('product')
            ->where('barcode', $pro_codigo)
            ->update($datos);
        return $contador;
    }
    public function hijosConvenio($idConvenio){
        $query = DB::SELECT("SELECT * FROM 1_4_documento_liq l
        WHERE l.pedidos_convenios_id = ?
        AND l.estado = '1'
        AND l.tipo_pago_id = '4'
        ",[$idConvenio]);
        return $query;
    }
    public function crearCapacitadores($request,$arreglo){
        $datos = json_decode($request->capacitadores);
        //eliminar si ya han quitado al capacitador
        $getCapacitadores = $this->getCapacitadoresXCapacitacion($arreglo->id_seminario);
        if(sizeOf($getCapacitadores) > 0){
            foreach($getCapacitadores as $key => $item){
                $capacitador        = "";
                $capacitador        = $item->idusuario;
                $searchCapacitador  = collect($datos)->filter(function ($objeto) use ($capacitador) {
                    // Condición de filtro
                    return $objeto->idusuario == $capacitador;
                });
                if(sizeOf($searchCapacitador) == 0){
                    DB::DELETE("DELETE FROM seminarios_capacitador
                      WHERE seminario_id = '$arreglo->id_seminario'
                      AND idusuario = '$capacitador'
                    ");
                }
            }
        }
        //guardar los capacitadores
        foreach($datos as $key => $item){
            $query = DB::SELECT("SELECT * FROM seminarios_capacitador c
            WHERE c.idusuario = '$item->idusuario'
            AND c.seminario_id = '$arreglo->id_seminario'");
            if(empty($query)){
                $capacitador = new SeminarioCapacitador();
                $capacitador->idusuario      = $item->idusuario;
                $capacitador->seminario_id   = $arreglo->id_seminario;
                $capacitador->save();
            }
        }
    }

    public function guardarContratoTemporada($contrato,$institucion,$asesor_id,$temporadas,$periodo,$ciudad,$asesor,$cedulaAsesor,$nombreDocente,$cedulaDocente,$nombreInstitucion){
        //validar que el contrato no existe
        $validate = DB::SELECT("SELECT * FROM temporadas t
        WHERE t.contrato = '$contrato'
        ");
        if(empty($validate)){
            $temporada = new Temporada();
            $temporada->contrato                = $contrato;
            $temporada->year                    = date("Y");
            $temporada->ciudad                  = $ciudad;
            $temporada->temporada               = $temporadas;
            $temporada->id_asesor               = $asesor_id;
            $temporada->cedula_asesor           = 0;
            $temporada->id_periodo              = $periodo;
            $temporada->id_profesor             = "0";
            $temporada->idInstitucion           = $institucion;
            $temporada->temporal_nombre_docente = $nombreDocente;
            $temporada->temporal_cedula_docente = $cedulaDocente;
            $temporada->temporal_institucion    = $nombreInstitucion;
            $temporada->nombre_asesor           = $asesor;
            $temporada->cedula_asesor           = $cedulaAsesor;
            $temporada->save();
            return $temporada;
        }else{
            $id_temporada                       = $validate[0]->id_temporada;
            $temporada                          = Temporada::findOrFail($id_temporada);
            $temporada->id_periodo              = $periodo;
            $temporada->idInstitucion           = $institucion;
            $temporada->id_asesor               = $asesor_id;
            $temporada->temporal_nombre_docente = $nombreDocente;
            $temporada->temporal_cedula_docente = $cedulaDocente;
            $temporada->temporal_institucion    = $nombreInstitucion;
            $temporada->nombre_asesor           = $asesor;
            $temporada->cedula_asesor           = $cedulaAsesor;
            $temporada->save();
            return $temporada;
        }
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

    /**
     * Display the specified resource.
     *
     * @param  \App\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function edit(Admin $admin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Admin $admin)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $admin)
    {
        //
    }

    // Consultas para administrador
    public function cant_user(){
        $cantidad = DB::SELECT("SELECT id_group, COUNT(id_group) as cantidad FROM usuario WHERE estado_idEstado =1  GROUP BY id_group");
        return $cantidad;
    }
    public function cant_cursos(){
        $cantidad = DB::SELECT("SELECT estado, COUNT(estado) as cantidad FROM curso  GROUP BY estado");
        return $cantidad;
    }
    public function cant_codigos(){
        $cantidad = DB::SELECT("SELECT COUNT(*) as cantidad FROM codigoslibros WHERE idusuario > 0");
        return $cantidad;
    }
    public function cant_codigostotal(){
        $cantidad = DB::SELECT("SELECT COUNT(*) as cantidad FROM codigoslibros");
        return $cantidad;
    }
    public function cant_evaluaciones(){
        $cantidad = DB::SELECT("SELECT estado, COUNT(estado) as cantidad FROM evaluaciones  GROUP BY estado");
        return $cantidad;
    }
    public function cant_preguntas(){
        $cantidad = DB::SELECT("SELECT id_tipo_pregunta, COUNT(id_tipo_pregunta) as cantidad FROM preguntas  GROUP BY id_tipo_pregunta");
        return $cantidad;
    }
    public function cant_multimedia(){
        $cantidad = DB::SELECT("SELECT tipo, COUNT(tipo) as cantidad FROM actividades_animaciones  GROUP BY tipo");
        return $cantidad;
    }
    public function cant_juegos(){
        // $cantidad = DB::SELECT("SELECT jj.id_tipo_juego, COUNT(jj.id_tipo_juego) as cantidad , jt.nombre_tipo_juego FROM j_juegos jj INNER JOIN j_tipos_juegos jt ON jj.id_tipo_juego = jt.id_tipo_juego GROUP BY jt.id_tipo_juego GROUP BY jj.id_tipo_juego");

        $cantidad = DB::table('j_juegos')
        ->join('j_tipos_juegos', 'j_tipos_juegos.id_tipo_juego','=','j_juegos.id_tipo_juego')
        ->select('j_tipos_juegos.nombre_tipo_juego', DB::raw('count(*) as cantidad'))
        ->groupBy('j_tipos_juegos.nombre_tipo_juego')
        ->get();
        return $cantidad;
    }
    public function cant_seminarios(){
        $cantidad = DB::SELECT("SELECT COUNT(*) as cantidad FROM seminario  WHERE estado=1");
        return $cantidad;
    }
    public function cant_encuestas(){
        $cantidad = DB::SELECT("SELECT COUNT(*) as cantidad FROM encuestas_certificados");
        return $cantidad;
    }
    public function cant_institucion(){
        $cantidad = DB::SELECT("SELECT DISTINCT COUNT(*) FROM institucion i, periodoescolar p, periodoescolar_has_institucion pi WHERE  i.idInstitucion = pi.institucion_idInstitucion AND pi.periodoescolar_idperiodoescolar = p.idperiodoescolar AND p.estado = 1 GROUP BY i.region_idregion");
        return $cantidad;
    }

    public function get_periodos_activos(){
        $periodos = DB::SELECT("SELECT * FROM periodoescolar p WHERE p.estado = '1' ORDER BY p.idperiodoescolar DESC;");
        return $periodos;
    }
    public function get_periodos_pedidos(){
        $periodos = DB::SELECT("SELECT * FROM periodoescolar p ORDER BY p.idperiodoescolar DESC  ");
        return $periodos;
    }

    public function get_asesores(){
        $asesores = DB::SELECT("SELECT `idusuario`, CONCAT(`nombres`,' ',`apellidos`) AS nombres, `cedula` FROM `usuario` WHERE `estado_idEstado` = '1' AND `id_group` = '5';");
        return $asesores;
    }
    public function get_asesor(){
        $asesores = DB::SELECT("SELECT `idusuario`, `iniciales`, CONCAT(`nombres`,' ',`apellidos`) AS nombres, `cedula` FROM `usuario` WHERE `estado_idEstado` = '1' AND `id_group` = '11';");
        return $asesores;
    }
    public function reporte_asesores(){

        $fecha_fin    = date("Y-m-d");
        $fecha_inicio = date("Y-m-d",strtotime($fecha_fin."- 7 days"));

        $agendas = DB::SELECT("SELECT a.id, a.title, a.label, a.classes, a.startDate, a.endDate, a.hora_inicio, a.hora_fin, a.url, a.nombre_institucion_temporal, a.opciones, u.nombres, u.apellidos, u.cedula, u.email, p.periodoescolar FROM agenda_usuario a, usuario u, periodoescolar p WHERE a.id_usuario = u.idusuario AND a.periodo_id = p.idperiodoescolar AND u.id_group != 6 AND p.estado = '1' AND a.startDate BETWEEN '$fecha_inicio' AND '$fecha_fin' ORDER BY u.cedula;");

        $email = 'mcalderonmediavilla@hotmail.com';
        $emailCC = 'reyesjorge10@gmail.com';
        $reporte = 'Reporte asesores';

        $envio = Mail::send('plantilla.reporte_asesores',
            [
                'fecha_fin'    => $fecha_fin,
                'fecha_inicio' => $fecha_inicio,
                'agendas'      => $agendas,
            ],
            function ($message) use ($email, $emailCC, $reporte) {
                $message->from('reportesgerencia@prolipadigital.com.ec', $reporte);
                $message->to($email)->bcc($emailCC)->subject('Agenda de asesores');
            }
        );
    }

    // public function reporte_asesores_view($periodo, $fecha_inicio, $fecha_fin){

    //     if( $periodo != 'null' ){
    //         $agendas = DB::SELECT("SELECT a.id, a.title, a.label, a.classes, a.startDate, a.endDate, a.hora_inicio, a.hora_fin, a.url, a.nombre_institucion_temporal, a.opciones, u.nombres, u.apellidos, u.cedula, u.email, p.periodoescolar FROM agenda_usuario a, usuario u, periodoescolar p WHERE a.id_usuario = u.idusuario AND a.periodo_id = p.idperiodoescolar AND u.id_group != 6 AND p.estado = '1' AND p.idperiodoescolar = $periodo ORDER BY u.cedula;");
    //     }else{
    //         if( $fecha_inicio != 'null' ){
    //             $agendas = DB::SELECT("SELECT a.id, a.title, a.label, a.classes, a.startDate, a.endDate, a.hora_inicio, a.hora_fin, a.url, a.nombre_institucion_temporal, a.opciones, u.nombres, u.apellidos, u.cedula, u.email, p.periodoescolar FROM agenda_usuario a, usuario u, periodoescolar p WHERE a.id_usuario = u.idusuario AND a.periodo_id = p.idperiodoescolar AND u.id_group != 6 AND p.estado = '1' AND a.startDate BETWEEN '$fecha_inicio' AND '$fecha_fin' ORDER BY u.cedula;");
    //         }else{

    //             $fecha_fin    = date("Y-m-d");
    //             $fecha_inicio = date("Y-m-d",strtotime($fecha_fin."- 30 days"));

    //             $agendas = DB::SELECT("SELECT a.id, a.title, a.label, a.classes, a.startDate, a.endDate, a.hora_inicio, a.hora_fin, a.url, a.nombre_institucion_temporal, a.opciones, u.nombres, u.apellidos, u.cedula, u.email, p.periodoescolar FROM agenda_usuario a, usuario u, periodoescolar p WHERE a.id_usuario = u.idusuario AND a.periodo_id = p.idperiodoescolar AND u.id_group != 6 AND p.estado = '1' AND a.startDate BETWEEN '$fecha_inicio' AND '$fecha_fin' ORDER BY u.cedula;");
    //         }
    //     }

    //     return $agendas;

    // }


    public function reporte_asesores_view($fecha_inicio, $fecha_fin,$periodo){

        // if( $periodo != 'null' ){
        //     $agendas = DB::SELECT("SELECT s.id, s.num_visita, s.tipo_seguimiento, s.fecha_genera_visita, s.fecha_que_visita, s.observacion, s.estado, i.nombreInstitucion, u.nombres, u.apellidos, u.cedula, u.email, p.periodoescolar FROM seguimiento_cliente s, usuario u, institucion i, periodoescolar p WHERE s.asesor_id = u.idusuario AND s.institucion_id = i.idInstitucion AND s.periodo_id = p.idperiodoescolar AND s.periodo_id = $periodo order BY u.nombres");
        // }else{
        //     if( $fecha_inicio != 'null' ){
        //         $agendas = DB::SELECT("SELECT s.id, s.num_visita, s.tipo_seguimiento, s.fecha_genera_visita, s.fecha_que_visita, s.observacion, s.estado, i.nombreInstitucion, u.nombres, u.apellidos, u.cedula, u.email, p.periodoescolar FROM seguimiento_cliente s, usuario u, institucion i, periodoescolar p WHERE s.asesor_id = u.idusuario AND s.institucion_id = i.idInstitucion AND s.periodo_id = p.idperiodoescolar AND s.fecha_genera_visita BETWEEN '$fecha_inicio' AND '$fecha_fin' order BY u.nombres");
        //     }else{
        //         $fecha_fin    = date("Y-m-d");
        //         $fecha_inicio = date("Y-m-d",strtotime($fecha_fin."- 30 days"));
        //         $agendas = DB::SELECT("SELECT s.id, s.num_visita, s.tipo_seguimiento, s.fecha_genera_visita, s.fecha_que_visita, s.observacion, s.estado, i.nombreInstitucion, u.nombres, u.apellidos, u.cedula, u.email, p.periodoescolar FROM seguimiento_cliente s, usuario u, institucion i, periodoescolar p WHERE s.asesor_id = u.idusuario AND s.institucion_id = i.idInstitucion AND s.periodo_id = p.idperiodoescolar AND s.fecha_genera_visita BETWEEN '$fecha_inicio' AND '$fecha_fin' order BY u.nombres");
        //     }
        // }
        if( $periodo != 'null' ){
            $visitas = DB::SELECT("CALL `pr_reporteVisitasInstitucionxPeriodo`('$periodo');
                ");
            $visitasITemporal = DB::SELECT("CALL pr_reporteVisitasInstitucionTemporalxPeriodo('$periodo')");
                return [
                    "visitasInstitucion" => $visitas,
                    "visitasInstitucionTemporal" => $visitasITemporal
                 ];
        }else{
            if($fecha_inicio != 'null' ){
                $visitas = DB::SELECT("CALL `pr_reporteVisitasInstitucion`('$fecha_inicio','$fecha_fin');
                ");
                $visitasITemporal = DB::SELECT("CALL pr_reporteVisitasInstitucionTemporal('$fecha_inicio', '$fecha_fin')");
                return [
                    "visitasInstitucion" => $visitas,
                    "visitasInstitucionTemporal" => $visitasITemporal
                ];
            }
        }

        return $agendas;

    }

    public function get_estadisticas_asesor_inst($periodo, $fecha_inicio, $fecha_fin){

        if( $periodo != 'null' ){
            $visitas = DB::SELECT("SELECT i.nombreInstitucion, COUNT(i.idInstitucion) AS cant_visitas
            FROM seguimiento_cliente s
            INNER JOIN institucion i ON s.institucion_id = i.idInstitucion
            WHERE s.periodo_id = $periodo AND s.fecha_genera_visita
            GROUP BY i.idInstitucion;");
        }else{
            if( $fecha_inicio != 'null' ){
                $visitas = DB::SELECT("SELECT i.nombreInstitucion, COUNT(i.idInstitucion) AS cant_visitas
                FROM seguimiento_cliente s
                INNER JOIN institucion i ON s.institucion_id = i.idInstitucion
                WHERE s.fecha_genera_visita BETWEEN '$fecha_inicio' AND '$fecha_fin'
                GROUP BY i.idInstitucion;");
            }else{

                $fecha_fin    = date("Y-m-d");
                $fecha_inicio = date("Y-m-d",strtotime($fecha_fin."- 30 days"));

                $visitas = DB::SELECT("SELECT i.nombreInstitucion, COUNT(i.idInstitucion) AS cant_visitas
                FROM seguimiento_cliente s
                INNER JOIN institucion i ON s.institucion_id = i.idInstitucion
                WHERE s.fecha_genera_visita BETWEEN '$fecha_inicio' AND '$fecha_fin'
                GROUP BY i.idInstitucion;");
            }
        }

        return $visitas;

    }
    //api:get/>>https://apitest.prolipadigital.com.ec/producto_reserva/SM1/5
    public function producto_reserva($producto,$cantidad){
        return $this->updateProducto($producto,$cantidad,"pro_reservar");
    }
    //api:get/>>https://apitest.prolipadigital.com.ec/producto_factura_prolipa/SM1/6
    public function producto_factura_prolipa($producto,$cantidad){
        return $this->updateProducto($producto,$cantidad,"pro_stock");
    }
    //api:get/>>https://apitest.prolipadigital.com.ec/producto_nota_prolipa/SM1/7
    public function producto_nota_prolipa($producto,$cantidad){
        return $this->updateProducto($producto,$cantidad,"pro_deposito");
    }
    //api:get/>>https://apitest.prolipadigital.com.ec/producto_factura_calmed/SM1/8
    public function producto_factura_calmed($producto,$cantidad){
        return $this->updateProducto($producto,$cantidad,"pro_stockCalmed");
    }
     //api:get/>>https://apitest.prolipadigital.com.ec/producto_nota_calmed/SM1/9
     public function producto_nota_calmed($producto,$cantidad){
        return $this->updateProducto($producto,$cantidad,"pro_depositoCalmed");
    }



    public function updateProducto($producto,$cantidad,$parametro1){
        DB::table('1_4_cal_producto')
        ->where("pro_codigo",$producto)
        ->update([$parametro1 => $cantidad]);
        $resultado =_14Producto::where('pro_codigo',$producto)->get();
        return $resultado;
    }

    // METODOS JEYSON INICIO
    public function Post_ActualizarPorcentaje_Venta(Request $request)
    {
        // Iniciar una transacción para garantizar la integridad de los datos
        DB::beginTransaction();

        try {
            // Buscar el registro de venta basado en id_empresa y ven_codigo
            $venta = DB::table('f_venta')
                    ->where('id_empresa', $request->id_empresa)
                    ->where('ven_codigo', $request->ven_codigo)
                    ->first();

            if (!$venta) {
                // Si no se encuentra el registro, devolver un error
                return response()->json(["status" => "0", 'message' => 'Venta no encontrada'], 404);
            }

            // Buscar la proforma asociada a la venta
            $proforma = DB::table('f_proforma')
            ->where('emp_id', $venta->id_empresa)
            ->where('prof_id', $venta->ven_idproforma)
            ->first();

            if (!$proforma) {
                // Si no se encuentra la proforma, devolver un error
                return response()->json(["status" => "0", 'message' => 'Proforma no encontrada'], 404);
            }

            // Actualizar el campo pro_des_por en la tabla f_proforma
            DB::table('f_proforma')
                ->where('emp_id', $venta->id_empresa)
                ->where('prof_id', $venta->ven_idproforma)
                ->update(['pro_des_por' => $request->ven_desc_por]);

            // Actualizar el campo ven_desc_por en la tabla f_venta
            DB::table('f_venta')
                ->where('id_empresa', $request->id_empresa)
                ->where('ven_codigo', $request->ven_codigo)
                ->update(['ven_desc_por' => $request->ven_desc_por]);

            // Llamar a ActualizarPorcentajeRepository si es necesario
            // DB::rollback();
            $this->ActualizarPorcentajeRepository($request);

            // Confirmar la transacción
            DB::commit();

            return response()->json(["status" => "1", 'message' => 'Registro actualizado correctamente']);
        } catch (\Exception $e) {
            // En caso de error, revertir la transacción
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }
    public function ActualizarPorcentajeRepository(Request $request)
    {
        // Obtener los valores de la solicitud
        $codigo_proforma = $request->ven_codigo;
        $codigo_empresa = $request->id_empresa;

        // Llamar al repositorio para actualizar los valores
        $this->devolucionRepository->updateValoresDocumentoF_venta($codigo_proforma, $codigo_empresa);
        $this->devolucionRepository->updateValoresDocumentoF_proforma($codigo_proforma, $codigo_empresa);

        return "se actualizó";
    }
    // METODOS JEYSON FIN
}
