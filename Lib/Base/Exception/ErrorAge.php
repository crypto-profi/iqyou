<?php

class Base_Exception_ErrorAge extends Base_Exception_InternalRedirect
{
    public function __construct($url = null)
    {
        Base_Context::getInstance()->getResponse()->setHeader('HTTP/1.0 403 Access Forbidden');
        $this->type = Base_Exception::TYPE_ACCESS_AGE;
        if (!$url) {
            $url = '/error/?code='.$this->getType();
        }
        parent::__construct($url);
    }
}