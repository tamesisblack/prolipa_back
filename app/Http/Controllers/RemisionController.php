<?php

namespace App\Http\Controllers;

use App\Models\Remision;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RemisionController extends Controller
{
    //
    public function GetRemision(){
        $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
        INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id  ORDER BY rem.remi_fecha_inicio DESC limit 50");
        return $query;
    }
    public function genCodigoE(Request $request){
        if ($request->id==1){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_Prolipa as cod from  f_tipo_documento where tdo_letra='$request->letra'");
        }
        if ($request->id==3){
            $query1 = DB::SELECT("SELECT tdo_letra, tdo_secuencial_calmed as cod from  f_tipo_documento where tdo_letra='$request->letra'");
        }
        if(!empty($query1)){
            $cod=$query1[0]->cod;
            $codi=$cod+1;
            return $codi;
        }
    }
    public function GetRemision_xfiltro(Request $request){
        if ($request->busqueda == 'codigo') {
            $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
            INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id
            WHERE rem.remi_codigo LIKE '%$request->razonbusqueda%'
            ORDER BY rem.remi_fecha_inicio DESC
            ");
            return $query;
        }
        if ($request->busqueda == 'undefined') {
            $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
            INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id
            WHERE rem.remi_codigo LIKE '%$request->razonbusqueda%'
            ORDER BY rem.remi_fecha_inicio DESC
            ");
            return $query;
        }
        if ($request->busqueda == 'nombres') {
            $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
            INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id
            WHERE rem.remi_destinatario LIKE '%$request->razonbusqueda%'
            ORDER BY rem.remi_fecha_inicio DESC
            ");
            return $query;
        }
        if ($request->busqueda == 'factura') {
            $query = DB::SELECT("SELECT *FROM 1_4_remision AS rem INNER JOIN 1_4_transporte AS trans on rem.trans_codigo=trans.trans_codigo 
            INNER JOIN 1_4_remision_motivo AS mot ON rem.remi_motivo=mot.mot_id 
            WHERE rem.remi_num_factura LIKE '%$request->razonbusqueda%'
            ORDER BY rem.remi_fecha_inicio DESC
            ");
            return $query;
        }
    }
    public function PostRemision_Registrar_modificar(Request $request)
    {
        
        
        if($request->remi_codigo){
       
        $remision = Remision::findOrFail($request->remi_codigo);
        
            $remision->remi_motivo = $request->remi_motivo;
            $remision->remi_dir_partida = $request->remi_dir_partida; 
            $remision->remi_destinatario = $request->remi_destinatario;
            $remision->remi_ruc_destinatario = $request->remi_ruc_destinatario;
            $remision->remi_direccion = $request->remi_direccion;
            $remision->remi_nombre_transportista = $request->remi_nombre_transportista;
            $remision->remi_ci_transportista = $request->remi_ci_transportista;
            $remision->remi_detalle = $request->remi_detalle;
            $remision->remi_cantidad = $request->remi_cantidad;
            $remision->remi_unidad_medida = $request->remi_unidad_medida;
            $remision->trans_codigo = $request->trans_codigo;
            $remision->remi_guia_remision = $request->remi_guia_remision;
            $remision->remi_obs = $request->remi_obs;
            $remision->remi_responsable = $request->remi_responsable;
            $remision->remi_paquete = $request->remi_paquete;
            $remision->remi_funda = $request->remi_funda;
            $remision->remi_rollo = $request->remi_rollo;
            $remision->remi_flete = $request->remi_flete;
            $remision->remi_pagado = $request->remi_pagado;
            $remision->remi_idempresa = $request->remi_idempresa;
            $remision->updated_at = now();

       }else{
            $query = DB::SELECT("SELECT remi_codigo FROM 1_4_remision ORDER BY remi_fecha_inicio DESC, remi_codigo DESC LIMIT 1");
            $getSecuencia = 1;
             if(!empty($query)){
                $cod= $query[0]->remi_codigo;
                $codi = explode("-",$cod);
                $getSecuencia=$codi[1]+1;
                $pre=$codi[0];
                $co=$pre."-".$getSecuencia;

             }
             
        
           $remision = new Remision;
            
            $remision->remi_codigo = $co;
            $remision->remi_motivo = $request->remi_motivo;
            $remision->remi_dir_partida = $request->remi_dir_partida; 
            $remision->remi_destinatario = $request->remi_destinatario;
            $remision->remi_ruc_destinatario = $request->remi_ruc_destinatario;
            $remision->remi_direccion = $request->remi_direccion;
            $remision->remi_nombre_transportista = $request->remi_nombre_transportista;
            $remision->remi_ci_transportista = $request->remi_ci_transportista;
            $remision->remi_detalle = $request->remi_detalle;
            $remision->remi_cantidad = $request->remi_cantidad;
            $remision->remi_unidad_medida = $request->remi_unidad_medida;
            $remision->remi_num_factura = $request->remi_num_factura;
            $remision->remi_fecha_inicio = $request->remi_fecha_inicio;
            $remision->trans_codigo = $request->trans_codigo;
            $remision->remi_guia_remision = $request->remi_guia_remision;
            $remision->remi_obs = $request->remi_obs;
            $remision->remi_responsable = $request->remi_responsable;
            $remision->remi_paquete = $request->remi_paquete;
            $remision->remi_funda = $request->remi_funda;
            $remision->remi_rollo = $request->remi_rollo;
            $remision->remi_flete = $request->remi_flete;
            $remision->remi_pagado = $request->remi_pagado;
            $remision->remi_idempresa = $request->remi_idempresa;
            $remision->user_created = $request->user_created;
            $remision->created_at = now();
            $remision->updated_at = now();
           //$area->tipoareas_idtipoarea = $request->idtipoarea;
       }
       $remision->save();
       if($remision){
           return $remision;
       }else{
           return "No se pudo guardar/actualizar";
       }
    
    }
    
   

    public function Eliminar_Remision(Request $request){
        if ($request->remi_codigo) {
            $remision = Remision::find($request->remi_codigo);

            if (!$remision) {
                return "El remi_codigo no existe en la base de datos";
            }

           
            $remision->delete();

            return $remision;
        } else {
            return "No está ingresando ningún remi_codigo";
        }
        

    }
    //api:get/individual>>>>GetRemisionCALMED_FECHA?op=1&individual=PF-C-C25-FR-0000685
    public function GetRemisionCALMED_FECHA(Request $request) {
        try {
            $fechaFiltro = null;
            $cedulaFiltro = null;
            $individual   = $request->individual;
    
            switch ($request->op) {
                case 0:
                    $fechaFiltro = $request->fecha_filtro;
                    break;
                case 1:
                    $fechaFiltro = now()->toDateString(); // Esto solo da la fecha en formato 'Y-m-d'
                    break;
                case 2:
                    // Para el caso 2, también se necesita la cédula
                    $fechaFiltro = $request->fecha_filtro;
                    $cedulaFiltro = $request->cedula;
                    break;
                default:
                    return ["status" => "0", "message" => "Operación no válida"];
            }
    
            // Construir la consulta base
            $query = "SELECT DISTINCT r.*,  t.trans_nombre, 
                    (SELECT CONCAT(i.telefonoInstitucion, ' ', u.telefono) 
                     FROM f_venta f 
                     INNER JOIN institucion i ON i.idInstitucion = f.institucion_id
                     INNER JOIN usuario u ON u.idusuario = f.ven_cliente
                     WHERE f.ven_codigo = r.remi_num_factura AND f.id_empresa = r.remi_idempresa) AS telefono,
                    (SELECT c.nombre 
                     FROM f_venta f
                     INNER JOIN institucion i ON i.idInstitucion = f.institucion_id 
                     INNER JOIN ciudad c ON i.ciudad_id = c.idciudad 
                     WHERE f.ven_codigo = r.remi_num_factura AND f.id_empresa = r.remi_idempresa) AS ciudad, 
                    i.ruc,
                    CONCAT(u.nombres, ' ', u.apellidos) AS cliente, 
                    fpr.prof_observacion,
                    fv.ven_observacion,
                    emp.descripcion_corta,
                    fv.ruc_cliente,
                    fv.est_ven_codigo
                FROM empacado_remision r
                LEFT JOIN 1_4_transporte t ON r.trans_codigo = t.trans_codigo
                LEFT JOIN f_venta fv ON fv.ven_codigo = r.remi_num_factura AND r.remi_idempresa = fv.id_empresa
                LEFT JOIN usuario u ON fv.ven_cliente = u.idusuario
                LEFT JOIN institucion i ON fv.institucion_id = i.idInstitucion
                LEFT JOIN f_proforma fpr ON fpr.prof_id = fv.ven_idproforma AND fpr.emp_id = fv.id_empresa
                LEFT JOIN empresas emp ON emp.id = r.remi_idempresa
                WHERE r.remi_estado = '2'
                AND ";
            if($individual){
                $query .= "r.remi_num_factura = ? ";
                $queryParams = [$individual];
            }else{
                  // Condicionales basadas en el valor de 'op'
                if ($request->op == 2) {
                    // En caso de 'op' == 2, agregar ambas condiciones (fecha y cédula)
                    $query .= "DATE(r.REMI_FECHA_INICIO) = ? AND r.remi_ci_transportista = ? ";
                    $queryParams = [$fechaFiltro, $cedulaFiltro];
                } else {
                    // Para 'op' == 0 o 'op' == 1, solo agregar la condición de fecha
                    $query .= "DATE(r.REMI_FECHA_INICIO) = ? ";
                    $queryParams = [$fechaFiltro];
                }
            }

            // Ordenar por fecha
            $query .= "ORDER BY r.remi_fecha_inicio ASC";
    
            // Ejecutar la consulta
            $result = DB::select($query, $queryParams);
    
            return response()->json($result);
        } catch (\Exception $e) {
            return ["status" => "0", "message" => "Error al obtener el listado de empacados", "error" => $e->getMessage()];
        }
    }

    public function guias_remision_list(Request $request)
    {
        $rucsRaw = json_decode($request->input('ruc'));
    
        if (!is_array($rucsRaw)) {
            $rucsRaw = [$rucsRaw];
        }
    
        $rucs = collect($rucsRaw)
            ->filter(fn($item) => isset($item->ruc_cliente))
            ->pluck('ruc_cliente')
            ->toArray();
    
        $periodo = $request->input('periodo');
    
        $guias = DB::table('f_venta as f')
            ->join('empacado_remision as r', 'r.remi_num_factura', '=', 'f.ven_codigo')
            ->select('f.ven_codigo', 'f.id_empresa', 'r.remi_num_factura', 'r.archivo', 'r.url', 'r.remi_guia_remision')
            ->whereIn('f.ruc_cliente', $rucs)
            ->where('f.periodo_id', $periodo)
            ->get();
    
        return response()->json($guias);
    }
    
}
