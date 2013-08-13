<?php
/**
 * Query Auth Example Implementation
 *
 * @copyright 2013 Jeremy Kendall
 * @license https://github.com/jeremykendall/query-auth-impl/blob/master/LICENSE.md MIT
 * @link https://github.com/jeremykendall/query-auth-impl
 */

namespace Example;

/**
 * Stores your API key and secret
 */
class ApiCredentials
{
    /**
     * @var string API key
     */
    private $key;

    /**
     * @var string API secret
     */
    private $secret;

    /**
     * Public constructor
     *
     * @param string $key    API key
     * @param string $secret API secret
     */
    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Gets API key
     *
     * @return string API key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets API key
     *
     * @param string $key API key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Gets API secret
     *
     * @return string API secret
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Sets API secret
     *
     * @param string $secret API secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }
}
