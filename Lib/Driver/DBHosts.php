<?php

class Driver_DBHosts
{
    static $dbHosts = array();      // рабочий конфиг
    static $dbSlaveHost = array();  // выбраные слейв сервера
    static $_lemonDbHostsCache; // статик кэш для dbHosts в лемоне

    const CACHE_KEY = 'dbHosts_v4';
    const LEMON_KEY_PREFIX = 'dbHosts_v4';
    const LEMON_KEY = 1;
    const CACHE_KEY_ERROR = 'dbH_error:';
    const CACHE_KEY_ERROR_DRIVER = 'dbH_error_dr:';

    // Исходный текстовый файл с конфигом хостов БД
    const DB_HOSTS_TEXT_FILE = 'sys/db/var/dbReplica.txt';
    // Файл - копия, используемый при разливе
    const DB_HOSTS_TEXT_BACK = 'sys/db/var/dbReplica_back.txt';

    const OFF = 0;
    const ON = 1;
    const OFF_AUTO = 2;
    
    static $onOffText = array(
        self::ON => 'On',
        self::OFF => 'Off-hand',
        self::OFF_AUTO => 'Off-auto',
    );    

    const SLAVE = 0;
    const MASTER = 1;
    
    // работа с кластером репликации
    // - текущее состояние серверов храниться в MemCache
    // - первоначальная загрузка конфига MemCache идет с крон сервера
    
    /**
     * Грузит dbHosts из php-конфига
     */
    public static function initDefault()
    {
        self::$dbHosts = Base_Application::getInstance()->config['db']['dbhosts_default'];
    }
    
    /**
     * Сохраняет dbHosts в мемкеш. Если выполняется на zz, то пишет еще в текстовый файл. По серверам не размножает.
     */
    public static function saveHosts()
    {
        // сохраняем на все возможные сервера в конфиге
    	Base_Service_Memcache::setOnAllHost(self::CACHE_KEY, self::$dbHosts, 86400 * 30);

        if (Base_Service_Common::isControlServer()) {

            $logFile = 'sys/db/var/log_dbReplica.txt';
    	    @file_put_contents($logFile, date('Y-m-d H:i:s') . "\t" . file_get_contents(self::DB_HOSTS_TEXT_FILE) . "\n", FILE_APPEND);

            $textConfig = serialize(self::$dbHosts);
    	    file_put_contents(self::DB_HOSTS_TEXT_FILE, $textConfig);
    	    @file_put_contents($logFile, date('Y-m-d H:i:s') . "\t" . $textConfig . "\n", FILE_APPEND);
    	}
    }

    public static function updateDefaultFromMcData()
    {
        $defaultConfig = array();
        $mcHosts = self::loadHosts();
        foreach (Base_Application::getInstance()->config['db']['dbhosts_default'] as $dbName => $dbHosts) {
            $defaultConfig[$dbName] = array();
            foreach ($dbHosts as $dbHost => $hostData) {
                $defaultConfig[$dbName][$dbHost] = isset($mcHosts[$dbName][$dbHost]) ? $mcHosts[$dbName][$dbHost] : $hostData;
            }
        }
        self::$dbHosts = $defaultConfig;
    }

    /**
     * Размножает текстовый конфиг с zz на все скриптовые. Работает только на zz.
     * @todo после заливки файла на сервер - надо проверять его контрольную сумму
     */
    public static function deployHostTextConfig()
    {
        if (!Base_Service_Common::isControlServer()) {
            return false;
        }

        $logfile = 'sys/db/var/log_config_deploy.txt';

        // Первым делом - проверим файлик, который собираемся разливать, что там что-то похожее на конфиг
        $config = self::getConfigFromText();
        if (!$config || !is_array($config) || empty($config)) {
            file_put_contents($logfile, date('Y-m-d H:i:s') . "\t" . 'Config file is corrupted! Deploy aborted' . "\n", FILE_APPEND);
            return 0;
        }

        // Файл валиден - делаем его копию на текущий момент, и разливаем с копии
        // копию делаем для того, чтобы разлить именно валидную версию, и подстраховаться от того, что сам файлик поменяется
        if (!@copy(self::DB_HOSTS_TEXT_FILE, self::DB_HOSTS_TEXT_BACK)) {
            file_put_contents($logfile, date('Y-m-d H:i:s') . "\t" . 'Can`t create copy file for deploy! Deploy aborted' . "\n", FILE_APPEND);
            return 0;
        }

        // Собственно - разольем конфиг по скриптовым
        $ssh = new Base_Service_Ssh();
        $serversList = Base_Service_Common::getScriptServers(true);
        $count = 0;

        foreach ($serversList as $serverInfo) {
            if (ENGLISH_VERSION && $serverInfo['name'] == 'Script-01') {
                continue;
            }
            try {
                $ssh->connect($serverInfo['ip']);
                $ssh->keylogin('embria', './' . Base_Service_Ssh::PUBLIC_KEY_FILE, './' . Base_Service_Ssh::PRIVATE_KEY_FILE);
                $ssh->makeDir($ssh->getRealPath() . '/sys', '0777');
                $ssh->makeDir($ssh->getRealPath() . '/sys/db', '0777');
                $ssh->makeDir($ssh->getRealPath() . '/sys/db/var', '0777');
                $ssh->uploadFile(self::DB_HOSTS_TEXT_BACK, $ssh->getRealPath() . '/' . self::DB_HOSTS_TEXT_FILE);
                $count++;
            } catch (Exception $e) {
                file_put_contents($logfile, date('Y-m-d H:i:s') . "\t" . 'text config depoly to server ' . $serverInfo['name'] . ' failed' . "\n", FILE_APPEND);
                continue;
            }
        }

        return $count;
    }
    
