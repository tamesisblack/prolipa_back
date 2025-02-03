<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\CombosCodigos;
use App\Models\HistoricoCombos;
use App\Models\CodigosLibros;
use Illuminate\Http\Request;
use App\Traits\Codigos\TraitCodigosGeneral;

class CombosCodigosController extends Controller
{
    use TraitCodigosGeneral;
    //api:get/combos/combos

    public function getExistsCombo($combo){
        $query = DB::SELECT("SELECT * FROM codigos_combos c WHERE c.codigo = '$combo'");
        return $query;
    }

    public function generarCodigosCombo(Request $request){
        $resp_search            = array();
        $codigos_validacion     = array();
        $longitud               = $request->longitud;
        $code                   = $request->code;
        $cantidad               = $request->cantidad;
        $codigos = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $caracter   = $this->makeid($longitud);
            $codigo     = $code.$caracter;
            // valida repetidos en generacion
            $valida_gen = 1;
            $cant_int   = 0;
            while ( $valida_gen == 1 ) {
                $caracter = $this->makeid($longitud);
                $codigo = $code.$caracter;
                $valida_gen = 0;
                for( $k=0; $k<count($codigos_validacion); $k++ ){
                    if( $codigo == $codigos_validacion[$k] ){
                        array_push($resp_search, $codigo);
                        $valida_gen = 1;
                        break;
                    }
                }
                $cant_int++;
                if( $cant_int == 10 ){
                    $codigo = "no_disponible";
                    $valida_gen = 0;
                }
            }
            if( $codigo != 'no_disponible' ){
                // valida repetidos en DB
                $validar  = $this->getExistsCombo($codigo);
                $cant_int = 0;
                $codigo_disponible = 1;
                while ( count($validar) > 0 ) {
                    // array_push($repetidos, $codigo);
                    $caracter = $this->makeid($longitud);
                    $codigo = $code.$caracter;
                    $validar  = $this->getExistsCombo($codigo);
                    $cant_int++;
                    if( $cant_int == 10 ){
                        $codigo_disponible = 0;
                        $validar = ['repetido' => 'repetido'];
                    }
                }
                if( $codigo_disponible == 1 ){
                    array_push($codigos_validacion, $codigo);
                    array_push($codigos, ["codigo" => $codigo]);
                }
            }
        }
        return ["codigos" => $codigos, "repetidos" => $resp_search];
    }

    public function store(Request $request)
    {
        set_time_limit(600000);
        ini_set('max_execution_time', 600000);
        $codigos                            = explode(",", $request->codigo);
        $porcentajeAnterior                 = 0;
        $codigosNoIngresadosAnterior        = [];
        //only codigos
        $resultado                          = $this->save_Codigos($request,$codigos);
        $porcentajeAnterior                 = $resultado["porcentaje"];
        $codigosNoIngresadosAnterior        = $resultado["codigosNoIngresados"];
        $codigosGuardados                   = $resultado["codigosGuardados"];
        return[
            "porcentajeAnterior"            => $porcentajeAnterior,
            "codigosNoIngresadosAnterior"   => $codigosNoIngresadosAnterior,
            "codigosGuardados"              => $codigosGuardados,
        ];
    }

    public function save_Codigos($request,$codigos){
        $tam                = sizeof($codigos);
        $porcentaje         = 0;
        $codigosError       = [];
        $codigosGuardados   = [];
        $contador           = 0;
        for( $i=0; $i<$tam; $i++ ){
            $codigos_libros                             = new CombosCodigos();
            $codigos_libros->user_created               = $request->user_created;
            $codigo_verificar                           = $codigos[$i];
            $verificar_codigo  = $this->getExistsCombo($codigo_verificar);
            if( count($verificar_codigo) > 0 ){
                $codigosError[$contador] = [
                    "codigo" =>  $codigo_verificar
                ];
                $contador++;
            }else{
                $codigos_libros->codigo = $codigos[$i];
                $codigos_libros->save();
                $codigosGuardados[$porcentaje] = [
                    "codigo" =>  $codigos[$i]
                ];
                $porcentaje++;
            }
        }
        return ["porcentaje" =>$porcentaje ,"codigosNoIngresados" => $codigosError,"codigosGuardados" => $codigosGuardados] ;
    }

    public function show($combo)
    {
        $query = DB::SELECT("SELECT cmb.*,
        CONCAT(u.nombres, ' ', u.apellidos) as editor
        FROM codigos_combos cmb
        LEFT JOIN usuario u ON cmb.user_created = u.idusuario
        WHERE cmb.codigo LIKE '%$combo%'
        ");
        $datos = [];
        foreach($query as $key => $item){
            $codigosCombos = [];
            $codigosCombos = $this->getCodigosXCombo($item->codigo);
            $datos[$key] = [
                "combo"         => $item->codigo,
                "editor"        => $item->editor,
                "user_created"  => $item->user_created,
                "estado"        => $item->estado,
                "created_at"    => $item->created_at,
                "codigos"       => $codigosCombos
            ];
        }
        return $datos;
    }

    public function getCodigosXCombo($combo){
        $query = DB::SELECT("SELECT codigo,libro,codigo_proforma,factura,combo FROM codigoslibros c
        WHERE c.codigo_combo = '$combo'
        ");
        return $query;
    }


    public function ComboModificar(Request $request){
        if($request->eliminarCombos) { return $this->eliminarCombos($request); }
        if($request->bloquearCombo) { return $this->bloquearCombo($request); }
        if($request->cleanCombo)    { return $this->cleanCombo($request); }
    }

    public function cleanCombo($request){
        $arrayCodigos       = json_decode($request->data_codigos);
        $codigo             = CombosCodigos::findOrFail($request->combo);
        $user_created       = $codigo->user_created;
        $comentario         = $request->comentario;
        $usuario_editor     = $request->user_created;
        $periodo_id         = $request->periodo_id;
        $institucion_id     = 0;
        if($codigo->estado == 2){
            return ["status" => "0", "message" => "El combo esta bloqueado"];
        }
        //historico codigos
        foreach($arrayCodigos as $key => $item){
            $oldvalues = [];
            $oldvalues = CodigosLibros::where('codigo',$item->codigo)->get();
            $this->GuardarEnHistorico(0,$institucion_id,$periodo_id,$item->codigo,$usuario_editor,$comentario,$oldvalues[0],null);
        }
        codigoslibros::where('codigo_combo',$request->combo)
        ->update([
            'codigo_combo'            => null,
            'fecha_registro_combo'    => null,
        ]);
        //dejamos el combo en estado abierto
        $codigoCombo = CombosCodigos::Where('codigo',$request->combo)
        ->update([
            'estado' => '1'
        ]);
        //guardar en historico combos
        $this->save_historico_combos([
            "codigo_combo"      => $request->combo,
            "user_created"      => $user_created,
            "observacion"       => $comentario,
            "old_values"        => json_encode($codigo)
        ]);
        return ["status" => "1", "message" => "Se limpio el combo"];
    }

    public function eliminarCombos($request){
        $codigo             = CombosCodigos::findOrFail($request->combo);
        $user_created       = $codigo->user_created;
        $comentario         = $request->comentario;
        if($codigo->estado == 0){
            return ["status" => "1", "message" => "No se puede eliminar el combo, ya fue utilizado"];
        }
        else{
            $codigo->delete();
            //guardar en historico
            $this->save_historico_combos([
                "codigo_combo"      => $request->combo,
                "user_created"      => $user_created,
                "observacion"       => $comentario,
                "old_values"        => json_encode($codigo)
            ]);
            return ["status" => "1", "message" => "Se elimino el combo"];
        }
    }

    public function bloquearCombo($request){
        $codigo             = CombosCodigos::findOrFail($request->combo);
        $user_created       = $codigo->user_created;
        $comentario         = $request->comentario;
        if($codigo->estado == 0){
            return ["status" => "1", "message" => "No se puede eliminar el combo, ya fue utilizado"];
        }else{
            $codigo->estado = 2;
            $codigo->save();
            //guardar en historico
            $this->save_historico_combos([
                "codigo_combo"      => $request->combo,
                "user_created"      => $user_created,
                "observacion"       => $comentario,
                "old_values"        => json_encode($codigo)
            ]);
            return ["status" => "1", "message" => "Se bloqueo el combo"];
        }
    }

    public function save_historico_combos($data){
        $historico                  = new HistoricoCombos();
        $historico->codigo          = $data["codigo_combo"];
        $historico->user_created    = $data["user_created"];
        $historico->observacion     = $data["observacion"];
        $historico->old_values      = $data["old_values"];
        $historico->save();
    }

    public function ImporteliminarCombo(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $id_usuario             = $request->id_usuario;
        $NoEliminados           = [];
        $porcentaje             = 0;
        $contador               = 0;
        $codigosNoExisten       = [];
        $contadorNoExisten      = 0;
        $contadorNoEliminados   = 0;
        $institucion_id         = 0;
        $observacion            = $request->observacion;
        $periodo_id             = $request->periodo_id;
        foreach($codigos as $key => $item){
            $consulta = $this->getExistsCombo($item->codigo);
            //si ya existe el codigo lo mando a un array
            if(count($consulta) > 0){
               $estadoCombo = $consulta[0]->estado;
               //estado 0 => combo utilizado; 1 => combo no utilizado
                if($estadoCombo == 1 || $estadoCombo == 2){
                    $codigos_libros = CombosCodigos::findOrFail($item->codigo);
                    $codigos_libros->delete();
                    $porcentaje++;
                    //guardar en historico
                    $this->save_historico_combos([
                        "codigo_combo"      => $item->codigo,
                        "user_created"      => $id_usuario,
                        "observacion"       => $observacion,
                        "old_values"        => json_encode($consulta[0])
                    ]);
                }
                //combos utilizados
                else{
                    $NoEliminados[$contadorNoEliminados] =[
                        "codigo" => $item->codigo,
                        "problema" => "Combo utilizado"
                    ];
                    $contadorNoEliminados++;
                }
            }
            //combo no existen
            else{
                $codigosNoExisten[$contadorNoExisten] =[
                    "codigo" => $item->codigo,
                    "problema" => "No existe"
                ];
                $contadorNoExisten++;
            }
        }
        $data = [
            "porcentaje"            => $porcentaje,
            "codigosNoExisten"      => $codigosNoExisten,
            "NoEliminados"          => $NoEliminados,
            "contadorNoExisten"     => $contadorNoExisten,
            "contadorNoEliminados"  => $contadorNoEliminados,
        ];
        return $data;
    }

    //api:post/combos/limpiar
    public function ImportLimpiarCombo(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        try{
            //transaccion
            DB::beginTransaction();
            $codigos                = json_decode($request->data_codigos);
            $id_usuario             = $request->id_usuario;
            $NoEliminados           = [];
            $porcentaje             = 0;
            $contador               = 0;
            $codigosNoExisten       = [];
            $codigosBloqueados      = [];
            $contadorNoExisten      = 0;
            $contadorNoEliminados   = 0;
            $contadorBloqueados     = 0;
            $observacion            = $request->observacion;
            $periodo_id             = $request->periodo_id;
            $institucion_id         = 0;
            foreach($codigos as $key => $item){
                $consulta = $this->getExistsCombo($item->codigo);
                //si ya existe el codigo lo mando a un array
                if(count($consulta) > 0){
                    $estadoCombo = $consulta[0]->estado;
                    //si el estado es 0 es que el combo esta utilizado
                    if($estadoCombo != 2){
                        //estado 0 => combo utilizado; 1 => combo no utilizado; 2 => combo bloqueado
                        $arrayCodigos = codigoslibros::where('codigo_combo', $item->codigo)->get();
                        //historico codigos
                        foreach($arrayCodigos as $key2 => $item2){
                            $oldvalues = [];
                            $oldvalues = CodigosLibros::where('codigo', $item2->codigo)->get();
                            $comentario = $observacion."_".$item->codigo;
                            $this->GuardarEnHistorico(0, $institucion_id, $periodo_id, $item2->codigo, $id_usuario, $comentario, $oldvalues[0], null);
                        }
                        //limpiar el combo de los codigos
                        codigoslibros::where('codigo_combo', $item->codigo)->update([
                            'codigo_combo'         => null,
                            'fecha_registro_combo'    => null,
                        ]);
                        $porcentaje++;
                        //dejamos el combo en estado abierto
                        CombosCodigos::Where('codigo',$item->codigo)
                        ->update([
                            'estado' => '1'
                        ]);
                        //guardar en historico
                        $this->save_historico_combos([
                            "codigo_combo"      => $item->codigo,
                            "user_created"      => $id_usuario,
                            "observacion"       => $observacion,
                            "old_values"        => json_encode($consulta[0])
                        ]);
                    }
                    //combos bloqueados
                    else{
                        $codigosBloqueados[$contadorBloqueados] =[
                            "codigo" => $item->codigo,
                            "problema" => "Combo bloqueado"
                        ];
                        $contadorBloqueados++;
                    }
                }
                //combo no existen
                else{
                    $codigosNoExisten[$contadorNoExisten] =[
                        "codigo" => $item->codigo,
                        "problema" => "No existe"
                    ];
                    $contadorNoExisten++;
                }
            }
            $data = [
                "porcentaje"            => $porcentaje,
                "codigosNoExisten"      => $codigosNoExisten,
                "NoEliminados"          => $NoEliminados,
                "contadorNoExisten"     => $contadorNoExisten,
                "contadorNoEliminados"  => $contadorNoEliminados,
                "contadorBloqueados"    => $contadorBloqueados,
                "codigosBloqueados"     => $codigosBloqueados
            ];
            DB::commit();
            return $data;
        }
        catch(\Exception $ex){
            DB::rollback();
            return ["status" => "0", "message" => "Error al limpiar combo", "error" => "error".$ex];
        }

    }

    //api:post/combos/bloquear
    public function ImportBloquearCombo(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $codigos                = json_decode($request->data_codigos);
        $id_usuario             = $request->id_usuario;
        $noBloqueados           = [];
        $porcentaje             = 0;
        $contador               = 0;
        $codigosNoExisten       = [];
        $contadorNoExisten      = 0;
        $contadorBloqueados     = 0;
        $institucion_id         = 0;
        $observacion            = $request->observacion;
        $periodo_id             = $request->periodo_id;
        foreach($codigos as $key => $item){
            $consulta = $this->getExistsCombo($item->codigo);
            //si ya existe el codigo lo mando a un array
            if(count($consulta) > 0){
               $estadoCombo = $consulta[0]->estado;
               //estado 0 => combo utilizado; 1 => combo no utilizado
                if($estadoCombo == 1 || $estadoCombo == 2){
                    $codigos_libros         = CombosCodigos::findOrFail($item->codigo);
                    $codigos_libros->estado = 2;
                    $codigos_libros->save();
                    $porcentaje++;
                    //guardar en historico
                    $this->save_historico_combos([
                        "codigo_combo"      => $item->codigo,
                        "user_created"      => $id_usuario,
                        "observacion"       => $observacion,
                        "old_values"        => json_encode($consulta[0])
                    ]);
                }
                //combos utilizados
                else{
                    $noBloqueados[$contadorBloqueados] =[
                        "codigo" => $item->codigo,
                        "problema" => "Combo utilizado"
                    ];
                    $contadorBloqueados++;
                }
            }
            //combo no existen
            else{
                $codigosNoExisten[$contadorNoExisten] =[
                    "codigo" => $item->codigo,
                    "problema" => "No existe"
                ];
                $contadorNoExisten++;
            }
        }
        $data = [
            "porcentaje"            => $porcentaje,
            "codigosNoExisten"      => $codigosNoExisten,
            "noBloqueados"          => $noBloqueados,
            "contadorNoExisten"     => $contadorNoExisten,
            "contadorBloqueados"    => $contadorBloqueados,
        ];
        return $data;
    }
}
