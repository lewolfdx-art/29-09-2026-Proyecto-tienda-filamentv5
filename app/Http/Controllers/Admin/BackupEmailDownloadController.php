<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupEmailDownloadController extends Controller
{
    public function __invoke(Request $request, string $file): BinaryFileResponse
    {
        abort_unless((bool) preg_match('/^[A-Za-z0-9._-]+$/', $file), 404);

        $user = $request->user('web');

        abort_unless($user?->can('ViewAny:EmailRecipient') ?? false, 403);

        $path = 'backup-emails/'.$file;
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $file, ['Content-Type' => 'application/zip']);
    }
}
