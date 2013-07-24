<?php

class Driver_Mongo
{
    protected $_db = array();
    private $_connection = array();
    /** @var Driver_Mongo */
    private static $instance;
    private function __construct() {
        //$this->_db = $config = Base_Context::getInstance()->getConfig('db', 'mongo');
        try {
            //$this->_con = new Mongo($config['server'], $config['params']);
        } catch(MongoConnectionException $e) {
            //throw new Base_Exception($config['server'] . ' - ' . $e->getMessage());
        }
    }
    private function __clone() { }
    private function __wakeup() { }

    private function _getHost($hosts)
    {
        foreach($hosts as $host) {
            if (isset($host['on']) && $host['on'] === 1) {
                return $host;
            }
        }

        return false;
    }

    /**
     * @param string $dbName
     * @param string $method
     *
     * @return bool|array array(MongoDB|HostId)
     * @throws Base_Exception
     * @throws MongoConnectionException
     */
    public function getDb($dbName = null, $method = __METHOD__)
    {
        if(is_null($dbName)) {
            $dbName = Base_Application::getInstance()->config['mongo']['default'];
        }

        if (!isset($this->_db[$dbName])) {
            if (!isset(Base_Application::getInstance()->config['mongo']['databases'][$dbName])) {
                throw new Base_Exception('Mongo database "'.$dbName.'" underknown config');
            }

            $hosts = Base_Application::getInstance()->config['mongo']['databases'][$dbName];
            $host = $this->_getHost($hosts);

            try {
                if ($host !== false && isset($host['server'], $host['hostId'])) {
                    $server = $host['server'];
                    $params = isset($host['params']) ? $host['params'] : array();

                    // Создаем коннект или берем существующий
                    if (isset($this->_connection[$host['hostId']])) {
                        $connection = $this->_connection[$host['hostId']];
                    } else {
                        Service_StatsFuncToId::startMongo(Service_StatsFuncToId::MODE_CONNECT, $host['hostId'], $method, $collection = '');
                        $connection = new MongoClient($server, $params);
                        Service_StatsFuncToId::stopMongo((bool) $connection);
                        $this->_connection[$host['hostId']] = array('connect' => $connection, 'dbs' => array());
                    }

                    // Выбираем БД
                    $_dbName = isset($host['dbName']) ? $host['dbName'] : $dbName;
                    if (!isset($this->_connection[$server]['dbs'][$_dbName])) {
                        $this->_connection[$server]['dbs'][$_dbName] = $connection->$host['dbName'];
                    }

                    // возвращаем подключение к БД
                    return array('db' => $this->_connection[$server]['dbs'][$_dbName], 'hostId' => $host['hostId']);
                } else {
                    throw new MongoConnectionException('Mongo database "'.$dbName.'" underknown params');
                }
            } catch(MongoConnectionException $e) {
                throw new $e($host . ' - ' . $e->getMessage());
            }
        }

        return false;
    }

    /** @return Driver_Mongo */
    public static function getInstance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}