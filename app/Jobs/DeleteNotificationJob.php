<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class DeleteNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationId;

    /**
     * Create a new job instance.
     *
     * @param int $notificationId
     * @return void
     */
    public function __construct($notificationId)
    {
        $this->notificationId = $notificationId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $notification = Notification::findOrFail($this->notificationId);
            $notification->delete();
        } catch (\Exception $e) {
            // Log or handle the error
            \Log::error('Error deleting notification: ' . $e->getMessage());
        }
    }
}
