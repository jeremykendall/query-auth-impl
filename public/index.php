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

use Example\Dao\Signature as SignatureDao;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\BadResponseException;
use JSend\JSendResponse;
use QueryAuth\Credentials\Credentials;
use QueryAuth\Factory as QueryAuthFactory;
use QueryAuth\Request\RequestValidator;
use QueryAuth\Request\Adapter\Outgoing\GuzzleHttpRequestAdapter;
use QueryAuth\Request\Adapter\Incoming\SlimRequestAdapter;
use Slim\Slim;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$config = require_once __DIR__ . '/../config.php';

// Prepare app
$app = new Slim($config['slim']);

$factory = new QueryAuthFactory();
$requestSigner = $factory->newRequestSigner();
$requestValidator = $factory->newRequestValidator();

$app->credentials = new Credentials($config['api']['key'], $config['api']['secret']);

// Route middleware to validate incoming request signatures
// See http://docs.slimframework.com/#Route-Middleware
$validateSignature = function (Slim $app, RequestValidator $requestValidator) {
    return function () use ($app, $requestValidator) {
        $response = $app->response;

        try {
            // Grabbing credentials from app container kind of mimics grabbing
            // credentials from persistent storage
            $credentials = $app->credentials;

            $isValid = $requestValidator->isValid(
                new SlimRequestAdapter($app->request),
                $credentials
            );

            if ($isValid === false) {
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
$app->get('/get-example', function () use ($app, $requestSigner) {

    // Create request
    $guzzle = new GuzzleHttpClient(['base_url' => 'http://query-auth.dev']);
    $request = $guzzle->createRequest('GET', '/api/get-example');

    // Sign request
    $requestSigner->signRequest(new GuzzleHttpRequestAdapter($request), $app->credentials);

    // Send request
    try {
        $response = $guzzle->send($request);
    } catch (BadResponseException $bre) {
        $response = $bre->getResponse();
    }

    // Render template with data
    $app->render('get.html', array('request' => (string) $request, 'response' => (string) $response));
});

/**
 * Sends a signed POST request to create a new user
 */
$app->get('/post-example', function () use ($app, $requestSigner) {

    $params = array(
        'name' => 'Ash',
        'email' => 'ash@s-mart.com',
        'department' => 'Housewares',
    );

    // Create request
    $guzzle = new GuzzleHttpClient(['base_url' => 'http://query-auth.dev']);
    $request = $guzzle->createRequest('POST', '/api/post-example', ['body' => $params]);

    // Sign request
    $requestSigner->signRequest(new GuzzleHttpRequestAdapter($request), $app->credentials);

    // Send request
    try {
        $response = $guzzle->send($request);
    } catch (BadResponseException $bre) {
        $response = $bre->getResponse();
    }

    $app->render('post.html', array('request' => (string) $request, 'response' => (string) $response));
});

/**
 * Sends a signed POST request to create a new user, OR replays a previous POST request
 */
$app->map('/replay-example', function () use ($app, $requestSigner) {

    // Create request
    $guzzle = new GuzzleHttpClient(['base_url' => 'http://query-auth.dev']);
    $request = $guzzle->createRequest('POST', '/api/replay-example');

    // Build a new request
    if ($app->request()->isGet()) {

        $params = array(
            'name' => 'Ash',
            'email' => 'ash@s-mart.com',
            'department' => 'Housewares',
        );

        $request->getBody()->replaceFields($params);

        // Sign request
        $requestSigner->signRequest(new GuzzleHttpRequestAdapter($request), $app->credentials);
    }

    // Build a replay request
    if ($app->request()->isPost()) {
        // Set a previous request's data on a new request
        $request->getBody()->replaceFields($app->request->post());
    }

    // Send request
    try {
        $response = $guzzle->send($request);
    } catch (BadResponseException $bre) {
        $response = $bre->getResponse();
    }

    $app->render('replay.html', array(
        'request' =>  (string) $request,
        'response' => (string) $response,
        'postFields' => $request->getBody()->getFields(),
    ));
})->via('GET', 'POST');

/**
 * Uses $validateSignature to ensure the request signature is valid.
 * If not valid, return the response generated by `$validateSignature`.
 * If valid, return the famous mangled phrase.
 */
$app->get('/api/get-example', $validateSignature($app, $requestValidator), function () use ($app) {

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
$app->post('/api/post-example', $validateSignature($app, $requestValidator), function () use ($app) {

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
$app->post('/api/replay-example', $validateSignature($app, $requestValidator), function () use ($app, $config) {

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
