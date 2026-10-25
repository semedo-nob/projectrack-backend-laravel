<?php

return [
    /*
    | Comma-separated proxy addresses allowed to set X-Forwarded-* headers.
    | Nginx on this host is 127.0.0.1. A value of "*" is ignored by bootstrap.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES', '127.0.0.1'),
];
