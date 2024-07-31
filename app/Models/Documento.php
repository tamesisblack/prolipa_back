<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = "archivo";
    protected $primaryKey = 'id';
    protected $fillable = [
        'url',
        'nombre',
    ];
	public $timestamps = true;
}
