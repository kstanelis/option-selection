<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = new Dotenv();
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv->usePutenv()->bootEnv(dirname(__DIR__) . '/.env');
}
if (file_exists(dirname(__DIR__) . '/.env.test.local')) {
    $dotenv->usePutenv()->bootEnv(dirname(__DIR__) . '/.env.test.local');
} elseif (file_exists(dirname(__DIR__) . '/.env.test')) {
    $dotenv->usePutenv()->bootEnv(dirname(__DIR__) . '/.env.test');
}
