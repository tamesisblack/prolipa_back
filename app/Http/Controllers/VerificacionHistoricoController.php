<?php

namespace App\Http\Controllers;
use App\Models\VerificacionHistorico;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;

class VerificacionHistoricoController extends Controller
{
    public function dventaxvencodigo($vencodigo){
        $DetalleVenta = DB::SELECT("SELECT * FROM verificaciones_detalleventa_historico WHERE vencodigo LIKE '%$vencodigo%' AND tipo = '1' AND accion = '1' ORDER BY created_At DESC");
        return["DetalleVenta" => $DetalleVenta];
    }

    public function dverificacionxvencodigo($vencodigo){
        $DetalleVerificacion = DB::SELECT("SELECT * FROM verificaciones_detalleventa_historico WHERE vencodigo LIKE '%$vencodigo%' AND tipo = '2' AND ( accion = '1' || accion = '2') ORDER BY created_At DESC");
        return["DetalleVerificacion" => $DetalleVerificacion];
    }

    public function historicoverificacionsinparametros(){
        $Historico = DB::SELECT("SELECT * FROM verificaciones_detalleventa_historico ORDER BY created_At DESC");
        return["Historico" => $Historico];
    }
}
