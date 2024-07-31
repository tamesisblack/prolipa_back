<?php

namespace App\Http\Controllers;

use App\Models\Contenido;
use Illuminate\Http\Request;
use DB;

class ContenidoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        if($request->busqueda == 'asignatura'){


            $contenido = DB::SELECT("SELECT contenido.idcontenido,contenido.nombre,contenido.url,contenido.unidad,
            contenido.file_ext,contenido.updated_at,asignatura.idasignatura,asignatura.nombreasignatura as asignatura
            FROM contenido
            join asignatura on contenido.idasignatura = asignatura.idasignatura
            WHERE contenido.idasignatura IS NOT NULL
            AND contenido.estado = '1'
            AND asignatura.nombreasignatura LIKE '%$request->razonBusqueda%'
            ORDER BY contenido.updated_at DESC
            ");
            if(count($contenido) == 0){
                return ["status" => "0", "message" => "No hay contenidos con ese criterio"];
            }



            foreach ($contenido as $key => $post) {
                    try {
                        $respuesta = DB::SELECT("SELECT temas.id,temas.nombre_tema FROM temas_has_contenido JOIN temas ON temas.id = temas_has_contenido.temas_id WHERE temas_has_contenido.contenido_idcontenido = ? ",[$post->idcontenido]);
                        $data['items'][$key] = [
                            'idcontenido' => $post->idcontenido,
                            'nombre' => $post->nombre,
                            'file_ext' => $post->file_ext,
                            'unidad' => $post->unidad,
                            'updated_at' => $post->updated_at,
                            'idasignatura' => $post->idasignatura,
                            'asignatura' => $post->asignatura,
                            'temas'=>$respuesta,
                        ];
                    } catch (\Throwable $th) {
                        $data['items'][$key] = [
                            'idcontenido' => $post->idcontenido,
                            'nombre' => $post->nombre,
                            'file_ext' => $post->file_ext,
                            'unidad' => $post->unidad,
                            'updated_at' => $post->updated_at,
                            'idasignatura' => $post->idasignatura,
                            'asignatura' => $post->asignatura,
                            'temas'=>[],
                        ];
                }
            }

            return $data;

        }

        if($request->busqueda == 'archivo'){

            $contenido = DB::SELECT("SELECT contenido.idcontenido,contenido.nombre,contenido.url,contenido.unidad,
            contenido.file_ext,contenido.updated_at,asignatura.idasignatura,asignatura.nombreasignatura as asignatura
            FROM contenido
            join asignatura on contenido.idasignatura = asignatura.idasignatura
            WHERE contenido.idasignatura IS NOT NULL
            AND contenido.estado = '1'
            AND contenido.nombre LIKE '%$request->razonBusqueda%'
            ORDER BY contenido.updated_at DESC
            ");

            if(count($contenido) == 0){
                return ["status" => "0", "message" => "No hay contenidos con ese criterio"];
            }

            foreach ($contenido as $key => $post) {
                    try {
                        $respuesta = DB::SELECT("SELECT temas.id,temas.nombre_tema FROM temas_has_contenido JOIN temas ON temas.id = temas_has_contenido.temas_id WHERE temas_has_contenido.contenido_idcontenido = ? ",[$post->idcontenido]);

                        $data['items'][$key] = [
                            'idcontenido' => $post->idcontenido,
                            'nombre' => $post->nombre,
                            'file_ext' => $post->file_ext,
                            'unidad' => $post->unidad,
                            'updated_at' => $post->updated_at,
                            'idasignatura' => $post->idasignatura,
                            'asignatura' => $post->asignatura,
                            'temas'=>$respuesta,
                        ];
                    } catch (\Throwable $th) {
                        $data['items'][$key] = [
                            'idcontenido' => $post->idcontenido,
                            'nombre' => $post->nombre,
                            'file_ext' => $post->file_ext,
                            'unidad' => $post->unidad,
                            'updated_at' => $post->updated_at,
                            'idasignatura' => $post->idasignatura,
                            'asignatura' => $post->asignatura,
                            'temas'=>[],
                        ];
                }
            }
            return $data;

        }

        else{

            $contenido = DB::SELECT("CALL `getContenido` ();");
            foreach ($contenido as $key => $post) {
                    try {
                        $respuesta = DB::SELECT("SELECT temas.id,temas.nombre_tema FROM temas_has_contenido JOIN temas ON temas.id = temas_has_contenido.temas_id WHERE temas_has_contenido.contenido_idcontenido = ? ",[$post->idcontenido]);
                        $data['items'][$key] = [
                            'idcontenido' => $post->idcontenido,
                            'nombre' => $post->nombre,
                            'file_ext' => $post->file_ext,
                            'unidad' => $post->unidad,
                            'updated_at' => $post->updated_at,
                            'idasignatura' => $post->idasignatura,
                            'asignatura' => $post->asignatura,
                            'temas'=>$respuesta,
                        ];
                    } catch (\Throwable $th) {
                        $data['items'][$key] = [
                            'idcontenido' => $post->idcontenido,
                            'nombre' => $post->nombre,
                            'file_ext' => $post->file_ext,
                            'unidad' => $post->unidad,
                            'updated_at' => $post->updated_at,
                            'idasignatura' => $post->idasignatura,
                            'asignatura' => $post->asignatura,
                            'temas'=>[],
                        ];
                }
            }
            return $data;

        }

    }

