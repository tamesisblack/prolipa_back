<?php

namespace App\Http\Controllers;

use App\Models\notaEstudiante;
use Illuminate\Http\Request;

class NotaEstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $request->all();
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
        return $request->all();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\notaEstudiante  $notaEstudiante
     * @return \Illuminate\Http\Response
     */
    public function show(notaEstudiante $notaEstudiante)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\notaEstudiante  $notaEstudiante
     * @return \Illuminate\Http\Response
     */
    public function edit(notaEstudiante $notaEstudiante)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\notaEstudiante  $notaEstudiante
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, notaEstudiante $notaEstudiante)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\notaEstudiante  $notaEstudiante
     * @return \Illuminate\Http\Response
     */
    public function destroy(notaEstudiante $notaEstudiante)
    {
        //
    }
}
