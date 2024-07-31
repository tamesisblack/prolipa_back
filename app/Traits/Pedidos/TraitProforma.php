<?php

namespace App\Traits\Pedidos;

use App\Models\Proformahistorico;
use DB;
use Illuminate\Support\Facades\Http;
trait TraitProforma
{
    public function tr_GuardarEnHistorico ($id_prof,$usuario_editor,$old_values,$new_values){
        $historico = new Proformahistorico();
        $historico->id_prof                 =  $id_prof;
        $historico->id_usua                 =  $usuario_editor;
        $historico->old_value               =  $old_values;
        $historico->new_value               =  $new_values;
        $historico->save();
        return "Guardado en historico";
    }


}