    public function setContenido(Request $request){
        if(!empty($request->idcontenido)){
            $file = $request->file('archivo');
            //RUTA LINUX
            $ruta = '/var/www/vhosts/prolipadigital.com.ec/httpdocs/software/PlataformaProlipa/public';
            //RUTA WINDOWS
            //$ruta=public_path();
            $name = $file->getClientOriginalName();
            $url = uniqid().'.'.$file->getClientOriginalExtension();
            $ext = $file->getClientOriginalExtension();
            $file->move($ruta,$url);
            $contenido = Contenido::find($request->idcontenido)->update(
                [
                    'nombre' => $name,
                    'url' => $url,
                    'file_ext' => $ext
                ]
            );

            return [
                'nombre' => $name,
                'url' => $url,
                'file_ext' => $ext
            ];

        }else{
            $file = $request->file('archivo');
            //RUTA LINUX
            $ruta = '/var/www/vhosts/prolipadigital.com.ec/httpdocs/software/PlataformaProlipa/public';
            //RUTA WINDOWS
            // $ruta=public_path();
            $name = $file->getClientOriginalName();
            $url = uniqid().'.'.$file->getClientOriginalExtension();
            $ext = $file->getClientOriginalExtension();
            $file->move($ruta,$url);
            $contenido = new Contenido();
            $contenido->nombre = $name;
            $contenido->url = $url;
            $contenido->file_ext = $ext;
            $contenido->save();
            return $contenido;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //

    public function store(Request $request)
    {
        $datosValidados=$request->validate([
            'nombre' => 'required',
            'idasignatura' => 'required',
        ]);
        if(!empty($request->idcontenido)){
            $contenido = Contenido::find($request->idcontenido)->update(
                [
                    'nombre' => $request->nombre,
                    'unidad' => $request->unidad,
                    'idasignatura' => $request->idasignatura
                ]
            );
            DB::DELETE("DELETE FROM `temas_has_contenido` WHERE `contenido_idcontenido` = ?",[$request->idcontenido]);
            foreach (json_decode($request->temas) as $key => $value) {
                DB::INSERT("INSERT INTO `temas_has_contenido`(`temas_id`, `contenido_idcontenido`) VALUES (?,?)",[$value,$request->idcontenido]);
            }
            // return $request->all();
        }else{
            $contenido = new Contenido();
            $contenido->nombre = $request->nombre;
            $contenido->unidad = $request->unidad;
            $contenido->idasignatura = $request->idasignatura;
            $contenido->save();
            try {
                foreach (json_decode($request->temas) as $key => $value) {
                    DB::INSERT("INSERT INTO `temas_has_contenido`(`temas_id`, `contenido_idcontenido`) VALUES (?,?)",[$value,$contenido->idcontenido]);
                }
            } catch (\Throwable $th) {
            }
            // return $contenido;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Contenido  $contenido
     * @return \Illuminate\Http\Response
     */
    public function show(Contenido $contenido)
    {
        //
    }


    public function teletareasunidades($id_unidad)
    {
        $teletareas = DB::SELECT("SELECT * FROM contenido c, temas_has_contenido tc, temas t WHERE c.idcontenido = tc.contenido_idcontenido AND tc.temas_id = t.id AND t.id_unidad = $id_unidad");

        return $teletareas;
    }


    public function teletareasunidades_tema($id_tema)
    {
        $teletareas = DB::SELECT("SELECT * FROM contenido c, temas_has_contenido tc, temas t WHERE c.idcontenido = tc.contenido_idcontenido AND tc.temas_id = t.id AND t.id = $id_tema");

        return $teletareas;
    }


    public function teletarea_asignatura($id)
    {
        $teletareas = DB::SELECT("SELECT * FROM contenido c WHERE c.idasignatura = $id AND c.estado = '1'");

        return $teletareas;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Contenido  $contenido
     * @return \Illuminate\Http\Response
     */
    public function edit(Contenido $contenido)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Contenido  $contenido
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contenido $contenido)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Contenido  $contenido
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contenido $contenido)
    {
        $eliminado = DB::UPDATE("UPDATE `contenido` SET `estado`='0' WHERE `idcontenido`=?",[$contenido->idcontenido]);
        return $contenido;
    }
}
