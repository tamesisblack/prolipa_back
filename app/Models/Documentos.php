<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documentos extends Model
{
    protected $table = "documentos";
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'descripcion',
        'status',
    ];
	public $timestamps = true;
}
