<?php

namespace App\Support;

class ShareInvite
{
    /**
     * Allow only local share paths (/p/{token}) from login/register redirects.
     */
    public static function pathFromRedirect(?string $redirect): ?string
    {
        if (! is_string($redirect) || $redirect === '') {
            return null;
        }

        $path = parse_url($redirect, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = str_starts_with($redirect, '/') ? explode('?', $redirect, 2)[0] : null;
        }
        if (! is_string($path)) {
            return null;
        }

        return preg_match('#^/p/[A-Za-z0-9_-]{16,}$#', $path) === 1 ? $path : null;
    }
}
