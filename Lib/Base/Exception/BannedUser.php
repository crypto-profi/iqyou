<?php

class Base_Exception_BannedUser extends Base_Exception 
{
    public function __construct($message = '') {
        parent::__construct($message);
        $this->type = Base_Exception::TYPE_BANNED_USER;
    }
}