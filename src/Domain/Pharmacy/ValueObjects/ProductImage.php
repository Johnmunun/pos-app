<?php

namespace Src\Domain\Pharmacy\ValueObjects;

/**
 * Value Object: ProductImage
 * 
 * Représente l'image d'un produit avec validation stricte
 * Supporte les formats : JPG, JPEG, PNG, WebP
 * Taille maximale : 2 Mo
 */
final class ProductImage
{
    private const MAX_SIZE = 2 * 1024 * 1024; // 2 Mo en bytes
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private ?string $path;
    private string $type; // 'upload' or 'url'

    private function __construct(?string $path, string $type = 'upload')
    {
        $this->path = $path;
        $this->type = $type;
    }

    /**
     * Créer une ProductImage depuis un chemin d'upload
     */
    public static function fromUpload(?string $path): self
    {
        return new self($path, 'upload');
    }

    /**
     * Créer une ProductImage depuis une URL
     */
    public static function fromUrl(?string $url): self
    {
        if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid image URL');
        }
        return new self($url, 'url');
    }

    /**
     * Créer une ProductImage vide (pas d'image)
     */
    public static function empty(): self
    {
        return new self(null, 'upload');
    }

    /**
     * Valider un fichier uploadé
     */
    public static function validateFile(\Illuminate\Http\UploadedFile $file): void
    {
        // Vérifier la taille
        if ($file->getSize() > self::MAX_SIZE) {
            throw new \InvalidArgumentException('Image size must not exceed 2MB');
        }

        // Vérifier le type MIME
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException('Image must be JPG, JPEG, PNG or WebP');
        }

        // Vérifier l'extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \InvalidArgumentException('Image extension must be jpg, jpeg, png or webp');
        }
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isEmpty(): bool
    {
        return $this->path === null;
    }

    public function isUpload(): bool
    {
        return $this->type === 'upload' && !$this->isEmpty();
    }

    public function isUrl(): bool
    {
        return $this->type === 'url' && !$this->isEmpty();
    }

    /**
     * Obtenir l'URL complète de l'image
     */
    public function getUrl(): ?string
    {
        if ($this->isEmpty()) {
            return null;
        }

        if ($this->isUrl()) {
            return $this->path;
        }

        // Pour les uploads, retourner le chemin relatif (sera complété par le service)
        return $this->path;
    }
}
