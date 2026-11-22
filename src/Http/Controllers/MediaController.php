<?php

namespace EffyAds\Laravel\Http\Controllers;

use EffyAds\Laravel\EffyAds;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Detected MIME type => extension used for the stored file. The client's
     * filename and mime_type are never trusted for either.
     */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    // POST /effyads/v1/media/upload — accepts a base64-encoded ad image and stores it on the configured disk
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'filename' => ['nullable', 'string'],
            'base64_data' => ['required', 'string'],
            // Accepted for compatibility but ignored: the type is detected from the bytes.
            'mime_type' => ['nullable', 'string'],
        ]);

        $maxBytes = (int) config('effyads.media_max_kb', 5120) * 1024;
        $encoded = preg_replace('/^data:[^,]*;base64,/', '', $validated['base64_data']);

        // Reject before decoding: base64 is 4/3 the size of the bytes.
        if (strlen($encoded) > (int) ceil($maxBytes / 3) * 4 + 4) {
            return $this->reject('File is too large');
        }

        $bytes = base64_decode($encoded, true);

        if ($bytes === false || $bytes === '') {
            return $this->reject('Invalid base64 data');
        }

        if (strlen($bytes) > $maxBytes) {
            return $this->reject('File is too large');
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $extension = self::EXTENSIONS[$mimeType] ?? null;

        if ($extension === null) {
            return $this->reject('Only JPEG, PNG, GIF and WebP images are allowed');
        }

        $info = @getimagesizefromstring($bytes);

        if ($info === false || ($info['mime'] ?? null) !== $mimeType) {
            return $this->reject('The file is not a valid image');
        }

        $disk = config('effyads.media_disk', 'public');
        $path = $this->directory().'/'.Str::random(40).'.'.$extension;

        Storage::disk($disk)->put($path, $bytes);

        return response()->json([
            'success' => true,
            'url' => Storage::disk($disk)->url($path),
            'mime_type' => $mimeType,
            'filename' => basename($path),
            'width' => $info[0],
            'height' => $info[1],
        ], 201);
    }

    private function directory(): string
    {
        $site = EffyAds::site();

        if ($site === null) {
            return 'effyads';
        }

        // Site keys come from the app, but keep them path-safe anyway.
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $site);

        return 'effyads/'.$safe;
    }

    private function reject(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }
}
