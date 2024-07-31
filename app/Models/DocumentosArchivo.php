<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentosArchivo extends Model
{
    protected $table = "documentos_archivo";
    protected $primaryKey = 'id';
    protected $fillable = [
        'archivo',
        'documento'
    ];
	public $timestamps = true;
}
