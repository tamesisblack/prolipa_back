<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DescripcionFaq extends Model
{
    use HasFactory;
    protected $table = "respuestas_faq";
    protected $primaryKey = 'respuestas_faq_id';
    protected $fillable = [
        'descripcion',
        'estado',
        'pregunta_id',
        'archivo',
        'url',
        'video',
      
        
    ];
}
