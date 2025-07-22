<?php

namespace App\Models\Models\Pedidos;

use App\Models\Models\Pagos\FormasPagos;
use App\Models\Models\Pagos\PedidosPagosHijo;
use App\Models\Models\Pagos\TipoPagos;
use App\Models\PedidoPagosDetalle;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedidosDocumentosLiq extends Model
{
    use HasFactory;
    protected $table        = "1_4_documento_liq";
    protected $primaryKey   = "doc_codigo";
    protected $fillable     = [
        "doc_valor",
        "doc_numero",
        "doc_nombre",
        "doc_ci",
        "doc_cuenta",
        "doc_institucion",
        "doc_tipo",
        "doc_observacion",
        "ven_codigo",
        "doc_fecha",
        "tip_pag_codigo"
    ];
    // public $timestamps = false;
    public function tipoPagos(): BelongsTo
    {
        return $this->belongsTo(TipoPagos::class, 'tipo_pago_id','id');
    }
    public function formaPagos(): BelongsTo
    {
        return $this->belongsTo(FormasPagos::class, 'forma_pago_id','tip_pag_codigo');
    }
    public function pedidoPagosHijo():HasMany
    {
        return $this->hasMany(PedidosPagosHijo::class, 'documentos_liq_id', 'doc_codigo');
    }
    //user_cierre relacion con usuario
    public function userCierre()
    {
        return $this->belongsTo(Usuario::class, 'user_cierre', 'idusuario');
    }
    public function detallePago(): HasMany
    {
        return $this->hasMany(PedidoPagosDetalle::class, 'id_pago', 'doc_codigo');
    }
    //===SCOPES==
    public function scopeActualizarDocumentoLiq($query, $doc_codigo, $datos)
    {
        return $query->where('doc_codigo', $doc_codigo)->update($datos);
    }
    public function scopePorInstitucionYPeriodo($query, $institucion, $periodo)
    {
        return $query->where('institucion_id', $institucion)
            ->where('periodo_id', $periodo);
    }
}
