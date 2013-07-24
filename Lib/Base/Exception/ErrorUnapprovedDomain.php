<?php

class Base_Exception_ErrorUnapprovedDomain extends Base_Exception_InternalRedirect
{
    public function __construct($url = null)
    {
        Base_Context::getInstance()->getResponse()->setHeader('HTTP/1.0 403 Access Forbidden');
        $this->type = Base_Exception::TYPE_UNAPPROVED_DOMAIN;
        if (!$url) {
            $url = '/error/?code='.$this->getType();
        }
        parent::__construct($url);
    }
}