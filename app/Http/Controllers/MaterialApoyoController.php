<?php

namespace App\Http\Controllers;
use DB;
use App\Quotation;
use App\Models\MaterialApoyo;
use DateTime;
use Illuminate\Http\Request;

class MaterialApoyoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $idusuario = $request->idusuario;
        $material = DB::select('CALL datosmateriald(?)',[$idusuario]);
        return $material;
    }

    public function Historial(Request $request){
        $date = new DateTime();
        $idusuario = auth()->user()->idusuario;
        $idmaterial = $request->idmaterial;
        $fecha = $date->format('y-m-d');
        $hora = $date->format('H:i:s');
        DB::insert("INSERT INTO `material_has_usuario`(`material_idmaterial`, `usuario_idusuario`, `fecha`, `hora`) VALUES (?,?,?,?)",[$idmaterial,$idusuario,$fecha,$hora]);
    }

    public function aplicativo(Request $request)
    {
        $libros = DB::select('CALL datosmateriald(?)',[$request->idusuario]);
        return $libros;
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
        MaterialApoyo::create($request->all());
    }

    public function material(Request $request)
    {
        $material = DB::select('SELECT * FROM material');
        return $material;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\MaterialApoyo  $materialApoyo
     * @return \Illuminate\Http\Response
     */
    public function show(MaterialApoyo $materialApoyo)
    {
        //
    }



    public function materialapoyolibro($idlibro)
    {
        $material = DB::select("SELECT m.idmaterial, m.nombrematerial, m.descripcionmaterial, m.webmaterial, m.exematerial, m.imagenmaterial, m.Estado_idEstado, m.zipmaterial FROM material m, material_has_asignatura ma, libro l WHERE m.idmaterial = ma.material_idmaterial AND ma.asignatura_idasignatura = l.asignatura_idasignatura AND l.idlibro = $idlibro");

        return $material;
    }


    public function materialapoyo_unidad($id_unidad)
    {
        $material = DB::SELECT("SELECT DISTINCT m .* FROM material m, temas_has_material tm, temas t WHERE m.idmaterial = tm.id_material AND tm.id_tema = t.id AND t.id_unidad = $id_unidad");

        return $material;
    }


    public function materialapoyolibro_tema($id_tema)
    {
        $material = DB::SELECT("SELECT * FROM material m, temas_has_material tm WHERE m.idmaterial = tm.id_material AND tm.id_tema = $id_tema");

        return $material;
    }



    public function calificaciones_material_curso(Request $request)
    {
        $calificaciones = DB::SELECT("SELECT u.nombres, u.apellidos, u.cedula, u.email, mc.calificacion FROM estudiante e, usuario u, ma_calificaciones_material mc WHERE e.usuario_idusuario = u.idusuario AND e.codigo = '$request->codigo_curso' AND e.usuario_idusuario = mc.id_usuario AND mc.id_material = $request->id_material");

        return $calificaciones;
    }

    public function material_curso_estudiante(Request $request)
    {
        $materiales = DB::SELECT("SELECT m .*, ca.calificacion FROM material m, ma_cursos_has_material cm, ma_calificaciones_material ca WHERE m.idmaterial = cm.id_material AND cm.codigo_curso = '$request->codigo_curso' AND cm.id_material = ca.id_material AND ca.id_usuario = $request->id_usuario");

        return $materiales;
    }

    public function material_curso(Request $request)
    {
        $materiales = DB::SELECT("SELECT * FROM material m, ma_cursos_has_material mc WHERE m.idmaterial = mc.id_material AND mc.codigo_curso = '$request->codigo_curso' AND m.idmaterial NOT IN (SELECT cm.id_material FROM ma_calificaciones_material cm WHERE cm.id_material = m.idmaterial AND cm.id_usuario = $request->id_usuario)");

        return $materiales;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\MaterialApoyo  $materialApoyo
     * @return \Illuminate\Http\Response
     */
    public function edit(MaterialApoyo $materialApoyo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\MaterialApoyo  $materialApoyo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $respuesta=DB::update('UPDATE material SET nombrematerial = ? ,descripcionmaterial = ? ,webmaterial = ? ,exematerial = ? ,Estado_idEstado = ? ,zipmaterial = ?  WHERE idmaterial = ?',[$request->nombrematerial,$request->descripcionmaterial,$request->webmaterial,$request->exematerial,$request->Estado_idEstado,$request->zipmaterial,$request->idmaterial]);
        return $respuesta;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\MaterialApoyo  $materialApoyo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::delete('DELETE FROM material WHERE idmaterial = ?',[$request->idmaterial]);
    }
    public function materialapoyo_asignaturas()
    {
        $material = DB::select("SELECT distinct mh.*, m.nombrematerial, a.nombreasignatura
        FROM material m, asignatura a, material_has_asignatura mh
        WHERE mh.material_idmaterial = m.idmaterial
        and mh.asignatura_idasignatura = a.idasignatura
        and a.tipo_asignatura = 1" );

        return $material;
    }
    public function todo_asignaturas(){
        $material = DB::select('SELECT * FROM asignatura WHERE tipo_asignatura = 1');
        return $material;
    }
    public function agregar_material_asignaturas(Request $request){
        $material= DB::insert("INSERT INTO material_has_asignatura (material_idmaterial, asignatura_idasignatura) VALUES (?,?)",[$request->material_idmaterial, $request->asignatura_idasignatura]);

        return $material .' material asignado ';
    }
    public function editar_material_asignaturas(Request $request){
        $material= DB::UPDATE("UPDATE `material_has_asignatura` SET `material_idmaterial`=$request->material_idmaterial,`asignatura_idasignatura`=$request->asignatura_idasignatura WHERE `id_materia_asignatura` = $request->id_material_asignatura");

        return $material .' material editado ';
    }
    public function quitar_material_asignatura(Request $request){
        $temas = explode(",", $request->temas);
        $tam = sizeof($temas);
        for( $i=0; $i<$tam; $i++ ){
            // $tema= DB::delete('DELETE FROM temas_has_material WHERE id_material = ? and id_tema = ?', [$request->material_idmaterial, $temas[$i]]);
        }
        $material =  DB::delete('DELETE FROM material_has_asignatura WHERE material_idmaterial = ? and asignatura_idasignatura = ?',[$request->material_idmaterial, $request->asignatura_idasignatura]);

    }
    public function registrar_material(Request $request)
    {
        // $material= DB::insert("INSERT INTO material (nombrematerial, descripcionmaterial, zipmaterial, webmaterial, imagenmaterial, exematerial, Estado_idEstado) VALUES (?,?,?,?,?,?,?)",[$request->nombrematerial, $request->descripcionmaterial, $request->zipmaterial, $request->webmaterial, $request->imagenmaterial, $request->exematerial, $request->Estado_idEstado]);
        // return $material. ' material registrado ';
        // return $request;

        if( $request->idmaterial ){
            $material = MaterialApoyo::find($request->idmaterial);
        }else{
            $material = new MaterialApoyo();
        }

        $material->nombrematerial = $request->nombrematerial;
        $material->descripcionmaterial = $request->descripcionmaterial;
        $material->zipmaterial = $request->zipmaterial;
        $material->webmaterial = $request->webmaterial;
        $material->imagenmaterial = $request->imagenmaterial;
        $material->exematerial = $request->exematerial;
        $material->Estado_idEstado = $request->Estado_idEstado;
        $material->creador = $request->creador;
        $material->save();

        return $material;

    }

    public function guardar_material_usuario(Request $request){
        $material= DB::INSERT("INSERT INTO `material_has_usuario`(`material_idmaterial`, `usuario_idusuario`) VALUES ($request->id_material, $request->id_usuario)");

        return $material;
    }

    public function temas_material(Request $request)
    {
        $temas = explode(",", $request->id_tema);
        $tam = sizeof($temas);

        for( $i=0; $i<$tam; $i++ ){
            $tema= DB::INSERT("INSERT INTO temas_has_material(id_material, id_tema) VALUES (?,?)", [$request->id_material, $temas[$i]]);
        }
    }

    public function temas_por_material($id_asignatura)
    {

        if( $id_asignatura == 'null' ){
            $material= DB::SELECT("SELECT DISTINCT a.nombreasignatura, m.nombrematerial, m.idmaterial, a.idasignatura, m.descripcionmaterial, m.descripcionmaterial, m.exematerial, m.webmaterial, m.imagenmaterial, m.zipmaterial, m.Estado_idEstado FROM material m, temas_has_material tm, temas t, asignatura a WHERE m.idmaterial = tm.id_material AND tm.id_tema = t.id AND t.id_asignatura = a.idasignatura");
        }else{
            $material= DB::SELECT("SELECT DISTINCT a.nombreasignatura, m.nombrematerial, m.idmaterial,
            a.idasignatura, m.descripcionmaterial, m.descripcionmaterial,
            m.exematerial, m.webmaterial, m.imagenmaterial, m.zipmaterial, m.Estado_idEstado
            FROM material m, temas_has_material tm, temas t, asignatura a
            WHERE m.idmaterial = tm.id_material
            AND tm.id_tema = t.id
            AND t.id_asignatura = a.idasignatura
            AND a.idasignatura = $id_asignatura
            ");
        }


        if(!empty($material)){
            foreach ($material as $key => $value) {
                $temas = DB::SELECT("SELECT t.nombre_tema, t.unidad, t.id_unidad, t.clasificacion, t.tipo_tema, tm.id_material, tm.id_tema from temas_has_material tm, temas t WHERE tm.id_tema = t.id AND tm.id_material = ? AND t.id_asignatura = ?",[$value->idmaterial, $value->idasignatura]);
                $data['items'][$key] = [
                    'idmaterial' => $value->idmaterial,
                    'nombreasignatura' => $value->nombreasignatura,
                    'asignatura_idasignatura' => $value->idasignatura,
                    'nombrematerial' => $value->nombrematerial,
                    'descripcionmaterial' => $value->descripcionmaterial,
                    'webmaterial' => $value->webmaterial,
                    'exematerial' => $value->exematerial,
                    'imagenmaterial' => $value->imagenmaterial,
                    'zipmaterial' => $value->zipmaterial,
                    'estado' => $value->Estado_idEstado,
                    'temas'=>$temas,
                ];
            }
        }else{
            $data = [];
        }
        return $data;
    }

    public function temas_asignatura_material(Request $request){
        // $temas = DB::SELECT("SELECT t.nombre_tema, t.id AS id, t.nombre_tema AS label, t.id_asignatura,
        // t.unidad, a.nombreasignatura, t.clasificacion
        // FROM temas t, asignatura a
        // WHERE t.id_asignatura = a.idasignatura
        // AND t.unidad = $request->unidad
        // AND t.id_asignatura = $request->asignatura
        // AND t.estado=1 ORDER BY cast(t.nombre_tema as int) ASC");

        $temas = DB::SELECT("SELECT t.nombre_tema, t.id AS id, t.nombre_tema AS label, t.id_asignatura,
        t.unidad, a.nombreasignatura, t.clasificacion
          FROM temas t, asignatura a
        WHERE t.id_asignatura = a.idasignatura
          AND t.unidad          = ?
        AND t.id_asignatura     = ?
        ORDER BY CAST(SUBSTRING_INDEX(t.nombre_tema, ' ', 1) AS SIGNED);
        ",[$request->unidad,$request->asignatura]);
        return $temas;

    }
    public function material_estados(){
        $estados = DB::select('SELECT * FROM estado ');
        return $estados;
    }
    public function todo_material_apoyo($id_asignatura){
        if( $id_asignatura == 'null' ){
            $material = DB::select('SELECT m .*, u.id_group FROM material m, usuario u WHERE m.creador = u.idusuario');
        }else{
            $material = DB::select("SELECT m .*, u.id_group, t.id_asignatura FROM material m, usuario u, temas_has_material tm, temas t WHERE m.creador = u.idusuario AND m.idmaterial = tm.id_material AND tm.id_tema = t.id AND t.id_asignatura = $id_asignatura");
        }

        return $material;
    }
    public function eliminarMaterial(Request $request)
    {
        $material= DB::delete('DELETE FROM material WHERE idmaterial = ?',[$request->idmaterial]);
        return $material .' dilitttt' ;
    }
    public function showMaterial($id)
    {
        $material= DB::select("SELECT *  FROM material WHERE idmaterial = $id");
        return $material;
    }




    ///METODO PARA BORRAR TEMAS DE UN MATERIAL
    public function borrar_temas_material(Request $request)
    {
        $material= DB::SELECT("SELECT tm.id_tema_material FROM temas_has_material tm, temas t WHERE tm.id_material = $request->idmaterial AND tm.id_tema = t.id AND t.id_asignatura = $request->idasignatura");

        if(!empty($material)){
            foreach ($material as $key => $value) {
                $temas = DB::DELETE("DELETE FROM temas_has_material WHERE id_tema_material = ?",[$value->id_tema_material]);
            }
        }else{
            $data = [];
        }

    }


    public function borrar_material_asig(Request $request)
    {
        DB::SELECT("DELETE FROM `material_has_asignatura` WHERE `material_idmaterial` = $request->idmaterial AND `asignatura_idasignatura` = $request->idasignatura");
    }


    public function asignar_cursos_material(Request $request)
    {
        $cursos= DB::SELECT("SELECT * FROM ma_cursos_has_material cm WHERE cm.codigo_curso = '$request->codigo_curso' AND cm.id_material = $request->id_material");

        if( empty($cursos) ){
            $juegos= DB::INSERT("INSERT INTO `ma_cursos_has_material`(`codigo_curso`, `id_material`) VALUES ('$request->codigo_curso', $request->id_material)");
            return ["status" => "1", "message" => "Asignado correctamente"];
        }else{
            return ["status" => "0", "message" => "Este material ya se encuentra asignado a este curso"];
        }

    }

}
