<?php

namespace App\Http\Controllers;

use App\Models\Contratos_agrupados;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class f_contrato_AgrupadoController extends Controller
{
    public function Get_Contratosagrupados_Completo(){
        $query = DB::SELECT("SELECT ca.*, CONCAT(ca.ca_codigo_agrupado, ' - ',ca.ca_descripcion) datoscontagrupados FROM f_contratos_agrupados ca;");
        return $query;
    }
}
