<?php

class Base_Exception_Error404 extends Base_Exception_InternalRedirect
{
    public function __construct($url = '/error/index/?code=404')
    {
        $user = Base_Context::getInstance()->getUser();

        $this->type = Base_Exception::TYPE_ERROR404;

        $userId = !empty($user) ? $user->getId() : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        $errorText = date('Y-m-d H:i:s') . ' ' . $_SERVER['REMOTE_ADDR'] . ' ' . Base_Service_Common::getRealIp() . ' url:' . $_SERVER['REQUEST_URI'] . '; referer:' . $referer.'; userId:'.$userId;
        
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $errorText .= '; requestMethod: ' . $_SERVER['REQUEST_METHOD'];
        }
        
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $errorText .= '; userAgent: ' . $_SERVER['HTTP_USER_AGENT'];
        }
        
        if (isset($_SERVER['REQUEST_METHOD']) &&
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            substr($_SERVER['REQUEST_URI'], -5) === '/edit') {

            file_put_contents('var/errors_404_bots.txt', $errorText . "\n\n", FILE_APPEND);
        } else {
            file_put_contents('var/errors_404.txt', $errorText . "\n\n", FILE_APPEND);
        }

        Base_Context::getInstance()->getResponse()->setHeader('HTTP/1.0 404 Not Found');
        parent::__construct($url);
    }
}
