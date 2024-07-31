<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Varios extends Model
{
    protected $table = "varios";
    protected $primaryKey = 'id';
    protected $fillable = ['id','titulo','descripcion','url','imagen','estado'];
}
