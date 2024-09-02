<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpaqueDetalle extends Model
{
    use HasFactory;
    protected $table  ='rempaque_detallecopy';

    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'det_empa_codigo',
        'empa_codigo',
        'tip_empa_codigo', 
        'dete_estado',
        'created_at',
        'updated_at',
        'user_created',
    ];
}