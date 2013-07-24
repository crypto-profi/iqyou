<?php

class Base_Service_Profiler_Log
{
    const LOG_TYPE_GENERAL = 0;
    const LOG_TYPE_DB      = 1;
    const LOG_TYPE_MC      = 2;
    const LOG_TYPE_DALD    = 3;
    const LOG_TYPE_SQ      = 4;
    const LOG_TYPE_LEMON   = 5;
    const LOG_TYPE_EMAIL   = 6;
    const LOG_TYPE_MONGO   = 7;
    const LOG_TYPE_ER      = 8;
    const LOG_TYPE_STAT    = 9;
    const LOG_TYPE_PACMAN  = 10;

    const DATA_ROWS    = 'rows';
    const DATA_SIZE    = 'size';
    const DATA_METAMSG = 'meta';
    
    public static $enabled = false;

    private static $_enabled = false;
    
    private static $_xDebugProperties = array(
    	'xdebug.var_display_max_children' => -1,
    	'xdebug.var_display_max_data' => -1,
    	'xdebug.var_display_max_depth' => -1,
    );
    
    public static function reinitXdebug() {
    	if (extension_loaded('xdebug')) {
        	foreach (self::$_xDebugProperties as $propName => $propVal) {
        		$savedConfigs[$propName] = ini_get($propName);
        		ini_set($propName, $propVal);
        	}
        }
    }

    private static $_logLimit = 100000;

    private static $_logCount = 0;

    private static $_log = array();

    private static $_dumps = array();
    
    private static $_server = array();

    private static $_types = array(
        self::LOG_TYPE_GENERAL => array(
            'title' => 'ALL', // Заголовок, с которым будет отображаться в профайлере
            'lTime' => 0,  // Допустимое значение времени выполнения, при привышении которого запись будет красной
            'lSize' => 0,  // Допустимое значение размера результата в байтах, при привышении которого запись будет красной
            'lRows' => 0,  // Допустимое значение затронутых событием строк, при привышении которого запись будет красной
        ),
        self::LOG_TYPE_DB => array(
            'title' => 'DB',
            'desc'  => 'Data Base',
            'lTime' => 0.01,
            'lSize' => 0,
            'lRows' => 200,
        ),
        self::LOG_TYPE_MC => array(
            'title' => 'MC',
            'desc'  => 'Memcache',
            'lTime' => 0.01,
            'lSize' => 4096,
            'lRows' => 0,
        ),
        self::LOG_TYPE_DALD => array(
            'title' => 'DALD',
            'desc'  => 'Dald',
            'lTime' => 0.01,
            'lSize' => 0,
            'lRows' => 0,
        ),
        self::LOG_TYPE_SQ => array(
            'title' => 'SQ',
            'lTime' => 0,
            'lSize' => 0,
            'lRows' => 0,
        ),
        self::LOG_TYPE_STAT => array(
            'title' => 'STATS',
            'lTime' => 0,
            'lSize' => 0,
            'lRows' => 0,
        ),
        self::LOG_TYPE_PACMAN => array(
            'title' => 'Pacman',
            'lTime' => 0,
            'lSize' => 0,
            'lRows' => 0,
        ),
        self::LOG_TYPE_LEMON => array(
            'title' => 'LEM',
            'desc'  => 'Lemon',
            'lTime' => 0.01,
            'lSize' => 4096,
            'lRows' => 0,
        ),
        self::LOG_TYPE_EMAIL => array(
            'title' => 'EMAIL',
            'desc'  => 'Emails',
            'lTime' => 0,
            'lSize' => 0,
            'lRows' => 0,
        ),
        self::LOG_TYPE_MONGO => array(
            'title' => 'MONGO',
            'desc'  => 'Mongo DB',
            'lTime' => 0,
            'lSize' => 0,
            'lRows' => 0,
        ),
        self::LOG_TYPE_ER => array(
            'title' => 'ER',
            'desc'  => 'External Request',
            'lTime' => 0.1,
            'lSize' => 16384,
            'lRows' => 0,
        ),
    );

    private static $_xhprof = null;

    /**
     * @return bool
     */
    public static function getEnabled()
    {
        return self::$_enabled;
    }

