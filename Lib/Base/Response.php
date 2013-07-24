<?php

class Base_Response
{
    /**
     * @var array
     */
    protected $headers = array();

    /**
     * Set HTTP header to response
     *
     * @param $value
     * @param bool $replace
     * @param null $httpResponseCode
     */
    public function setHeader($value, $replace = null, $httpResponseCode = null)
    {
        $this->headers[] = $value;

        if (!defined('TESTING')) {
            header($value, $replace, $httpResponseCode);
        }
    }

    /**
     * Return all HTTP headers;
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Wipe HTTP headers
     */
    public function cleanHeaders()
    {
        $this->headers = array();
    }
}