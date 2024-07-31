<?php

namespace App\Http\Controllers\Perseo;

use App\Http\Controllers\Controller;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;

class PerseoConsultasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    use TraitPedidosGeneral;
    /*
        Este procedimiento se utiliza para extraer el listado de todos los agentes de ventas activos; estos incluyen que sea vendedores, facturadores, cobradores y despachadores respectivamente.
    */
    //api:post/perseo/consultas/facturadores_consulta
    public function facturadores_consulta(Request $request)
    {
        try {
            $formData = [];
            $url        = "facturadores_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de los almacenes existentes en el sistema. No contiene filtros, así que extrae todos los almacenes.
    */
    //api:post/perseo/consultas/almacenes_consulta
    public function almacenes_consulta(Request $request)
    {
        try {
            $formData = [];
            $url        = "almacenes_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las provincias para la ubicacion de los clientes. No contiene filtros, así que extrae todos los almacenes.
    */
    //api:post/perseo/consultas/consulta_provincias
    public function consulta_provincias(Request $request)
    {
        try {
            $formData   = [];
            $url        = "consulta_provincias";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las parroquias. No contiene filtros, así que extrae todos los almacenes.
    */
    //api:post/perseo/consultas/consulta_parroquias
    public function consulta_parroquias(Request $request)
    {
        try {
            $formData   = [];
            $url        = "consulta_parroquias";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las ciudades. No contiene filtros, así que extrae todos los almacenes.
    */
    //api:post/perseo/consultas/consulta_ciudades
    public function consulta_ciudades(Request $request)
    {
        try {
            $formData   = [];
            $url        = "consulta_ciudades";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las cuentas bancarias pertenecientes a la empresa y que se hayan registrado en el sistema contable.
    */
    //api:post/perseo/consultas/bancos_consulta
    public function bancos_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "bancos_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de los centros de costos pertenecientes a la empresa y que se hayan registrado en el sistema contable.
    */
    //api:post/perseo/consultas/centrocosto_consulta
    public function centrocosto_consulta(Request $request)
    {
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
    /*
        Este procedimiento se utiliza para extraer el listado de los bancos en relación a los clientes.
    */
    //api:post/perseo/consultas/clientes_bancos_consulta
    public function clientes_bancos_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "clientes_bancos_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las cajas existentes en el sistema. No contiene filtros, así que extrae todos las cajas.
    */
    //api:post/perseo/consultas/cajas_consulta
    public function cajas_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "cajas_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las formas de pagos utilizadas en el sistema, estos datos son fijos.
    */
    //api:post/perseo/consultas/formapagoempresa_consulta
    public function formapagoempresa_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "formapagoempresa_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las formas de pago requeridas por el SRI. Estos datos son fijos sin posibilidad a cambios
    */
    //api:post/perseo/consultas/formapagosri_consulta
    public function formapagosri_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "formapagosri_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las secuencias activas con las que se trabaja en el sistema; esto incluye, secuencias de facturas, retenciones, notas de créditos.. etc.
    */
    //api:post/perseo/consultas/secuencias_consulta
    public function secuencias_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "secuencias_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de los nombres de las tarjetas de créditos creadas en el sistema contable.
    */
    //api:post/perseo/consultas/tarjetas_consulta
    public function tarjetas_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "tarjetas_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de todas las unidades de medidas con las que trabajan en el sistema contable.
    */
    //api:post/perseo/consultas/medidas_consulta
    public function medidas_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "medidas_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las tarifas con las que se trabaja en el sistema contable.
    */
    //api:post/perseo/consultas/tarifas_consulta
    public function tarifas_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "tarifas_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de los diferentes valores de IVA registrados en el sistema contable.
    */
    //api:post/perseo/consultas/tipoiva_consulta
    public function tipoiva_consulta(Request $request)
    {
        try {
            $formData   = [];
            $url        = "tipoiva_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //api:get/perseo/consultas/consultas
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
