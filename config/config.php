<?php

return [
    'listen' => '127.0.0.1:7007',
    'gitea.secret' => '733tD00d',
    'gitee.password' => 'P455w0rd',

    'tasks' => [
        [
            'when' => [
                'driver' => 'gitea',
                'event' => 'push',
                'target' => 'monyxie/webhooked',
            ],
            'where' => ['/outside/code/webhooked'],
            'what' => ['git pull >> a.txt', 'git status >> a.txt'],
        ]
    ]
];
