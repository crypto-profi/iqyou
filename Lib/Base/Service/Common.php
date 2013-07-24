<?php

class Base_Service_Common
{
    const SALT = 'af<o!?sf$i>US3;4%+AEGd#Q-z';

    const SCRIPT_SERVERS_CONFIG_TEXT = 'var/script-servers-details.txt';
    const STATIC_SERVERS_CONFIG_TEXT = 'var/static-servers-details.txt';
    const VIRTSTAGE_SERVERS_CONFIG_TEXT = 'var/virtstages-servers-details.txt';
    const IS_OUR_IP_COOKIE = 'ourip';

    const GROUP_MCKEY = 'script_server_group';

    const SCRIPT_SERVERS_NEW_SCHEMA = 0; 
    const MEMCACHE_SCRIPT_SERVERS_KEY = 'common_scriptservers';
    const LEMON_SCRIPT_SERVERS_PREFIX = 'common_scriptservers';
    const LEMON_SCRIPT_SERVERS_KEY    = 1;

    private static $isOurIp = null;
    private static $needProjectSync;

    public static function isOurPerson()
    {
        if (self::$isOurIp !== null) {
            return self::$isOurIp;
        }

        if (isset($_COOKIE['disableIsOurIp']) && $_COOKIE['disableIsOurIp'] == 1) {
            self::$isOurIp = false;
            return false;
        }

        $uid = 0;
        $isUserKnown = false;

        if (class_exists('Base_Context')) {
            $currentUserGlobal = Base_Context::getInstance()->getUser();
            if ($currentUserGlobal) {
                $uid = $currentUserGlobal->getId();
                $isUserKnown = true;
            }
        } else {
            $uid = (isset($GLOBALS['requestUserId']) ? $GLOBALS['requestUserId'] : @$_GET['userId']);
        }

        $isRealOurIp = self::isRealOurIp();
        $ourIds = Base_Service_Acl::getAdminIds();

        $isOurIp = ($isRealOurIp || (in_array($uid, $ourIds))) ? true : false;

        // Êåøèðóåì çíà÷åíèå òîëüêî ïîëå òîãî, êàê îïðåäåëåí òåêóùèé þçåð, èíà÷å âñå ñëîìàåòñÿ
        if ($isUserKnown) {
            self::$isOurIp = $isOurIp;
        }

        return $isOurIp;
    }
    private static function logOurPerson()
    {
        $data = array('HTTP_X_REAL_IP' => @$_SERVER['HTTP_X_REAL_IP'], 'REMOTE_ADDR' => @$_SERVER['REMOTE_ADDR'], 'UID' => @$_COOKIE['uid'],
            'HTTP_REFERER' => @$_SERVER['HTTP_REFERER'], 'REQUEST_URI' => @$_SERVER['REQUEST_URI'],
        );
        Base_Service_Log::log('ourperson', array(serialize($data)));
    }

    /**
     * Ñóòü ôóíêöèè íèêàê íå ñîîòíîñèòñÿ ñ åå íàçâàíèåì. Íà ñàìîì äåëå, îíà âêëþ÷àåò ðàçíûå îïöèè,
     * âêëþ÷åííûå òîëüêî äëÿ òåñòîâîãî ðåæèìà.
     */
    public static function isOurIp()
    {
        // âêëþ÷åíèå ïî êóêå
        if (!isset($_COOKIE[self::IS_OUR_IP_COOKIE]) || $_COOKIE[self::IS_OUR_IP_COOKIE] != 1) {
            return false;
        }
        return self::isOurPerson();
    }

    //public static function is

    public static function isRealOurIp($ip = false)
    {
        if ($ip) {
            $realIp = $ip;
        } else {
            $realIp = self::getRealIp();
        }

        $isOurIp = ((
                    $realIp == '127.0.0.1'
                    || strpos($realIp, '192.168.1.33') === 0 
                    || !PRODUCTION) ? true : false);

        if ($realIp == '10.13.177.109') {
            $isOurIp = false;
        }
        return $isOurIp;
    }

    /**
     * @deprecated use Base_Model_User::isAdmin() instead
     */
    public static function isAdminId($userId)
    {
        return self::isOurId($userId);
    }

    /**
     * @deprecated use Base_Model_User::isAdmin() instead
     *
     * Check user ID. For testing purpose
     *
     * @param Int $id
     * @return bool
     */
    public static function isOurId($id)
    {
        return in_array($id, Base_Service_Acl::getAdminIds());
    }

