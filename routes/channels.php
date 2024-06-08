<?php

// routes/channels.php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('notifications', function (User $user) {
    // You can add additional logic here to check if the user should be authorized
    // to join the channel, such as checking user roles or permissions.
    return true; // Allow all authenticated users to join the notifications channel
});
