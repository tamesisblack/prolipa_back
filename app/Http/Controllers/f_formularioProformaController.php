<?php

namespace App\Http\Controllers;

use App\Models\f_formulario_proforma;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class f_formularioProformaController extends Controller
{
    public function Get_formularioProformaContador(Request $request){
        $valormasuno = $request->conteo+1;
        $query = DB::SELECT("SELECT ffp_id FROM f_formulario_proforma LIMIT $valormasuno");
        $conteogeneral = count($query);
        if($conteogeneral<$valormasuno){
            return response()->json(['mensaje' => 'data_menor', 'conteo' => $conteogeneral]);
        }else if($conteogeneral==$valormasuno){
            return response()->json(['mensaje' => 'data_igual', 'conteo' => $conteogeneral]);
        }
    }
    
    public function Get_formularioProforma(Request $request){
        $query = DB::SELECT("SELECT ffp.* , pe.descripcion, CONCAT(u.nombres, ' ', u.apellidos) AS usercreador , i.nombreInstitucion, c.nombre as ciudad
        FROM f_formulario_proforma ffp
        INNER JOIN institucion i ON ffp.idInstitucion = i.idInstitucion
        INNER JOIN ciudad c ON i.ciudad_id = c.idciudad
        INNER JOIN periodoescolar pe ON ffp.idperiodoescolar = pe.idperiodoescolar
        LEFT JOIN usuario u ON ffp.user_created = u.idusuario
        WHERE ffp.idperiodoescolar = $request->periodoelegido
        ORDER BY created_at ASC");
        return $query;
    }

    public function GetformularioProforma_xfiltro(Request $request){
        if ($request->busqueda == 'undefined' || $request->busqueda == 'codigoagrupado' || $request->busqueda == '' || $request->busqueda == null) {
            $query = DB::SELECT("SELECT fca.ca_descripcion, fca.ca_codigo_agrupado, CONCAT(u.nombres, ' ', u.apellidos) AS usuariolibreria ,fc.*, u.cedula FROM f_formulario_proforma fc 
            INNER JOIN f_contratos_agrupados fca ON fc.ca_id = fca.ca_id
            INNER JOIN usuario u ON fc.idusuario = u.idusuario
            WHERE fc.ffp_id LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
        if ($request->busqueda == 'nombreusuario') {
            $query = DB::SELECT("SELECT fca.ca_descripcion, fca.ca_codigo_agrupado, CONCAT(u.nombres, ' ', u.apellidos) AS usuariolibreria ,fc.*, u.cedula FROM f_formulario_proforma fc 
            INNER JOIN f_contratos_agrupados fca ON fc.ca_id = fca.ca_id
            INNER JOIN usuario u ON fc.idusuario = u.idusuario
            WHERE fc.idusuario LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
        if ($request->busqueda == 'infocontratoagrupado') {
            $query = DB::SELECT("SELECT fca.ca_descripcion, fca.ca_codigo_agrupado, CONCAT(u.nombres, ' ', u.apellidos) AS usuariolibreria ,fc.*, u.cedula 
            FROM f_formulario_proforma fc 
            INNER JOIN f_contratos_agrupados fca ON fc.ca_id = fca.ca_id
            INNER JOIN usuario u ON fc.idusuario = u.idusuario
            WHERE fc.ca_id LIKE '%$request->razonbusqueda%'
            ORDER BY created_at ASC
            ");
            return $query;
        }
    }

    public function Post_Registrar_modificar_formularioProforma(Request $request)
    {
        DB::beginTransaction();
            try {
            $this->ActualizarAutoIncrementable();
            // Buscar el formularioProforma por su ffp_id o crear uno nuevo
            $formularioProforma = f_formulario_proforma::firstOrNew(['ffp_id' => $request->ffp_id]);
            // Asignar los demás datos del formularioProforma
            $formularioProforma->idInstitucion = $request->idInstitucion;
            $formularioProforma->idperiodoescolar = $request->idperiodoescolar;
            $formularioProforma->ffp_credito = $request->ffp_credito;
            $formularioProforma->ffp_cupo = $request->ffp_cupo;
            $formularioProforma->ffp_descuento = $request->ffp_descuento;
            // Verificar si es un nuevo registro o una actualización
            if ($formularioProforma->exists){
                // Si ya existe, omitir el campo user_created para evitar que se establezca en null
                $formularioProforma->updated_at = now();
                // Guardar el formularioProforma sin modificar user_created
                $formularioProforma->save();
            } else {
                // Si es un nuevo registro, establecer user_created y updated_at
                $formularioProforma->updated_at = now();
                $formularioProforma->user_created = $request->user_created;
                $formularioProforma->save();
            }
            // Verificar si el producto se guardó correctamente
            if ($formularioProforma->wasRecentlyCreated || $formularioProforma->wasChanged()) {
                DB::commit();
                return "Se guardó correctamente";
            } else {

                DB::rollback();
                return "No se pudo guardar/actualizar";
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar los datos: ' . $e->getMessage()], 500);
        }
    }

    public function Desactivar_formularioProfroma(Request $request)
    {
        if ($request->ffp_id) {
            $formularioProforma = f_formulario_proforma::find($request->ffp_id);

            if (!$formularioProforma) {
                return "El ffp_id no existe en la base de datos";
            }

            $formularioProforma->ffp_estado = $request->ffp_estado;
            $formularioProforma->save();

            return $formularioProforma;
        } else {
            return "No está ingresando ningún ffp_id";
        }
    }

    public function Post_Eliminar_formularioProfroma(Request $request)
    {
        if ($request->ffp_id) {
            $formularioProforma = f_formulario_proforma::findOrFail($request->ffp_id);

            if (!$formularioProforma) {
                return "El tdo_id no existe en la base de datos";
            }
           
            $formularioProforma->delete();

            $this->ActualizarAutoIncrementable();

            return $formularioProforma;
        } else {
            return "No está ingresando ningún ffp_id";
        }
    }

    public function ActualizarAutoIncrementable()
    {
        // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto 
        $ultimoId  = f_formulario_proforma::max('ffp_id') + 1;
        DB::statement('ALTER TABLE f_formulario_proforma AUTO_INCREMENT = ' . $ultimoId);
    }
}
