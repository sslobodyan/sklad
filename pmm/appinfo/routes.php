<?php
return [
    'routes' => [
        // API має бути ПЕРЕД catchAll
        ['name' => 'settings#save', 'url' => '/api/settings', 'verb' => 'POST'],
        // Сторінки
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#catchAll', 'url' => '/{path}', 'verb' => 'GET', 'requirements' => ['path' => '.+']],
        ['name' => 'page#catchAllPost', 'url' => '/{path}', 'verb' => 'POST', 'requirements' => ['path' => '.+']],
    ],
];