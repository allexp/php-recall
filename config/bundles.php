<?php

declare(strict_types=1);

// Список Symfony-бандлов, подключаемых во всех окружениях приложения.
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
];
