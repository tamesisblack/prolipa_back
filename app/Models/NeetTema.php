<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeetTema extends Model
{
    use HasFactory;
    protected $table = "neet_temas";
    protected $primaryKey = "id";
}
