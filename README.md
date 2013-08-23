# Query Auth Example Implementation

#### Example implementation of the [Query Auth library](https://github.com/jeremykendall/query-auth)

## Requirements

In order to run this example implementation, you'll need to have the following
installed:

* [Vagrant](http://www.vagrantup.com/)
* [VirtualBox](https://www.virtualbox.org/)

## Usage

* Clone repo
* `cd /path/to/repo`
* Run `vagrant up`
* Add `192.168.56.102 query-auth.dev` to `/etc/hosts`
* Open a browser and visit [http://query-auth.dev](http://query-auth.dev)

## Request Signing

Request signing has been abstracted in the `Example\ApiRequestSigner` class.
Signing requests is now as simple as passing the request object and credentials
object to the `signRequest` method:

    /**
     * Signs API request
     *
     * @param RequestInterface $request     HTTP Request
     * @param ApiCredentials   $credentials API Credentials
     */
    public function signRequest(RequestInterface $request, ApiCredentials $credentials)
    {
        $signedParams = $this->client->getSignedRequestParams(
            $credentials->getKey(),
            $credentials->getSecret(),
            $request->getMethod(),
            $request->getHost(),
            $request->getPath(),
            $this->getParams($request)
        );

        $this->replaceParams($request, $signedParams);
    }

## Signature Validation

Signature validation has been abstracted in the `Example\ApiRequestValidator`
class. Validating request signatures is now as simple as passing the request
object and credentials object to the `isValid` method:

    /**
     * Validates an API request
     *
     * @param  Request        $request     HTTP Request
     * @param  ApiCredentials $credentials API Credentials
     * @return bool           True if valid, false if invalid
     */
    public function isValid(Request $request, ApiCredentials $credentials)
    {
        return $this->server->validateSignature(
            $credentials->getSecret(),
            $request->getMethod(),
            $request->getHost(),
            $request->getPath(),
            $this->getParams($request)
        );
    }

## Implementation Examples

All code samples below can be found in `/public/index.php`.

### Using Slim Middleware to Validate Request Signatures

Since this example implementation includes multiple routes that require signature
validation, I decided to use Slim Framework's [Route Middleware](http://docs.slimframework.com/#Route-Middleware)
so that I'd only have to write the code once. When you see `$validateSignature`
attached to the `/api/*` routes below, know that the validation is being performed
by the middleware before the code in those routes is being executed.

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

### GET Example

#### Client: Sending a Signed GET Request

Visit [http://query-auth.dev/get-example](http://query-auth.dev/get-example) to see an example of a signed GET request:

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

#### Server: Handling a GET Request

Uses `$validateSignature` to ensure the request signature is valid.
* If not valid, return the response generated by `$validateSignature`.
* If valid, return the famous mangled phrase.

    /**
     * Validates a signed GET request and, if the request is valid, returns a
     * famous mangled phrase
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

### POST Example

#### Client: Sending a Signed POST Request

Visit [http://query-auth.dev/post-example](http://query-auth.dev/post-example) to see an example of a signed POST request:

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

#### Server: Handling a POST Request

Uses `$validateSignature` to ensure the request signature is valid.
* If valid, save the new user and return the new user data.
* If not valid, return the response generated by `$validateSignature`.

    /**
     * Validates a signed POST request and, if the request is valid, mimics creating
     * a new user
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

### Replay Prevention Example

#### Client: Sending a Signed POST Request OR Replaying a Previous Request

Visit [http://query-auth.dev/replay-example](http://query-auth.dev/replay-example) to see an example of replay prevention:

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

#### Server: Handling a Replayed Request

Uses `$validateSignature` to ensure the request signature is valid.
* If valid, save the API key, request signature, and signature expiration timestamp
    * If the save is successful, this is a new request
    * If the save is unsuccessful, this is a replayed request and is denied
* If not valid, return the response generated by `$validateSignature`.

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

## Running Tests

Unit and Integration tests are provided in this example implementation. You can
run the tests from the command line by executing `./vendor/bin/phpunit` from
the command line.

**IMPORTANT**: The VM *must* be running in order to run the integration tests.
If you'd like to run just the unit tests, include the `--exclude-group` flag,
like so: `./vendor/bin/phpunit --exclude-group=vm-required`.

## Credits

This example implementation makes use of the following external dependencies:

* [Slim Framework](http://slimframework.com/): A PHP microframework
* [Guzzle](http://guzzlephp.org/): A PHP HTTP client, used here to send requests
* [JSend](https://github.com/shkm/JSend): [Jamie Schembri's](https://twitter.com/shkm)
  PHP implementation of the OmniTI [JSend specifiction](http://labs.omniti.com/labs/jsend)
* [Parsedown PHP](https://github.com/erusev/parsedown): Emanuil Rusev's Markdown parser for PHP
* [Composer](http://getcomposer.org) Dependency Manager for PHP
