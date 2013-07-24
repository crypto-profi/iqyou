<?php

class Base_Exception_DeletedUser extends Base_Exception 
{
    public function __construct($message = '') {
        parent::__construct($message);
        $this->type = Base_Exception::TYPE_DELETED_USER;
    }
}