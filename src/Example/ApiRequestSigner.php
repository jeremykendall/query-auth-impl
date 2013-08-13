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
use Guzzle\Http\Message\RequestInterface;
use QueryAuth\Client as QueryAuthClient;

/**
 * Signs API requests
 *
 * This is an example of how one might choose to abstract the QueryAuth\Client
 * rather than use it directly.
 */
class ApiRequestSigner
{
    /**
     * @var QueryAuthClient Client instance
     */
    private $client;

    /**
     * Public constructor
     *
     * @param QueryAuthClient $client Client instance
     */
    public function __construct(QueryAuthClient $client)
    {
        $this->client = $client;
    }

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

    /**
     * Gets request parameters as array
     *
     * @param  RequestInterface $request HTTP Request
     * @return array            Request parameters as array
     */
    protected function getParams(RequestInterface $request)
    {
        if ($request->getMethod() == 'POST') {
            return $request->getPostFields()->toArray();
        }

        return $request->getQuery()->toArray();
    }

    /**
     * Replaces request parameters with signed request parameters
     *
     * @param RequestInterface $request      HTTP Request
     * @param array            $signedParams Signed request parameters
     */
    protected function replaceParams(RequestInterface $request, array $signedParams)
    {
        if ($request->getMethod() == 'POST') {
            $request->getPostFields()->replace($signedParams);
        } else {
            $request->getQuery()->replace($signedParams);
        }
    }
}
