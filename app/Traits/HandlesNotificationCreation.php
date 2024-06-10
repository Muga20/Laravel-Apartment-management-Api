<?php

namespace App\Traits;

use App\Events\NotificationEvent;
use App\Models\Channel;
use App\Models\ChannelUsers;
use App\Models\Notification;
use App\Models\UserRoles;

trait HandlesNotificationCreation
{
    private function createNotification($user, $authUser)
    {
        $newUserDetails = $user->detail;
        $creatorDetails = $authUser->detail;

        $message = 'New user ' . $this->getFullName($newUserDetails) . ' was created by ' . $this->getFullName($creatorDetails) . '. Company: ' . $authUser->company->name;

        // Find or create a channel based on the event
        $channel = Channel::firstOrCreate(['event' => 'NotificationEvent'], [
            'channel_name' => 'notifications',
            'status' => 'active',
        ]);

        // Create the notification
        $notification = Notification::create([
            'type' => 'UserCreated',
            'message' => $message,
            'channel_id' => $channel->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get roles to notify (admin, landlord, and sudo)
        $rolesToNotify = ['admin', 'landlord', 'sudo'];

        // Get user IDs with the roles to notify in the same company
        $userIdsToNotify = UserRoles::join('users', 'user_roles.user_id', '=', 'users.id')
            ->whereHas('role', function ($query) use ($rolesToNotify) {
                $query->whereIn('name', $rolesToNotify);
            })
            ->where('users.company_id', $authUser->company_id)
            ->pluck('user_id')
            ->toArray();

        // Attach the relevant users to the channel if not already attached
        foreach ($userIdsToNotify as $userIdToNotify) {
            // Check if the user is already associated with the channel
            $existingChannelUser = ChannelUsers::where([
                'user_id' => $userIdToNotify,
                'company_id' => $authUser->company_id,
                'channel_id' => $channel->id,
            ])->exists();

            // If the user is not already associated with the channel, create the association
            if (!$existingChannelUser) {
                ChannelUsers::create([
                    'user_id' => $userIdToNotify,
                    'company_id' => $authUser->company_id,
                    'channel_id' => $channel->id,
                ]);
            }
        }

        event(new NotificationEvent($notification));
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
