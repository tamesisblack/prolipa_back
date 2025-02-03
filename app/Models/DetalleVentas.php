<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleVentas extends Model
{
    use HasFactory;
    protected $table = "f_detalle_venta";
    protected $primaryKey = 'det_ven_codigo';
    protected $fillable = [
        'det_ven_codigo',
        'ven_codigo',
        'id_empresa',
        'pro_codigo',
        'det_ven_cantidad',
        'det_ven_valor_u',
        'det_ven_cantidad_despacho',
        'idProforma',
        'det_ven_dev',
        'det_cant_intercambio',
        'doc_intercambio',
        'detalle_notaCreditInterna',
    ];
	public $timestamps = false;
   /**
     * Scope para obtener detalles del libro basado en parámetros específicos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ven_codigo
     * @param int $id_empresa
     * @param string $pro_codigo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGetLibroDetalle($query, $ven_codigo, $id_empresa, $pro_codigo)
    {
        return $query->where('ven_codigo', $ven_codigo)
                     ->where('id_empresa', $id_empresa)
                     ->where('pro_codigo', $pro_codigo)
                     ->get();
    }
     /**
     * Scope para obtener detalles del libro basado en parámetros específicos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ven_codigo
     * @param int $id_empresa
     * @param string $pro_codigo
     * @param int $det_ven_dev
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function updateDevolucion($ven_codigo, $id_empresa, $pro_codigo, $det_ven_dev)
    {
        try {
            // Realiza la actualización en la base de datos
            $updated = self::where('ven_codigo', $ven_codigo)
                           ->where('id_empresa', $id_empresa)
                           ->where('pro_codigo', $pro_codigo)
                           ->update(['det_ven_dev' => $det_ven_dev]);
            return [
                'status' => 1,
                'message' => "Actualización exitosa."
            ];
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'message' => "Error general: " . $e->getMessage()
            ];
        }
    }
}
