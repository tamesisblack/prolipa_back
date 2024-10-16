<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigosLibrosDevolucionSon extends Model
{
    use HasFactory;
    protected $table = 'codigoslibros_devolucion_son';
    protected $primaryKey = 'id';
    //relacion con la tabla codigos
    public function codigos()
    {
        return $this->belongsTo(CodigosLibros::class, 'codigo');
    }
}
