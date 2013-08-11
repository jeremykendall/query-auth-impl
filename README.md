# Query Auth Implementation

Example implementation of the [Query
Auth](https://github.com/jeremykendall/query-auth) API query authentication
library.

## Requirements

* [Vagrant](http://www.vagrantup.com/)
* [VirtualBox](https://www.virtualbox.org/)

## Usage

* Clone repo
* Run `vagrant up`
* Add `192.168.56.102 query-auth.dev` to `/etc/hosts`

## Implementation Examples

All code samples below come from `/public/index.php`.  This sample implementation
uses the [Slim Framework](http://slimframework.com/) and the [Guzzle HTTP client](http://guzzlephp.org/).

### GET Example

Visit http://query-auth.dev/phrase to see an example of a signed GET request:

``` php
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
```

Validates the GET request and returns the famous mangled phrase:

``` php
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
```

### POST Example

Visit http://query-auth.dev/new-user to see an example of a signed POST request:

``` php
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
```

Validates the POST request and returns new user data:

``` php
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
```
