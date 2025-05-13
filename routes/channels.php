<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
// routes/channels.php
Broadcast::channel('notification.{email}', function ($user, $email) {
    return $user->email === $email;  // Solo permite acceder al canal si el usuario es el mismo que el email
});

Broadcast::channel('admin.notifications_verificaciones', function ($user) {
    // Verifica si el ID del grupo del usuario es 1, 22 o 23
    return in_array($user->id_group, [1, 22, 23]);
});
Broadcast::channel('asesor.notificacionVerificacion', function ($user) {
    return $user->idgroup === 11;
});

