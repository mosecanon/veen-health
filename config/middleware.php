<?php
return [
    'global' => ['csrf'],
    'aliases' => [
        'csrf'  => App\Middleware\VerifyCsrfToken::class,
        'auth'  => App\Middleware\Authenticate::class,
        'guest' => App\Middleware\RedirectIfAuthenticated::class,
        'can'   => App\Middleware\Authorize::class,
        'super' => App\Middleware\SuperAdmin::class,
        'tenant' => App\Middleware\EnsureTenant::class,
    ],
];
