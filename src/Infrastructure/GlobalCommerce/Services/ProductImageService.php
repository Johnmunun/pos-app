<?php

namespace Src\Infrastructure\GlobalCommerce\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Service simple pour gérer les images produits GlobalCommerce.
 * Stockage dans storage/app/public/commerce/products, sans logique métier spécifique.
 */
class ProductImageService
{
    private const STORAGE_DISK = 'public';
    private const STORAGE_PATH = 'commerce/products';

    /**
     * Enregistre une image et retourne le chemin + type.
     *
     * @param UploadedFile $file
     * @return array{image_path: string, image_type: string}
     */
    public function store(UploadedFile $file): array
    {
        $path = $file->store(self::STORAGE_PATH, self::STORAGE_DISK);

        return [
            'image_path' => basename($path),
            'image_type' => 'upload',
        ];
    }

    /**
     * Supprime une image existante si nécessaire.
     */
    public function delete(?string $imagePath, string $imageType = 'upload'): void
    {
        if (!$imagePath || $imageType !== 'upload') {
            return;
        }

        $path = self::STORAGE_PATH . '/' . ltrim($imagePath, '/');
        Storage::disk(self::STORAGE_DISK)->delete($path);
    }

    /**
     * Retourne l'URL publique d'une image.
     */
    public function getUrl(?string $imagePath, string $imageType = 'upload'): string
    {
        if (!$imagePath) {
            return asset('images/default-product.svg');
        }

        if ($imageType === 'url') {
            return $imagePath;
        }

        $path = self::STORAGE_PATH . '/' . ltrim($imagePath, '/');

        return asset('storage/' . $path);
    }
}

