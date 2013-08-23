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

### GET Example

#### Client: Sending a signed GET request

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

        $response = $request->send();

        $app->render('get.html', array('request' => (string) $request, 'response' => (string) $response));
    });

#### Server: Validating a signed GET request

Validates the GET request and returns the famous mangled phrase:

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

### POST Example

#### Client: Sending a signed POST request

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

        $response = $request->send();

        $app->render('post.html', array('request' => (string) $request, 'response' => (string) $response));
    });

#### Server: Validating a signed POST request

Validates the POST request and returns new user data:

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
