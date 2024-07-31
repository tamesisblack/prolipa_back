<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NivelInstitucion extends Model
{
    use HasFactory;
    protected $table = "mat_niveles_institucion";
    protected $primaryKey = 'nivelInstitucion_id';
    protected $fillable = [
        'mat_niveles_periodos_institucion_id',
        'nivel_id',
        'descripcion',
        'valor',
        'matricula',
        'institucion_id',
        'periodo_id',
        'estado',
    ];
    public $timestamps = false;

}
