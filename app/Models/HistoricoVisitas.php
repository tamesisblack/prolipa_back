<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoVisitas extends Model
{
    use HasFactory;
    protected $table = "historico_visitas";
    protected $primaryKey = 'id';
    protected $fillable = [
        'idusuario', 'institucion_id', 'periodo_id', 'id_group', 'fecha', 'hora',
    ];
}
