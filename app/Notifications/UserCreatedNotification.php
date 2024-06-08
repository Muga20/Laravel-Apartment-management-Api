<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $newUser;
    protected $createdBy;
    protected $companyName;

    public function __construct(User $newUser, User $createdBy, Company $companyName)
    {
        $this->newUser = $newUser;
        $this->createdBy = $createdBy;
        $this->companyName = $companyName->name;
    }

    public function via($notifiable)
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => 'A new user has been created by ' . $this->createdBy->detail->first_name . ' ' . $this->createdBy->detail->last_name . ' in ' . $this->companyName . ' New users names are : ' . $this->newUser->detail->first_name . ' ' . $this->newUser->detail->last_name,
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'A new user has been created by ' . $this->createdBy->detail->first_name . ' ' . $this->createdBy->detail->last_name . ' in ' . $this->companyName . 'New Users Names are : ' . $this->newUser->detail->first_name . ' ' . $this->newUser->detail->last_name,
        ];
    }
}
