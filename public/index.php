<?php
/**
 * Query Auth Example Implementation
 *
 * @copyright 2013 Jeremy Kendall
 * @license https://github.com/jeremykendall/query-auth-impl/blob/master/LICENSE.md MIT
 * @link https://github.com/jeremykendall/query-auth-impl
 */

date_default_timezone_set('UTC');
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require '../vendor/autoload.php';

use Example\ApiCredentials;
use Example\ApiRequestSigner;
use Example\ApiRequestValidator;
use Example\Dao\Signature as SignatureDao;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\BadResponseException;
use JSend\JSendResponse;
use QueryAuth\Factory as QueryAuthFactory;
use Slim\Log;
use Slim\Slim;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$config = require_once __DIR__ . '/../config.php';

$credentials = new ApiCredentials($config['api']['key'], $config['api']['secret']);
$factory = new QueryAuthFactory();

// The ApiRequestSigner would be used by an API consumer to sign their requests
$requestSigner = new ApiRequestSigner($factory->newClient());

// The ApiRequestValidator would be used by an API creator to validate incoming requests
$requestValidator = new ApiRequestValidator($factory->newServer());

// Middleware to validate incoming request signatures
$validateSignature = function(Slim $app, ApiCredentials $credentials, ApiRequestValidator $requestValidator) {
    return function() use ($app, $credentials, $requestValidator) {
        $response = $app->response();

        try {
            if ($requestValidator->isValid($app->request(), $credentials) === false) {
                $jsend = new JSendResponse('fail', array('message' => 'Invalid signature'));
                $response->setStatus(403);
                $response->headers->set('Content-Type', 'application/json');
                $response->setBody($jsend->encode());
            }
        } catch (\Exception $e) {
            $jsend = new JSendResponse('error', array(), $e->getMessage(), $e->getCode());
            $response->setStatus(403);
            $response->headers->set('Content-Type', 'application/json');
            $response->setBody($jsend->encode());
        }
    };
};

// Prepare app
$app = new Slim($config['slim']);

$app->configureMode('development', function () use ($app) {
    $app->config(array(
        'log.enabled' => true,
        'log.level' => Log::DEBUG
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

    // Send request
    try {
        $response = $request->send();
    } catch (BadResponseException $bre) {
        $response = $bre->getResponse();
    }

    // Render template with data
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

    // Send request
    try {
        $response = $request->send();
    } catch (BadResponseException $bre) {
        $response = $bre->getResponse();
    }

    $app->render('post.html', array('request' => (string) $request, 'response' => (string) $response));
});

/**
 * Sends a signed POST request to create a new user, OR replays a previous POST request
 */
$app->map('/replay-example', function () use ($app, $credentials, $requestSigner) {

    // Create request
    $guzzle = new GuzzleClient('http://query-auth.dev');
    $request = $guzzle->post('/api/replay-example');

    // Build a new request
    if ($app->request()->isGet()) {

        $params = array(
            'name' => 'Ash',
            'email' => 'ash@s-mart.com',
            'department' => 'Housewares',
        );

        // Add new user data to request
        foreach ($params as $name => $value) {
            $request->setPostField($name, $value);
        }

        // Sign request
        $requestSigner->signRequest($request, $credentials);
    }

    // Build a replay request
    if ($app->request()->isPost()) {

        // Set a previous request's data on a new request
        foreach ($app->request()->post() as $param => $value) {
            $request->setPostField($param, $value);
        }
    }

    // Send request
    try {
        $response = $request->send();
    } catch (BadResponseException $bre) {
        $response = $bre->getResponse();
    }

    $app->render('replay.html', array(
        'request' =>  (string) $request, 
        'response' => (string) $response, 
        'postFields' => $request->getPostFields(),
    ));
})->via('GET', 'POST');

/**
 * Uses $validateSignature to ensure the request signature is valid.
 * If not valid, return the response generated by `$validateSignature`.
 * If valid, return the famous mangled phrase.
 */
$app->get('/api/get-example', $validateSignature($app, $credentials, $requestValidator), function () use ($app) {
    
    $response = $app->response();

    // If client error (400 - 499) because signature is invalid, return response
    if ($response->isClientError()) {
        return $response;
    }

    $mistakes = array('necktie', 'neckturn', 'nickle', 'noodle');
    $format = 'Klaatu... barada... n... %s!';
    $data = array('message' => sprintf($format, $mistakes[array_rand($mistakes)]));
    $jsend = new JSendResponse('success', $data);

    $response->headers->set('Content-Type', 'application/json');
    $response->setBody($jsend->encode());
    return $response;
});

/**
 * Uses $validateSignature to ensure the request signature is valid.
 * If not valid, return the response generated by `$validateSignature`.
 * If valid, return the famous mangled phrase.
 */
$app->post('/api/post-example', $validateSignature($app, $credentials, $requestValidator), function() use ($app) {

    $response = $app->response();

    // If client error (400 - 499) because signature is invalid, return response
    if ($response->isClientError()) {
        return $response;
    }

    $params = $app->request()->post();

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

    $response->headers->set('Content-Type', 'application/json');
    $response->setBody($jsend->encode());
    return $response;
});

/**
 * Uses $validateSignature to ensure the request signature is valid.
 * If valid, save the API key, request signature, and signature expiration timestamp
 *     If the save is successful, this is a new request
 *     If the save is unsuccessful, this is a replayed request and is denied
 * If not valid, return the response generated by `$validateSignature`.
 */
$app->post('/api/replay-example', $validateSignature($app, $credentials, $requestValidator), function() use ($app, $config) {

    $response = $app->response();

    // If client error (400 - 499) because signature is invalid, return response
    if ($response->isClientError()) {
        return $response;
    }

    try {
        $db = new \PDO(
            $config['pdo']['dsn'],
            $config['pdo']['username'],
            $config['pdo']['password'],
            $config['pdo']['options']
        );

        $params = $app->request()->post();

        $signatureDao = new SignatureDao($db);
        $signatureDao->save($params['key'], $params['signature'], (int) gmdate('U') + 3600);

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
    } catch (\PDOException $pe) {
        if ($pe->getCode() == 23000) {
            $response->setStatus(403);
            $jsend = new JSendResponse('error', array(), sprintf('REPLAYED REQUEST: %s', $pe->getMessage()), $pe->getCode());
        }
    } catch (\Exception $e) {
        $response->setStatus(400);
        $jsend = new JSendResponse('error', array(), $e->getMessage(), $e->getCode());
    }

    $response->headers->set('Content-Type', 'application/json');
    $response->setBody($jsend->encode());
    return $response;
});

// Run app
$app->run();
