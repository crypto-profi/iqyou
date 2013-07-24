<?php

class Driver_DBQuery 
{
    const SEARCH_SLAVE = -1;            // искать слейв-сервер бд если он включен, если выключен - брать мастер
    const SEARCH_MASTER = 1;            // искать мастер сервер бд
    const SEARCH_ANY = 0;               // искать или мастер или слейв
    const SEARCH_SLAVE_SURELY = 2;  // искать слейв, даже выключенный, и если не найти - умереть в муках эксепшона

    // для отслеживания стека вызова методов в сишной статсе
    public static $tracespy = Array(
		//'Dal_Relations_Bond::_list' => "bl"
		"Base_Dao_Geo::getLocationByName" => "loc_by_name"
    );
    public static $connections = array();         // массив подключений
	private static $last_dbh = false;      // последнее подключение к базе
	private static $lastDbName = array();  // массив текущих баз для подключений ['current'] - текущий 
	
	public static function Connect($dbName, $dbHost, $dbBase = '', $params = array())
	{
		// проверка коннекта
		if (!isset(self::$connections[$dbName][$dbHost]) ){
			// получение настроек соединения
		    if(isset(Base_Application::getInstance()->config['db']['connect'][$dbName][$dbHost])) {
	            $config = parse_url(Base_Application::getInstance()->config['db']['connect'][$dbName][$dbHost]);
	            // текущая база
	            $dbBaseDefault = str_replace('/', '', $config['path']);
	            if ($dbBase == '') {
	                $dbBase = $dbBaseDefault;
	            }
	            self::$lastDbName[$dbName][$dbHost]['default'] = $dbBaseDefault;  
	            self::$lastDbName[$dbName][$dbHost]['current'] = $dbBase;
	                  
	            if (Base_Service_Profiler_Log::$enabled) $_ts = microtime(true);
	            Service_StatsFuncToId::startDb('connect', $dbName, $dbHost, '_connect', true);
	            // коннект
	            $connectionParams = array();
	            if (isset($params['timeout'])) {
	                $connectionParams[PDO::ATTR_TIMEOUT] = (int) $params['timeout'];
	            }
                // для сфинкса нам не нужны персистентные коннекты
	            if (PRODUCTION && $dbName != 'database_sphinx' && $dbName != 'database_antispam_sphinx') {
	               $connectionParams[PDO::ATTR_PERSISTENT] = true;
	            }
	            try {
	               $dbh = new PDO('mysql:dbname='.$dbBase.';host='.$config['host'], $config['user'], @$config['pass'], $connectionParams);
	            } catch (PDOException $e) {
	            	Service_StatsFuncToId::stopDb(1);
	            	// пытаемся еще раз
	            	Service_StatsFuncToId::startDb('connect', $dbName, $dbHost, '_connect', true);
	            	try {
	            		sleep(1); // ожидание перед коннектом
	            		$dbh = new PDO('mysql:dbname='.$dbBase.';host='.$config['host'], $config['user'], @$config['pass'], $connectionParams);
	            	}catch (PDOException $e){
	            		Service_StatsFuncToId::stopDb(1);
	                	Driver_DBHosts::setError($dbName, $dbHost, 2009, $e->getMessage());
                    	throw new Base_Exception($dbName . ':' . $dbHost . ':' . $dbBase . ' - ' . $e->getMessage());
	            	}
	            	Base_Exception::logError("Reconnect DB: ".$dbName . ':' . $dbHost . ':' . $dbBase . ' - ' . $e->getMessage(), $e->getTraceAsString());
	            }
	            Service_StatsFuncToId::stopDb($dbh ? 0 : 1); 
	            if (Base_Service_Profiler_Log::$enabled) {
	                Base_Service_Profiler_Log::profilerDb($_ts, $dbName, $dbHost, 'Driver_DBQuery.Connect', 'connect');
	            }
	            
            	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
/*
            	if (!PRODUCTION) {
	            	try {
                            $dbh->query('SET NAMES "' . Utf::charsetDb() . '"');
	            	} catch (PDOException $e) {
	            	    Driver_DBHosts::setError($dbName, $dbHost, $e->errorInfo[1], $e->errorInfo[2]);
	            		throw new Base_Exception($e->getMessage());
	            	}
            	}
*/
            	self::$connections[$dbName][$dbHost] = $dbh;
            	return true;            
		    } else {
                throw new Base_Exception("Can`t find connection info for server ".$dbName.".".$dbHost." see config");
		    }
		}
		// проверка текущий базы и изменение
		if($dbBase == '') $dbBase = self::$lastDbName[$dbName][$dbHost]['default'];
		if(!isset(self::$lastDbName[$dbName][$dbHost]['current']) || $dbBase != self::$lastDbName[$dbName][$dbHost]['current']){
//			echo "USE $dbBase<br>";
            try{
                self::$connections[$dbName][$dbHost]->query('USE '.$dbBase.";");
                self::$lastDbName[$dbName][$dbHost]['current'] = $dbBase;
            }catch (PDOException $e){
     		    throw new Base_Exception($e->getMessage());
            }			 
        }
	    return true;
	}
	
