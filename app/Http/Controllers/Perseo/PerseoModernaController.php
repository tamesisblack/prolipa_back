<?php

namespace App\Http\Controllers\Perseo;

use App\Http\Controllers\Controller;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;

class PerseoModernaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    use TraitPedidosGeneral;
    /*
        Toda la información de los clientes, que se ha ingresado en la ventana de mantenimiento de clientes.
    */
    //api:post/perseo/moderna/CUSTOMERS
    public function CUSTOMERS(Request $request)
    {
        try {
            $formData   = [
                "dateFrom"  => "20000101",
                "dateTo"    => "20211231"
            ];
            // $formData   = [
            //     "dateFrom"  => $request->dateFrom,
            //     "dateTo"    => $request->dateTo,
            // ];
            $url        = "CUSTOMERS";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Recibe la información de las ventas realizadas, en este procedimiento incluyen también los pedidos y notas de créditos realizadas, las cuales se se puede diferenciar por el campo type.
    */
    //api:post/perseo/moderna/SALES
    public function SALES(Request $request)
    {
        try {
            $formData = [
                "dateFrom"  => "20000101",
                "dateTo"    => "20211231"
            ];
            // $formData   = [
            //     "dateFrom"  => $request->dateFrom,
            //     "dateTo"    => $request->dateTo,
            // ];
            $url        = "SALES";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Devuelve el listado de todas las sucursales de los clientes ingresado en el sistema contable Perseo.
    */
    //api:post/perseo/moderna/CUSTOMERS_ADDRESSES
    public function CUSTOMERS_ADDRESSES(Request $request)
    {
        try {
            $formData = [
                "dateFrom"  => "20000101",
                "dateTo"    => "20211231"
            ];
            // $formData   = [
            //     "dateFrom"  => $request->dateFrom,
            //     "dateTo"    => $request->dateTo,
            // ];
            $url        = "CUSTOMERS_ADDRESSES";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Listado de los productos ingresado en la ventana de mantenimiento de productos del sistema Perseo.
    */
    //api:post/perseo/moderna/ARTICLES
    public function ARTICLES(Request $request)
    {
        try {
            $formData = [
                "dateFrom"  => "20000101",
                "dateTo"    => "20211231"
            ];
            // $formData   = [
            //     "dateFrom"  => $request->dateFrom,
            //     "dateTo"    => $request->dateTo,
            // ];
            $url        = "ARTICLES";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Historial del movimiento de cada uno de los productos.
    */
    //api:post/perseo/moderna/INVENTORY
    public function INVENTORY(Request $request)
    {
        try {
            $formData = [
                "dateFrom"  => "20000101",
                "dateTo"    => "20211231"
            ];
            // $formData   = [
            //     "dateFrom"  => $request->dateFrom,
            //     "dateTo"    => $request->dateTo,
            // ];
            $url        = "INVENTORY";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Devuelve el listado de las rutas creadas en el sistema Perseo.

    */
    //api:post/perseo/moderna/ROUTES
    public function ROUTES(Request $request)
    {
        try {
            $formData   = [];
            $url        = "ROUTES";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Se extrae de los datos ingresados en las visitas de los clientes.
    */
    //api:post/perseo/moderna/ROUTES_DETAILS
    public function ROUTES_DETAILS(Request $request)
    {
        try {
            $formData = [
                "dateFrom"  => "20000101",
                "dateTo"    => "20211231"
            ];
            // $formData   = [
            //     "dateFrom"  => $request->dateFrom,
            //     "dateTo"    => $request->dateTo,
            // ];
            $url        = "ROUTES_DETAILS";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Se extrae de los datos ingresados en las visitas de los clientes, incluyendo la geo ubicación de cada visita.
    */
    //api:post/perseo/moderna/USERS_HISTORY
    public function USERS_HISTORY(Request $request)
    {
        try {
            $formData = [
                "dateFrom"  => "20000101",
                "dateTo"    => "20211231"
            ];
            // $formData   = [
            //     "dateFrom"  => $request->dateFrom,
            //     "dateTo"    => $request->dateTo,
            // ];
            $url        = "USERS_HISTORY";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Muestra la información de los vendedores.
    */
    //api:post/perseo/moderna/USER_IN_ROUTE
    public function USER_IN_ROUTE(Request $request)
    {
        try {
            $formData = [
                "dateFrom"  => "20000101",
                "dateTo"    => "20211231"
            ];
            // $formData   = [
            //     "dateFrom"  => $request->dateFrom,
            //     "dateTo"    => $request->dateTo,
            // ];
            $url        = "USER_IN_ROUTE";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //api:get/perseo/moderna/moderna
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
