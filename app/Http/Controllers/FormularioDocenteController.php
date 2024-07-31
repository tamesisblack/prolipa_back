<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DocenteFormularioAsignaturas;
use App\Models\FormularioDocente;
use App\Models\FormularioDocenteLibros;
use App\Models\FormularioDocenteLink;
use App\Models\User;
use DB;
use Illuminate\Http\Request;

class FormularioDocenteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->validar){
            //trear el periodo actual de la institucion
            $buscarPeriodo = $this->traerPeriodo($request->institucion_id);
            if($buscarPeriodo["status"] == "1"){
                $periodo = $buscarPeriodo["periodo"][0]->periodo;
                $periodoDescripcion = $buscarPeriodo["periodo"][0]->descripcion;
            }else{
                return ["status" => "0","message" => "La institucion no tiene periodo"];
            }
            $fecha = date("Y-m-d");
            //validar que el formulario es
            $validate = DB::SELECT("SELECT  * FROM docentes_formulario_links l
            WHERE l.institucion_id = '$request->institucion_id'
            AND l.id = '$request->id'
            AND l.periodo_id = '$periodo'
            AND l.fecha_expiracion >= '$fecha'
            AND l.estado = '1'
            ");
            if(empty($validate)){
                return ["status" => "0","message" => "ESTE FORMULARIO DE INSCRIPCIÓN NO ESTA DISPONIBLE"];
            }else{
                //traer nombre Institucion
                $getInstitucion = DB::SELECT("SELECT i.nombreInstitucion FROM institucion i
                WHERE i.idInstitucion = '$request->institucion_id'
                ");
                return ["periodoDescripcion" => $periodoDescripcion,"nombreInstitucion" => $getInstitucion,"periodo_id" => $periodo];
            }
        }
       //traer todos los formularios
       if($request->all){
        return $this->getFormsAll();
       }
       //parar traer las solicitudes para el administrador
       if($request->solicitudes){
        return $this->solicitudes();
       }
       //para traer las solicitudes de cada maestro
       if($request->maestroSolicitudes){
        return $this->getSolicitudProfesor($request->id);
       }
       //Para traer los libros del maestro
       if($request->getMaestroLibros){
        return $this->getBookMaestro($request->id);
       }
       //para actualizar el libro
       if($request->actualizarLibro){
        return $this->updateLibro($request->id,$request->solicitud_id);
       }
       //para que el maestro cancele el libro
       if($request->cancelarLibro){
        return $this->CancelarLibro($request->id);
       }
       //para eliminar el libro que genero el asesor para el docente
       if($request->cancelarGeneracionBook){
        return $this->cancelarGeneracionBook($request->id);
       }
       //para listar los libros aprobados del docente
       if($request->getLibrosAprobados){
        return $this->getLibrosAprobados($request->idusuario);
       }

    }
    public function traerPeriodo($institucion_id){
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo , periodoescolar AS descripcion FROM periodoescolar WHERE idperiodoescolar = (
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$institucion_id'))
        ");
        if(count($periodoInstitucion)>0){
            return ["status" => "1", "message"=>"correcto","periodo" => $periodoInstitucion];
        }else{
            return ["status" => "0", "message"=>"no hay periodo"];
        }
    }
    public function getFormsAll(){
        $forms = DB::SELECT("SELECT  * FROM docentes_formulario_links l WHERE estado = '1'");
        $solicitudes = DB::SELECT("SELECT  * FROM docentes_formulario l WHERE estado = '1'");
        return ["forms" => $forms,"solicitudes" =>$solicitudes];
    }
    public function solicitudes(){
        $solicitudes = DB::SELECT("SELECT  l.*,
            i.nombreInstitucion,p.periodoescolar AS periodo,
            (SELECT  COUNT(id) FROM docentes_formulario f WHERE f.estado = '1'
            AND f.formulario_id = l.id) AS cantidad,
            CONCAT(u.nombres,' ',u.apellidos) AS editor
            FROM docentes_formulario_links l
            LEFT JOIN institucion i ON l.institucion_id = i.idInstitucion
            LEFT JOIN periodoescolar p ON l.periodo_id = p.idperiodoescolar
            LEFT JOIN usuario u ON l.user_created = u.idusuario
            ORDER BY l.id DESC
        ");
        return $solicitudes;
    }
    public function getBookMaestro($id){
        $getBooks = DB::SELECT("SELECT l.*, li.nombrelibro,li.asignatura_idasignatura 
        FROM docentes_formulario_libros l
        LEFT JOIN libro li ON l.libro_id = li.idlibro
        WHERE l.docente_formulario_id = '$id'
        ORDER BY id DESC
        ");
        return $getBooks;
    }
    public function getSolicitudProfesor($id){
        $maestroSolicitudes = DB::SELECT("SELECT  l.* ,
            i.nombreInstitucion,p.periodoescolar AS periodo,
        CONCAT(u.nombres,' ',u.apellidos) AS profesor
        FROM docentes_formulario l 
        LEFT JOIN institucion i ON l.institucion_id = i.idInstitucion
        LEFT JOIN periodoescolar p ON l.periodo_id = p.idperiodoescolar
        LEFT JOIN usuario u ON l.idusuario = u.idusuario
        WHERE l.formulario_id = '$id'
        ORDER BY l.updated_at DESC
      ");
      return $maestroSolicitudes;
    }
    //actualizar el libro solicitado
    public function updateLibro($id,$solicitud){
        $libro = FormularioDocenteLibros::findOrFail($id);
        $libro->estado = "1";
        $libro->save();
        //quitar la notificacion 
        $solicitud = FormularioDocente::findOrFail($solicitud);
        $solicitud->estado = "0";
        $solicitud->save();
    }
    //Para cancelar el libro 
    public function CancelarLibro($id){
        FormularioDocenteLibros::findOrFail($id)->delete(); 
        return "se elimino correctamente";
    }
    public function cancelarGeneracionBook($id){
        DocenteFormularioAsignaturas::findOrFail($id)->delete(); 
        return "se elimino correctamente";
    }
    public function getLibrosAprobados($idusuario){
        $aprobados = DB::SELECT("SELECT a.*,asig.nombreasignatura
        FROM asignaturausuario a
        LEFT JOIN asignatura asig ON a.asignatura_idasignatura = asig.idasignatura
        WHERE a.usuario_idusuario = '$idusuario'
        ORDER BY asig.nombreasignatura ASC
        ");
       return $aprobados;
    }
    public function verInfoCedula(Request $request){
        //validar que existe el usuario
        $validate = DB::SELECT("SELECT u.idusuario,u.nombres,u.apellidos,
         u.cedula,u.telefono,u.email,u.id_group,u.institucion_idInstitucion
         FROM usuario u
         WHERE u.cedula = '$request->cedula'
        ");
        if(empty($validate)){
            return 0;
        }else{
            //id del docente
            $idusuario          = $validate[0]->idusuario;
            //validar que el usuario sea un docente
            $ifDocente          = $validate[0]->id_group;
            //validar si la institucion es donde se encuentra el usuario
            $ifBelongsInstitute = $validate[0]->institucion_idInstitucion;
            if($ifDocente != 6)                                 return ["status" => "3","message" => "Su cédula no pertenece a un usuario Docente, comuniquese con soporte por favor"];
            if($ifBelongsInstitute != $request->institucion_id) return ["status" => "3","message" => "Su cédula No pertenece a esta Institución, comuniquese con soporte por favor"];
            //===si el docente tiene libros traer====
            return  $this->traerLibrosDocente($request->institucion_id,$idusuario,$request->periodo_id,$validate);
        }
    }

    public function traerLibrosDocente($institucion,$idusuario,$periodo,$validate){
        $validateIfHaveBooks = DB::SELECT("SELECT * FROM docentes_formulario f
        WHERE f.institucion_id = '$institucion'
        AND f.periodo_id = '$periodo'
        AND f.idusuario = '$idusuario'
        ORDER By f.id DESC
        ");
        if(empty($validateIfHaveBooks)){
            //si no hay libros regresamos solo los datos
            return ["status" => "1","info" => $validate];
        }else{
            $id = $validateIfHaveBooks[0]->id;
            $getBooks = DB::SELECT("SELECT l.*, li.nombrelibro
                FROM docentes_formulario_libros l
                LEFT JOIN libro li ON l.libro_id = li.idlibro
                WHERE l.docente_formulario_id = '$id'
            ");
            return ["status" => "1","info" => $validate,"libros" => $getBooks,"formularioDocente" => $validateIfHaveBooks];
        }
    }
    public function getBooksGenerados(Request $request)
    {
        $getBooks = DB::SELECT("SELECT l.*, li.nombrelibro
        FROM docente_formulario_asignaturas l
        LEFT JOIN libro li ON l.libro_id = li.idlibro
        WHERE l.formulario_link_id = '$request->formulario_link_id'
        ");
        return $getBooks;
    }
    //api:post/GenerarFormulario
    public function GenerarFormulario(Request $request){
        if($request->id > 0){
            $link = FormularioDocenteLink::findOrFail($request->id);
        }else{
            $link = new FormularioDocenteLink();
            $link->institucion_id   = $request->institucion_id;
            $link->periodo_id       = $request->periodo_id;
        }
        $link->user_created     = $request->user_created;
        $link->observacion      = $request->observacion;
        $link->fecha_expiracion = $request->fecha_expiracion;
        $link->estado           = $request->estado;
        $link->save();
        //generate link
        $this->generateLink($link,$request->id,$request->link);
        if($link){
            return ["status" => "1", "message" => "Se guardo correctamente","datos" => $link];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
        }
    }
    public function generateLink($dato,$id,$link){
        if($id == 0){
            $generar = FormularioDocenteLink::findOrFail($dato->id);
            $generar->link =  $link.$dato->id;
            $generar->save();
        }
    }
    //metodo para que asesor genere libros para los docentes
    public function asesorGeneraBook(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //variables
        $manantial          = json_decode($request->manantial);
        $ruta               = json_decode($request->ruta);
        $complementarias    = json_decode($request->complementarias);
        // //edit generate asignaturas
        // if($request->id > 0){
        //     $formulario = DocenteFormularioAsignaturas::findOrFail($request->id);
        // }else{
        //     //guardar generate asignaturas
        //     $formulario = new DocenteFormularioAsignaturas();
        // }
        //====GUARDAR MANANTIAL=======
        if($manantial!=""){
            foreach($manantial as $key => $item1){
                $lmanantial                             =   new DocenteFormularioAsignaturas;
                $lmanantial->formulario_link_id         =   $request->formulario_link_id;
                $lmanantial->asignatura_id              =   $item1->idasignatura;
                $lmanantial->libro_id                   =   $item1->idLibro;
                $lmanantial->save();
            }
        }
        if($ruta!=""){
        //====GUARDAR RUTA=======
            foreach($ruta as $key => $item2){
                $lruta                                  =   new DocenteFormularioAsignaturas;
                $lruta->formulario_link_id              =   $request->formulario_link_id;;
                $lruta->asignatura_id                   =   $item2->idasignatura;
                $lruta->libro_id                        =   $item2->idLibro;
                $lruta->save();
            }
        }
        //====GUARDAR AREAS COMPLEMENTARIAS=======
        if($complementarias!=""){
            foreach($complementarias as $key => $item3){
                $lcomplementaria                        =   new DocenteFormularioAsignaturas;
                $lcomplementaria->formulario_link_id    =   $request->formulario_link_id;;
                $lcomplementaria->asignatura_id         =   $item3->idasignatura;
                $lcomplementaria->libro_id              =   $item3->idLibro;
                $lcomplementaria->save();
            }
        }
        return ["status" => "0","message" =>"se guardo correctamente"];
    }

   //metodo para guardar el formulario del docente
    public function store(Request $request)
    {
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //variables
        $manantial          = json_decode($request->manantial);
        $ruta               = json_decode($request->ruta);
        $complementarias    = json_decode($request->complementarias);
        //buscar el periodo actual de la institucion
        $buscarPeriodo = $this->traerPeriodo($request->institucion_id);
        if($buscarPeriodo["status"] == "1"){
            $periodo = $buscarPeriodo["periodo"][0]->periodo;
        }else{
            return ["status" => "3","message" => "Su institución no tiene periodo"];
        }
        //Si el usuario ya existe
        if($request->nuevoUser == "no"){
            $user = User::findOrFail($request->idmaestro);
        }else{
        //Si no existe el docente se crea
        $user = $this->saveUser($request->cedula,$request->nombres,$request->apellidos,$request->email,$request->telefono,$request->institucion_id);
        }
        //edit formulario
        if($request->id > 0){
            $formulario = FormularioDocente::findOrFail($request->id);
            //validar que el formulario sea del mismo periodo
            $getPeriodoFormulario = $formulario->periodo_id;
            if($getPeriodoFormulario != $request->periodo_id){
                return ["status" => "3","message" => "Este formulario no corresponde al periodo actual por favor solicite un nuevo formulario"];
            }
        }else{
            //guardar formulario
            $formulario = new FormularioDocente();
            $formulario->formulario_id = $request->formulario_id;
        }
        $formulario->idusuario      = $user->idusuario;
        $formulario->institucion_id = $user->institucion_idInstitucion;
        $formulario->periodo_id     = $periodo;
        $formulario->estado         = "1";
        $formulario->save();
        //====GUARDAR MANANTIAL=======
        if($manantial!=""){
            foreach($manantial as $key => $item1){
                $lmanantial                             =   new FormularioDocenteLibros;
                $lmanantial->docente_formulario_id      =   $formulario->id;
                $lmanantial->serie                      =   $item1->nombre_serie;
                $lmanantial->libro_id                   =   $item1->idLibro;
                $lmanantial->save();
            }
        }
        if($ruta!=""){
        //====GUARDAR RUTA=======
            foreach($ruta as $key => $item2){
                $lruta                                  =   new FormularioDocenteLibros;
                $lruta->docente_formulario_id           =   $formulario->id;
                $lruta->serie                           =   $item2->nombre_serie;
                $lruta->libro_id                        =   $item2->idLibro;
                $lruta->save();
            }
        }
        //====GUARDAR AREAS COMPLEMENTARIAS=======
        if($complementarias!=""){
            foreach($complementarias as $key => $item3){
                $lcomplementaria                        =   new FormularioDocenteLibros;
                $lcomplementaria->docente_formulario_id =   $formulario->id;
                $lcomplementaria->serie                 =   $item3->nombre_serie;
                $lcomplementaria->libro_id              =   $item3->idLibro;
                $lcomplementaria->save();
            }
        }
        return ["status" => "0","message" =>"se guardo correctamente","datos" =>$formulario];
    }
    public function saveUser($cedula,$nombres,$apellidos,$email,$telefono,$institucion){
        $user = new User();
        $user->cedula                       = $cedula;
        $user->nombres                      = $nombres;
        $user->apellidos                    = $apellidos;
        $user->email                        = $email;
        $user->name_usuario                 = $email;
        $user->telefono                     = $telefono;
        $user->id_group                     = "6";
        $user->estado_idEstado              = "1";
        $user->institucion_idInstitucion    = $institucion;
        $user->password                     = sha1(md5($cedula));
        $user->save();
        return $user;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //validar que no tenga solicitudes de los docentes
        $validate = DB::SELECT("SELECT * FROM docentes_formulario
        WHERE formulario_id = '$id'
        ");
        if(!empty($validate)){
            return ["status" => "0","message" => "No se puede eliminar el formulario por que existe solicitudes de docentes"];
        }else{
            DB::DELETE("DELETE FROM docentes_formulario_links WHERE id = '$id' ");
        }
    }
}
