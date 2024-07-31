<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class _14TipoEmpaque extends Model
{
    use HasFactory;
    protected $table  ="1_4_tipo_empaque";

    protected $primaryKey = 'tip_empa_codigo';
    public $timestamps = false;

    protected $fillable = [
        'tip_empa_codigo',
        'tip_empa_nombre',
        'tip_empa_peso',
        'tip_empa_estado',
        'updated_at',
        'user_created',
    ];
}
