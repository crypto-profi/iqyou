<?php

class Base_Exception extends Exception
{
    const TYPE_BASE = 0;
    const TYPE_ACCESS_DENY = 1;
    const TYPE_ERROR401 = 401;
    const TYPE_ERROR404 = 404;
    const TYPE_DELETED_USER = 4;
    const TYPE_BANNED_USER = 5;
    const TYPE_UNAPPROVED_DOMAIN = 6;
    const TYPE_TECH_DOMAIN = 7;
    const TYPE_ACCESS_AGE = 8;
    const TYPE_ACCESS_DENY_STAFF = 9;

    public static $errorDetails = null;

    protected $type = self::TYPE_BASE;

    public function handle($writeLog = true)
    {
        $errorDetails = self::getErrorDetails();
        if ($this->getType() == self::TYPE_BASE) {

            echo "<p style='font-size:18px; font-family:Verdana; margin:30px 0px;'>" . $errorDetails[self::TYPE_BASE]['title'] . "</p>";
            if ($writeLog) {
                $this->logError($this->getMessage(), $this->getTraceAsString());
            }
            if (Base_Service_Common::isOurPerson()) {
                echo "<div><font color=\"red\"><b>Exception: " . $this->getMessage() . "</b></font></div>";
                echo "<pre>";
                echo $this->__toString();
                echo "</pre>";
            }
        } else {
            Base_Context::getInstance()->getResponse()->setHeader('Location: http://' . 'sdf' . '/error/?code=' . $this->getType());
        }
    }

    public static function getErrorDetails()
    {
        if (self::$errorDetails === null) {
            self::$errorDetails = array(
                self::TYPE_BASE => array("title" => "Ошибка на {string}. <a href='{string}' style='color:#369EFF'>Вернуться на главную страницу</a>."),
                self::TYPE_ACCESS_DENY => array("title" => "У Вас не хватает прав для просмотра данной страницы."),
                self::TYPE_ACCESS_AGE => array("title" => "Приложение доступно пользователям старше "),
                self::TYPE_ERROR401 => array("title" => "Вам необходимо <a href='{string}'>войти на сайт</a>, чтобы просматривать данную страницу."),
                self::TYPE_ERROR404 => array("title" => "Запрошенная страница не найдена."),
                self::TYPE_DELETED_USER => array("title" => "Этот пользователь удален."),
                self::TYPE_BANNED_USER => array("title" => "Этот пользователь скрыт."),
                self::TYPE_UNAPPROVED_DOMAIN => array("title" => "Вы пытаетесь зайти на неподтвержденный домен.<br/><br/>Для того, чтобы открыть доступ на этот домен с текущего IP адреса,<br/>необходимо зайти в его настройки на "),
                self::TYPE_TECH_DOMAIN => array("title" => "Вы пытаетесь зайти на технический домен, который не зарегистрирован в системе."),
                self::TYPE_ACCESS_DENY_STAFF => array("title" => "Раздел недоступен."),
            );
        }
        return self::$errorDetails;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public static function logError($message, $stackTrace)
    {
        Base_Context::getInstance()->getLogger()->error('Exception: ' . $message . ' trace: ' . $stackTrace);
        file_put_contents('var/errors_full.txt', date('Y-m-d H:i:s') . ' ' . $_SERVER['REMOTE_ADDR'] . ' ' . Base_Service_Common::getRealIp() . ' url:' . $_SERVER['REQUEST_URI'] . ' ' . $message . "\n\n" . $stackTrace . "\n\n\n\n", FILE_APPEND);
    }

}