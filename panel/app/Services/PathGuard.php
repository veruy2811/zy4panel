<?php

namespace App\Services;

class PathGuard
{
    public static function clean(?string $path): string
    {
        $path = trim((string) $path);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?: '/';

        if ($path === '' || $path === '.') {
            return '/';
        }

        abort_if(str_contains($path, '../') || str_contains($path, '..\\') || $path === '..' || str_starts_with($path, '../'), 422, 'Invalid path.');

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }
}
