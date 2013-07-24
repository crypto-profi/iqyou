<?php

class Base_Logger
{
    /**
     * @var Zend_Log_Writer_Abstract
     */
    protected $formatter;
    protected $logPath = 'var/errors.txt';

    protected static $loggers = array();
    protected static $reportLevel = self::LOG_FINE;

    const LOG_CRITICAL    = 1;
    const LOG_ERROR       = 2;
    const LOG_WARNING     = 3;
    const LOG_NOTICE      = 4;
    const LOG_INFO        = 5;
    const LOG_DEBUG       = 6;
    const LOG_FINE        = 7;

    protected static $logged = array(
        self::LOG_CRITICAL,
        self::LOG_ERROR,
        self::LOG_WARNING,
        self::LOG_NOTICE,
        self::LOG_INFO,
        self::LOG_DEBUG,
        self::LOG_FINE
    );

    public function __construct($logPath)
    {
        $this->formatter = new Zend_Log_Formatter_Simple("%timestamp%\t%url%\t%domain%\t%userId%\t%message%\t%parameters%\n");
        $this->logPath = $logPath;
    }

    public function isLoggedType($type)
    {
        return $type <= self::getReportLevel();
    }

    public function setLogged($types)
    {
        $this->logged;
    }

    public function log($message, $type = self::LOG_INFO, $parameters = array())
    {

        if ($this->isLoggedType($type)) {
            $parametersString = '';
            foreach ($parameters as $key=>$value) {
                $parametersString .= $key . '=' . $value . '|';
            }

            $currentUser = Base_Context::getInstance()->getUser();
            if (isset($currentUser['user_id'])) {
                $userId = 'uid#' . $currentUser['user_id'];
            } else {
                $userId = 'guest#' . Base_Service_Common::getRealIp();
            }
            
            $event = array('timestamp' => date('Y-m-d H:i:s'), 'userId' => $userId,
                           'url' => $_SERVER['REQUEST_URI'], 'message' => $message, 'parameters' => $parametersString);
            $line = $this->formatter->format($event);

            if (!file_put_contents($this->logPath, $line, FILE_APPEND)) {
                throw new Zend_Log_Exception("Unable to write to stream");
            }
        }
    }

    public function info($message, $parameters = array())
    {
        $this->log($message, self::LOG_INFO, $parameters);
    }

    public function error($message, $parameters = array())
    {
        $this->log($message, self::LOG_ERROR, $parameters);
    }

    public function debug($message, $parameters = array())
    {
        $this->log($message, self::LOG_DEBUG, $parameters);
    }

    public function warn($message, $parameters = array())
    {
        $this->log($message, self::LOG_WARNING, $parameters);
    }

    public function fine($message, $parameters = array())
    {
        $this->log($message, self::LOG_FINE, $parameters);
    }

    /**
     * @param Exception $exception
     */
    public function exception($exception, $parameters = array())
    {
        if ($exception instanceof Base_Exception) {
            if ($exception instanceof Base_Exception && is_array($exception->getParams())) {
                $parameters = array_merge($parameters, $exception->getParams());
            }
        }
        $this->log($exception->getMessage(), self::LOG_ERROR, $parameters);
    }

    public static function setReportLevel($reportLevel)
    {
        self::$reportLevel = $reportLevel;
    }

    public function getReportLevel()
    {
        return self::$reportLevel;
    }

}