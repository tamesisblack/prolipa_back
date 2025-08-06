<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigosLibrosDevolucionDesarmadoHeader extends Model
{
    use HasFactory;
    protected $table = 'codigoslibros_devolucion_desarmados_header';
    protected $primaryKey = 'id';

    protected $fillable = [
        'institucion_id',
        'periodo_id',
        'user_created',
    ];
    public function codigosLibrosDevolucionDesarmadoSons()
    {
        return $this->hasMany(CodigosLibrosDevolucionDesarmadoSon::class, 'codigoslibros_devolucion_desarmados_header_id');
    }
}
