<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectorHasInstitucion extends Model
{
    use HasFactory;
    protected $table = "director_has_institucion";
    protected $primaryKey = "id";
}
