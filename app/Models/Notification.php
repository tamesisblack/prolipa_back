<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $table = "notificaciones";
    protected $primaryKey = 'id';
    protected $fillable = [
        'descripcion',
        'user_created',  
        'group_id'
    ];
}
