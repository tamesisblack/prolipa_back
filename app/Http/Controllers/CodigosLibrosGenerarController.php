<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\CodigosLibros;
use App\Models\Ciudad;
use App\Models\Institucion;
use App\Models\Usuario;
use App\Models\Periodo;
use DataTables;
Use Exception;
use App\Models\CodigosObservacion;
use App\Repositories\Codigos\CodigosRepository;
use App\Traits\Codigos\TraitCodigosGeneral;
use GraphQL\Server\RequestError;

class CodigosLibrosGenerarController extends Controller
{
    use TraitCodigosGeneral;
    protected $codigosRepository;
    public function __construct(CodigosRepository $codigosRepository)
    {
        $this->codigosRepository = $codigosRepository;
    }
    public function index(Request $request)
    {
        $codigos_libros = DB::SELECT("SELECT * from codigoslibros limit 100");
        return $codigos_libros;
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
    public function generarCodigosUnicos(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos = json_decode($request->data_codigos);
        //checkTipo 0 => normales; 1 => con institucion y venta lista y periodo
        $checkTipo = $request->checkTipo;
        $codigosIngresados = [];
        $contador = 0;
        foreach($codigos as $key => $item){
            for($i = 0; $i<$item->cantidad;$i++){
                $ingresar = false;
                while ($ingresar == false) {
                    $getCode  = $this->makeid($item->longitud_codigo);
                    $code     = $item->codigo_liquidacion.'-'.$getCode;
                    //validar si existe ya existe el codigo
                    $validate = DB::SELECT("SELECT * FROM codigoslibros WHERE codigo = '$code'");
                    if(empty($validate)){
                        //si el codigo no existe lo creo
                        $this->ingresarCodigo($item->nombre_serie,$item->nombre,$item->year,$item->idLibro,$request->responsable,$code,$request->estado_codigo_fisico,$checkTipo,$request);
                        //almaceno en un array los codigos
                        $codigosIngresados[$contador] = [
                            "codigo" => $code,
                            "libro"  => $item->nombre
                        ];
                        $contador++;
                        $ingresar = true;
                    }
                    //si ya existe le mando a buscar de nuevo
                    else{
                        $ingresar = false;
                    }
                }
            }
        }
        $codigosVerificados = [];
        //trare un select de los codigos generados
        foreach($codigosIngresados as $key2 => $item2){
            $code = $item2["codigo"];
            $codigosVerif = DB::SELECT("SELECT codigo,libro,estado,fecha_create,estado_codigo_fisico
            FROM codigoslibros WHERE codigo = '$code'");
            $codigosVerificados[$key2] = [
                "codigo"                    => $codigosVerif[0]->codigo,
                "libro"                     => $codigosVerif[0]->libro,
                "estado"                    => $codigosVerif[0]->estado,
                "fecha_create"              => $codigosVerif[0]->fecha_create,
                "estado_codigo_fisico"      => $codigosVerif[0]->estado_codigo_fisico,
            ];
        }
        return ["codigos" => $codigosIngresados,"porcentaje" => $contador,"codigosVerificados" => $codigosVerificados];
    }
    public function ingresarCodigo($serie,$libro,$anio,$idlibro,$idusuario,$codigo,$estado_codigo_fisico,$checkTipo=0,$request=null){
        $codigos_libros                             = new CodigosLibros();
        $codigos_libros->serie                      = $serie;
        $codigos_libros->libro                      = $libro;
        $codigos_libros->anio                       = $anio;
        $codigos_libros->libro_idlibro              = $idlibro;
        $codigos_libros->estado                     = '0';
        $codigos_libros->idusuario                  = 0;
        $codigos_libros->bc_estado                  = 1;
        $codigos_libros->idusuario_creador_codigo   = $idusuario;
        $codigos_libros->codigo                     = $codigo;
        $codigos_libros->estado_codigo_fisico       = $estado_codigo_fisico;
        if($checkTipo == 1){
            $codigos_libros->bc_periodo              = $request->periodo_id;
            $codigos_libros->venta_lista_institucion = $request->institucion_id;
            $codigos_libros->venta_estado            = 2;
            //guardar en el historico
            // $observacion = "Código ingresado desde el modulo generar codigos con institución y venta lista";
            // $this->GuardarEnHistorico(0,$request->institucion_id,$request->periodo_id,$codigo,$idusuario,$observacion,null,null);
        }
        $codigos_libros->save();
    }
    public function generarCodigos(Request $request){
        $repetidos              = array();
        $resp_search            = array();
        $repetido_gen           = array();
        $codigos_validacion     = array();
        $longitud               = $request->longitud;
        $code                   = $request->code;
        $cantidad               = $request->cantidad;
        $codigos = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $caracter = $this->makeid($longitud);
            $codigo = $code.$caracter;
            // valida repetidos en generacion
            $valida_gen = 1;
            $cant_int = 0;
            while ( $valida_gen == 1 ) {
                $caracter = $this->makeid($longitud);
                $codigo = $code.$caracter;
                $valida_gen = 0;
                for( $k=0; $k<count($codigos_validacion); $k++ ){
                    if( $codigo == $codigos_validacion[$k] ){
                        array_push($resp_search, $codigo);
                        $valida_gen = 1;
                        break;
                    }
                }
                $cant_int++;
                if( $cant_int == 10 ){
                    $codigo = "no_disponible";
                    $valida_gen = 0;
                }
            }
            if( $codigo != 'no_disponible' ){
                // valida repetidos en DB
                $validar = DB::SELECT("SELECT codigo from codigoslibros WHERE codigo = '$codigo'");
                $cant_int = 0;
                $codigo_disponible = 1;
                while ( count($validar) > 0 ) {
                    // array_push($repetidos, $codigo);
                    $caracter = $this->makeid($longitud);
                    $codigo = $code.$caracter;
                    $validar = DB::SELECT("SELECT codigo from codigoslibros WHERE codigo = '$codigo'");
                    $cant_int++;
                    if( $cant_int == 10 ){
                        $codigo_disponible = 0;
                        $validar = ['repetido' => 'repetido'];
                    }
                }
                if( $codigo_disponible == 1 ){
                    array_push($codigos_validacion, $codigo);
                    array_push($codigos, ["codigo" => $codigo]);
                    // $codigos[$i] = ["codigo" => $codigo];
                }
            }
        }
        return ["codigos" => $codigos, "repetidos" => $resp_search];
    }
    //api::Get/contadorCodigo
    public function contadorCodigo(Request $request){
        //CONTADOR LIBROS ANTERIORES
        $obtenerNumero = DB::SELECT("SELECT max(contador) as contador FROM codigoslibros c
        WHERE  c.libro_idlibro = '$request->libro'
        AND c.prueba_diagnostica = '0'
        ");
        $contador = 1;
        if(count($obtenerNumero) > 0){
            $contador = $obtenerNumero[0]->contador;
        }
        if($contador == null || $contador == ""){
            $contador = 1;
        }
        //CONTADOR PRUEBA DIANOSTICO
        $obtenerNumeroDiagnostica = DB::SELECT("SELECT max(contador) as contador FROM codigoslibros c
         WHERE  c.libro_idlibro = '$request->libro'
         AND c.prueba_diagnostica = '1'
        ");
        $contadorDiagnostica = 1;
        if(count($obtenerNumeroDiagnostica) > 0){
            $contadorDiagnostica = $obtenerNumeroDiagnostica[0]->contador;
        }
        if($contadorDiagnostica == null || $contadorDiagnostica == ""){
            $contadorDiagnostica = 1;
        }
        $datos = [
            "contador"              => $contador,
            "contadorDiagnostica"   => $contadorDiagnostica
        ];
        return $datos;
    }

    public function store(Request $request)
    {
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $codigos                            = explode(",", $request->codigo);
        $codigosDiagnostico                 = explode(",", $request->codigosDiagnostico);
        $porcentajeAnterior                 = 0;
        $porcentajeDiagnostico              = 0;
        $codigosNoIngresadosAnterior        = [];
        $codigosNoIngresadosDiagnostico     = [];
        //only codigos
        if($request->tipoCodigo == 0){
            $resultado                      = $this->save_Codigos($request,$codigos,0,$request->contador);
            $porcentajeAnterior             = $resultado["porcentaje"];
            $codigosNoIngresadosAnterior    = $resultado["codigosNoIngresados"];
        }
        //only diagnostico
        if($request->tipoCodigo == 1){
            $resultado                      = $this->save_Codigos($request,$codigosDiagnostico,1,$request->contadorDiagnostico);
            $porcentajeDiagnostico          = $resultado["porcentaje"];
            $codigosNoIngresadosDiagnostico = $resultado["codigosNoIngresados"];
        }
        //Ambos
        if($request->tipoCodigo == 2){
            $resultado                      = $this->save_Codigos($request,$codigos,0,$request->contador);
            $resultadoDiagnostico           = $this->save_Codigos($request,$codigosDiagnostico,1,$request->contadorDiagnostico);
            //only codigos
            $porcentajeAnterior             = $resultado["porcentaje"];
            $codigosNoIngresadosAnterior    = $resultado["codigosNoIngresados"];
            //diagnostico
            $porcentajeDiagnostico          = $resultadoDiagnostico["porcentaje"];
            $codigosNoIngresadosDiagnostico = $resultadoDiagnostico["codigosNoIngresados"];
        }
        return[
            "porcentajeAnterior"            => $porcentajeAnterior,
            "codigosNoIngresadosAnterior"   => $codigosNoIngresadosAnterior,
            "porcentajeDiagnostico"         => $porcentajeDiagnostico,
            "codigosNoIngresadosDiagnostico"=> $codigosNoIngresadosDiagnostico,
        ];
    }
    public function save_Codigos($request,$codigos,$prueba_diagnostica,$contador){
        $tam            = sizeof($codigos);
        $porcentaje     = 0;
        $codigosError   = [];
        for( $i=0; $i<$tam; $i++ ){
            $codigos_libros                             = new CodigosLibros();
            $codigos_libros->serie                      = $request->serie;
            $codigos_libros->libro                      = $request->libro;
            $codigos_libros->anio                       = $request->anio;
            $codigos_libros->libro_idlibro              = $request->idlibro;
            $codigos_libros->estado                     = $request->estado;
			$codigos_libros->idusuario                  = 0;
            $codigos_libros->bc_estado                  = 1;
            $codigos_libros->idusuario_creador_codigo   = $request->idusuario;
            $codigos_libros->prueba_diagnostica         = $prueba_diagnostica;
            $codigo_verificar                           = $codigos[$i];
            $verificar_codigo = DB::SELECT("SELECT codigo from codigoslibros WHERE codigo = '$codigo_verificar'");
            if( $verificar_codigo ){
                $codigoNoIngresado = $codigos[$i];
                $codigosError[$i] = [
                    "codigos" => $codigoNoIngresado
                ];
            }else{
                $codigos_libros->codigo = $codigos[$i];
                $codigos_libros->contador = ++$contador;
                $codigos_libros->save();
                $porcentaje++;
            }
        }
        return ["porcentaje" =>$porcentaje ,"codigosNoIngresados" => $codigosError] ;
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function editarCodigoBuscado($datos)
    {

        $data = explode("*", $datos);
        $codigo = $data[0];
        $libro = $data[1];
        $serie = $data[2];
        $anio = $data[3];
        $idusuario_creador_codigo = $data[4];
        $idusuario = $data[5];
        $idLibro = $data[6];
        $id_periodo = $data[7];
        $id_institucion = $data[8];
        if( $codigo != "" ){
            $has_periodo = DB::SELECT("SELECT `id_periodo` FROM `codigoslibros` WHERE `codigo` = '$codigo'");
            if($id_periodo == "undefined") {
                $codigos_libros = DB::UPDATE("UPDATE `codigoslibros` SET `codigo`='$codigo',`serie`='$serie',`libro`='$libro',`anio`='$anio',`idusuario_creador_codigo`=$idusuario_creador_codigo,`idusuario`='$idusuario', `libro_idlibro`=$idLibro WHERE `codigo`= '$codigo'");
                //guardar en el historico
                //se obtiene el periodo actual del codigo
                $periodo = $has_periodo[0]->id_periodo ;
                DB::INSERT("INSERT INTO hist_codlibros(id_usuario, codigo_libro,usuario_editor, idInstitucion,  observacion,id_periodo) VALUES ($idusuario, '$codigo', $id_institucion, $idusuario_creador_codigo, 'modificado', $periodo)");
            }else{
                $codigos_libros = DB::UPDATE("UPDATE `codigoslibros` SET `codigo`='$codigo',`serie`='$serie',`libro`='$libro',`anio`='$anio',`idusuario_creador_codigo`=$idusuario_creador_codigo,`idusuario`='$idusuario', `libro_idlibro`=$idLibro ,`id_periodo` = $id_periodo WHERE `codigo`= '$codigo'");
                //guardar en el historico
                DB::INSERT("INSERT INTO hist_codlibros(id_usuario, codigo_libro,usuario_editor, idInstitucion,  observacion,id_periodo) VALUES ($idusuario, '$codigo', $id_institucion, $idusuario_creador_codigo, 'modificado', $id_periodo)");
            }
            return $codigos_libros;
        }else{
            return 'Codigo no encontrado';
        }
    }


    public function show($id)
    {
        $codigos_libros = DB::SELECT("SELECT * from codigoslibros WHERE libro = '$id'");
        return $codigos_libros;
    }



    public function codigosLibrosFecha($datos)
    {
        $data = explode("*", $datos);
        if( $data[0] != "" ){
            $datalibro = explode("-", $data[0]);
            $fecha = $data[1];
            $libro = $datalibro[1];
            $serie = $datalibro[0];
            $codigos_libros = DB::SELECT("SELECT c.idusuario, c.codigo,
            l.nombrelibro as libro, c.serie, c.anio, c.fecha_create,
            s.id_serie, s.nombre_serie, c.libro_idlibro, u.nombres, u.apellidos, u.cedula,
            i.nombreInstitucion
            FROM codigoslibros c
            INNER JOIN series s ON c.serie = s.nombre_serie
            INNER JOIN libro l ON c.libro_idlibro = l.idlibro
            LEFT JOIN usuario u ON c.idusuario = u.idusuario
            LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
            WHERE c.libro = '$libro'
            AND c.serie = '$serie'
             AND c.created_at like '$fecha%'");
            return $codigos_libros;
        }else{
            return 0;
        }
    }
    public function codigosLibrosCodigo($codigo)
    {

        $codigos_libros = DB::SELECT("SELECT c.verif1,c.verif2,c.verif3,c.verif4,c.verif5,c.verif6,c.verif7,c.verif8,c.verif9,c.verif10,
         c.contrato,c.codigo, c.serie, l.nombrelibro as libro, c.anio, c.idusuario, c.idusuario_creador_codigo, c.libro_idlibro,
         c.contador,c.bc_estado,c.estado_liquidacion,c.venta_estado,
        c.estado, c.fecha_create, c.created_at, c.updated_at, s.id_serie, s.nombre_serie,
        u.nombres, u.apellidos, u.email, u.cedula, i.nombreInstitucion ,
        periodoescolar.descripcion as periodo, periodoescolar.idperiodoescolar
        FROM codigoslibros c
        INNER JOIN series s ON c.serie = s.nombre_serie
        INNER JOIN libro l ON c.libro_idlibro = l.idlibro
        LEFT JOIN usuario u ON c.idusuario = u.idusuario
        LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
        LEFT JOIN periodoescolar ON periodoescolar.idperiodoescolar = c.id_periodo
        WHERE c.codigo like '%$codigo%'");
        return $codigos_libros;
    }
    public function codigosBuscarCodigoXContador($idlibro,$contador){
        $codigos_libros = $this->getCodigos(null,0,2,$idlibro,$contador);
        return $codigos_libros;
    }
    public function codigosBuscarCodigo($codigo){
        $consulta = $this->getCodigosVerificaciones($codigo);
        return $consulta;
    }
    public function codigosBuscarxCodigo($codigo){
        $codigos_libros = $this->getCodigos($codigo,0);
        return $codigos_libros;
    }
    public function librosBuscar(){//select buscar
        //SELECT l.id_libro_serie as id, concat_ws('-', s.nombre_serie, l.nombre) as label from libros_series l, series s WHERE l.id_serie = s.id_serie
        $codigos_libros = DB::SELECT("SELECT l.id_libro_serie as id,idLibro,
        concat_ws('-', s.nombre_serie, l.nombre) as label
        from libros_series l, series s
        WHERE l.id_serie = s.id_serie
        ");
        return $codigos_libros;
    }
    public function codigosLibrosExportados($data){
        $datos = explode("*", $data);
        $usuario = $datos[0];
        $cantidad = $datos[1];
        $codigos_libros = DB::SELECT("SELECT *
        from codigoslibros
        WHERE idusuario_creador_codigo = '$usuario'
        ORDER BY fecha_create
        DESC LIMIT $cantidad");
        return $codigos_libros;
    }
    public function reportesCodigoInst(Request $request){
        $codigos_libros = DB::SELECT("(SELECT COUNT(c.codigo) as cantidad, GROUP_CONCAT(DISTINCT c.serie) as serie, (SELECT l.nombrelibro FROM libro l WHERE l.idlibro = (GROUP_CONCAT(DISTINCT c.libro_idlibro)) ) as libro, GROUP_CONCAT(DISTINCT (SELECT ciudad.nombre FROM ciudad WHERE ciudad.idciudad = i.ciudad_id) ) as ciudad, GROUP_CONCAT(DISTINCT (SELECT GROUP_CONCAT(usuario.nombres,' ',usuario.apellidos) as vendedor FROM usuario WHERE usuario.cedula = (SELECT institucion.vendedorInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) ) as asesor, GROUP_CONCAT(DISTINCT (SELECT i.nombreInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) as institucion FROM codigoslibros c, usuario u, institucion i WHERE c.idusuario = u.idusuario AND u.institucion_idInstitucion = i.idInstitucion AND i.idInstitucion = $request->id AND c.updated_at BETWEEN CAST('$request->fromdate' AS DATE) AND CAST('$request->todate' AS DATE) AND c.codigo not like '%plus%' GROUP BY c.libro_idlibro ORDER BY `c`.`updated_at`  DESC) UNION (SELECT COUNT(c.codigo) as cantidad, GROUP_CONCAT(DISTINCT c.serie, ' PLUS') as serie, (SELECT l.nombrelibro FROM libro l WHERE l.idlibro = (GROUP_CONCAT(DISTINCT c.libro_idlibro)) ) as libro, GROUP_CONCAT(DISTINCT (SELECT ciudad.nombre FROM ciudad WHERE ciudad.idciudad = i.ciudad_id) ) as ciudad, GROUP_CONCAT(DISTINCT (SELECT GROUP_CONCAT(usuario.nombres,' ',usuario.apellidos) as vendedor FROM usuario WHERE usuario.cedula = (SELECT institucion.vendedorInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) ) as asesor, GROUP_CONCAT(DISTINCT (SELECT i.nombreInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) as institucion FROM codigoslibros c, usuario u, institucion i WHERE c.idusuario = u.idusuario AND u.institucion_idInstitucion = i.idInstitucion AND i.idInstitucion = $request->id AND c.updated_at BETWEEN CAST('$request->fromdate' AS DATE) AND CAST('$request->todate' AS DATE) AND c.codigo like '%plus%' GROUP BY c.libro_idlibro ORDER BY `c`.`updated_at`  DESC)");
        return $codigos_libros;
    }
    public function reportesCodigoAsesor($id,$periodo){
        $codigos_libros = DB::SELECT("SELECT c.factura, c.prueba_diagnostica,c.contador,c.codigo_union,
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
                when (c.estado_liquidacion = '4') then 'Código Guia'
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
        from codigoslibros c
        LEFT JOIN usuario u ON c.idusuario = u.idusuario
        LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
        LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
        LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
        LEFT JOIN periodoescolar p ON c.id_periodo = p.idperiodoescolar
        LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
        WHERE (c.bc_periodo  = '$periodo' OR c.id_periodo = '$periodo')
        AND (c.bc_institucion = '$id' OR u.institucion_idInstitucion = '$id' OR c.venta_lista_institucion = '$id' )
        AND c.prueba_diagnostica = '0'
        ");
        return $codigos_libros;
    }
    public function seriesCambiar(){
        $codigos_libros = DB::SELECT("SELECT id_serie as id, nombre_serie as label from series");
        return $codigos_libros;
    }
    public function librosSerieCambiar($id){
        $codigos_libros = DB::SELECT("SELECT idLibro as id, nombre as label from libros_series WHERE id_serie = $id");

        return $codigos_libros;
    }
    public function institucionesResportes(Request $request){
        if($request->filtroInstitucion){
            $instituciones = DB::SELECT("SELECT i.idInstitucion as id, i.region_idregion,i.nombreInstitucion as label, c.nombre as nombre_ciudad, pi.periodoescolar_idperiodoescolar as id_periodo
            from institucion i, ciudad c, periodoescolar_has_institucion pi
            WHERE i.ciudad_id = c.idciudad
            AND i.idInstitucion = pi.institucion_idInstitucion
            AND i.ciudad_id = $request->ciudad_id
             AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = i.idInstitucion)");
        }else{
            $instituciones = DB::SELECT("SELECT i.idInstitucion as id,i.nombreInstitucion as label, c.nombre as nombre_ciudad, pi.periodoescolar_idperiodoescolar as id_periodo
            from institucion i, ciudad c, periodoescolar_has_institucion pi
            WHERE i.ciudad_id = c.idciudad
            AND i.idInstitucion = pi.institucion_idInstitucion
             AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = i.idInstitucion)");
        }
        return $instituciones;
    }


    public function editarInstEstud(Request $request){


           ///Para buscar el periodo

           $verificarperiodoinstitucion = DB::table('periodoescolar_has_institucion')
           ->select('periodoescolar_has_institucion.periodoescolar_idperiodoescolar')

           ->where('periodoescolar_has_institucion.institucion_idInstitucion','=',$request->idInstitucion)
           ->get();

            foreach($verificarperiodoinstitucion  as $clave=>$item){
               $verificarperiodos =DB::SELECT("SELECT p.idperiodoescolar
               FROM periodoescolar p
               WHERE p.estado = '1'
               and p.idperiodoescolar = $item->periodoescolar_idperiodoescolar
               ");
            }

            if(count($verificarperiodoinstitucion) <=0){
               return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];
           }

            //verificar que el periodo exista
           if(count($verificarperiodos) <= 0){
               return ["status"=>"0", "message" => "No existe el periodo lectivo por favor, asigne un periodo a esta institucion"];
            }

         //fin de busqueda del periodo
            //almancenar el periodo
           $periodo =  $verificarperiodos[0]->idperiodoescolar;


     $periodos =   DB::table('codigoslibros')
        ->where('codigo', $request->codigo)
        ->update(['id_periodo' => $periodo]);



        $institucion = DB::UPDATE("UPDATE usuario SET institucion_idInstitucion=$request->idInstitucion WHERE idusuario=$request->id;");



        // DB::UPDATE("UPDATE codigoslibros SET id_periodo=$periodo WHERE codigo=$request->codigo");

        return $institucion;
    }



    public function librosCambiar($id){
        $codigos_libros = DB::SELECT("SELECT * from libros_series WHERE idLibro = $id");

        return $codigos_libros;
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function cambioEstadoCodigo(Request $request)
    {

       $encontrarUsuarioQuemado = DB::select("SELECT idusuario, institucion_idInstitucion
       FROM usuario
       WHERE email = 'quemarcodigos@prolipa.com'
       AND id_group = '4'

       ");

         //almacenar usuario institucion
         $usuarioQuemadoInstitucion = $encontrarUsuarioQuemado[0]->institucion_idInstitucion;


        $traerPeriodo = $this->institucionTraerPeriodo($usuarioQuemadoInstitucion);
        $periodo = $traerPeriodo[0]->periodo;

       if(!empty($encontrarUsuarioQuemado)){


           //almacenar usuario quemado
           $usuarioQuemado = $encontrarUsuarioQuemado[0]->idusuario;
            $cambio = CodigosLibros::find($request->codigo);
            $cambio->estado = $request->estado;

            $cambio->save();
            if($cambio){
               $observacion= DB::INSERT("INSERT INTO hist_codlibros(id_usuario, codigo_libro, idInstitucion, usuario_editor, observacion,id_periodo) VALUES ($usuarioQuemado, '$request->codigo', $request->usuario_editor, '66', '$request->observacion', '$periodo')");


               //Actualizar la tabla codigos libros
               DB::table('codigoslibros')
                ->where('codigo', $request->codigo)
                ->update([
                    'idusuario' => $usuarioQuemado,
                    'id_periodo' => $periodo

                ]);
            }
            if($observacion){
                return $cambio;
            }
       }else{
           return ["status" => "0", "message" => "No se encontro el usuario quemado quemarcodigos@prolipa.com con id de usuario 45017"];
       }


    }

        //api para traer el periodo por institucion
     public function institucionTraerPeriodo($institucion){
            $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo , periodoescolar AS descripcion FROM periodoescolar WHERE idperiodoescolar = (
                SELECT  pi.periodoescolar_idperiodoescolar as id_periodo
                from institucion i,  periodoescolar_has_institucion pi
                WHERE i.idInstitucion = pi.institucion_idInstitucion
                AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
                WHERE phi.institucion_idInstitucion = i.idInstitucion
                AND i.idInstitucion = '$institucion'))
            ");

            return $periodoInstitucion;
    }


    //agregar codigos perdidos, solicitados por soporte
    public function agregar_codigo_perdido(Request $request)
    {
        $agregar = new CodigosLibros();
        $agregar->codigo = $request->codigo;
        $agregar->serie = $request->serie;
        $agregar->libro = $request->libro;
        $agregar->anio = $request->anio;
        $agregar->idusuario =$request->idusuario;
        $agregar->idusuario_creador_codigo = $request->idusuario_creador_codigo;
        $agregar->libro_idlibro = $request->libro_idlibro;
        $agregar->prueba_diagnostica = $request->prueba_diagnostica;
        $agregar->estado = $request->estado;
        $agregar->save();
        return $agregar;
    }
//busqueda de codigos de libros registrados y eliminados por los estudiantes
    public function getHistoricoCodigos($id)
    {
        $codigos = DB::SELECT("SELECT his.codigo_libro,cd.id_periodo, p.descripcion, his.observacion,  his.created_at as fecha_observacion, u.cedula, concat(u.nombres, ' ',  u.apellidos) as usuario, u.email, u.estado_idEstado, i.nombreInstitucion, c.nombre as ciudad, cd.serie, cd.libro, cd.anio, cd.estado, cd.fecha_create
        from hist_codlibros his, usuario u, institucion i, ciudad c, codigoslibros cd
        LEFT JOIN periodoescolar p ON cd.id_periodo = p.idperiodoescolar
        WHERE his.id_usuario = u.idusuario
        AND u.institucion_idInstitucion = i.idInstitucion
        AND i.ciudad_id = c.idciudad
        AND his.codigo_libro = cd.codigo

        -- AND p.idperiodoescolar  = cd.id_periodo
        AND u.cedula  = '$id'");

        return $codigos;
    }

    // metodo para cargar el id del periodo actual del estudiante al cual se le haya asignado cada codigo
    public function cargarPeriodoCodigo()
    {
        set_time_limit(60000);
        ini_set('max_execution_time', 60000);

        $codigos = DB::SELECT("SELECT c.codigo, p.idperiodoescolar FROM codigoslibros c, usuario u, periodoescolar_has_institucion pi, periodoescolar p WHERE c.idusuario IS NOT null AND c.idusuario != 0 AND c.idusuario = u.idusuario AND u.institucion_idInstitucion = pi.institucion_idInstitucion AND pi.periodoescolar_idperiodoescolar = p.idperiodoescolar AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = pi.institucion_idInstitucion) AND c.id_periodo IS null");



        foreach ($codigos as $key => $value) {
            DB::UPDATE("UPDATE `codigoslibros` SET `id_periodo` = ? WHERE `codigo` = ?", [$value->idperiodoescolar, $value->codigo]);
        }
        return ["status"=>"1"];
    }

    //metodo para agregar el periodo a los cursos
    public function agregarPeriodoCurso(){

        set_time_limit(60000);
        ini_set('max_execution_time', 60000);

        $cursos = DB::SELECT("SELECT c.idcurso, p.idperiodoescolar
        FROM curso c, usuario u, periodoescolar_has_institucion pi, periodoescolar p
        WHERE c.idusuario IS NOT null
        AND c.idusuario != 0
        AND c.idusuario = u.idusuario
        AND u.institucion_idInstitucion = pi.institucion_idInstitucion
        AND pi.periodoescolar_idperiodoescolar = p.idperiodoescolar
        AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = pi.institucion_idInstitucion)
        AND c.id_periodo IS null
        LIMIT 100
        ");



        foreach ($cursos as $key => $value) {
            DB::UPDATE("UPDATE `curso` SET `id_periodo` = ? WHERE `idcurso` = ?", [$value->idperiodoescolar, $value->idcurso]);
        }
        return ["status"=>"1"];
    }




    public function hist_codigos($id)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $registro = CodigosLibros::select('codigoslibros.serie','codigoslibros.libro','codigoslibros.fecha_create','codigoslibros.updated_at as actualizado','codigoslibros.idusuario', 'usuario.nombres','usuario.apellidos', 'periodoescolar.periodoescolar', 'periodoescolar.descripcion as periododescripcion', 'institucion.nombreInstitucion', 'ciudad.nombre as nombre_ciudad' )
        ->leftjoin('usuario','codigoslibros.idusuario','=','usuario.idusuario')
        ->leftjoin('libro','codigoslibros.libro_idlibro', '=', 'libro.idlibro')
        ->leftjoin('periodoescolar','codigoslibros.id_periodo', '=', 'periodoescolar.idperiodoescolar')
        ->leftjoin('institucion','usuario.institucion_idInstitucion', '=', 'institucion.idInstitucion')
        ->leftjoin('ciudad','institucion.ciudad_id', '=', 'ciudad.idciudad')
        // ->where('codigoslibros.codigo', 'LIKE',$id)
        ->where('codigoslibros.codigo', '=',$id)
        ->get();


        //en el campo id institucion, esta guardado el id del usuario, y en el usuario editor, el id institucion
        $codigos = DB::SELECT("SELECT h.codigo_libro,i.nombreInstitucion as institucion_historico,
        ins.nombreInstitucion as institucion_usuario, c.nombre as ciudad,
        CONCAT(u.nombres, ' ' , u.apellidos) as usuario_editor,
        CONCAT(us.nombres, ' ' , us.apellidos) as usuario, us.email, us.cedula,
        e.nombreestado,
        h.observacion, h.created_at as fecha_registro,p.periodoescolar as periodo_historico,
        h.old_values,h.new_values,

        (
            SELECT CONCAT(' Cliente: ', d.cliente  , ' - ',d.fecha_devolucion) AS devolucion
            FROM codigos_devolucion d
            WHERE d.codigo = co.codigo
            AND d.estado = '1'
            ORDER BY d.id DESC
            LIMIT 1
        ) as devolucionInstitucion, h.tipo_tabla
        FROM hist_codlibros h
        LEFT JOIN institucion i ON h.usuario_editor = i.idInstitucion
        LEFT JOIN usuario u ON h.idInstitucion = u.idusuario
        LEFT JOIN usuario us ON h.id_usuario = us.idusuario
        LEFT JOIN institucion ins ON us.institucion_idInstitucion = ins.idInstitucion
        LEFT JOIN periodoescolar p ON p.idperiodoescolar = h.id_periodo
        LEFT JOIN ciudad c ON ins.ciudad_id = c.idciudad
        LEFT JOIN estado e on us.estado_idEstado = e.idEstado
        LEFT JOIN codigoslibros co ON h.codigo_libro = co.codigo
        -- WHERE h.codigo_libro  LIKE '$id%'
        WHERE h.codigo_libro  = '$id'
        ORDER BY h.created_at DESC");

        return ['historico'=> $codigos, 'registro'=>$registro];
    }
    public function guardarCodigos2(Request $request){
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $codigos                    = json_decode($request->data_codigos);
        $contador                   = $request->contador;
        $codigosError               = [];
        $codigosGuardados           = [];
        $contadorError              = 0;
        $porcentajeA                = 0;
        $contadorUnion              = 0;
        $prueba_diagnostica         = $request->prueba_diagnostica;
        foreach($codigos as $key => $item){
            $codigo                 = "";
            $codigo                 = $item->codigo;
            $statusIngreso          = 0;
            $contadorCodigoA        = "";
            $ingresoA               = $this->codigosRepository->save_Codigos($request,$item,$codigo,$prueba_diagnostica,$contador);
            $statusIngreso          = $ingresoA["contadorIngreso"];
            $contadorCodigoA        = $ingresoA["contador"];
            //si ingresa el codigo de activacion y el codigo de diagnostico
            if($statusIngreso == 1){
                $contador++;
                $porcentajeA++;
                $codigosGuardados[$contadorUnion] = [
                    "codigo"            => $codigo,
                    "libro"              => $item->libro,
                    "serie"              => $item->serie,
                    "anio"               => $item->anio,
                    "contadorCodigoA"    => $contadorCodigoA,
                ];
                $contadorUnion++;
            }else{
                $codigosError[$contadorError] = [
                    "codigo"  => $codigo,
                    "message"            => "Problemas no se ingresaron bien"
                ];
                $contadorError++;
            }
        }
        return [
            "porcentajeA"           => $porcentajeA ,
            "codigosNoIngresados"   => $codigosError,
            "codigosGuardados"      => $codigosGuardados,
        ];
    }
}
