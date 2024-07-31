<?php
namespace App\Repositories\perseo;


use App\Models\Pedidos;
use App\Repositories\BaseRepository;
use App\Traits\Pedidos\TraitPedidosGeneral;
use DB;


class  PerseoConsultasRepository extends BaseRepository
{
    use TraitPedidosGeneral;
    public function __construct(Pedidos $perseoConsultasRepository)
    {
        parent::__construct($perseoConsultasRepository);
    }
    public function facturaSecuencia()
    {
        $formData   = [];
        $url        = "secuencias_consulta";
        $process    = $this->tr_PerseoPost($url, $formData);
        return $process;
    }
}
