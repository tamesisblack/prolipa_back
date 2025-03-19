<?php
namespace App\Repositories\Facturacion;

use App\Models\_1_4TipoEmpaque;
use App\Models\f_tipo_documento;
use App\Repositories\BaseRepository;
use DB;
class  EmpaqueRepository extends BaseRepository
{
    public function __construct(_1_4TipoEmpaque $empaque)
    {
        parent::__construct($empaque);
    }
    public function getTipoEmpaquest(){ return $this->model->all(); }

    public function actualizarSecuencia($idDocumento, $empresa, $prefijo)
    {
        $query = f_tipo_documento::obtenerSecuenciaXId($idDocumento, $empresa)->first();

        if (!$query) {
            return null;
        }

        $nuevoCodigo = (int) $query->cod + 1;
        $query->update([
            $empresa == 1 ? 'tdo_secuencial_Prolipa' : 'tdo_secuencial_calmed' => $nuevoCodigo
        ]);

        return "{$prefijo}-" . ($empresa == 1 ? 'P' : 'C') . "-{$nuevoCodigo}";
    }
}
?>
