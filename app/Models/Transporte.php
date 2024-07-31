<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transporte extends Model
{
    use HasFactory;
    protected $table  ='1_4_transporte';

    protected $primaryKey = 'trans_codigo';
    public $timestamps = false;

    protected $fillable = [
        'trans_codigo',
        'trans_nombre',
        'trans_ruc', 
        'trans_direccion',
        'trans_guia_remision',
        'updated_at',
        'user_created'
    ];
}
