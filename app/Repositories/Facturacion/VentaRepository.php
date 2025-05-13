<?php
namespace App\Repositories\Facturacion;

use App\Models\Ventas;
use App\Repositories\BaseRepository;
use App\Repositories\pedidos\PedidosRepository;
use DB;
class  VentaRepository extends BaseRepository
{
    protected $proformaRepository;
    protected $pedidosRepository;
    public function __construct(ProformaRepository $proformaRepository, PedidosRepository $pedidosRepository)
    {
        $this->proformaRepository = $proformaRepository;
        $this->pedidosRepository  = $pedidosRepository;
    }

    public function getVentasTipoVenta($periodo, $tipoVenta){
        $query = DB::select("SELECT
                fdv.pro_codigo AS codigo_liquidacion,
                p.pro_nombre AS nombrelibro,
                SUM(fdv.det_ven_cantidad - fdv.det_ven_dev) AS valor
            FROM f_detalle_venta fdv
            INNER JOIN f_venta fv ON fv.ven_codigo = fdv.ven_codigo
                AND fv.id_empresa = fdv.id_empresa
            INNER JOIN 1_4_cal_producto p ON fdv.pro_codigo = p.pro_codigo
            WHERE fv.periodo_id = ?
                AND fv.tip_ven_codigo = ?
                AND fv.idtipodoc IN (1, 3, 4)
                AND fv.est_ven_codigo <> 3
            GROUP BY fdv.pro_codigo, p.pro_nombre, fdv.det_ven_valor_u
        ", [$periodo, $tipoVenta]);
        return $query;
    }
    public function getPedidosTipoVenta($periodo, $tipoVenta){
        $getPedidos = DB::SELECT("SELECT  p.*
        FROM pedidos p
        WHERE p.id_periodo = ?
        AND p.estado = '1'
        AND p.tipo = '0'
        AND p.contrato_generado IS NOT NULL
        AND p.tipo_venta = ?
        ",[$periodo, $tipoVenta]);
        return $getPedidos;
    }
    public function getProductosPerseo($periodo_id, $empresa, $tipoInstitucion)
    {
        $getInstituciones = $this->proformaRepository->listadoInstitucionesXVenta($periodo_id, $empresa, $tipoInstitucion);
        $flatResultadoProductos = [];

        foreach ($getInstituciones as $key => $item) {
            // Obtener los datos de venta AGRUPADOS para la institución actual
            $getDatosVentaAgrupado = $this->proformaRepository->listadoDocumentosAgrupado(
                $periodo_id,
                null,
                $tipoInstitucion,
                $item->institucion_id
            );

            // Asignamos los productos agrupados al objeto de la institución
            $item->resultadoProductos = $getDatosVentaAgrupado;

            // Convertimos a array para poder hacer merge
            if (is_object($getDatosVentaAgrupado)) {
                $getDatosVentaAgrupado = json_decode(json_encode($getDatosVentaAgrupado), true);
            }

            if (is_array($getDatosVentaAgrupado)) {
                $flatResultadoProductos = array_merge($flatResultadoProductos, $getDatosVentaAgrupado);
            }
        }

        // Filtramos solo los campos deseados y renombramos claves
        $flatResultadoProductos = array_map(function ($producto) {
            return [
                'nombrelibro' => $producto['nombrelibro'] ?? null,
                'codigo_liquidacion' => $producto['pro_codigo'] ?? null,
                'valor' => $producto['det_ven_cantidad'] ?? null,
            ];
        }, $flatResultadoProductos);

        // Agrupamos por 'codigo_liquidacion' y sumamos los valores duplicados
        $productosAgrupados = [];

        foreach ($flatResultadoProductos as $producto) {
            $codigo = $producto['codigo_liquidacion'];

            if (!isset($productosAgrupados[$codigo])) {
                $productosAgrupados[$codigo] = $producto;
            } else {
                // Sumamos el valor
                $productosAgrupados[$codigo]['valor'] += $producto['valor'];
            }
        }

        // Reindexamos el array para que sea una lista simple
        $flatResultadoProductos = array_values($productosAgrupados);

        // Retornamos solo el array limpio con los campos requeridos y valores agrupados
        return $flatResultadoProductos;
    }

    public function getProductosPedidos($periodo, $tipoVenta){
        $getPedidos = $this->getPedidosTipoVenta($periodo, $tipoVenta);
        $arrayDetalles = [];

        // 3. Obtener detalles de cada pedido
        foreach($getPedidos as $key => $item10){
            $pedido = $item10->id_pedido;
            $libroSolicitados = $this->pedidosRepository->obtenerLibroxPedidoTodo($pedido);
            $arrayDetalles[$key] = $libroSolicitados;
        }

        // 4. Agrupar los libros pedidos
        $agrupado = [];
        $arrayDetalles = collect($arrayDetalles)->flatten(10);

        foreach ($arrayDetalles as $key => $detalle) {
            $codigo_liquidacion = $detalle->codigo_liquidacion;

            if (isset($agrupado[$codigo_liquidacion])) {
                $agrupado[$codigo_liquidacion]['valor'] += $detalle->valor;
                if (empty($agrupado[$codigo_liquidacion]['nombrelibro'])) {
                    $agrupado[$codigo_liquidacion]['nombrelibro'] = $detalle->nombrelibro;
                }
            } else {
                $agrupado[$codigo_liquidacion] = [
                    'codigo_liquidacion' => $codigo_liquidacion,
                    'valor' => $detalle->valor,
                    'nombrelibro' => $detalle->nombrelibro,
                ];
            }
        }
        return array_values($agrupado);
    }
    public function getProdutosPedidosNuevo($periodo,$tipoVenta,$ifContratos=1){
          $resultado = DB::table('pedidos_val_area_new as pvn')
            ->join('pedidos as p', 'pvn.id_pedido', '=', 'p.id_pedido')
            ->join('libro as l', 'pvn.idlibro', '=', 'l.idlibro')
            ->join('libros_series as ls', 'l.idlibro', '=', 'ls.idLibro') // Nuevo join
            ->select(
                'l.idlibro',
                'ls.nombre as nombrelibro',  // Nombre desde libros_series
                'ls.codigo_liquidacion',      // Agregado código de liquidación
                DB::raw('SUM(pvn.pvn_cantidad) as cantidad'),
                DB::raw('COUNT(DISTINCT p.id_pedido) as pedidos')
            )
            ->where('p.tipo_venta', $tipoVenta)
            ->where('p.id_periodo', $periodo)
            ->where('pvn.pvn_tipo', 0)
            ->where('l.Estado_idEstado', 1)
            ->where('p.tipo', '0')
            ->where('p.estado','1')
            ->where('ls.estado', '1')  // Filtro para libros_series activos
            ->when($ifContratos == 0, function ($query) {
                $query->whereNull('p.contrato_generado'); // Uso correcto para NULL
            })
            ->when($ifContratos == 1, function ($query) {
                $query->whereNotNull('p.contrato_generado'); // Uso correcto para NOT NULL
            })
            ->groupBy('l.idlibro', 'ls.nombre', 'ls.codigo_liquidacion') // Agregado a groupBy
            ->orderBy('ls.nombre')  // Ordenar por nombre de libros_series
            ->get();
        //alcances
        if($ifContratos == 1){
            foreach ($resultado as $key => $value) {
                // Obtener cantidad de alcances con parámetros enlazados
                $query = DB::select("SELECT COALESCE(SUM(pv.pvn_cantidad), 0) AS cantidad
                    FROM pedidos_val_area_new pv
                    LEFT JOIN pedidos p ON pv.id_pedido = p.id_pedido
                    LEFT JOIN pedidos_alcance ac ON ac.id = pv.pvn_tipo
                    WHERE pv.pvn_tipo <> '0'
                    AND p.tipo = '0'
                    AND p.estado = '1'
                    AND p.id_periodo = ?
                    AND ac.estado_alcance = '1'
                    AND pv.idlibro = ?
                ", [$periodo, $value->idlibro]);

                // Asegurar que haya resultados y convertir a float
                $alcances = !empty($query) ? (float) $query[0]->cantidad : 0.0;

                // Asignar valores al resultado
                $resultado[$key]->alcances = $alcances;
                $resultado[$key]->cantidad = (float) $resultado[$key]->cantidad + $alcances;
            }
        }
        foreach ($resultado as $key => $value) {
            // Asignar valores al resultado
            $resultado[$key]->valor = (float) $resultado[$key]->cantidad;
        }
        return $resultado;
    }
    public function getDespachoBodegaDirecta($periodo){
        $query = DB::SELECT("SELECT
                ls.codigo_liquidacion,
                l.nombrelibro,
                COUNT(*) AS valor
            FROM codigoslibros c
            LEFT JOIN libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON l.idlibro = ls.idLibro
            WHERE
                c.prueba_diagnostica = '0'
                AND c.estado_liquidacion IN ('0', '1', '2')
                AND c.bc_periodo = '$periodo'
                AND ( c.venta_estado = '1' OR c.venta_estado = '0')
            GROUP BY ls.codigo_liquidacion, l.nombrelibro
            ORDER BY ls.codigo_liquidacion;

        ");
        return $query;
    }
    public function getDespachoBodegaLista($periodo){
        $query = DB::SELECT("SELECT
                ls.codigo_liquidacion,
                l.nombrelibro,
                COUNT(*) AS valor
            FROM codigoslibros c
            LEFT JOIN libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON l.idlibro = ls.idLibro
            WHERE
                c.prueba_diagnostica = '0'
                AND c.estado_liquidacion IN ('0', '1', '2')
                AND c.bc_periodo = '$periodo'
                AND c.venta_estado = '2'
            GROUP BY ls.codigo_liquidacion, l.nombrelibro
            ORDER BY ls.codigo_liquidacion;

        ");
        return $query;
    }
    public function getDespachoBodegaTodo($periodo){
        $query = DB::SELECT("SELECT
                ls.codigo_liquidacion,
                l.nombrelibro,
                COUNT(*) AS valor
            FROM codigoslibros c
            LEFT JOIN libros_series ls ON ls.idLibro = c.libro_idlibro
            LEFT JOIN libro l ON l.idlibro = ls.idLibro
            WHERE
                c.prueba_diagnostica = '0'
                AND c.estado_liquidacion IN ('0', '1', '2')
                AND c.bc_periodo = '$periodo'
            GROUP BY ls.codigo_liquidacion, l.nombrelibro
            ORDER BY ls.codigo_liquidacion;
        ");
        return $query;
    }
}
?>
