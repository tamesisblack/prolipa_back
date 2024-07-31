<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Obsequio extends Model
{
    use HasFactory;
    protected $table = "obsequios";
    protected $primaryKey  = "id";
}
