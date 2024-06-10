<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendUserCreationNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $authUser;
    protected $userRoles;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, User $authUser, array $userRoles)
    {
        $this->user = $user;
        $this->authUser = $authUser;
        $this->userRoles = $userRoles;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $newUserDetails = $this->user->detail;
            $creatorDetails = $this->authUser->detail;

            $notification = Notification::create([
                'type' => 'Notifications',
                'message' => json_encode([
                    'message' => 'New user was created by',
                    'creator_name' => $this->getFullName($creatorDetails),
                    'new_user_name' => $this->getFullName($newUserDetails),
                    'creator_company' => $this->authUser->company->name,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);


        } catch (\Exception $e) {
            report($e);
        }
    }

    private function getFullName($userDetails)
    {
        $fullName = $userDetails->first_name;
        if ($userDetails->middle_name) {
            $fullName .= ' ' . $userDetails->middle_name;
        }
        $fullName .= ' ' . $userDetails->last_name;

        return $fullName;
    }
}