    /**
     * Возвращает конфиг из текстового файла
     */
    public static function getConfigFromText()
    {
        $file = self::DB_HOSTS_TEXT_FILE;
        if (!file_exists($file)) {
            return false;
        }
        $contents = file_get_contents($file);
        if (empty($contents)) {
            return false;
        }
        return unserialize($contents);
    }

    /**
     * Инициализация конфига. Ищем хост конфиг в memcache, если нету в Lemon и Lemon2
     *
     * @return array
     *
     * @throws Exception
     */
    public static function loadHosts()
    {
        if (PRODUCTION) {
            self::$dbHosts = Base_Service_Memcache::get(self::CACHE_KEY);

            if (!self::$dbHosts) {
                self::$dbHosts = self::_getHostsFromLemon();
            }
        } else {
            self::$dbHosts = Base_Application::getInstance()->config['db']['dbhosts_default'];
        }

        if(!self::$dbHosts) {
            throw new Exception('Cant load dbHosts config');
        }

        return self::$dbHosts;
    }

    /**
     * Возвращает dbHosts из Lemon либо Lemon2. Если конфиг находится, то он сохраняется в статик-кэше на 1 секунду
     *
     * @return array|bool
     */
    private static function _getHostsFromLemon()
    {
        if(!empty(self::$_lemonDbHostsCache) && is_numeric(self::$_lemonDbHostsCache['time'])
            && ((microtime(1) - self::$_lemonDbHostsCache['time']) < 1)
        ) {
            return self::$_lemonDbHostsCache['hosts'];
        }

        $hosts = Base_Service_Lemon::get(__METHOD__, self::LEMON_KEY_PREFIX, self::LEMON_KEY);

        if(!$hosts) {
            $hosts = Base_Service_Lemon2::get(__METHOD__, self::LEMON_KEY_PREFIX, self::LEMON_KEY);
        }

        if($hosts) {
            self::$_lemonDbHostsCache = array(
                'time' => microtime(1),
                'hosts' => $hosts
            );
        }

        return $hosts;
    }

    public static function selectMaster($dbName)
    {
        // перебор хостов и выбор одного c master = 1 // update, delete
        //if (!self::$dbHosts) self::loadHosts();
        self::loadHosts();

        foreach(self::$dbHosts[$dbName] as $name => $server){
            if($server['master'] == self::MASTER && $server['on'] == self::ON) return $name;
        }
        return false;
    }

    /**
     * @param $dbName               имя БД
     * @param bool $onlySlave       выбирать только слейв-сервера
     * @param bool $ignoreOnFlag    выбирать даже выключенные из раздачи сервера
     * @return bool|array
     * возвращает false если не удалось найти сервер или массив-конфиг найденного сервера
     */
    public static function selectSlave($dbName, $onlySlave=false, $ignoreOnFlag = false)
    {

        // выбираем любой рабочий сервер с группы // select
        //if (!self::$dbHosts) self::loadHosts();
        self::loadHosts();

        if(isset(self::$dbSlaveHost[$dbName])){
            if(
                ($ignoreOnFlag || self::$dbHosts[$dbName][self::$dbSlaveHost[$dbName]]['on'] == self::ON)
                // Если нужны только слейвы, то ищем в кэше только их
                && (!$onlySlave || self::$dbHosts[$dbName][self::$dbSlaveHost[$dbName]]['master'] == self::SLAVE)
            ) {
                return self::$dbSlaveHost[$dbName];  // возвращаем ранее выбраный
            }
        }
        $dbWork = array();
        $find = 0;
        foreach(self::$dbHosts[$dbName] as $name => $server){
            if ($onlySlave && $server['master'] != self::SLAVE) {
                continue;
            }
            if($ignoreOnFlag || $server['on'] == self::ON) {
                $find = 1;
                $dbWork[$name] = $server; 
            }
        }
        if($find){
            $host = array_rand($dbWork);
            self::$dbSlaveHost[$dbName] = $host;     // запоминаем выбраный slave хост
            return $host;
        }
          return false;
    }

