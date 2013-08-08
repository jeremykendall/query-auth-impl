<?php

date_default_timezone_set('UTC');

require '../vendor/autoload.php';

use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$config = require_once __DIR__ . '/../config.php';
$config['mode'] = (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production');

// Prepare app
$app = new Slim\Slim($config['slim']);

// Only invoked if mode is "development"
$app->configureMode('development', function () use ($app) {
    error_reporting(-1);
    ini_set('display_errors', 1);

    $app->config(array(
        'log.enabled' => true,
        'log.level'   => Slim\Log::DEBUG
    ));

    $app->getLog()->debug('wtf');
});

$app->configureMode('development', function () use ($app) {
    error_reporting(-1);
    ini_set('display_errors', 1);

    $app->config(array(
        'log.enabled' => true,
        'log.level'   => Slim\Log::ERROR
    ));
});

// Prepare view
$app->view(new Twig());
$app->view->parserOptions = $config['twig'];
$app->view->parserExtensions = array(
    new TwigExtension()
);

// Define routes
$app->get('/', function () use ($app) {
    $app->render('index.html');
});

// Run app
$app->run();
