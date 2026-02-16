<?php

namespace Src\Infrastructure\Pharmacy\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Domain\Pharmacy\ValueObjects\ProductImage;
use Illuminate\Support\Str;

/**
 * Service: ProductImageService
 * 
 * Gère l'upload, le stockage et la suppression des images produits
 * Respecte l'architecture DDD (Infrastructure Layer)
 */
class ProductImageService
{
    private const STORAGE_DISK = 'public';
    private const STORAGE_PATH = 'pharmacy/products';
    private const MAX_SIZE = 2 * 1024 * 1024; // 2 Mo

    /**
     * Upload et stocke une image produit
     * 
     * @param UploadedFile $file
     * @param string $productId
     * @return ProductImage
     * @throws \InvalidArgumentException Si validation échoue
     */
    public function upload(UploadedFile $file, string $productId): ProductImage
    {
        // Valider le fichier
        ProductImage::validateFile($file);

        // Générer un nom de fichier unique
        $extension = $file->getClientOriginalExtension();
        $filename = $productId . '_' . Str::random(10) . '.' . $extension;
        $path = self::STORAGE_PATH . '/' . $filename;

        // Stocker le fichier
        $storedPath = $file->storeAs(self::STORAGE_PATH, $filename, self::STORAGE_DISK);

        if (!$storedPath) {
            throw new \RuntimeException('Failed to store product image');
        }

        // Retourner le nom du fichier (sans le chemin complet)
        return ProductImage::fromUpload($filename);
    }

    /**
     * Supprime une image produit
     * 
     * @param ProductImage $image
     * @return void
     */
    public function delete(ProductImage $image): void
    {
        if ($image->isEmpty() || !$image->isUpload()) {
            return;
        }

        $path = self::STORAGE_PATH . '/' . $image->getPath();
        Storage::disk(self::STORAGE_DISK)->delete($path);
    }

    /**
     * Supprime une image par son chemin
     * 
     * @param string|null $imagePath
     * @param string $imageType
     * @return void
     */
    public function deleteByPath(?string $imagePath, string $imageType = 'upload'): void
    {
        if (!$imagePath || $imageType !== 'upload') {
            return;
        }

        $path = self::STORAGE_PATH . '/' . $imagePath;
        Storage::disk(self::STORAGE_DISK)->delete($path);
    }

    /**
     * Obtient l'URL publique d'une image
     * 
     * @param ProductImage $image
     * @return string|null
     */
    public function getUrl(ProductImage $image): ?string
    {
        if ($image->isEmpty()) {
            return null;
        }

        if ($image->isUrl()) {
            return $image->getPath();
        }

        // Pour les uploads, retourner l'URL publique
        $path = self::STORAGE_PATH . '/' . $image->getPath();
        return Storage::disk(self::STORAGE_DISK)->url($path);
    }

    /**
     * Obtient l'URL publique depuis un chemin et type
     * 
     * @param string|null $imagePath
     * @param string $imageType
     * @return string
     */
    public function getUrlFromPath(?string $imagePath, string $imageType = 'upload'): string
    {
        if (!$imagePath) {
            // Placeholder par défaut si aucune image n'est définie
            return asset('images/default-product.svg');
        }

        if ($imageType === 'url') {
            return $imagePath;
        }

        $path = self::STORAGE_PATH . '/' . $imagePath;
        return Storage::disk(self::STORAGE_DISK)->url($path);
    }

    /**
     * Crée une ProductImage depuis un chemin et type
     * 
     * @param string|null $imagePath
     * @param string $imageType
     * @return ProductImage
     */
    public function createFromPath(?string $imagePath, string $imageType = 'upload'): ProductImage
    {
        if (!$imagePath) {
            return ProductImage::empty();
        }

        if ($imageType === 'url') {
            return ProductImage::fromUrl($imagePath);
        }

        return ProductImage::fromUpload($imagePath);
    }
}
