<?php
namespace App\Repositories\Facturacion;

use App\Models\Proforma;
use App\Repositories\BaseRepository;
use DB;
class  ProformaRepository extends BaseRepository
{
    public function __construct(Proforma $proforma)
    {
        parent::__construct($proforma);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $institucion
     */
    public function listadoProformasAgrupadas($institucion){
        $query = DB::SELECT("SELECT * FROM f_venta v
        WHERE v.institucion_id = ?
        AND v.est_ven_codigo <> '3'",[$institucion]);
        return $query;
    }
    public function prefacturaValidaForDevolver($preFactura,$empresa){
        $getPreproforma    = DB::SELECT("SELECT * FROM f_venta v
        WHERE v.ven_codigo = '$preFactura'
        AND v.id_empresa   ='$empresa'
        ");
        if(empty($getPreproforma))    { return []; }
        foreach($getPreproforma as $key => $item){
            $query = DB::SELECT("SELECT * FROM f_venta_agrupado v
            WHERE v.id_factura = ?
            AND v.id_empresa = ?
            ",[$item->id_factura,$item->id_empresa]);
            // $query = DB::SELECT("SELECT * FROM f_venta_agrupado v
            // WHERE v.id_factura = ?
            // AND v.estadoPerseo = '1'
            // AND v.id_empresa = ?
            // ",[$item->id_factura,$item->id_empresa]);
            if(count($query) > 0){
                $getPreproforma[$key]->ifPedidoPerseo = 1;
            }else{
                $getPreproforma[$key]->ifPedidoPerseo = 0;
            }
        }
        $resultado = collect($getPreproforma);
        //filtrar por ifPedidoPerseo igual a 1
        $resultado = $resultado->where('ifPedidoPerseo','1')->all();
        return $resultado;
    }

}
?>
