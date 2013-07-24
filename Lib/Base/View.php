<?php

// Это замена для Zend_View (теоретически немного более быстрая, но может быть не полностью совместимая)
class Base_View
{
    private $_helperPath = 'application/views/helpers/';
    protected $_path = null;
    private $_file = null;
    private $_request = null;
    private $_encoding = 'CP1251';
    private $_strictVars = false;

    private $_helper;
    private $_helperClass;

    public function __construct($config = array())
    {
        $this->setScriptPath(null);

        if (array_key_exists('scriptPath', $config)) {
            $this->addScriptPath($config['scriptPath']);
            unset($config['scriptPath']);
        }

        if (array_key_exists('helperPath', $config)) {
            $this->addHelperPath($config['helperPath']);
            unset($config['helperPath']);
        }

        if (array_key_exists('encoding', $config)) {
            $this->setEncoding($config['encoding']);
            unset($config['encoding']);
        }

        if (!empty($config)) {
            trigger_error('Base_View __construct config is not empty!');
        }
    }

    public function render($name)
    {
        $this->_file = $this->_script($name);
        unset($name);

        if (class_exists('Base_Service_Profiler_Log')) {
            Base_Service_Profiler_Log::logGeneral('Start render template  '.$this->_file);
        }

        ob_start();

        include $this->_file;

        return ob_get_clean();
    }

    public function p($key, $default = '')
    {
        $this->_request = Base_Context::getInstance()->getRequest();
        if ($key == '*') {
            return $this->_request->getParams();
        }
        return $this->_request->getParam($key, $default);
    }

    public function __call($name, $args)
    {
        $this->_helper = ucfirst($name);
        $this->_helperClass = 'Zend_View_Helper_'. $this->_helper;
        include_once $this->_helperPath . $this->_helper . '.php';
        $this->_helper = new $this->_helperClass;
        return call_user_func_array(array($this->_helper, $name), $args);
    }

    public function setScriptPath($path)
    {
        $this->_path = array();
        $this->_addPath($path);
        return $this;
    }

    public function addScriptPath($path)
    {
        $this->_addPath($path);
        return $this;
    }

    public function setBasePath($path)
    {
        $path        = rtrim($path, '/');
        $path        = rtrim($path, '\\');
        $path       .= DIRECTORY_SEPARATOR;
        $this->setScriptPath($path . 'scripts');
        return $this;
    }

    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        return $this;
    }

    public function strictVars($flag = true)
    {
        $this->_strictVars = ($flag) ? true : false;
        return $this;
    }

    public function addHelperPath($path = null)
    {
        $this->_helperPath = $path === null ? $this->_helperPath : $path;
    }

    public function __get($key)
    {
        if ($this->_strictVars) {
            trigger_error('Key "' . $key . '" does not exist', E_USER_NOTICE);
        }

        return null;
    }

    public function __isset($key)
    {
        if ('_' != substr($key, 0, 1)) {
            return isset($this->$key);
        }

        return false;
    }

    public function __set($key, $val)
    {
        if ('_' != substr($key, 0, 1)) {
            $this->$key = $val;
            return;
        }

        throw new Base_Exception('Setting private or protected class members is not allowed');
    }

    public function __unset($key)
    {
        if ('_' != substr($key, 0, 1) && isset($this->$key)) {
            unset($this->$key);
        }
    }

    protected function _addPath($path)
    {
        if (is_array($path)) {
            trigger_error('Base_View _addPath path is array');
        }
        foreach ((array) $path as $dir) {
            $dir = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $dir);
            $dir = rtrim($dir, DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            array_unshift($this->_path, $dir);
            if (count($this->_path) >= 2) {
                trigger_error('Base_View _path is array with more than 1 element');
            }
        }
    }

    private function _script($name)
    {
        if (0 == count($this->_path)) {
            throw new Base_Exception('no view script directory set; unable to determine location for view script');
        }
        foreach ($this->_path as $dir) {

            // пето-костыль для переезда на 3х колоночную верстку, простите меня люди добрые :( @darazum
            if (Base_Context::getInstance() && Base_Context::getInstance()->getRequest() && Base_Context::getInstance()->getRequest()->getModuleName() == 'Pet' && Pet_Service_Base::isFS2Pet() && (strstr($dir, 'App/Pet/Templates/') !== false)) {

                $newDir = str_replace('App/Pet/Templates/', 'App/Pet/FS2Templates/', $dir);
                if (is_readable($newDir . $name)) {
                    if (!PRODUCTION || mt_rand(0, 100) == 1) {
                        Pet_Dao_Base::saveFs2TplLog($newDir . $name);
                    }
                    return $newDir . $name;
                }

                if (is_readable($dir . $name)) {
                    return $dir . $name;
                }

            } else {
                if (is_readable($dir . $name)) {
                    return $dir . $name;
                }
            }
        }

        $message = "script '$name' not found in path (". implode(PATH_SEPARATOR, $this->_path) .")";
        throw new Base_Exception($message);
    }

    public function assign($spec, $value = null)
    {
        if (is_string($spec)) {
            if ('_' == substr($spec, 0, 1)) {
                trigger_error('Base_View: Setting private or protected class members is not allowed!');
                return $this;
            }
            $this->$spec = $value;
        } elseif (is_array($spec)) {
            $error = false;
            foreach ($spec as $key => $val) {
                if ('_' == substr($key, 0, 1)) {
                    $error = true;
                    break;
                }
                $this->$key = $val;
            }
            if ($error) {
                trigger_error('Base_View: Setting private or protected class members is not allowed!');
                return $this;
            }
        } else {
            trigger_error('Base_View: assign() expects a string or array, received ' . gettype($spec));
            return $this;
        }

        return $this;
    }
}