<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FilesDepartamentos extends Model
{
    protected $table = "files_archivos";
    protected $primaryKey = 'id_archivo';
    protected $fillable = [
        'id_departamento',
    ];
}