    /**
     * @param $enabled
     */
    public static function setEnable($enabled)
    {
        if (defined('TESTING')) {
            return;
        }
        $enabled = (bool) $enabled;
        self::$_enabled = $enabled; 
        self::$enabled = self::$_enabled;
    }

    /**
     * Активен новый профайлер или нет
     *
     * @return bool
     */
    public static function isFs2ProfEnabled ()
    {
        return self::$_enabled;
    }

    /**
     * @param int    $type       Тип записи
     * @param int    $ts         Время начала выполнения журналируемого участка
     * @param string $msg        Сообщение журнала
     * @param string $caller     Метод, в котором произошол вызов
     * @param int    $resultSize Размер полученного результата в байтах
     * @param int    $resultRows Кол-во строк в полученном результате
     * @param string $metaMsg    Дополнительная информация
     * @param array  $data
     *
     * @internal param int $rows
     * @return bool
     */
    public static function log($type, $ts, $msg, $caller = null, $resultSize = 0, $resultRows = 0, $metaMsg = '', $data = array())
    {
    	self::reinitXdebug();
        if (!self::getEnabled() || self::$_logCount >= self::$_logLimit) {
            return false;
        }

        self::$_log[] = array(
            'type'   => $type,
            'ts'     => microtime(true) - $ts,
            'memory' => round(memory_get_peak_usage() / 1024 / 1024, 2), // in Mb
            'msg'    => $msg,
            'caller' => $caller,
            'size'   => $resultSize,
            'rows'   => $resultRows,
            'meta'   => $metaMsg,
            'data'   => $data
        );

        self::$_logCount++;
        return true;
    }

    /**
     * @param $message
     */
    public static function logGeneral($message)
    {
        self::log(self::LOG_TYPE_GENERAL, microtime(1), $message);
    }

    /**
     * @param $query
     *
     * @return string
     */
    public static function getQueryHash($query)
    {
        $config = Base_Application::getInstance()->config['db']['connect'];

        $hashValue = '';
        foreach ($config as $db => $record) {
            foreach ($record as $host => $data) {
                $realValues = parse_url($data);
                $hashValue = @md5($realValues['user'] . '_' . $realValues['pass']);
                break 2;
            }
        }

        return md5('_afioawfhipawfhipgs_' . $query . '_AFWEOUHOAUWEGHuofef_' . $hashValue);
    }

    /**
     * @param     $ts
     * @param     $dbName
     * @param     $dbHost
     * @param     $method
     * @param     $query
     * @param int $count
     */
    public static function profilerDb($ts, $dbName, $dbHost, $method, $query, $count = 0)
    {
        $meta = $dbName . ':' . $dbHost;
        $data = array('hash' => self::getQueryHash($query));
        self::log(self::LOG_TYPE_DB, $ts, (string)$query, $method, 0, $count, $meta, $data);
    }

    /**
     * @param        $ts
     * @param        $method
     * @param        $query
     * @param string $keys
     * @param string $value
     */
    public static function profilerMc($ts, $method, $query, $keys = '', $value = '')
    {
        $resultSize = is_array($value) || is_object($value) ? Utf::strlen(serialize($value)) : Utf::strlen($value);

        if ($query == 'connect') { // обнуляем ключи
            $keys = array();
        } elseif (is_array($keys)) { // чтобы на клиент уходил массив а не обьект - перестрахуемся и обновим ключи
            $keys = array_values($keys);
        } else {  // если ключ один - все равно массивом отдаем
            $keys = (array) $keys;
        }

        self::log(self::LOG_TYPE_MC, $ts, $query, $method, $resultSize, count($keys), '', array('keys' => $keys));
    }

