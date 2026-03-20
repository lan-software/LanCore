<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageFileController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        abort_unless(Storage::exists($path), 404);

        /** @var string $mimeType */
        $mimeType = Storage::mimeType($path);

        return Storage::response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
