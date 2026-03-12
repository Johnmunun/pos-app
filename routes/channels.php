<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privé pour les notifications ROOT / admins
Broadcast::channel('root.notifications', function ($user) {
    if (!$user) {
        return false;
    }

    // Autoriser ROOT + utilisateurs ayant accès au dashboard ROOT
    if (method_exists($user, 'isRoot') && $user->isRoot()) {
        return true;
    }

    if (method_exists($user, 'hasPermission')) {
        return $user->hasPermission('admin.dashboard.view');
    }

    return false;
});
