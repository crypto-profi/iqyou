<?php

class Base_Exception_AccessDeny extends Base_Exception_InternalRedirect
{
    public function __construct($message = '') {
        $this->type = Base_Exception::TYPE_ACCESS_DENY;

        Base_Context::getInstance()->getResponse()->setHeader('HTTP/1.0 403 Access Forbidden');
        parent::__construct('/error/?code=' . $this->type);
    }
}