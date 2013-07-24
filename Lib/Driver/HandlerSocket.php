<?php
class Driver_HandlerSocket
{
    /** @var Driver_HandlerSocket */
    private static $_instance = null;

    private $_port = 9999;
    private $_lastError = '';
    private $_configs = array();

    /**
     * Соединения к партам и хостам
     * @var HandlerSocket[]
     */
    private $_connections = array();

    private $_indexCache = array();

    private function __construct() {}
    private function __clone() {}

    /**
     * @return Driver_HandlerSocket
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        if (!extension_loaded('handlersocket')) {
            return false;
        }

        return true;
    }

    /**
     * @param string $tableName
     *
     * @return array
     * @throws Base_Exception
     */
    private function _getTableConfig($tableName)
    {
        if (!isset($this->_configs[$tableName])) {

            $dbName = Driver_Sql::_getDbnameByTable($tableName);
            $dbHost = Driver_DBHosts::selectMaster($dbName);

            $application = Base_Application::getInstance();
            if (empty($application->config['db']['connect'][$dbName][$dbHost])) {
                throw new Base_Exception("Can`t find connection info for server ".$dbName.".".$dbHost." see config");
            }

            $config = parse_url(Base_Application::getInstance()->config['db']['connect'][$dbName][$dbHost]);
            $this->_configs[$tableName] = array(
                'dbName' => $dbName,
                'dbHost' => $dbHost,
                'dbBase' => str_replace('/', '', $config['path']),
                'config' => $config,
            );
        }

        return $this->_configs[$tableName];
    }

    /**
     * @param string $tableName
     * @return HandlerSocket|false
     */
    private function _connect($tableName)
    {
        $config = $this->_getTableConfig($tableName);

        if (empty($this->_connections[$config['dbName']][$config['dbHost']])) {
            try {
                $this->_connections[$config['dbName']][$config['dbHost']] = new HandlerSocket($config['config']['host'], $this->_port);
            } catch (HandletSocketException $e) {
                $this->_connections[$config['dbName']][$config['dbHost']] = false;
                $this->_lastError = $e->getMessage();
            }
        }

        return $this->_connections[$config['dbName']][$config['dbHost']];
    }

    /**
     * @param int    $indexId
     * @param string $tableName
     * @param string $indexName
     * @param string $field
     * @param string $filter
     *
     * @return Handlersocket|bool
     */
    public function openIndex($indexId, $tableName, $indexName, $field, $filter = '')
    {
        if (!self::isEnabled()) {
            $this->_lastError = 'HandlerSocket extension missed!';
            return false;
        }

        $connection = $this->_connect($tableName);
        if (!$connection) {
            return false;
        }

        $config = $this->_getTableConfig($tableName);
        $this->_lastError = '';
        if (!$connection->openIndex($indexId, $config['dbBase'], $tableName, $indexName, $field, $filter)) {
            $this->_lastError = 'Fault openIndex: ' . $connection->getError();
            return false;
        }

        // cache openned connection & table for index
        $this->_indexCache[$indexId] = array(
            'connection' => $connection,
            'tableName' => $tableName
        );

        return $connection;
    }

    /**
     * @param string $method
     * @param int    $indexId
     * @param string $operate    supported '=', '<', '<=', '>', '>='
     * @param array  $criteria   comparison values
     * @param int    $limit
     * @param int    $offset
     * @param array  $filters    filter values
     * @param int    $inKey      index number of in field
     * @param array  $inValues
     *
     * @return mixed
     */
    public function execute($method, $indexId, $operate, $criteria, $limit = 1, $offset = 0, $filters = null, $inKey = -1, $inValues = null)
    {
        if (!self::isEnabled()) {
            $this->_lastError = 'HandlerSocket extension missed!';
            return false;
        }

        if (!isset($this->_indexCache[$indexId])) {
            $this->_lastError = 'Connection info for index "'.$indexId.'" missed!';
            return false;
        }

        $connection = $this->_indexCache[$indexId]['connection'];
        $tableName = $this->_indexCache[$indexId]['tableName'];

        $start = microtime(1);
        $res = $connection->executeSingle($indexId, $operate, $criteria, $limit, $offset, null, null, $filters, $inKey, $inValues);

        if (Base_Service_Profiler_Log::$enabled) {
            $query = array(
                '"'.$operate.'"',
                'array('.implode(',', $criteria).')',
                $limit,
                $offset,
                '[filters]',
                $inKey,
                is_array($inValues) ? 'array('.implode(', ', $inValues).')' : 'null'
            );
            $uqery = "`$tableName` > executeSingle: (".implode(', ', $query).")";
            //Base_Service_Profiler_Log::profilerHs($start, $method, $uqery, count($res));
        }


        $this->_lastError = $connection->getError() ? 'Execute error: ' . $connection->getError() : '';
        return $res;
    }


    /**
     * Получить последнюю ошибку.
     * @return string
     */
    public function getLastError()
    {
        return $this->_lastError;
    }
}