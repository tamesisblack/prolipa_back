<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PreguntasFaq;
use App\Models\DescripcionFaq;
use DB;

class PreguntasfaqController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->todo){
            $preguntas = DB::select("SELECT p.preguntasfaq_id, p.descripcion as pregunta , p.estado as estado_pregunta, r.descripcion, r.archivo,r.url, r.video
            FROM preguntas_faq p
            LEFT JOIN respuestas_faq r ON r.pregunta_id = p.preguntasfaq_id
            ORDER BY p.preguntasfaq_id DESC
          
            
        
            ");
        }else{
            $preguntas = DB::select("SELECT p.preguntasfaq_id, p.descripcion as pregunta , p.estado as estado_pregunta, r.descripcion , r.archivo,r.url ,r.video
            FROM preguntas_faq p
            LEFT JOIN respuestas_faq r ON r.pregunta_id = p.preguntasfaq_id
            WHERE p.estado = '1'
            
        
            ");
        }
        
      return $preguntas;
    }


    public function cambioEstadoPregunta(Request $request){
        
        DB::table('preguntas_faq')
        ->where('preguntasfaq_id', $request->preguntasfaq_id)
        ->update(['estado' => $request->estado]);
        
    }

       
    public function makeid(){
        $characters = '123456789abcdefghjkmnpqrstuvwxyz';
        $charactersLength = strlen($characters);
   
        $randomString = '';
        for ($i = 0; $i < 5; $i++) {
            for ($i = 0; $i < 16; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
      
        
         }   
      }
   
    public function store(Request $request)
    {
        //===PARA EDITAR=======
        if($request->preguntasfaq_id){
            
            //Actualizar la pregunta faq
            $preguntas = PreguntasFaq::findOrFail($request->preguntasfaq_id);
            $preguntas->descripcion = $request->pregunta;
            $preguntas->save(); 

             //En caso que no haya elegido una imagen
            if($request->sinimagen){
                 //Actualizar la respuesta faq
                DB::table('respuestas_faq')
                ->where('pregunta_id', $request->preguntasfaq_id)
                ->update(
                    [
                    'descripcion' => $request->respuesta,
                    'video' => $request->video
                ]);
            }else{

                    $traercodigo = $this->makeid();
                    $files = $request->file('archivo');

                    foreach($files as $clave => $file){
                        $buscarArchivo = DB::select("SELECT * from respuestas_faq WHERE pregunta_id = $request->preguntasfaq_id");
                        
                        $traerarchivo = $buscarArchivo[0]->url;
                        
                        if($traerarchivo == null || $traerarchivo = ""){
                            
                        }else{
                            $buscarArchivo = DB::select("SELECT * from respuestas_faq WHERE pregunta_id = $request->preguntasfaq_id");
                        
                            $traerarchivo = $buscarArchivo[0]->url;
                            
                            $path = "preguntas_frecuentes/".$traerarchivo;
                       
                            if(file_exists($path)) {
                                if(file_exists('preguntas_frecuentes/'.$traerarchivo) ){
                                    unlink('preguntas_frecuentes/'.$traerarchivo);
                                }
                              
                                
                            }
                        
                        }
                    
                     
                        $path = "/preguntas_frecuentes/";
                        $filename = $traercodigo."".$file->getClientOriginalName();
                        if($file->move(public_path().$path,$filename)){
                            //Actualizar la respuesta faq
                            DB::table('respuestas_faq')
                            ->where('pregunta_id', $request->preguntasfaq_id)
                            ->update([
                                'video' => $request->video,
                                'descripcion' => $request->respuesta,
                                "url" => $traercodigo."".$file->getClientOriginalName(),
                                "archivo" => $file->getClientOriginalName(),

                            ]);
                        }

                    
                    }

            }
              
            
            if($preguntas){
                return "Se guardo correctamente";
            }else{
                return "No se pudo guardar/actualizar";
            }
        //===PARA GUARDAR=======
        }else{
             //Guardar la pregunta faq
                $preguntas = new PreguntasFaq;
                $preguntas->descripcion = $request->pregunta;
                $preguntas->save(); 

            //En caso que no haya elegido una imagen
            if($request->sinimagen){
                //Guardar la respuesta faq
                
                $respuesta = new DescripcionFaq;
                $respuesta->descripcion = $request->respuesta;
                $respuesta->pregunta_id = $preguntas->preguntasfaq_id;
                $respuesta->video = $request->video;
                $respuesta->save();
                //en caso que haya elegido una imagen a subir
            }else{
                $traercodigo = $this->makeid();
                $files = $request->file('archivo');
                foreach($files as $clave => $file){
                 
                    $path = "/preguntas_frecuentes/";
                    $filename = $traercodigo."".$file->getClientOriginalName();
                     if($file->move(public_path().$path,$filename)){
            
                    $respuesta =    DescripcionFaq::create([
                                'video' => $request->video,
                                "descripcion" =>  $request->respuesta,
                                "archivo" => $file->getClientOriginalName(),
                                'pregunta_id' => $preguntas->preguntasfaq_id,
                                "url" => $traercodigo."".$file->getClientOriginalName()
                            ]);
                    }
                  }
            }
        }
      
        
        if($respuesta){
            return "Se guardo correctamente";
        }else{
            return "No se pudo guardar/actualizar";
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
        //
    }
}
