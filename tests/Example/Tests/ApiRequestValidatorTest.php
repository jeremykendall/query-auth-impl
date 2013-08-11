<?php

namespace Example\Tests;

use Example\ApiCredentials;
use Example\ApiRequestValidator;
use Guzzle\Http\Client as GuzzleClient;
use Slim\Http\Request;
use QueryAuth\Server as QueryAuthServer;

class ApiRequestValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApiRequestValidator
     */
    private $validator;

    /**
     * @var QueryAuthServer
     */
    private $queryAuthServer;

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

    /**
     * @var array
     */
    private $userData;

    /**
     * @var Slim\Http\Request
     */
    private $request;

    protected function setUp()
    {
        $this->request = $this->getMockBuilder('Slim\Http\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryAuthServer = $this->getMockBuilder('QueryAuth\Server')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new ApiRequestValidator($this->queryAuthServer);

        $this->userData = array(
            'name' => 'Ash',
            'email' => 'ash@s-mart.com',
            'department' => 'Housewares',
        );

        $this->signedParams = array(
            'key' => 'key',
            'timestamp' => 1234567,
            'signature' => 'Bruce Campbell',
        );

        $this->credentials = new ApiCredentials('key', 'secret');
    }

    public function testIsValidPostRequest()
    {
        $this->request->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue('POST'));

        $this->request->expects($this->once())
            ->method('getHost')
            ->will($this->returnValue('query-auth.dev'));

        $this->request->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('/api/query'));
        
        $this->request->expects($this->once())
            ->method('post')
            ->will($this->returnValue(array_merge($this->userData, $this->signedParams)));

        $this->queryAuthServer->expects($this->once())
            ->method('validateSignature')
            ->with(
                $this->credentials->getSecret(),
                'POST',
                $this->host,
                $this->path,
                array_merge($this->userData, $this->signedParams)
            )
            ->will($this->returnValue(true));

        $this->assertTrue($this->validator->isValid($this->request, $this->credentials));
    }

    public function testIsValidGetRequest()
    {
        $this->request->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue('GET'));

        $this->request->expects($this->once())
            ->method('getHost')
            ->will($this->returnValue('query-auth.dev'));

        $this->request->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('/api/query'));
        
        $this->request->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->signedParams));

        $this->queryAuthServer->expects($this->once())
            ->method('validateSignature')
            ->with(
                $this->credentials->getSecret(),
                'GET',
                $this->host,
                $this->path,
                $this->signedParams
            )
            ->will($this->returnValue(true));

        $this->assertTrue($this->validator->isValid($this->request, $this->credentials));
    }
}
