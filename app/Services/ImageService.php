<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    private static function MakeFolder($folderName)
    {
        $pathFolder = storage_path(sprintf('app/public/%s', $folderName));

        if (!File::isDirectory($pathFolder)) {
            File::makeDirectory($pathFolder, 0755, true, true);
        }
    }




    public static function storeImage($image, $folder, $name = null)
    {
        self::MakeFolder($folder);

        $baseName = $name ? $name . '-' . uniqid() : uniqid();
        $imageName = $baseName . '.webp';
        $new_path = storage_path("app/public/{$folder}/{$imageName}");

        $manager = new ImageManager(new Driver());

        $imageContent = $manager->read($image)
            ->toWebp(quality: 10);

        $imageContent->save($new_path);

        return "{$folder}/{$imageName}";
    }


    public static function updateImage($image, $folder, $oldImageName): string|null
    {
        // return ImageService::deleteImage($oldImageName) ? ImageService::storeImage($image, $folder) : null;
        return ImageService::storeImage($image, $folder);
    }


    public static function deleteImage($imagePath)
    {
        if (strpos($imagePath, asset("storage/")) !== false) {
            $imagePath = str_replace(asset("storage/"), "", $imagePath);
        }

        if (Storage::disk('public')->exists($imagePath)) {
            return  Storage::disk('public')->delete($imagePath);
        }

        return false;
    }


    // public static function removeImages($ids)
    // {
    //     if (!is_array($ids)) {
    //         abort(
    //             response()->json([
    //                 'success' => false,
    //                 'message' =>  'يجب تقديم معرفات الصور كمصفوفة غير فارغة.',
    //             ], 422),
    //         );
    //     }

    //     $existingImages = Image::whereIn('id', $ids)->get();

    //     if (count($ids) !== $existingImages->count()) {
    //         abort(
    //             response()->json([
    //                 'success' => false,
    //                 'message' => 'بعض الصور المطلوب حذفها غير موجودة.',
    //             ], 422),
    //         );
    //     }



    //     foreach ($existingImages as $image) {
    //         if (Storage::exists("public/" . $image->path)) {
    //             Storage::delete("public/" . $image->path);
    //         }
    //         $image->delete();
    //     }
    // }
}
