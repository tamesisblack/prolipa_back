<?php
namespace App\Repositories\pedidos;

use App\Models\_14Producto;
use App\Models\DetalleVentas;
use App\Models\f_tipo_documento;
use App\Models\PedidoHistoricoActas;
use App\Models\Pedidos;
use App\Models\Pedidos_val_area_new;
use App\Models\PedidoValArea;
use App\Models\Ventas;
use App\Repositories\BaseRepository;
use DB;
class  GuiaRepository extends BaseRepository
{
    public function __construct(Pedidos $pedidoRepository)
    {
        parent::__construct($pedidoRepository);
    }
       /**
     * Obtener la secuencia según la empresa.
     */
    public function obtenerSecuenciaxEmpresa($empresa_id)
    {
        $getSecuencia = f_tipo_documento::obtenerSecuencia("GUIA");
        if (!$getSecuencia) {
            return null;
        }

        if ($empresa_id == 1) {
            return ['secuencia' => $getSecuencia->tdo_secuencial_Prolipa, 'letra' => 'P'];
        } elseif ($empresa_id == 3) {
            return ['secuencia' => $getSecuencia->tdo_secuencial_calmed, 'letra' => 'C'];
        }

        return null;
    }

    /**
     * Generar el código del acta de guías.
     */
    public function generarCodigoActa($request, $secuenciaData)
    {
        $secuencia = $secuenciaData['secuencia'] + 1;
        $letra     = $secuenciaData['letra'];
        $format_id_pedido = f_tipo_documento::formatSecuencia($secuencia);
        return 'A-' . $letra . '-' . $request->codigo_contrato . '-' . $request->codigo_usuario_fact . '-' . $format_id_pedido;
    }

    /**
     * Actualizar los datos del pedido.
     */
    public function actualizarGuia($request, $codigo_ven, $secuenciaData, $tipo)
    {
        try {
            //tipo  0 => padre; 1 => pendientes
            $secuencia      = $secuenciaData['secuencia'] + 1;
            $estado_entrega = $request->ifAprobarSinStock == '1' ? '3' : '1';
            $fechaActual    = now();

            if ($tipo == 0) {
                // Actualizar el pedido
                Pedidos::where('id_pedido', $request->id_pedido)
                    ->update([
                        'ven_codigo'                    => $codigo_ven,
                        'id_usuario_verif'              => $request->usuario_fact,
                        'fecha_aprobado_facturacion'    => $fechaActual,
                        'estado_entrega'                => $estado_entrega,
                        'empresa_id'                    => $request->empresa_id,
                    ]);
            } else {
               $this->actualizarEstadoPendientes($request->id_pedido,$request->ifnuevo,$request->empresa_id);
            }

            // Actualizar la secuencia
            f_tipo_documento::updateSecuencia("GUIA", $request->empresa_id, $secuencia);
        } catch (\Exception $e) {
            // Manejar la excepción
            throw new \Exception("Error al actualizar la guía: " . $e->getMessage());
        }
    }

    public function actualizarEstadoPendientes($id_pedido, $ifnuevo, $empresa_id)
    {
        // Obtener el modelo según ifnuevo
        $model = $ifnuevo == '0'
            ? PedidoValArea::where('id_pedido', $id_pedido)->get()
            : Pedidos_val_area_new::where('id_pedido', $id_pedido)->get();

        // Verificar si hay algún registro con cantidad_pendiente > 0
        $hayPendientes = $model->filter(function ($item) {
            return $item->cantidad_pendiente > 0;
        })->isNotEmpty();

        if ($hayPendientes) {
            // Si hay pendientes, no hacer nada
            return 1;
        }

        // Si no hay pendientes, actualizar el estado a 1 (aprobado)
        Pedidos::where('id_pedido', $id_pedido)
            ->update([
                'estado_entrega' => 1,
                'empresa_id'     => $empresa_id,
            ]);
        return 0;
    }


