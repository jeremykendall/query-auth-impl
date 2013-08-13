<?php
/**
 * Query Auth Example Implementation
 *
 * @copyright 2013 Jeremy Kendall
 * @license https://github.com/jeremykendall/query-auth-impl/blob/master/LICENSE.md MIT
 * @link https://github.com/jeremykendall/query-auth-impl
 */

namespace Example;

use Example\ApiCredentials;
use Slim\Http\Request;
use QueryAuth\Server as QueryAuthServer;

/**
 * Validates API request signature
 *
 * This is an example of how one might choose to abstract the QueryAuth\Server
 * rather than use it directly.
 */
class ApiRequestValidator
{
    /**
     * @var QueryAuthServer Server instance
     */
    private $server;

    /**
     * Public constructor
     *
     * @param QueryAuthServer $server Server instance
     */
    public function __construct(QueryAuthServer $server)
    {
        $this->server = $server;
    }

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

    /**
     * Gets request parameters as array
     *
     * @param  Request $request HTTP Request
     * @return array   Request parameters as array
     */
    protected function getParams(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            return $request->post();
        }

        return $request->get();
    }
}
