<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewAccount extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $company;
    public $resetLink;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param Company $company
     * @param string $resetLink
     */
    public function __construct(User $user, Company $company, $resetLink)
    {
        $this->user = $user;
        $this->company = $company;
        $this->resetLink = $resetLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to ' . $this->company->name)
            ->view('emails.new_account')
            ->with([
                'user' => $this->user,
                'company' => $this->company,
                'resetLink' => $this->resetLink,
            ]);
    }
}
