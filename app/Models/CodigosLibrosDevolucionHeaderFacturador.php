<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigosLibrosDevolucionHeaderFacturador extends Model
{
    use HasFactory;
    protected $table = 'codigoslibros_devolucion_header_facturador';
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }
    //casts
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->setTimezone('America/Lima')->toDateTimeString();
    }
}
