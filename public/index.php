<?php

date_default_timezone_set('UTC');

require '../vendor/autoload.php';

use Example\ApiCredentials;
use Example\ApiRequestSigner;
use Example\ApiRequestValidator;
use Guzzle\Http\Client as GuzzleClient;
use JSend\JSendResponse;
use QueryAuth\Client as QueryAuthClient;
use QueryAuth\NormalizedParameterCollection;
use QueryAuth\Server as QueryAuthServer;
use QueryAuth\Signer as QueryAuthSigner;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$config = require_once __DIR__ . '/../config.php';
$config['mode'] = (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production');

// API credentials
$credentials = new ApiCredentials($config['api']['key'], $config['api']['secret']);

$collection = new NormalizedParameterCollection();
$signer = new QueryAuthSigner($collection);
$requestSigner = new ApiRequestSigner(new QueryAuthClient($signer));
$requestValidator = new ApiRequestValidator(new QueryAuthServer($signer));

// Prepare app
$app = new Slim\Slim($config['slim']);

$app->configureMode('development', function () use ($app) {
    error_reporting(-1);
    ini_set('display_errors', 1);

    $app->config(array(
        'log.enabled' => true,
        'log.level' => Slim\Log::DEBUG
    ));
});

// Prepare view
$app->view(new Twig());
$app->view->parserOptions = $config['twig'];
$app->view->parserExtensions = array(new TwigExtension());

// Define routes
$app->get('/', function () use ($app) {
    $app->render('index.html');
});

/**
 * Sends a GET request which returns a famous mangled phrase
 */
$app->get('/phrase', function() use ($credentials, $requestSigner) {

    $guzzle = new GuzzleClient('http://query-auth.dev');
    $request = $guzzle->get('/api/phrase');
    $requestSigner->signRequest($request, $credentials);

    $response = $request->send();

    var_dump(JSendResponse::decode($response->getBody()));
});

/**
 * Sends a POST request to create a new user
 */
$app->get('/new-user', function() use ($credentials, $requestSigner) {

    $params = array(
        'name' => 'Ash',
        'email' => 'ash@s-mart.com',
        'department' => 'Housewares',
    );

    $guzzle = new GuzzleClient('http://query-auth.dev');
    $request = $guzzle->post('/api/user', array(), $params);
    $requestSigner->signRequest($request, $credentials);

    $response = $request->send();

    var_dump(JSendResponse::decode($response->getBody()));
});

/**
 * Accepts a signed GET request and returns a famous mangled phrase
 */
$app->get('/api/phrase', function () use ($app, $credentials, $requestValidator) {

    try {
        $isValid = $requestValidator->isValid($app->request(), $credentials);

        if ($isValid) {
            $mistakes = array('necktie', 'neckturn', 'nickle', 'noodle');
            $format = 'Klaatu... barada... n... %s!';
            $data = array('message' => sprintf($format, $mistakes[array_rand($mistakes)]));
            $jsend = new JSendResponse('success', $data);
        } else {
            $jsend = new JSendResponse('fail', array('message' => 'Invalid signature'));
        }
    } catch (\Exception $e) {
        $jsend = new JSendResponse('error', array(), $e->getMessage());
    }

    $response = $app->response();
    $response['Content-Type'] = 'application/json';
    echo $jsend->encode();
});

/**
 * Accepts a signed POST request to mimic creating a new user
 */
$app->post('/api/user', function() use ($app, $credentials, $requestValidator) {

    $request = $app->request();

    try {
        $isValid = $requestValidator->isValid($request, $credentials);

        if ($isValid) {
            $params = $request->post();

            // Assume appropriate POST action of some sort, in this case saving
            // a new user and returning the persisted user data.
            $data = array(
                'user' => array(
                    'id' => 666,
                    'name' => $params['name'],
                    'email' => $params['email'],
                    'department' => $params['department'],
                ),
            );

            $jsend = new JSendResponse('success', $data);
        } else {
            $jsend = new JSendResponse('fail', array('message' => 'Invalid signature'));
        }
    } catch (\Exception $e) {
        $jsend = new JSendResponse('error', array(), $e->getMessage());
    }

    $response = $app->response();
    $response['Content-Type'] = 'application/json';
    echo $jsend->encode();
});

// Run app
$app->run();
