<?php

namespace App\Http\Controllers\Perseo;

use App\Http\Controllers\Controller;
use App\Traits\Pedidos\TraitPedidosGeneral;
use Illuminate\Http\Request;

class PerseoClienteController extends Controller
{
    use TraitPedidosGeneral;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /*
        Nota: Si el cliente ya existe, devuelve el id y código del cliente con que se encuentra registrado en el sistema.
    */
    //api:get/perseo/clientes/clientes_crear
    public function clientes_crear(Request $request)
    {
        try {
            $formData = [
                "registros" => [
                    [
                        "clientes" => [
                            "clientesid" => 2,
                            "clientescodigo" => "CL00000003",
                            "codigocontable" => "1.1.02.05.01",
                            "clientes_gruposid" => 1,
                            "provinciasid" => "09",
                            "ciudadesid" => "0901",
                            "razonsocial" => " JOSEFINA ROSARIO SUING NAGUA",
                            "parroquiasid" => "090111",
                            "clientes_zonasid" => 1,
                            "nombrecomercial" => " JOSEFINA ROSARIO SUING NAGUA",
                            "direccion" => "MITAD DEL MUNDO",
                            "identificacion" => "1100411824",
                            "tipoidentificacion" => "C",
                            "email" => "test@tmail.com",
                            "telefono1" => "9999999999",
                            "telefono2" => "",
                            "telefono3" => "",
                            "vendedoresid" => 3,
                            "cobradoresid" => 3,
                            "creditocupo" => 10000,
                            "creditodias" => 30,
                            "estado" => true,
                            "tarifasid" => 1,
                            "forma_pago_empresaid" => 1,
                            "ordenvisita" => 0,
                            "latitud" => "",
                            "longitud" => "",
                            "usuariocreacion" => "PERSEO",
                            "fechacreacion" => "20190730111642",
                            "fechamodificacion" => "20190620113822"
                        ]
                    ]
                ]
            ];
            $url        = "clientes_crear";
            $process    = $this->tr_SolinfaPost($url, $formData,2);
            // $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //api:get/perseo/clientes/clientes_editar
    public function clientes_editar(Request $request)
    {
        // "codigocontable" => "1.1.02.05.01", PRODUCCION PROLIPA
        // "codigocontable" => "1.01.02.01.01", LOCAL PROLIPA
        try {
            $formData = [
                "registros" => [
                    [
                        "clientes" => [
                            "clientesid" => 3,
                            "clientescodigo" => "CL00000003",
                            "codigocontable" => "1.01.02.01.01",
                            "clientes_gruposid" => 1,
                            "provinciasid" => "09",
                            "ciudadesid" => "0901",
                            "razonsocial" => "QUINTERO MARQUEZ JOSE GABRIEL",
                            "parroquiasid" => "090111",
                            "clientes_zonasid" => 1,
                            "clientes_rutasid" => 1,
                            "nombrecomercial" => "",
                            "direccion" => "SANTO DOMINGO",
                            "identificacion" => "0800374142001",
                            "tipoidentificacion" => "C",
                            "email" => "pinta@tmail.com",
                            "telefono1" => "0993250851",
                            "telefono2" => "0996314786",
                            "telefono3" => "",
                            "vendedoresid" => 3,
                            "cobradoresid" => 3,
                            "creditocupo" => 10000,
                            "creditodias" => 30,
                            "estado" => true,
                            "tarifasid" => 1,
                            "forma_pago_empresaid" => 1,
                            "ordenvisita" => 0,
                            "latitud" => "",
                            "longitud" => "",
                            "usuariomodificacion" => "PERSEO",
                            "fechamodificacion" => "20190620113822"
                        ]
                    ]
                ]
            ];

            // $formData = [
            //     "registros" => [
            //         [
            //             "clientes" => [
            //                 "clientesid"                    => $request->clientesid,
            //                 "clientescodigo"                => $request->clientescodigo,
            //                 "codigocontable"                => $request->codigocontable,
            //                 "clientes_gruposid"             => $request->clientes_gruposid,
            //                 "provinciasid"                  => $request->provinciasid,
            //                 "ciudadesid"                    => $request->ciudadesid,
            //                 "razonsocial"                   => $request->razonsocial,
            //                 "parroquiasid"                  => $request->parroquiasid,
            //                 "clientes_zonasid"              => $request->clientes_zonasid,
            //                 "clientes_rutasid"              => $request->clientes_rutasid,
            //                 "nombrecomercial"               => $request->nombrecomercial,
            //                 "direccion"                     => $request->direccion,
            //                 "identificacion"                => $request->identificacion,
            //                 "tipoidentificacion"            => $request->tipoidentificacion,
            //                 "email"                         => $request->email,
            //                 "telefono1"                     => $request->telefono1,
            //                 "telefono2"                     => $request->telefono2,
            //                 "telefono3"                     => $request->telefono3,
            //                 "vendedoresid"                  => $request->vendedoresid,
            //                 "cobradoresid"                  => $request->cobradoresid,
            //                 "creditocupo"                   => $request->creditocupo,
            //                 "creditodias"                   => $request->creditodias,
            //                 "estado"                        => $request->estado,
            //                 "tarifasid"                     => $request->tarifasid,
            //                 "forma_pago_empresaid"          => $request->forma_pago_empresaid,
            //                 "ordenvisita"                   => $request->ordenvisita,
            //                 "latitud"                       => $request->latitud,
            //                 "longitud"                      => $request->longitud,
            //                 "usuariomodificacion"           => $request->usuariomodificacion,
            //                 "fechamodificacion"             => $request->fechamodificacion
            //             ]
            //         ]
            //     ]
            // ];
            $url        = "clientes_editar";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

    }
    /*
        Nota: Antes de realizar el proceso de eliminación, se verifique que el cliente no tenga documentos asociados, si es así enviar un mensaje de error en formato JSON, caso contrario retorna un texto fijo con el siguiente enunciado: Registro eliminado correctamente en el sistema.
        clientesid: id del cliente a eliminar.
        empresaid: Opcional, id de la empresa en la que se esta trabajando.
        usuarioid: Opcional, id del usuario con que accedió al sistema.
    */
    //api:get/perseo/clientes/clientes_eliminar
    public function clientes_eliminar(Request $request)
    {
        try {
            $formData = [
                "clientesid"    => 492,
                "empresaid"     => "",
                "usuarioid"     => ""
            ];
            // $formData = [
            //     "clientesid"    => $request->clientesid,
            //     "empresaid"     => $request->empresaid,
            //     "usuarioid"     => $request->usuarioid
            // ];
            $url        = "clientes_eliminar";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        Devuelve todos los clientes almacenados en el sistema central y que su estado sea activo, de acuerdo con el parámetro que se haya enviado.
        Nota: Tomar en cuenta que al realizar una búsqueda personalizada, se debe enviar solo 1 de los 4 parámetros solicitados; es decir, que realiza la busque por:
        id del cliente
        Razón Social o nombre comercial del cliente
        Código del cliente
        Identificación

        //
        clienteid: Si se conoce el id del cliente.
        clientescodigo: Código generado al crear el cliente.
        identificacion: RUC, Cédula o pasaporte del cliente.
        contenido: Nombre comercial o razón social, sea completo o una parte del dato, buscará coincidencias.
        //
    */
    //api:post/perseo/clientes/clientes_consulta
    public function clientes_consulta(Request $request)
    {
        $ifSolinfa  = 0;
        $cedula     = $request->busqueda;
        $empresa    = $request->empresa;
        $process    = [];
        if($request->ifSolinfa){ $ifSolinfa = 1; }
        try {
            $formData = [
                "identificacion"       => $cedula,
            ];
            $url        = "clientes_consulta";
            if($ifSolinfa == 1) { $process = $this->tr_SolinfaPost($url, $formData, $empresa); }
            else                { $process = $this->tr_PerseoPost($url, $formData, $empresa); }
            return $process;
        } catch (\Exception $e) {
            return ["status" => "0", "message" => "Ocurrio un eror al intentar mandar la proforma a perseo", "error" => $e->getMessage()];
        }
    }
    /*
        Procedimiento utilizado para el registro de los clientes prospectos.
    */
    //api:post:/perseo/clientes/clientes_prospectos_crear
    public function clientes_prospectos_crear(Request $request){
        try {
            $formData = [
                "registros" => [
                    [
                        "clientesprospecto" => [
                            "clientesid" => 1,
                            "clientes_gruposid" => 1,
                            "ciudadesid" => "2301",
                            "razonsocial" => "QUINTERO RODRIGUEZ SEGUNDO GABRIEL",
                            "nombrecomercial" => "PRUEBA ",
                            "direccion" => "ELOY ALFARO Y ANTONIO SOLANO",
                            "identificacion" => "0803784008",
                            "tipoidentificacion" => "C",
                            "email" => "gabriel@gmail.com",
                            "telefono1" => "0996314786",
                            "telefono2" => "",
                            "vendedoresid" => 3,
                            "estado" => 1,
                            "empresaid" => 2,
                            "usuarioid" => 2,
                            "usuariocreacion" => "PERSEO",
                            "fechacreacion" => "20210830134424",
                            "usuariomodificacion" => "PERSEO",
                            "fechamodificacion" => "2021-08-30T13:44:24.542"
                        ]
                    ]
                ]
            ];

            // $formData = [
            //     "registros" => [
            //         [
            //             "clientesprospecto"             => [
            //                 "clientesid"                => $request->clientesid,
            //                 "clientes_gruposid"         => $request->clientes_gruposid,
            //                 "ciudadesid"                => $request->ciudadesid,
            //                 "razonsocial"               => $request->razonsocial,
            //                 "nombrecomercial"           => $request->nombrecomercial,
            //                 "direccion"                 => $request->direccion,
            //                 "identificacion"            => $request->identificacion,
            //                 "tipoidentificacion"        => $request->tipoidentificacion,
            //                 "email"                     => $request->email,
            //                 "telefono1"                 => $request->telefono1,
            //                 "telefono2"                 => $request->telefono2,
            //                 "vendedoresid"              => $request->vendedoresid,
            //                 "estado"                    => $request->estado,
            //                 "empresaid"                 => $request->empresaid,
            //                 "usuarioid"                 => $request->usuarioid,
            //                 "usuariocreacion"           => $request->usuariocreacion,
            //                 "fechacreacion"             => $request->fechacreacion,
            //                 "usuariomodificacion"       => $request->usuariomodificacion,
            //                 "fechamodificacion"         => $request->fechamodificacion
            //             ]
            //         ]
            //     ]
            // ];
            $url        = "clientes_prospectos_crear";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        *Este procedimiento se utiliza para extraer el listado de los grupos con los que se va a trabajar a los clientes. No contiene filtros, así que extrae todos los almacenes.
        El formato con que devuelve el resultado es tipo JSON.
    */
    //api:post:/perseo/clientes/clientes_consulta_grupos
    public function clientes_consulta_grupos(Request $request){
        try {
            //no lleva parametros
            $formData   = [];
            $url        = "clientes_consulta_grupos";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        *Este procedimiento se utiliza para extraer el listado de las rutas para los clientes se que crearon en el sistema. No contiene filtros, así que extrae todos los almacenes.
        El formato con que devuelve el resultado es tipo JSON.
    */
    //api:post:/perseo/clientes/clientes_consulta_rutas
    public function clientes_consulta_rutas(Request $request){
        try {
            //no lleva parametros
            $formData   = [];
            $url        = "clientes_consulta_rutas";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /*
        *Este procedimiento se utiliza para extraer el listado de as zonas para los clientes, existentes en el sistema. No contiene filtros, así que extrae todos los almacenes.
        El formato con que devuelve el resultado es tipo JSON.
    */
    //api:post:/perseo/clientes/clientes_consulta_zonas
    public function clientes_consulta_zonas(Request $request){
        try {
            //no lleva parametros
            $formData   = [];
            $url        = "clientes_consulta_zonas";
            $process    = $this->tr_PerseoPost($url, $formData);
            return $process;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //api:get/perseo/clientes/cliente
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
