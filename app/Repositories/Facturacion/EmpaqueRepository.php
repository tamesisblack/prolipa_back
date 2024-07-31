<?php
namespace App\Repositories\Facturacion;

use App\Models\_1_4TipoEmpaque;
use App\Repositories\BaseRepository;
use DB;
class  EmpaqueRepository extends BaseRepository
{
    public function __construct(_1_4TipoEmpaque $empaque)
    {
        parent::__construct($empaque);
    }
    public function getTipoEmpaquest(){ return $this->model->all(); }
}
?>
