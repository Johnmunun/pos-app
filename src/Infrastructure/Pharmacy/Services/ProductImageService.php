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
 * Optimise automatiquement les images pour réduire la taille et améliorer les performances
 * Respecte l'architecture DDD (Infrastructure Layer)
 */
class ProductImageService
{
    private const STORAGE_DISK = 'public';
    private const STORAGE_PATH = 'pharmacy/products';
    private const MAX_SIZE = 2 * 1024 * 1024; // 2 Mo
    private const MAX_WIDTH = 1200; // Largeur maximale en pixels
    private const MAX_HEIGHT = 1200; // Hauteur maximale en pixels
    private const JPEG_QUALITY = 85; // Qualité JPEG (0-100)
    private const PNG_QUALITY = 9; // Qualité PNG (0-9, 9 = meilleure compression)
    private const WEBP_QUALITY = 85; // Qualité WebP (0-100)

    /**
     * Upload et stocke une image produit avec compression automatique
     * 
     * @param UploadedFile $file
     * @param string $productId
     * @return ProductImage
     * @throws \InvalidArgumentException Si validation échoue
     */
    public function upload(UploadedFile $file, string $productId): ProductImage
    {
        if ($file->getSize() !== false && $file->getSize() > self::MAX_SIZE) {
            throw new \InvalidArgumentException('La taille du fichier ne doit pas dépasser 2 Mo.');
        }
        // Valider le fichier
        ProductImage::validateFile($file);

        // Générer un nom de fichier unique
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $productId . '_' . Str::random(10) . '.' . $extension;
        $path = self::STORAGE_PATH . '/' . $filename;
        $fullPath = Storage::disk(self::STORAGE_DISK)->path($path);

        // Créer le répertoire s'il n'existe pas
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Compresser et optimiser l'image
        $this->compressAndResizeImage($file, $fullPath, $extension);

        // Retourner le nom du fichier (sans le chemin complet)
        return ProductImage::fromUpload($filename);
    }

    /**
     * Compresse et redimensionne une image pour optimiser les performances
     * 
     * @param UploadedFile $file
     * @param string $outputPath
     * @param string $extension
     * @return void
     */
    private function compressAndResizeImage(UploadedFile $file, string $outputPath, string $extension): void
    {
        // Vérifier que GD est disponible
        if (!extension_loaded('gd')) {
            // Si GD n'est pas disponible, stocker l'image telle quelle
            $file->move(dirname($outputPath), basename($outputPath));
            return;
        }

        // Lire l'image selon son type
        $imageResource = match ($extension) {
            'jpg', 'jpeg' => imagecreatefromjpeg($file->getRealPath()),
            'png' => imagecreatefrompng($file->getRealPath()),
            'webp' => imagecreatefromwebp($file->getRealPath()),
            default => throw new \InvalidArgumentException("Format d'image non supporté: {$extension}"),
        };

        if (!$imageResource) {
            throw new \RuntimeException('Impossible de charger l\'image');
        }

        // Obtenir les dimensions originales
        $originalWidth = imagesx($imageResource);
        $originalHeight = imagesy($imageResource);

        // Calculer les nouvelles dimensions en conservant le ratio
        $ratio = min(self::MAX_WIDTH / $originalWidth, self::MAX_HEIGHT / $originalHeight, 1);
        $newWidth = (int) ($originalWidth * $ratio);
        $newHeight = (int) ($originalHeight * $ratio);

        // Créer une nouvelle image redimensionnée
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG et WebP
        if (in_array($extension, ['png', 'webp'])) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefill($resizedImage, 0, 0, $transparent);
        }

        // Redimensionner l'image avec une meilleure qualité
        imagecopyresampled(
            $resizedImage,
            $imageResource,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // Sauvegarder l'image compressée selon son format
        $saved = match ($extension) {
            'jpg', 'jpeg' => imagejpeg($resizedImage, $outputPath, self::JPEG_QUALITY),
            'png' => imagepng($resizedImage, $outputPath, self::PNG_QUALITY),
            'webp' => imagewebp($resizedImage, $outputPath, self::WEBP_QUALITY),
            default => false,
        };

        // Libérer la mémoire
        imagedestroy($imageResource);
        imagedestroy($resizedImage);

        if (!$saved) {
            throw new \RuntimeException('Impossible de sauvegarder l\'image compressée');
        }
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
        $path = self::STORAGE_PATH . '/' . ltrim($image->getPath(), '/');
        return asset('storage/' . $path);
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

        $path = self::STORAGE_PATH . '/' . ltrim($imagePath, '/');
        return asset('storage/' . $path);
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