    public static function setMaster($dbName, $dbServer, $turnOn = false)
    {
        // устанавливаем новый мастер
        if (!self::$dbHosts) self::loadHosts();

        if (isset(self::$dbHosts[$dbName][$dbServer])) {
            foreach(self::$dbHosts[$dbName] as $name => $server) {
                self::$dbHosts[$dbName][$name]['master'] = self::SLAVE;
            }
            self::$dbHosts[$dbName][$dbServer]['master'] = self::MASTER;
            if ($turnOn) {
                self::$dbHosts[$dbName][$dbServer]['on'] = self::ON;
            }
            self::saveHosts();
            return true;
        }
        return false;
    }

    public static function offSlave($dbName, $dbServer, $off = self::OFF)
    {
        // выключаем слейв сервер
        if (!self::$dbHosts) self::loadHosts();

        if(isset(self::$dbHosts[$dbName][$dbServer])){
            self::$dbHosts[$dbName][$dbServer]['on'] = $off;
            self::saveHosts();
            return true;
        }
        return false;
    }

    public static function offAllSlaves($dbName, $off = self::OFF)
    {
        // выключаем слейв сервер
        if (!self::$dbHosts) self::loadHosts();

        foreach(self::$dbHosts[$dbName] as $name => $server){
            if (!$server['master'] && $server['on'] == self::ON) {
                self::$dbHosts[$dbName][$name]['on'] = $off;
            }
        }
        self::saveHosts();
        return true;
    }

    public static function onSlave($dbName, $dbServer)
    {
        // включаем слейв сервер
        if (!self::$dbHosts) self::loadHosts();

        if(isset(self::$dbHosts[$dbName][$dbServer])){
            self::$dbHosts[$dbName][$dbServer]['on'] = self::ON;
            self::saveHosts();
            return true;
        }
        return false;
    }

    public static function offMaster($dbName, $dbServer, $off = self::OFF)
    {
        // выключаем мастер сервер
        if (!self::$dbHosts) self::loadHosts();

        if(isset(self::$dbHosts[$dbName][$dbServer])){
            self::$dbHosts[$dbName][$dbServer]['on'] = $off;
            self::saveHosts();
            return true;
        }
           return false;
    }

    public static function onMaster($dbName, $dbServer)
    {
        // включаем мастер сервер
        if (!self::$dbHosts) self::loadHosts();

        if(isset(self::$dbHosts[$dbName][$dbServer])){
            self::$dbHosts[$dbName][$dbServer]['on'] = self::ON;
            self::saveHosts();
            return true;
        }
           return false;
    }

    public static function resetAllError()
    {
        // устанавливает все ключи ошибок в 0/ или их создает
        if (!self::$dbHosts) self::loadHosts();

        foreach(self::$dbHosts as $dbkey => $db){
            foreach($db as $serverkey => $server){
                Base_Service_Memcache::set(self::CACHE_KEY_ERROR.$dbkey."_".$serverkey, 0, 14*24*60*60);
                Base_Service_Memcache::set(self::CACHE_KEY_ERROR_DRIVER.$dbkey."_".$serverkey, 0, 14*24*60*60);
            }    
        }
    }

    public static function setError($dbName, $dbServer, $error, $mess)
    {
        // инкрементим счетчик ошибок
        if (!self::$dbHosts) self::loadHosts();

        if(isset(self::$dbHosts[$dbName][$dbServer])){
            if($error > 2000 || Utf::preg_match('/(memory|shutdown|denied|connect)/', $mess)){
                Base_Service_Memcache::increment(self::CACHE_KEY_ERROR_DRIVER.$dbName."_".$dbServer);
            }else{
                Base_Service_Memcache::increment(self::CACHE_KEY_ERROR.$dbName."_".$dbServer);
            }
            return true;
        }
        return false;
    }

    public static function getError($dbName, $dbServer)
    {
        // получаем ошибки пользовательские
        if (!self::$dbHosts) self::loadHosts();

        if(isset(self::$dbHosts[$dbName][$dbServer])){
            return Base_Service_Memcache::get(self::CACHE_KEY_ERROR.$dbName."_".$dbServer);
        }
        return false;
    }

    public static function getErrorDriver($dbName, $dbServer)
    {
        // получаем ошибки драйвера/коннектов
        if (!self::$dbHosts) self::loadHosts();

        if(isset(self::$dbHosts[$dbName][$dbServer])){
            return Base_Service_Memcache::get(self::CACHE_KEY_ERROR_DRIVER.$dbName."_".$dbServer);
        }
        return false;
    }     
}