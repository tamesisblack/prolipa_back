<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoContratos extends Model
{
    use HasFactory;
    protected $table = 'historico_contratos';
    protected $primaryKey = 'id';
    protected $fillable = [
        'contrato', 'institucion', 'periodo_id'
     

    ];

}
