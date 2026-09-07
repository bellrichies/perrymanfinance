<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Content;

use DateTimeImmutable;
use DateTimeZone;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\MediaRepository;

final readonly class MediaService
{
    public function __construct(
        private MediaRepository $media,
        private AuditLogRepository $audit,
        private Config $config,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->media->all();
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    public function upload(array $file, ?string $alt, int $actor, ?string $requestId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)) {
            throw new ValidationException(['file' => ['Choose a valid image upload.']]);
        }
        $size = (int) ($file['size'] ?? 0);
        $maxBytes = (int) $this->config->get('media.max_bytes', 5_242_880);
        if ($size < 1 || $size > $maxBytes || !is_file($file['tmp_name'])) {
            throw new ValidationException(['file' => ['The image is empty or exceeds the upload limit.']]);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $dimensions = getimagesize($file['tmp_name']);
        $bytes = file_get_contents($file['tmp_name']);
        $decoded = is_string($bytes) ? @imagecreatefromstring($bytes) : false;
        if (!is_string($mime) || !isset($allowed[$mime]) || !is_array($dimensions) || $decoded === false) {
            throw new ValidationException(['file' => ['Only valid JPEG, PNG, or WebP images are allowed.']]);
        }
        imagedestroy($decoded);
        [$width, $height] = $dimensions;
        $maxDimension = (int) $this->config->get('media.max_dimension', 6000);
        if ($width < 1 || $height < 1 || $width > $maxDimension || $height > $maxDimension) {
            throw new ValidationException(['file' => ['The image dimensions exceed the allowed limit.']]);
        }
        $uuid = $this->uuid();
        $relative = gmdate('Y/m') . '/' . bin2hex(random_bytes(20)) . '.' . $allowed[$mime];
        $root = rtrim($this->config->string('media.path'), '/\\');
        $directory = $root . DIRECTORY_SEPARATOR . dirname($relative);
        if ($root === '' || (!is_dir($directory) && !mkdir($directory, 0750, true))) {
            throw new \RuntimeException('The media storage directory is unavailable.');
        }
        $destination = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('The uploaded image could not be stored.');
        }
        chmod($destination, 0640);
        $now = $this->now();
        $this->media->create([
            'uuid' => $uuid, 'disk' => 'local', 'path' => $relative,
            'name' => basename(is_string($file['name'] ?? null) ? $file['name'] : 'image'),
            'mime' => $mime, 'size' => $size, 'width' => $width, 'height' => $height,
            'alt' => $alt === null ? null : mb_substr(trim($alt), 0, 255), 'actor' => $actor, 'now' => $now,
        ]);
        $this->audit->record($actor, 'media.uploaded', ['subject_type' => 'media_asset', 'subject_id' => $uuid, 'request_id' => $requestId, 'mime_type' => $mime, 'byte_size' => $size], $now);
        return $this->media->find($uuid) ?? throw new \RuntimeException('Stored media metadata was not found.');
    }

    public function delete(string $uuid, int $actor, ?string $requestId): void
    {
        $asset = $this->media->find($uuid) ?? throw new NotFoundException();
        $root = rtrim($this->config->string('media.path'), '/\\');
        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $asset['path']);
        $realRoot = realpath($root);
        $realPath = realpath($path);
        if ($realRoot !== false && $realPath !== false && str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR)) {
            unlink($realPath);
        }
        $now = $this->now();
        $this->media->delete($uuid, $now);
        $this->audit->record($actor, 'media.deleted', ['subject_type' => 'media_asset', 'subject_id' => $uuid, 'request_id' => $requestId], $now);
    }

    /** @return array<string, mixed> */
    public function update(string $uuid, ?string $alt, int $actor, ?string $requestId): array
    {
        $this->media->find($uuid) ?? throw new NotFoundException();
        $now = $this->now();
        $safeAlt = $alt === null || trim($alt) === '' ? null : mb_substr(strip_tags($alt), 0, 255);
        $this->media->updateAltText($uuid, $safeAlt, $now);
        $this->audit->record($actor, 'media.updated', ['subject_type' => 'media_asset', 'subject_id' => $uuid, 'request_id' => $requestId], $now);
        return $this->media->find($uuid) ?? throw new NotFoundException();
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
