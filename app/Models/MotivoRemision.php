<?php

namespace App\Models;

use App\Models\Remision;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoRemision extends Model
{
    use HasFactory;
     protected $table  ='1_4_remision_motivo';

    protected $primaryKey = 'mot_id';
    public $timestamps = false;

    protected $fillable = [
        'mot_id',
        'mot_nombre',
        'mot_observacion',
        'mot_estado',
        'created_at',
        'updated_at',
        'user_created',
    ];
}
