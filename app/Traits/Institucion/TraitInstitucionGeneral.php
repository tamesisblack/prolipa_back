<?php

namespace App\Traits\Institucion;

use DB;
use Illuminate\Support\Facades\Http;
trait TraitInstitucionGeneral
{
    public function tr_GetEscuelasXZona($idzona){
        $query = DB::SELECT("SELECT * FROM institucion WHERE zona_id = ?",[$idzona]);
        return $query;
    }
}