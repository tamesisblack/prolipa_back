<?php

namespace App\Http\Controllers;

use App\Models\AbonoRetencionPorcentaje;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbonoRetencionPorcentajeController extends Controller
{
    public function VerifcarMetodosGet_AbonoRetencionPorcentaje(Request $request)
    {
        $action = $request->query('action'); // Leer el parámetro `action` desde la URL

        switch ($action) {
            case 'GetPorcentajeRetencion':
                return $this->GetPorcentajeRetencion();
            case 'Get_PorcentajeContador':
                return $this->Get_PorcentajeContador($request);
            case 'GetPorcentajeRetencionTodo':
                return $this->GetPorcentajeRetencionTodo();
            case 'GetPorcentaje_xfiltro':
                return $this->GetPorcentaje_xfiltro($request);
            default:
                return response()->json(['error' => 'Acción no válida'], 400);
        }
    }

    public function VerifcarMetodosPost_AbonoRetencionPorcentaje(Request $request)
    {
        $action = $request->input('action'); // Recibir el parámetro 'action'

        switch ($action) {
            case 'Post_Registrar_modificar_Porcentaje':
                return $this->Post_Registrar_modificar_Porcentaje($request);
            case 'ActivarDesactivar_Porcentaje':
                return $this->ActivarDesactivar_Porcentaje($request);
            case 'Post_Eliminar_Porcentaje':
                return $this->Post_Eliminar_Porcentaje($request);
            default:
                return response()->json(['error' => 'Acción no válida'], 400);
        }
    }

    public function GetPorcentajeRetencion() {
        $query = DB:: SELECT("SELECT * FROM abono_retencion_porcentaje arp
        WHERE arp.arp_estado = 1
        ORDER BY arp.arp_valor ASC
        ");
        return $query;
    }

    public function GetPorcentaje_xfiltro(Request $request){
        if ($request->busqueda == 'undefined' || $request->busqueda == 'codigo' || $request->busqueda == '' || $request->busqueda == null) {
            $query = DB::SELECT("SELECT arp.*, arp.arp_id as codigoanterior FROM abono_retencion_porcentaje arp
            WHERE arp.arp_id LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
        if ($request->busqueda == 'nombre') {
            $query = DB::SELECT("SELECT arp.*, arp.arp_id as codigoanterior FROM abono_retencion_porcentaje arp
            WHERE arp.dia_nombre LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
    }

    public function Get_PorcentajeContador(Request $request){
        $valormasuno = $request->conteo+1;
        $query = DB::SELECT("SELECT arp_id FROM abono_retencion_porcentaje LIMIT $valormasuno");
        $conteogeneral = count($query);
        if($conteogeneral<$valormasuno){
            return response()->json(['mensaje' => 'data_menor', 'conteo' => $conteogeneral]);
        }else if($conteogeneral==$valormasuno){
            return response()->json(['mensaje' => 'data_igual', 'conteo' => $conteogeneral]);
        }
    }

    public function GetPorcentajeRetencionTodo() {
        $query = DB:: SELECT("SELECT * FROM abono_retencion_porcentaje arp
        ORDER BY arp.arp_id ASC
        ");
        return $query;
    }

    public function Post_Registrar_modificar_Porcentaje($request)
    {
        // Buscar el constporcentaje por su arp_id o crear uno nuevo
        $constporcentaje = AbonoRetencionPorcentaje::firstOrNew(['arp_id' => $request->arp_id]);
        // Asignar los demás datos del constporcentaje
        $constporcentaje->arp_nombre = $request->arp_nombre;
        $constporcentaje->arp_valor = $request->arp_valor;
        // Verificar si es un nuevo registro o una actualización
        if ($constporcentaje->exists){
            // Si ya existe, omitir el campo user_created para evitar que se establezca en null
            $constporcentaje->updated_at = now();
            // Guardar el constporcentaje sin modificar user_created
            $constporcentaje->save();
        } else {
            // Si es un nuevo registro, establecer user_created y updated_at
            $constporcentaje->updated_at = now();
            $constporcentaje->user_created = $request->user_created;
            $constporcentaje->save();
        }

        // Verificar si el producto se guardó correctamente
        if ($constporcentaje->wasRecentlyCreated || $constporcentaje->wasChanged()) {
            return "Se guardó correctamente";
        } else {
            return "No se pudo guardar/actualizar";
        }
    }


    public function ActivarDesactivar_Porcentaje($request)
    {
        if ($request->arp_id) {
            $constporcentaje = AbonoRetencionPorcentaje::find($request->arp_id);

            if (!$constporcentaje) {
                return "El arp_id no existe en la base de datos";
            }

            $constporcentaje->arp_estado = $request->arp_estado;
            $constporcentaje->save();

            return $constporcentaje;
        } else {
            return "No está ingresando ningún arp_id";
        }
    }

    public function Post_Eliminar_Porcentaje($request)
    {
        //return $request;
        //$Rol = Rol::destroy($request->id);
        $constdias = AbonoRetencionPorcentaje::findOrFail($request->arp_id);
        $constdias->delete();

        // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto
        $ultimoId  = AbonoRetencionPorcentaje::max('arp_id') + 1;
        DB::statement('ALTER TABLE abono_retencion_porcentaje AUTO_INCREMENT = ' . $ultimoId);

        return $constdias;
        //Esta función obtendra el id de la tarea que hayamos seleccionado y la borrará de nuestra BD
    }
}
