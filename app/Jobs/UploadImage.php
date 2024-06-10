<?php

namespace App\Jobs;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class UploadImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $modelClass;
    protected $modelId;
    protected $field;

    /**
     * Create a new job instance.
     *
     * @param  string  $filePath
     * @param  string  $modelClass
     * @param  int  $modelId
     * @param  string  $field
     * @return void
     */
    public function __construct(string $filePath, string $modelClass, int $modelId, string $field)
    {
        $this->filePath = $filePath;
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->field = $field;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Upload the image to Cloudinary
            $uploadedImageUrl = Cloudinary::upload($this->filePath)->getSecurePath();

            // Update the model's image URL in the database
            $model = $this->modelClass::findOrFail($this->modelId);
            $model->update([$this->field => $uploadedImageUrl]);

            // Delete the temporary file
            Storage::delete($this->filePath);
        } catch (\Exception $e) {
            \Log::error('Failed to upload image: ' . $e->getMessage());
        }
    }
}
