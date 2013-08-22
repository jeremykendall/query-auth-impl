<?php

$database = __DIR__ . '/db/example.db';

$config = array(
    'slim' => array(
        'templates.path' => __DIR__ . '/templates',
        'log.level' => Slim\Log::ERROR,
        'log.enabled' => true,
        'log.writer' => new Slim\Extras\Log\DateTimeFileWriter(
            array(
                'path' => __DIR__ . '/logs',
                'name_format' => 'Y-m-d'
            )
        )
    ),
    'api' => array(
        // You wouldn't version these in a public repo. Obviously. Duh.
        'key' => 'ah5yEgQzjuFsC9nWsRI4Nar3ikOqWVPcD3OntHpg',
        'secret' => 'M/4SVUQwO0qqvnXgENfylhocR.e1FQYHYeFTn808n8YR3oojPtR0HkB5E/Ms',
    ),
    'twig' => array(
        'charset' => 'utf-8',
        'cache' => realpath(__DIR__ . '/templates/cache'),
        'auto_reload' => true,
        'strict_variables' => false,
        'autoescape' => true
    ),
    'database' => $database,
    'pdo' => array(
        'dsn' => 'sqlite:' . $database,
        'username' => null,
        'password' => null,
        'options' => array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    ),
    'cookies' => array(
        'expires' => '20 minutes',
        'path' => '/',
        'domain' => null,
        'secure' => true,
        'httponly' => false,
        'name' => 'slim_session',
        'secret' => 'CHANGE_ME. FOR_REAL, CHANGE_ME',
        'cipher' => MCRYPT_RIJNDAEL_256,
        'cipher_mode' => MCRYPT_MODE_CBC
    ),
);

return $config;
