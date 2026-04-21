<?php

namespace App\Http\Controllers;

use App\Support\StorageRole;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageFileController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        $disk = StorageRole::public();

        abort_unless($disk->exists($path), 404);

        /** @var string $mimeType */
        $mimeType = $disk->mimeType($path);

        return $disk->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
