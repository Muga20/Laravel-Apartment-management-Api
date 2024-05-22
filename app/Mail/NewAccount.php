<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewAccount extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $company;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param Company $company
     */
    public function __construct(User $user, Company $company)
    {
        $this->user = $user;
        $this->company = $company;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Generate a unique token for one-time login
        $uniqueId = uniqid($this->user->id, true);
        $authToken = sha1($uniqueId);
        $authLink = base64_encode($authToken . ':' . $uniqueId);

        // Store the original token in the user's record
        $this->user->otp = $authToken;
        $this->user->save();

        // Generate the login link using the encoded token
        $loginLink = route('AuthNewUser', ['authLink' => $authLink]);

        return $this->subject('Welcome to ' . $this->company->name)
            ->view('emails.new_account')
            ->with([
                'user' => $this->user,
                'company' => $this->company,
                'email' => $this->user->email,
                'authLink' => $loginLink,
                'customMessage' => 'Use that link to login to the system for the first time.',
            ]);
    }
}
