<?php

namespace App\Traits;

use App\Models\Message;

trait UnreadMessagesCountTrait
{
    public function getUnreadMessagesCount($user)
    {
        return Message::where('recipients_id', $user->id)
            ->where('status', 'notViewed')
            ->count();
    }

}
