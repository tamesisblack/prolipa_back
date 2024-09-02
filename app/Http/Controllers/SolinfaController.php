<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\Codigos\TraitCodigosGeneral;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

use DB;
class SolinfaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //api:get/solinfa
    use TraitPedidosGeneral;
    use TraitCodigosGeneral;
    public function index(Request $request){

        if($request->filtroFechasFacturas)      { return $this->filtroFechasFacturas($request); }
        // $results = DB::connection('mysql2')->select('SELECT * FROM cajas limit 10');
        // return $results;
    }
    //api:get/solinfa?filtroFechasFacturas=1&fechaInicio=2023-08-01&fechaFin=2023-09-01
    public function filtroFechasFacturas($request){
        $query = DB::connection('mysql2')->select("
            SELECT s.*,
            CONCAT(p.name,' ',p.lastname) AS cliente, p.pin as cedula,
            (
                SELECT COUNT(o.id) AS contador
                FROM operation o
                WHERE  o.sell_id = s.id
            ) as contador
            FROM sell s
            LEFT JOIN person p ON p.id = s.person_id
            WHERE s.created_at >= ?
            AND s.created_at < ?
            AND s.code LIKE 'F%'
            AND s.status = '0'
            AND s.person_id <> '10'
        ", [$request->fechaInicio, $request->fechaFin]);
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
    //solinfa/124
    public function show($id)
    {
        $results = DB::connection('mysql2')->select("SELECT o.*, p.barcode, p.name, p.price_in, o.discount,
            ROUND(o.q * p.price_in, 2) AS valorTotal
            FROM operation o
            LEFT JOIN product p ON o.product_id = p.id
            WHERE o.sell_id = ?
        ",[$id]);
        return $results;
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
