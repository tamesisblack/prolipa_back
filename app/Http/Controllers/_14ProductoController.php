<?php

namespace App\Http\Controllers;

use App\Models\_14Producto;
use App\Models\Libro;
use App\Models\LibroSerie;
use App\Models\_14ProductoStockHistorico;
use App\Models\f_movimientos_producto;
use App\Models\f_movimientos_detalle_producto;
use App\Models\f_tipo_documento;
use App\Models\Institucion;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class _14ProductoController extends Controller {
    public function GetProducto() {
        $query = DB:: SELECT("SELECT * FROM 1_4_cal_producto ORDER BY pro_nombre ASC");
        return $query;
    }

    public function GetProducto_Reportes(Request $request) {
        $grupoCodigos = $request->input('grupo_codigo_selected');
        $tipoReporte = $request->input('tipoReporte');
        // return $tipoReporte;
        if ($tipoReporte == 1) {
            $query = DB::select("SELECT pr.*, gp.gru_pro_nombre FROM 1_4_cal_producto pr
                LEFT JOIN 1_4_grupo_productos gp ON pr.gru_pro_codigo = gp.gru_pro_codigo
                ORDER BY pr.pro_nombre ASC");
            return $query;
        }
        // Verifica que haya elementos
        if (is_array($grupoCodigos) && count($grupoCodigos) > 0) {
            $placeholders = implode(',', array_fill(0, count($grupoCodigos), '?'));

            $query = DB::select("SELECT pr.*, gp.gru_pro_nombre FROM 1_4_cal_producto pr
                LEFT JOIN 1_4_grupo_productos gp ON pr.gru_pro_codigo = gp.gru_pro_codigo
                WHERE pr.gru_pro_codigo IN ($placeholders)
                ORDER BY pr.pro_nombre ASC", $grupoCodigos);

            return $query;
        }
        // Si no hay grupos seleccionados, devuelve todos
        return DB::select("SELECT * FROM 1_4_cal_producto ORDER BY pro_nombre ASC");
    }
    //INICIO SECCION OBETENER LISTADO COMBOS X TEMPORADA
    public function GetListaCombosXTemporada(Request $request)
    {
        try {
            $request->validate([
                'idperiodoescolar_selected' => 'required|integer',
                'verificar_periodo_busqueda' => 'required|string',
            ]);
            $idPeriodo = $request->input('idperiodoescolar_selected');
            $tipoBusqueda = $request->input('verificar_periodo_busqueda');
            $pedidos = DB::table('pedidos')
                ->where('estado', '<>', 2)
                ->where('tipo', 0)
                ->where('id_periodo', $idPeriodo)
                ->whereNotNull('contrato_generado')
                ->pluck('id_pedido');
            if ($pedidos->isEmpty()) {
                return response()->json([
                    'status' => 0,
                    'mensaje' => 'No hay pedidos ni alcances de combos para esta temporada.'
                    ], 200);
            }
            if ($tipoBusqueda === 'es_menor_o_igual') {
                $productos = $this->obtenerProductosPedidosNormales($pedidos);
                $productosAlcance = $this->obtenerProductosPedidosAlcance($idPeriodo);
                $idsAlcance = collect($productosAlcance)
                    ->pluck('pedidos_alcance')
                    ->filter()
                    ->flatMap(fn($alcances) => explode(',', $alcances))
                    ->unique()
                    ->values()
                    ->all();
                $mapaAlcancePedido = DB::table('pedidos_alcance')
                    ->whereIn('id', $idsAlcance)
                    ->pluck('id_pedido', 'id')
                    ->toArray();
                $productosFinal = $this->unificarProductosConAlcance($productos, $productosAlcance, $mapaAlcancePedido);
                return response()->json([
                    'status' => 1,
                    'productos_pedidos_agrupados' => $productos,
                    'productos_alcance_agrupados' => $productosAlcance,
                    'productos_final_agrupados' => $productosFinal->values(),
                ], 200);
            } elseif ($tipoBusqueda === 'es_mayor') {
                $productosNew = $this->obtenerProductosPedidosNormalesNuevo($pedidos);
                $productosAlcanceNew = $this->obtenerProductosPedidosAlcanceNuevo($idPeriodo);
                $idsAlcance = $productosAlcanceNew
                    ->pluck('pedidos_alcance')
                    ->filter()
                    ->flatMap(fn($alcances) => explode(',', $alcances))
                    ->unique()
                    ->values()
                    ->all();
                $mapaAlcancePedido = DB::table('pedidos_alcance')
                    ->whereIn('id', $idsAlcance)
                    ->pluck('id_pedido', 'id')
                    ->toArray();
                $productosFinalNew = $productosNew
                    ->merge($productosAlcanceNew)
                    ->groupBy('pro_codigo')
                    ->map(function ($items) use ($mapaAlcancePedido) {
                        $pedidos = $items->pluck('pedidos')
                            ->filter()
                            ->flatMap(fn($p) => explode(',', $p))
                            ->unique()
                            ->values()
                            ->all();
                        $alcancesRaw = $items->pluck('pedidos_alcance')
                            ->filter()
                            ->flatMap(fn($a) => explode(',', $a))
                            ->unique()
                            ->values()
                            ->all();
                        $pedidos_alcance = [];
                        foreach ($alcancesRaw as $idAlcance) {
                            $idAlcance = trim($idAlcance);
                            if (isset($mapaAlcancePedido[$idAlcance])) {
                                $pedidos_alcance[] = [
                                    $idAlcance => [
                                        'id_pedido' => [$mapaAlcancePedido[$idAlcance]]
                                    ]
                                ];
                            }
                        }
                        return (object)[
                            'pro_codigo' => $items[0]->pro_codigo,
                            'total_valor' => $items->sum('total_valor'),
                            'pedidos' => $pedidos,
                            'pedidos_alcance' => $pedidos_alcance,
                            'nombre_serie' => optional($items->first(fn($i) => !empty($i->nombre_serie)))->nombre_serie,
                            'nombrearea' => optional($items->first(fn($i) => !empty($i->nombrearea)))->nombrearea,
                            'nombreasignatura' => optional($items->first(fn($i) => !empty($i->nombreasignatura)))->nombreasignatura,
                        ];
                    })->values();
                return response()->json([
                    'status' => 1,
                    'productos_pedidos_agrupados' => $productosNew,
                    'productos_alcance_agrupados' => $productosAlcanceNew,
                    'productos_final_agrupados' => $productosFinalNew,
                ], 200);
            }
            return response()->json(['mensaje' => 'Tipo de búsqueda inválido'], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Parámetros inválidos',
                'detalles' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los combos',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }
    // Métodos auxiliares
    private function obtenerProductosPedidosNormales($pedidos)
    {
        return DB::select("SELECT p.pro_codigo,
                SUM(pva.valor) AS total_valor,
                GROUP_CONCAT(DISTINCT pva.id_pedido) AS pedidos,
                MAX(s.nombre_serie) AS nombre_serie,
                MAX(ar.nombrearea) AS nombrearea,
                MAX(asi.nombreasignatura) AS nombreasignatura
            FROM pedidos_val_area pva
            LEFT JOIN series s ON pva.id_serie = s.id_serie
            LEFT JOIN area ar ON pva.id_area = ar.idarea
            LEFT JOIN asignatura asi ON asi.area_idarea = pva.id_area
            LEFT JOIN libro l ON l.asignatura_idasignatura = asi.idasignatura
            LEFT JOIN libros_series ls
                ON ls.idLibro = l.idlibro
                AND ls.id_serie = pva.id_serie
                AND ls.year = pva.year
            LEFT JOIN 1_4_cal_producto p ON p.pro_codigo = ls.codigo_liquidacion
            WHERE pva.id_pedido IN (" . $pedidos->implode(',') . ")
            AND p.pro_codigo IS NOT NULL
            AND pva.id_serie = 19
            AND pva.alcance = 0
            GROUP BY p.pro_codigo, ls.codigo_liquidacion");
    }
    private function obtenerProductosPedidosAlcance($idPeriodo)
    {
        return DB::select("SELECT p.pro_codigo,
                SUM(pva.valor) AS total_valor,
                GROUP_CONCAT(DISTINCT pa.id) AS pedidos_alcance,
                MAX(s.nombre_serie) AS nombre_serie,
                MAX(ar.nombrearea) AS nombrearea,
                MAX(asi.nombreasignatura) AS nombreasignatura
            FROM pedidos_alcance pa
            JOIN pedidos_val_area pva ON pa.id = pva.alcance
            LEFT JOIN series s ON pva.id_serie = s.id_serie
            LEFT JOIN area ar ON pva.id_area = ar.idarea
            LEFT JOIN asignatura asi ON asi.area_idarea = pva.id_area
            LEFT JOIN libro l ON l.asignatura_idasignatura = asi.idasignatura
            LEFT JOIN libros_series ls
                ON ls.idLibro = l.idlibro
                AND ls.id_serie = pva.id_serie
                AND ls.year = pva.year
            LEFT JOIN 1_4_cal_producto p ON p.pro_codigo = ls.codigo_liquidacion
            WHERE pa.estado_alcance = 1
            AND pa.id_periodo = ?
            AND pva.id_serie = 19
            AND p.pro_codigo IS NOT NULL
            GROUP BY p.pro_codigo, ls.codigo_liquidacion", [$idPeriodo]);
    }
    private function unificarProductosConAlcance($productos, $productosAlcance, $mapaAlcancePedido)
    {
        $productosFinal = collect($productos)->mapWithKeys(function ($item) {
            return [$item->pro_codigo => [
                'pro_codigo' => $item->pro_codigo,
                'total_valor' => $item->total_valor,
                'pedidos' => $item->pedidos ? explode(',', $item->pedidos) : [],
                'pedidos_alcance' => [],
                'nombre_serie' => $item->nombre_serie,
                'nombrearea' => $item->nombrearea,
                'nombreasignatura' => $item->nombreasignatura,
            ]];
        })->toArray();

        foreach ($productosAlcance as $alcance) {
            $pedidosAlcanceArray = [];
            $idsAlcance = $alcance->pedidos_alcance ? explode(',', $alcance->pedidos_alcance) : [];
            foreach ($idsAlcance as $idAlcance) {
                $idAlcance = trim($idAlcance);
                $idPedido = $mapaAlcancePedido[$idAlcance] ?? null;
                if ($idPedido) {
                    $pedidosAlcanceArray[] = [
                        $idAlcance => [
                            'id_pedido' => [$idPedido]
                        ]
                    ];
                }
            }
            if (isset($productosFinal[$alcance->pro_codigo])) {
                $productosFinal[$alcance->pro_codigo]['total_valor'] += $alcance->total_valor;
                $productosFinal[$alcance->pro_codigo]['pedidos_alcance'] = array_merge(
                    $productosFinal[$alcance->pro_codigo]['pedidos_alcance'],
                    $pedidosAlcanceArray
                );
            } else {
                $productosFinal[$alcance->pro_codigo] = [
                    'pro_codigo' => $alcance->pro_codigo,
                    'total_valor' => $alcance->total_valor,
                    'pedidos' => [],
                    'pedidos_alcance' => $pedidosAlcanceArray,
                    'nombre_serie' => $alcance->nombre_serie,
                    'nombrearea' => $alcance->nombrearea,
                    'nombreasignatura' => $alcance->nombreasignatura,
                ];
            }
        }
        return collect($productosFinal);
    }
    private function obtenerProductosPedidosNormalesNuevo($pedidos)
    {
        return collect(DB::select("SELECT pro.pro_codigo,
                SUM(pvn.pvn_cantidad) AS total_valor,
                GROUP_CONCAT(DISTINCT pvn.id_pedido) AS pedidos,
                MAX(se.nombre_serie) AS nombre_serie,
                MAX(a.nombrearea) AS nombrearea,
                MAX(asi.nombreasignatura) AS nombreasignatura
            FROM pedidos_val_area_new pvn
            LEFT JOIN libros_series ls ON pvn.idlibro = ls.idLibro
            LEFT JOIN series se ON ls.id_serie = se.id_serie
            LEFT JOIN libro li ON ls.idLibro = li.idlibro
            LEFT JOIN asignatura asi ON li.asignatura_idasignatura = asi.idasignatura
            LEFT JOIN area a ON asi.area_idarea = a.idarea
            LEFT JOIN 1_4_cal_producto pro ON ls.codigo_liquidacion = pro.pro_codigo
            WHERE
                pvn.id_pedido IN (" . $pedidos->implode(',') . ")
                AND pvn.pvn_tipo = 0
                AND ls.id_serie = 19
                AND pro.pro_codigo IS NOT NULL
            GROUP BY pro.pro_codigo, ls.codigo_liquidacion
        "))
        ->map(function ($item) {
            $item->total_valor = (int) $item->total_valor;
            return $item;
        });
    }
    private function obtenerProductosPedidosAlcanceNuevo($idPeriodo)
    {
        return collect(DB::select("SELECT pro.pro_codigo,
                SUM(pvn.pvn_cantidad) AS total_valor,
                GROUP_CONCAT(DISTINCT pa.id) AS pedidos_alcance,
                MAX(se.nombre_serie) AS nombre_serie,
                MAX(a.nombrearea) AS nombrearea,
                MAX(asi.nombreasignatura) AS nombreasignatura
            FROM pedidos_alcance pa
            JOIN pedidos_val_area_new pvn ON pa.id = pvn.pvn_tipo
            LEFT JOIN libros_series ls ON pvn.idlibro = ls.idLibro
            LEFT JOIN series se ON ls.id_serie = se.id_serie
            LEFT JOIN libro li ON ls.idLibro = li.idlibro
            LEFT JOIN asignatura asi ON li.asignatura_idasignatura = asi.idasignatura
            LEFT JOIN area a ON asi.area_idarea = a.idarea
            LEFT JOIN 1_4_cal_producto pro ON ls.codigo_liquidacion = pro.pro_codigo
            WHERE
                pa.estado_alcance = 1
                AND pa.id_periodo = ?
                AND ls.id_serie = 19
                AND pro.pro_codigo IS NOT NULL
            GROUP BY pro.pro_codigo, ls.codigo_liquidacion
        ", [$idPeriodo]))
        ->map(function ($item) {
            $item->total_valor = (int) $item->total_valor;
            return $item;
        });
    }
    public function Recorrer_Listado_Combos_X_Temporada(Request $request)
    {
        $productos = $request->all();
        foreach ($productos as &$producto) {
            $codigo_combo = $producto['pro_codigo'] ?? null;
            $producto['pro_codigos_desglose'] = [];
            if ($codigo_combo) {
                // Consultar el campo codigos_combos desde la tabla 1_4_cal_producto
                $comboData = DB::select("
                    SELECT codigos_combos
                    FROM 1_4_cal_producto
                    WHERE pro_codigo = ?
                    LIMIT 1
                ", [$codigo_combo]);
                if (!empty($comboData) && !empty($comboData[0]->codigos_combos)) {
                    $codigos_combos = $comboData[0]->codigos_combos;
                    $codigos_array = explode(',', $codigos_combos);
                    foreach ($codigos_array as $codigo) {
                        $desglose = DB::select("
                            SELECT ls.codigo_liquidacion, l.nombrelibro
                            FROM libros_series ls
                            LEFT JOIN libro l ON ls.idLibro = l.idlibro
                            WHERE ls.codigo_liquidacion = ?
                        ", [$codigo]);
                        if (empty($desglose)) {
                            // ⚠️ Si no se encuentra el código, lanzar error
                            throw new \Exception("⚠️ Código '$codigo' asociado al combo '$codigo_combo' no fue encontrado. Verifica los códigos del combo.");
                        }
                        $producto['pro_codigos_desglose'][] = [
                            "codigo_liquidacion" => $desglose[0]->codigo_liquidacion,
                            "nombrelibro" => $desglose[0]->nombrelibro,
                        ];
                    }
                }
            }
        }
        return response()->json([
            'productos_agrupados_mas_cod_desglose' => $productos,
            'status' => 1,
        ]);
    }
    //FIN SECCION OBETENER LISTADO COMBOS X TEMPORADA
    public function Mover_Stock_SoloTxt_Todo_A_DepositoCALMED() {
        // 1. Obtener productos antes del cambio
        $productosAntes = DB::table('1_4_cal_producto')
            ->where('gru_pro_codigo', 1)
            ->get();
        // 2. Hacer el UPDATE masivo
        $actualizados = DB::update("UPDATE `1_4_cal_producto` SET
            pro_depositoCalmed = pro_depositoCalmed + pro_stock + pro_stockCalmed + pro_deposito,
            pro_stock = 0,
            pro_stockCalmed = 0,
            pro_deposito = 0
            WHERE gru_pro_codigo = 1");
        // 3. Obtener productos después del cambio
        $productosDespues = DB::table('1_4_cal_producto')
            ->where('gru_pro_codigo', 1)
            ->get()
            ->keyBy('pro_codigo'); // Para buscar por código luego
        // 4. Comparar y registrar historial solo si hay cambios
        $HistoricoStock = [];
        foreach ($productosAntes as $producto) {
            $codigo = $producto->pro_codigo;
            $old_values = [
                'pro_codigo' => $codigo,
                'pro_reservar' => $producto->pro_reservar,
                'pro_stock' => $producto->pro_stock,
                'pro_stockCalmed' => $producto->pro_stockCalmed,
                'pro_deposito' => $producto->pro_deposito,
                'pro_depositoCalmed' => $producto->pro_depositoCalmed,
            ];
            $nuevo = $productosDespues[$codigo];
            $new_values = [
                'pro_codigo' => $codigo,
                'pro_reservar' => $nuevo->pro_reservar,
                'pro_stock' => $nuevo->pro_stock,
                'pro_stockCalmed' => $nuevo->pro_stockCalmed,
                'pro_deposito' => $nuevo->pro_deposito,
                'pro_depositoCalmed' => $nuevo->pro_depositoCalmed,
            ];
            $cambios = false;
            foreach (['pro_reservar', 'pro_stock', 'pro_stockCalmed', 'pro_deposito', 'pro_depositoCalmed'] as $campo) {
                if ($old_values[$campo] != $new_values[$campo]) {
                    $cambios = true;
                    break;
                }
            }
            if ($cambios) {
                $HistoricoStock[$codigo] = [
                    'pro_codigo' => $codigo,
                    'psh_old_values' => json_encode($old_values),
                    'psh_new_values' => json_encode($new_values),
                ];
            }
        }
        if (!empty($HistoricoStock)) {
            $query = DB::SELECT("SELECT * FROM usuario u
            WHERE u.name_usuario = 'sadmin'
            ");
            $registroHistorial = [
                'psh_old_values' => json_encode(array_column($HistoricoStock, 'psh_old_values', 'pro_codigo')),
                'psh_new_values' => json_encode(array_column($HistoricoStock, 'psh_new_values', 'pro_codigo')),
                'psh_tipo' => 8,
                'user_created' => $query[0]->idusuario,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            _14ProductoStockHistorico::insert($registroHistorial);
        }
        return response()->json([
            'mensaje' => "$actualizados productos actualizados correctamente en Depósito CALMED",
            'cantidad' => $actualizados
        ]);
    }

    public function GetProductoxcodynombre(Request $request) {
        $query = DB:: SELECT("SELECT pro_codigo, pro_nombre,pro_stock,pro_deposito,pro_stockCalmed,pro_depositoCalmed,id_perseo_prolipa,id_perseo_calmed,id_perseo_prolipa_produccion,id_perseo_calmed_produccion,temporal FROM 1_4_cal_producto
        WHERE pro_codigo LIKE '%$request->busquedacodnombre%' || pro_nombre LIKE '%$request->busquedacodnombre%'
        ORDER BY pro_nombre ASC");
        return $query;
    }

    public function GetProducto_Inactivo() {
        $query = DB:: SELECT("SELECT * FROM 1_4_cal_producto p
        INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
        WHERE p.pro_estado = 0
        ORDER BY p.pro_nombre ASC");
        return $query;
    }

    public function GetProducto_ComienzaconG() {
        $query = DB:: SELECT("SELECT * FROM 1_4_cal_producto
        WHERE pro_codigo
        LIKE 'G%'
        ORDER BY pro_codigo ASC");
        return $query;
    }

    public function GetProductoxFiltro(Request $request) {
        if ($request -> busqueda == 'codigopro') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            WHERE pro_codigo
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request -> busqueda == 'undefined') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            WHERE pro_codigo
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
        if ($request -> busqueda == 'nombres') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            WHERE pro_nombre
            LIKE '%$request->razonbusqueda%'
            ");
            return $query;
        }
    }

    public function GetProductoActivosxFiltro(Request $request) {
        if ($request -> busqueda == 'codigopro') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre, ls.iniciales, ls.id_serie, s.nombre_serie, l.nombrelibro, l.idlibro, ls.year
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            LEFT JOIN libros_series ls ON p.pro_codigo = ls.codigo_liquidacion
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN series s ON s.id_serie = ls.id_serie
            WHERE p.pro_codigo LIKE '%$request->razonbusqueda%' AND p.pro_estado = 1");
            foreach($query as $key => $item){
                $ifcombo = $item->ifcombo;
                if($ifcombo == 1){
                    $codigos_combos = explode(",", $item->codigos_combos);
                    $productosCombo = _14Producto::wherein('pro_codigo', $codigos_combos)->select('pro_codigo','pro_nombre')->get();
                    $query[$key]->productosCombo = $productosCombo;
                }else{
                    $query[$key]->productosCombo = [];
                }
            }
            return $query;
        }
        if ($request -> busqueda == 'undefined' || $request -> busqueda == '' || $request -> busqueda == null) {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre, ls.iniciales, ls.id_serie, s.nombre_serie, l.nombrelibro, l.idlibro, ls.year
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            LEFT JOIN libros_series ls ON p.pro_codigo = ls.codigo_liquidacion
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN series s ON s.id_serie = ls.id_serie
            WHERE p.pro_codigo LIKE '%$request->razonbusqueda%' AND p.pro_estado = 1");
            foreach($query as $key => $item){
                $ifcombo = $item->ifcombo;
                if($ifcombo == 1){
                    $codigos_combos = explode(",", $item->codigos_combos);
                    $productosCombo = _14Producto::wherein('pro_codigo', $codigos_combos)->select('pro_codigo','pro_nombre')->get();
                    $query[$key]->productosCombo = $productosCombo;
                }else{
                    $query[$key]->productosCombo = [];
                }
            }
            return $query;
        }
        if ($request -> busqueda == 'nombres') {
            $query = DB:: SELECT("SELECT p.*, g.gru_pro_nombre, ls.iniciales, ls.id_serie, s.nombre_serie, l.nombrelibro, l.idlibro, ls.year
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo
            LEFT JOIN libros_series ls ON p.pro_codigo = ls.iniciales
            LEFT JOIN libro l ON ls.idLibro = l.idlibro
            LEFT JOIN series s ON s.id_serie = ls.id_serie
            WHERE pro_nombre LIKE '%$request->razonbusqueda%' AND pro_estado = 1");
            foreach($query as $key => $item){
                $ifcombo = $item->ifcombo;
                if($ifcombo == 1){
                    $codigos_combos = explode(",", $item->codigos_combos);
                    $productosCombo = _14Producto::wherein('pro_codigo', $codigos_combos)->select('pro_codigo','pro_nombre')->get();
                    $query[$key]->productosCombo = $productosCombo;
                }else{
                    $query[$key]->productosCombo = [];
                }
            }
            return $query;
        }
    }

    public function Registrar_modificar_producto_libro(Request $request) {
        DB::beginTransaction();

        try {
            // Crear o actualizar el producto
            $producto = _14Producto::updateOrCreate(
                ['pro_codigo' => $request->pro_codigo],
                [
                    'gru_pro_codigo' => $request->gru_pro_codigo,
                    'pro_nombre' => $request->pro_nombre,
                    'pro_descripcion' => $request->pro_descripcion,
                    'pro_iva' => $request->pro_iva ? 1 : 0, // Convertir el valor booleano a 1 o 0
                    'pro_valor' => $request->pro_valor,
                    'pro_descuento' => $request->pro_descuento,
                    'pro_deposito' => $request->pro_deposito,
                    'pro_reservar' => $request->pro_reservar,
                    'pro_stock' => $request->pro_stock,
                    'pro_costo' => $request->pro_costo,
                    'pro_peso' => $request->pro_peso,
                    'pro_depositoCalmed' => $request->pro_depositoCalmed,
                    'pro_stockCalmed' => $request->pro_stockCalmed,
                    'user_created' => $request->user_created,
                    'updated_at' => now()
                ]
            );

            if (!$producto) {
                throw new \Exception('Error al crear o actualizar el producto');
            }

            // Crear o actualizar el libro
            $libro = Libro::updateOrCreate(
                ['nombrelibro' => $request->nombrelibro],
                [
                    'nombre_imprimir' => $request->nombrelibro,
                    'descripcionlibro' => $request->descripcionlibro,
                    'serie' => $request->serie,
                    'titulo' => $request->titulo,
                    'portada' => $request->portada,
                    'weblibro' => $request->weblibro,
                    'exelibro' => $request->exelibro,
                    'pdfsinguia' => $request->pdfsinguia,
                    'pdfconguia' => $request->pdfconguia,
                    'guiadidactica' => $request->guiadidactica,
                    'Estado_idEstado' => $request->Estado_idEstado ?? 1,
                    'asignatura_idasignatura' => $request->asignatura_idasignatura,
                    'ziplibro' => $request->ziplibro,
                    'libroFechaModificacion' => now(),
                    'grupo' => $request->grupo ?? '0',
                    'puerto' => $request->puerto ?? 0,
                    's_weblibro' => $request->s_weblibro,
                    's_pdfsinguia' => $request->s_pdfsinguia,
                    's_pdfconguia' => $request->s_pdfconguia,
                    's_guiadidactica' => $request->s_guiadidactica,
                    's_portada' => $request->s_portada ?? 'portada.png',
                    'c_weblibro' => $request->c_weblibro,
                    'c_pdfsinguia' => $request->c_pdfsinguia,
                    'c_pdfconguia' => $request->c_pdfconguia,
                    'c_guiadidactica' => $request->c_guiadidactica,
                    'c_portada' => $request->c_portada ?? 'portada.png',
                    'demo' => $request->demo
                ]
            );

            if (!$libro) {
                throw new \Exception('Error al crear o actualizar el libro');
            }

            // Crear o actualizar la serie del libro
            $libroSerie = LibroSerie::updateOrCreate(
                [
                    'codigo_liquidacion' => $request->codigo_liquidacion,
                    'idLibro' => $libro->idlibro
                ],
                [
                    'id_serie' => $request->id_serie,
                    'nombre' => $request->nombrelibro,
                    'year' => $request->year,
                    'version' => $request->version2,
                    'boton' => $request->boton ?? 'success',
                    'estado' => $request->estado ?? 1,
                    'cantidad' => $request->cantidad ?? 0,
                    'iniciales' => $request->codigo_liquidacion,
                ]
            );

            if (!$libroSerie) {
                throw new \Exception('Error al crear o actualizar la serie del libro');
            }

            // Confirmar la transacción
            DB::commit();
            return response()->json(['message' => 'Se guardó correctamente']);
        } catch (\Exception $e) {
            // Deshacer la transacción en caso de error
            DB::rollBack();
            // Manejar el error
            return response()->json([
                'error' => 'No se pudo actualizar/guardar',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function Registrar_modificar_producto(Request $request) {
        try {
            DB::beginTransaction();

            // Buscar el producto por su código antiguo
            $producto = _14Producto::where('pro_codigo', $request->pro_codigo_antiguo)->first();

            if (!$producto) {
                // Crear un nuevo producto si no existe
                $producto = _14Producto::create([
                    'pro_codigo' => $request->pro_codigo,
                    'gru_pro_codigo' => $request->gru_pro_codigo,
                    'pro_nombre' => $request->pro_nombre,
                    'pro_descripcion' => $request->pro_descripcion,
                    'pro_iva' => $request->pro_iva,
                    'pro_valor' => $request->pro_valor,
                    'pro_descuento' => $request->pro_descuento,
                    'pro_deposito' => $request->pro_deposito,
                    'pro_reservar' => $request->pro_reservar,
                    'pro_stock' => $request->pro_stock,
                    'pro_costo' => $request->pro_costo,
                    'pro_peso' => $request->pro_peso,
                    'user_created' => $request->user_created,
                    'pro_depositoCalmed' => $request->pro_depositoCalmed,
                    'pro_stockCalmed' => $request->pro_stockCalmed,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                // Actualizar el producto existente
                $producto->update([
                    'pro_codigo' => $request->pro_codigo,
                    'gru_pro_codigo' => $request->gru_pro_codigo,
                    'pro_nombre' => $request->pro_nombre,
                    'pro_descripcion' => $request->pro_descripcion,
                    'pro_iva' => $request->pro_iva,
                    'pro_valor' => $request->pro_valor,
                    'pro_descuento' => $request->pro_descuento,
                    'pro_deposito' => $request->pro_deposito,
                    'pro_reservar' => $request->pro_reservar,
                    'pro_stock' => $request->pro_stock,
                    'pro_costo' => $request->pro_costo,
                    'pro_peso' => $request->pro_peso,
                    'pro_depositoCalmed' => $request->pro_depositoCalmed,
                    'pro_stockCalmed' => $request->pro_stockCalmed,
                    'updated_at' => now()
                ]);
            }

            if (in_array($request->gru_pro_codigo, [1, 3, 6])) {
                $librosSerie = LibroSerie::where('codigo_liquidacion', $producto->pro_codigo)->first();

                if ($librosSerie) {
                    // Libro y serie del libro ya existen
                    $libro = Libro::find($librosSerie->idLibro);

                    if ($libro) {
                        // Actualizar el libro existente
                        DB::table('libro')->where('idLibro', $libro->idLibro)->update([
                            'nombrelibro' => $request->pro_nombre,
                            'nombre_imprimir' => $request->pro_nombre,
                            'descripcionlibro' => $request->pro_nombre,
                        ]);

                        // Actualizar la serie del libro existente
                        DB::table('libros_series')->where('idLibro', $librosSerie->idLibro)->update([
                            'id_serie' => $request->id_serie,
                            'year' => $request->year,
                            'version' => $request->version2,
                            'nombre' => $request->pro_nombre,
                            'iniciales' => $request->codigo_liquidacion,
                        ]);
                    } else {
                        // El libro asociado a la serie no existe, manejar el error
                        throw new \Exception('Libro asociado a la serie no encontrado');
                    }
                } else {
                    // Crear un nuevo libro
                    $libroId = DB::table('libro')->insertGetId([
                        'nombrelibro' => $request->pro_nombre,
                        'nombre_imprimir' => $request->pro_nombre,
                        'descripcionlibro' => $request->pro_nombre,
                    ]);

                    if (!$libroId) {
                        throw new \Exception('No se pudo crear el libro');
                    }

                    // Crear la serie del libro
                    $serieCreated = DB::table('libros_series')->insert([
                        'idLibro' => $libroId,  // Usa el ID del nuevo libro
                        'codigo_liquidacion' => $request->pro_codigo,
                        'id_serie' => $request->id_serie,
                        'year' => $request->year,
                        'version' => $request->version2,
                        'nombre' => $request->pro_nombre,
                        'iniciales' => $request->codigo_liquidacion,
                        'boton' => 'success',
                    ]);

                    if (!$serieCreated) {
                        throw new \Exception('No se pudo crear la serie del libro');
                    }
                }
            }

            // Confirmar la transacción
            DB::commit();
            return response()->json(['message' => 'Se guardó correctamente']);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollback();
            return response()->json([
                'error' => 'No se pudo actualizar/guardar',
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }








    // public function Registrar_modificar_producto(Request $request) {
    //     try {
    //         DB::beginTransaction();

    //         // Buscar el producto por su código antiguo
    //         $producto = _14Producto::where('pro_codigo', $request->pro_codigo_antiguo)->first();

    //         if ($producto) {
    //             // Actualizar el producto existente
    //             $producto->pro_codigo = $request->pro_codigo; // Asegúrate de que pro_codigo no sea nulo
    //             $producto->gru_pro_codigo = $request->gru_pro_codigo;
    //             $producto->pro_nombre = $request->pro_nombre;
    //             $producto->pro_descripcion = $request->pro_descripcion;
    //             $producto->pro_iva = $request->pro_iva;
    //             $producto->pro_valor = $request->pro_valor;
    //             $producto->pro_descuento = $request->pro_descuento;
    //             $producto->pro_deposito = $request->pro_deposito;
    //             $producto->pro_reservar = $request->pro_reservar;
    //             $producto->pro_stock = $request->pro_stock;
    //             $producto->pro_costo = $request->pro_costo;
    //             $producto->pro_peso = $request->pro_peso;
    //             $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
    //             $producto->pro_stockCalmed = $request->pro_stockCalmed;
    //             $producto->updated_at = now();
    //             $producto->save();

    //             if ($request->gru_pro_codigo == 1 || $request->gru_pro_codigo == 3 || $request->gru_pro_codigo == 6) {
    //                 $librosSerie = LibroSerie::where('codigo_liquidacion', $producto->pro_codigo)
    //                 ->first();

    //                 $libro = $librosSerie ? Libro::find($librosSerie->idlibro) : null;

    //                 if ($libro) {
    //                     // Actualizar el libro existente
    //                     $libro->update([
    //                         'nombrelibro' => $request->pro_nombre,
    //                         'nombre_imprimir' => $request->pro_nombre,
    //                         'descripcionlibro' => $request->pro_nombre,
    //                         // Puedes agregar más campos si es necesario
    //                     ]);
    //                     $librosSerie = LibroSerie::where('idLibro', $libro->idLibro)
    //                     ->first();
    //                     if ($librosSerie) {
    //                         // Actualizar la serie del libro existente
    //                         $librosSerie->update([
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     } else {
    //                         return $libro + 'con libro existente';
    //                     // Crear una nueva serie del libro si no existe
    //                         LibroSerie::create([
    //                             'idLibro' => $libro->idLibro,
    //                             'codigo_liquidacion' => $request->pro_codigo_antiguo,
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     }
    //                 } else {
    //                     // Crear un nuevo libro si no existe
    //                     $libro = Libro::create([
    //                         'idLibro' => $request->idlibro,
    //                         'nombrelibro' => $request->pro_nombre,
    //                         'nombre_imprimir' => $request->pro_nombre,
    //                         'descripcionlibro' => $request->pro_nombre,
    //                         // Puedes agregar más campos si es necesario
    //                     ]);
    //                     return $libro;
    //                     $librosSerie = LibroSerie::where('idLibro', $libro->idLibro)
    //                     ->first();
    //                     if ($librosSerie) {
    //                         $librosSerie->update([
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     } else {
    //                         LibroSerie::create([
    //                             'idLibro' => $libro->idLibro,
    //                             'codigo_liquidacion' => $request->pro_codigo_antiguo,
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     }
    //                 }
    //             }


    //         } else {
    //             // Crear un nuevo producto si no existe
    //             $producto = _14Producto::create([
    //                 'pro_codigo' => $request->pro_codigo,
    //                 'gru_pro_codigo' => $request->gru_pro_codigo,
    //                 'pro_nombre' => $request->pro_nombre,
    //                 'pro_descripcion' => $request->pro_descripcion,
    //                 'pro_iva' => $request->pro_iva,
    //                 'pro_valor' => $request->pro_valor,
    //                 'pro_descuento' => $request->pro_descuento,
    //                 'pro_deposito' => $request->pro_deposito,
    //                 'pro_reservar' => $request->pro_reservar,
    //                 'pro_stock' => $request->pro_stock,
    //                 'pro_costo' => $request->pro_costo,
    //                 'pro_peso' => $request->pro_peso,
    //                 'user_created' => $request->user_created,
    //                 'pro_depositoCalmed' => $request->pro_depositoCalmed,
    //                 'pro_stockCalmed' => $request->pro_stockCalmed,
    //                 'created_at' => now(),
    //                 'updated_at' => now()
    //             ]);
    //             if ($request->gru_pro_codigo == 1 || $request->gru_pro_codigo == 3 || $request->gru_pro_codigo == 6) {
    //                 $librosSerie = LibroSerie::where('codigo_liquidacion', $producto->pro_codigo)
    //                 ->first();

    //                 $libro = $librosSerie ? Libro::find($librosSerie->idlibro) : null;

    //                 if ($libro) {
    //                     // Actualizar el libro existente
    //                     $libro->update([
    //                         'nombrelibro' => $request->pro_nombre,
    //                         'nombre_imprimir' => $request->pro_nombre,
    //                         'descripcionlibro' => $request->pro_nombre,
    //                     ]);

    //                     $librosSerie = LibroSerie::where('idLibro', $libro->idLibro)
    //                     ->first();

    //                     if ($librosSerie) {
    //                         // Actualizar la serie del libro existente
    //                         $librosSerie->update([
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     } else {
    //                         // Crear una nueva serie del libro si no existe
    //                         LibroSerie::create([
    //                             'idLibro' => $libro->idLibro,
    //                             'codigo_liquidacion' => $request->pro_codigo_antiguo,
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     }
    //                 } else {
    //                     // Crear un nuevo libro si no existe
    //                     $libro = Libro::create([
    //                         'idLibro' => $request->idlibro,
    //                         'nombrelibro' => $request->pro_nombre,
    //                         'nombre_imprimir' => $request->pro_nombre,
    //                         'descripcionlibro' => $request->pro_nombre,
    //                         // Puedes agregar más campos si es necesario
    //                     ]);

    //                     $librosSerie = LibroSerie::where('idLibro', $libro->idLibro)
    //                     ->first();
    //                     if ($librosSerie) {
    //                         // Actualizar la serie del libro existente
    //                         $librosSerie->update([
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     } else {
    //                         // Crear una nueva serie del libro si no existe
    //                         LibroSerie::create([
    //                             'idLibro' => $libro->idLibro,
    //                             'codigo_liquidacion' => $request->pro_codigo_antiguo,
    //                             'id_serie' => $request->id_serie,
    //                             'year' => $request->year,
    //                             'version' => $request->version2,
    //                             'nombre' => $libro->nombrelibro,
    //                             'iniciales' => $request->codigo_liquidacion,
    //                         ]);
    //                     }
    //                 }
    //             }
    //         }

    //         // Confirmar la transacción
    //         DB::commit();
    //         return response()->json(['message' => 'Se guardó correctamente']);
    //     } catch (\Exception $e) {
    //         // Revertir la transacción en caso de error
    //         DB::rollback();
    //         return response()->json([
    //             'error' => 'No se pudo actualizar/guardar',
    //             'message' => $e->getMessage(),
    //             'line' => $e->getLine()
    //         ]);
    //     }
    // }

    public function Registrar_modificar_producto_backup(Request $request) {
        try {
            DB:: beginTransaction();
            $producto = _14Producto:: firstOrNew(['pro_codigo' => $request -> pro_codigo_antiguo]);
            $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
            $producto -> pro_nombre = $request -> pro_nombre;
            $producto -> pro_descripcion = $request -> pro_descripcion;
            $producto -> pro_iva = $request -> pro_iva;
            $producto -> pro_valor = $request -> pro_valor;
            $producto -> pro_descuento = $request -> pro_descuento;
            $producto -> pro_deposito = $request -> pro_deposito;
            $producto -> pro_reservar = $request -> pro_reservar;
            $producto -> pro_stock = $request -> pro_stock;
            $producto -> pro_costo = $request -> pro_costo;
            $producto -> pro_peso = $request -> pro_peso;
            $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
            $producto->pro_stockCalmed = $request->pro_stockCalmed;
            // return 'antiguo codigo ' . $request->pro_codigo_antiguo . ' nuevo codigo ' . $request->pro_codigo;
            // Verificar si es un nuevo registro o una actualización ->exists
            if ($producto -> exists) {
                // return 'entro al producto existente' ;
                $librosSerie = LibroSerie:: where('codigo_liquidacion', $request -> pro_codigo_antiguo) -> delete ();
                $producto = _14Producto:: findOrFail($request -> pro_codigo_antiguo);
                $producto -> delete ();
                $producto -> pro_codigo = $request -> pro_codigo;
                $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
                $producto -> pro_nombre = $request -> pro_nombre;
                $producto -> pro_descripcion = $request -> pro_descripcion;
                $producto -> pro_iva = $request -> pro_iva;
                $producto -> pro_valor = $request -> pro_valor;
                $producto -> pro_descuento = $request -> pro_descuento;
                $producto -> pro_deposito = $request -> pro_deposito;
                $producto -> pro_reservar = $request -> pro_reservar;
                $producto -> pro_stock = $request -> pro_stock;
                $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
                $producto->pro_stockCalmed = $request->pro_stockCalmed;
                $producto -> pro_costo = $request -> pro_costo;
                $producto -> pro_peso = $request -> pro_peso;
                $producto -> user_created = $request -> user_created;
                // Si ya existe, omitir el campo user_created para evitar que se establezca en null
                $producto -> updated_at = now();
                // Guardar el producto sin modificar user_created
                $producto -> save();
                if ($request -> idlibro) {
                    $libro = DB:: table('libro')
                        -> where('idLibro', $request -> idlibro)
                        -> update([
                            'nombrelibro' => $request -> pro_nombre,
                            'descripcionlibro' => $request -> pro_descripcion,
                        ]);
                    if ($librosSerie > 0) {
                        $librosSerie = new LibroSerie();
                        $librosSerie -> idLibro = $request -> idlibro;
                        $librosSerie -> iniciales = $request -> codigo_liquidacion;
                        $librosSerie -> id_serie = $request -> id_serie;
                        $librosSerie -> year = $request -> year;
                        $librosSerie -> version = $request -> version2;
                        $librosSerie -> codigo_liquidacion = $request -> codigo_liquidacion;
                        $librosSerie -> nombre = $request->pro_nombre;
                        $librosSerie -> boton = "success";
                        $librosSerie -> save();
                    }else {
                        return 'Error al crear el libro-serie';
                    }
                }else {
                    if ($request -> gru_pro_codigo == 1 || $request -> gru_pro_codigo == 3 || $request -> gru_pro_codigo == 6) {
                        if ($request -> idlibro) {
                            $libro = Libro:: findOrFail($request -> idlibro);
                        }else {
                            $libro = new Libro;
                            $libro -> nombrelibro = $request -> pro_nombre;
                            $libro -> nombre_imprimir = $request -> pro_nombre;
                            $libro -> descripcionlibro = $request -> pro_nombre;
                            // Guardar el libro
                            $libro -> save();
                            if ($request -> idlibro) {
                                $librosSerie = DB:: table('libros_series')
                                    -> where('idLibro', $request -> idlibro)
                                    -> update([
                                        'id_serie' => $request -> id_serie,
                                        'codigo_liquidacion' => $request -> pro_codigo,
                                        'nombre' => $libro -> nombrelibro,
                                    ]);
                            }else {
                                $librosSerie = new LibroSerie();
                                $librosSerie -> idLibro = $libro -> idlibro;
                                $librosSerie -> iniciales = $request -> codigo_liquidacion;
                                $librosSerie -> id_serie = $request -> id_serie;
                                $librosSerie -> year = $request -> year;
                                $librosSerie -> version = $request -> version2;
                                $librosSerie -> codigo_liquidacion = $request -> codigo_liquidacion;
                                $librosSerie -> nombre = $libro -> nombrelibro;
                                $librosSerie -> boton = "success";
                                $librosSerie -> save();
                            }
                        }

                    }else {
                        $producto = _14Producto:: findOrFail($request -> pro_codigo_antiguo);
                        $producto -> delete ();
                        $producto -> pro_codigo = $request -> pro_codigo;
                        $producto -> user_created = $request -> user_created;
                        $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
                        $producto -> pro_nombre = $request -> pro_nombre;
                        $producto -> pro_descripcion = $request -> pro_descripcion;
                        $producto -> pro_iva = $request -> pro_iva;
                        $producto -> pro_valor = $request -> pro_valor;
                        $producto -> pro_descuento = $request -> pro_descuento;
                        $producto -> pro_deposito = $request -> pro_deposito;
                        $producto -> pro_reservar = $request -> pro_reservar;
                        $producto -> pro_stock = $request -> pro_stock;
                        $producto -> pro_costo = $request -> pro_costo;
                        $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
                        $producto->pro_stockCalmed = $request->pro_stockCalmed;
                        $producto -> pro_peso = $request -> pro_peso;
                        $producto -> user_created = $request -> user_created;
                        $producto -> updated_at = now();
                        // Guardar el producto
                        $producto -> save();
                    }
                }
            }else {
                // Si es un nuevo registro, establecer user_created y updated_at
                $producto -> pro_codigo = $request -> pro_codigo;
                $producto -> user_created = $request -> user_created;
                $producto -> gru_pro_codigo = $request -> gru_pro_codigo;
                $producto -> pro_nombre = $request -> pro_nombre;
                $producto -> pro_descripcion = $request -> pro_descripcion;
                $producto -> pro_iva = $request -> pro_iva;
                $producto -> pro_valor = $request -> pro_valor;
                $producto -> pro_descuento = $request -> pro_descuento;
                $producto -> pro_deposito = $request -> pro_deposito;
                $producto -> pro_reservar = $request -> pro_reservar;
                $producto -> pro_stock = $request -> pro_stock;
                $producto -> pro_costo = $request -> pro_costo;
                $producto->pro_depositoCalmed = $request->pro_depositoCalmed;
                $producto->pro_stockCalmed = $request->pro_stockCalmed;
                $producto -> pro_peso = $request -> pro_peso;
                $producto -> updated_at = now();
                // Guardar el producto
                $producto -> save();
                // Verificar si se debe ejecutar el bloque de código para el libro y la serie
                if (($request -> gru_pro_codigo == 1 || $request -> gru_pro_codigo == 3 || $request -> gru_pro_codigo == 6)) {
                    if ($request -> idlibro) {
                        $libro = Libro:: findOrFail($request -> idlibro);
                    }else {
                        $libro = new Libro;
                        $libro -> nombrelibro = $request -> pro_nombre;
                        $libro -> nombre_imprimir = $request -> pro_nombre;
                        $libro -> descripcionlibro = $request -> pro_nombre;
                        // Guardar el libro
                        $libro -> save();
                        if ($request -> idlibro) {
                            $librosSerie = DB:: table('libros_series')
                                -> where('idLibro', $request -> idlibro)
                                -> update([
                                    'id_serie' => $request -> id_serie,
                                    'codigo_liquidacion' => $request -> pro_codigo,
                                    'nombre' => $libro -> nombrelibro,
                                ]);
                        }else {
                            $librosSerie = new LibroSerie();
                            $librosSerie -> idLibro = $libro -> idlibro;
                            $librosSerie -> iniciales = $request -> codigo_liquidacion;
                            $librosSerie -> id_serie = $request -> id_serie;
                            $librosSerie -> codigo_liquidacion = $request -> codigo_liquidacion;
                            $librosSerie -> year = $request -> year;
                            $librosSerie -> version = $request -> version2;
                            $librosSerie -> nombre = $libro -> nombrelibro;
                            $librosSerie -> boton = "success";
                            $librosSerie -> save();
                        }
                    }
                }
            }
            DB:: commit();
            return response()->json(['message' => 'Se guardó correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar/guardar', 'message' => $e->getMessage(),'line' => $e->getLine()]);
            DB:: rollback();
        }
        // Verificar si el producto se guardó correctamente
        if ($producto -> wasRecentlyCreated || $producto -> wasChanged()) {
            return "Se guardó correctamente";
        }else {
            return "No se pudo guardar/actualizar";
        }
    }

    public function GetSeriesL() {
        $query = DB:: SELECT("SELECT * FROM series ORDER BY id_serie ASC");
        return $query;
    }

    public function Desactivar_producto(Request $request) {
        if ($request -> pro_codigo) {
            $producto = _14Producto:: find($request -> pro_codigo);

            if (!$producto) {
                return "El pro_codigo no existe en la base de datos";
            }

            $producto -> pro_estado = $request -> pro_estado;
            $producto -> save();

            return $producto;
        }else {
            return "No está ingresando ningún pro_codigo";
        }
    }

    public function Eliminar_producto(Request $request) {

        LibroSerie:: where('iniciales', $request -> pro_codigo) -> delete ();
        // Buscar el producto por su ID
        $producto = _14Producto:: find($request -> pro_codigo);

        // Verificar si el producto existe
        if (!$producto) {
            // Manejar el caso en el que el producto no existe
            return response() -> json(['message' => 'Producto no encontrado'], 404);
        }

        // Eliminar el producto
        $producto -> delete ();

        // Eliminar registros relacionados (si es necesario)
        Libro:: where('nombrelibro', $request -> pro_nombre) -> delete ();
        // Retornar una respuesta exitosa
        return response() -> json(['message' => 'Producto eliminado correctamente'], 200);
    }
    public function getProductosStockMinimo() {
        $query = DB:: SELECT("SELECT * FROM 1_4_cal_producto p WHERE p.gru_pro_codigo = 1 OR p.gru_pro_codigo = 3 OR p.gru_pro_codigo = 6");
        return $query;
    }
    public function getConfiguracionStock(Request $request) {
        if($request->empresaStock == 'prolipaF'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 2");
        }else if($request->empresaStock == 'prolipaN'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 4");
        }
        if($request->empresaStock == 'calmedF'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 3");
        }else if($request->empresaStock == 'calmedN'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 5");
        }
        if($request->empresaStock == 'general'){
            $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.id = 1");
        }
        return $query;
    }
    public function getProductosStockMinimoNotas() {
        $query = DB:: SELECT("SELECT * FROM configuracion_general c WHERE c.nombre LIKE '%notas%'");
        return $query;
    }
    public function getProductosSuma() {
        $query = DB:: SELECT("SELECT
            SUM(pro_reservar) AS total_reservar,
            SUM(pro_stock) AS total_stock,
            SUM(pro_deposito) AS total_deposito,
            SUM(pro_stockCalmed) AS total_stockCalmed,
            SUM(pro_depositoCalmed) AS total_depositoCalmed
            FROM
            1_4_cal_producto;");
        return $query;
    }

    public function GetProducto_StockMenor() {
        // Obtener los productos
        $productos = DB::select("
            SELECT p.*, g.*
            FROM 1_4_cal_producto p
            INNER JOIN 1_4_grupo_productos g ON p.gru_pro_codigo = g.gru_pro_codigo

        ");

        // Obtener los valores mínimos de configuracion_general
        $configuracion = DB::select("SELECT * FROM configuracion_general");

        // Filtrar los productos que cumplen con la condición de stock menor
        $productosMenores = [];

        foreach ($productos as $producto) {
            // Inicializamos un flag para verificar si el producto cumple alguna condición
            $esMenor = false;

            // Comparar cada campo de la tabla 1_4_cal_producto con los valores mínimos correspondientes de configuracion_general
            foreach ($configuracion as $config) {
                // Verificar si el campo del producto es menor que el valor mínimo correspondiente en configuracion_general
                switch ($config->id) {
                    case 5: // DEPOSITO CALMED
                        if (isset($producto->pro_depositoCalmed) && $producto->pro_depositoCalmed < $config->minimo) {
                            $esMenor = true;
                        }
                        break;
                    case 4: // DEPOSITO PROLIPA
                        if (isset($producto->pro_deposito) && $producto->pro_deposito < $config->minimo) {
                            $esMenor = true;
                        }
                        break;
                    case 3: // STOCK CALMED
                        if (isset($producto->pro_stockCalmed) && $producto->pro_stockCalmed < $config->minimo) {
                            $esMenor = true;
                        }
                        break;
                    case 2: // STOCK PROLIPA
                        if (isset($producto->pro_stock) && $producto->pro_stock < $config->minimo) {
                            $esMenor = true;
                        }
                        break;
                    case 1: // RESERVAR
                        if (isset($producto->pro_reservar) && $producto->pro_reservar < $config->minimo) {
                            $esMenor = true;
                        }
                        break;
                }
            }

            // Si alguna de las condiciones se cumple, agregar el producto al resultado
            if ($esMenor) {
                $productosMenores[] = $producto;
            }
        }

        // Devolver los productos que cumplen con la condición
        return $productosMenores;
    }

    public function GetProductosSoloStocks() {
        // Consulta original
        $productos = DB::select("
            SELECT pro.pro_codigo, pro.pro_nombre, pro.pro_reservar, pro.pro_stock,
                   pro.pro_stockCalmed, pro.pro_deposito, pro.pro_depositoCalmed, pro.gru_pro_codigo
            FROM libros_series ls
            INNER JOIN 1_4_cal_producto pro ON ls.codigo_liquidacion = pro.pro_codigo
            WHERE pro.ifcombo != 1
            AND (pro.gru_pro_codigo = 1)
            ORDER BY pro.pro_codigo ASC;
        ");

        // Crear una nueva lista con los códigos modificados
        $productosConG = [];
        foreach ($productos as $producto) {
            $nuevoCodigo = 'G' . $producto->pro_codigo;

            // Verificar si el producto con el nuevo código existe en la tabla
            $productoConG = DB::selectOne("
                SELECT pro.pro_codigo, pro.pro_nombre, pro.pro_reservar, pro.pro_stock,
                       pro.pro_stockCalmed, pro.pro_deposito, pro.pro_depositoCalmed, pro.gru_pro_codigo
                FROM 1_4_cal_producto pro
                WHERE pro.pro_codigo = ?
            ", [$nuevoCodigo]);

            if ($productoConG) {
                $productosConG[] = $productoConG;
            }
        }

        // Combinar los productos originales con los productos encontrados con 'G'
        $resultadoFinal = array_merge($productos, $productosConG);

        return response()->json($resultadoFinal);
    }

    public function GuardarDatosEdicionStockMasiva(Request $request) {
        // Verifica que el array `DatosAcumuladosStockMasivo` esté presente en el request
        DB::beginTransaction();
        try {
            if ($request->has('DatosAcumuladosStockMasivo') && is_array($request->DatosAcumuladosStockMasivo)) {
                $cambios = []; // Array para almacenar los productos con cambios detectados
                // Recorre cada elemento en `DatosAcumuladosStockMasivo`
                foreach ($request->DatosAcumuladosStockMasivo as $item) {
                    // Busca el producto por `pro_codigo`
                    $producto = _14Producto::find($item['pro_codigo']);
                    // Verifica si el producto existe
                    if ($producto) {
                        // Captura los valores originales antes de la actualización
                        $old_values = [
                            'pro_codigo' => $producto->pro_codigo,
                            'pro_reservar' => $producto->pro_reservar,
                            'pro_stock' => $producto->pro_stock,
                            'pro_stockCalmed' => $producto->pro_stockCalmed,
                            'pro_deposito' => $producto->pro_deposito,
                            'pro_depositoCalmed' => $producto->pro_depositoCalmed
                        ];
                        // Compara los valores actuales con los nuevos
                        $hayCambio = (
                            $item['pro_reservar'] != $item['pro_reservar_anterior'] ||
                            $item['pro_stock'] != $item['pro_stock_anterior'] ||
                            $item['pro_stockCalmed'] != $item['pro_stockCalmed_anterior'] ||
                            $item['pro_deposito'] != $item['pro_deposito_anterior'] ||
                            $item['pro_depositoCalmed'] != $item['pro_depositoCalmed_anterior']
                        );
                        // Si hay cambios, prepara los datos para el historial
                        if ($hayCambio) {
                            // Calcula la diferencia para cada campo
                            $diferencia_reservar = $item['pro_reservar'] - $item['pro_reservar_anterior'];
                            $diferencia_stock = $item['pro_stock'] - $item['pro_stock_anterior'];
                            $diferencia_stockCalmed = $item['pro_stockCalmed'] - $item['pro_stockCalmed_anterior'];
                            $diferencia_deposito = $item['pro_deposito'] - $item['pro_deposito_anterior'];
                            $diferencia_depositoCalmed = $item['pro_depositoCalmed'] - $item['pro_depositoCalmed_anterior'];

                            // Actualiza los valores del producto con la diferencia calculada
                            $producto->pro_reservar += $diferencia_reservar;
                            $producto->pro_stock += $diferencia_stock;
                            $producto->pro_stockCalmed += $diferencia_stockCalmed;
                            $producto->pro_deposito += $diferencia_deposito;
                            $producto->pro_depositoCalmed += $diferencia_depositoCalmed;
                            //Almacena los cambios para historico
                            $cambios[] = [
                                'psh_old_values' => json_encode($old_values),
                                'psh_new_values' => json_encode([
                                    'pro_codigo' => $item['pro_codigo'],
                                    'pro_reservar' => $producto->pro_reservar,
                                    'pro_stock' => $producto->pro_stock,
                                    'pro_stockCalmed' => $producto->pro_stockCalmed,
                                    'pro_deposito' => $producto->pro_deposito,
                                    'pro_depositoCalmed' => $producto->pro_depositoCalmed
                                ]),
                            ];
                            // Almacena el código del producto con cambios
                            $codigosConCambios[] = $item['pro_codigo'];
                            // Guarda los cambios en la base de datos
                            $producto->save();
                        }
                    } else {
                        // Si algún `pro_codigo` no existe, se retorna un mensaje de error
                        return response()->json([
                            'status' => 0,
                            'message' => "El producto con código {$item['pro_codigo']} no existe en la base de datos."
                        ], 404);
                    }
                }
                // Si no hubo cambios, retorna un mensaje
                if (empty($cambios) && empty($codigosConCambios)) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 0,
                        'message' => 'No se realizó ninguna actualización porque no hubo cambios.'
                    ], 200);
                }
                // Verifica si la variable 'EdicionCombos' está presente en el request y es igual a 'yes'
                if ($request->has('EdicionCombos') && $request->EdicionCombos == 'yes') {
                    try {
                        // DB::rollBack();
                        // Llamar al submétodo y manejar su respuesta
                        $submetodoResultados = $this->GuardarDatosEdicionStockMasiva_Combos($request, $codigosConCambios);
                        // Agregar los cambios del submétodo
                        $cambios = array_merge($cambios, $submetodoResultados['cambios']);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 3,
                            'message' => 'Error al editar combos',
                            'errores' => json_decode($e->getMessage(), true) // Convertir el JSON de la excepción en un array PHP
                        ], 200);
                    }
                }
                // Guarda historico y retorna una respuesta de éxito
                if ($request->has('EdicionCombos') && $request->EdicionCombos == 'yes') {
                    // Si hay cambios, consolida todo en un único registro para el historial
                    if (!empty($cambios)) {
                        $registroHistorial = [
                            'psh_old_values' => json_encode(array_column($cambios, 'psh_old_values', 'pro_codigo')),
                            'psh_new_values' => json_encode(array_column($cambios, 'psh_new_values', 'pro_codigo')),
                            'psh_tipo' => 1,
                            'user_created' => $request->user_created,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        _14ProductoStockHistorico::insert($registroHistorial);
                    }
                    DB::commit();
                    return response()->json([
                        'Diferencias_Entre_Stocks' => $submetodoResultados['Diferencias_Entre_Stocks'],
                        'status' => 1,
                        'message' => 'Stock de productos actualizados exitosamente.'
                    ], 200);
                }else{
                    // Si hay cambios, consolida todo en un único registro para el historial
                    if (!empty($cambios)) {
                        $registroHistorial = [
                            'psh_old_values' => json_encode(array_column($cambios, 'psh_old_values', 'pro_codigo')),
                            'psh_new_values' => json_encode(array_column($cambios, 'psh_new_values', 'pro_codigo')),
                            'psh_tipo' => 0,
                            'user_created' => $request->user_created,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        _14ProductoStockHistorico::insert($registroHistorial);
                    }
                    DB::commit();
                    return response()->json([
                        'status' => 1,
                        'message' => 'Stock de productos actualizados exitosamente.'
                    ], 200);
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => 'No se han enviado datos válidos para actualizar.'
                ], 400);
            }
        } catch (\Exception $e) {
            // Rollback de transacción
            DB::rollBack();
            // Puedes registrar el error aquí
            return response()->json(['status' => '0', 'message' => 'Error al actualizar los registros: ' . $e->getMessage()], 200);
        }
    }
    public function GuardarDatosEdicionStockMasiva_Combos($request, $codigosConCambios) {
        // return $request;
        // Inicia la transacción del submétodo
        DB::beginTransaction();
        try {
            $codigosCombosConCambios = []; //Para guardar los codigos con cambios encontrados
            $cambios = []; // Para guardar histórico
            $Diferencias_Entre_Stocks = []; //Para realizar operaciones de aumentar o disminuir stock y registro en los movimientos de combos no operativos
            //Proceso para crear movimiento combo no operativo
            $nombre_movimiento_combo = '';
            $id_tipo_documento = 18;
            $Tipo_Documento = DB::SELECT("SELECT LPAD(CAST(ftd.tdo_secuencial_calmed + 1 AS UNSIGNED),
            (SELECT MAX(CHAR_LENGTH(tdo_secuencial_calmed)) FROM f_tipo_documento), '0') AS tdo_secuencial_calmed,ftd.tdo_letra
            FROM f_tipo_documento ftd WHERE ftd.tdo_id = $id_tipo_documento");
            if (empty($Tipo_Documento)) {
                throw new \Exception("Tipo de documento no encontrado.");
            }
            $Periodo_Usuario = DB::SELECT("SELECT pe.codigo_contrato FROM periodoescolar pe WHERE pe.idperiodoescolar = $request->periodo_id");
            if (empty($Periodo_Usuario)) {
                throw new \Exception("Contrato no encontrado para el periodo especificado.");
            }
            $nombre_movimiento_combo = $Tipo_Documento[0]->tdo_letra .'-'. $Periodo_Usuario[0]->codigo_contrato .'-'. $request->user_iniciales.'-'.$Tipo_Documento[0]->tdo_secuencial_calmed;
            //Insertar registro en la tabla f_movimientos_producto
            f_movimientos_producto::create([
                'fmp_id'       => $nombre_movimiento_combo,
                'observacion'  => 'Documento generado a partir de la edición de stock masiva de combos.',
                'id_periodo'   => $request->periodo_id,
                'fmp_estado'   => 4,
                'user_created' => $request->user_created,
                'updated_at'   => now()
            ]);
            $tipo_doc = f_tipo_documento::findOrFail($id_tipo_documento);
            $tipo_doc->tdo_secuencial_calmed = $Tipo_Documento[0]->tdo_secuencial_calmed;
            $tipo_doc->save();
            //-----------------Proceso para actualizar los stocks de los codigos_asociados a cada combo.
            // Array para acumular diferencias de stock por cada código asociado
            $stocks_totales_agrupados = [];
            // Primera pasada: Agrupar diferencias por código asociado
            foreach ($request->DatosAcumuladosStockMasivo as $item) {
                if (in_array($item['pro_codigo'], $codigosConCambios)) {
                    // Buscar el producto combo
                    $productoCombo = DB::table('1_4_cal_producto')
                        ->where('pro_codigo', $item['pro_codigo'])
                        ->first();
                    if (!$productoCombo) {
                        throw new \Exception("Producto combo con código {$item['pro_codigo']} no encontrado.");
                    }
                    // Obtener códigos asociados
                    $codigosAsociados = $productoCombo->codigos_combos ? explode(',', $productoCombo->codigos_combos) : [];
                    // Calcular diferencias de stock
                    $diferencias = [
                        'pro_reservar' => $item['pro_reservar'] - $item['pro_reservar_anterior'],
                        'pro_stock' => $item['pro_stock'] - $item['pro_stock_anterior'],
                        'pro_stockCalmed' => $item['pro_stockCalmed'] - $item['pro_stockCalmed_anterior'],
                        'pro_deposito' => $item['pro_deposito'] - $item['pro_deposito_anterior'],
                        'pro_depositoCalmed' => $item['pro_depositoCalmed'] - $item['pro_depositoCalmed_anterior']
                    ];

                    // Validacion para guardaar en stock de combos no operativos
                    // Calcula las diferencias para el combo (usando los campos 'anterior')
                    $pro_reservar_diferencia      = $item['pro_reservar'] - $item['pro_reservar_anterior'];
                    $pro_stock_diferencia         = $item['pro_stock'] - $item['pro_stock_anterior'];
                    $pro_stockCalmed_diferencia   = $item['pro_stockCalmed'] - $item['pro_stockCalmed_anterior'];
                    $pro_deposito_diferencia      = $item['pro_deposito'] - $item['pro_deposito_anterior'];
                    $pro_depositoCalmed_diferencia= $item['pro_depositoCalmed'] - $item['pro_depositoCalmed_anterior'];
                    // Guarda las diferencias en el array Diferencias_Entre_Stocks
                    $Diferencias_Entre_Stocks[] = [
                        'pro_codigo' => $item['pro_codigo'],
                        'codigos_asociados' => $codigosAsociados,
                        'pro_reservar_diferencia' => $pro_reservar_diferencia,
                        'pro_stock_diferencia' => $pro_stock_diferencia,
                        'pro_stockCalmed_diferencia' => $pro_stockCalmed_diferencia,
                        'pro_deposito_diferencia' => $pro_deposito_diferencia,
                        'pro_depositoCalmed_diferencia' => $pro_depositoCalmed_diferencia,
                    ];

                    // Acumular diferencias por código asociado
                    foreach ($codigosAsociados as $codigo) {
                        if (!isset($stocks_totales_agrupados[$codigo])) {
                            $stocks_totales_agrupados[$codigo] = [
                                'pro_reservar' => 0,
                                'pro_stock' => 0,
                                'pro_stockCalmed' => 0,
                                'pro_deposito' => 0,
                                'pro_depositoCalmed' => 0
                            ];
                        }
                        // Sumar diferencias al código asociado
                        foreach ($diferencias as $key => $value) {
                            $stocks_totales_agrupados[$codigo][$key] += $value;
                        }
                        // Almacenar item solo si no hay errores de stock
                        $codigosCombosConCambios[] = $item;
                    }
                }
            }
            // Segunda pasada: Verificar disponibilidad de stock
            $errores_stock = [];
            foreach ($stocks_totales_agrupados as $codigo => $diferencias) {
                $productoAsociado = DB::table('1_4_cal_producto')
                    ->where('pro_codigo', $codigo)
                    ->first();

                if (!$productoAsociado) {
                    throw new \Exception("Producto con código $codigo no encontrado.");
                }
                $errores_item = [];
                // Verificar si hay suficiente stock
                foreach ($diferencias as $key => $diff) {
                    if ($diff > $productoAsociado->$key) {
                        $errores_item[] = "$key insuficiente (Disponible: {$productoAsociado->$key}, Requerido: {$diff})";
                    }
                }
                if (!empty($errores_item)) {
                    $errores_stock[] = [
                        'pro_codigo' => $codigo,
                        'pro_nombre' => $productoAsociado->pro_nombre, // Agregamos el nombre del producto
                        'errores' => $errores_item
                    ];
                }
            }
            // Si hay errores, detener el proceso
            if (!empty($errores_stock)) {
                // Estructura de errores en un array
                $erroresFormatoArray = [];
                foreach ($errores_stock as $error) {
                    $erroresFormatoArray[] = [
                        'pro_codigo' => $error['pro_codigo'],
                        'pro_nombre' => $error['pro_nombre'],
                        'errores' => $error['errores']
                    ];
                }
                // Lanzar la excepción con el array de errores convertido en JSON
                throw new \Exception(json_encode($erroresFormatoArray));
            }
            // Tercera pasada: Actualizar los stocks
            foreach ($stocks_totales_agrupados as $codigo => $diferencias) {
                $productoAsociado = DB::table('1_4_cal_producto')->where('pro_codigo', $codigo)->first();
                $Actualizar_Stocks = [];
                foreach ($diferencias as $key => $value) {
                    if ($value != 0) {
                        $Actualizar_Stocks[$key] = DB::raw("$key - ($value)");
                    }
                }
                // Actualizar solo si hay cambios
                if (!empty($Actualizar_Stocks)) {
                    DB::table('1_4_cal_producto')
                        ->where('pro_codigo', $codigo)
                        ->update($Actualizar_Stocks);
                    // Guardar el histórico después de actualizar el stock
                    $cambios[] = [
                        'psh_old_values' => json_encode([
                            'pro_codigo' => $productoAsociado->pro_codigo,
                            'pro_reservar' => $productoAsociado->pro_reservar,
                            'pro_stock' => $productoAsociado->pro_stock,
                            'pro_stockCalmed' => $productoAsociado->pro_stockCalmed,
                            'pro_deposito' => $productoAsociado->pro_deposito,
                            'pro_depositoCalmed' => $productoAsociado->pro_depositoCalmed
                        ]),
                        'psh_new_values' => json_encode([
                            'pro_codigo' => $codigo,
                            'pro_reservar' => $productoAsociado->pro_reservar - $diferencias['pro_reservar'],
                            'pro_stock' => $productoAsociado->pro_stock - $diferencias['pro_stock'],
                            'pro_stockCalmed' => $productoAsociado->pro_stockCalmed - $diferencias['pro_stockCalmed'],
                            'pro_deposito' => $productoAsociado->pro_deposito - $diferencias['pro_deposito'],
                            'pro_depositoCalmed' => $productoAsociado->pro_depositoCalmed - $diferencias['pro_depositoCalmed']
                        ]),
                    ];
                }
            }
            // return $Diferencias_Entre_Stocks;
            //Proceso para el registro de los detalles de los combos no operativos
            foreach ($Diferencias_Entre_Stocks as $diferencia) {
                $pro_codigo = $diferencia['pro_codigo'];
                $codigos_asociados = $diferencia['codigos_asociados'];
                $diferencias = [
                    'pro_reservar_diferencia' => [null, 3],
                    'pro_stock_diferencia' => [1, 2],
                    'pro_stockCalmed_diferencia' => [3, 2],
                    'pro_deposito_diferencia' => [1, 1],
                    'pro_depositoCalmed_diferencia' => [3, 1],
                ];
                foreach ($diferencias as $campo => [$emp_id, $fmdp_tipo_bodega]) {
                    // Verifica que el campo exista
                    // if (isset($diferencia[$campo])) {
                    // Verifica que el campo exista y si es diferente de cero
                    if (isset($diferencia[$campo]) && $diferencia[$campo] != 0) {
                        // Registrar para pro_codigo
                        f_movimientos_detalle_producto::create([
                            'fmp_id'                 => $nombre_movimiento_combo,
                            'pro_codigo'             => $pro_codigo,
                            'emp_id'                 => $emp_id,
                            'fmdp_tipo_bodega'       => $fmdp_tipo_bodega,
                            'fmdp_cantidad'          => $diferencia[$campo],
                            'fmdp_tipo_codigo_combo' => 'codigo_combo',
                        ]);
                        // Registrar para cada código asociado con cantidad invertida
                        foreach ($codigos_asociados as $codigo_asociado) {
                            f_movimientos_detalle_producto::create([
                                'fmp_id'                 => $nombre_movimiento_combo,
                                'pro_codigo'             => $codigo_asociado,
                                'emp_id'                 => $emp_id,
                                'fmdp_tipo_bodega'       => $fmdp_tipo_bodega,
                                'fmdp_cantidad'          => -$diferencia[$campo],
                                'fmdp_tipo_codigo_combo' => $pro_codigo,
                            ]);
                        }
                    }
                }
            }
            // Contar la cantidad de registros insertados en f_movimientos_detalle_producto con el fmp_id
            $totalItems = f_movimientos_detalle_producto::where('fmp_id', $nombre_movimiento_combo)->count();
            // Actualizar el campo fmp_items en f_movimientos_producto según el fmp_id
            f_movimientos_producto::where('fmp_id', $nombre_movimiento_combo)->update(['fmp_items' => $totalItems]);
            DB::commit(); // Si todo va bien, confirmamos los cambios
            return [
                'Diferencias_Entre_Stocks' => $Diferencias_Entre_Stocks,
                'cambios' => $cambios,
                'fmp_id' => $nombre_movimiento_combo
            ];
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback en caso de error
            // Re-lanzar la excepción para que el controlador principal lo maneje
            throw $e;
        }
    }
    //SEGUNDO A APLICAR
    // public function Getstockproductosrestablecer() {
    //     // Inicializar las variables de respuesta
    //     $query_proforma_y_detalles = [];
    //     $query_sumas = [];
    //     $producto_actual = [];

    //     // Obtener los registros principales
    //     $query = DB::SELECT("SELECT prof.id, prof.prof_id, prof.prof_estado
    //                          FROM f_proforma prof
    //                          WHERE prof.prof_estado = 1 OR prof.prof_estado = 3");

    //     // Recorrer cada registro para agregar los detalles
    //     foreach ($query as &$proforma) {
    //         // Realizar la consulta de detalles basada en el prof_id
    //         $detalles = DB::SELECT("SELECT det.prof_id, det.pro_codigo, det.det_prof_cantidad, det.det_prof_valor_u
    //                                 FROM f_detalle_proforma det
    //                                 WHERE det.prof_id = ?", [$proforma->id]);

    //         // Agregar los detalles al registro principal
    //         $proforma->detalles_proforma = $detalles;

    //         // Acumular cantidades en query_sumas
    //         foreach ($detalles as $detalle) {
    //             if (isset($query_sumas[$detalle->pro_codigo])) {
    //                 $query_sumas[$detalle->pro_codigo] += $detalle->det_prof_cantidad;
    //             } else {
    //                 $query_sumas[$detalle->pro_codigo] = $detalle->det_prof_cantidad;
    //             }
    //         }
    //     }

    //     // Consultar los datos de cada producto en query_sumas
    //     foreach ($query_sumas as $pro_codigo => $cantidad) {
    //         $producto = DB::SELECT("SELECT pro_reservar, pro_stock, pro_stockCalmed, pro_deposito, pro_depositoCalmed
    //                                 FROM 1_4_cal_producto
    //                                 WHERE pro_codigo = ?", [$pro_codigo]);

    //                                 // Calcular la cantidad a restar
    //         $cantidad_a_restar = $query_sumas[$pro_codigo];

    //         if (!empty($producto)) {
    //             $producto = $producto[0]; // Acceder al primer resultado
    //             $suma = $producto->pro_stock + $producto->pro_stockCalmed + $producto->pro_deposito + $producto->pro_depositoCalmed;
    //             $producto_actual[] = [
    //                 'pro_codigo' => $pro_codigo,
    //                 'pro_reservar' => $producto->pro_reservar,
    //                 'pro_stock' => $producto->pro_stock,
    //                 'pro_stockCalmed' => $producto->pro_stockCalmed,
    //                 'pro_deposito' => $producto->pro_deposito,
    //                 'pro_depositoCalmed' => $producto->pro_depositoCalmed,
    //                 'suma' => $suma,
    //                 'cantidad_a_restar' => $cantidad_a_restar,
    //                 'sumamenos_querysumas' => $suma - $cantidad_a_restar,
    //             ];
    //         }
    //     }

    //     // Actualizar los valores en la base de datos
    //     foreach ($producto_actual as $producto) {
    //         DB::UPDATE("UPDATE 1_4_cal_producto
    //                     SET pro_reservar = ?
    //                     WHERE pro_codigo = ?", [
    //             $producto['sumamenos_querysumas'],
    //             $producto['pro_codigo']
    //         ]);
    //     }

    //     // Asignar el resultado al query_proforma_y_detalles
    //     $query_proforma_y_detalles = $query;

    //     // Retornar las dos variables en un arreglo asociativo
    //     return [
    //         'query_proforma_y_detalles' => $query_proforma_y_detalles,
    //         'query_sumas' => $query_sumas,
    //         'producto_actual' => $producto_actual,
    //     ];
    // }
    //SOLO VERIFICACION_STOCK
    public function Getstockproductosrestablecer_SINACTUALIZAR() {
        // Inicializar las variables de respuesta
        $query_proforma_y_detalles = [];
        $query_sumas = [];
        $producto_actual = [];

        // Obtener los registros principales
        $query = DB::SELECT("SELECT prof.id, prof.prof_id, prof.prof_estado
                             FROM f_proforma prof
                             WHERE prof.prof_estado = 1 OR prof.prof_estado = 3");

        // Recorrer cada registro para agregar los detalles
        foreach ($query as &$proforma) {
            // Realizar la consulta de detalles basada en el prof_id
            $detalles = DB::SELECT("SELECT det.prof_id, det.pro_codigo, det.det_prof_cantidad, det.det_prof_valor_u
                                    FROM f_detalle_proforma det
                                    WHERE det.prof_id = ?", [$proforma->id]);

            // Agregar los detalles al registro principal
            $proforma->detalles_proforma = $detalles;

            // Acumular cantidades en query_sumas
            foreach ($detalles as $detalle) {
                if (isset($query_sumas[$detalle->pro_codigo])) {
                    $query_sumas[$detalle->pro_codigo] += $detalle->det_prof_cantidad;
                } else {
                    $query_sumas[$detalle->pro_codigo] = $detalle->det_prof_cantidad;
                }
            }
        }

        // Consultar los datos de cada producto en query_sumas
        foreach ($query_sumas as $pro_codigo => $cantidad) {
            $producto = DB::SELECT("SELECT pro_reservar, pro_stock, pro_stockCalmed, pro_deposito, pro_depositoCalmed
                                    FROM 1_4_cal_producto
                                    WHERE pro_codigo = ?", [$pro_codigo]);

                                    // Calcular la cantidad a restar
            $cantidad_a_restar = $query_sumas[$pro_codigo];

            if (!empty($producto)) {
                $producto = $producto[0]; // Acceder al primer resultado
                $suma = $producto->pro_stock + $producto->pro_stockCalmed + $producto->pro_deposito + $producto->pro_depositoCalmed;
                $producto_actual[] = [
                    'pro_codigo' => $pro_codigo,
                    'pro_reservar' => $producto->pro_reservar,
                    'pro_stock' => $producto->pro_stock,
                    'pro_stockCalmed' => $producto->pro_stockCalmed,
                    'pro_deposito' => $producto->pro_deposito,
                    'pro_depositoCalmed' => $producto->pro_depositoCalmed,
                    'suma' => $suma,
                    'cantidad_a_restar' => $cantidad_a_restar,
                    'sumamenos_querysumas' => $suma - $cantidad_a_restar,
                ];
            }
        }

        // Asignar el resultado al query_proforma_y_detalles
        $query_proforma_y_detalles = $query;

        // Retornar las dos variables en un arreglo asociativo
        return [
            'query_proforma_y_detalles' => $query_proforma_y_detalles,
            'query_sumas' => $query_sumas,
            'producto_actual' => $producto_actual,
        ];
    }
    //VERIFICAR STOCKS
    public function GetSumarTodo_Productos() {
        // Obtener los registros principales con la suma de los campos
        $query = DB::SELECT("SELECT
                                    pro_codigo,
                                    pro_stock,
                                    pro_stockCalmed,
                                    pro_deposito,
                                    pro_depositoCalmed,
                                    (pro_stock + pro_stockCalmed + pro_deposito + pro_depositoCalmed) AS total_stock,
                                    pro_reservar
                              FROM 1_4_cal_producto");

        // Retornar los resultados
        return $query;
    }
    //PRIMERO A APLICAR
    // public function GetSumarTodo_ProductosFinal() {
    //     try {
    //         DB::statement("
    //             UPDATE 1_4_cal_producto
    //             SET pro_reservar = pro_stock + pro_stockCalmed + pro_deposito + pro_depositoCalmed
    //         ");
    //         return response()->json(['message' => 'Las filas fueron actualizadas correctamente.'], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Ocurrió un error al actualizar los productos: ' . $e->getMessage()], 500);
    //     }
    // }
}
