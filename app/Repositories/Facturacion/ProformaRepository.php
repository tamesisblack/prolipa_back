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
        // $query = DB::SELECT("SELECT p.*, v.proformas_codigo
        // FROM f_proforma p
        // INNER  JOIN f_venta v ON p.prof_id = v.ven_idproforma
        // INNER JOIN f_venta_agrupado vg ON vg.id_factura = v.id_factura
        // WHERE p.id_ins_depacho = ?
        // AND v.proformas_codigo IS NOT NULL
        // AND v.id_factura IS NOT NULL
        // AND p.prof_estado <> '0'
        // AND v.est_ven_codigo <> '3'
        // ",[$institucion]);
        // return response()->json($query);
        $query = DB::SELECT("SELECT * FROM f_venta v
        WHERE v.institucion_id = ?
        AND v.idtipodoc = '1'
        AND v.est_ven_codigo <> '3'",[$institucion]);
        return response()->json($query);
    }
}
?>
