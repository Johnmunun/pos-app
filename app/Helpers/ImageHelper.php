<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    /**
     * Store product image
     */
    public static function storeImage(?UploadedFile $file, ?string $url, string $type = 'url'): ?string
    {
        if ($type === 'upload' && $file) {
            $path = $file->store('products', 'public');
            return basename($path);
        }

        if ($type === 'url' && $url) {
            return $url;
        }

        return null;
    }

    /**
     * Delete product image
     */
    public static function deleteImage(?string $image, string $type = 'url'): void
    {
        if ($type === 'upload' && $image) {
            Storage::disk('public')->delete('products/' . $image);
        }
    }

    /**
     * Get product image URL
     */
    public static function getProductImage(?string $image, ?string $imageType = 'url'): string
    {
        if (!$image) {
            return asset('images/default-product.png');
        }

        if ($imageType === 'url') {
            return $image;
        }

        return asset('storage/products/' . $image);
    }
}


