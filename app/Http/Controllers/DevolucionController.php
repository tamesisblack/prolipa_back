<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\Facturacion\ProformaRepository;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;

class DevolucionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $proformaRepository;
    public function __construct(ProformaRepository $proformaRepository)
    {
        $this->proformaRepository = $proformaRepository;
    }
    //API:GET/devoluciones
    public function index(Request $request)
    {
       if($request->listadoProformasAgrupadas)  { return $this->listadoProformasAgrupadas($request); }
    }
    //api:get/devoluciones?listadoProformasAgrupadas=1&institucion=
    public function listadoProformasAgrupadas(Request $request): \Illuminate\Http\JsonResponse
    {
        $institucion = $request->input('institucion');
        return $this->proformaRepository->listadoProformasAgrupadas($institucion);
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
    //api:post/devoluciones
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
