<?php

namespace App\Http\Controllers;

use App\Models\Temporada;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Institucion;
use App\Models\Ciudad;
use App\Models\HistoricoContratos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;



class TemporadaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //api para el get para milton
    public function temporadaDatos(){
        $temporada = DB::select("select t.* 
        from temporadas t 
       
     "); 

     return $temporada;
    }

    //api institucion para milton

    public function instituciones_facturacion(){
 
    $grupo ="11";
    $estado ="1";
         $institucion_sin_asesor = DB::select("select i.idInstitucion, i.direccionInstitucion, i.cod_contrato, i.telefonoInstitucion, r.nombreregion  as region, c.nombre as ciudad,  u.nombres, u.apellidos
        from institucion i, region r, ciudad c,  usuario u
        where i.region_idregion  = r.idregion
        and i.ciudad_id  =  c.idciudad
        and i.vendedorInstitucion = u.cedula
        and u.id_group <> $grupo
       and i.estado_idEstado  = $estado
     ");
    //para traer las instituciones con asesor
       
    $institucion_con_asesor = DB::select("select i.idInstitucion, i.direccionInstitucion, i.cod_contrato, i.telefonoInstitucion, r.nombreregion  as region, c.nombre as ciudad,  u.nombres, u.apellidos
    from institucion i, region r, ciudad c,  usuario u
    where i.region_idregion  = r.idregion
    and i.ciudad_id  =  c.idciudad
    and i.vendedorInstitucion = u.cedula
    and u.id_group = $grupo
   and i.estado_idEstado  = $estado
 ");

     return ["institucion_con_asesor"=> $institucion_con_asesor,"institucion_sin_asesor"=>$institucion_sin_asesor];
    }


    //api para un formulario de prueba para  milton
    public function crearliquidacion(Request $request){
    //    $user = Auth::user();
    //     return $user; 
         return view('testearapis.apitemporada');
    }  
    public function eliminarTemporada(Request $request){
     

        $id = $request->get('id_temporada');
        Temporada::findOrFail($id)->delete();
    }
    //api para miton vea los numeros de contratos
    public function show($contrato){
       

         $contratoid = $contrato;

        $contratos =  DB::table('temporadas')
              ->where('contrato', $contrato)
           
             ->get();


        return $contratos;
    }


    public function index(Request $request)
    {
        // $asesores =  DB::table('usuario')
        //         ->where('id_group', '11')
        //         ->where('estado_idEstado','1')
        //         ->get();


        
        $asesores= DB::table('usuario')
            ->select(DB::raw('CONCAT(usuario.nombres , " " , usuario.apellidos ) as asesornombres'),'usuario.idusuario','usuario.nombres','usuario.cedula')
            ->where('id_group', '11')
            ->where('estado_idEstado','1')
            ->get();
        
         $profesores= DB::table('usuario')
            ->select(DB::raw('CONCAT(usuario.nombres , " " , usuario.apellidos ) as  profesornombres'),'usuario.idusuario','usuario.nombres','usuario.cedula')
            ->where('id_group', '6')
            ->where('estado_idEstado','1')
            ->get();
        
       
        $ciudad = Ciudad::all();

         $institucion = Institucion::where('estado_idEstado', '=',1)->get();
      
   

         $temporada = DB::select("select t.* 
            from temporadas t 
           

         "); 
       
          return ['temporada' => $temporada, 'asesores'=> $asesores,'profesores' => $profesores, 'ciudad' => $ciudad, 'listainstitucion' => $institucion];

    }


   
    //para traer las instituciones por ciudad
    public function traerInstitucion(Request $request){
        $ciudad = $request->ciudad_id;
        $traerInstitucion = DB::table('institucion')
        ->select('institucion.idInstitucion','institucion.nombreInstitucion','institucion.region_idregion')
        ->where('ciudad_id', $ciudad)
        ->where('estado_idEstado','1')
        ->get();
      return  $traerInstitucion;
   
    
    }
    //para traer los profesores por institucion 
    public function traerprofesores(Request $request){
         $institucion = $request->idInstitucion;
     
        $profesores= DB::table('usuario')
        ->select(DB::raw('CONCAT(usuario.nombres , " " , usuario.apellidos ) as  profesornombres'),'usuario.idusuario','usuario.nombres','usuario.cedula')
        ->where('id_group', '6')
        ->where('institucion_idInstitucion',$institucion)
        ->where('estado_idEstado','1')
        ->get();
        return $profesores;
     
    }
    //para traer los periodos por institucion
    public function traerperiodos(Request $request){
      
        $periodo = $request->region_idregion;
        $estado = $request->condicion;
        $traerPeriodo = DB::table('periodoescolar')
        ->select('periodoescolar.idperiodoescolar',DB::raw('CONCAT(periodoescolar.fecha_inicial , " a " , periodoescolar.fecha_final," | " ,periodoescolar.descripcion ) as  periodo'),'periodoescolar.region_idregion')
        ->OrderBy('periodoescolar.idperiodoescolar','desc') 
        ->where('region_idregion', $periodo)
        ->where('periodoescolar.estado',$estado)
        
        ->get();
         return  $traerPeriodo;
    }


    public function store(Request $request)
    {

     
      
         if( $request->id ){
            $temporada = Temporada::find($request->id);
            $temporada->contrato = $request->contrato;
            $temporada->year = $request->year;
            $temporada->ciudad = $request->ciudad;
            $temporada->temporada = $request->temporada;
            $temporada->id_asesor = $request->id_asesor;
            $temporada->cedula_asesor = $request->cedula_asesor;
            
            if($request->id_profesor =="undefined"){
                $temporada->id_profesor = "0";
            }else{
                $temporada->id_profesor = $request->id_profesor;
            }
           
            $temporada->idInstitucion  = $request->idInstitucion;

            //para buscar  la institucion  y sacar su periodo
                //para buscar el contador de verificacion  de la tabla verificacion
                $buscarcodigoPeriodo= DB::table('periodoescolar_has_institucion')
                ->select('periodoescolar_has_institucion.periodoescolar_idperiodoescolar')
               ->where('institucion_idInstitucion', $request->idInstitucion)
           
       
               ->get();
   
   
               $data_obtenerperiodo = $buscarcodigoPeriodo[0]->periodoescolar_idperiodoescolar;
         
            //para ingresar el historico contratos
            $historico = new HistoricoContratos;
            $historico->contrato = $request->contrato;
            $historico->institucion=  $request->idInstitucion;
            $historico->periodo_id=  $data_obtenerperiodo;
            $historico->save();

    
        }else{
        

            $temporada = new Temporada();
            $temporada->contrato = $request->contrato;
            $temporada->year = $request->year;
            $temporada->ciudad = $request->ciudad;
            $temporada->temporada = $request->temporada;
            if($request->id_profesor =="undefined"){
                $temporada->id_profesor = "0";
            }else{
                $temporada->id_profesor = $request->id_profesor;
            }

            if($request->temporal_cedula_docente =="undefined"){
                $temporada->temporal_cedula_docente = "0";
            }else{
                $temporada->temporal_cedula_docente = $request->temporal_cedula_docente;
            }

            if($request->temporal_nombre_docente =="undefined"){
                $temporada->temporal_nombre_docente = "0";
            }else{
                $temporada->temporal_nombre_docente = $request->temporal_nombre_docente;
            }
            
           
            $temporada->idInstitucion  = $request->idInstitucion;
            $temporada->temporal_institucion  = $request->temporal_institucion;
            $temporada->id_asesor = $request->id_asesor;
            $temporada->cedula_asesor = $request->cedula_asesor;
            $temporada->nombre_asesor = $request->nombre_asesor;
            
        }


        
       
        $temporada->save();

        return $temporada;
    }
    //api para que los asesores puedan ver sus contratos
    public function asesorcontratos(Request $request){
        $cedula = $request->cedula;

          
        $temporadas= DB::table('temporadas')
            ->select('temporadas.*')
            ->where('cedula_asesor', $cedula)
            ->where('estado','1')
            ->get();

        return $temporadas;
        
    }
    //api para mostrar  las liquidaciones para milton //api:showliquidacion/{contrato}
    public function showLiquidacion($contrato){

        $temporadas= DB::table('temporadas')
            ->select('temporadas.*')
            ->where('contrato', $contrato)
            ->where('estado','1')
            ->get();

        $buscarContrato= DB::table('temporadas')
            ->select('temporadas.idInstitucion')
            ->where('contrato', $contrato)
            ->where('estado','1')
            ->get();
        
  

      
        if($data_obtenerinstitucion = $buscarContrato[0]->idInstitucion == "0"){
            return "El contrato existe pero no se han seleccionado bien la institucion";
        }else{
            $data_obtenerinstitucion = $buscarContrato[0]->idInstitucion;
            
      

            $codigos_libros = DB::select("select COUNT(c.libro_idlibro) as cantidad , c.id_periodo, c.libro_idlibro, c.serie, c.contrato, c.id_periodo,l.nombre as libro, l.codigo_liquidacion, u.institucion_idInstitucion 
            from codigoslibros c, usuario u, libros_series l
            where c.idusuario  = u.idusuario  
            and c.libro_idlibro   = l.idLibro 
            and c.contrato LIKE '$contrato%'
            GROUP BY  c.libro_idlibro, l.nombre  ,c.serie, c.contrato, c.id_periodo,l.codigo_liquidacion, u.institucion_idInstitucion 
        
        ");
  
        }
  
         return ['temporadas'=>$temporadas,'codigos_libros' => $codigos_libros];
        
       

    }


    
    // //api para  hacer la liquidacion
    // public function liquidacion(Request $request){

    //   $temporada = Temporada::where('id_temporada',$request->id_Temporada)->get();

    //   $codigos_libros = DB::SELECT("(SELECT COUNT(c.codigo) as cantidad, GROUP_CONCAT(DISTINCT c.serie) as serie, (SELECT l.nombrelibro FROM libro l WHERE l.idlibro = (GROUP_CONCAT(DISTINCT c.libro_idlibro)) ) as libro, GROUP_CONCAT(DISTINCT (SELECT ciudad.nombre FROM ciudad WHERE ciudad.idciudad = i.ciudad_id) ) as ciudad, GROUP_CONCAT(DISTINCT (SELECT GROUP_CONCAT(usuario.nombres,' ',usuario.apellidos) as vendedor FROM usuario WHERE usuario.cedula = (SELECT institucion.vendedorInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) ) as asesor, GROUP_CONCAT(DISTINCT (SELECT i.nombreInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) as institucion FROM codigoslibros c, usuario u, institucion i WHERE c.idusuario = u.idusuario AND u.institucion_idInstitucion = i.idInstitucion AND i.idInstitucion = $request->id AND c.updated_at BETWEEN CAST('$request->fromdate' AS DATE) AND CAST('$request->todate' AS DATE) AND c.codigo not like '%plus%' GROUP BY c.libro_idlibro ORDER BY `c`.`updated_at`  DESC) UNION (SELECT COUNT(c.codigo) as cantidad, GROUP_CONCAT(DISTINCT c.serie, ' PLUS') as serie, (SELECT l.nombrelibro FROM libro l WHERE l.idlibro = (GROUP_CONCAT(DISTINCT c.libro_idlibro)) ) as libro, GROUP_CONCAT(DISTINCT (SELECT ciudad.nombre FROM ciudad WHERE ciudad.idciudad = i.ciudad_id) ) as ciudad, GROUP_CONCAT(DISTINCT (SELECT GROUP_CONCAT(usuario.nombres,' ',usuario.apellidos) as vendedor FROM usuario WHERE usuario.cedula = (SELECT institucion.vendedorInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) ) as asesor, GROUP_CONCAT(DISTINCT (SELECT i.nombreInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) as institucion FROM codigoslibros c, usuario u, institucion i WHERE c.idusuario = u.idusuario AND u.institucion_idInstitucion = i.idInstitucion AND i.idInstitucion = $request->id AND c.updated_at BETWEEN CAST('$request->fromdate' AS DATE) AND CAST('$request->todate' AS DATE) AND c.codigo like '%plus%' GROUP BY c.libro_idlibro ORDER BY `c`.`updated_at`  DESC)");



    //         return ['temporada'=>$temporada,'codigos_libros' => $codigos_libros];



    // }


//     //api para MILTON
//     public function liquidacionApi(Request $request){

//       $temporada = Temporada::where('contrato',$request->contrato)->get();
//       // $institucion_nombre = 'UNIDAD EDUCATIVA PARTICULAR JOHN BELLERS';
//       $buscarinstitucion = "UNIDAD EDUCATIVA PARTICULAR JOHN BELLERS";
//       // $institucion_nombre = $request->get('institucion');
//         // $temporada = DB::select("select t.* ,i.idInstitucion , i.nombreInstitucion, p.nombres as profesor, a.nombres as asesor, a.idusuario , a.nombres , a.apellidos, p.idusuario  as idusuarios, p.apellidos as papellido, p.cedula as pcedula
//         //     from temporadas t,institucion  i, usuario p , usuario a
//         //     where t.idInstitucion  = i.idInstitucion 
//         //     and t.id_profesor = p.idusuario 
//         //     and t.id_asesor = a.idusuario 
//         //     and t.nombre_asesor LIKE '%$buscar%'

//         //  ");



//  $codigos_libros = DB::SELECT("(SELECT COUNT(c.codigo) as cantidad, GROUP_CONCAT(DISTINCT c.serie) as serie, (SELECT l.nombrelibro FROM libro l WHERE l.idlibro = (GROUP_CONCAT(DISTINCT c.libro_idlibro)) ) as libro, GROUP_CONCAT(DISTINCT (SELECT ciudad.nombre FROM ciudad WHERE ciudad.idciudad = i.ciudad_id) ) as ciudad, GROUP_CONCAT(DISTINCT (SELECT GROUP_CONCAT(usuario.nombres,' ',usuario.apellidos) as vendedor FROM usuario WHERE usuario.cedula = (SELECT institucion.vendedorInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) ) as asesor, GROUP_CONCAT(DISTINCT (SELECT i.nombreInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) as institucion FROM codigoslibros c, usuario u, institucion i WHERE c.idusuario = u.idusuario AND u.institucion_idInstitucion = i.idInstitucion AND i.nombreInstitucion  = '$buscarinstitucion' AND c.updated_at BETWEEN CAST('$request->fromdate' AS DATE) AND CAST('$request->todate' AS DATE) AND c.codigo not like '%plus%' GROUP BY c.libro_idlibro ORDER BY `c`.`updated_at`  DESC) UNION (SELECT COUNT(c.codigo) as cantidad, GROUP_CONCAT(DISTINCT c.serie, ' PLUS') as serie, (SELECT l.nombrelibro FROM libro l WHERE l.idlibro = (GROUP_CONCAT(DISTINCT c.libro_idlibro)) ) as libro, GROUP_CONCAT(DISTINCT (SELECT ciudad.nombre FROM ciudad WHERE ciudad.idciudad = i.ciudad_id) ) as ciudad, GROUP_CONCAT(DISTINCT (SELECT GROUP_CONCAT(usuario.nombres,' ',usuario.apellidos) as vendedor FROM usuario WHERE usuario.cedula = (SELECT institucion.vendedorInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) ) as asesor, GROUP_CONCAT(DISTINCT (SELECT i.nombreInstitucion FROM institucion WHERE institucion.idInstitucion = i.idInstitucion ) ) as institucion FROM codigoslibros c, usuario u, institucion i WHERE c.idusuario = u.idusuario AND u.institucion_idInstitucion = i.idInstitucion AND i.nombreInstitucion  = '$buscarinstitucion' AND c.updated_at BETWEEN CAST('$request->fromdate' AS DATE) AND CAST('$request->todate' AS DATE) AND c.codigo like '%plus%' GROUP BY c.libro_idlibro ORDER BY `c`.`updated_at`  DESC)");



//             return ['temporada'=>$temporada,'codigos_libros' => $codigos_libros];



//     }

    //Api para milton para nos envia la data y nos guarde en nuestra bd
    public function generarApiTemporada(Request $request){

        $verificar_contrato = $request->contrato;
        $verificarcontratos = DB::table('temporadas')
        ->select('temporadas.contrato','temporadas.year')
     
        ->where('temporadas.contrato','=',$verificar_contrato)
        ->get();

        if(count($verificarcontratos) <= 0){
         
        $temporada = new Temporada();
        $temporada->contrato = $request->contrato; 
        $temporada->year = $request->year; 
        $temporada->ciudad = $request->ciudad; 
        $temporada->temporada = $request->temporada; 
        $temporada->temporal_nombre_docente = $request->temporal_nombre_docente; 
        $temporada->temporal_cedula_docente = $request->temporal_cedula_docente; 
        $temporada->temporal_institucion = $request->temporal_institucion; 
        $temporada->nombre_asesor = $request->nombre_asesor;
        //campos a null
        $temporada->id_profesor= "0";
        $temporada->id_asesor= "0";
        $temporada->idInstitucion= "0";
        $temporada->cedula_asesor = "0";
        $temporada->save();

        return response()->json($temporada);
            
        }else{
            return "ya existe el contrato";
        }
      
    }

   
    

     public function desactivar(Request $request)
    {
       // dd($request->get('id_Usuario'));
       
        
            
       
        $temporada =  Temporada::findOrFail($request->get('id_temporada'));
     
        
      
        $temporada->estado = 2;
        $temporada->save();
      
        return response()->json($temporada);
    }

     public function activar(Request $request)
    {
       
        $temporada =  Temporada::findOrFail($request->get('id_temporada'));
        
        $temporada->estado = 1;
        $temporada->save();
        return response()->json($temporada);
    }
    // funcion para agregar el docente a la vista de temporadas
    public function agregardocente(Request $request){
        $docente = new Usuario();
        $docente->cedula = $request->cedula;
        $docente->nombres = $request->nombres;
        $docente->apellidos = $request->apellidos;
        $docente->email = $request->email;
        $docente->name_usuario = $request->name_usuario;
        $docente->password=sha1(md5($request->cedula));
        $docente->id_group = 6;
        $docente->institucion_idInstitucion  = $request->institucion_idInstitucion;
        $docente->save();

        return $docente;
    }
}
