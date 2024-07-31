<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoInstitucion extends Model
{
    protected $table = "periodoescolar_has_institucion";
    protected $primaryKey = 'id';
    protected $fillable = [
        'periodoescolar_idperiodoescolar',
        'institucion_idInstitucion',
        'fecha_inicio_pension',
        'valor_matricula',
     
    ];
    
	
}