    /**
     * @param        $ts
     * @param        $method
     * @param        $query
     * @param string $keys
     * @param string $value
     * @param int    $lemonType
     */
    public static function profilerLemon($ts, $method, $query, $keys = '', $value = '', $lemonType = 1)
    {
        $resultSize = is_array($value) || is_object($value) ? Utf::strlen(serialize($value)) : Utf::strlen($value);
        $keys = (array) $keys;

        $lemonTypes = array(
            2 => 'Lemon2',
            3 => 'Ab',
            4 => 'Bigfoot',
            5 => 'FriendsActivity',
            6 => 'Int2',
            7 => 'IntBig',
            8 => 'Retentions'
        );

        self::log(
            self::LOG_TYPE_LEMON, $ts, $query, $method, $resultSize, count($keys),
            array_key_exists($lemonType, $lemonTypes) ? $lemonTypes[$lemonType] : '',
            array('keys' => $keys, 'lemonType' => $lemonType)
        );
    }

    /**
     * @param        $ts
     * @param        $method
     * @param        $query
     * @param        $index
     * @param        $value
     * @param string $server
     * @param int    $countServerAnswer
     * @param int    $countNoEmpty
     */
    public static function profilerSharedQueue($ts, $method, $query, $index, $value, $server = '', $countServerAnswer = 0, $countNoEmpty = 0)
    {
        $resultSize = 0;
        $resultRows = 0;
        $data = '';

        switch ($query) {
            case 'PUSH' : {
                $data = serialize($value);
                $resultSize = Utf::strlen($data);
                if ($resultSize > 200) {
                    $key = Utf::substr($data, 0, 200) . '...';
                }
                break;
            }
            case 'POP' : {
                $resultRows = count($value);
                break;
            }
            case 'POPALL' : {
                $resultRows = count($value);
                break;
            }
        }

        $message = $query . ($query == 'PUSH' ? ' to ' : ' from ') . $index . (empty($data) ? '' : ' [' . $data . ']');
        self::log(self::LOG_TYPE_SQ, $ts, $message, $method, $resultSize, $resultRows);
    }

    /**
     * @param $ts
     * @param $client
     * @param $host
     * @param $method
     * @param $query
     */
    public static function profilerDald($ts, $client, $host, $method, $query)
    {
        $meta = $client . ':' . $host;
        self::log(self::LOG_TYPE_DALD, $ts, $query, $method, 0, 0, $meta);
    }

    /**
     * @param $ts
     * @param $typeId
     * @param $eventId
     * @param $userId
     * @param $emails
     * @param $isSystem
     * @param $viaPhpMail
     */
    public static function profilerEmail($ts, $typeId, $eventId, $userId, $emails, $isSystem, $viaPhpMail)
    {
        $message = http_build_query(array(
            'typrId'  => $typeId,
            'eventId' => $eventId,
            'userId'  => $userId,
            'emails'  => implode(',', $emails),
            'system'  => (int)$isSystem,
            'phpmail' => (int)$viaPhpMail
        ));

        self::log(self::LOG_TYPE_EMAIL, $ts, $message);
    }

    /**
     * @param     $ts
     * @param     $dbName
     * @param     $dbHost
     * @param     $method
     * @param     $query
     * @param int $count
     */
    public static function profilerMongo($ts, $dbName, $dbHost, $method, $query, $count = 0)
    {
        $meta = $dbName . ':' . $dbHost;
        $data = array('hash' => self::getQueryHash($query));
        self::log(self::LOG_TYPE_MONGO, $ts, (string)$query, $method, 0, $count, $meta, $data);
    }

    /**
     * Метод учета чтения внешних данных через http (file_get_content, curl)
     *
     * @param int    $ts
     * @param string $caller
     * @param string $msg
     * @param string $requestType - имя метода, через который было произведено получение данных
     * @param int    $responseSize
     * @param array  $requestParams
     */
    public static function profilerExternalRequest($ts, $caller, $msg, $requestType, $responseSize,
                                                   array $requestParams = array()
    )
    {
        self::log(self::LOG_TYPE_ER, $ts, $msg, $caller, $responseSize, 0, $requestType, $requestParams);
    }

