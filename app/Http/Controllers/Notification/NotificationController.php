<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Jobs\DeleteNotificationJob;
use App\Models\Channel;
use App\Models\ChannelUsers;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;

class NotificationController extends Controller
{
    /**
     * Get all notifications.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllNotifications(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);
            $user = $data['user'];
            $company = $data['company'];

            // Get the channel IDs associated with the user in the company
            $channelIds = ChannelUsers::where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->pluck('channel_id')
                ->toArray();

            // Fetch all notifications related to the user's channels ordered by creation date
            $notifications = Notification::whereIn('channel_id', $channelIds)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['notifications' => $notifications], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve notifications: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a notification.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteNotification($id)
    {
        try {
            // Dispatch the job to delete the notification
            Queue::push(new DeleteNotificationJob($id));

            return response()->json(['message' => 'Notification delete request queued successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to queue notification delete request: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all channels.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllChannels(Request $request)
    {
        try {
            $channels = Channel::all();
            return response()->json(['channels' => $channels], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching channels data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create a new channel.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createChannel(Request $request)
    {
        try {
            $request->validate([
                'channelName' => 'required|string|max:255',
                'eventName' => 'required|string|max:255',
            ]);

            $channel = Channel::create([
                'channel_name' => $request->input('channelName'),
                'event' => $request->input('eventName'),
                'status' => 'active',
            ]);

            return response()->json(['success' => 'Channel created successfully.'], 200);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => 'Failed to create channel: ' . $e->getMessage()], 500);
        }
    }
}
