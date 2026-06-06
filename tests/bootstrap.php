<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env.test.local')) {
    (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/.env.test.local');
} elseif (file_exists(dirname(__DIR__) . '/.env.test')) {
    (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/.env.test');
}
