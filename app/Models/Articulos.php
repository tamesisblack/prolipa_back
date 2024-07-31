<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Articulos extends Model
{
    //use HasFactory;
    protected $table = "articulos_pedagogicos";
    protected $primaryKey = 'id';
}
