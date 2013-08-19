<?php
/**
 * Query Auth Example Implementation
 *
 * @copyright 2013 Jeremy Kendall
 * @license https://github.com/jeremykendall/query-auth-impl/blob/master/LICENSE.md MIT
 * @link https://github.com/jeremykendall/query-auth-impl
 */

date_default_timezone_set('UTC');

require '../vendor/autoload.php';

use Example\ApiCredentials;
use Example\ApiRequestSigner;
use Example\ApiRequestValidator;
use Guzzle\Http\Client as GuzzleClient;
use JSend\JSendResponse;
use QueryAuth\Factory as QueryAuthFactory;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$config = require_once __DIR__ . '/../config.php';
$config['mode'] = (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production');

// API credentials
$credentials = new ApiCredentials($config['api']['key'], $config['api']['secret']);

// QueryAuth Factory for retrieving Server and Client instances
$factory = new QueryAuthFactory();

// The ApiRequestSigner would be used by an API consumer to sign their requests
$requestSigner = new ApiRequestSigner($factory->newClient());

// The ApiRequestValidator would be used by an API creator to validation incoming
// request signatures
$requestValidator = new ApiRequestValidator($factory->newServer());

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
    $readme = Parsedown::instance()->parse(
        file_get_contents(dirname(__DIR__) . '/README.md')
    );
    $app->render('index.html', array('readme' => $readme));
});

/**
 * Sends a signed GET request which returns a famous mangled phrase
 */
$app->get('/get-example', function() use ($app, $credentials, $requestSigner) {

    // Create request
    $guzzle = new GuzzleClient('http://query-auth.dev');
    $request = $guzzle->get('/api/get-example');

    // Sign request
    $requestSigner->signRequest($request, $credentials);

    $response = $request->send();

    $app->render('get.html', array('request' => (string) $request, 'response' => (string) $response));
});

/**
 * Sends a signed POST request to create a new user
 */
$app->get('/post-example', function() use ($app, $credentials, $requestSigner) {

    $params = array(
        'name' => 'Ash',
        'email' => 'ash@s-mart.com',
        'department' => 'Housewares',
    );

    // Create request
    $guzzle = new GuzzleClient('http://query-auth.dev');
    $request = $guzzle->post('/api/post-example', array(), $params);

    // Sign request
    $requestSigner->signRequest($request, $credentials);

    $response = $request->send();

    $app->render('post.html', array('request' => (string) $request, 'response' => (string) $response));
});

/**
 * Validates a signed GET request and, if the request is valid, returns a
 * famous mangled phrase
 */
$app->get('/api/get-example', function () use ($app, $credentials, $requestValidator) {

    try {
        // Validate the request signature
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
 * Validates a signed POST request and, if the request is valid, mimics creating
 * a new user
 */
$app->post('/api/post-example', function() use ($app, $credentials, $requestValidator) {

    $request = $app->request();

    try {
        // Validate the request signature
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
