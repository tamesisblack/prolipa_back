<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichasFiles extends Model
{
    use HasFactory;
    protected $table = "fichas_files";
    protected $fillable = [
        "ficha_id",
        "archivo",
        "url",
        "ext"
    ];
}
