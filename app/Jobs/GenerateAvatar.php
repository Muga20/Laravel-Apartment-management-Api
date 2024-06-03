<?php

namespace App\Jobs;

use App\Models\UserDetails;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAvatar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userDetails;

    public function __construct(UserDetails $userDetails)
    {
        $this->userDetails = $userDetails;
    }

    public function handle()
    {
        $colors = ['#FFC107', '#9C27B0', '#3F51B5', '#FF5722', '#4CAF50', '#009688', '#E91E63', '#607D8B'];
        $backgroundColor = $colors[array_rand($colors)];
        $initials = strtoupper(substr($this->userDetails->first_name, 0, 1) . substr($this->userDetails->last_name, 0, 1));
        $fontColor = $this->getFontColor($backgroundColor);
        $fontPath = public_path('/fonts/Karla/Karla-Bold.ttf');
        $image = imagecreatetruecolor(100, 100);
        sscanf($backgroundColor, "#%02x%02x%02x", $red, $green, $blue);
        $bgColor = imagecolorallocate($image, $red, $green, $blue);
        sscanf($fontColor, "#%02x%02x%02x", $red, $green, $blue);
        $fontColor = imagecolorallocate($image, $red, $green, $blue);
        imagefill($image, 0, 0, $bgColor);
        $textSize = 40;
        $textWidth = imagettfbbox($textSize, 0, $fontPath, $initials)[2] - imagettfbbox($textSize, 0, $fontPath, $initials)[0];
        $textHeight = imagettfbbox($textSize, 0, $fontPath, $initials)[1] - imagettfbbox($textSize, 0, $fontPath, $initials)[7];
        $x = (100 - $textWidth) / 2;
        $y = (100 + $textHeight) / 2;
        imagettftext($image, $textSize, 0, $x, $y, $fontColor, $fontPath, $initials);
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        $base64Image = 'data:image/png;base64,' . base64_encode($imageData);
        $uploadedImage = Cloudinary::upload($base64Image, ["folder" => "avatars"]);
        $avatarUrl = $uploadedImage->getSecurePath();
        $this->userDetails->profileImage = $avatarUrl;
        $this->userDetails->save();
    }

    private function getFontColor($hexColor)
    {
        sscanf($hexColor, "#%02x%02x%02x", $red, $green, $blue);
        $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;
        return $brightness > 125 ? '#000000' : '#ffffff';
    }
}
