<?php

namespace Example;

use Example\ApiCredentials;
use Slim\Http\Request;
use QueryAuth\Server as QueryAuthServer;

class ApiRequestValidator
{
    /**
     * @var QueryAuthServer
     */
    private $server;

    public function __construct(QueryAuthServer $server)
    {
        $this->server = $server;
    }

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

    protected function getParams(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            return $request->post();
        }
        
        return $request->get();
    }
}
