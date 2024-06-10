<?php

use App\Http\Controllers\SocketsController;
use Illuminate\Support\Facades\Route;

//Route::post("/sockets/connect", [SocketsController::class, "connect"]);

require __DIR__ . '/routes/company.php';
require __DIR__ . '/routes/plans.php';
require __DIR__ . '/routes/role.php';
require __DIR__ . '/routes/dashboard.php';
require __DIR__ . '/routes/user.php';
require __DIR__ . '/routes/events.php';
require __DIR__ . '/routes/checkout.php';
require __DIR__ . '/routes/contact.php';
require __DIR__ . '/routes/management.php';
require __DIR__ . '/routes/settings.php';
require __DIR__ . '/routes/auth.php';
require __DIR__ . '/routes/notification.php';
require __DIR__ . '/routes/broadcast.php';
