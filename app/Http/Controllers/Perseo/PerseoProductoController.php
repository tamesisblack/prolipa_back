<?php

namespace App\Http\Controllers\Perseo;

use App\Http\Controllers\Controller;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;

class PerseoProductoController extends Controller
{
    use TraitPedidosGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //api:post/perseo/productos/productos_crear
    public function productos_crear(Request $request)
    {
        try {
            $formData = [
                "registros" => [
                    [
                        "productos" => [
                            "productocodigo" => "TEST 2 OBJ",
                            "descripcion" => "test MARCATEST MODEL TEST",
                            "sri_tipos_ivas_codigo" => 2,
                            "productos_lineasid" => 300,
                            "productos_categoriasid" => 1,
                            "sri_impuestosid" => 21,
                            "productos_subcategoriasid" => 1,
                            "productos_subgruposid" => 1,
                            "estado" => true,
                            "venta" => true,
                            "existenciastotales" => 0,
                            "controlnegativos" => true,
                            "controlprecios" => true,
                            "servicio" => false,
                            "bien" => false,
                            "series" => false,
                            "vehiculos" => false,
                            "fichatecnica" => "",
                            "costoactual" => 0,
                            "costoestandar" => 0,
                            "costoultimacompra" => 0,
                            "fechaultimacompra" => "",
                            "observaciones" => "",
                            "unidadinterna" => 1,
                            "unidadsuperior" => 0,
                            "unidadinferior" => 0,
                            "unidadcompra" => 1,
                            "unidadventa" => 1,
                            "factorsuperior" => 0,
                            "factorinferior" => 0,
                            "usuariocreacion" => "CYUNGA",
                            "fechacreacion" => "20240411100330",
                            "tarifas" => [
                                [
                                    "tarifasid" => 1,
                                    "medidasid" => 100,
                                    "medidas" => "Unidad",
                                    "precioiva" => 23,
                                    "precio" => 20,
                                    "margen" => 0,
                                    "utilidad" => 0,
                                    "descuento" => 0,
                                    "escala" => 0
                                ],
                                [
                                    "tarifasid" => 2,
                                    "medidasid" => 100,
                                    "medidas" => "Unidad",
                                    "precioiva" => 24.15,
                                    "precio" => 21,
                                    "margen" => 0,
                                    "utilidad" => 0,
                                    "descuento" => 0,
                                    "escala" => 0
                                ]
                            ]
                        ]
                    ]
                ]
            ];


            // $formData = [
            //     "registros" => [
            //         [
            //             "productos" => [
            //                 "productocodigo"                        => $request->productocodigo,
            //                 "descripcion"                           => $request->descripcion,
            //                 "sri_tipos_ivas_codigo"                 => $request->sri_tipos_ivas_codigo,
            //                 "productos_lineasid"                    => $request->productos_lineasid,
            //                 "productos_categoriasid"                => $request->productos_categoriasid,
            //                 "sri_impuestosid"                       => $request->sri_impuestosid,
            //                 "productos_subcategoriasid"             => $request->productos_subcategoriasid,
            //                 "productos_subgruposid"                 => $request->productos_subgruposid,
            //                 "estado"                                => $request->estado,
            //                 "venta"                                 => $request->venta,
            //                 "existenciastotales"                    => $request->existenciastotales,
            //                 "controlnegativos"                      => $request->controlnegativos,
            //                 "controlprecios"                        => $request->controlprecios,
            //                 "servicio"                              => $request->servicio,
            //                 "bien"                                  => $request->bien,
            //                 "series"                                => $request->series,
            //                 "vehiculos"                             => $request->vehiculos,
            //                 "fichatecnica"                          => $request->fichatecnica,
            //                 "costoactual"                           => $request->costoactual,
            //                 "costoestandar"                         => $request->costoestandar,
            //                 "costoultimacompra"                     => $request->costoultimacompra,
            //                 "fechaultimacompra"                     => $request->fechaultimacompra,
            //                 "observaciones"                         => $request->observaciones,
            //                 "unidadinterna"                         => $request->unidadinterna,
            //                 "unidadsuperior"                        => $request->unidadsuperior,
            //                 "unidadinferior"                        => $request->unidadinferior,
            //                 "unidadcompra"                          => $request->unidadcompra,
            //                 "unidadventa"                           => $request->unidadventa,
            //                 "factorsuperior"                        => $request->factorsuperior,
            //                 "factorinferior"                        => $request->factorinferior,
            //                 "usuariocreacion"                       => $request->usuariocreacion,
            //                 "fechacreacion"                         => $request->fechacreacion,
            //                 "tarifas"                               => json_decode($request->tarifas)
            //             ]
            //         ]
            //     ]
            // ];
            $url        = "productos_crear";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //api:post/perseo/productos/productos_editar
    public function productos_editar(Request $request){
        try{
            $formData = [
                "registros" => [
                    [
                        "productos" => [
                            "productosid" => 7,
                            "productocodigo" => "P000000007",
                            "descripcion" => "productos de prueba modificado",
                            "sri_codigo_impuestos" => "312",
                            "barras" => "7301122384577",
                            "sri_tipos_ivas_codigo" => "2",
                            "productos_lineasid" => 1,
                            "productos_categoriasid" => 1,
                            "productos_subcategoriasid" => 1,
                            "productos_subgruposid" => 1,
                            "estado" => true,
                            "venta" => true,
                            "existenciastotales" => 0,
                            "controlnegativos" => true,
                            "controlprecios" => true,
                            "servicio" => false,
                            "bien" => false,
                            "series" => false,
                            "vehiculos" => false,
                            "fichatecnica" => "",
                            "costoactual" => 0.75,
                            "costoestandar" => 0.75,
                            "costoultimacompra" => 0.75,
                            "fechaultimacompra" => "",
                            "observaciones" => "",
                            "unidadinterna" => 1,
                            "unidadsuperior" => 0,
                            "unidadinferior" => 0,
                            "unidadcompra" => 1,
                            "unidadventa" => 1,
                            "factorsuperior" => 0,
                            "factorinferior" => 0,
                            "usuariocreacion" => "PERSEO",
                            "fechacreacion" => "20190730112354",
                            "tarifas" => [
                                [
                                    "tarifasid" => 1,
                                    "medidasid" => 1,
                                    "medidas" => "Unidad",
                                    "precioiva" => 33.6,
                                    "precio" => 30,
                                    "margen" => 0,
                                    "utilidad" => 0,
                                    "descuento" => 0,
                                    "factor" => 1,
                                    "escala" => 0
                                ],
                                [
                                    "tarifasid" => 2,
                                    "medidasid" => 1,
                                    "medidas" => "Unidad",
                                    "precioiva" => 40,
                                    "precio" => 35.714286,
                                    "margen" => 0,
                                    "utilidad" => 0,
                                    "descuento" => 0,
                                    "factor" => 1,
                                    "escala" => 0
                                ],
                                [
                                    "tarifasid" => 3,
                                    "medidasid" => 1,
                                    "medidas" => "Unidad",
                                    "precioiva" => 39.2,
                                    "precio" => 35,
                                    "margen" => 0,
                                    "utilidad" => 0,
                                    "descuento" => 0,
                                    "factor" => 1,
                                    "escala" => 0
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            // $formData = [
            //     "registros" => [
            //         [
            //             "productos" => [
            //                 "productosid"                           => $request->productosid,
            //                 "productocodigo"                        => $request->productocodigo,
            //                 "descripcion"                           => $request->descripcion,
            //                 "sri_codigo_impuestos"                  => $request->sri_codigo_impuestos,
            //                 "barras"                                => $request->barras,
            //                 "sri_tipos_ivas_codigo"                 => $request->sri_tipos_ivas_codigo,
            //                 "productos_lineasid"                    => $request->productos_lineasid,
            //                 "productos_categoriasid"                => $request->productos_categoriasid,
            //                 "productos_subcategoriasid"             => $request->productos_subcategoriasid,
            //                 "productos_subgruposid"                 => $request->productos_subgruposid,
            //                 "estado"                                => $request->estado,
            //                 "venta"                                 => $request->venta,
            //                 "existenciastotales"                    => $request->existenciastotales,
            //                 "controlnegativos"                      => $request->controlnegativos,
            //                 "controlprecios"                        => $request->controlprecios,
            //                 "servicio"                              => $request->servicio,
            //                 "bien"                                  => $request->bien,
            //                 "series"                                => $request->series,
            //                 "vehiculos"                             => $request->vehiculos,
            //                 "fichatecnica"                          => $request->fichatecnica,
            //                 "costoactual"                           => $request->costoactual,
            //                 "costoestandar"                         => $request->costoestandar,
            //                 "costoultimacompra"                     => $request->costoultimacompra,
            //                 "fechaultimacompra"                     => $request->fechaultimacompra,
            //                 "observaciones"                         => $request->observaciones,
            //                 "unidadinterna"                         => $request->unidadinterna,
            //                 "unidadsuperior"                        => $request->unidadsuperior,
            //                 "unidadinferior"                        => $request->unidadinferior,
            //                 "unidadcompra"                          => $request->unidadcompra,
            //                 "unidadventa"                           => $request->unidadventa,
            //                 "factorsuperior"                        => $request->factorsuperior,
            //                 "factorinferior"                        => $request->factorinferior,
            //                 "usuariocreacion"                       => $request->usuariocreacion,
            //                 "fechacreacion"                         => $request->fechacreacion,
            //                 "tarifas"                               => json_decode($request->tarifas)
            //             ]
            //         ]
            //     ]
            // ];
            $url        = "productos_editar";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        }catch(\Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Nota: Antes de realizar el proceso de eliminación, se verifique que el producto no tenga documentos asociados, si es así envía un mensaje de error en formato JSON, caso contrario retorna un texto fijo con el siguiente enunciado: Registro eliminado correctamente en el sistema.
    */
    //api:post/perseo/productos/productos_eliminar
    /*
        productosid: id del producto a eliminar.
        empresaid: Opcional, id de la empresa en la que se esta trabajando.
        usuarioid: Opcional, id del usuario con que accedió al sistema.
        Nota: Los dos parámetros opcionales, son utilizados para registrar la auditoria en el sistema contable.
    */
    public function productos_eliminar(Request $request){
        try {
            $formData = [
                "productosid"   => 9,
                "empresaid"     => 2,
                "usuarioid"     => 2
            ];
            // $formData = [
            //     "productosid"   => $request->productosid,
            //     "empresaid"     => $request->empresaid,
            //     "usuarioid"     => $request->usuarioid
            // ];
            $url        = "productos_eliminar";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
    Devuelve todos los productos almacenados en el sistema central, debe estar configurado en la opcion de estado ecommerce: como Disponible para ambos o Solo en venta publico y que su estado sea activo , de acuerdo con el parámetro que se haya enviado.
    Tomar en cuenta que al realizar una búsqueda personalizada, se debe enviar solo 1 de los 4 parámetros solicitados; es decir, que realiza la busque por:
    */
    //api:post/perseo/productos/productos_consulta
    /*
        productosid: Si se conoce el id del producto..
        productocodigo: Código generado al crear el cliente.
        barras: Código de barra del producto.
        contenido: Descripción del producto. Se puede enviar el nombre completo o una parte para buscar coincidencia.
    */
    public function productos_consulta(Request $request){
        try {
            $formData = [
                // "productosid"=>"67",
                "productocodigo"=>"GSMC2",
                // "barras"=>"6181220422071",
                // "contenido"=>"producto"

                // "productosid_nuevo"=>1324,
                // "productos_codigo"=>"SMLL2",
                // "barras"=>"6181220422071"
            ];
            // $formData = [
            //     "productosid"    => $request->productosid,
            //     "productocodigo" => $request->productocodigo,
            //     "barras"         => $request->barras,
            //     "contenido"      => $request->contenido
            // ];
            $url        = "productos_consulta";
            $process    = $this->tr_PerseoPost($url, $formData,3);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Nota: Devuelve todas las imágenes de los productos almacenados que exista. Se puede realizar la busque por el parámetro id del producto.
        *Para agregar las imágenes en el Json, se envían codificadas en formato encodeBASE64noCR
    */
    //api:post/perseo/productos/productos_imagenes_consulta
    public function productos_imagenes_consulta(Request $request){
        try {
            $formData = [
                "productosid" => 1
            ];
            // $formData = [
            //     "productosid" => $request->productosid
            // ];
            $url        = "productos_imagenes_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las líneas creadas para clasificar a los productos.
    */
    //api:post/perseo/productos/productos_lineas_consulta
    public function productos_lineas_consulta(Request $request){
        try {
            $formData = [];
            $url        = "productos_lineas_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las categorías creadas para clasificar a los productos. El formato con que devuelve el resultado es tipo JSON.
     */
    //api:post/perseo/productos/productos_categorias_consulta
    public function productos_categorias_consulta(Request $request){
        try {
            $formData = [];
            $url        = "productos_categorias_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de las subcategorías creadas para clasificar a los productos. El formato con que devuelve el resultado es tipo JSON.
    */
    //api:post/perseo/productos/productos_subcategorias_consulta
    public function productos_subcategorias_consulta(Request $request){
        try {
            $formData = [];
            $url        = "productos_subcategorias_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Este procedimiento se utiliza para extraer el listado de los subgrupos creados para clasificar a los productos. El formato con que devuelve el resultado es tipo JSON.
    */
    //api:post/perseo/productos/productos_subgrupos_consulta
    public function productos_subgrupos_consulta(Request $request){
        try {
            $formData = [];
            $url        = "productos_subgrupos_consulta";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        *Consulta la existencia por almacenes de los productos.
        *Se puede utilizar para consultar un producto especifico, filtrando por id; ó de manera general para filtrar todos los productos
    */
    //api:post/perseo/productos/existencia_producto
    public function existencia_producto(Request $request){
        try {
            $formData = [
                "productosid" => 1
            ];
            // $formData = [
            //     "productosid" => $request->productosid
            // ];
            $url        = "existencia_producto";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //api:get/perseo/productos/producto
    public function index()
    {
        //
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
        //
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
