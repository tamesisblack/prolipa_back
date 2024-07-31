<?php

namespace App\Http\Controllers;

use App\Models\Materialcargar;
use App\Models\Materialarchivo;
use App\Models\Materialunidad;
use App\Models\Materialtema;
use App\Http\Controllers\Controller;
use App\Models\MaterialSubir;
use App\Models\MaterialSubirTemas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use stdClass;

class MaterialcargarController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      if($request->limpiar){
        DB::DELETE("DELETE FROM material_subir WHERE estado = '5'");
      }else{
         $libros= DB::table('libro')
          ->select('libro.nombrelibro','libro.idlibro')
          ->where('estado_idEstado','1')
          ->get();
          $material = DB::select("select m.*, l.nombrelibro
          from material_cargar m, libro l
          where m.id_libro  = l.idlibro 
          ORDER BY m.id DESC
        
         "); 
          return ['material' => $material ,'libros' => $libros] ;
      }
       
    }

   //para traer archivos por asignatura
   public function traer_archivos_asignaturas($asignatura){
  
  
      $material = DB::select("select ma.id_archivo , ma.id_material, ma.nombre_archivo, ma.archivo, ma.url, ma.id_asignatura, a.nombreasignatura
        from material_archivos  ma, asignatura a
  
        where ma.id_asignatura = a.idasignatura 
        and ma.id_asignatura = $asignatura
        ORDER BY ma.id_archivo DESC
      
      "); 
        return $material;

   }
   //para asignar el el archivo al curso
   public function asignar_cursos_archivos(Request $request)
   {   
       $cursos= DB::SELECT("SELECT * FROM ma_cursos_has_archivo cm WHERE cm.codigo_curso = '$request->codigo_curso' AND cm.id_archivo = $request->id_archivo");

       if( empty($cursos) ){
           $juegos= DB::INSERT("INSERT INTO `ma_cursos_has_archivo`(`codigo_curso`, `id_archivo`) VALUES ('$request->codigo_curso', $request->id_archivo)");

           return ["status" => "1", "message" => "Asignado correctamente"];
       }else{
           return ["status" => "0", "message" => "Este material ya se encuentra asignado a este curso"];
       }
       
   }
   //para listar los materiales archivos en el estudiante
   public function archivo_curso(Request $request)
   {
    //  return csrf_token();
      //  $materiales = DB::SELECT("SELECT *
      //   FROM material_archivos m, ma_cursos_has_archivo mc 
      //  WHERE m.id_archivo = mc.id_archivo
      //   AND mc.codigo_curso = '$request->codigo_curso' ");

      //  return $materiales;
       $materiales = DB::SELECT("SELECT *
        FROM material_subir m, ma_cursos_has_archivo mc 
       WHERE m.id = mc.id_archivo
        AND mc.codigo_curso = '$request->codigo_curso' ");

       return $materiales;
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
      // var_dump($request->unidadestemas);
     
  

        try{
          DB::beginTransaction();
          $material = new Materialcargar;
          $material->id_libro = $request->id_libro;
          // $material->id_unidad = $request->id_unidad;
          // $material->id_tema = $request->id_tema;
          $material->save();
          
          //para ingresar los archivos

          $max_size = (int)ini_get('upload_max_filesize')*10240;
          // dd($request);
          $traercodigo = $this->makeid();
          $files = $request->file('archivo');
          foreach($files as $clave => $file){
            // if(Storage::putFileAs('/public/'.'material_cargar'.'/',$file,$traercodigo."".$file->getClientOriginalName())){
      
            //   Materialarchivo::create([
            //               "id_material" => $material->id,
            //               "archivo" => $file->getClientOriginalName(),
            //               "nombre_archivo" => $request->nombre_archivo[$clave],
            //               "url" => $traercodigo."".$file->getClientOriginalName()
            //           ]);
            //   }
              $path = "/material_cargar/";
              $filename = $traercodigo."".$file->getClientOriginalName();
               if($file->move(public_path().$path,$filename)){
      
              Materialarchivo::create([
                          "id_material" => $material->id,
                          "archivo" => $file->getClientOriginalName(),
                          "nombre_archivo" => $request->nombre_archivo[$clave],
                          'id_asignatura' => $request->id_asignatura,
                          "url" => $traercodigo."".$file->getClientOriginalName()
                      ]);
              }
            }
            
        

   
      //para ingresar a la ta temas
            
      $datas = $request->unidadestemas;
      $dataFinally = array();
  
      foreach($datas as $data){
          $data = json_decode($data);
          $temas = $data->temas; //array
          $unidad = $data->unidad; //objeto
            //para agregar en la tabla unidades
              $munidad=new Materialunidad;
              $munidad->id_material=$material->id;
              $munidad->id_unidad=$unidad->id_unidad_libro;
            
              $munidad->save(); 

          foreach($temas as $tema){
              $obj = new stdClass();
              $obj->idUnidad = $unidad->id_unidad_libro;
              $obj->idTema = $tema->id;
  
              array_push($dataFinally,$obj);
          }
      }

         foreach($dataFinally as $item){
       
          Materialtema::create([
              "id_material" =>$material->id,
              "id_tema" => $item->idTema,
              "id_unidad" => $item->idUnidad
          ]);
         
      }
          

          DB::commit();
      }catch(\Exception $e){
        return [
          // "error"=> $e,
          "message"=>"no se  pudo ingresar la informacion".'<br>'.$e,
          "status" => "0",
        ];
        // return "no se pudo ingresar el material".$e;
          DB::rollback();
      }

           return [
          
          "message"=>"se guardo correctamente",
          "status" => "1",
        ];
       
   
        
    }
    //api:post//guardarMaterialSubir
    public function guardarMaterialSubir(Request $request){
      $material = MaterialSubir::findOrFail($request->id);
      $material->libro_id =        $request->libro_id;
      $material->nombre_material = $request->nombre_material;
      $material->descripcion =     $request->descripcion;
      $material->estado =          $request->estado;
      $material->user_created =    $request->user_created;
      $material->save();
      //delete para poder subir de nuevo los temas
      DB::DELETE("DELETE FROM material_subir_temas where material_subir_id = '$request->id'");
      //unidad 1 - temas
      if($request->unidad1Temas != "[]"){
        $u1Temas = new MaterialSubirTemas();
        $u1Temas->material_subir_id = $material->id;
        $u1Temas->unidad            = 1;
        $u1Temas->temas             = $request->unidad1Temas;
        $u1Temas->save();
      }
      //unidad 2 - temas
      if($request->unidad2Temas != "[]"){
      $u2Temas = new MaterialSubirTemas();
      $u2Temas->material_subir_id = $material->id;
      $u2Temas->unidad            = 2;
      $u2Temas->temas             = $request->unidad2Temas;
      $u2Temas->save();
      }
      //unidad 3 - temas
      if($request->unidad3Temas != "[]"){
      $u3Temas = new MaterialSubirTemas();
      $u3Temas->material_subir_id = $material->id;
      $u3Temas->unidad            = 3;
      $u3Temas->temas             = $request->unidad3Temas;
      $u3Temas->save();
      }
      //unidad 4 - temas
      if($request->unidad4Temas != "[]"){
      $u4Temas = new MaterialSubirTemas();
      $u4Temas->material_subir_id = $material->id;
      $u4Temas->unidad            = 4;
      $u4Temas->temas             = $request->unidad4Temas;
      $u4Temas->save();
      }
      //unidad 5 - temas
      if($request->unidad5Temas != "[]"){
      $u5Temas = new MaterialSubirTemas();
      $u5Temas->material_subir_id = $material->id;
      $u5Temas->unidad            = 5;
      $u5Temas->temas             = $request->unidad5Temas;
      $u5Temas->save();
      }
      //unidad 6 - temas
      if($request->unidad6Temas != "[]"){
      $u6Temas = new MaterialSubirTemas();
      $u6Temas->material_subir_id = $material->id;
      $u6Temas->unidad            = 6;
      $u6Temas->temas             = $request->unidad6Temas;
      $u6Temas->save();
      }
      if($material){
        return ["status" => "1","message" => "Se guardo el material correctamente"];
      }else{
        return ["status" => "0","message" => "No se pudo guardar el material"];
      }
    }
    //api::get/getMaterialSubir
    public function getMaterialSubir(Request $request){
      $datos = [];
      $getMaterial = DB::SELECT("SELECT m.*, l.nombrelibro, 
      CONCAT(u.nombres, ' ',u.apellidos) AS editor,a.nombreasignatura,idasignatura
      FROM material_subir m
      LEFT JOIN libro l ON m.libro_id = l.idlibro
      LEFT JOIN asignatura a ON a.idasignatura = l.asignatura_idasignatura
      LEFT JOIN usuario u ON m.user_created = u.idusuario
      ");
      foreach($getMaterial as $key => $item){
        $temas = DB::SELECT("SELECT * FROM material_subir_temas mt
        WHERE mt.material_subir_id = '$item->id'
        ");
        $datos[$key] = [
          "id" =>               $item->id,
          "libro_id" =>         $item->libro_id,
          "idasignatura"=>      $item->idasignatura,
          "nombreasignatura"=>  $item->nombreasignatura,
          "nombre_material" =>  $item->nombre_material,
          "descripcion"=>       $item->descripcion,
          "archivo" =>          $item->archivo,
          "url" =>              $item->url,
          "estado" =>           $item->estado,
          "created_at" =>       $item->created_at,
          "updated_at" =>       $item->updated_at,
          "nombrelibro" =>      $item->nombrelibro,
          "editor" =>           $item->editor,
          "temas" =>            $temas,
        ];   
      }
      return $datos;
    }
    //api:get/material/subir/temas/{id}
    public function verTemas($id){
      $temas = DB::SELECT("SELECT * FROM material_subir_temas mt
      WHERE mt.material_subir_id = '$id'
      ");
      return $temas;
    }

    public function desactivar(Request $request){
        $material =  Materialcargar::findOrFail($request->get('id'));
        
        $material->estado = "0";
        $material->save();
        return response()->json($material);
    }
    public function activar(Request $request){
       $material =  Materialcargar::findOrFail($request->get('id'));

        
        $material->estado = "1";
        $material->save();
        return response()->json($material);
    }

    //para traer las unidades por libro
    public function traerunidades(Request $request){
      //si es por asignaturas
      if($request->byasignaturas){
        $getLibro = DB::SELECT("SELECT l.idlibro AS libro_id FROM libro l
        WHERE l.asignatura_idasignatura = '$request->asignatura_id'
        ");
        $libro = $getLibro[0]->libro_id;
        $getUnits = DB::SELECT("SELECT u.id_unidad_libro,u.unidad as unit,
        CONCAT(u.unidad , ' : ' , u.nombre_unidad ) as  unidad
          FROM unidades_libros u
          WHERE u.id_libro = '$libro'
          AND u.estado = '1'
       ");
      }else{
        $getUnits = DB::SELECT("SELECT u.id_unidad_libro,u.unidad as unit,
        CONCAT(u.unidad , ' : ' , u.nombre_unidad ) as  unidad
          FROM unidades_libros u
          WHERE u.id_libro = '$request->idlibro'
          AND u.estado = '1'
       ");
      }
     
    return  $getUnits;
    }
    public function traertemas(Request $request){
        if($request->id_unidad_libro){
          $unidad = $request->id_unidad_libro;
          $traerTema = DB::table('temas')
          ->select('temas.id','temas.nombre_tema')
          ->where('id_unidad', $unidad)
          ->where('estado','1')
          ->get();
        }else{
          $traerTema = DB::table('temas')
          ->select('temas.id','temas.nombre_tema')
          ->where('estado','1')
          ->get();
        }
      return  $traerTema;
    }
    // para el listado de archivos
    public function show($id){
   
    $material = DB::select("select ma.id_archivo , ma.nombre_archivo, ma.archivo, ma.url, l.nombrelibro
    from material_cargar m, material_archivos  ma, libro l

     where ma.id_material = m.id
     and ma.id_material = $id
     and m.id_libro  = l.idlibro 
 "); 
     return $material;
    }
    //para el listado de unidades
    public function materialunidades($id){
   
      $material = DB::select("select  mu.id, mu.id_material, mu.id_unidad, l.nombrelibro, CONCAT(un.unidad, un.nombre_unidad) as unidad
      from material_cargar m, material_unidades  mu, libro l, unidades_libros un
  
       where mu.id_material = m.id
       and mu.id_material = $id
       and mu.id_unidad = un.id_unidad_libro 
       and m.id_libro  = l.idlibro 
       
      "); 
       return $material;
      }
      //para editar unidades
      public function materialunidadeseditar(Request $request){
   
          try{
            DB::beginTransaction();
            $unidad = Materialunidad::find($request->id);
            $unidad->id_unidad = $request->id_unidad;
            $unidad->save();

            DB::commit();
        }catch(\Exception $e){
          return "no se pudo editar las unidades";
            DB::rollback();
        }
        $unidad;
      }

      //para eliminar la unidades
      public function materialunidadeseliminar(Request $request){

        
        try{
          DB::beginTransaction();

   

          $id_unidad=$request->id_unidad;
          $id_material =$request->id_material;
        

          $libro = DB::table('material_temas')
          ->join('material_unidades','material_temas.id_unidad','=','material_unidades.id_unidad')
        
           ->select('material_temas.*')
           
  
             ->where('material_temas.id_unidad','=',$id_unidad)
             ->where('material_temas.id_material','=',$id_material)
             ->get();
       
         
    

              if(count($libro) <= 0){
               
                   $unidad = Materialunidad::findOrFail($request->id);
                  $unidad->delete();
           
             }else{
              return [
                // "error"=> $e,
                "message"=>"no se  puede eliminar una unidad que tiene temas".'<br>',
                "status" => "0",
              ];
             }
         

              DB::commit();
          }catch(\Exception $e){
            return [
              // "error"=> $e,
              "message"=>"no se  puede eliminar una unidad que tiene temas".'<br>'.$e,
              "status" => "0",
            ];
            
              DB::rollback();
          }
            
        
          return [
          
            "message"=>"Se elimino correctamente la unidad",
            "status" => "1",
          ];
     
      
        }
   
      //para el listado de temas
      public function materialtemas($id){
      
    
         $material = DB::select("select  mt.id, mt.id_tema, l.nombrelibro, t.nombre_tema,  CONCAT(un.unidad, un.nombre_unidad) as unidad
         from material_cargar m, material_temas  mt, libro l, temas t,unidades_libros un
     
          where mt.id_material = m.id
          and mt.id_material = $id
          and mt.id_tema = t.id 
          and m.id_libro  = l.idlibro 
          and mt.id_unidad = un.id_unidad_libro 
       
      
          
          
 
         "); 
          return $material;
        }

         //para editar los temas
      public function materialtemaseditar(Request $request){
   
        try{
          DB::beginTransaction();
          $tema = Materialtema::find($request->id);
          $tema->id_tema = $request->id_tema;
          $tema->save();

          DB::commit();
      }catch(\Exception $e){
        return "no se pudo editar el tema";
          DB::rollback();
      }
      $tema;
    }

     //para eliminar la unidades
     public function materialtemaseliminar(Request $request){

      try{
        DB::beginTransaction();
        $tema = Materialtema::findOrFail($request->id);
        $tema->delete();

          DB::commit();
      }catch(\Exception $e){
        return "no se pudo eliminar el tema";
           DB::rollback();
     }
  }
  //para eliminar archivos
  public function eliminar(Request $request){
    $archivo = Materialarchivo::findOrFail($request->id_archivo);
    $filename = $archivo->url;
  //  if(Storage::delete('/public/'.'material_cargar'.'/'.$archivo->url)){
 
  //  }
  //  $path = "/material_cargar/";
  //  if(unlink(public_path().$path,$filename)){
  //     $archivo->delete();
     
   

   if(file_exists('material_cargar/'.$filename) ){
    unlink('material_cargar/'.$filename);
      $archivo->delete();

}
    return response()->json($archivo);
  
    
  }

   
}
