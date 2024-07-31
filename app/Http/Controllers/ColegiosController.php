<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Libro;
use App\Models\Area;
use App\Models\RecursosColegio;
use App\Models\Planificacion;
use App\Models\Juegos;
use App\Models\ColegioPermisos;
use App\Models\ColegioRecursosAdicionales;
use App\Models\Usuario;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Hash;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
class ColegiosController extends Controller
{
    //select areas 
    public function selectArea(Request $request)
    {
        $area = Area::all();
        foreach ($area as $key => $post) {
            $respuesta = DB::SELECT("SELECT DISTINCT a.idasignatura as id, a.nombreasignatura as name
            FROM asignatura  a
            join area on area.idarea = a.area_idarea
             LEFT JOIN libro l ON l.asignatura_idasignatura = a.idasignatura
            LEFT JOIN libros_series ls ON l.idlibro = ls.idLibro
            WHERE a.area_idarea = ?
            AND a.estado = '1'
            AND (ls.version <> 'PLUS' OR ls.version IS NULL)
            ",[$post->idarea]);
            $data['items'][$key] = [
                'id' => "a".$post->idarea,
                'name' => $post->nombrearea,
                'children'=>$respuesta,
            ];
        }
        return $data;
    }
    //para traer los links generados
    public function colegios_get_links_libro(Request $request){
        $links = DB::SELECT("SELECT ll.id_link, ll.pag_ini, ll.pag_fin, 
        ll.fecha_ini, ll.fecha_fin, l.weblibro,ll.recurso_externo,ll.institucion_id,i.nombreInstitucion,ll.link
        FROM links_libros ll
        LEFT JOIN libro l ON l.idlibro = ll.id_libro
        LEFT JOIN institucion i ON ll.institucion_id = i.idInstitucion
        WHERE ll.id_libro = l.idlibro
         AND ll.id_libro = '$request->id_libro' 
        AND ll.institucion_id = '$request->institucion_id'
        ORDER BY ll.id_link DESC
        ");
        return $links;
    }
    //para traer el listado del las asignaturas del colegio
    public function asignaturas_x_colegio(Request $request)
        {
            $dato = DB::SELECT("SELECT p.id, 
            asig.nombreasignatura,asig.idasignatura,asig.area_idarea,
            p.institucion_id, p.asignatura_id,p.permisos_acordeon,
            p.permisos_libros,p.permisos_cursos,p.permisos_cuadernos,p.permisos_planificaciones,
            a.radicional_id, a.zona_diversion_mi_juego, a.zona_diversion_juego_prolipa, a.material_apoyo_digital, a.material_apoyo_pdf,
            a.propuestas_metodologicas, a.adaptaciones , a.articulos, a.documentos_ministeriales, a.colegio_permiso_id
            FROM colegio_permisos p
            LEFT JOIN asignatura  asig ON p.asignatura_id = asig.idasignatura
            LEFT JOIN colegio_recursos_adicionales a  ON p.id = a.colegio_permiso_id
            WHERE p.institucion_id = '$request->institucion_id'
            ");
            return $dato;
    }
    //para traer el listado del las asignaturas del colegio por asignatura
       public function asignaturas_x_colegio_x_asignatura(Request $request)
       {
           $dato = DB::SELECT("SELECT p.id, 
           asig.nombreasignatura,asig.idasignatura,asig.area_idarea,
           p.institucion_id, p.asignatura_id,p.permisos_acordeon,
           p.permisos_libros,p.permisos_cursos,p.permisos_cuadernos,p.permisos_planificaciones,
           a.radicional_id, a.zona_diversion_mi_juego, a.zona_diversion_juego_prolipa, a.material_apoyo_digital, a.material_apoyo_pdf,
           a.propuestas_metodologicas, a.adaptaciones , a.articulos, a.documentos_ministeriales, a.colegio_permiso_id
           FROM colegio_permisos p
           LEFT JOIN asignatura  asig ON p.asignatura_id = asig.idasignatura
           LEFT JOIN colegio_recursos_adicionales a  ON p.id = a.colegio_permiso_id
           WHERE p.institucion_id = '$request->institucion_id'
           AND p.asignatura_id = '$request->asignatura_id'
           ");
           return $dato;
   }
   //api:post//colegios_cuadernos_usuario_libro
   public function cuadernos_usuario_libro(Request $request)
   {
       $cuadernos = DB::SELECT("SELECT * FROM cuaderno c
       left join colegio_permisos a on c.asignatura_idasignatura = a.asignatura_id
       WHERE a.institucion_id = '$request->institucion_id'
       AND a.asignatura_id = $request->asignatura_id");
       return $cuadernos;
   }
    //para ingresar asignaturas al colegio
    public function asignar_asignatura_colegio(Request $request)
    {
        //editar
        if($request->id > 0){
            $libro = ColegioPermisos::findOrFail($request->id);
        }//guardar
        else{
            //validar que el recurso ya fue asignado a la institucion
            $validar = DB::SELECT("SELECT * FROM colegio_permisos 
            WHERE  institucion_id = '$request->institucion_id'
            AND  asignatura_id = '$request->asignatura_id'
            ");
            if(count($validar) >0){
                return count($validar);
            }
            $libro = new ColegioPermisos();
        }
        $libro->institucion_id           = $request->institucion_id;
        $libro->asignatura_id            = $request->asignatura_id;
        $libro->permisos_acordeon        = $request->permisos_acordeon;
        $libro->permisos_libros          = $request->permisos_libros;
        $libro->permisos_cursos          = $request->permisos_cursos;
        $libro->permisos_cuadernos       = $request->permisos_cuadernos;
        $libro->permisos_planificaciones = $request->permisos_planificaciones;
        $libro->save();
        //editar recurso adicionales
        if($request->radicional_id > 0){
            $radicional = ColegioRecursosAdicionales::findOrFail($request->radicional_id);
            $radicional->colegio_permiso_id             = $request->radicional_id;
        }
        //guardar recurso adicionales
        else{
            $radicional = new ColegioRecursosAdicionales();
            $radicional->colegio_permiso_id             = $libro->id;
        }
        $radicional->zona_diversion_mi_juego            = $request->zona_diversion_mi_juego;
        $radicional->zona_diversion_juego_prolipa       = $request->zona_diversion_juego_prolipa;
        $radicional->material_apoyo_digital             = $request->material_apoyo_digital;
        $radicional->material_apoyo_pdf                 = $request->material_apoyo_pdf;
        $radicional->propuestas_metodologicas           = $request->propuestas_metodologicas;
        $radicional->adaptaciones                       = $request->adaptaciones;
        $radicional->articulos                          = $request->articulos;
        $radicional->documentos_ministeriales           = $request->documentos_ministeriales;
        $radicional->save();
        return ["status" => "1","message" =>"Se guardo correctamente"];
    }


    //para traer los permisos 
    //api:get//>/colegios/permisos
    public function permisos(Request $request){
        $permisos = DB::SELECT("SELECT * FROM colegio_permisos c
        WHERE c.institucion_id = '$request->institucion_id'
        AND  c.asignatura_id = '$request->asignatura_id'
        ");
        return $permisos;
    }

    public function eliminaAsignacionColegio($id)
    {
        $data = ColegioPermisos::find($id);
        $data->delete();
        DB::DELETE("DELETE FROM colegio_recursos_adicionales WHERE colegio_permiso_id = '$id'");
        return $data;
    }
    public function index(Request $request)
    {
        // return csrf_token();
    }
    public function colegioUsuarios(Request $request){
        $usuarios = DB::SELECT("SELECT * FROM usuario u
        WHERE u.institucion_idInstitucion = '$request->institucion_id'
        AND u.estado_idEstado = '1'
        AND u.id_group <> '4' 
        ");
        return $usuarios;
    }
    //guardar usuarios externos
    //api::get>/guardarUsuarioExterno
    public function guardarUsuarioExterno(Request $request){
        $usuario = Usuario::findOrFail($request->idusuario);
        $usuario->recurso_externo = $request->recurso;
        $usuario->save();
        if($usuario){
            return["status" => "1","message" => "Se guardo correctamente"];
        }else{
            return["status" => "1","message" => "No se pudo guardar"];
        }
    }
    //api:get/colegios_series_libros_doc
    public function series_libros_doc($id){
        $series = DB::SELECT("SELECT s.nombre_serie, ls.id_libro_serie, ls.id_serie, ls.idLibro, 
        ls.nombre, ls.version, ls.iniciales
        FROM colegio_permisos au
        INNER JOIN libro l ON au.asignatura_id = l.asignatura_idasignatura 
        INNER JOIN libros_series ls ON ls.idLibro = l.idlibro
        INNER JOIN series s ON s.id_serie = ls.id_serie
        WHERE au.institucion_id = $id 
        GROUP BY s.nombre_serie
        ");
        return $series;
    }
    //api:get/colegios_ver_areas_serie
    public function ver_areas_serie($id_serie, $institucion){
        $areas = DB::SELECT("SELECT ar.nombrearea AS nombre, ar.idarea AS iniciales 
        FROM libros_series ls
        INNER JOIN libro l ON ls.idLibro = l.idlibro
        INNER JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
        INNER JOIN area ar ON a.area_idarea = ar.idarea
        INNER JOIN colegio_permisos au ON a.idasignatura = au.asignatura_id
        WHERE ls.id_serie = $id_serie 
        AND au.institucion_id = $institucion
        GROUP BY ar.idarea");
        return $areas;
    }
    //api:get/colegios_get_libros_area
    public function get_libros_area($institucion, $area, $serie){
        $series = DB::SELECT("SELECT l.*,a.* FROM libro l
        INNER JOIN asignatura a ON l.asignatura_idasignatura = a.idasignatura
        INNER JOIN colegio_permisos au ON a.idasignatura = au.asignatura_id
        INNER JOIN libros_series ls ON l.idlibro = ls.idLibro
        WHERE a.area_idarea = $area
        AND au.institucion_id = $institucion
        AND ls.id_serie = $serie
        ORDER BY a.nivel_idnivel;");
        return $series;
    }
     //api:get/colegios_get_libros_serie
    public function get_libros_serie($institucion, $serie){
        $series = DB::SELECT("SELECT l.*,a.*
        FROM libro l 
        INNER JOIN libros_series ls ON l.idLibro = ls.idLibro
        INNER JOIN asignatura a ON a.idasignatura = l.asignatura_idasignatura
        INNER JOIN colegio_permisos au ON a.idasignatura = au.asignatura_id
        WHERE au.institucion_id = $institucion
        AND ls.id_serie = $serie
        ORDER BY a.nivel_idnivel
        ");

        return $series;
    }
    public function ingreso(Request $request){
        // return csrf_token();
        $input = $request->all();
        $this->validate($request,[ 
           "email" => 'required|string',
        ]);
        $correo = filter_var($request->email, FILTER_VALIDATE_EMAIL);
        if(!$correo){
            return response()->json([
                'status' => 'invalid_credentials',
                'message' => 'Correo no coincide con los permisos de la institución.',
            ], 401);
        }
         $user = DB::table('usuario')
             ->where('email', '=', $correo)
             ->where('recurso_externo', '=', '1')
             ->take(1)
             ->get();
        //   $user  = User::where('email', $correo)
        //      ->take(1)
        //      ->get();
        if(count($user)<=0){
            return response()->json([
                 'status' => 'invalid_credentials',
                 'message' => 'Correo no coincide con los permisos de la institución.',
             ], 401);
        }
    //   return $user;
        foreach($user as $key => $item){
            $token  = csrf_token();
        $data =[
            "idusuario" => $item->idusuario,
            "cedula"=>  $item->cedula,
            "nombres"=>  $item->nombres,
            "apellidos"=>  $item->apellidos,
            "name_usuario"=>  $item->name_usuario,
            "email"=>  $item->email,
            "id_group" => "16",
            "p_ingreso"=> 0,
            "institucion_idInstitucion" =>$item->institucion_idInstitucion,
            "estado_idEstado" => 1,
            "idcreadorusuario" => 5103,
            "password_status" =>$item->password_status,
            "session_id" => $item->session_id,
            "foto_user" => $item->foto_user,
            "telefono" => $item->telefono,
            "updated_at" => $item->updated_at,
            "created_at" => $item->created_at,
            "token" => $token
        ];
        }
        return $data;
    }  
}
