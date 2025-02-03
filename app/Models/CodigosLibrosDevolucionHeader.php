<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigosLibrosDevolucionHeader extends Model
{
    use HasFactory;
    protected $table = 'codigoslibros_devolucion_header';
    protected $primaryKey = 'id';
    //relacion con la tabla codigoslibros_devolucion_son
    public function devolucionSon()
    {
        return $this->hasMany(CodigosLibrosDevolucionSon::class, 'codigoslibros_devolucion_id', 'id');
    }
    //relacion con la tabla instituciones
    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'id_cliente');
    }
    //relacion con usuarios
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_created');
    }
    //relacion con usuario revision
    public function usuarioRevision()
    {
        return $this->belongsTo(Usuario::class, 'user_created_revisado');
    }
    //relacion con usuario finalizacion
    public function usuarioFinalizacion()
    {
        return $this->belongsTo(Usuario::class, 'user_created_finalizado');
    }
    //relacion con periodo 
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function devolucionSonSinEmpresa()
    {
        return $this->hasMany(CodigosLibrosDevolucionSon::class, 'codigoslibros_devolucion_id', 'id')
                    ->whereNull('id_empresa');
    }

    //casts
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->setTimezone('America/Lima')->toDateTimeString();
    }
}
