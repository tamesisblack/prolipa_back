<?php

namespace App\Http\Controllers;

use App\Models\AsignaturaDocente;
use Illuminate\Http\Request;
use DB;
use App\Models\Curso;
use App\Quotation;
class AsignaturaDocenteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $usuario = DB::select("CALL `asignaturasDocente` ( $request->idusuario );");
        return $usuario;
    }

    public function asignaturas_crea_docente($id)
    {
        $asignaturas = DB::SELECT("SELECT a.idasignatura, a.nombreasignatura FROM asignatura a, asignaturausuario au WHERE a.idasignatura = au.asignatura_idasignatura AND au.usuario_idusuario = $id AND a.tipo_asignatura = 0 AND a.estado = '1'");
        return $asignaturas;
    }


    public function deshabilitarasignatura($id)
    {
        $asignatura = DB::UPDATE("UPDATE asignatura SET estado = '0' WHERE idasignatura = $id");

        return $asignatura;
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
        DB::SELECT("DELETE FROM `asignaturausuario` WHERE usuario_idusuario = ?",[$request->usuario_idusuario]);
        foreach ($request->asignaturas as $key => $post) {
            $asignatura = new AsignaturaDocente();
            $asignatura->usuario_idusuario = $request->usuario_idusuario;
            $asignatura->asignatura_idasignatura = $post;
            $asignatura->save();
        }
    }



     public function guardar_asignatura_usuario(Request $request)
    {
        $asignatura = new AsignaturaDocente();
        $asignatura->usuario_idusuario = $request->usuario_idusuario;
        $asignatura->asignatura_idasignatura = $request->asignatura_idasignatura;

        $asignatura->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\AsignaturaDocente  $asignaturaDocente
     * @return \Illuminate\Http\Response
     */
    public function show(AsignaturaDocente $asignaturaDocente)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AsignaturaDocente  $asignaturaDocente
     * @return \Illuminate\Http\Response
     */
    public function edit(AsignaturaDocente $asignaturaDocente)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AsignaturaDocente  $asignaturaDocente
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AsignaturaDocente $asignaturaDocente)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AsignaturaDocente  $asignaturaDocente
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $respuesta=DB::delete('DELETE FROM `asignaturausuario` WHERE  idasiguser = ?',[$request->asignatura_idasignatura]);
        return $respuesta;
    }
    //API:GET/moverAsignaturasPeriodo/{usuario}/{periodo}
    public function moverAsignaturasPeriodo($usuario,$periodo){
        $query = DB::SELECT("SELECT * FROM asignaturausuario a
        WHERE a.usuario_idusuario = '$usuario'
        ");
        foreach($query as $key => $item){
            DB::table('asignaturausuario')
            ->where('idasiguser', $item->idasiguser)
            ->update(['periodo_id' => $periodo]);
        }
        return "se guardo correctamente";
    }
    public function asignaturas_x_docente(Request $request)
    {
        $dato = DB::table('asignaturausuario as ausu')
        ->where('ausu.usuario_idusuario','=',$request->idusuario)
        ->leftjoin('asignatura as asig','ausu.asignatura_idasignatura','=','asig.idasignatura')
        ->leftjoin('periodoescolar as pe','pe.idperiodoescolar','=','ausu.periodo_id')
        ->select('asig.nombreasignatura','asig.idasignatura','asig.area_idarea', 'ausu.usuario_idusuario as user','ausu.asignatura_idasignatura','ausu.idasiguser as idasignado','pe.periodoescolar','ausu.periodo_id','ausu.updated_at')
        ->get();
        return $dato;
    }
    public function asignaturas_x_docente_xPeriodo(Request $request)
    {
        $dato = DB::table('asignaturausuario as ausu')
        ->where('ausu.usuario_idusuario','=',$request->idusuario)
        ->leftjoin('asignatura as asig','ausu.asignatura_idasignatura','=','asig.idasignatura')
        ->select('asig.nombreasignatura','asig.idasignatura','asig.area_idarea', 'ausu.usuario_idusuario as user','ausu.asignatura_idasignatura','ausu.idasiguser as idasignado')
        ->Where('periodo_id','=',$request->periodo_id)
        ->get();
        return $dato;
    }
    public function asignar_asignatura_docentes(Request $request)
    {
        $dato = DB::table('asignaturausuario')
        ->where('usuario_idusuario','=',$request->usuario_idusuario)
        ->where('asignatura_idasignatura','=',$request->asignatura_idasignatura)
        ->get();
        if ($dato->count() > 0) {
            return $dato->count();
        }else{
            $docente = DB::SELECT("SELECT * FROM usuario WHERE idusuario = '$request->usuario_idusuario'");
            $institucion = $docente[0]->institucion_idInstitucion;
            $buscarPeriodo = $this->traerPeriodo($institucion);
            $periodo = $buscarPeriodo["periodo"][0]->periodo;
            $asignatura = new AsignaturaDocente();
            $asignatura->usuario_idusuario       = $request->usuario_idusuario;
            $asignatura->asignatura_idasignatura = $request->asignatura_idasignatura;
            $asignatura->periodo_id              = $periodo;
            $asignatura->save();
            //$this->addCurso($request->asignatura_idasignatura,$request->usuario_idusuario);
            return $asignatura;
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
    public function addCurso($id_asignatura,$idusuario)
    {
        $periodo=DB::SELECT("SELECT u.idusuario, i.idInstitucion, MAX(pi.periodoescolar_idperiodoescolar) AS periodo FROM usuario u, institucion i, periodoescolar_has_institucion pi WHERE u.institucion_idInstitucion = i.idInstitucion AND i.idInstitucion = pi.institucion_idInstitucion AND u.idusuario = $idusuario");

        $curso = Curso::create([
            'nombre' => 'DEMO',
            'id_asignatura'=> $id_asignatura,
            'seccion' => 'DEMO',
            'materia' => 'DEMO',
            'aula' => 'DEMO',
            'codigo' => $this->codigo(8),
            'idusuario'=> $idusuario,
            'id_periodo'=> $periodo[0]->periodo,
        ]);
    }

    function codigo($count)
    {
        // This string MUST stay FS safe, avoid special chars
        $base = 'ABCDEFGHKMNPRSTUVWXYZ123456789';
        $ret = '';
        $strlen = \strlen($base);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= $base[random_int(0, $strlen - 1)];
        }

        return $ret;
    }
    public function eliminaAsignacion($id)
    {
        $data = AsignaturaDocente::find($id);
        $data->delete();
        return $data;
    }
    public function quitarTodasAsignaturasDocente(Request $request)
    {
        $ids = explode(",",$request->idasiguser);
        $data = AsignaturaDocente::destroy($ids);
        return $data;

    }
    //INICIO METODOS JEYSON
    public function asignar_asignatura_docentes_xarea_disponible(Request $request)
    {
        //INICIO VALIDACION PARA VERIFIAR SI EL AREA ESTA ACTIVA
        $verificacion_antes_de_actualizar = DB::select("SELECT asi.*, ar.estado, ar.permiso_visible_asignacion_libros, ar.nombrearea
            FROM asignatura asi
            LEFT JOIN area ar ON asi.area_idarea = ar.idarea
            WHERE asi.idasignatura = ?
        ", [$request->asignatura_idasignatura]);

        if (empty($verificacion_antes_de_actualizar)) {
            return response()->json([
                'status' => 0,
                'mensaje' => 'Asignatura no encontrada o sin área asociada.'
            ], 200);
        }
        $area = $verificacion_antes_de_actualizar[0];
        if ($area->estado != 1 || $area->permiso_visible_asignacion_libros != 1) {
            return response()->json([
                'status' => 0,
                'mensaje' => "El área '$area->nombrearea' se encuentra inactiva o sin permiso para asignación."
            ], 200);
        }
        //FIN VALIDACION PARA VERIFIAR SI EL AREA ESTA ACTIVA
        $dato = DB::table('asignaturausuario')
        ->where('usuario_idusuario','=',$request->usuario_idusuario)
        ->where('asignatura_idasignatura','=',$request->asignatura_idasignatura)
        ->get();
        if ($dato->count() > 0) {
            return response()->json([
                'status' => 2,
                'mensaje' => 'Esta asignatura ya se encuentra asignada al docente',
                'conteo' => $dato->count()
            ], 200);
        }else{
            $docente = DB::SELECT("SELECT * FROM usuario WHERE idusuario = '$request->usuario_idusuario'");
            $institucion = $docente[0]->institucion_idInstitucion;
            $buscarPeriodo = $this->traerPeriodo($institucion);
            $periodo = $buscarPeriodo["periodo"][0]->periodo;
            $asignatura = new AsignaturaDocente();
            $asignatura->usuario_idusuario       = $request->usuario_idusuario;
            $asignatura->asignatura_idasignatura = $request->asignatura_idasignatura;
            $asignatura->periodo_id              = $periodo;
            $asignatura->save();
            //$this->addCurso($request->asignatura_idasignatura,$request->usuario_idusuario);
            return response()->json([
                'status' => 1,
                'mensaje' => 'Asignatura agregada con éxito',
                'data' => $asignatura
            ], 200);
        }
    }
    //FIN METODOS JEYSON
}
