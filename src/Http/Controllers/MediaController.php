<?php

namespace EffyAds\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    // POST /effyads/v1/media/upload — accepts base64-encoded ad creative, stores it on the configured disk
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'filename' => ['required', 'string'],
            'base64_data' => ['required', 'string'],
            'mime_type' => ['nullable', 'string'],
        ]);

        $imageData = base64_decode($validated['base64_data'], true);

        if ($imageData === false) {
            return response()->json(['success' => false, 'message' => 'Invalid base64 data'], 400);
        }

        $mimeType = $validated['mime_type'] ?? (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageData);

        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return response()->json(['success' => false, 'message' => 'Only image files are allowed'], 400);
        }

        $disk = config('effyads.media_disk', 'public');
        $extension = pathinfo($validated['filename'], PATHINFO_EXTENSION) ?: 'jpg';
        $path = 'effyads/'.Str::random(20).'.'.$extension;

        Storage::disk($disk)->put($path, $imageData);

        return response()->json([
            'success' => true,
            'url' => Storage::disk($disk)->url($path),
            'mime_type' => $mimeType,
            'filename' => basename($path),
        ], 201);
    }
}
