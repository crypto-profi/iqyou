<?php

class Base_Exception_Error401 extends Base_Exception 
{
    public function __construct($message = '')
    {
        parent::__construct($message);
        $this->type = Base_Exception::TYPE_ERROR401;
    }
    
    public function handle()
    {
        // ���� ���� ��� ���������, ������ �� ����� � ����� ������� ��� ��� ����-��, ������ �����
        if (Base_Context::getInstance()->getUser()) {
            $url = '/profile';
            $status = 302;
        } else {
            $url = '/user/login/?redirect=' . urlencode(Base_Context::getInstance()->getRequest()->getRequestUri());
            $status = 301;
        }

        Base_Context::getInstance()->getResponse()->setHeader('Location: '. $url, true, $status);
    }
    
}