<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use App\Models\EvaluacionInstitucionAsignada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Temas;

class TemaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->byAsignatura){
            // $temas = DB::SELECT("SELECT
            // t.id AS id, t.idusuario, t.nombre_tema AS label,
            // `id_asignatura`, `unidad`, a.nombreasignatura,
            // t.clasificacion, t.id_unidad
            // FROM `temas` t, `asignatura` a
            // WHERE t.id_asignatura = a.idasignatura
            // AND t.estado = '1'
            // AND a.idasignatura = '$request->asignatura'
            // ORDER BY a.idasignatura");
            $temas = DB::SELECT("SELECT
            t.id AS id, t.idusuario, t.nombre_tema AS label,
            t.id_asignatura, t.unidad, a.nombreasignatura,
            t.clasificacion, t.id_unidad
            FROM temas t
            LEFT JOIN asignatura a ON t.id_asignatura = a.idasignatura
            LEFT JOIN unidades_libros u ON t.id_unidad = u.id_unidad_libro
            LEFT JOIN libro l ON u.id_libro = l.idlibro
            WHERE  t.estado = '1'
            AND a.idasignatura = '$request->asignatura'
            AND l.asignatura_idasignatura = '$request->asignatura'
            ORDER BY a.idasignatura
            ");

            return $temas;
        }else{
            $temas = DB::SELECT("SELECT t.id AS id, t.idusuario, t.nombre_tema AS label, `id_asignatura`, `unidad`, a.nombreasignatura, t.clasificacion, t.id_unidad FROM `temas` t, `asignatura` a WHERE t.id_asignatura = a.idasignatura AND t.estado=1 ORDER BY a.idasignatura");

            return $temas;
        }


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
    public function store(Request $request)
    {
        if($request->porImportacion) { return $this->porImportacion($request); }
        if( $request->id ){
            $tema = Temas::find($request->id);
        }else{
            $tema = new Temas();
        }

        $tema->nombre_tema      = $request->nombre;
        $tema->id_asignatura    = $request->asignatura;
        $tema->unidad           = $request->unidad;
        $tema->id_unidad        = $request->id_unidad;
        $tema->clasificacion    = $request->clasificacion;
        $tema->idusuario        = $request->idusuario;
        $tema->estado           = $request->estado;
        $tema->save();

        return $tema;
    }
    //api:post/tema?porImportacion=1
    public function porImportacion(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $datos_array = json_decode($request->datos_array);
        $contador = 0;
    
        try {
            // Inicia transacción
            DB::beginTransaction();
    
            foreach($datos_array as $key => $item) {
                $validarAsignatura = Asignatura::where('idasignatura', $item->id_asignatura)
                ->leftjoin('libro as l', 'l.asignatura_idasignatura', '=', 'asignatura.idasignatura')
                ->select('l.idlibro')
                ->first();
                if(!$validarAsignatura){
                    return ["status" => "0", "message" => "No existe la asignatura con id ".$item->id_asignatura];
                }
                $idlibro = $validarAsignatura->idlibro;
                // Creación del nuevo tema
                //validacion unidad
                $validateUnidad = DB::SELECT("SELECT * FROM unidades_libros u
                WHERE u.id_libro = '$idlibro'
                and u.unidad = '$item->unidad'
                AND u.estado = '1';
                ");
                if(count($validateUnidad) == 0){
                    return ["status" => "0", "message" => "No existe la unidad  ".$item->unidad .' con la id_asignatura '.$item->id_asignatura];
                }
                $id_unidad_libro        = $validateUnidad[0]->id_unidad_libro;
                $tema = new Temas();
                $tema->nombre_tema      = $item->nombre_tema;
                $tema->id_asignatura    = $item->id_asignatura;
                $tema->unidad           = $item->unidad;
                // $tema->id_unidad        = $item->id_unidad;
                $tema->id_unidad        = $id_unidad_libro;
                $tema->clasificacion    = $item->destreza == '0' ? null : $item->destreza;
                $tema->idusuario        = $request->idusuario;
                $tema->estado           = 1;
                $tema->save();
                // Guardado del modelo
                if ($tema) {
                    $contador++; // Solo incrementa si la inserción fue exitosa
                }
            }
            // Confirmar transacción si todo salió bien
            DB::commit();
    
            return ["status" => "1", "message" => "Se importó correctamente", "contador" => $contador];
        } catch (\Exception $e) {
            // En caso de error, deshacer la transacción
            DB::rollback();
            return ["status" => "0", "message" => $e->getMessage()];
        }
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $temas = DB::SELECT("SELECT t.id AS id, t.idusuario, t.nombre_tema AS label, t.id_asignatura, t.unidad, a.nombreasignatura, t.clasificacion FROM temas t, asignatura a, asignaturausuario au WHERE t.id_asignatura = a.idasignatura AND a.idasignatura = au.asignatura_idasignatura AND t.idusuario = $id AND t.estado=1 ORDER BY a.idasignatura");

        return $temas;
    }

    public function temasignunidadExport(Request $request)
    {
        $temas = DB::SELECT("SELECT t.nombre_tema, t.id AS id,
            t.nombre_tema AS label, t.id_asignatura, t.unidad,
            a.nombreasignatura, t.clasificacion,t.unidad
            FROM temas t, asignatura a
            WHERE t.id_asignatura = a.idasignatura
            AND t.unidad = $request->unidad
            AND t.id_asignatura = $request->asignatura
            AND t.estado=1
            -- ORDER BY cast(t.nombre_tema as int) ASC
        ");
        return $temas;
    }
    public function allTemasxAsignatura(Request $request)
    {
        $temas = DB::SELECT("SELECT t.nombre_tema, t.id AS id,
            t.nombre_tema AS label, t.id_asignatura, t.unidad,
            a.nombreasignatura, t.clasificacion,t.unidad
            FROM temas t, asignatura a
            WHERE t.id_asignatura = a.idasignatura
            -- AND t.unidad = $request->unidad
            AND t.id_asignatura = $request->asignatura
            AND t.estado=1
            ORDER BY t.unidad, CAST(SUBSTRING_INDEX(t.nombre_tema, ' ', 1) AS SIGNED)
        ");
        return $temas;
    }
    public function temasignunidad(Request $request)
    {

        // $temas = DB::SELECT("SELECT t.nombre_tema, t.id AS id, t.nombre_tema AS label, t.id_asignatura,
        // t.unidad, a.nombreasignatura, t.clasificacion
        // FROM temas t, asignatura a
        // WHERE t.id_asignatura = a.idasignatura
        // AND t.unidad = $request->unidad
        // AND t.id_asignatura = $request->asignatura
        // AND t.estado=1
        // ORDER BY cast(t.nombre_tema as int) ASC
        // ");
        // return $temas;
        $temas = DB::SELECT("SELECT t.nombre_tema, t.id AS id, t.nombre_tema AS label, t.id_asignatura,
        t.unidad, a.nombreasignatura, t.clasificacion,t.tipo_tema
        FROM temas t, asignatura a
        WHERE t.id_asignatura = a.idasignatura
        AND t.unidad        = ?
        AND t.id_asignatura = ?
        AND t.estado=1
        ORDER BY CAST(SUBSTRING_INDEX(t.nombre_tema, ' ', 1) AS SIGNED);
        ",[$request->unidad,$request->asignatura]);
        return $temas;
    }


    public function temAsignaruta($id)
    {
         $temas = DB::SELECT("SELECT * from temas t where id_asignatura = $id");

        return $temas;
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
        $tema = Temas::find($id);
        $tema->nombre_tema = $request->nombre;
        $tema->id_asignatura = $request->asignatura;
        $tema->unidad = $request->unidad;
        $tema->save();

        return $tema;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $tema = Temas::find($request->id);

        if($tema->delete()){
            return 1;
        }else{
            return 0;
        }

    }


    public function eliminar_tema(Request $request)
    {
        $temas = DB::UPDATE("UPDATE `temas` SET `idusuario`=$request->idusuario,`estado`=0 WHERE `id` = $request->id_tema;");

        return $temas;
    }
    //API:POST/temas_preguntas_respuestas
    public function temas_pregunt_respues(Request $request){
        $temas = DB::SELECT("SELECT t.nombre_tema, t.id AS id, t.nombre_tema AS label, t.id_asignatura,
        t.unidad, a.nombreasignatura, t.clasificacion,t.tipo_tema
        FROM temas t, asignatura a
        WHERE t.id_asignatura = a.idasignatura
        AND t.id_asignatura = ?
        AND t.estado=1
        ORDER BY CAST(SUBSTRING_INDEX(t.nombre_tema, ' ', 1) AS SIGNED);
        ",[$request->asignatura]);
        $datos = [];
        foreach($temas as $key=> $item2){
            $preguntas = DB::SELECT("SELECT ti.nombre_tipo, ti.descripcion_tipo,
            p.id, p.id_tema, p.descripcion, p.img_pregunta, p.id_tipo_pregunta,
            p.puntaje_pregunta, te.nombre_tema, p.idusuario,te.unidad,
            CONCAT(u.nombres,' ',u.apellidos) as editor,p.estado
            FROM preguntas p, tipos_preguntas ti, temas te,usuario u
            WHERE p.id_tema = ?
            AND p.id_tipo_pregunta = ti.id_tipo_pregunta
            -- AND p.idusuario != ?
            AND p.id_tema = te.id
            AND p.idusuario = u.idusuario
            AND u.id_group = 1
            AND p.estado = '1'
            ORDER BY p.descripcion DESC
            ",[$item2->id,$request->idusuario]);
            $datos[$key] = $preguntas;
        }
        $preResultado = collect($datos)->flatten(10);
        if(count($preResultado) == 0){
            return [];
        }
        $resultado    = [];
        //opciones
        foreach ($preResultado as $key => $value) {
            $opciones = DB::SELECT("SELECT id_opcion_pregunta, id_pregunta,
            opcion, img_opcion, tipo, cant_coincidencias
            FROM opciones_preguntas
            WHERE opciones_preguntas.id_pregunta = ?",[$value->id]);
            $value->opciones = $opciones;
            $resultado[$key] = $value;
        }
        $resultado = collect($resultado)->chunk(1)->flatten(10);
        return $resultado;
    }
    //API:GET/getInstitucionesEvaluaciones
    public function get_evaluaciones_instituciones(Request $request){
        if($request->getInstituciones) { return $this->getInstituciones(); }
    }
    //API:GET/get_evaluaciones_instituciones?getInstituciones=yes
    public function getInstituciones(){
        $instituciones = DB::SELECT("SELECT idinstitucion, nombreinstitucion
        FROM institucion i
        WHERE i.estado_idEstado = 1
        AND evaluacion_personalizada = 1
        ORDER BY nombreinstitucion ASC");
        return $instituciones;
    }
    //API:POST/asignar_preguntas_institucion
    public function asignar_preguntas_institucion(Request $request){
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        //EvaluacionInstitucionAsignada
        $user_created              = $request->user_created;
        $institucion_id            = $request->institucion_id;
        $preguntas                 = json_decode($request->arrayPreguntas);
        $preguntasYaIngresadas     = [];
        $contador                  = 0;
        foreach($preguntas as $key => $item){
            //validar que la pregunta no ingresada
            $ExistsQuestion = EvaluacionInstitucionAsignada::where('pregunta_id',$item->id)->where('institucion_id',$institucion_id)->where('estado','1')->first();
            if($ExistsQuestion){
                $preguntasYaIngresadas[] = $item;
            }else{
                $evaluacionInstitucionAsignada = new EvaluacionInstitucionAsignada();
                $evaluacionInstitucionAsignada->pregunta_id     = $item->id;
                $evaluacionInstitucionAsignada->institucion_id  = $institucion_id;
                $evaluacionInstitucionAsignada->user_created    = $user_created;
                $evaluacionInstitucionAsignada->save();
                $contador++;
            }
        }
        return ["preguntasYaIngresadas" => $preguntasYaIngresadas,"preguntasIngresadas" => $contador];
    }
}
