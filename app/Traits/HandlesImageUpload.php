<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Validation\ValidationException;

trait HandlesImageUpload
{
    /**
     * Handle single or multiple image uploads
     */
    protected function storeImage(
        UploadedFile|array $files,
        string $directory,
        ?int $maxWidth = 1400,
        string $disk = 'public'
    ) {
        if (is_array($files)) {
            $paths = [];
            foreach ($files as $file) {
                $paths[] = $this->processSingleImage($file, $directory, $maxWidth, $disk);
            }
            return $paths;
        }

        return $this->processSingleImage($files, $directory, $maxWidth, $disk);
    }

    /**
     * Handle single image upload + optimization
     */
    private function processSingleImage(
        UploadedFile $file,
        string $directory,
        ?int $maxWidth,
        string $disk = 'public'
    ): string {
        try {
            // Clean filename
            $extension = strtolower($file->getClientOriginalExtension());
            $baseName  = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

            // Add timestamp only to filename (not folder)
            $timestamp = now()->format('Y_m_d_His');
            $newName   = "{$baseName}_{$timestamp}.{$extension}";

            // Final path (only directory, no year/month)
            $directory = trim($directory, '/');
            $finalPath = "{$directory}/{$newName}";

            // 🚀 Ensure directory exists
            if (!Storage::disk($disk)->exists($directory)) {
                Storage::disk($disk)->makeDirectory($directory, 0775, true);
            }

            // Load image
            $image = Image::read($file->getRealPath())
                ->orient();

            // Resize
            if ($maxWidth && $image->width() > $maxWidth) {
                $image->resize($maxWidth, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Encode in original extension
            $encoded = match ($extension) {
                'jpg', 'jpeg' => $image->toJpeg(85),
                'png'         => $image->toPng(),
                'webp'        => $image->toWebp(85),
                default       => $image->encode(),
            };

            // Save file
            Storage::disk($disk)->put($finalPath, (string) $encoded, [
                'visibility' => 'public'
            ]);

            // Permission (optional)
            try {
                @chmod(Storage::disk($disk)->path($finalPath), 0644);
            } catch (\Throwable $e) {}

            return $finalPath;

        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'image' => ['The uploaded image is invalid or cannot be processed.']
            ]);
        }
    }

    protected function deleteOldImage(?string $path, string $disk = 'public')
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
