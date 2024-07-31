<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\VerificacionHistorico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class FacturacionApiController extends Controller
{

    //PRUEBA API
    public function Pruebaapi()
    {
        $dato = Http::get("http://186.4.218.168:9095/api/f_Pruebaapi");
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Pruebaapi_post(Request $request)
    {
        $form_data = [
            'nombre'        => $request->nombre,
            'apellido' => $request->apellido,
            'edad'   => $request->edad,
            'correoElectronico' => $request->correoElectronico
        ];
        // return $form_data;
        $dato = Http::post("http://186.4.218.168:9095/api/f_Pruebaapi", $form_data);
        $prueba_post = json_decode($dato, true);
        return $prueba_post;
    }
    public function Pruebaapi_put(Request $request)
    {
        $form_data = [
            'id' => $request->id,
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'edad' => $request->edad,
            'correoElectronico' => $request->correoElectronico
        ];
        // return $form_data;
        //$json_data = json_encode($form_data);
        $dato = Http::post("http://186.4.218.168:9095/api/f_Pruebaapi/", $form_data);
        $prueba_put = json_decode($dato, true);
        return $prueba_put;
    }
    public function Prueba_api_delete(Request $request)
    {
        $valor = $request->id;
        //return $valor;
        $dato = Http::post("http://186.4.218.168:9095/api/f_Pruebaapi/DeletePruebaapi?id=".$valor);
        $prueba_delete = json_decode($dato, true);
        return $prueba_delete;
    }

    //VENDEDOR
    public function Get_Vendedor()
    {
        $dato = Http::get("http://186.4.218.168:9095/api/f_Vendedor/GetVen_Ciudades");
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Get_Vendedorxbusquedayrazonbusqueda(Request $request)
    {
        $valor = $request->busqueda;
        $valor2 = $request->razonbusqueda;
        $dato = Http::get("http://186.4.218.168:9095/api/f_Vendedor/GetxParametros?busqueda=".$valor."&razonbusqueda=".$valor2);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Post_VendedorCrear(Request $request)
    {
        $form_data = [
            'venDCodigo' => $request->venDCodigo,
            'ciuCodigo' => $request->ciuCodigo,
            'venDApellidos' => $request->venDApellidos,
            'venDNombres' => $request->venDNombres,
            'venDDireccion,' => $request->venDDireccion,
            'venDTelefono' => $request->venDTelefono,
            'venDEmail' => $request->venDEmail,
            'venDCi' => $request->venDCi,
            'venDSecuencial' => $request->venDSecuencial
        ];
        $dato = Http::post("http://186.4.218.168:9095/api/f_Vendedor", $form_data);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Post_VendedorEditar(Request $request)
    {
        $form_data = [
            'venDCodigo' => $request->venDCodigo,
            'ciuCodigo' => $request->ciuCodigo,
            'venDApellidos' => $request->venDApellidos,
            'venDNombres' => $request->venDNombres,
            'venDDireccion' => $request->venDDireccion,
            'venDTelefono' => $request->venDTelefono,
            'venDEmail' => $request->venDEmail,
            'venDCi' => $request->venDCi,
            'venDSecuencial' => $request->venDSecuencial
        ];
        $dato = Http::post("http://186.4.218.168:9095/api/f_Vendedor", $form_data);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    //PRODUCTO
    public function Get_Producto()
    {
        $dato = Http::get("http://186.4.218.168:9095/api/f_Producto/GetPro_Grupos");
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Get_Productocompleto()
    {
        $dato = Http::get("http://186.4.218.168:9095/api/f_Producto");
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Get_Productoxbusquedayrazonbusqueda(Request $request)
    {
        $valor = $request->busqueda;
        $valor2 = $request->razonbusqueda;
        //return $valor;
        //return $request;
        $dato = Http::get("http://186.4.218.168:9095/api/f_Producto/GetxParametros?busqueda=".$valor."&razonbusqueda=".$valor2);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Post_ProductoEditar(Request $request)
    {
        $form_data = [
            'proCodigo' => $request->proCodigo,
            'gruProCodigo' => $request->gruProCodigo,
            'proNombre' => $request->proNombre,
            'proDescripcion' => $request->proDescripcion,
            'proIva' => $request->proIva,
            'proValor' => $request->proValor,
            'proDescuento' => $request->proDescuento,
            'proStock' => $request->proStock,
            'proCosto' => $request->proCosto,
            'proPeso' => $request->proPeso
        ];
        //return $form_data;
        $dato = Http::post("http://186.4.218.168:9095/api/f_Producto/AggOrUpdateProducto", $form_data);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    //INSTITUCION
    public function Get_Institucion()
    {
        $dato = Http::get("http://186.4.218.168:9095/api/f_Institucion");
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Get_InstitucionxNombre(Request $request)
    {
        $valor = $request->nameInstitucion;
        //return $valor;
        //return $request;
        $dato = Http::get("http://186.4.218.168:9095/api/f_Institucion?nameInstitucion=".$valor);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Post_InstitucionCrear(Request $request)
    {
        $form_data = [
            'ciuCodigo'        => $request->ciuCodigo,
            'tipInsCodigo' => $request->tipInsCodigo,
            'cicCodigo'   => $request->cicCodigo,
            'insNombre'   => $request->insNombre,
            'insDireccion'   => $request->insDireccion,
            'insTelefono'   => $request->insTelefono,
            'insAlias'   => $request->insAlias,
            'insMail'   => $request->insMail,
            'insSitioWeb'   => $request->insSitioWeb,
            'insNombreJuridico'   => $request->insNombreJuridico,
            'insNombreComercial'   => $request->insNombreComercial,
            'insRepresentanteLegal'   => $request->insRepresentanteLegal,
            'insRuc'   => $request->insRuc,
            'insSector' => $request->insSector
        ];
        // return $form_data;
        $dato = Http::post("http://186.4.218.168:9095/api/f_Institucion", $form_data);
        $prueba_post = json_decode($dato, true);
        return $prueba_post;
    }
    public function Post_InstitucionEditar(Request $request)
    {
        $form_data = [
            'insCodigo'        => $request->insCodigo,
            'ciuCodigo'        => $request->ciuCodigo,
            'tipInsCodigo' => $request->tipInsCodigo,
            'cicCodigo'   => $request->cicCodigo,
            'insNombre'   => $request->insNombre,
            'insDireccion'   => $request->insDireccion,
            'insTelefono'   => $request->insTelefono,
            'insAlias'   => $request->insAlias,
            'insMail'   => $request->insMail,
            'insSitioWeb'   => $request->insSitioWeb,
            'insNombreJuridico'   => $request->insNombreJuridico,
            'insNombreComercial'   => $request->insNombreComercial,
            'insRepresentanteLegal'   => $request->insRepresentanteLegal,
            'insRuc'   => $request->insRuc,
            'insSector' => $request->insSector
        ];
        // return $form_data;
        // return $form_data;
        $dato = Http::post("http://186.4.218.168:9095/api/f_Institucion/", $form_data);
        $prueba_post = json_decode($dato, true);
        return $prueba_post;
    }
    //CLIENTE_INSTITUCION
    /*public function Get_Cliente_Institucion()
    {
        $dato = Http::get("http://186.4.218.168:9095/api/f_ClienteInstitucion");
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    */
    public function Get_ClienteInstitucionxbusquedayrazonbusqueda(Request $request)
    {
        $valor = $request->busqueda;
        $valor2 = $request->razonbusqueda;
        $dato = Http::get("http://186.4.218.168:9095/api/f_ClienteInstitucion/GetxParametros?busqueda=".$valor."&razonbusqueda=".$valor2);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Delete_Cliente_Institucion(Request $request)
    {
        $valor = $request->id;
        // return $valor;
        $dato = Http::post("http://186.4.218.168:9095/api/f_ClienteInstitucion/DeleteClienteInstitucion?id=".$valor);
        $prueba_delete = json_decode($dato, true);
        return $prueba_delete;
    }
    //VENTA
    /*
    SOLO UN PARAMETRO
    public function Get_Ventaxcontrato(Request $request)
    {
        $valor = $request->codcontrato;
        //return $valor
        $dato = Http::get("http://186.4.218.168:9095/api/f_Venta?codcontrato=".$valor);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    */
    /*SOLO UN PARAMETRO 10.10.1.4*/
    public function Get_Ventaxfacturabase10_10_1_4(Request $request)
    {
        $valor = $request->ven_codigo;
        //return $valor
        $dato = Http::get("http://186.4.218.168:9095/api/f_Venta/BusquedaxFactura?ven_codigo=".$valor);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    } 
    /*SOLO UN PARAMETRO 10.10.1.1*/
    public function Get_Ventaxfacturabase10_10_1_1(Request $request)
    {
        $valor = $request->ven_codigo;
        //return $valor
        $dato = Http::get("http://186.4.218.168:9095/api/f2_Venta/BusquedaxFactura?ven_codigo=".$valor);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    /*DOS PARAMETROS*/
    public function Get_Ventaxcontrato(Request $request)
    {
        $valor = $request->codcontrato;
        $valor2 = $request->periodo;
        //return $valor
        $dato = Http::get("http://186.4.218.168:9095/api/f_Venta?codcontrato=".$valor."&periodo=".$valor2);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    //METODO PARA PONER EN NULL LOS CAMPOS VEN_REMISION Y VEN_FECHA_REMISION DE BASE 10.10.1.4
    public function Post_EditarVentaxFactura10_10_1_4(Request $request)
    {
        $valor = $request->ven_codigo;
        //return $valor;
        try {
            $dato = Http::post("http://186.4.218.168:9095/api/f_Venta/ActualizarRemision_Fecharemision?venCodigo=".$valor);
            $prueba_update = json_decode($dato, true);
            return $prueba_update;
        } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
    }

    //METODO PARA PONER EN NULL LOS CAMPOS VEN_REMISION Y VEN_FECHA_REMISION DE BASE 10.10.1.1
    public function Post_EditarVentaxFactura10_10_1_1(Request $request)
    {
        $valor = $request->ven_codigo;
        //return $valor;
        try {
            $dato = Http::post("http://186.4.218.168:9095/api/f2_Venta/ActualizarRemision_Fecharemision?venCodigo=".$valor);
            $prueba_update = json_decode($dato, true);
            return $prueba_update;
        } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
    }

    public function Post_VentaEditarestado(Request $request)
    {
        $form_data = [
            'venCodigo'        => $request->venCodigo,
            'usuCodigo' => $request->usuCodigo,
            'venDCodigo'   => $request->venDCodigo,
            'cliInsCodigo'   => $request->cliInsCodigo,
            'tipVenCodigo'   => $request->tipVenCodigo,
            'estVenCodigo'   => $request->estVenCodigo,
            'venObservacion'   => $request->venObservacion,
            'venCheq'   => $request->venCheq,
            'venComision'   => $request->venComision,
            'venValor'   => $request->venValor,
            'venPagado'   => $request->venPagado,
            'venAnticipo'   => $request->venAnticipo,
            'venConObsequios'   => $request->venConObsequios,
            'venConObsFinal'   => $request->venConObsFinal,
            'venComPorcentaje'   => $request->venComPorcentaje,
            'venIva'   => $request->venIva,
            'venDescuento'   => $request->venDescuento,
            'venFecha'   => $request->venFecha,
            'venConvertido'   => $request->venConvertido,
            'venTransporte'   => $request->venTransporte,
            'venEstadoTransporte'   => $request->venEstadoTransporte,
            'venFirmado'   => $request->venFirmado,
            'venTemporada'   => $request->venTemporada,
            'cuenNumero'   => $request->cuenNumero,
            'venDevolucion'   => $request->venDevolucion,
            'venRemision'   => $request->venRemision,
            'venFechRemision'   => $request->venFechRemision,
            'sucursal'   => $request->sucursal
        ];
        //return $form_data;
        $dato = Http::post("http://186.4.218.168:9095/api/f_Venta", $form_data);
        $prueba_post = json_decode($dato, true);
        return $prueba_post;
    }
    //CLIENTE
    public function Get_Clientexbusquedayrazonbusqueda(Request $request)
    {
        $valor = $request->busqueda;
        $valor2 = $request->razonbusqueda;
        $dato = Http::get("http://186.4.218.168:9095/api/f_Cliente?busqueda=".$valor."&razonbusqueda=".$valor2);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function Post_ClienteCrear(Request $request)
    {
        $form_data = [
            'cliCi'        => $request->cliCi,
            'cliApellidos' => $request->cliApellidos,
            'cliNombres'   => $request->cliNombres,
            'cliDireccion'   => $request->cliDireccion,
            'cliTelefono'   => $request->cliTelefono,
            'cliEmail'   => $request->cliEmail,
            'cliCredito'   => $request->cliCredito,
            'cliPlazo'   => $request->cliPlazo,
            'cliAlias'   => $request->cliAlias,
            'cliCelular'   => $request->cliCelular,
            'cliFechaNacimiento'   => $request->cliFechaNacimiento,
            'venDCodigo'   => $request->venDCodigo,
            'cliTitulo'   => $request->cliTitulo
        ];
        //return $form_data;
        $dato = Http::post("http://186.4.218.168:9095/api/f_Cliente", $form_data);
        $prueba_post = json_decode($dato, true);
        return $prueba_post;
    }
    public function Post_ClienteEditar(Request $request)
    {
        $form_data = [
            'cliCi'        => $request->cliCi,
            'cliApellidos' => $request->cliApellidos,
            'cliNombres'   => $request->cliNombres,
            'cliDireccion'   => $request->cliDireccion,
            'cliTelefono'   => $request->cliTelefono,
            'cliEmail'   => $request->cliEmail,
            'cliCredito'   => $request->cliCredito,
            'cliPlazo'   => $request->cliPlazo,
            'cliAlias'   => $request->cliAlias,
            'cliCelular'   => $request->cliCelular,
            'cliFechaNacimiento'   => $request->cliFechaNacimiento,
            'venDCodigo'   => $request->venDCodigo,
            'cliTitulo'   => $request->cliTitulo
        ];
        //return $form_data;
        $dato = Http::post("http://186.4.218.168:9095/api/f_Cliente", $form_data);
        $prueba_post = json_decode($dato, true);
        return $prueba_post;
    }
    //DETALLE DE VERIFICACION
    public function Get_DVerificacionxvencodigoyprocodigo(Request $request)
    {
        $valor = $request->ven_codigo;
        $valor2 = $request->pro_codigo;
        //return $valor;
        //return $request;
        $dato = Http::get("http://186.4.218.168:9095/api/f_DetalleVerificacion/Getxvencodigoyprocodigo?ven_codigo=".$valor."&pro_codigo=".$valor2);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }

    public function Post_EditarDetalleVerificacionxdet_ver_id(Request $request)
    {
        $valor = $request->detVerId;
        $form_data = [
            'detVerCantidad' => intval($request->detVerCantidad),
        ];
        try {
            // return $valor;
            $dato = Http::post("http://186.4.218.168:9095/api/f_DetalleVerificacion/Updatedvcantidadxdetverid?det_ver_id=".$valor, $form_data);
            $prueba_update = json_decode($dato, true);
            $historico = new VerificacionHistorico();
            $historico->vencodigo = $request->venCodigo;
            $historico->procodigo = $request->proCodigo;
            $historico->tipo = '2';
            $historico->accion = '1';
            $historico->numverificacion = $request->detVerVerificacion;
            $historico->cantidadanterior = floatval($request->detVerCantidadAnterior);
            $historico->cantidadactual = floatval($request->detVerCantidad);
            $historico->save();
            if($historico){
                echo " se guartdo  sea";
            }else{
                echo "no se guardo";
            }
            return $prueba_update;
        } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
    }

    public function Post_DeleteDetalleVerificacionxdet_ver_id(Request $request)
    {
        $valor = $request->detVerId;
        // return $valor;
        try {
            $dato = Http::post("http://186.4.218.168:9095/api/f_DetalleVerificacion/Deletexdetverid?det_ver_id=".$valor);
            $prueba_delete = json_decode($dato, true);
            $historico = new VerificacionHistorico();
            $historico->vencodigo = $request->venCodigo;
            $historico->procodigo = $request->proCodigo;
            $historico->tipo = '2';
            $historico->accion = '2';
            $historico->numverificacion = $request->detVerVerificacion;
            $historico->cantidadanterior = floatval($request->detVerCantidadAnterior);
            $historico->cantidadactual = floatval($request->detVerCantidad);
            $historico->save();
            if($historico){
                echo " se guartdo  sea";
            }else{
                echo "no se guardo";
            }
            return $prueba_delete;
        } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
    }
    //DETALLE DE VENTA
    public function Get_DVentaxvencodigoyprocodigo(Request $request)
    {
        $valor = $request->ven_codigo;
        $valor2 = $request->pro_codigo;
        //return $valor;
        //return $request;
        $dato = Http::get("http://186.4.218.168:9095/api/f_DetalleVenta/Busquedaxvencodyprocod?ven_codigo=".$valor."&pro_codigo=".$valor2);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }

    public function Post_EditarDetalleVentaxdet_ven_codigo(Request $request)
    {
        $valor = $request->detVenCodigo;
        $form_data = [
            'detVenCantidadReal' => intval($request->detVenCantidadReal),
        ];
        try {
            //return $formdata;
            $dato = Http::post("http://186.4.218.168:9095/api/f_DetalleVenta/Updatexdetvencodigo?det_ven_codigo=".$valor, $form_data);
            $prueba_update = json_decode($dato, true);
            $historico = new VerificacionHistorico();
            $historico->vencodigo = $request->venCodigo;
            $historico->procodigo = $request->proCodigo;
            $historico->tipo = '1';
            $historico->accion = '1';
            $historico->cantidadanterior = floatval($request->detVenCantidadRealAnterior);
            $historico->cantidadactual = floatval($request->detVenCantidadReal);
            $historico->save();
            if($historico){
                echo " se guartdo  sea";
            }else{
                echo "no se guardo";
            }
            return $prueba_update;
        } catch (\Exception  $ex) {
            return ["status" => "0","message" => "Hubo problemas con la conexión al servidor".$ex];
        }
    }
    public function Pruebacliapi()
    {
        // $cedula = 1707639017;
        // $institucion=33;
        // $vendedor='XH';
        $cedula = 915171920;
        $institucion=14497;
        $vendedor='LJ';
        $dato = Http::get("http://186.4.218.168:9095/api/f2_ClienteInstitucion/Getxclici_inscodigo_vendcodigo?cli_ci=".$cedula."&ins_codigo=".$institucion."&ven_d_codigo=".$vendedor);

        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }
    public function PruebaInsapi()
    {
        $institucion=22898;
        $dato = Http::get("http://186.4.218.168:9095/api/f_Institucion/".$institucion);
        // $institucion='JACINTO JIJON';
        // $form_data = [
        //     'nameInstitucion' => $institucion,
        // ];
        // $dato = Http::get("http://186.4.218.168:9095/api/f_Institucion", $form_data);
        $prueba_get = json_decode($dato, true);
        return $prueba_get;
    }

    public function Post_Cliente_InstitucionCrear()
    {
        $cedula = '0915171920';
        $institucion=14497;
        $vendedor='LJ';
        $form_data = [
            'cliCi'        => $cedula,
            'insCodigo' => $institucion,
            'venDCodigo'   => $vendedor
        ];
        //return $form_data;
        $dato = Http::post("http://186.4.218.168:9095/api/f2_ClienteInstitucion/CreateOrUpdateClienteInstitucion", $form_data);
        $prueba_post = json_decode($dato, true);
        return $prueba_post;
    }
}
