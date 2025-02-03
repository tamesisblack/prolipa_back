<?php

namespace App\Http\Controllers;

use App\Models\f_tipo_documento;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class f_tipo_documentoController extends Controller
{
    public function GetTipoDocumento_todo(){
        $query = DB::SELECT("SELECT * FROM f_tipo_documento tdo");
        return $query;
    }

    public function GetTipoDocumento_secuenciaxid(Request $request){
        // Obtener la longitud del valor más largo en tdo_secuencial_calmed
        $maxLengthResult = DB::SELECT("SELECT MAX(CHAR_LENGTH(tdo_secuencial_calmed)) as max_length FROM f_tipo_documento");

        if (count($maxLengthResult) > 0 && $maxLengthResult[0]->max_length) {
            $maxLength = $maxLengthResult[0]->max_length;

            // Obtener el valor actual de tdo_secuencial_calmed, tdo_letra y tdo_nombre
            $queryResult = DB::SELECT("SELECT tdo_secuencial_calmed, tdo_letra, tdo_nombre FROM f_tipo_documento
                                       WHERE tdo_id = ?", [$request->id_documento]);

            if (count($queryResult) > 0) {
                $tdoSecuencial = $queryResult[0]->tdo_secuencial_calmed+1;
                $tdoLetra = $queryResult[0]->tdo_letra;
                $tdoNombre = $queryResult[0]->tdo_nombre;

                // Rellenar el valor de tdo_secuencial_calmed con ceros a la izquierda hasta alcanzar la longitud máxima
                $formattedSecuencial = str_pad($tdoSecuencial, $maxLength, '0', STR_PAD_LEFT);

                return response()->json([
                    'tdo_secuencial_calmed' => $formattedSecuencial,
                    'tdo_letra' => $tdoLetra,
                    'tdo_nombre' => $tdoNombre
                ]);
            }

            return response()->json(['error' => 'No se pudo obtener el valor de tdo_secuencial_calmed'], 500);
        }

        return response()->json(['error' => 'No se pudo obtener la longitud máxima de tdo_secuencial_calmed'], 500);
    }

    public function PostRegistrar_modificar_tipo_documento(Request $request)
    {
       if($request->tdo_id){
        $tipo_docuemnto = f_tipo_documento::findOrFail($request->tdo_id);
        $tipo_docuemnto->tdo_nombre = $request->tdo_nombre;
        $tipo_docuemnto->tdo_secuencial_Prolipa = $request->tdo_secuencial_Prolipa;
        $tipo_docuemnto->tdo_secuencial_calmed = $request->tdo_secuencial_calmed;
        $tipo_docuemnto->tdo_letra = $request->tdo_letra;
        $tipo_docuemnto->tdo_descripcion = $request->tdo_descripcion;
        //$tipo_docuemnto->user_created = $request->user_created;
        //$tipo_docuemnto->updated_at = $request->updated_at;
       }else{
           $tipo_docuemnto = new f_tipo_documento;
           $tipo_docuemnto->tdo_nombre = $request->tdo_nombre;
            $tipo_docuemnto->tdo_secuencial_Prolipa = $request->tdo_secuencial_Prolipa;
            $tipo_docuemnto->tdo_secuencial_calmed = $request->tdo_secuencial_calmed;
            $tipo_docuemnto->tdo_letra = $request->tdo_letra;
            $tipo_docuemnto->tdo_descripcion = $request->tdo_descripcion;
            $tipo_docuemnto->user_created = $request->user_created;
        //$tipo_docuemnto->updated_at = $request->updated_at;
       }
        $tipo_docuemnto->save();
        if($tipo_docuemnto){
           return $tipo_docuemnto;
       }else{
           return "No se pudo guardar/actualizar";
       }
    }

    public function Desactivar_tipo_documento(Request $request)
    {
        if ($request->tdo_id) {
            $tipo_docuemnto = f_tipo_documento::find($request->tdo_id);

            if (!$tipo_docuemnto) {
                return "El tdo_id no existe en la base de datos";
            }

            $tipo_docuemnto->tdo_estado = $request->tdo_estado;
            $tipo_docuemnto->save();

            return $tipo_docuemnto;
        } else {
            return "No está ingresando ningún tdo_id";
        }
    }

    public function Eliminar_tipo_documento(Request $request)
    {
        if ($request->tdo_id) {
            $tipo_docuemnto = f_tipo_documento::find($request->tdo_id);

            if (!$tipo_docuemnto) {
                return "El tdo_id no existe en la base de datos";
            }

            $tipo_docuemnto->delete();

            // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto
            $ultimoId  = f_tipo_documento::max('tdo_id') + 1;
            DB::statement('ALTER TABLE f_tipo_documento AUTO_INCREMENT = ' . $ultimoId);

            return $tipo_docuemnto;
        } else {
            return "No está ingresando ningún mot_id";
        }
    }

    public function GetTipoDocumentoParametro(Request $request ){

        if ($request->busqueda == 'nombre') {
            $query = DB::SELECT("SELECT * FROM f_tipo_documento tdo
            WHERE tdo_nombre LIKE '%$request->razonbusqueda%'");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT * FROM f_tipo_documento tdo");
            return $query;
        }
    }
    //api:get/tipoDocumentoXId/5
    public function tipoDocumentoXId($id)
    {
        try {
            $tdo = f_tipo_documento::getLetra($id);
            return $tdo;
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json(['status' => '0', 'mensaje' => $e->getMessage()], 200);
        }
    }

}
