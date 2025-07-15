<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;


class MediaService
{
    /**
     * آپلود تصویر - نسخه ساده برای تست
     */
    public function uploadImage(?UploadedFile $file, string $folder = 'images'): ?string
    {
        if (!$file) {
            return null;
        }

        try {
            // ساده‌ترین روش آپلود
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("public/{$folder}", $filename);

            return str_replace('public/', '', $path);
        } catch (\Exception $e) {
            Log::error('Media upload failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * حذف تصویر
     */
    public function deleteImage(?string $path): bool
    {
        return true; // موقتاً true برمی‌گردونیم
    }

    /**
     * دریافت URL کامل تصویر
     */
    public function getImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return asset("storage/{$path}");
    }
}
