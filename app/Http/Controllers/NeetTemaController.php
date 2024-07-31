<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Models\Neet\NeetCursosHasMaterial;
use App\Models\Models\Neet\NeetTareas;
use App\Models\NeetSubTema;
use App\Models\NeetTema;
use App\Models\NeetUpload;
use App\Models\NeetUsuarioDocumento;
use App\Traits\Codigos\TraitCodigosGeneral;
use App\Traits\Usuario\TraitUsuarioGeneral;
use Illuminate\Http\Request;
use App\Repositories\NeetRepository;
use DB;
class NeetTemaController extends Controller
{
    use TraitCodigosGeneral;
    use TraitUsuarioGeneral;
    private $neetRepository;
    public function __construct(NeetRepository $neetRepository)
    {
     $this->neetRepository = $neetRepository;
    }
    //api:get>>getUsuarios/{rol}/{institucion}
    public function getUsuarios($rol,$institucion){
        $query = $this->userxRolxInstitucion($rol,$institucion);
        return $query;
    }
    //api:get/neetTema?listadoTemas=yes
    public function index(Request $request)
    {
        //temas
        if($request->listadoTemas){
            return $this->listadoTemas();
        }
        //Documentos
        if($request->listadoDocumentos){
            return $this->listadoDocumentos();
        }
        //Subniveles
        if($request->listadoSubniveles){
            return $this->listadoSubniveles();
        }
        //asignados x usuario
        if($request->getAsignados){
            return $this->getAsignados($request);
        }
        //todos asignados incluyendo los documentos generales
        if($request->getAsignadosAll){
            return $this->getAsignadosAll($request);
        }
        //TRAER ASIGNACIONES POR DOCUMENTOS DE DOCENTES
         if($request->getDocentesXDocumento){
            return $this->getDocentesXDocumento($request->periodo_id,$request->neet_upload_id,$request->institucion_id);
        }
        //TRAER TODOS LOS CURSOS DE UN DOCENTE
        if($request->AllCursosXDocente){
            return $this->AllCursosXDocente($request->idusuario,$request->periodo_id);
        }
        //TRAER LOS  CURSOS ASIGNADOS LOS DOCUMENTOS QUE HIZO DOCENTE
        if($request->getCursosAsignados){
            return $this->getCursosAsignados($request->idusuario,$request->periodo_id,$request->neet_upload_id);
        }
        /***********************METODOS DE TAREAS****************************** */
        if($request->tareaNeetEstudiante){
            return $this->tareaNeetEstudiante($request);
        }
        //LISTADO DE RESPUESTAS DE LAS TAREAS
        if($request->listadoRespuestas){
            return $this->listadoRespuestas($request);
        }
        //INFORMACION DE LA TAREA
        if($request->getInfoTarea){
            $data = explode('*',$request->datos);
            $estudiante_id  = $data[0];
            $id             = $data[1];
            return $this->getInfoTarea($estudiante_id,$id);
        }
        //lista de estudiantes del curso
        if($request->listadoEstudiantesXCurso){
            return $this->listadoEstudiantesXCurso($request);
        }
    }
    public function listadoTemas(){
        $query = $this->neetRepository->allDesc(2);
        return $query;
    }
    public function listadoDocumentos(){
        $query = DB::SELECT("SELECT nu.*,nu.nombre as nombre_documento, nu.descripcion as desc_documento
        , t.nombre AS tema,s.nombre as subnivel
        FROM neet_upload nu
        LEFT JOIN neet_temas t ON nu.tema_id = t.id
        LEFT JOIN neet_subnivel s ON nu.nee_subnivel = s.id
        ORDER BY nu.id DESC
        ");
        if(empty($query)){
            return $query;
        }
        $datos = [];
        foreach($query as $key => $item){
            $files = $this->getFilesUpload($item->id);
            $datos[$key] = [
                "id"                => $item->id,
                "nombre_documento"  => $item->nombre_documento,
                "desc_documento"    => $item->desc_documento,
                "nombre"            => $item->nombre,
                "descripcion"       => $item->descripcion,
                "estado"            => $item->estado,
                "tema"              => $item->tema,
                "tema_id"           => $item->tema_id,
                "user_created"      => $item->user_created,
                "created_at"        => $item->created_at,
                "updated_at"        => $item->updated_at,
                "nee_subnivel"      => $item->nee_subnivel,
                "tipo"              => $item->tipo,
                "subnivel"          => $item->subnivel,
                "actividad1"        => $item->actividad1,
                "actividad2"        => $item->actividad2,
                "actividad3"        => $item->actividad3,
                "actividad4"        => $item->actividad4,
                "actividad5"        => $item->actividad5,
                "solucionario"      => $item->solucionario,
                "files"             => $files
            ];
        }
        return $datos;
    }
    public function getFilesUpload($id){
        $files = DB::SELECT("SELECT * FROM neet_upload_files WHERE neet_upload_id = '$id'");
        return $files;
    }
    public function listadoSubniveles(){
        $query = DB::SELECT("SELECT * FROM neet_subnivel");
        return $query;
    }
    public function getAsignados($request){
        $buscarPeriodo = $this->PeriodoInstitucion($request->institucion_id);
        if(empty($buscarPeriodo)) return ["status" => "0", "message" => "No se encontro un período para la institución"];
        $periodo = $buscarPeriodo[0]->periodo;
        $query   = $this->ifAsignadoUsuario($request->idusuario,$periodo);
        return $query;
    }
    //CONSULTAR SI USUARIO TIENE ASIGNADO
    public function ifAsignadoUsuario($idusuario,$periodo){
        $query = DB::SELECT("SELECT nd.*, nu.nombre AS documento, pe.periodoescolar AS periodo,
        sn.nombre AS subnivel,t.nombre as tema,nu.nee_subnivel
        FROM neet_usuario_documento nd
        LEFT JOIN neet_upload  nu ON nd.neet_upload_id = nu.id
        LEFT JOIN periodoescolar pe ON nd.periodo_id = pe.idperiodoescolar
        LEFT JOIN neet_subnivel sn ON sn.id =  nu.nee_subnivel
        LEFT JOIN neet_temas t ON nu.tema_id = t.id
        WHERE nd.idusuario      = '$idusuario'
        AND nd.periodo_id       = '$periodo'
        ORDER BY nd.id DESC;
        ");
        return $query;
    }
    //CONSULTAR SI EL CURSO ESTA ASIGNADO UNA NECESIDAD EDUCATIVA
    //API:GET/ifAsignadoCurso/codigo
    public function ifAsignadoCurso($codigo){
        $query = DB::SELECT("SELECT * FROM neet_cursos_has_material m
        WHERE m.codigo_curso = '$codigo'
        AND m.estado = '1'
        ");
        return $query;
    }
    //INCLUYE LOS DOCUMENTOS GENERALES
    public function getAsignadosAll($request){
        $array_resultante = [];
        $buscarPeriodo = $this->PeriodoInstitucion($request->institucion_id);
        if(empty($buscarPeriodo)) return ["status" => "0", "message" => "No se encontro un período para la institución"];
        $periodo = $buscarPeriodo[0]->periodo;
        $query = DB::SELECT("SELECT nd.*, nu.nombre AS documento,
        nu.nombre as nombre_documento, nu.descripcion as desc_documento,nu.estado,
        pe.periodoescolar AS periodo,
        sn.nombre AS subnivel,t.nombre as tema,nu.nee_subnivel,
        nu.actividad1, nu.actividad2 , nu.actividad3, nu.actividad4, nu.actividad5,nu.solucionario
        FROM neet_usuario_documento nd
        LEFT JOIN neet_upload  nu ON nd.neet_upload_id = nu.id
        LEFT JOIN periodoescolar pe ON nd.periodo_id = pe.idperiodoescolar
        LEFT JOIN neet_subnivel sn ON sn.id =  nu.nee_subnivel
        LEFT JOIN neet_temas t ON nu.tema_id = t.id
        WHERE nd.idusuario      = '$request->idusuario'
        AND nd.periodo_id       = '$periodo'
        AND nu.estado           = '1'
        ORDER BY nd.id DESC;
        ");
        $datos  = [];
        $datos2 = [];
        foreach($query as $key => $item){
            $files              = $this->getFilesUpload($item->neet_upload_id);
            $docentesAsignados  = $this->getDocentesXDocumento($periodo,$item->neet_upload_id,$request->institucion_id);
            $cursosAsignados    = $this->getCursosAsignados($request->idusuario,$periodo,$item->neet_upload_id);
            $datos[$key] = [
                "id"                => $item->id,
                "idusuario"         => $item->idusuario,
                "user_created"      => $item->user_created,
                "neet_upload_id"    => $item->neet_upload_id,
                "nee_subnivel"      => $item->nee_subnivel,
                "periodo_id"        => $item->periodo_id,
                "created_at"        => $item->created_at,
                "documento"         => $item->documento,
                "nombre_documento"  => $item->nombre_documento,
                "desc_documento"    => $item->desc_documento,
                "periodo"           => $item->periodo,
                "subnivel"          => $item->subnivel,
                "tema"              => $item->tema,
                "estado"            => $item->estado,
                "actividad1"        => $item->actividad1,
                "actividad2"        => $item->actividad2,
                "actividad3"        => $item->actividad3,
                "actividad4"        => $item->actividad4,
                "actividad5"        => $item->actividad5,
                "solucionario"      => $item->solucionario,
                "files"             => $files,
                "docentesAsignados" => count($docentesAsignados),
                "cursosAsignados"   => $cursosAsignados
            ];
        }
        //archivos generales
        $query2 = DB::SELECT("SELECT nu.id, nu.nombre AS documento,
        sn.nombre AS subnivel,t.nombre as tema,nu.nee_subnivel
        FROM neet_upload nu
        LEFT JOIN neet_subnivel sn ON sn.id =  nu.nee_subnivel
        LEFT JOIN neet_temas t ON nu.tema_id = t.id
        WHERE nu.nee_subnivel = '5'
        AND nu.estado = '1'
        ");
        foreach($query2 as $key => $item){
            $files = $this->getFilesUpload($item->id);
            $datos2[$key] = [
                "neet_upload_id"    => $item->id,
                "nee_subnivel"      => $item->nee_subnivel,
                "documento"         => $item->documento,
                "subnivel"          => $item->subnivel,
                "tema"              => $item->tema,
                "files"             => $files
            ];
        }
        $array_resultante= array_merge($datos,$datos2);
        return $array_resultante;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //neetTema
    public function store(Request $request)
    {
        //AGREGAR TEMAS
        if($request->save_temas){
            return $this->save_temas($request);
        }
        //ASIGNAR DOCUMENTO
        if($request->asignarDocumento){
            return $this->asignarDocumento($request);
        }
        //ASIGNAR DOCUMENTOS DOCENTES
        if($request->asignarDocentes){
            return $this->asignarDocentes($request);
        }
        //GUARDAR ACTIVIDADES
        if($request->guardarActividadesNeet){
            return $this->guardarActividadesNeet($request);
        }
        //GUADAR ASIGNACION A CURSOS
        if($request->save_AsignarDocumentoCurso){
            return $this->save_AsignarDocumentoCurso($request);
        }
        /***METODOS POST TAREAS *********/
        if($request->calificarTarea){
            return $this->calificarTarea($request);
        }
        //METODOS ACTUALIZAR INFORMACION TAREA
        if($request->saveGuardaTarea){
            return $this->saveGuardaTarea($request);
        }
    }

    public function save_temas($request){
        if($request->id > 0){
            $tema               = NeetTema::findOrFail($request->id);
        }else{
            $tema               = new NeetTema();
        }
        $tema->nombre           = $request->nombre;
        $tema->estado           = $request->estado;
        $tema->user_created     = $request->user_created;
        $tema->save();
        if($tema){
            return ["status" => "1","message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0","message" => "No se pudo guardar"];
        }
    }
    public function asignarDocumento($request){
        $buscarPeriodo = $this->PeriodoInstitucion($request->institucion_id);
        if(empty($buscarPeriodo)) return ["status" => "0", "message" => "No se encontro un período para la institución"];
        $periodo = $buscarPeriodo[0]->periodo;
        $query = $this->getAsignadoXDocente($request->idusuario,$periodo,$request->neet_upload_id);
        //validar que si existe no se crea
        if(count($query) > 0) return ["status" => "0", "message" => "El documento ya ha sido asignado anteriormente"];
        //PROCESO
        $documento = $this->saveAsignacionDocente($request,$periodo,$request->idusuario);
        if($documento){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
        }
    }
    public function getAsignadoXDocente($idusuario,$periodo,$neet_upload_id){
        $query = DB::SELECT("SELECT nd.*
        FROM neet_usuario_documento nd
        WHERE nd.idusuario      = '$idusuario'
        AND nd.periodo_id       = '$periodo'
        AND nd.neet_upload_id   = '$neet_upload_id'
        ");
        return $query;
    }
    public function saveAsignacionDocente($request,$periodo,$idusuario){
        $documento = new NeetUsuarioDocumento();
        $documento->idusuario       = $idusuario;
        $documento->user_created    = $request->user_created;
        $documento->neet_upload_id  = $request->neet_upload_id;
        $documento->periodo_id      = $periodo;
        $documento->id_group        = $request->id_group;
        $documento->save();
        return $documento;
    }
    public function asignarDocentes($request){
        $periodo_id             = $request->periodo_id;
        $neet_upload_id         = $request->neet_upload_id;
        $institucion_id         = $request->institucion_id;
        $datos                  = json_decode($request->docentes);
        //eliminar si ya han quitado al docente
        //traer los documentos asignados a los docentes
        $getDocentesAsignados   = $this->getDocentesXDocumento($periodo_id,$neet_upload_id,$institucion_id);
        if(sizeOf($getDocentesAsignados) > 0){
            foreach($getDocentesAsignados as $key => $item){
                $docente        = "";
                $docente        = $item->idusuario;
                $searchDocente  = collect($datos)->filter(function ($objeto) use ($docente) {
                    // Condición de filtro
                    return $objeto->idusuario == $docente;
                });
                if(sizeOf($searchDocente) == 0){
                    DB::DELETE("DELETE FROM neet_usuario_documento
                      WHERE neet_upload_id  = '$neet_upload_id'
                      AND idusuario         = '$docente'
                      AND periodo_id        = '$periodo_id'
                    ");
                }
            }
        }
        //guardar las asignaciones para los docentes
        foreach($datos as $key => $item){
            $query = $this->getAsignadoXDocente($item->idusuario,$periodo_id,$request->neet_upload_id);
            if(empty($query)){
                //guardar
                $this->saveAsignacionDocente($request,$periodo_id,$item->idusuario);
            }
        }
    }
    public function getDocentesXDocumento($periodo_id,$neet_upload_id,$institucion_id){
        $query = DB::SELECT("SELECT  CONCAT(u.nombres,' ',u.apellidos) as nombre_completo,
        u.id_group,u.cedula,u.idusuario,u.institucion_idInstitucion
        FROM neet_usuario_documento d
        LEFT JOIN usuario u ON d.idusuario  = u.idusuario
        WHERE d.id_group                    = '6'
        AND d.periodo_id                    = '$periodo_id'
        AND d.neet_upload_id                = '$neet_upload_id'
        AND u.institucion_idInstitucion     = '$institucion_id'
        ");
        return $query;
    }
    public function guardarActividadesNeet($request){
        $actividad1 = $request->actividad1;
        $actividad2 = $request->actividad2;
        $actividad3 = $request->actividad3;
        $actividad4 = $request->actividad4;
        $actividad5 = $request->actividad5;
        $dato = NeetUpload::find($request->id);
        $dato->actividad1      = $actividad1 == null || $actividad1 == "null" ? null :$actividad1;
        $dato->actividad2      = $actividad2 == null || $actividad1 == "null" ? null :$actividad2;
        $dato->actividad3      = $actividad3 == null || $actividad1 == "null" ? null :$actividad3;
        $dato->actividad4      = $actividad4 == null || $actividad1 == "null" ? null :$actividad4;
        $dato->actividad5      = $actividad5 == null || $actividad1 == "null" ? null :$actividad5;
        $dato->user_created    = $request->user_created;
        $dato->save();
        if($dato){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
        }
    }
    public function save_AsignarDocumentoCurso($request){
        $codigo         = $request->codigo_curso;
        $neet_upload_id = $request->neet_upload_id;
        $cursos= DB::SELECT("SELECT * FROM neet_cursos_has_material cm
        WHERE cm.codigo_curso = '$codigo'
        AND cm.neet_upload_id = '$neet_upload_id'
        AND cm.estado         = '1'
        ");
        if( empty($cursos) ){
            return $this->saveGuardaTarea($request);
        }else{
            return ["status" => "0", "message" => "Este material ya se encuentra asignado a este curso"];
        }
    }
    public function saveGuardaTarea($request){
        if($request->id > 0){
            $asignacion = NeetCursosHasMaterial::findOrFail($request->id);
        }else{
            $asignacion = new NeetCursosHasMaterial();
        }
            $asignacion->codigo_curso       = $request->codigo_curso;
            $asignacion->neet_upload_id     = $request->neet_upload_id;
            $asignacion->user_created       = $request->idusuario;
            $asignacion->periodo_id         = $request->periodo_id;
            $asignacion->fecha_inicio       = $request->fecha_inicio;
            $asignacion->fecha_fin          = $request->fecha_fin;
            $asignacion->estado             = $request->estado;
            $asignacion->observacion        = $request->observacion == null ? null : $request->observacion;
            $asignacion->save();
            if($asignacion){
                return ["status" => "1", "message" => "Asignado correctamente"];
            }else{
                return ["status" => "0", "message" => "No se pudo guardar"];
            }
    }
    public function AllCursosXDocente($idusuario,$periodo){
        $query = DB::SELECT("SELECT c.*, a.nombreasignatura
            FROM curso c
            LEFT JOIN asignatura a ON c.id_asignatura = a.idasignatura
            WHERE c.idusuario       = '$idusuario'
            AND c.id_periodo        = '$periodo'
            AND c.estado = '1'
        ");
        return $query;
    }
    public function getCursosAsignados($idusuario,$periodo_id,$neet_upload_id){
        $query = DB::SELECT("SELECT m.*,c.nombre,c.seccion,c.aula,c.id_asignatura,a.nombreasignatura
        FROM neet_cursos_has_material m
        LEFT JOIN curso c ON c.codigo = m.codigo_curso
        LEFT JOIN asignatura a ON c.id_asignatura = a.idasignatura
        WHERE m.user_created    = '$idusuario'
        AND m.periodo_id        = '$periodo_id'
        AND m.neet_upload_id    = '$neet_upload_id'
        AND m.estado            = '1'
        ORDER BY m.id DESC
        ");
        return $query;
    }
    public function neetEliminar(Request $request){
        //ELIMINAR TEMA
        if($request->eliminar_tema){
            return $this->eliminar_tema($request);
        }
        //ELIMINAR ASIGNACION CURSO
        if($request->eliminarAsignacionCurso){
            return $this->eliminarAsignacionCurso($request);
        }
    }
    public function eliminar_tema($request){
        //validar que no tenga hijos
        $query = DB::SELECT("SELECT * FROM neet_upload nu
        WHERE nu.tema_id = '$request->id'
        ");
        if(count($query) > 0){
            return ["status" => "0", "message" => "No se puede eliminar el tema por que hay documentos asociados al tema"];
        }
        $tema = NeetTema::findOrFail($request->id)->delete();
    }
    public function eliminarAsignacionCurso($request){
        $asignacion = NeetCursosHasMaterial::findOrFail($request->id);
        $asignacion->estado  ='0';
        $asignacion->save();
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //api:get/neetTema/{id}
    //subtemas asignados
    public function show($id)
    {
        $files = DB::SELECT("SELECT * FROM neet_upload_files WHERE neet_upload_id = '$id'");
        $asignaciones = DB::SELECT("SELECT * FROM neet_usuario_documento nud
        WHERE nud.neet_upload_id = '$id'
        ");
        return ["files" => $files,"asignaciones"=>$asignaciones];
    }
    public function getAsignaciones(){

    }
    //API:GET/eliminaAsignacionNeet/id
    public function eliminaAsignacionNeet($id){
        $data = NeetUsuarioDocumento::find($id);
        $data->delete();
        return $data;
    }
    //API:POST/quitarTodasDocumentosAsignados
    public function quitarTodasDocumentosAsignados(Request $request)
    {
        $ids = explode(",",$request->ids);
        $data = NeetUsuarioDocumento::destroy($ids);
        return $data;
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    /**********************METODOS DE TAREAS PARA NEET************************** */
    //api:get/neetTema?tareaNeetEstudiante=yes&codigoCurso=abc&idusuario=123
    public function tareaNeetEstudiante($request){
        $query = DB::SELECT("SELECT
        m.*,
        nu.nombre as nombre_documento, nu.descripcion as desc_documento,nu.estado,
        nu.actividad1, nu.actividad2 , nu.actividad3, nu.actividad4, nu.actividad5,nu.solucionario,
        sn.nombre AS subnivel
        FROM neet_cursos_has_material m
        LEFT JOIN neet_upload  nu ON m.neet_upload_id = nu.id
        LEFT JOIN neet_subnivel sn ON sn.id =  nu.nee_subnivel
        WHERE m.codigo_curso = '$request->codigoCurso'
        AND m.estado = '1'
        ");
        if(empty($query)){return $query;}
        else{
            $datos = [];
            foreach($query as $key => $item){
                $query2 = [];
                $query3 = [];
                $query4 = [];
                //profesor para contar las tareas enviadas
                if($request->id_group == 6){
                    $query4 = $this->getInfoAllTarea($item->id);
                }else{
                    //estudiante
                    $query3 = $this->getInfoTareaSinFinalizar($request->idusuario,$item->id);
                    $query4 = $this->getInfoTarea($request->idusuario,$item->id);
                    //si existe y esta pendiente veo si ya se puede finalizar
                    if(count($query3) > 0){
                        //finalizar tareas si ya paso el tiempo
                        $this->UpdateFinalizados($query3,$item);
                    }
                    $query2 = $this->getInfoTareaFinalizada($request->idusuario,$item->id);
                }
                $datos[$key] = (Object)[
                    "id"                => $item->id,
                    "codigo_curso"      => $item->codigo_curso,
                    "neet_upload_id"    => $item->neet_upload_id,
                    "user_created"      => $item->user_created,
                    "periodo_id"        => $item->periodo_id,
                    "fecha_inicio"      => $item->fecha_inicio,
                    "fecha_fin"         => $item->fecha_fin,
                    "observacion"       => $item->observacion,
                    "estado"            => $item->estado,
                    "created_at"        => $item->created_at,
                    "updated_at"        => $item->updated_at,
                    "nombre_documento"  => $item->nombre_documento,
                    "desc_documento"    => $item->desc_documento,
                    "subnivel"          => $item->subnivel,
                    "actividad1"        => $item->actividad1,
                    "actividad2"        => $item->actividad2,
                    "actividad3"        => $item->actividad3,
                    "actividad4"        => $item->actividad4,
                    "actividad5"        => $item->actividad5,
                    "estadoTarea"       => count($query2) > 0 ? 1: 0,
                    "tarea"             => $query4,
                ];
            }
           return $datos;
        }
    }
    public function listadoRespuestas($request){
        $query = DB::SELECT("SELECT re.*, CONCAT(u.nombres, ' ',u.apellidos) AS mensajero
        FROM neet_tareas_respuestas re
        LEFT JOIN usuario u ON re.idusuario = u.idusuario
        WHERE re.neet_tareas_id = '$request->tarea_id'
        ORDER BY re.id DESC
        ");
        $datos = [];
        foreach($query as $key => $item){
            $consulta = DB::SELECT("SELECT * FROM neet_tareas_files crf
            WHERE crf.neet_tareas_respuestas_id = '$item->id'
            ");
            $datos[$key] = [
                "id"                                => $item->id,
                "neet_tareas_id"                    => $item->neet_tareas_id,
                "idusuario"                         => $item->idusuario,
                "mensaje"                           => $item->mensaje,
                "created_at"                        => $item->created_at,
                "mensajero"                         => $item->mensajero,
                "files"                             => $consulta,
            ];
        }
        return $datos;
    }
    public function listadoEstudiantesXCurso($request){
        $data           = explode('*',$request->datos);
        $codigoCurso    = $data[0];
        $tareaId        = $data[1];
        $query = DB::SELECT("SELECT e.*,
        CONCAT(u.nombres,' ', u.apellidos) AS estudiante
         FROM estudiante  e
        LEFT JOIN  usuario u ON e.usuario_idusuario = u.idusuario
        WHERE e.codigo = '$codigoCurso'
        ");
        if(empty($query)){
            return $query;
        }
        $datos = [];
        foreach($query as $key => $item){
            $tarea =  $this->getInfoTarea($item->usuario_idusuario,$tareaId);
            $datos[$key] = [
                "id"                    => $item->id,
                "usuario_idusuario"     => $item->updated_at,
                "grupo"                 => $item->grupo,
                "codigo"                => $item->codigo,
                "estado"                => $item->estado,
                "updated_at"            => $item->updated_at,
                "created_at"            => $item->created_at,
                "estudiante"            => $item->estudiante,
                "tarea"                 => $tarea,
            ];
        }
        return $datos;
    }
    public function getInfoTareaFinalizada($estudiante_id,$id){
        $query = DB::SELECT("SELECT * FROM neet_tareas t
        WHERE t.neet_cursos_has_material_id = '$id'
        AND t.estudiante_id                 = '$estudiante_id'
        AND t.estado                        = '1'
        ");
        return $query;
    }
    public function getInfoTareaSinFinalizar($estudiante_id,$id){
        $query = DB::SELECT("SELECT * FROM neet_tareas t
        WHERE t.neet_cursos_has_material_id = '$id'
        AND t.estudiante_id                 = '$estudiante_id'
        AND t.estado                        = '0'
        ");
        return $query;
    }
    public function getInfoTarea($estudiante_id,$id){
        $query = DB::SELECT("SELECT * FROM neet_tareas t
        WHERE t.neet_cursos_has_material_id  = '$id'
        AND t.estudiante_id                 = '$estudiante_id'
        ");
        return $query;
    }
    public function getInfoAllTarea($id){
        $query = DB::SELECT("SELECT * FROM neet_tareas t
        WHERE t.neet_cursos_has_material_id  = '$id'
        ");
        return $query;
    }
    public function UpdateFinalizados($query,$item){
        $fecha =  date("Y-m-d H:i:s");
        $id    =  $query[0]->id;
        if($fecha >= $item->fecha_fin ){
            $change = NeetTareas::findOrFail($id);
            $change->estado = 1;
            $change->save();
        }
    }
    ///===METODOS POST TAREAS NEET======
    public function calificarTarea($request){
        $calificacion = NeetTareas::findOrFail($request->id);
        $calificacion->calificacion = $request->calificacion;
        $calificacion->estado       = 1;
        $calificacion->save();
        if($calificacion){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
        }
    }
}
