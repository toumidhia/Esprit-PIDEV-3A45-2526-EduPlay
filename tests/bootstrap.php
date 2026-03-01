<?php
// tests/bootstrap.php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    // Charge le fichier .env.test
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env.test');
}

// S'assurer que l'environnement est bien 'test'
$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';

// Définir les variables d'environnement par défaut si elles ne sont pas définies
if (empty($_ENV['ABSTRACT_API_KEY'])) {
    $_ENV['ABSTRACT_API_KEY'] = 'test_api_key_12345';
    $_SERVER['ABSTRACT_API_KEY'] = 'test_api_key_12345';
}

if (empty($_ENV['DATABASE_URL'])) {
    $_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
    $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
}