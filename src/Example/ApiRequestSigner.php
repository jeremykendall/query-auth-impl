<?php

namespace Example;

use Example\ApiCredentials;
use Guzzle\Http\Message\RequestInterface;
use QueryAuth\Client as QueryAuthClient;

class ApiRequestSigner
{
    /**
     * @var QueryAuthClient
     */
    private $client;

    public function __construct(QueryAuthClient $client)
    {
        $this->client = $client;
    }

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

    protected function getParams(RequestInterface $request)
    {
        if ($request->getMethod() == 'POST') {
            return $request->getPostFields()->toArray();
        }
        
        return $request->getQuery()->toArray();
    }

    protected function replaceParams(RequestInterface $request, array $signedParams)
    {
        if ($request->getMethod() == 'POST') {
            $request->getPostFields()->replace($signedParams);
        } else {
            $request->getQuery()->replace($signedParams);
        }
    }
}