    public static function logerr($text)
    {
        @file_put_contents('var/log/driverPdoErr.txt', date('Y-m-d H:i:s')."\t$text\n", FILE_APPEND);
    }	
	
    public static function fetchCol($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase);
    }
    
    public static function fetchAll($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase);
    }
    
    public static function fetchOne($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase);
    }
    
    public static function fetchRow($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase);
    }
    
    public static function fetchPairs($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase);
    }
    
    public static function fetchAssoc($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase);
    }
    
    public static function fetchColMaster($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_MASTER);
    }
    
    public static function fetchAllMaster($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_MASTER);
    }
    
    public static function fetchOneMaster($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_MASTER);
    }
    
    public static function fetchRowMaster($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_MASTER);
    }    
    
    public static function fetchPairsMaster($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_MASTER);
    }

    public static function fetchPairsSlave($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_SLAVE);
    }
    
    public static function fetchAssocMaster($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_MASTER);
    }
    
    public static function fetchAssocSlave($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_SLAVE);
    }

    public static function fetchColSlave($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_SLAVE);
    }
    
    public static function fetchAllSlave($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_SLAVE);
    }

    public static function fetchAllSlaveSurely($query, $dbName, $_method='', $dbBase = '')
    {
        return self::querySelect(__FUNCTION__, $query, $dbName, $_method, $dbBase, self::SEARCH_SLAVE_SURELY);
    }
    
    public static function tracespy($_method = '')
    {
		if(!$_method) {
			$_func = '';
            $trace = debug_backtrace(false);
            foreach($trace as $t) {
                if(isset($t["class"]) && (Utf::strpos($t["class"], "Driver") === FALSE)) {
                    $_func = $t["class"]."::".$t["function"];
                    break;
                }
            }
			trigger_error($_func.": __METHOD__ missed", E_USER_WARNING);
        }
        else {
            if(isset(self::$tracespy[$_method])) {
                $trace = debug_backtrace(false);
                $str = "";
                foreach($trace as $t) {
                    if(isset($t["class"])) {
                        if(Utf::strpos($t["class"], "Driver") !== FALSE) {
                            continue;
                        }
                        $st = $t["class"]."::".$t["function"];
                        if(Utf::strpos($st, $_method) !== FALSE) {
                            continue;
						}
						if(isset(self::$tracespy[$st])) {
							$str .= self::$tracespy[$st].'.';
							continue;
						}
                        $str .= (isset($t["class"]) ? $t["class"] : "") . "." . $t["function"];
                        break;
                    }
                }
                $_method = self::$tracespy[$_method].".".$str;
            }
        }

        $_method = (!$_method ? 'Driver_DBQuery.querySelect' : str_replace('::', '.', $_method));
        return $_method;
    }

    /**
     * @param string $func Метод (тип запроса в бд - fetchCol, fetchRow, fetchAll и тд)
     * @param string $query     сам запрос
     * @param string $dbName    имя базы в которую идет запрос (database_heap, database_part5...)
     * @param string $_method   пхп-класс и метод из которого вызвано обращение к БД
     * @param string $dbBase    БД к которой надо сделать запрос (никогда в ручную не указывается, всегда берется дефолтная из конфига)
     * @param int $master       логика выбора мастера или слейва (Driver_DBQuery::SEARCH_*)
     * @return array
     * @throws Base_Exception
     *
     * Выбирает сервер для селекта, коннектится к базе если нужно и отправляет запрос на исполнение.
     */
    private static function querySelect($func, $query, $dbName, $_method = '', $dbBase = '', $master = self::SEARCH_ANY)
    {
        if ($master == self::SEARCH_MASTER) $func = str_replace('Master', '', $func);
        if ($master == self::SEARCH_SLAVE_SURELY) {
            $func = str_replace('SlaveSurely', '', $func);

            // селекты со слейва находятся под суровым гнетом, поэтому их число будет трекаться в аналитику и при большом количестве оных будут алерты, также будем писать запросы в лог чтоб мало ли что...
            $analytics = new Base_Service_Counter_Analytics();
            $analytics->increment(null, 'db_slave_selects');
            $user = Base_Context::getInstance()->getUser();
            Base_Service_Log::log('SlaveDbSelect', array($user ? $user->getId() : 'no_user', substr($query, 0, 300)));
        }
        if ($master == self::SEARCH_SLAVE || $master == self::SEARCH_SLAVE_SURELY) {
            $func = str_replace('Slave', '', $func);
            $dbHost = Driver_DBHosts::SelectSlave($dbName, true, $master == self::SEARCH_SLAVE_SURELY);
            if($dbHost === false && $master != self::SEARCH_SLAVE_SURELY){
                $dbHost = Driver_DBHosts::SelectSlave($dbName);
            }
        } else {
            $dbHost = $master == self::SEARCH_MASTER ? Driver_DBHosts::SelectMaster($dbName):Driver_DBHosts::SelectSlave($dbName);
        }
        
    	if($dbHost === false){
    	   throw new Base_Exception("can`t find Slave server $dbName:$dbHost see Hosts config");
    	}
    	if(!self::Connect($dbName, $dbHost, $dbBase)){
    		$mess = "can`t connections $dbName:$dbHost";
            self::logerr($mess);
            throw new Base_Exception($mess);
    	}

        $_method = self::tracespy($_method);

        if (Base_Service_Profiler_Log::$enabled) $_ts = microtime(true);
        Service_StatsFuncToId::startDb('select', $dbName, $dbHost, $_method, false, Driver_Sql::_getTableFromSelect($query));
        $result = array();
        $count = 0;
        try{
        	self::$last_dbh = self::$connections[$dbName][$dbHost];
        	$sth = self::$last_dbh->prepare($query);
	        $sth->execute();
	        $count = $sth->rowCount();
	        if ($func == 'fetchCol') {
	            $result = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
	        } elseif ($func == 'fetchAll') {
	            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
	        } elseif ($func == 'fetchOne') {
	            $result = $sth->fetchColumn(0);
	        } elseif ($func == 'fetchRow') {
	            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
	            $result = @$result[0];
	        } elseif ($func == 'fetchPairs') {
	            $result = array();
                while ($row = $sth->fetch(PDO::FETCH_NUM)) {
                    $result[$row[0]] = $row[1];
                }
            } elseif ($func == 'fetchAssoc') {
                $result = array();
                while ($row = $sth->fetch(Zend_Db::FETCH_ASSOC)) {
                    $tmp = array_values(array_slice($row, 0, 1));
                    $result[$tmp[0]] = $row;
                }
	        }

            //if (!PRODUCTION) {
            //    file_put_contents('var/tmp/queries.select.log', date('Y-m-d H:i:s') . "\t" . $query . "\n", FILE_APPEND);
            //}
        }catch(PDOException $e){
        	self::$last_dbh = false;
            Service_StatsFuncToId::stopDb(1, $count);
            Driver_DBHosts::setError($dbName, $dbHost, @$e->errorInfo[1], @$e->errorInfo[2]);
        	throw new Base_Exception($e->getMessage().' '.$query);
        }
        Service_StatsFuncToId::stopDb(0, $count);
        if (Base_Service_Profiler_Log::$enabled)  
            Base_Service_Profiler_Log::profilerDb($_ts, $dbName, $dbHost, $_method, $query, $count);
        
        return $result;
    }
    
    public static function insert($query, $dbName, $_method='', $dbBase = '', $table = null)
    {
        return self::queryWrite($query, $dbName, $_method, $dbBase, $table);
    }

    public static function update($query, $dbName, $_method='', $dbBase = '', $table = null)
    {
        return self::queryWrite($query, $dbName, $_method, $dbBase, $table);
    }

    public static function delete($query, $dbName, $_method='', $dbBase = '', $table = null)
    {
        return self::queryWrite($query, $dbName, $_method, $dbBase, $table);
    }

    public static function queryWrite($query, $dbName, $_method='', $dbBase = '', $table = null, $dbHost = false)
    {
        if (empty($dbHost)) {
            $dbHost = Driver_DBHosts::SelectMaster($dbName);
        }
        if ($dbHost === false) {
            throw new Base_Exception("can`t find Master server $dbName:$dbHost see Hosts config");
        }
        if(!self::Connect($dbName, $dbHost, $dbBase)){
            $mess = "can`t connections $dbName:$dbHost";
            self::logerr($mess);
            throw new Base_Exception($mess);
        } 

        if (!PRODUCTION) {
            $filename = 'var/tmp/queries.dml.log';
            if (filesize($filename) > 1024*1024) {
                file_put_contents($filename, '');
            }
            file_put_contents($filename, date('Y-m-d H:i:s') . "\t" . $query . "\n", FILE_APPEND);
        }

        $_method = self::tracespy($_method);

        if (Base_Service_Profiler_Log::$enabled) $_ts = microtime(true);
        Service_StatsFuncToId::startDb('update', $dbName, $dbHost, $_method, false, $table);
        $count = 0;
        $tries = 0;
        do {
            $retry = false;
            try {
                self::$last_dbh = self::$connections[$dbName][$dbHost];
                $sth = self::$last_dbh->prepare($query);
                $sth->execute();
                $count = $sth->rowCount();
            } catch (PDOException $e) {
            	self::$last_dbh = false;
                if ($tries < 10 && ($e->errorInfo[1] == 1213) ) {
                    $retry = true;
                    trigger_error(__METHOD__." tries[$tries] ".$e->getCode()." ".$e->errorInfo[1]." ".(isset($e->errorInfo[2])?($e->errorInfo[2]):""), E_USER_WARNING);
                    usleep(10000);
                } else {
                	self::$last_dbh = false;
                	Service_StatsFuncToId::stopDb(1, $count);
                	Driver_DBHosts::setError($dbName, $dbHost, $e->errorInfo[1], isset($e->errorInfo[2])?($e->errorInfo[2]):"");
                    throw new Base_Exception($e->getMessage().' '.$query);
                }
                $tries++;
            }
        } while ($retry);   
        
        Service_StatsFuncToId::stopDb(0, $count);
        if (Base_Service_Profiler_Log::$enabled) {
            Base_Service_Profiler_Log::profilerDb($_ts, $dbName, $dbHost, $_method, $query, $count);
        }
        
        if ($table) {
            $projectGlobalConfig = Base_Application::getInstance()->config['project'];
            if (!empty($projectGlobalConfig['db_sync']['tables']) && in_array($table, $projectGlobalConfig['db_sync']['tables'])) {
                if ($table != 'market_goods_system' || Utf::strpos($_SERVER['REQUEST_URI'], '/admin/marketplace') !== false) {
                    // костылем пропускаем обновление market_goods_system не из админки 
                    Base_Service_Dbsync::setNeedProjectSync(true, $table);
                }
            }
        }
        
        return $count;
    }
    
    public static function lastInsertId()
    {
        if (Base_Service_Profiler_Log::$enabled) $_ts = microtime(true);
        try {
    	   $result = self::$last_dbh->lastInsertId(); 
        }catch (PDOException $e){
            throw new Base_Exception($e->getMessage());
        }
        if (Base_Service_Profiler_Log::$enabled) 
            Base_Service_Profiler_Log::profilerDb($_ts, '_last_', '_last_', 'Driver_DBQuery.lastInsertId', "LastInsertId");
        // дебаг возврата нуля
        if (!$result) {
            $backtrace = debug_backtrace();
            Base_Service_Log::text($result.' called from '.$backtrace[1]['class'].'.'.$backtrace[1]['function'].':'.$backtrace[0]['line'], 'lastinsertid_zeros');
        }
        return $result;
    }

    public static function queryFoundRows()
    {
    	$res = 0;
    	if (Base_Service_Profiler_Log::$enabled) $_ts = microtime(true);
        try {
            $sth = self::$last_dbh->prepare('SELECT FOUND_ROWS()');
            $sth->execute();        	
            $res = $sth->fetchColumn(0);
    	}catch (PDOException $e){
            throw new Base_Exception($e->getMessage());
    	}
    	if (Base_Service_Profiler_Log::$enabled) 
    	   Base_Service_Profiler_Log::profilerDb($_ts, '_last_', '_last_', 'Driver_DBQuery.queryFoundRows', "SELECT FOUND_ROWS()");
    	return $res;
    }
    
}
