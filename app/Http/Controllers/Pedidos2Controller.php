<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\Institucion;
use App\Models\User;
use App\Models\Usuario;
use App\Repositories\pedidos\PedidosRepository;
use App\Traits\Pedidos\TraitPedidosGeneral;
use DB;
use Illuminate\Http\Request;

class Pedidos2Controller extends Controller
{
    use TraitPedidosGeneral;
    protected $pedidosRepository = null;
    public function __construct(PedidosRepository $pedidosRepository)
    {
        $this->pedidosRepository = $pedidosRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //pedidos2/pedidos
    public function index(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        if($request->getLibrosFormato)              { return $this->getLibrosFormato($request->periodo_id); }
        if($request->geAllLibrosxAsesor)            { return $this->geAllLibrosxAsesor($request->asesor_id,$request->periodo_id); }
        //api:get/pedidos2/pedidos?getAsesoresPedidos=1
        if($request->getAsesoresPedidos)            { return $this->getAsesoresPedidos(); }
        if($request->getInstitucionesDespacho)      { return $this->getInstitucionesDespacho($request); }
        if($request->getLibrosXDespacho)            { return $this->getLibrosXDespacho($request); }
        if($request->getLibrosXInstituciones)       { return $this->getLibrosXInstituciones($request->id_periodo,$request->tipo_venta); }
        if($request->getLibrosXInstitucionesAsesor) { return $this->getLibrosXInstitucionesAsesor($request->id_periodo,$request->tipo_venta,$request->id_asesor); }
        if($request->getproStockReserva)            { return $this->getproStockReserva($request); }
        if($request->getDatosPuntosVenta)           { return $this->getDatosPuntosVenta($request); }
        if($request->getDatosClient)                { return $this->getDatosClient($request); }
        if($request->getDatosClientes)              { return $this->getDatosClientes($request); }
        if($request->getDatosDespachoProforma)      { return $this->getDatosDespachoProforma($request); }
        if($request->InformacionAgrupado)           { return $this->InformacionAgrupado($request->codigo); }
        if($request->informacionInstitucionPerseo)  { return $this->informacionInstitucionPerseo($request); }
    }
    //API:GET/pedidos2/pedidos?getLibrosFormato=yes&periodo_id=22
    /**
     * Get the libros formato for a given periodo.
     *
     * @param  string  $periodo
     * @return \Illuminate\Support\Collection
     */
    public function getLibrosFormato($periodo){
        $librosNormales = [];
        $librosPlan     = [];
        $resultado      = [];
        $librosNormales = $this->pedidosRepository->getLibrosNormalesFormato($periodo);
        $librosPlan     = $this->pedidosRepository->getLibrosPlanLectorFormato($periodo);
        //unir los dos arreglos
        $resultado      = array_merge(array($librosNormales),array($librosPlan));
        $coleccion      = collect($resultado)->flatten(10);
        return $coleccion;
    }
    //api:get/pedidos2/pedidos?getInstitucionesDespacho=1
    public function getInstitucionesDespacho($request){
        $id_periodo = $request->id_periodo;
        $query      = $this->tr_getInstitucionesDespacho($id_periodo);
        $datos = [];
        //sacar el id de pedido de cada ca_codigo_agrupado
        foreach($query as $key => $item){
            $datos[$key] = [
                'ca_codigo_agrupado' => $item->ca_codigo_agrupado,
                'id_periodo'        => $item->id_periodo,
                'codigo_contrato'   => $item->codigo_contrato,
                'pedidos'           => $this->tr_pedidosXDespacho($item->ca_codigo_agrupado,$id_periodo),
                'ca_descripcion'    => $item->ca_descripcion,
                'ca_tipo_pedido'    => $item->ca_tipo_pedido,
                'ca_id'             => $item->ca_id,
            ];
        }
        //que sean unicos
        $datos = collect($datos)->unique('ca_codigo_agrupado')->values();
        return $datos;
    }
    public function editarInstitucionDespacho(Request $request){
        $institucion = Institucion::findOrFail($request->idInstitucion);
        $institucion->ruc = $request->ruc;
        $institucion->telefonoInstitucion = $request->telefonoInstitucion;
        $institucion->email = $request->email;
        $institucion->direccionInstitucion = $request->direccionInstitucion;
        $institucion->save();
        if ($institucion->wasRecentlyCreated || $institucion->wasChanged()) {
            return "Se guardó correctamente";
        } else {
            return "No se pudo guardar/actualizar";
        }
    }
    //API:GET/pedidos2/pedidos?getLibrosXDespacho=yes&id_pedidos=1,2,3
    public function getLibrosXDespacho($request){
        $query = $this->geAllLibrosxAsesor(0,$request->id_periodo,1,$request->id_pedidos);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getLibrosXInstituciones=yes&id_periodo=23&tipo_venta=1
    public function getLibrosXInstituciones($id_periodo,$tipo_venta){
        $query = $this->tr_getInstitucionesVentaXTipoVenta($id_periodo,$tipo_venta);
        $id_pedidos = "";
        //crear un coleccion y pluck de id_pedido
        $id_pedidos = collect($query)->pluck('id_pedido');
        //convertir en string
        $id_pedidos = implode(",",$id_pedidos->toArray());
        $query = $this->geAllLibrosxAsesor(0,$id_periodo,1,$id_pedidos);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getLibrosXInstitucionesAsesor=yes&id_periodo=23&tipo_venta=1&id_asesor=1
    public function getLibrosXInstitucionesAsesor($id_periodo,$tipo_venta,$asesor){
        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo,$tipo_venta,$asesor);
        $id_pedidos = "";
        //crear un coleccion y pluck de id_pedido
        $id_pedidos = collect($query)->pluck('id_pedido');
        //convertir en string
        $id_pedidos = implode(",",$id_pedidos->toArray());
        $query = $this->geAllLibrosxAsesor(0,$id_periodo,1,$id_pedidos);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getproStockReserva=yes
    public function getproStockReserva($pro_codigo){
        $query = DB::SELECT("SELECT pro_codigo,pro_reservar FROM 1_4_cal_producto WHERE pro_codigo = '$pro_codigo' ");
        return $query;
    }
    //API:GET/pedidos2/pedidos?getDatosPuntosVenta=1&busqueda=prolipa&id_periodo=22
    public function getDatosPuntosVenta($request){
        $busqueda   = $request->busqueda;
        $id_periodo = $request->id_periodo;
        $query = $this->tr_getPuntosVenta($busqueda);
        //traer datos de la tabla f_formulario_proforma por id_periodo
        foreach($query as $key => $item){ $query[$key]->datosInstitucion = DB::SELECT("SELECT * FROM f_formulario_proforma fp WHERE fp.idInstitucion = '$item->idInstitucion' AND fp.idperiodoescolar = '$id_periodo'"); }
        return $query;
    }
    //API:GET/pedidos2/pedidos?getDatosClient=1&busqueda=prolipa
    public function getDatosClient($request){
        $busqueda = $request->busqueda;
        $query = $this->tr_getCliente($busqueda);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getDatosClientes=1&busqueda=prolipa
    public function getDatosClientes($request){
        $busqueda = $request->busqueda;
        $query = $this->tr_getClientes($busqueda);
        return $query;
    }
    //API:GET/pedidos2/pedidos?getDatosDespachoProforma=1&idProforma=P-ER-0000022&id_periodo=22
    public function getDatosDespachoProforma($request){
        $idProforma = $request->idProforma;
        $query = $this->tr_getDespachoProforma($idProforma);
        //traer datos de la tabla f_formulario_proforma por id_periodo
        foreach($query as $key => $item){ $query[$key]->datosInstitucion = DB::SELECT("SELECT * FROM f_formulario_proforma fp WHERE fp.idInstitucion = '$item->id_ins_depacho' AND fp.idperiodoescolar = '$request->id_periodo'"); }
        return $query;
    }
    //API:GET/pedidos2/pedidos?InformacionAgrupado=1&codigo=AG-44
    public function InformacionAgrupado($codigo){
        $query = $this->tr_getInformacionAgrupado($codigo);
        //traer la primera proforma con estado diferente a 3
        $query[0]->proformas = DB::SELECT("SELECT p.*, i.nombreInstitucion, c.nombre AS ciudad,
        CONCAT(u.nombres, ' ', u.apellidos) AS cliente, u.email as emailCliente
        FROM f_proforma p
        LEFT JOIN institucion i ON p.id_ins_depacho = i.idInstitucion
        LEFT JOIN ciudad c ON i.ciudad_id = c.idciudad
        LEFT JOIN usuario u ON p.ven_cliente = u.idusuario
        WHERE p.idPuntoventa = ?
        AND p.prof_estado <> '0'
        LIMIT 1",[$codigo]);
        if(count($query[0]->proformas) > 0){
            $id_ins_depacho = $query[0]->proformas[0]->id_ins_depacho;
            $id_periodo     = $query[0]->id_periodo;
            $query[0]->proformas[0]->datosInstitucion = DB::SELECT("SELECT * FROM f_formulario_proforma fp WHERE fp.idInstitucion = '$id_ins_depacho' AND fp.idperiodoescolar = '$id_periodo'");
        }
        return $query;
    }
    //API:GET/pedidos2/pedidos?informacionInstitucionPerseo=1&institucion_id=981
    public function informacionInstitucionPerseo($request){
        $institucion_id = $request->institucion_id;
        $query = $this->tr_getInformacionInstitucionPerseo($institucion_id);
        return $query;
    }
    //API:GET/pedidos2/pedidos?geAllLibrosxAsesor=yes&asesor_id=4179&periodo_id=22
    public function geAllLibrosxAsesor($asesor_id,$periodo_id,$tipo = null,$parametro1=null){
        $val_pedido = [];
        //por asesor
        if($tipo == null){
            $val_pedido = DB::SELECT("SELECT DISTINCT pv.valor,
            pv.id_area, pv.tipo_val, pv.id_serie, pv.year,pv.plan_lector,pv.alcance,
            p.id_periodo,
            CONCAT(se.nombre_serie,' ',ar.nombrearea) as serieArea,
            se.nombre_serie
            FROM pedidos_val_area pv
            LEFT JOIN area ar ON  pv.id_area = ar.idarea
            LEFT JOIN series se ON pv.id_serie = se.id_serie
            LEFT JOIN pedidos p ON pv.id_pedido = p.id_pedido
            LEFT JOIN usuario u ON p.id_asesor = u.idusuario
            WHERE p.id_asesor = '$asesor_id'
            AND p.id_periodo  = '$periodo_id'
            AND p.tipo        = '0'
            AND p.estado      = '1'
            GROUP BY pv.id
            ");
        }
        //por varios ids de pedido
        if($tipo == 1){
            //quitar las comas y convertir en array
            $ids = explode(",",$parametro1);
            $val_pedido = DB::table('pedidos_val_area as pv')
            ->selectRaw('DISTINCT pv.valor, pv.id_area, pv.tipo_val, pv.id_serie, pv.year, pv.plan_lector, pv.alcance,
                        p.id_periodo,
                        CONCAT(se.nombre_serie, " ", ar.nombrearea) as serieArea,
                        se.nombre_serie')
            ->leftJoin('area as ar', 'pv.id_area', '=', 'ar.idarea')
            ->leftJoin('series as se', 'pv.id_serie', '=', 'se.id_serie')
            ->leftJoin('pedidos as p', 'pv.id_pedido', '=', 'p.id_pedido')
            ->leftJoin('usuario as u', 'p.id_asesor', '=', 'u.idusuario')
            ->whereIn('p.id_pedido', $ids)
            ->where('p.tipo', '0')
            ->where('p.estado', '1')
            ->where('p.id_periodo',$periodo_id)
            ->groupBy('pv.id')
            ->get();
        }
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
                $getPlanlector = DB::SELECT("SELECT l.nombrelibro,l.idlibro,pro.pro_reservar,
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
                $getLibros = DB::SELECT("SELECT ls.*, l.nombrelibro, l.idlibro,pro.pro_reservar,
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
                "valor"             => $item->valor,
                // "tipo_val"          => $item->tipo_val,
                "id_serie"          => $item->id_serie,
                // "year"              => $item->year,
                // "anio"              => $valores[0]->year,
                // "version"           => $valores[0]->version,
                // "plan_lector"       => $item->plan_lector,
                "serieArea"         => $item->id_serie == 6 ? $item->nombre_serie." ".$valores[0]->nombrelibro : $item->serieArea,
                "libro_id"          => $valores[0]->idlibro,
                "nombrelibro"       => $valores[0]->nombrelibro,
                "nombre_serie"      => $item->nombre_serie,
                "precio"            => $valores[0]->precio,
                "codigo"            => $valores[0]->codigo_liquidacion,
                "stock"             => $valores[0]->pro_reservar,
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
        //array unicos con array unique
        // $resultado  = [];
        // $resultado  = array_unique($datos, SORT_REGULAR);
        // $coleccion  = collect($resultado);
        // return $coleccion->values();
    }
    public function getAlcanceAbiertoXId($id){
        $query = DB::SELECT("SELECT * FROM pedidos_alcance a
        WHERE a.id = '$id'
        AND a.estado_alcance = '1'");
        return $query;
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
    //API:POST/pedidos2/pedidos
    public function store(Request $request)
    {
        if($request->getValoresLibrosContratos)                         { return $this->getValoresLibrosContratos($request->asesor_id,$request->periodo_id,$request); }
        if($request->getValoresLibrosContratosDespacho)                 { return $this->getValoresLibrosContratosDespacho($request); }
        if($request->getValoresLibrosContratosInstituciones)            { return $this->getValoresLibrosContratosInstituciones($request); }
        if($request->getValoresLibrosContratosInstitucionesAsesor)      { return $this->getValoresLibrosContratosInstitucionesAsesor($request); }
        if($request->crearUsuario)                                      { return $this->crearUsuario($request); }
        if($request->updateClienteInstitucion)                          { return $this->updateClienteInstitucion($request); }
    }
      //API:POST/pedidos2/pedidos?getValoresLibrosContratos
      public function getValoresLibrosContratos($asesor_id,$periodo_id,$request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        // $arrayLibros = $this->geAllLibrosxAsesor($asesor_id,$periodo_id);
        $query = DB::SELECT("SELECT p.id_pedido, p.contrato_generado,
            i.nombreInstitucion, c.nombre as ciudad
            FROM pedidos p
            LEFT JOIN institucion i ON p.id_institucion = i.idInstitucion
            LEFT JOIN ciudad c ON c.idciudad = i.ciudad_id
            WHERE p.id_asesor = ?
            AND p.estado = '1'
            AND p.tipo = '0'
            AND p.contrato_generado IS NOT NULL
            AND p.id_periodo = ?
        ",[$asesor_id,$periodo_id]);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
    }
    //API:POST/pedidos2/pedidos?getValoresLibrosContratosDespacho
    public function getValoresLibrosContratosDespacho($request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        $ca_codigo_agrupado = $request->id_despacho;
        $id_periodo  = $request->id_periodo;
        $query = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
    }
    //API:POST/pedidos2/pedidos?getValoresLibrosContratosInstituciones
    public function getValoresLibrosContratosInstituciones($request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        $id_periodo  = $request->id_periodo;
        $tipo_venta  = $request->tipo_venta;
        $query = $this->tr_getInstitucionesVentaXTipoVenta($id_periodo,$tipo_venta);
        // $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'id_asesor'         => $item->id_asesor,
                'asesor'            => $item->asesor,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
    }
    //API:POST/pedidos2/pedidos?getValoresLibrosContratosInstitucionesAsesor
    public function getValoresLibrosContratosInstitucionesAsesor($request){
        $arrayLibros = [];
        $arrayLibros = json_decode($request->arrayLibros);
        $id_periodo  = $request->id_periodo;
        $tipo_venta  = $request->tipo_venta;
        $id_asesor   = $request->id_asesor;
        $query = $this->tr_getInstitucionesVentaXTipoVentaAsesor($id_periodo,$tipo_venta,$id_asesor);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'id_asesor'         => $item->id_asesor,
                'asesor'            => $item->asesor,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
         return $datos;
    }
    //API:POST/pedidos2/pedidos/crearUsuario=1
    public function crearUsuario($request){
        $password                           = sha1(md5($request->cedula));
        $user                               = new User();
        $user->cedula                       = $request->cedula;
        $user->nombres                      = $request->nombres;
        $user->apellidos                    = $request->apellidos;
        $user->name_usuario                 = $request->email;
        $user->password                     = $password;
        $user->email                        = $request->email;
        $user->id_group                     = $request->id_grupo;
        $user->institucion_idInstitucion    = $request->institucion;
        $user->estado_idEstado              = 1;
        $user->idcreadorusuario             = $request->user_created;
        $user->telefono                     = $request->telefono;
        $user->save();
        $query = DB::SELECT("SELECT u.idusuario,u.cedula,u.nombres,u.apellidos,u.email,u.telefono,  CONCAT(u.nombres,' ',u.apellidos) as usuario
        FROM usuario u
        WHERE u.cedula = '$request->cedula'
        ");
        return $query;
    }
    //API:POST/pedidos2/pedidos/updateClienteInstitucion=1
    public function updateClienteInstitucion($request){
        $institucion                                = Institucion::findOrFail($request->id_institucion);
        $empresa                                    = $request->empresa;
        if($empresa == 1){
            $institucion->idrepresentante_prolipa   = $request->idusuario;
            $institucion->cliente_perseo_prolipa    = $request->clientePerseo;
        }
        if($empresa == 3){
            $institucion->idrepresentante_calmed    = $request->idusuario;
            $institucion->cliente_perseo_calmed     = $request->clientePerseo;
        }
        $institucion->save();
        if($institucion){
            return ["status" => "1", "message" => "Se actualizó correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo actualizar"];
        }
    }
    //api:get/pedidosDespacho?ca_codigo_agrupado=1265&id_periodo=23
    public function pedidosDespacho(Request $request){
        $ca_codigo_agrupado = $request->id_despacho;
        $id_periodo  = $request->id_periodo;
        $arrayLibros = [];
        //get id_pedidos de despacho
        $query       = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        if(empty($query)){
            return ["status" => "0", "message" => "No se encontraron pedidos en el despacho"];
        }
        $arrayIds    = [];
        //guardar en un array los id de pedidos
        foreach($query as $key => $item){
            if($item->contrato_generado!=null){
                $arrayIds[] = (String)$item->id_pedido;
            }
         }
        if(empty($arrayIds)){
            //response codigo 404
            return ["status" => "0", "message" => "No se encontraron pedidos para este despacho"];
        }
        //convierte en string el array
        $arrayIds = implode(",",$arrayIds);
        $arrayLibros = $this->geAllLibrosxAsesor(0,$id_periodo,1,$arrayIds);
        $ca_codigo_agrupado = $request->id_despacho;
        $id_periodo  = $request->id_periodo;
        $query = $this->tr_pedidosXDespacho($ca_codigo_agrupado,$id_periodo);
        $datos = [];
        foreach($query as $key => $item){
            $validate               = [];
            if($item->contrato_generado!=null){
                $validate               = $this->obtenerValores($arrayLibros,$item->id_pedido);
            }
            $datos[$key] = [
                'id_pedido'         => $item->id_pedido,
                'contrato_generado' => $item->contrato_generado,
                'nombreInstitucion' => $item->nombreInstitucion,
                'ciudad'            => $item->ciudad,
                'librosFormato'     => $validate,
            ];
        }
        //del arreglo datos voy a recorrer el arrayLibros y sumar la propiedad valor que esta en librosFormato
        $resultado = [];
        foreach($arrayLibros as $key => $item){
            //del array de datos en la propiedad librosFormato voy a filtrar por el libro_id y sumar el valor
            //y sumar el valor
            $total = 0;
            foreach($datos as $k => $tr){
                $libro = collect($tr['librosFormato'])->where('libro_id',$item->libro_id)->first();
                if($libro){
                    $total += $libro['valor'];

                }
            }
            $resultado[$key] = [
                "nombrelibro"        => $item->nombrelibro,
                "nombre_serie"       => $item->nombre_serie,
                "precio"             => $item->precio,
                "codigo_liquidacion" => $item->codigo,
                "libro_id"           => $item->libro_id,
                "valor"              => $total,
                "stock"              => $item->stock,
                "cantidad"           => 0
            ];
        }
        return $resultado;
    }
    public function obtenerValores($arrayLibros,$id_pedido){
        $validate               = [];
        $libroSolicitados       = [];
        $libroSolicitados       = $this->pedidosRepository->obtenerLibroxPedidoTodo($id_pedido);
        foreach($arrayLibros as $key =>  $item){
            $validate[$key] = $this->validarIfExistsLibro($item,$libroSolicitados);
        }
        return $validate;
    }
    public function validarIfExistsLibro($Objectlibro,$libroSolicitados){
        //buscar el idLibro en el array de libros solicitados
        $resultado  = [];
        $coleccion  = collect($libroSolicitados);
        $libro = $coleccion->where('idlibro',$Objectlibro->libro_id)->first();
        if($libro){
            $resultado = [
                'libro_id'      => $Objectlibro->libro_id,
                'nombrelibro'   => $Objectlibro->nombrelibro,
                'valor'         => $libro->valor,
                "codigo"        => $Objectlibro->codigo,
                "precio"        => $Objectlibro->precio,
            ];
        }
        else{
            $resultado = [
                'libro_id'      => $Objectlibro->libro_id,
                'nombrelibro'   => $Objectlibro->nombrelibro,
                'valor'         => 0,
                "codigo"        => $Objectlibro->codigo,
                "precio"        => $Objectlibro->precio,
            ];
        }
        return $resultado;
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
}
