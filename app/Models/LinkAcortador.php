<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LinkAcortador extends Model
{
    use HasFactory;
    protected $table = "links_acortadores";
    protected $primaryKey = 'id';
    protected $fillable = [
        'libro_id',
        'unidad',
        'pagina',
        'link_original',
        'link_acortado',
        'codigo',
        'usuario_editor',
        'estado',
    ];
    public static function generateUniqueCode($length = 6)
    {
        do {
            $code = Str::random($length); // Generar un código aleatorio de longitud específica
        } while (self::where('codigo', $code)->exists()); // Verificar que no exista en la base de datos

        return $code;
    }
}
