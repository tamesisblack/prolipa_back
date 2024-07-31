<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenFunctions extends Model
{
    use HasFactory;
    public static function codigo($count)
    {
        // This string MUST stay FS safe, avoid special chars
        $base = 'ABCDEFGHKMNPRSTUVWXYZ123456789';
        $ret = '';
        $strlen = \strlen($base);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= $base[random_int(0, $strlen - 1)];
        }

        return $ret;
    }
}
