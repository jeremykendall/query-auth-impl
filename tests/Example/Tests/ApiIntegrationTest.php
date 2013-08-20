<?php

namespace Example\Tests;

use Example\ApiCredentials;
use Example\ApiRequestSigner;
use Guzzle\Http\Client as GuzzleClient;
use JSend\JSendResponse;
use QueryAuth\Factory as QueryAuthFactory;

/**
 * @group vm-required
 */
class ApiIntegrationTest extends \PHPUnit_Framework_TestCase
{
    private $credentials;
    private $factory;
    private $guzzle;
    private $requestSigner;

    protected function setUp()
    {
        $config = require realpath(dirname(__DIR__) . '/../../config.php');
        $this->credentials = new ApiCredentials($config['api']['key'], $config['api']['secret']);
        $this->factory = new QueryAuthFactory();
        $this->guzzle = new GuzzleClient(
            'http://query-auth.dev',
            array(
                'request.options' => array(
                    // Set timeouts short so test fails fast if run w/o vm started
                    'timeout' => 2,
                    'connect_timeout' => 1
                )
            )
        );
        $this->requestSigner = new ApiRequestSigner($this->factory->newClient());
    }

    protected function tearDown()
    {
        $this->credentials = null;
        $this->factory = null;
        $this->guzzle = null;
        $this->requestSigner = null;
    }

    public function testGetRequest()
    {
        $request = $this->guzzle->get('/api/get-example');
        $this->requestSigner->signRequest($request, $this->credentials);

        $response = $request->send();

        $jsend = JSendResponse::decode($response->getBody());

        $this->assertEquals('success', $jsend->getStatus(), "GET response status message is not 'success'");
        $this->assertArrayHasKey('message', $jsend->getData());

        $data = $jsend->getData();
        $this->assertStringStartsWith("Klaatu... barada... n...", $data['message']);
    }

    public function testPostRequest()
    {
        $params = array(
            'name' => 'Ash',
            'email' => 'ash@s-mart.com',
            'department' => 'Housewares',
        );

        $request = $this->guzzle->post('/api/post-example', array(), $params);
        $this->requestSigner->signRequest($request, $this->credentials);

        $response = $request->send();

        $jsend = JSendResponse::decode($response->getBody());

        $this->assertEquals('success', $jsend->getStatus(), "POST response status message is not 'success'");
        $this->assertArrayHasKey('user', $jsend->getData());

        $params['id'] = 666;

        $data = $jsend->getData();
        $this->assertEquals($params, $data['user']);
    }
}
