<?php

namespace Tests\Unit;

use App\Support\ShareInvite;
use PHPUnit\Framework\TestCase;

class ShareInviteTest extends TestCase
{
    public function test_accepts_relative_share_path(): void
    {
        $this->assertSame(
            '/p/'.str_repeat('a', 48),
            ShareInvite::pathFromRedirect('/p/'.str_repeat('a', 48)),
        );
    }

    public function test_accepts_absolute_share_url(): void
    {
        $token = str_repeat('b', 48);
        $this->assertSame(
            '/p/'.$token,
            ShareInvite::pathFromRedirect('https://example.trycloudflare.com/p/'.$token),
        );
    }

    public function test_rejects_open_redirect(): void
    {
        $this->assertNull(ShareInvite::pathFromRedirect('https://evil.example/phish'));
        $this->assertNull(ShareInvite::pathFromRedirect('/dashboard'));
        $this->assertNull(ShareInvite::pathFromRedirect(''));
    }
}
