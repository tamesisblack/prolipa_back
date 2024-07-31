<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreguntasFaq extends Model
{
    use HasFactory;
    protected $table = "preguntas_faq";
    protected $primaryKey = 'preguntasfaq_id';
    protected $fillable = [
        'descripcion',
        'estado',
        
    ];
}
