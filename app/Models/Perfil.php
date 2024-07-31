<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    use HasFactory;
    protected $table = "sys_group_users";
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'level',
        'deskripsi',  
    ];
    
}
