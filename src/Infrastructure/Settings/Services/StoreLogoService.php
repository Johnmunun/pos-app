<?php

namespace Src\Infrastructure\Settings\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service: StoreLogoService
 * 
 * Gère l'upload, le stockage et la suppression des logos de boutique
 */
class StoreLogoService
{
    private const STORAGE_DISK = 'public';
    private const STORAGE_PATH = 'settings/logos';
    private const MAX_SIZE = 2 * 1024 * 1024; // 2 Mo
    private const ALLOWED_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    /**
     * Upload et stocke un logo
     * 
     * @param UploadedFile $file
     * @param string $shopId
     * @return string Chemin relatif du fichier
     * @throws \InvalidArgumentException Si validation échoue
     */
    public function upload(UploadedFile $file, string $shopId): string
    {
        // Valider le fichier
        $this->validateFile($file);

        // Générer un nom de fichier unique
        $extension = $file->getClientOriginalExtension();
        $filename = $shopId . '_' . Str::random(10) . '.' . $extension;
        $path = $this->STORAGE_PATH . '/' . $filename;

        // Stocker le fichier
        $storedPath = $file->storeAs($this->STORAGE_PATH, $filename, $this->STORAGE_DISK);

        if (!$storedPath) {
            throw new \RuntimeException('Failed to store logo');
        }

        return $filename; // Retourner uniquement le nom du fichier
    }

    /**
     * Supprime un logo par son chemin
     * 
     * @param string|null $logoPath
     * @return bool
     */
    public function deleteByPath(?string $logoPath): bool
    {
        if (!$logoPath) {
            return false;
        }

        $fullPath = $this->STORAGE_PATH . '/' . $logoPath;
        
        if (Storage::disk($this->STORAGE_DISK)->exists($fullPath)) {
            return Storage::disk($this->STORAGE_DISK)->delete($fullPath);
        }

        return false;
    }

    /**
     * Récupère l'URL complète d'un logo
     * 
     * @param string|null $logoPath
     * @return string|null
     */
    public function getUrl(?string $logoPath): ?string
    {
        if (!$logoPath) {
            return null;
        }

        $fullPath = $this->STORAGE_PATH . '/' . $logoPath;
        
        if (Storage::disk($this->STORAGE_DISK)->exists($fullPath)) {
            return Storage::disk($this->STORAGE_DISK)->url($fullPath);
        }

        return null;
    }

    /**
     * Valide un fichier uploadé
     * 
     * @param UploadedFile $file
     * @throws \InvalidArgumentException
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Le fichier uploadé est invalide');
        }

        if ($file->getSize() > $this->MAX_SIZE) {
            throw new \InvalidArgumentException('Le fichier est trop volumineux (max 2 Mo)');
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->ALLOWED_MIMES)) {
            throw new \InvalidArgumentException('Format de fichier non autorisé (JPG, JPEG, PNG, WebP uniquement)');
        }
    }
}
