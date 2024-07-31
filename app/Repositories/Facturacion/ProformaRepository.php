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
    public function listadoDevoluciones($institucion){
        $query = DB::SELECT("SELECT p.*, v.proformas_codigo
        FROM f_proforma p
        INNER  JOIN f_venta v ON p.prof_id = v.ven_idproforma
        WHERE p.id_ins_depacho = ?
        AND v.proformas_codigo IS NOT NULL
        ",[$institucion]);
        return response()->json($query);
    }
}
?>
