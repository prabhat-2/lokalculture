<?php

declare(strict_types=1);

final class ImageUploader
{
    public function __construct(private string $directory, private string $publicPrefix = '/uploads/products/')
    {
    }

    public function store(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Choose an image file to upload.');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('Images must be smaller than 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Upload a valid JPG, PNG, or WebP image.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true)) {
            throw new RuntimeException('The upload directory could not be created.');
        }
        if (!move_uploaded_file($file['tmp_name'], $this->directory . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException('The image could not be saved.');
        }
        return $this->publicPrefix . $filename;
    }
}