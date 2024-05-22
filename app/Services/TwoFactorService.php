<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Str;

class TwoFactorService
{
    protected $twilio;

    public function __construct()
    {
        $this->twilio = new Client(config('twilio.sid'), config('twilio.token'));
    }

    public function sendToken($phoneNumber, $token)
    {
        if (!Str::startsWith($phoneNumber, '+')) {
            $phoneNumber = '+254' . substr($phoneNumber, 1);
        }

        try {
            $this->twilio->messages->create(
                $phoneNumber,
                [
                    'from' => config('twilio.from'),
                    'body' => "Your verification code is {$token}",
                ]
            );
        } catch (TwilioException $e) {
            \Log::error('Twilio error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function generateToken()
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
