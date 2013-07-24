<?php

class Base_Exception_Disabled extends Base_Exception_InternalRedirect
{
    public function __construct($url)
    {
        parent::__construct($url);
    }
}