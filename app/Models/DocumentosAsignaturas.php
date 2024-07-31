<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentosAsignaturas extends Model
{
    protected $table = "documentos_asignatura";
    protected $primaryKey = 'id';
    protected $fillable = [
        'iddocumento',
        'idasignatura',
    ];
	public $timestamps = true;
}