    /**
     * Actualizar los pendientes en las tablas correspondientes.
     */
    public function actualizarPendientes($detalleGuias, $ifnuevo,$tipo)
    {
        //tipo 0 => padre; 1 => pendientes
        foreach ($detalleGuias as $item) {
            $model = $ifnuevo == '0'
                ? PedidoValArea::findOrFail($item->id)
                : Pedidos_val_area_new::findOrFail($item->id);

            if($tipo == 0){
                $model->cantidad_pendiente += $item->cantidad_pendiente;
                $model->cantidad_pendiente_especifico += $item->cantidad_pendiente;
            }else{
                $model->cantidad_pendiente -= $item->cantidadAutorizar;
            }
            $model->save();

            if (!$model) {
                throw new \Exception("No se pudo actualizar en el modelo correspondiente para {$item->codigoFact}");
            }
        }
    }
    //actualizar stock
    public function actualizarStockFacturacion($arregloCodigos, $codigo_ven, $empresa_id,$tipo,$id_pedido)
    {
        //tipo  0 => padre; 1 => pendientes
        $contador = 0;

        // Validación inicial
        if (empty($arregloCodigos) || !is_array($arregloCodigos)) {
            return ["status" => "0", "message" => "El arreglo de códigos es inválido."];
        }

        try {
            DB::beginTransaction(); // Iniciar transacción

            foreach ($arregloCodigos as $item) {
                // Validación de item
                if (!isset($item->codigo, $item->codigoFact, $item->valorNew, $item->cantidad_pendiente)) {
                    throw new \Exception("Datos incompletos en uno de los elementos del arreglo.");
                }

                $producto = _14Producto::obtenerProducto($item->codigoFact);
                if (!$producto) {
                    throw new \Exception("Producto no encontrado: {$item->codigoFact}");
                }

                $stockAnteriorReserva   = $producto->pro_reservar;
                $stockEmpresa           = $empresa_id == 1 ? $producto->pro_stock : ($empresa_id == 3 ? $producto->pro_stockCalmed : 0);

                //tipo  0 => padre; 1 => pendientes
                if($tipo == 0){
                    $cantidadDescontar      = $item->valorNew - $item->cantidad_pendiente;
                    $nuevoStockReserva      = $stockAnteriorReserva - $cantidadDescontar;
                    $nuevoStockEmpresa      = $stockEmpresa - $cantidadDescontar;
                }else{
                    $cantidadDescontar      = $item->cantidadAutorizar;
                    $nuevoStockReserva      = $stockAnteriorReserva - $cantidadDescontar;
                    $nuevoStockEmpresa      = $stockEmpresa - $cantidadDescontar;
                }

                //si el nuevoStockReserva es menor a 0 no se actualiza el stock mostrar un mensaje de alerta
                if($nuevoStockReserva < 0 || $nuevoStockEmpresa < 0){
                    throw new \Exception("No hay stock suficiente para el producto {$item->codigoFact}");
                }
                // Actualizar stock
                _14Producto::updateStock($item->codigoFact, $empresa_id, $nuevoStockReserva, $nuevoStockEmpresa);

                // Guardar histórico
                PedidoHistoricoActas::create([
                    'cantidad'                  => $cantidadDescontar,
                    'ven_codigo'                => $codigo_ven,
                    'pro_codigo'                => $item->codigo,
                    'stock_anterior'            => $stockAnteriorReserva,
                    'nuevo_stock'               => $nuevoStockReserva,
                    'stock_anterior_empresa'    => $stockEmpresa,
                    'nuevo_stock_empresa'       => $nuevoStockEmpresa,
                    'id_pedido'                 => $id_pedido,
                ]);
                $contador++;
            }

            DB::commit(); // Confirmar transacción

            return ["status" => "1", "message" => "Se guardó correctamente", "procesados" => $contador];
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir transacción en caso de error
            return ["status" => "0", "message" => "Error: " . $e->getMessage()];
        }
    }
    public function crearVenta($codigo_ven, $id_empresa, $id_periodo, $user_created, $idPadre)
    {
        try {
            // Verificar si ya existe un registro con el mismo ven_codigo
            $ventaExistente = Ventas::where('ven_codigo', $codigo_ven)->where('id_empresa', $id_empresa)->first();

            if ($ventaExistente) {
                return ["status" => "0", "message" => "La venta con el código ya existe."];
            }

            // Crear una nueva venta si no existe
            $venta                          = new Ventas();
            $venta->ven_codigo              = $codigo_ven;
            $venta->id_empresa              = $id_empresa;
            $venta->est_ven_codigo          = '1';
            $venta->periodo_id              = $id_periodo;
            $venta->idtipodoc               = 12;
            $venta->tip_ven_codigo          = 1;
            $venta->ven_fecha               = now();
            $venta->user_created            = $user_created;
            $venta->id_pedido               = $idPadre;
            $venta->save();
            if(!$venta){
                throw new \Exception("No se pudo guardar la venta");
            }

            return ["status" => "1", "message" => "Venta creada exitosamente."];
        } catch (\Exception $e) {
            throw new \Exception("Error al crear la venta: " . $e->getMessage());
        }
    }

    public function crearDetalleVenta($guias_send, $codigo_ven, $id_empresa, $id_periodo, $user_created, $idPadre)
    {
        try {
            foreach ($guias_send as $guia) {
                // Verificar si ya existe un registro con el mismo ven_codigo
                $pro_CodigoExists = DetalleVentas::where('ven_codigo', $codigo_ven)->where('pro_codigo', $guia->codigoFact)->where('id_empresa', $id_empresa)->first();
                if (!$pro_CodigoExists) {
                    //NO HAGO NADA
                }
                $detalleVenta                          = new DetalleVentas();
                $detalleVenta->ven_codigo              = $codigo_ven;
                $detalleVenta->id_empresa              = $id_empresa;
                $detalleVenta->pro_codigo              = $guia->codigoFact;
                $detalleVenta->det_ven_cantidad        = $guia->cantidadAutorizar;
                $detalleVenta->save();
                if(!$detalleVenta){
                    throw new \Exception("No se pudo guardar el detalle de venta");
                }
            }
            return ["status" => "1", "message" => "Detalle de venta creada exitosamente."];
        } catch (\Exception $e) {
            throw new \Exception("Error al crear los detalles de venta: " . $e->getMessage());
        }
    }
    public function getPendientes($id_pedido){
        $query = DB::SELECT("
            SELECT
                SUBSTRING(v2.pro_codigo, 2) AS pro_codigo,
                SUM(v2.det_ven_cantidad) AS cantidad
            FROM f_venta v
            LEFT JOIN f_detalle_venta v2
                ON v2.ven_codigo = v.ven_codigo
                AND v2.id_empresa = v.id_empresa
            WHERE v.id_pedido = ?
            GROUP BY SUBSTRING(v2.pro_codigo, 2)
        ", [$id_pedido]);

        return $query;
    }

}
