<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticoEnlace;
use App\Models\DiagnosticoLibros;
use App\Models\HistoricoCodigos;
use App\Models\DiagnosticoRespuesta;
use Illuminate\Http\Request;
use DB;

class DiagnosticoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->validar){
            return $this->validarEnlace(decrypt($request->institucion_id),decrypt($request->periodo_id));
        }
       if($request->getBooks){
        return $this->getBooks();
       }
       if($request->getPreguntas){
        return $this->getPreguntas($request->libro_id);
       }  
       if($request->getPreguntasOpciones){
        return $this->getPreguntasOpciones($request->libro_id);
       }    
    }
    public function validarEnlace($institucion,$periodo){
        //validar que el enlace este activo/ el periodo activo /
        $validate = DB::SELECT("SELECT  l.*, p.periodoescolar AS periodo,i.nombreInstitucion
        FROM diagnostico_enlace l
        LEFT JOIN periodoescolar p ON l.periodo_id = p.idperiodoescolar
        LEFT JOIN institucion i ON i.idInstitucion = l.institucion_id
        WHERE l.institucion_id = '$institucion'
        AND l.periodo_id = '$periodo'
        AND l.estado = '1'
        AND p.estado = '1'
        LIMIT 1
        ");
        if(empty($validate)){
            return ["status" => "0","message" => "ESTE FORMULARIO DE DIAGNOSTICO NO ESTA DISPONIBLE"];
        }else{
            $periodoDescripcion = $validate[0]->periodo;
            $getInstitucion     = $validate[0]->nombreInstitucion;
            return ["periodoDescripcion" => $periodoDescripcion,"nombreInstitucion" => $getInstitucion,"periodo_id" => $periodo];
        }
    }
    public function getBooks(){
        $books = DB::SELECT("SELECT p.*, l.nombrelibro,
        (
            SELECT COUNT(*) AS contador FROM diagnostico_preguntas_detalle d
            WHERE d.libro_id = l.idlibro
        )AS contador
        FROM diagnostico_preguntas p
        LEFT JOIN libro l ON p.libro_id = l.idlibro
        ORDER BY p.id desc");
        return $books;
    }
    public function getPreguntas($libro_id){
        $opciones = DB::SELECT("SELECT * FROM diagnostico_preguntas_detalle 
        WHERE libro_id = $libro_id
        ORDER BY created_at DESC");
        return $opciones;
    }
    public function getPreguntasOpciones($libro_id){
        $opciones = DB::SELECT("SELECT id,formato,pregunta, opcion, imagen
        FROM diagnostico_preguntas_detalle 
        WHERE libro_id = $libro_id
        ORDER BY created_at DESC
        ");
        $data = [];
        foreach($opciones as $key => $item){
            $data[$key] = [
                "pregunta_id"   => $item->id,
                "formato"       => $item->formato,
                "pregunta"      => $item->pregunta,
                "_id"           => encrypt($item->opcion),
                "imagen"        => $item->imagen
            ];
        }
        return $data;
    }
    public function GenerarEnlaceDiagnostico(Request $request){
        $institucionE   =  encrypt($request->institucion_id);
        $periodoE       =  encrypt($request->periodo_id);
        $enlace = $request->link.$institucionE.'/'.$periodoE;
        //validate que no este ya generado el link para la institucion en el periodo actual
        $validate = DB::SELECT("SELECT * FROM diagnostico_enlace e
        WHERE e.institucion_id = '$request->institucion_id'
        AND e.periodo_id = '$request->periodo_id'
        ");
        if(empty($validate)){
            $link  = new DiagnosticoEnlace();
            $link->institucion_id   = $request->institucion_id;
            $link->periodo_id       = $request->periodo_id;
            $link->link             = $enlace;
            $link->save();
            $dato[0] = $link;
            return $dato;
        }else{
            return $validate;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $codigoLiquidados = [];
        $todate         = date('Y-m-d H:i:s');  
        $institucion    = decrypt($request->institucion_id);
        $periodo        = decrypt($request->periodo_id);
        $codigo         = $request->codigo;
        $estudiante_id  = $request->estudiante_id;
        $code = $this->getCodigos($codigo);
        //para ver cuantos tickets abiertos tiene el usuario maximo 10
        $cantidadTicketOpen = DB::SELECT("SELECT t.* FROM tickets t
        WHERE t.usuario_id = $request->estudiante_id
        AND t.estado = '1'
        AND t.ticket_asesor = '1'
        ");
        $realizarTicket = "ok";
        if(count($cantidadTicketOpen) > 10){
            $realizarTicket = "no";
        }
        //validar que el codigo existe
        if(empty($code)){
            return [
                "status" => "2",
                "message" => "El código ingresado no existe",
                'realizarTicket' => $realizarTicket,
                "datos" => 0,
            ];
        }
        //validar que no haya un ticket abierto con el mismo codigo
        $validate = DB::SELECT("SELECT t.* FROM tickets t
        WHERE t.usuario_id = $request->estudiante_id
        AND t.estado = '1'
        AND t.ticket_asesor = '1'
        AND t.codigo = '$codigo';
        ");
        if(count($validate) > 0){
            return [
                "status" => "0",
                "message" => "Ya ha sido generado un ticket con soporte de este código, revise en su perfil",
                'realizarTicket' => "no",
                "datos" => 0,
            ];
        }
        $libro_id = $code[0]->libro_idlibro;
        //validate que no se ingrese 2 codigos del mismo libro
        $validate = DB::SELECT("SELECT dl.* FROM diagnostico_libros dl
            WHERE dl.institucion_id = '$institucion'
            AND dl.periodo_id = '$periodo'
            AND dl.libro_id = '$libro_id'
            AND dl.estudiante_id = '$estudiante_id'
        ");
        if(count($validate) > 0){
            return ["status" => "0", "message" => "Usted ya ingreso un código del mismo libro anteriormente"];
        }
        //validar que el codigo no tenga otro estudiante
        $ifEstudiante       = $code[0]->idusuario;
        //validar si el codigo ya haya sido leido
        $ifLeido            = $code[0]->bc_estado;
        //validar si el codigo ya esta liquidado
        $ifLiquidado        = $code[0]->estado_liquidacion;
        //validar si el codigo no este liquidado
        $ifBloqueado        = $code[0]->estado;
         //validar que el bc_institucion sea 0 o sea igual al que se envia
        $ifBc_Institucion   = $code[0]->bc_institucion;
        //validar que el periodo del estudiante sea 0 o sea igual al que se envia
        $ifid_periodo   = $code[0]->id_periodo;
        if(($ifEstudiante == 0 || $ifEstudiante == null) && ($ifid_periodo  == $periodo || $ifid_periodo == 0 ||  $ifid_periodo == null  ||  $ifid_periodo == "") && ($ifBc_Institucion  == $institucion || $ifBc_Institucion  == $institucion || $ifBc_Institucion == 0)&& $ifLeido == '1' && $ifLiquidado == '1' && $ifBloqueado !=2){
            $codigoE =  DB::table('codigoslibros')
            ->where('codigo', $request->codigo)
            ->where('bc_estado', '1')
            ->where('estado','<>', '2')
            ->where('estado_liquidacion','=', '1')
            ->update([
                'idusuario'             => $estudiante_id,
                'id_periodo'            => $periodo,
                'bc_institucion'        => $institucion,
                'bc_estado'             => 2,
                'bc_periodo'            => $periodo,
                'bc_fecha_ingreso'      => $todate,
                // 'venta_estado'          => $venta_estado
            ]);   
            if($codigoE){
                //ingreso en el historico
                $this->saveHistorico($codigo,$institucion,$estudiante_id,$periodo);
                //ingreso en la tabla diagnostico libro
                $this->saveDiagnostico($libro_id,$codigo,$estudiante_id,$institucion,$periodo);
                //si existe traigo los codigos que haya puesto anteriormente y cargar las pruebas diagnosticas
                return  $this->traerCodigos($institucion,$periodo,$estudiante_id,[]);
            } 
        }else{
            $codigoLiquidados[] = [
                "codigo"                => $codigo,
                "barrasEstado"          => $code[0]->barrasEstado,
                "codigoEstado"          => $code[0]->codigoEstado,
                "liquidacion"           => $code[0]->liquidacion,
                "ventaEstado"           => $code[0]->ventaEstado,
                "idusuario"             => $code[0]->idusuario,
                "estudiante"            => $code[0]->estudiante,
                "nombreInstitucion"     => $code[0]->nombreInstitucion,
                "institucionBarra"      => $code[0]->institucionBarra,
                "periodo"               => $code[0]->periodo,
                "periodo_barras"        => $code[0]->periodo_barras,
                "cedula"                => $code[0]->cedula,
                "email"                 => $code[0]->email,
                "estado_liquidacion"    => $code[0]->estado_liquidacion,
                "estado"                => $code[0]->estado,
                "status"                => $code[0]->status,
                "contador"              => $code[0]->contador
            ];
            return [
                "status" => "2",
                "message" => "El codigo ingresado presenta problemas",
                'realizarTicket' => $realizarTicket,
                "datos" => 1];
        }
    }
    public function guardarRespuestaDiagnostico(Request $request){
        $institucion    = decrypt($request->institucion_id);
        $periodo        = decrypt($request->periodo_id);
        $arreglorespuestas = json_decode($request->respuestas);
        //validar si el estudiante ya dio la prueba
        $validate = DB::SELECT("SELECT * FROM diagnostico_libros lb
        WHERE lb.id = '$request->diagnostico_libros_id' 
        AND lb.prueba_diagnostica = '0'
        ");
        if(empty($validate)){
            return ["status" => "0", "message" => "El estudiante ya dio la prueba anteriormente"];
        }
        //guardar las opciones
        $sumResultado = 0;
        foreach($arreglorespuestas as $key => $item){
            $respuesta = decrypt($item->_id);
            if($item->formato == $respuesta){
                $sumResultado++;
            }
            $respuesta = new DiagnosticoRespuesta();
            $respuesta->diagnostico_libros_id   = $request->diagnostico_libros_id;
            $respuesta->diagnostico_pregunta_id = $item->pregunta_id;
            $respuesta->estudiante_id           = $request->estudiante_id;
            $respuesta->respuesta_estudiante    = $item->formato;
            $respuesta->respuesta_correcta      = decrypt($item->_id);
            $respuesta->save();
        }
        //actualizar como diagnostico realizado
        $diagnostico = DiagnosticoLibros::findOrFail($request->diagnostico_libros_id);
        $diagnostico->prueba_diagnostica        = "1";
        $diagnostico->calificacion_diagnostica  = $sumResultado;
        $diagnostico->save();
        if($diagnostico){
            return ["status" => "1", "message" => "Se guardo correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
        }
    }
    public function saveHistorico($codigo,$institucion,$estudiante_id,$periodo){
        $comentario                 = "Codigo ingresado desde el formulario de diagnostico";
        $historico                  = new HistoricoCodigos();
        $historico->codigo_libro    = $codigo;
        $historico->usuario_editor  = $institucion;
        $historico->id_usuario      = $estudiante_id;
        $historico->id_periodo      = $periodo;
        $historico->observacion     = $comentario;
        $historico->b_estado        = "1";
        $historico->save();
    }
    public function saveDiagnostico($libro_id,$codigo,$estudiante_id,$institucion_id,$periodo_id){
        $diagnostico = new DiagnosticoLibros();
        $diagnostico->libro_id          = $libro_id;  
        $diagnostico->codigo            = $codigo;  
        $diagnostico->estudiante_id     = $estudiante_id;  
        $diagnostico->institucion_id    = $institucion_id;  
        $diagnostico->periodo_id        = $periodo_id;    
        $diagnostico->save();
    }
    public function getCodigos($codigo){
        $consulta = DB::SELECT("SELECT 
        (SELECT CONCAT(' Cliente: ', d.cliente  , ' - ',d.fecha_devolucion) AS devolucion 
        FROM codigos_devolucion d
        WHERE d.codigo = c.codigo
        AND d.estado = '1'
        ORDER BY d.id DESC
        LIMIT 1) as devolucionInstitucion,c.libro_idlibro,
        c.codigo,c.bc_estado,c.estado,c.estado_liquidacion,contador,
        c.venta_estado,c.bc_periodo,c.bc_institucion,c.idusuario,c.id_periodo,c.contrato,c.libro,
        CONCAT(u.nombres, ' ', u.apellidos) as estudiante, u.email,u.cedula, ib.nombreInstitucion as institucion_barras,
        i.nombreInstitucion, p.periodoescolar as periodo,pb.periodoescolar as periodo_barras,
        IF(c.estado ='2', 'bloqueado','activo') as codigoEstado,
        (case when (c.estado_liquidacion = '0') then 'liquidado'
            when (c.estado_liquidacion = '1') then 'sin liquidar'
            when (c.estado_liquidacion = '2') then 'codigo regalado'
            when (c.estado_liquidacion = '3') then 'codigo devuelto'
        end) as liquidacion,
        (case when (c.bc_estado = '2') then 'codigo leido'
        when (c.bc_estado = '1') then 'codigo sin leer'
        end) as barrasEstado,
        (case when (c.venta_estado = '0') then ''
            when (c.venta_estado = '1') then 'Venta directa'
            when (c.venta_estado = '2') then 'Venta por lista'
        end) as ventaEstado,
        ib.nombreInstitucion as institucionBarra, i.nombreInstitucion,
        p.periodoescolar as periodo, pb.periodoescolar as periodo_barras,ivl.nombreInstitucion as InstitucionLista
        FROM codigoslibros c
        LEFT JOIN usuario u ON c.idusuario = u.idusuario
        LEFT JOIN institucion ib ON c.bc_institucion = ib.idInstitucion
        LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
        LEFT JOIN institucion ivl ON c.venta_lista_institucion = ivl.idInstitucion
        LEFT JOIN periodoescolar p ON c.id_periodo = p.idperiodoescolar
        LEFT JOIN periodoescolar pb ON c.bc_periodo = pb.idperiodoescolar
        WHERE codigo = '$codigo'
        ");
        return $consulta;
    }
    public function verInfoCedulaDiagnostico(Request $request){
        //validar que existe el usuario
        $institucion = decrypt($request->institucion_id);
        $periodo = decrypt($request->periodo_id);
        $validate = DB::SELECT("SELECT u.idusuario,u.nombres,u.apellidos,
         u.cedula,u.telefono,u.email,u.id_group,u.institucion_idInstitucion
         FROM usuario u
         WHERE u.cedula = '$request->cedula'
         AND u.institucion_idInstitucion = '$institucion'
         AND u.periodo_actualizacion = '$periodo'
        ");
        if(empty($validate)){
            return ["status" => "0", "message" => "Su cedula no pertenece a esta institución, comuniquese con soporte por favor"];
        }else{
            $idusuario = $validate[0]->idusuario;
            //si existe traigo los codigos que haya puesto anteriormente y cargar las pruebas diagnosticas
            return  $this->traerCodigos($institucion,$periodo,$idusuario,$validate);
        }
    }
    public function traerCodigos($institucion,$periodo,$idusuario,$validate){
        $codigos = DB::SELECT("SELECT dl.*,
            CONCAT(dl.codigo,' - ',l.nombrelibro) AS getlibro,
            l.nombrelibro
            FROM diagnostico_libros dl
            LEFT JOIN libro l ON dl.libro_id = l.idlibro 
            WHERE dl.institucion_id = '$institucion'
            AND dl.periodo_id = '$periodo'
            AND dl.estudiante_id = '$idusuario'
            ORDER BY dl.id DESC
        ");
        return ["status" => "1","info" => $validate,"codigos" => $codigos];
    }
    public function show($id)
    {
        $consulta = DB::SELECT("SELECT dr.* FROM diagnostico_respuesta dr
        WHERE dr.diagnostico_pregunta_id = '$id'
        ");
        //para validar que no haya preguntas ya contestas y se pueda eliminar
        if(count($consulta) > 0){
            return ["status" => "0", "message" => "La pregunta ya se encuentra respondida por un estudiante, no puede ser eliminada"];
        }else{
            return ["status" => "1"];
        }
    }
    public function getDatosDiagnostico(Request $request){
        $consulta = DB::SELECT("SELECT dr.*,
        pd.pregunta,pd.imagen
        FROM diagnostico_respuesta dr
        LEFT JOIN diagnostico_preguntas_detalle pd ON dr.diagnostico_pregunta_id = pd.id
        WHERE dr.diagnostico_libros_id = '$request->diagnostico_libros_id'
        AND dr.estudiante_id = '$request->estudiante_id'
        ");
        return $consulta;
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
}
