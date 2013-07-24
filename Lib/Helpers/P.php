<?php

class Zend_View_Helper_P
{
    public function p($key, $default = '')
    {
        $request = Base_Context::getInstance()->getRequest();
        if ($key == '*') return @$request->getParams();
        return @$request->getParam($key, $default);
    }
}

