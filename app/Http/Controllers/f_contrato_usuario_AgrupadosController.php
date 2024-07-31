<?php

namespace App\Http\Controllers;

use App\Models\f_contrato_usuario_agrupados;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class f_contrato_usuario_AgrupadosController extends Controller
{
    public function Get_Contrato_UsuarioContador(Request $request){
        $valormasuno = $request->conteo+1;
        $query = DB::SELECT("SELECT fcu_id FROM f_contratos_usuarios_agrupados LIMIT $valormasuno");
        $conteogeneral = count($query);
        if($conteogeneral<$valormasuno){
            return response()->json(['mensaje' => 'data_menor', 'conteo' => $conteogeneral]);
        }else if($conteogeneral==$valormasuno){
            return response()->json(['mensaje' => 'data_igual', 'conteo' => $conteogeneral]);
        }
    }
    
    public function Get_Contrato_Usuario(){
        $query = DB::SELECT("SELECT fca.ca_descripcion, fca.ca_codigo_agrupado, CONCAT(u.nombres, ' ', u.apellidos) AS usuariolibreria ,fc.* , u.cedula
        FROM f_contratos_usuarios_agrupados fc 
        INNER JOIN f_contratos_agrupados fca ON fc.ca_id = fca.ca_id
        INNER JOIN usuario u ON fc.idusuario = u.idusuario
        ORDER BY created_at ASC");
        return $query;
    }

    public function GetContrato_Usuario_xfiltro(Request $request){
        if ($request->busqueda == 'undefined' || $request->busqueda == 'codigoagrupado' || $request->busqueda == '' || $request->busqueda == null) {
            $query = DB::SELECT("SELECT fca.ca_descripcion, fca.ca_codigo_agrupado, CONCAT(u.nombres, ' ', u.apellidos) AS usuariolibreria ,fc.*, u.cedula FROM f_contratos_usuarios_agrupados fc 
            INNER JOIN f_contratos_agrupados fca ON fc.ca_id = fca.ca_id
            INNER JOIN usuario u ON fc.idusuario = u.idusuario
            WHERE fc.fcu_id LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
        if ($request->busqueda == 'nombreusuario') {
            $query = DB::SELECT("SELECT fca.ca_descripcion, fca.ca_codigo_agrupado, CONCAT(u.nombres, ' ', u.apellidos) AS usuariolibreria ,fc.*, u.cedula FROM f_contratos_usuarios_agrupados fc 
            INNER JOIN f_contratos_agrupados fca ON fc.ca_id = fca.ca_id
            INNER JOIN usuario u ON fc.idusuario = u.idusuario
            WHERE fc.idusuario LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
        if ($request->busqueda == 'infocontratoagrupado') {
            $query = DB::SELECT("SELECT fca.ca_descripcion, fca.ca_codigo_agrupado, CONCAT(u.nombres, ' ', u.apellidos) AS usuariolibreria ,fc.*, u.cedula 
            FROM f_contratos_usuarios_agrupados fc 
            INNER JOIN f_contratos_agrupados fca ON fc.ca_id = fca.ca_id
            INNER JOIN usuario u ON fc.idusuario = u.idusuario
            WHERE fc.ca_id LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
    }

    public function Post_Registrar_modificar_contrato_usuario(Request $request)
    {
        DB::beginTransaction();
            try {
            // Buscar el contrato_usuario_agrupado por su fcu_id o crear uno nuevo
            $contrato_usuario_agrupado = f_contrato_usuario_agrupados::firstOrNew(['fcu_id' => $request->fcu_id]);
            // Asignar los demás datos del contrato_usuario_agrupado
            $contrato_usuario_agrupado->ca_id = $request->ca_id;
            $contrato_usuario_agrupado->idusuario = $request->idusuario;
            // Verificar si es un nuevo registro o una actualización
            if ($contrato_usuario_agrupado->exists){
                // Si ya existe, omitir el campo user_created para evitar que se establezca en null
                $contrato_usuario_agrupado->updated_at = now();
                // Guardar el contrato_usuario_agrupado sin modificar user_created
                $contrato_usuario_agrupado->save();
            } else {
                // Si es un nuevo registro, establecer user_created y updated_at
                $contrato_usuario_agrupado->updated_at = now();
                $contrato_usuario_agrupado->user_created = $request->user_created;
                $contrato_usuario_agrupado->save();
            }
            // Verificar si el producto se guardó correctamente
            if ($contrato_usuario_agrupado->wasRecentlyCreated || $contrato_usuario_agrupado->wasChanged()) {
                DB::commit();
                return "Se guardó correctamente";
            } else {
                $this->ActualizarAutoIncrementable();
                DB::rollback();
                return "No se pudo guardar/actualizar";
            }
        } catch (\Exception $e) {
            $this->ActualizarAutoIncrementable();
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }

    public function Desactivar_contrato_usuario(Request $request)
    {
        if ($request->fcu_id) {
            $contrato_usuario_agrupado = f_contrato_usuario_agrupados::find($request->fcu_id);

            if (!$contrato_usuario_agrupado) {
                return "El fcu_id no existe en la base de datos";
            }

            $contrato_usuario_agrupado->fcu_estado = $request->fcu_estado;
            $contrato_usuario_agrupado->save();

            return $contrato_usuario_agrupado;
        } else {
            return "No está ingresando ningún fcu_id";
        }
    }

    public function Post_Eliminar_contrato_usuario(Request $request)
    {
        if ($request->fcu_id) {
            $contrato_usuario_agrupado = f_contrato_usuario_agrupados::findOrFail($request->fcu_id);

            if (!$contrato_usuario_agrupado) {
                return "El tdo_id no existe en la base de datos";
            }
           
            $contrato_usuario_agrupado->delete();

            $this->ActualizarAutoIncrementable();

            return $contrato_usuario_agrupado;
        } else {
            return "No está ingresando ningún mot_id";
        }
    }

    public function ActualizarAutoIncrementable()
    {
        // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto 
        $ultimoId  = f_contrato_usuario_agrupados::max('fcu_id') + 1;
        DB::statement('ALTER TABLE f_contratos_usuarios_agrupados AUTO_INCREMENT = ' . $ultimoId);
    }
}