    /**
     * @param mixed $var
     */
    public static function dump($var)
    {
        if (PRODUCTION && !Base_Service_Common::isStage(false)) {
            $trace = Base_Service_Log::getTrace(3);
            if (is_array($trace)) {
                $traceStr = ' from ' .  (isset($trace[0]) ? $trace[0] : ' xz') . ' from ' . (isset($trace[1]) ? $trace[1] : ' xz');
            } else {
                $traceStr = 'no trace';
            }
            trigger_error(__METHOD__ . ' called on production server ' . $traceStr);
            return;
        }

        $savedConfigs = array();
        if (extension_loaded('xdebug')) {
        	foreach (self::$_xDebugProperties as $propName => $propVal) {
        		$savedConfigs[$propName] = ini_get($propName);
        		ini_set($propName, $propVal);
        	}
        }
        ob_start();
        var_dump($var); // production-ok
        $output = ob_get_clean();
		
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
            $output = htmlspecialchars($output);
            $output = '<pre>' . $output . '</pre>';
        } else {
        	foreach ($savedConfigs as $propName => $propVal) {
        		ini_set($propName, $propVal);
        	}
        }

        self::$_dumps[] = $output;
    }

    /**
     * Получить массив с журналом
     * @return array
     */
    public static function getLogArray()
    {
        return self::getEnabled() ? self::$_log : array();
    }
    
    public static function profilerWd($_ts, $widgetName, $widgetId = 0, $cached = false)
    {
    	// Do nothing
    }

    /**
     * Получить журнал как json
     *
     * @return string
     */
    public static function getLogJson()
    {
        if (!self::getEnabled()) {
            return '';
        }

        $dumps = array();
        foreach (self::$_dumps as $str) {
            $dumps[] = iconv('CP1251', 'UTF-8', $str);
        }

        $result = array(
            'url'    => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'status' => '',
            'types'  => self::$_types,
            'params' => '',
            'total'  => array(
                'time'   => microtime(true) - $GLOBALS['time_start'],
                'memory' => round(memory_get_peak_usage(true) / 1024, 2),
            ),
            'events' => array(),
            'dumps'  => $dumps,
            'cstats' => array(
                'service' => Service_StatsFuncToId::getServiceByUrl($_SERVER['REQUEST_URI']),
                'url' => Service_StatsFuncToId::parseUrl($_SERVER['REQUEST_URI'])
            ),
            'xhprof' => self::getXHProfData(),
            'server' => self::getServer(),
        );
        

        // определим нарушение ограничений
        foreach (self::$_log as $log) {

            $typeInfo = self::$_types[$log['type']];
            if (!empty($typeInfo['lTime']) && $log['ts'] >= $typeInfo['lTime']) {
                $log['bold'][] = 'time';
            }
            if (!empty($typeInfo['lSize']) && $log['ts'] >= $typeInfo['lSize']) {
                $log['bold'][] = 'size';
            }
            if (!empty($typeInfo['lRows']) && $log['ts'] >= $typeInfo['lRows']) {
                $log['bold'][] = 'rows';
            }
            $result['events'][] = $log;
        }

        Utf::toUtfRecursive($result);
        $result = json_encode($result);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            $result = '<![CDATA[profiler:'.$result.']]>';
        } else {
            $result = '<script>var __profilerData = '.$result.'</script>';
        }

        return $result;
    }

    /**
     * For test only
     * @return string
     */
    public static function getLogHtml()
    {
        $html = '';

        if (self::getEnabled()) {

            $html  = '<div id="newprof">';
            $html .= '<p>' . str_repeat('=', 250) . '</p>';

            foreach (self::$_log as $log) {
                $type = self::$_types[$log['type']];
                $html .= '<p>';
                $html .= number_format($log['ts'], 6) . ' > ';
                $html .= $type['title'] . ' ';
                $html .= $log['msg'];
                $html .= '</p>';
            }

            $html .= '</div>';
        }

        $css = '<style>
        #newprof { font-size: 11px }
        #newprof p { margin: 0; padding: 0; line-height: 14px; }
        </style>';

        return $css . $html;
    }

    /**
     * @param $data mixed
     */
    public static function setXHProfData ($data)
    {
        self::$_xhprof = $data;
    }

    /**
     * @return mixed
     */
    public static function getXHProfData ()
    {
        return self::$_xhprof;
    }
    
    public static function getServer() {
    	return self::$_server;
    }
    
    public static function setServer($ip, $name, $group) {
    	self::$_server = array(
    		'ip' => $ip,
    		'name' => $name,
    		'group' => $group,
    	);
    }
}