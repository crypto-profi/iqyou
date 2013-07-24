<?php

class Base_Request extends Zend_Controller_Request_Http
{
    const
        PARAM_INT = 1,
        PARAM_TEXT = 2,     // Применяется перекодировка + htmlspecialchars()
        PARAM_LEGACY = 3,   // Как было до включения htmlspecialchars (только пееркодировка + strip_tags)
        PARAM_HTML = 4,     // Применяется только перекодировка utf-8 -> cp1251
        PARAM_JSON = 5,     // Применяется только перекодировка utf-8 -> cp1251
        PARAM_URL = 6,      // Применяется только перекодировка utf-8 -> cp1251
        PARAM_DATA_RECODED = 7,  // Применяется только перекодировка utf-8 -> cp1251
        PARAM_BINARY = 10,  // Возвращает данные в исходном виде, без любых перекодировок
        PARAM_FLOAT = 11;

    private $_domain;
    private $_moduleLocation = '';

    private $_subModule = '';
    private $_isInternal = false;

    public function setDomain($domain)
    {
        $this->_domain = $domain;
    }

    public function getDomain()
    {
        return $this->_domain ? $this->_domain : PROJECT_DOMAIN;
    }

    public function setModuleLocation($location)
    {
        $this->_moduleLocation = $location;
    }

    public function getModuleLocation()
    {
        return $this->_moduleLocation;
    }

    public function getTextParam($name, $default = '')
    {
        if ($name == '*') {
            trigger_error("You cannot get all text params at once, use binary mode or whatever you prefer",
                E_USER_WARNING);
            return false;
        }

        if (isset($_REQUEST[$name])) {

            $val = $_REQUEST[$name];

            if (!empty($_REQUEST['ajax'])) {
                $val = Utf::fromUtf($val);
            }
        } elseif (null !== ($val = $this->get($name))) {

        } else {
            $val = $default;
        }

        return $val;
    }

    public function getBinaryParam($name, $default = '')
    {
        if ($name == '*') {
            // параметры запроса + зендовские
            $allParams = $_POST + $_GET + $this->getParams();
            return $allParams;
        }

        return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
    }

    public function getParamWithType($name, $default, $type = self::PARAM_TEXT)
    {
        if ($name == '*') {
            return ($type == self::PARAM_BINARY) ? $this->getBinaryParam('*') : $this->getParams();
        }
        switch ($type) {
            case self::PARAM_TEXT:
                return $this->getParam($name, $default);
            case self::PARAM_INT:
                return (int)$this->getParam($name, $default);
            case self::PARAM_FLOAT:
                return (float)$this->getParam($name, $default);
            case self::PARAM_LEGACY:
                $val = $this->getTextParam($name, $default);
                return strip_tags($val);
            case self::PARAM_BINARY:
                return $this->getBinaryParam($name, $default);
            default :
                return $this->getTextParam($name, $default);
        }
    }

    public function setSubModuleName($name)
    {
        $this->_subModule = $name;
    }

    public function getSubModuleName()
    {
        return $this->_subModule;

    }

    public function setInternal($isInternal = true)
    {
        $this->_isInternal = $isInternal;
    }

    public function isInternal()
    {
        return $this->_isInternal;
    }

    public function isAjaxRequest()
    {
        return $this->isXmlHttpRequest();
    }

    public function getActionName($short = false)
    {
        $ret = parent::getActionName();
        return ($short===true) ? substr($ret, 0, -6) : $ret;
    }
}