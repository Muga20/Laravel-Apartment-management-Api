<?php

use App\Http\Controllers\SocketsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post("/sockets/connect", [SocketsController::class, "connect"]);
