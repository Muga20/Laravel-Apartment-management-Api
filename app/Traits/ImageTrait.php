<?php

namespace App\Traits;

use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

trait ImageTrait
{
    private function updateImage(Request $request, &$data, $fieldName)
    {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);
            $uploadedFileUrl = $this->uploadImage($file);
            $data[$fieldName] = $uploadedFileUrl;
        }
    }

    private function uploadImage($image)
    {
        return Cloudinary::upload($image->getRealPath())->getSecurePath();
    }
}
