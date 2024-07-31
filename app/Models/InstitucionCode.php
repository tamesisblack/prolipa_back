<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitucionCode extends Model
{
    protected $table = "instituciones_code";
    protected $primaryKey = 'id';
    protected $fillable = [
        'institucion', 'estado', 'updated_at', 'created_at'
    ];
}
