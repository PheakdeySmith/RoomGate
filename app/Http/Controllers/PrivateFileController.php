<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivateFileController extends Controller
{
    public function show(Request $request, string $path)
    {
        if (!Auth::check()) {
            abort(401);
        }

        $normalized = str_replace(['\\', "\0"], '/', $path);
        $normalized = ltrim($normalized, '/');

        if (str_contains($normalized, '..')) {
            abort(400);
        }

        $baseDir = public_path('uploads/private');
        $fullPath = $baseDir . DIRECTORY_SEPARATOR . $normalized;
        $realPath = realpath($fullPath);

        if (!$realPath || !str_starts_with($realPath, $baseDir)) {
            abort(404);
        }

        $user = Auth::user();
        $segments = explode('/', $normalized);
        $ownerId = $segments[0] ?? null;

        if (
            !$user->hasAnyRole(['admin', 'platform_admin']) &&
            (string) $user->id !== (string) $ownerId
        ) {
            abort(403);
        }

        if (!is_file($realPath)) {
            abort(404);
        }

        app(AuditLogger::class)->log(
            'downloaded',
            'PrivateFile',
            $normalized,
            null,
            ['path' => $normalized, 'size' => filesize($realPath)],
            $request
        );

        if ($request->boolean('download')) {
            return response()->download($realPath);
        }

        return response()->file($realPath);
    }
}
