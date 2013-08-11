<?php

namespace Example;

class ApiCredentials
{
    /**
     * @var string API Key
     */
    private $key;

    /**
     * @var string API Secret
     */
    private $secret;

    /**
     * Public constructor
     *
     * @param string $key    API Key
     * @param string $secret API Secret
     */
    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Get key
     *
     * @return key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set key
     *
     * @param $key the value to set
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get secret
     *
     * @return secret
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set secret
     *
     * @param $secret the value to set
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }
}
