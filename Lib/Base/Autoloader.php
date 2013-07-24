<?php

class Base_Autoloader
{
    protected static $_instance;

    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected function __construct()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    public static function autoload($class)
    {
        $self = self::getInstance();
        if ($self->_autoload($class)) {
            return true;
        }
        return false;
    }

    protected function _autoload($class)
    {
        try {
            self::loadClass($class);
            return $class;
        } catch (Exception $e) {
            trigger_error($e->getMessage());
            return false;
        }
    }

    public static function loadClass($class)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return;
        }

        $file = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

        include $file;

        if (!class_exists($class, false) && !interface_exists($class, false)) {
            throw new Exception("File \"$file\" does not exist or class \"$class\" was not found in the file");
        }
    }
}