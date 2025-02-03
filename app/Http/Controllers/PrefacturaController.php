<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodigosLibros;
use App\Models\CodigosLibrosDevolucionSon;
use App\Models\DetalleVentas;
use App\Models\f_tipo_documento;
use App\Models\Periodo;
use App\Models\Ventas;
use App\Repositories\Facturacion\ProformaRepository;
use App\Traits\Codigos\TraitCodigosGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class PrefacturaController extends Controller
{
    use TraitCodigosGeneral;
    protected $proformaRepository;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(ProformaRepository $proformaRepository)
    {
        $this->proformaRepository   = $proformaRepository;
    }
    public function index()
    {
        //
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
    //api:post>>/notasMoverToPrefactura
    public function notasMoverToPrefactura(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $validator = Validator::make($request->all(), [
            'ven_codigo'   => 'required|string',
            'id_empresa'   => 'required|integer',
            'id_periodo'   => 'required|integer',
            'iniciales'    => 'required|string',
            'observacion'  => 'required|string|max:500', // Validación para máximo 500 caracteres
            'id_usuario'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => '0', 'message' => $validator->errors()->first()], 200);
        }
        $ven_codigoAnterior = $request->input('ven_codigo');
        $id_empresa         = $request->input('id_empresa');
        $id_periodo         = $request->input('id_periodo');
        $iniciales          = $request->input('iniciales');
        $observacion        = $request->input('observacion');
        $id_usuario         = $request->input('id_usuario');
        $letraDocumento     = "PF";
        $letraEmpresa       = $id_empresa == 1 ? "P" : "C";
        // Obtener el código de contrato del período
        $getPeriodo = Periodo::where('idperiodoescolar', $id_periodo)->first();
        if (!$getPeriodo) {
            return ["status" => "0", "mensaje" => "No existe el código para el período escolar"];
        }
        $codigo_contrato    = $getPeriodo->codigo_contrato;

        // Obtener nuevo número de documento
        $getNumeroDocumento = $this->proformaRepository->getNumeroDocumento($id_empresa);
        $nuevo_ven_codigo   = $letraDocumento . "-" . $letraEmpresa . "-" . $codigo_contrato . "-" . $iniciales . "-" . $getNumeroDocumento;

        try {
            // Iniciar la transacción
            DB::beginTransaction();

            // Buscar la venta existente
            $f_venta = DB::table('f_venta')
                ->where('id_empresa', $id_empresa)
                ->where('ven_codigo', $ven_codigoAnterior)
                ->where('ven_valor','>',0)
                ->first();

            if (!$f_venta) {
                return ["status" => "0", "message" => "El registro de venta no existe."];
            }

            // Convertir el objeto en un array
            $f_ventaArray = (array)$f_venta;

            // Eliminar campos innecesarios
            unset($f_ventaArray['ven_fecha'], $f_ventaArray['updated_at']);
            $f_ventaArray['ven_codigo'] = $nuevo_ven_codigo; // Cambiar al nuevo código
            $f_ventaArray['idtipodoc']  = 1; // Cambiar a pre-factura
            $f_ventaArray['ven_fecha']  = now();

            // Insertar el nuevo registro
            $insertado = DB::table('f_venta')->insert($f_ventaArray);
            if (!$insertado) {
                return ["status" => "0", "message" => "No se pudo insertar el nuevo registro en f_venta."];
            }

            // Procesar los detalles de venta
            $f_detalle_venta = DB::table('f_detalle_venta')
                ->where('ven_codigo', $ven_codigoAnterior)
                ->where('id_empresa', $id_empresa)
                ->get();

            if ($f_detalle_venta->isEmpty()) {
                return ["status" => "0", "message" => "No se encontraron detalles para el ven_codigo: $ven_codigoAnterior."];
            }

            // Transformar y preparar los detalles
            $f_detalle_ventaArray = $f_detalle_venta->map(function ($detalle) use ($nuevo_ven_codigo) {
                $detalleArray = (array)$detalle;
                unset($detalleArray['det_ven_codigo'], $detalleArray['created_at'], $detalleArray['updated_at']);
                $detalleArray['ven_codigo'] = $nuevo_ven_codigo; // Cambiar al nuevo código
                return $detalleArray;
            })->toArray();

            // Insertar los nuevos detalles en la nueva pre factura
            $insertado_f_detalle_venta = DB::table('f_detalle_venta')->insert($f_detalle_ventaArray);
            if (!$insertado_f_detalle_venta) {
                return ["status" => "0", "message" => "No se pudieron insertar los detalles en f_detalle_venta."];
            }

            // Actualizar la nota de venta existente
            $filasActualizadasVenta = DB::table('f_venta')
                ->where('id_empresa', $id_empresa)
                ->where('ven_codigo', $ven_codigoAnterior)
                ->update([
                    'ven_valor'                 => 0,
                    'ven_subtotal'              => 0,
                    'ven_descuento'             => 0,
                    'doc_intercambio'           => $nuevo_ven_codigo,
                    'user_intercambio'          => $id_usuario,
                    'fecha_intercambio'         => now(),
                    'observacion_intercambio'   => $observacion,
                ]);

            if ($filasActualizadasVenta === 0) {
                return ["status" => "0", "message" => "No se actualizó f_venta con ven_codigo: $ven_codigoAnterior"];
            }

            // Actualizar los detalles existentes
            $filasActualizadasDetalle = DB::table('f_detalle_venta')
                ->where('ven_codigo', $ven_codigoAnterior)
                ->where('id_empresa', $id_empresa)
                ->update([
                    'det_ven_cantidad'          => 0,
                    'det_ven_cantidad_despacho' => 0,
                    'det_ven_dev'               => 0,
                ]);

            if ($filasActualizadasDetalle === 0) {
                return ["status" => "0", "message" => "No se actualizaron los detalles en f_detalle_venta con ven_codigo: $ven_codigoAnterior."];
            }
            // Mensaje histórico
            $mensajeHistorico = "Se movió la nota $ven_codigoAnterior a la prefactura $nuevo_ven_codigo";

            // Realizar la consulta en lotes (chunk) para evitar sobrecargar la memoria
            CodigosLibros::where('codigo_proforma', $ven_codigoAnterior)
                ->where('proforma_empresa', $id_empresa)
                ->chunk(1000, function ($registros) use ($id_usuario, $id_periodo, $mensajeHistorico) {
                    foreach ($registros as $itemCodigo) {
                        // Obtener el estado de la venta y la institución correspondiente
                        $venta_estado   = $itemCodigo->venta_estado;
                        $id_institucion = $venta_estado == 2 ? $itemCodigo->venta_lista_institucion : $itemCodigo->bc_institucion;

                        // Guardar en el historial
                        $this->GuardarEnHistorico(0, $id_institucion, $id_periodo, $itemCodigo->codigo, $id_usuario, $mensajeHistorico, null, null, null, null);
                    }
                });

            // Actualizar los códigos en la tabla CodigosLibros
            $existsCodigosLibros = CodigosLibros::where('codigo_proforma', $ven_codigoAnterior)
            ->where('proforma_empresa', $id_empresa)
            ->exists();

            if ($existsCodigosLibros) {
                $codigosLibros = CodigosLibros::where('codigo_proforma', $ven_codigoAnterior)
                    ->where('proforma_empresa', $id_empresa)
                    ->update([
                        'codigo_proforma' => $nuevo_ven_codigo,
                    ]);

                // Verificar si la actualización fue exitosa
                if ($codigosLibros === 0) {
                    return ["status" => "0", "message" => "No se actualizó el código en CodigosLibros con ven_codigo: $ven_codigoAnterior."];
                }
            }

            //Actualizar CodigosLibrosDevolucionSon donde documento este igual a ven_codigoAnterior y id_empresa igual a id_empresa
            $existsCodigosLibrosDevolucionSon = CodigosLibrosDevolucionSon::where('documento', $ven_codigoAnterior)
                ->where('id_empresa', $id_empresa)
                ->exists();

            if ($existsCodigosLibrosDevolucionSon) {
                $codigosLibrosDevolucionSon = CodigosLibrosDevolucionSon::where('documento', $ven_codigoAnterior)
                    ->where('id_empresa', $id_empresa)
                    ->update([
                        'documento' => $nuevo_ven_codigo,
                    ]);

                if ($codigosLibrosDevolucionSon === 0) {
                    return ["status" => "0", "message" => "No se actualizó el código en CodigosLibrosDevolucionSon con ven_codigo: $ven_codigoAnterior."];
                }
            }
            //ACTUALIZAR STOCK
            foreach($f_detalle_venta as $key => $item){
                //GUARDAR EN HISTORICO PARA NOTAS
                $datos = (Object)[
                    "descripcion"       => $item->pro_codigo,
                    "tipo"              => "1",
                    "nueva_prefactura"  => $nuevo_ven_codigo,
                    "cantidad"          => 1,
                    "id_periodo"        => $id_periodo,
                    "id_empresa"        => $id_empresa,
                    "observacion"       => $mensajeHistorico,
                    "user_created"      => $id_usuario
                ];
                $this->proformaRepository->saveHistoricoNotasMove($datos);
                //disminuir stock en las notas
                $datosStockNota = (Object)[
                    "codigo_liquidacion"  => $item->pro_codigo,
                    "cantidad"            => $item->det_ven_cantidad,
                    "proforma_empresa"    => $id_empresa,
                    "documentoPrefactura" => 1
                ];
                //aumentar stock en prefacturas
                $datosStockPrefactura = (Object)[
                    "codigo_liquidacion"  => $item->pro_codigo,
                    "cantidad"            => $item->det_ven_cantidad,
                    "proforma_empresa"    => $id_empresa,
                    "documentoPrefactura" => 0
                ];
                //NOTA EL disminuir NO SE CAMBIA PORQUE SOLO SE INTERCAMBIA LOS VALORES DE NOTAS SE DESCUENTA Y SE MUEVAN A LA PREFACTURA
                //metodo aumentar stock en notas
                $this->proformaRepository->restaStock($datosStockNota,1);
                //metodo aumentar stock en prefacturas
                $this->proformaRepository->sumaStock($datosStockPrefactura,1);
            }
            //SUMAR SECUENCIA
            // ACTUALIZAR SECUENCIAL
            if($id_empresa==1){
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_id=1");
            }else if ($id_empresa==3){
                $query1 = DB::SELECT("SELECT tdo_id as id, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_id=1");
            }
            $id=$query1[0]->id;
            $codi=$query1[0]->cod;
            $co=(int)$codi+1;
            $tipo_doc = f_tipo_documento::findOrFail($id);
            if($id_empresa==1){
                $tipo_doc->tdo_secuencial_Prolipa = $co;
            }else if ($id_empresa==3){
                $tipo_doc->tdo_secuencial_calmed = $co;
            }
            $tipo_doc->save();
            if(!$tipo_doc){
                return ["status" => "0", "message" => "No se pudo actualizar la secuencia de documentos."];
            }
            // Confirmar la transacción
            DB::commit();
        } catch (\Exception $e) {
            // Deshacer la transacción si ocurre un error
            DB::rollBack();
            return response()->json(['status' => '0', 'message' => $e->getMessage()], 200);
        }

        return response()->json(['status' => '1', 'message' => "La nota fue movida y guardada con la nueva Pre factura $nuevo_ven_codigo."]);
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
