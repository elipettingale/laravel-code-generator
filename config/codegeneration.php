<?php

return [

    'stub-directory' => __DIR__ . '/../resources/stubs',

    'factories' => [
        'generator' => 'App\Services\GeneratorFactory',
        'stub' => 'App\Services\StubFactory'
    ],

    'generator-aliases' => [
        'test' => 'example'
    ]

];
