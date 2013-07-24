<?php

class Base_Exception_Redirect extends Base_Exception 
{
    protected $url;
    protected $status = false;

    public function __construct($url, $status = false)
    {
        $this->setUrl($url);
        $this->status = $status;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }
    
    public function handle($writeLog = true)
    {
        if ($this->status) {
            Base_Context::getInstance()->getResponse()->setHeader('Location: ' . $this->getUrl(), true, $this->status);
        } else {
            if (class_exists('Base_Context') && // всё может быть
               (Base_Context::getInstance()->getUser() instanceof Base_Model_User)
            ) {
                Base_Context::getInstance()->getResponse()->setHeader('Location: ' . $this->getUrl()); // для залогиненых всегда 302 редирект
            } else {
                Base_Context::getInstance()->getResponse()->setHeader('Location: ' . $this->getUrl(), true, 301); // 302 -> 301
            }
        }
    }
        
}