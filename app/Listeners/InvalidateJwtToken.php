<?php

namespace App\Listeners;

use App\Events\SessionChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redirect;
use Tymon\JWTAuth\Facades\JWTAuth;

class InvalidateJwtToken implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SessionChanged $event)
    {
        $user = $event->user;

        JWTAuth::invalidate();

        $cookie = Cookie::forget('jwt_token');

        return Redirect::route('login')->withCookie($cookie);
    }
}
