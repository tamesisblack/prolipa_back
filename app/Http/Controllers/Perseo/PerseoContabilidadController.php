<?php

namespace App\Http\Controllers\Perseo;

use App\Http\Controllers\Controller;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;

class PerseoContabilidadController extends Controller
{
    use TraitPedidosGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /*
       Devuelve la información de los asientos contables generados en el sistema PERSEO.
        Se puede filtrar por:
        rango de fechas
        Cuentas contable.
        id del asiento contable.
    */
    //api:post/perseo/contabilidad/asientoscontables_consulta
    /*
        fechadesde y fechahasta: rango de fecha que se desea consultar los asientos contables
        codigocontable: Cuenta contable que se desea consultar sus movimientos o asientos contables
        id: en caso especial, si se conoce el id del asiento contable
    */
    public function asientoscontables_consulta(Request $request)
    {
        try {
            $formData = [
                "fechadesde"     => "",
                "fechahasta"     => "",
                "codigocontable" => "",
                "id"             => "",
            ];
            // $formData = [
            //     "fechadesde"     => $request->fechadesde,
            //     "fechahasta"     => $request->fechahasta,
            //     "codigocontable" => $request->codigocontable,
            //     "id"             => $request->id,
            // ];
            $url        = "centrocosto_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Crea un asiento contable manual, sin afectar otro módulo.
    */
    //api:post/perseo/contabilidad/asientocontable_individual_crear
    public function asientocontable_individual_crear(Request $request){
        try {
            $formData = [
                "fechamovimiento" => "20220204",
                "usuario"         => "PERSEO",
                "registro"        => [
                    [
                        "concepto"    => "descripcion 1 ejemplo 2",
                        "referencia"  => "L000030259",
                        "debe"        => 10,
                        "haber"       => 10,
                        "mayorizado"  => 1,
                        "detalle"     => [
                            [
                                "conceptodetalle" => "descripcion detalle 1",
                                "codigocontable"   => "1.1.01.01.01",
                                "referenciaDetalles" => "Hola mundo",
                                "valor"            => 10,
                                "centros_costosid" => "1"
                            ],
                            [
                                "conceptodetalle" => "descripcion dtalle 2",
                                "codigocontable"   => "1.1.01.02.01",
                                "referenciaDetalles" => "Hola mundo",
                                "valor"            => -10,
                                "centros_costosid" => "1"
                            ]
                        ]
                    ]
                ]
            ];
            // $formData = [
            //     "fechamovimiento" => $request->fechamovimiento,
            //     "usuario"         => $request->usuario,
            //     "registro"        => [
            //         [
            //             "concepto"    => $request->concepto,
            //             "referencia"  => $request->referencia,
            //             "debe"        => $request->debe,
            //             "haber"       => $request->haber,
            //             "mayorizado"  => $request->mayorizado,
            //             "detalle"     => json_decode($request->detalle)
            //         ]
            //     ]
            // ];
            $url        = "asientocontable_individual_crear";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las cuentas contable creadas en el sistema Perseo.
    */
    //api:post/perseo/contabilidad/cuentascontables_consulta
    public function cuentascontables_consulta(Request $request){
        try {
            $formData = [
                "id"  => 631
            ];
            // $formData = [
            //     "id"  => $request->id,
            // ];
            $url        = "cuentascontables_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de los centros de costos existentes.
        Los centros de costos son utilizados para clasificar o agrupar los movimientos de la empresa.
    */
    //api:post/perseo/contabilidad/centrocosto_consulta
    public function centrocosto_consulta(Request $request){
        try {
            $formData = [];
            $url        = "centrocosto_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //api:get/perseo/contabilidad/contabilidad
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
