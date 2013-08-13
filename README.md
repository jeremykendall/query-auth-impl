# Query Auth Sample Implementation

Example implementation of the [Query
Auth](https://github.com/jeremykendall/query-auth) API query authentication
library.

## Requirements

In order to run this sample implementation, you'll need to have the following
installed:

* [Vagrant](http://www.vagrantup.com/)
* [VirtualBox](https://www.virtualbox.org/)

## Features

This sample implementation makes use of the following tools:

* [Slim Framework](http://slimframework.com/): A PHP microframework
* [Guzzle](http://guzzlephp.org/): A PHP HTTP client, used here to send requests
* [JSend](https://github.com/shkm/JSend): [Jamie Schembri's](https://twitter.com/shkm)
  PHP implementation of the OmniTI [JSend specifiction](http://labs.omniti.com/labs/jsend)
* [Parsedown PHP](https://github.com/erusev/parsedown): Emanuil Rusev's Markdown parser for PHP

## Usage

* Clone repo
* `cd /path/to/repo`
* Run `vagrant up`
* Add `192.168.56.102 query-auth.dev` to `/etc/hosts`
* Open a browser and visit [http://query-auth.dev](http://query-auth.dev)

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

Visit [http://query-auth.dev/new-user](http://query-auth.dev/new-user) to see an example of a signed POST request:

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