    /**
     * Returns user IP
     *
     * @return string
     */
    public static function getRealIp()
    {
        return isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];
    }

    public static function getRealIpWithOpera()
    {
        $userAgent = Base_Context::getInstance()->getRequest()->getServer('HTTP_USER_AGENT');
        $xForward = Base_Context::getInstance()->getRequest()->getServer('HTTP_X_FORWARDED_FOR');

        if ($userAgent && $xForward) {
            if(preg_match("/Opera Mini/i", $userAgent))
            {
                preg_match_all("|([0-9]{1,3}\.){3}[0-9]{1,3}|",$xForward,$arr_ip);
                $ip = $arr_ip[0][0];
                if(!empty($ip)) {
                    return $ip;
                }
            }
        }

        return self::getRealIp();
    }

    /**
     * Âîçâðàùàåò õåø, çàâèñÿùèé îò IP àäðåñà
     */
    public static function getIpHash($ip)
    {
        $salt = 'N3v3rG0nNaG1v3youUp';
        return Utf::substr(md5($ip . $salt), -8);
    }


    /**
     * Âîçâðàùàåò ñïèñîê ñêðèïòîâûõ ñåðâåðîâ
     *
     * @return array
     */
    public static function getScriptServers($upOnly = false,
        $skipMemcache = false,
        $includeTestLangServer = false, /* âðåìåíûé êîñòûëü äëÿ òåñòîâ */
        $includeControlServer = false,
        $includeVirtualStages = false /* Âêëþ÷èòü ëè â âûâîä âèðòóàëüíûå ñòåéäæè? Þçàåòñÿ äëÿ SQ */)
    {
        $cacheKey = self::MEMCACHE_SCRIPT_SERVERS_KEY;

        $serversGroups = Base_Service_Memcache::get(self::GROUP_MCKEY, __METHOD__);

        if(self::SCRIPT_SERVERS_NEW_SCHEMA) {
            $servers = self::_getScriptServers($skipMemcache);
        } else {
            $servers = $skipMemcache ? false : Base_Service_Memcache::get($cacheKey, __METHOD__);

            if (!$servers) {

                $serversString = @file(self::SCRIPT_SERVERS_CONFIG_TEXT);
                $serversString = $serversString ? $serversString : array();
                $servers = array();
                foreach ($serversString as $serverInfo) {
                    $info = explode('|', $serverInfo);
                    if (count($info) >= 4) {
                        $servers[$info[0]] = array('ip' => $info[0],
                                                   'extIp' => $info[1],
                                                   'name' => $info[2],
                                                   'status' => (int)$info[3],
                                                   'nginx' => (int)@$info[4],
                        );
                    }
                }

                if (self::isControlServer()) {
                    // êëþ÷ â ìåìêåøå ñòàâèòñÿ òîëüêî ñ zz. òàê êàê òàì êîíôèã àêòóàëüíûé âñåãäà.
                    Base_Service_Memcache::setOnAllHost($cacheKey, $servers, 86400 * 7);
                }
            }
        }

        if ($upOnly) {
            foreach ($servers as $key => $serverInfo) {
                if ($includeControlServer && ($serverInfo['name'] == 'control-server' || $serverInfo['name'] == 'control-server2')) {
                    continue;
                }
                if ($serverInfo['status'] !== 1) {
                    unset($servers[$key]);
                }
            }
        }

        foreach ($servers as $ip => $server) {
            $servers[$ip]['group'] = isset($serversGroups[$ip]) ? $serversGroups[$ip] : '';
        }

        /*
        // îòêëþ÷àåì äî ëó÷øèõ âðåìåí
        if (PRODUCTION && $includeTestLangServer) {
            $servers['10.13.177.177'] = array('ip' => '10.13.177.177',
                                            'extIp' => '91.210.7.40',
                                            'name' => 'Qiped-script-01',
                                            'status' => 1,
                                            'nginx' => 0,
            );
        }
        */
        if ($includeVirtualStages) {
            $servers += self::getVirtualStageServers($upOnly, $skipMemcache);
        }

        return $servers;
    }

    /**
     * Âîçâðàùàåò ñïèñîê ñêðèïòîâûõ ñåðâåðîâ
     *
     * @return array
     */
    public static function getScriptServers2($upOnly = false,
                                            $skipMemcache = false,
                                            $includeTestLangServer = false, /* âðåìåíûé êîñòûëü äëÿ òåñòîâ */
                                            $includeControlServer = false,
                                            $includeVirtualStages = false, /* Âêëþ÷èòü ëè â âûâîä âèðòóàëüíûå ñòåéäæè? Þçàåòñÿ äëÿ SQ */
                                            $forceControlsExclude = false)
    {
        $cacheKey = self::MEMCACHE_SCRIPT_SERVERS_KEY;
        $serversGroups = Base_Service_Memcache::get(self::GROUP_MCKEY, __METHOD__);

        if (self::SCRIPT_SERVERS_NEW_SCHEMA) {
            $servers = self::_getScriptServers($skipMemcache);
        } else {
            $servers = $skipMemcache ? false : Base_Service_Memcache::get($cacheKey, __METHOD__);

            if (!$servers) {

                Base_Service_Log::text('case', 'test_log_zz');

                $serversString = @file(self::SCRIPT_SERVERS_CONFIG_TEXT);
                $serversString = $serversString ? $serversString : array();
                $servers = array();

                foreach ($serversString as $serverInfo) {
                    $info = explode('|', $serverInfo);
                    if (count($info) >= 4) {
                        $servers[$info[0]] = array('ip' => $info[0],
                                                   'extIp' => $info[1],
                                                   'name' => $info[2],
                                                   'status' => (int)$info[3],
                                                   'nginx' => (int)@$info[4],
                        );
                    }
                }

                if (self::isControlServer()) {
                    // êëþ÷ â ìåìêåøå ñòàâèòñÿ òîëüêî ñ zz. òàê êàê òàì êîíôèã àêòóàëüíûé âñåãäà.
                    Base_Service_Memcache::setOnAllHost($cacheKey, $servers, 86400 * 7);
                }
            }
        }

        if ($upOnly) {
            foreach ($servers as $key => $serverInfo) {
                if (($serverInfo['name'] == 'control-server' || $serverInfo['name'] == 'control-server2')) {
                    if ($includeControlServer) {
                        continue;
                    } elseif ($forceControlsExclude) {
                        unset($servers[$key]);
                        continue;
                    }
                }
                if ($serverInfo['status'] !== 1) {
                    unset($servers[$key]);
                }
            }
        }

        foreach ($servers as $ip => $server) {
            $servers[$ip]['group'] = isset($serversGroups[$ip]) ? $serversGroups[$ip] : '';
        }

        /*
        // îòêëþ÷àåì äî ëó÷øèõ âðåìåí
        if (PRODUCTION && $includeTestLangServer) {
            $servers['10.13.177.177'] = array('ip' => '10.13.177.177',
                                            'extIp' => '91.210.7.40',
                                            'name' => 'Qiped-script-01',
                                            'status' => 1,
                                            'nginx' => 0,
            );
        }
        */
        if ($includeVirtualStages) {
            $servers += self::getVirtualStageServers($upOnly, $skipMemcache);
        }

        return $servers;
    }

    /**
     * Ïîëó÷åíèå êîíôèãà ñêðèïòîâûõ ñåðâåðîâ èç memcache/lemon
     *
     * @param bool $ignoreMemcache
     *
     * @return array|bool
     */
    private function _getScriptServers($ignoreMemcache = false)
    {
        $config = false;

        if (!$ignoreMemcache) {
            $config = Base_Service_Memcache::get(self::MEMCACHE_SCRIPT_SERVERS_KEY . '_2', __METHOD__);
        }

        if ($config === false) {
            $config = Base_Service_Lemon::get(
                __METHOD__, self::LEMON_SCRIPT_SERVERS_PREFIX . '_2', self::LEMON_SCRIPT_SERVERS_KEY
            );
        }

        if ($config === false) {
            $config = Base_Service_Lemon2::get(
                __METHOD__, self::LEMON_SCRIPT_SERVERS_PREFIX . '_2', self::LEMON_SCRIPT_SERVERS_KEY
            );
        }

        return $config;
    }

    /**
     * Ïðîâåðÿåò äîñòóïíîñòü è ðàáîòîñïîñîáíîñòü øàðåíûõ î÷åðåäåé íà âèðòóàëüíûõ ñåðâåðàõ.
     * Â ñëó÷àå ôýéëà îäíîãî èç íèõ îáíîâëÿåò ñòàòóñ â ôàéëå è ìåìêýøå
     */
    public static function checkVitualStageServers()
    {
        if (!self::isControlServer()) {
            return;
        }

        $servers = self::getVirtualStageServers();

        $async = new Base_Util_AsyncHttp();
        $async->options = array(
            CURLOPT_TIMEOUT => 1
        );
        foreach ($servers as $intIp => $server) {
            $async->get('http://' . $intIp . '/fast/sharedtesting/monitoring.php?ip=' . $intIp, null, null, $intIp);
        }

        $statusChanged = array();
        for ($i = 0; $i < 2; $i++) {
            foreach ($async->execute() as $intIp => $response) {
                $failed = ($response['error'] || $response['info']['http_code'] != 200 || strpos($response['output'], 'OK') === false);
                $sName  = isset($servers[$intIp]['name']) ? $servers[$intIp]['name'] : '???';

                $newStatus = null;

                if ($failed && $servers[$intIp]['status'] != 0) {
                    $newStatus = $servers[$intIp]['status'] = 0;
                } elseif (!$failed && $servers[$intIp]['status'] != 1) {
                    $newStatus = $servers[$intIp]['status'] = 1;
                }

                if ($newStatus !== null) {
                    if (isset($statusChanged[$intIp]) && $statusChanged[$intIp]['status'] != $newStatus) {
                        unset($statusChanged[$intIp]);
                    } elseif ($i == 0) { // çàïèñûâàåì òîëüêî åñëè ñòàòóñ ïîìåíÿëñÿ ïðè ïåðâîì çàïóñêå
                        $statusChanged[$intIp] = array(
                            'name' => $sName,
                            'status' => $newStatus,
                            'http_code' => $response['info']['http_code'],
                            'response' => $response['output'],
                            'error' => $response['error']
                        );
                    }
                }
            }
            sleep(2); // ñïèì ñåêóíäó ïåðåä ñëåäóþùèì çàïóñêîì
        }

        if (!empty($statusChanged)) {
            $logMsg    = '%s [%s] is turned %s; http_code: %s; response: %s; error: %s';
            $nagiosMsg = '/usr/local/bin/notify-nagios %s \'SQ Check\' %s';

            self::saveServersFile($servers, self::VIRTSTAGE_SERVERS_CONFIG_TEXT);
            foreach ($statusChanged as $intIp => $data) {
                Base_Service_Log::text(vsprintf($logMsg, array(
                    $intIp,
                    $data['name'],
                    ($data['status'] == 0 ? 'off' : 'on'),
                    $data['http_code'],
                    $data['response'],
                    $data['error']
                )), 'log_cron_vstages');

                exec(vsprintf($nagiosMsg, array(
                    strtolower($data['name']),
                    ($data['status'] == 0 ? 'WARNING "WARNING: switched OFF from SQ list"' : 'OK "Switched ON to SQ list"')
                )));
            }
        }

        return count($statusChanged);
    }

    /**
     * Îáíîâëÿåò ôàéëèê ñåðâåðàìè
     * @param $servers
     * @param $file
     * @return mixed
     */
    private static function saveServersFile($servers, $file)
    {
        if ($servers) {
            @rename($file . '.4.bak', $file . '.5.bak');
            @rename($file . '.3.bak', $file . '.4.bak');
            @rename($file . '.2.bak', $file . '.3.bak');
            @rename($file . '.1.bak', $file . '.2.bak');
            @rename($file, $file . '.1.bak');

            @unlink($file);
            foreach ($servers as $server) {
                file_put_contents($file, $server['ip'] . '|' . $server['extIp'] . '|' . $server['name'] . '|' . $server['status'] . '|' . $server['nginx'] . "\n", FILE_APPEND);
            }

            chmod($file, 0660);
        }

        if ($file == Base_Service_Common::VIRTSTAGE_SERVERS_CONFIG_TEXT) {
            Base_Service_Memcache::delete('virtual_stageservers');
            Base_Service_Common::getVirtualStageServers(false, true);
        }

        return $servers;
    }

    /**
     * Âîçâðàùàåò ñïèñîê ñåðâåðîâ âèðòóàëüíûõ ñòåéäæåé
     *
     * @return array
     */
    public static function getVirtualStageServers($upOnly = false, $skipMemcache = false)
    {
        $cacheKey = 'virtual_stageservers';
        $servers = $skipMemcache ? false : Base_Service_Memcache::get($cacheKey, __METHOD__);

        if (!$servers) {
            $serversString = @file(self::VIRTSTAGE_SERVERS_CONFIG_TEXT);
            $serversString  = $serversString ? $serversString : array();
            $servers = array();
            foreach ($serversString as $serverInfo) {
                $info = explode('|', $serverInfo);
                if (count($info) >= 4) {
                    $servers[$info[0]] = array('ip' => $info[0],
                        'extIp' => $info[1],
                        'name' => $info[2],
                        'status' => (int)$info[3],
                        'nginx' => (int)@$info[4],
                    );
                }
            }

            if (self::isControlServer()) {
                // êëþ÷ â ìåìêåøå ñòàâèòñÿ òîëüêî ñ zz. òàê êàê òàì êîíôèã àêòóàëüíûé âñåãäà.
                Base_Service_Memcache::setOnAllHost($cacheKey, $servers, 86400 * 7);
            }
        }

        if ($upOnly) {
            foreach ($servers as $key => $serverInfo) {
                if ($serverInfo['status'] !== 1) {
                    unset($servers[$key]);
                }
            }
        }

        foreach ($servers as $ip => $server) {
            $servers[$ip]['group'] = 'V-stage';
        }

        return $servers;
    }

    private static function getStaticConfigCacheKey()
    {
        return 'common_staticservers';
    }

    public static function cacheStaticServersConfig($config)
    {
        // êëþ÷ â ìåìêåøå ñòàâèòñÿ òîëüêî ñ zz. òàê êàê òàì êîíôèã àêòóàëüíûé âñåãäà.
        if (!self::isControlServer()) {
            return false;
        }
        if (is_string($config) && !empty($config)) {
            $config = @unserialize($config);
        }
        if (!is_array($config) || empty($config)) {
            return false;
        }
        $cacheKey = self::getStaticConfigCacheKey();
        Base_Service_Memcache::setOnAllHost($cacheKey, $config, 86400 * 7);
        return true;
    }

    public static function getStaticServers($activeOnly = false, $skipMemcache = false)
    {
        if (!PRODUCTION) {
            if (strpos(Base_Project_Manager::getProject()->getDomain(), 'fs16.vs58.net') === false) {
                return array();
            }
        }
        $cacheKey = self::getStaticConfigCacheKey();
        $servers = $skipMemcache ? false : Base_Service_Memcache::get($cacheKey);

        if (!$servers && file_exists(self::STATIC_SERVERS_CONFIG_TEXT)) {
            $serversString = file_get_contents(self::STATIC_SERVERS_CONFIG_TEXT);
            $servers = unserialize($serversString);
            self::cacheStaticServersConfig($servers);
        }

        if (PRODUCTION) {
            if (ENGLISH_VERSION) {
                $servers = array(
                    's.qiped.com' => array('active' => 1, 'weight' => 1, 'interval' => 1000),
                );
            }
        }

        if (!is_array($servers)) {
            $servers = array();
        }

        $result = array();
        if ($activeOnly) {
            foreach ($servers as $server => $data) {
                if ($data['active'] && $data['weight']) {
                    $result[$server] = $data;
                }
            }
        } else {
            $result = $servers;
        }

        return $result;
    }

    /**
     * Ðàçìíîæàåò êîíôèã ñåðâåðîâ ñòàòèêè ïî ñêðèïòîâûì. Ðàáîòàåò òîëüåî íà zz.
     */
    public static function deployStaticServersConfig()
    {
        if (!PRODUCTION || !Base_Service_Common::isControlServer()) {
            return false;
        }
        $ssh = new Base_Service_Ssh();
        $serversList = Base_Service_Common::getScriptServers(
            true,  /* $upOnly */
            false, /* $skipMemcache */
            false, /* $includeTestLangServer */
            false, /* $includeControlServer */
            true   /* $includeVirtualStages */
        );
        $count = 0;
        foreach ($serversList as $serverInfo) {
            try {
                $ssh->connect($serverInfo['ip']);
                $ssh->keylogin('embria', './' . Base_Service_Ssh::PUBLIC_KEY_FILE, './' . Base_Service_Ssh::PRIVATE_KEY_FILE);
                $ssh->uploadFile(self::STATIC_SERVERS_CONFIG_TEXT, $ssh->getRealPath() . '/' . self::STATIC_SERVERS_CONFIG_TEXT);
                $count++;
            } catch (Exception $e) {
                continue;
            }
        }
        return $count;
    }

    public static function deployScriptServersConfig()
    {
        if (!Base_Service_Common::isControlServer()) {
            return false;
        }
        $ssh = new Base_Service_Ssh();
        $serversList = Base_Service_Common::getScriptServers2(
            true,  /* $upOnly */
            false, /* $skipMemcache */
            false, /* $includeTestLangServer */
            false, /* $includeControlServer */
            true,  /* $includeVirtualStages */
            true   /* $forceControlsExclude */
        );
        $count = 0;
        foreach ($serversList as $serverInfo) {
            try {
                $ssh->connect($serverInfo['ip']);
                $ssh->keylogin('embria', './' . Base_Service_Ssh::PUBLIC_KEY_FILE, './' . Base_Service_Ssh::PRIVATE_KEY_FILE);
                $ssh->uploadFile(self::SCRIPT_SERVERS_CONFIG_TEXT, $ssh->getRealPath() . '/' . self::SCRIPT_SERVERS_CONFIG_TEXT);
                $ssh->uploadFile(self::VIRTSTAGE_SERVERS_CONFIG_TEXT, $ssh->getRealPath() . '/' . self::VIRTSTAGE_SERVERS_CONFIG_TEXT);
                $count++;
            } catch (Exception $e) {
                continue;
            }
        }
        return $count;
    }

    /**
     * Ïîëó÷àåì âíåøíèé IP òåêóùåãî ñåðâåðà
     *
     * @return string
     */
    public static function getServerIp()
    {
        $serverIp = $_SERVER['SERVER_ADDR'];
        $servers = self::getScriptServers();

        if (isset($servers[$serverIp])) {
            return $servers[$serverIp]['extIp'];
        } else {
            return 'fotostrana.ru';
        }
    }

    public static function getSecurityHash($text)
    {
        return Utf::substr(Utf::preg_replace('/[^0-9]/', '', md5($text . self::SALT)), 0, 8);
    }

    public static function traceSpy($_class = null, $depth = null)
    {
        $spy = array();
        $trace = debug_backtrace(false);
        array_shift($trace);
        array_shift($trace);
        $count = 0;
        foreach ($trace as $row) {
            if ($_class !== null && isset($row["class"]) && (Utf::strpos($row["class"], $_class) !== FALSE)) {
                continue;
            }
            $spy[] = (isset($row["class"]) ? $row["class"] : "") . "." . $row["function"];
            if ($depth !== null && ++$count > $depth) {
                break;
            }
        }
        return !empty($spy) ? implode(' <= ', $spy) : '';
    }

    public static function isStage($checkForIp = true)
    {
        return (!$checkForIp || Base_Service_Common::isOurIp())
            && isset($_SERVER['HTTP_HOST'])
            && ($_SERVER['HTTP_HOST'] == 'stage.fotostrana.ru' || $_SERVER['HTTP_HOST'] == 'stage3.fotostrana.ru');
    }

    // warning: this function is under active development
    public static function getProjectTitle($form = 0, $user = false, $domain = false)
    {
        if ($domain) {
            $project = Base_Project_Manager::getProject($domain);
        } else {
            $project = $user ? $user->getNativeProject() : Base_Project_Manager::getProject();
        }

        return $project->getTitle($form);
    }

    // warning: this function is under active development
    /**
     *
     * @param Base_Model_User $user
     * @param boolean $overrideDomain
     * @return Base_Mailer_NewCommon
     */
    public static function getUserMailer($user, $overrideDomain = false)
    {
        if (!$user) {
            $project = Base_Project_Manager::getProject();
        } else {
            $project = $user->getNativeProject();
        }

        return $overrideDomain ?
            Base_Project_Manager::getProject($overrideDomain)->getDefaultMailer($user) :
            $project->getDefaultMailer($user);
    }

    public static function getPetMailer($user)
    {
        if (!$user) {
            $project = Base_Project_Manager::getProject();
        } else {
            $project = $user->getNativeProject();
        }

        return $project->getPetMailer($user);
    }


    public static function getCurrencyTitle($full = false)
    {
        return !$full ? _('ÔÌ') : _('ÔîòîÌàíè');
    }


    /**
     * Ïðîâåðÿåò, ïðèø¸ë ëè êëèåíò íà ýòó ñòðàíèöó ñ äð ñòðàíèöû ýòîãî æå ñàéòà.
     * Åñëè ðåô ïóñòîé (þçåð òóïî âáèë ëèíê â áðàóçåð)
     * èëè äîìåí â ðåôå íå ñîîòâåòñòâóåò äîìåíó ñåðâà - âîçâðàùàåò ôîëñ
     * ïðîâåðêà ïî äîìåíó 2ãî óðîâíÿ
     */
    public static function checkReferer()
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
        if (!$referer) {
            return false;
        }
        Utf::preg_match('/[^\.\/]+\.[^\.\/]+$/', $_SERVER['HTTP_HOST'], $serverDomain); // èçâëåêàåì äâå ïîñëåäíèå ÷àñòè èìåíè õîñòà
        Utf::preg_match('/[^\.\/]+\.[^\.\/]+$/', parse_url($referer, PHP_URL_HOST), $refDomain);
        return $refDomain[0] == $serverDomain[0];
    }

    public static function setNeedProjectSync($val = true)
    {
        self::$needProjectSync = $val;
    }

    public static function checkNeedProjectSync()
    {
        return !!self::$needProjectSync;
    }

    public static function doSyncHttpRequest()
    {
        $projectGlobalConfig = Base_Application::getInstance()->config['project'];
        if (empty($projectGlobalConfig['db_sync']['hosts'])) {
            return false;
        }

        $timeout = (int)ini_get('max_execution_time');
        $opts = array(
            'returnCode' => true,
            'timeout' => $timeout ? $timeout : 5,
            'header' => array('Cookie: ' . $_SERVER['HTTP_COOKIE'])
        );
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $opts['auth'] = $_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW'];
        }
        $params = $_POST;
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $file) {
                $params[$key] = '@' . $file['tmp_name'];
            }
        }
        $params['admin_hash'] = Base_Controller_Admin::generateAdminHash();

        foreach ($projectGlobalConfig['db_sync']['hosts'] as $host) {
            $url = 'http://' . $host . $_SERVER['REQUEST_URI'];
            $res = Service_Apache::postabs($url, $params, $opts);
            if (!isset($res[1]) || $res[1] != 200 /* OK */ && $res[1] != 302 /* REDIRECT */) {
                trigger_error('Db sync failed. URL = ' . $url . '; Response code: ' . $res[1], E_USER_WARNING);
            }
        }
        return true;
    }

    public static function isControlServer()
    {
        return !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == CONTROL_SERVER_PATH;
    }

    /**
     * @static
     * @param string $email
     * @return array|null
     */
    public static function getEmailInfoByEmail($email)
    {
        $link = null;
        $email = Utf::strtolower($email);
        if (Utf::strpos($email, '@mail.ru') || Utf::strpos($email, '@bk.ru') || Utf::strpos($email, '@inbox.ru') || Utf::strpos($email, '@list.ru')) {
            $link = array('title' => "mail.ru", 'url' => "http://e.mail.ru/cgi-bin/msglist");
        } elseif (Utf::strpos($email, '@gmail.com') || Utf::strpos($email, '@googlemail.com')) {
            $link = array('title' => "gmail.com", 'url' => "http://gmail.com");
        } elseif (Utf::strpos($email, '@yandex.ru') || Utf::strpos($email, '@ya.ru')) {
            $link = array('title' => "mail.yandex.ru", 'url' => "http://mail.yandex.ru");
        } elseif (Utf::strpos($email, '@yandex.com')) {
            $link = array('title' => "mail.yandex.com", 'url' => "http://mail.yandex.com");
        } elseif (Utf::strpos($email, '@rambler.ru') || Utf::strpos($email, '@lenta.ru') || Utf::strpos($email, '@myrambler.ru') || Utf::strpos($email, '@autorambler.ru') || Utf::strpos($email, '@ro.ru') || Utf::strpos($email, '@r0.ru')) {
            $link = array('title' => "rambler.ru", 'url' => "http://rambler.ru");
        } elseif (Utf::strpos($email, '@yahoo.com')) {
            $link = array('title' => "yahoo.com", 'url' => "https://login.yahoo.com/config/login_verify2");
        } elseif (Utf::strpos($email, '@hotmail.com')) {
            $link = array('title' => "hotmail.com", 'url' => "https://hotmail.com");
        } elseif (Utf::strpos($email, '@msn.com')) {
            $link = array('title' => "msn.com", 'url' => "https://msn.com");
        } elseif (Utf::strpos($email, '@tut.by')) {
            $link = array('title' => "tut.by", 'url' => "http://mail.tut.by");
        }
        return $link;
    }

    /**
     * @static
     * @param string $email
     * @return bool
     */
    public static function isEmailsDomainTrusted($email)
    {
        $trustedDomains = array(
            'spaces.ru',
            'pochtamt.ru',
            'e1.ru',
            'email.ru',
            'ukrpost.ua',
            'nm.ru',
            'bigmir.net',
            'sibmail.com',
            'inbox.lv',
            'e-mail.ua',
            'hot.ee',
            'pisem.net',
            'hotbox.ru',
            'gmx.de',
            'ua.fm',
            'narod.ru',
            'qip.ru',
            'meta.ua',
            'yandex.by',
            'yandex.ua',
            'tut.by',
            'i.ua',
            'ukr.net',
            'pochta.ru',
            'mail.ru',
            'bk.ru',
            'inbox.ru',
            'list.ru',
            'gmail.com',
            'googlemail.com',
            'yandex.ru',
            'ya.ru',
            'rambler.ru',
            'yahoo.com',
            'hotmail.com',
            'msn.com',
        );
        return in_array(substr(strstr($email, '@'),1),$trustedDomains);
    }



    /**
     * Ïîëó÷åíèå id áàííåðà, ïî êîòîðîìó íà ôîòîñòðàíó ïåðåøåë þçåð
     * @return int
     */
    public static function getElephantBannerId()
    {
        return Service_Base::getCookie('elephant_bid');
    }

    /**
     * Ñîõðàíåíèå id áàííåðà â êóêó íà 1 äåíü
     * @param int $bannerId
     */
    public static function setElephantBannerId($bannerId)
    {
        Service_Base::setCookie('elephant_bid', (int) $bannerId, 1);
    }

    public static function setRefIdCookie($refId, $subId='')
    {
        $days = 7;
        Service_Base::setCookie('ref_id', (int) $refId, $days, '/', true, true);
        Service_Base::setCookie('sub_id', (string) $subId, $days, '/', true, true);
    }

    public static function isOurMenu()
    {
        $module = Base_Context::getInstance()->getRequest()->getModuleName();

        switch ($module) {
            case "Staff":
            case "fsStats":
                return true;
            default:
                return false;
        }

//        if (self::isOurIp() && isset($_COOKIE['our_menu']) && !empty($_COOKIE['our_menu'])) {
//            return true;
//        }
    }

    public static function isAccessStaff()
    {
        $user = Base_Context::getInstance()->getUser();

        if (self::isOurMenu() && $user) {
            $staffDao = new Staff_Dao_Base();
            return (bool) $staffDao->getEmployeeByFsId($user->getId());
        }

        return $user && self::isOurPerson();
    }

    public static function isExternalReferer($referer)
    {
        return (
            $referer
            && ($domain = Base_Project_Manager::getProject()->getDomain())
            && (strpos($referer, '://' . $domain . '/') === false)
            && (strpos($referer, '.' . $domain . '/') === false)
            && ((strpos($referer, '.fsimg.ru/')  === false)
                || (strpos($referer, '.fsimg.ru/elephant') !== false)
            )
        );
    }

    public static function logWrongRedirect($redirectUrl, $type = 'full')
    {
        $fromParams = array('eRf', 'fromHeader', 'h', 'fromServiceBlock', 'fromPeopleBlock', 'fromServicePage', 'fp', 'fromBubble', 'from');

        if ($sourceFrom = array_intersect($fromParams, array_keys($_GET))) {
            $redirect = parse_url($redirectUrl);
            $redirectGet = array();
            if (isset($redirect['query'])) {
                parse_str($redirect['query'], $redirectGet);
            }

            if ($test = array_diff($sourceFrom, array_keys($redirectGet))) {
                Base_Service_Log::log('internal_traffic_redirects', array($_SERVER['REQUEST_URI'], $redirectUrl, $type));
            }
        }
    }
}