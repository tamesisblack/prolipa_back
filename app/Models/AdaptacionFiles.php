<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdaptacionFiles extends Model
{
    use HasFactory;
    protected $table = "adaptaciones_files";
    protected $primaryKey = 'id';
    protected $fillable = [
        'adaptacion_id',
        'unidad',
        'archivo',
        'url',
        'ext'
    ];
}
