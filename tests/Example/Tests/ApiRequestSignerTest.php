<?php

namespace Example\Tests;

use Example\ApiCredentials;
use Example\ApiRequestSigner;
use Guzzle\Http\Client as GuzzleClient;
use QueryAuth\Client as QueryAuthClient;

class ApiRequestSignerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApiRequestSigner
     */
    private $signer;

    /**
     * @var QueryAuthClient
     */
    private $queryAuthClient;

    /**
     * @var array
     */
    private $signedParams;

    /**
     * @var string
     */
    private $host = 'query-auth.dev';

    /**
     * @var string
     */
    private $path = '/api/query';

    /**
     * @var ApiCredentials
     */
    private $credentials;

    protected function setUp()
    {
        $this->queryAuthClient = $this->getMockBuilder('QueryAuth\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $this->signer = new ApiRequestSigner($this->queryAuthClient);

        $this->signedParams = array(
            'key' => 'key',
            'timestamp' => 1234567,
            'signature' => 'Bruce Campbell',
        );

        $this->credentials = new ApiCredentials('key', 'secret');
    }

    public function testSignGetRequestNoParams()
    {
        $guzzle = new GuzzleClient('http://' . $this->host);
        $request = $guzzle->get($this->path);

        $this->queryAuthClient->expects($this->once())
            ->method('getSignedRequestParams')
            ->with(
                $this->credentials->getKey(), 
                $this->credentials->getSecret(), 
                'GET', 
                $this->host,
                $this->path,
                array()
            )
            ->will($this->returnValue($this->signedParams));

        $this->signer->signRequest($request, $this->credentials);

        $this->assertEquals($this->signedParams, $request->getQuery()->toArray()); 
    }

    public function testSignPostRequestNewUser()
    {
        $params = array(
            'name' => 'Ash',
            'email' => 'ash@s-mart.com',
            'department' => 'Housewares',
        );
        
        $guzzle = new GuzzleClient('http://' . $this->host);
        $request = $guzzle->post($this->path, array(), $params);

        $this->queryAuthClient->expects($this->once())
            ->method('getSignedRequestParams')
            ->with(
                $this->credentials->getKey(), 
                $this->credentials->getSecret(), 
                'POST', 
                $this->host,
                $this->path,
                $params
            )
            ->will($this->returnValue(array_merge($params, $this->signedParams)));

        $this->signer->signRequest($request, $this->credentials);

        $this->assertEquals(
            array_merge($params, $this->signedParams), 
            $request->getPostFields()->toArray()
        ); 
    }
}
