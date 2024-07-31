<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NivelesPeriodosInstitucion extends Model
{
    use HasFactory;
    protected $table = "mat_niveles_periodos_institucion";
    protected $primaryKey = 'id';
    protected $fillable = [
        'institucion_id',
        'periodo_id',
        'fecha_inicio_pension',
        'fecha_fin_pension',
        'valor_matricula',
     
    ];
    public $timestamps = false;

}
