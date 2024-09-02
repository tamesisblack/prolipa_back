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
    public function facturaSecuencia($empresa,$solinfa = 0)
    {
        $formData   = [];
        $url        = "secuencias_consulta";
        if($solinfa == 1) { $process    = $this->tr_SolinfaPost($url, $formData,$empresa);
        }else             { $process    = $this->tr_PerseoPost($url, $formData,$empresa); }
        return $process;
    }
}
