<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// 1. Channel untuk Sidebar "Who's Online" (PRESENCE CHANNEL)
// Wajib mengembalikan ARRAY data user
Broadcast::channel('online-users', function ($user) {
    if (auth()->check()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar ? asset('storage/user/avatar/' . $user->avatar) : null,
            'initials' => substr($user->name, 0, 1)
        ];
    }
});

// 2. Channel untuk Notifikasi Pribadi / Force Logout (PRIVATE CHANNEL)
// Wajib mengembalikan BOOLEAN (True/False)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
