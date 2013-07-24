<?php

class Base_Dao_User
{
    /*
     * Source constants moved to Base_Service_UserSource
     */
    const TABLE_USER_SETTINGS = 'user_settings';
    const MC_USER_SETTINGS = 'userSettings:';
    const MC_USER_DELETE_REQUEST_TIME = 'userRequestDelTime:';
    const MC_USER_INTERESTPEOPLE = 'userInterestPeople:';

    const LKEY_PREVIOUS_TIME_IN = 'userOldTimeIn';

    const SHOW_STATUS_DEFAULT = 0;
    const SHOW_STATUS_NODATING = 1;
    const SHOW_STATUS_NODATING_IN_PEOPLE = 2;

    const LASTNAME_STATUS_SHOW = 0;
    const LASTNAME_STATUS_HIDE = 1;
    
    private static $requestLog = array();
    private static $requestHashes = array();
    private static $requestLoaded = array();
    private static $requestId = 0;
    private static $userToRequest = array();

//    const USER_SOURCE_DATING = 633;
//    const USER_SOURCE_LETITBIT = 803;
//    const USER_SOURCE_TMP_VK = 753;
//    const USER_SOURCE_MAILRU = 644;
    
    const DELETE_HISTORY_COME_BACK = -1; // reason в user_delete_history когда юзер возвращается на сайт (анкета восстанавливается)

    /**
     * Get the site population count
     *
     * @return int
     */
    public static function getUsersCount()
    {
        return Base_Service_Memcache::get('countUsersMain', __METHOD__);
    }

    /**
     * Get the site population count without doubt
     * @see CronController::saveUsersCount()
     * @return int
     */
    public static function getUsersCountForSure()
    {
        return self::getUsersCount() ?: Base_Service_Lemon::get(__METHOD__, 'persistent_all_users_count', 1);
    }

    /**
    * получить ков-ло пользователей online
    */
    public static function getUsersOnlineCount()
    {
        return Base_Service_Memcache::get('countUsersOnline', __METHOD__);
    }

    /**
    * обновить кол-во пользователей online
    */
    public static function updateUsersOnlineCount()
    {
        $db = Base_Context::getInstance()->getDbConnection();
        $count = $db->fetchOne('SELECT COUNT(*) FROM auto_online', __METHOD__);
        Base_Service_Memcache::set('countUsersOnline', $count, 432000);
    }

    /**
     * Получаем пользователя по имени страницы
     *
     * @param string $pageName
     * @return array
     */
    public static function getUserByPageName($pageName)
    {
        if (!$pageName) {
            return ;
        }

        if (in_array($pageName, Db_User::$loginStopWords)) {
            return false;
        }

        $key = 'userPage:'.$pageName;
        $userId = Base_Service_Memcache::get($key, __METHOD__);
        if (!$userId) {
            $db = Base_Context::getInstance()->getDbConnection();
            $user = $db->fetchRow($db->select()->from('user')->where('user_pagename = ?', $pageName), __METHOD__);

            if ($user) {
                Base_Service_Memcache::set($key, $user['user_id'], 86400);
                return $user;
            }

            $userNew = $db->fetchRow($db->select()->from('user_new')->where('user_pagename = ?', $pageName), __METHOD__);

            if ($userNew) {
                Base_Service_Memcache::set($key, $userNew['user_id'], 86400);
                return $userNew;
            }

            return false;
        }

        return self::getUserById($userId);
    }

    /**
     * Загружаем пользователя по ID. Если пользователя нет - вовращает NULL.
     *
     * @param int $userId
     * @return Base_Model_User
     */
    public static function getFullUserById($userId)
    {
    	if (Db_User::isShortUserEnabled()) {
    		self::preloadAllUsersWithUser($userId);
    	}	
        $userData = Db_User::getMemcachedUser((int)$userId);
        return $userData && isset($userData['user_id']) && $userData['user_id'] > 0 ? new Base_Model_User($userData) : null;
    }

        /**
     * Загружаем пользователя по ID. Если пользователя нет - вовращает NULL.
     *
     * @param int $userId
     * @return Base_Model_UserShort
     */
    public static function getShortUserById($userId)
    {
    	if (!Db_User::isShortUserEnabled()) {
    		return self::getFullUserById($userId);
    	}
    	self::logGetRequest(array($userId));
        $userData = Db_User::getMemcachedShortUser((int)$userId);
        return $userData && isset($userData['user_id']) && $userData['user_id'] > 0 ? new Base_Model_UserShort($userData) : null;
    }
    
    public static function getUserById($userId) {
    	if (!Db_User::isShortUserEnabled()) {
    		return self::getFullUserById($userId);
    	}
    	return self::getShortUserById($userId);
    }
    
    
    
	/**
     * Возвращает список укороченных моделек пользователей.
     * @static
     * @param array $userIds иды пользователей
     * @param bool $saveSort флаг сохранения порядка пользователей
     *
     * @return array|Base_Model_User[]
     */
    public static function getShortUsersByIds(array $userIds, $saveSort = false)
    {
    	if (!Db_User::isShortUserEnabled()) {
    		return self::getFullUsersByIds($userIds, $saveSort);
    	}
    	self::logGetRequest($userIds);
    	
        $users = array();
        $usersData = Db_User::getMemcachedShortUser($userIds);

        if ($saveSort) {
            foreach ($userIds as $userId) {
                if (!empty($usersData[$userId]['user_id'])) {
                    $users[$userId] = new Base_Model_UserShort($usersData[$userId]);
                }
            }
        } else {
            foreach ($usersData as $k => $data) {
                if (!empty($data['user_id'])) {
                    $users[$k] = new Base_Model_UserShort($data);
                }
            }
        }

        return $users;
    }
    
    public static function getUsersByIds(array $userIds, $saveSort = false)
    {
    	if (!Db_User::isShortUserEnabled()) {
    		return self::getFullUsersByIds($userIds, $saveSort);
    	}
    	return self::getShortUsersByIds($userIds, $saveSort);
    }
    
    /**
     * Возвращает список моделек пользователей.
     * @static
     * @param array $userIds иды пользователей
     * @param bool $saveSort флаг сохранения порядка пользователей
     *
     * @return array|Base_Model_User[]
     */
    public static function getFullUsersByIds(array $userIds, $saveSort = false)
    {
    	/*
    	$trace = debug_backtrace(); $trace = $trace[1];
    	$report = isset($trace['class']) 
    		? $trace['class']."::".$trace['function'] ." // line: ".$trace['line']
    		: $trace['file'] ." // " . $trace['line'];
    	Base_Service_Profiler_Log::dump("guM >> " . $report);
    	*/
    	$users = array();
        $usersData = Db_User::getMemcachedUser($userIds);

        if ($saveSort) {
            foreach ($userIds as $userId) {
                if (!empty($usersData[$userId]['user_id'])) {
                    $users[$userId] = new Base_Model_User($usersData[$userId]);
                }
            }
        } else {
            foreach ($usersData as $k => $data) {
                if (!empty($data['user_id'])) {
                    $users[$k] = new Base_Model_User($data);
                }
            }
        }

        return $users;
    }

    public static function getAdultUsersByIds(array $userIds)
    {
        $users = self::getShortUsersByIds($userIds);
        foreach ($users as $userId => $user) {
            if (!$user->isAdult() && $user->getAge()) {
                unset($users[$userId]);
            }
        }
        return $users;
    }

    /**
     * @return Base_Model_User
     */
    public static function getUserByLoginPass($login, $password)
    {
        if (!$login || $login == Db_User::DEFAULT_EMAIL) {
            return null;
        }
        $passHash = Base_Service_User::getPasswordHash($password);
        $userData = null;
        $db = Base_Context::getInstance()->getDbConnection();
        foreach (array('user', 'user_new') as $table) {
            $select = $db->select()->from($table)->where('user_email = ?', $login);
            $users = $db->fetchAll($select, __METHOD__);
            foreach ($users as $user) {
                if (isset($user['password_hash']) && $user['password_hash']) {
                    if ($user['password_hash'] == $passHash) {
                        $userData = $user;
                    }
                    break;
                } elseif (isset($user['user_password']) && $user['user_password'] == $password) {
                    $userData = $user;
                    break;
                }
            }
        }
        return $userData ? new Base_Model_User($userData) : null;
    }

    /**
     * @return Base_Model_User
     */
    public static function getUserByLoginPassHash($login, $passwordHash)
    {
        if (!$login || $login == Db_User::DEFAULT_EMAIL) {
            return null;
        }
        $userData = null;
        $db = Base_Context::getInstance()->getDbConnection();
        foreach (array('user', 'user_new') as $table) {
            $select = $db->select()->from($table)->where('user_email = ?', $login);
            $users = $db->fetchAll($select, __METHOD__);
            foreach ($users as $user) {
                if (isset($user['password_hash']) && $user['password_hash'] == $passwordHash) {
                    $userData = $user;
                    break;
                }
            }
        }
        return $userData ? new Base_Model_User($userData) : null;
    }

    /**
     * Получение пользователя по номеру телефона и паролю (для авторизации по номеру телефону).
     *
     * @static
     * @param string $phone
     * @param string $passwordHash
     * @return Base_Model_User|null
     */
    public static function getUserByPhonePassHash($phone, $passwordHash)
    {
        if (!$phone) {
            return null;
        }

        $userData = null;

        $db = Base_Context::getInstance()->getDbConnection();
        foreach (array('user', 'user_new') as $table) {
            $select = $db->select()->from($table)->where('user_phone = ?', $phone);
            $users = $db->fetchAll($select, __METHOD__);
            foreach ($users as $user) {
                if (isset($user['password_hash']) && $user['password_hash'] == $passwordHash) {
                    $userData = $user;
                    break;
                }
            }
        }
        return $userData ? new Base_Model_User($userData) : null;
    }

    // получение пользователя по номеру телефона
    public static function getUserByPhone($phoneNumber, $approvedOnly = true)
    {
        if (!$phoneNumber) {
            return null;
        }

        $userData = null;

        $db = Base_Context::getInstance()->getDbConnection();
        foreach (array('user', 'user_new') as $table) {
            $where = $approvedOnly ? 'user_phone = ? AND user_phone_approved = 1' : 'user_phone = ?';
            $select = $db->select()->from($table)->where($where, $phoneNumber);
            $users = $db->fetchAll($select, __METHOD__);
            foreach ($users as $user) {
                if (isset($user['user_id'])) {
                    $userData = $user;
                    break;
                }
            }
        }

        return $userData ? new Base_Model_User($userData) : null;
    }

    public static function updateLastnameExternal($userId, $lastname, $gender=null)
    {
        $user = self::getUserById($userId);
        if($user && $user->getLastName()=='' && (!$gender || $user->getGender()==$gender)) {
            $update = array("user_lastname"=>$lastname, "user_lastname_show"=>Base_Dao_User::LASTNAME_STATUS_HIDE);
            self::updateUser($user->getId(), $update);

            $stats = new Base_Service_Counter_Social();
            $stats->increment($user, "socialsearch_lastnameadd");

            return true;
        }
        return false;
    }

    /**
     * @return Base_Model_User
     */
    public static function getUserByLogin($login)
    {
        if (!$login || $login == Db_User::DEFAULT_EMAIL) {
            return null;
        }
        $userData = null;
        $db = Base_Context::getInstance()->getDbConnection();
        foreach (array('user', 'user_new') as $table) {
            $select = $db->select()->from($table)->where('user_email = ?', $login);
            $users = $db->fetchAll($select, __METHOD__);
            foreach ($users as $user) {
                if (isset($user['user_id'])) {
                    $userData = $user;
                    break;
                }
            }
            if($userData) {
                break;
            }
        }
        return $userData ? new Base_Model_User($userData) : null;
    }
    
    /**
    * получить пользователей по почтам
    * $fullLoad - получить модели или просто список userId
    */
    public static function getUsersByEmails($emails, $fullLoad = true)
    {
        if(!$emails) {
            return null;
        }
        $searchEmails = array();
        foreach ($emails as $email) {
            if($email && $email != Db_User::DEFAULT_EMAIL) {
                if (!is_object($email)) {
                    $email = (string) $email;
                }
                
                $searchEmails[$email] = $email;
            }
        }
        if(!$searchEmails) {
            return null;
        }
        $users = array();
        $db = Base_Context::getInstance()->getDbConnection();
        foreach (array('user', 'user_new') as $table) {
            if(!$searchEmails) {
                break;
            }
            $sql = $fullLoad ? $db->select()->from($table) : $db->select()->from($table, array('user_email', 'user_id'));
            $sql->where('user_email IN (?)', $searchEmails);
            if($records = $db->fetchAssoc($sql, __METHOD__)) {
                //$users = array_merge($users, $records);
                foreach ($records as $id => $record) {
                    $users[$id] = $record;
                    unset($searchEmails[$record['user_email']]);
                }
            }
        }
        return !$users ? NULL : ($fullLoad ? Base_Dao_User::makeUserModelsFromArray($users) : $users);
    }
    
    public static function addDeleteRequest($userId, $reason = '')
    {
        $user = Base_Dao_User::getUserById($userId);
        if (!$user) {
            return false;
        }        

        $db = Base_Context::getInstance()->getDbConnection();
        $insert = array(
            'user_id' => (int) $userId
        );
        if ($reason && is_string($reason)) {
            $insert['reason'] = $reason;
        }
        try {
            $db->insert('user_delete_request', $insert, __METHOD__);
            
            $user->getNativeProject()->getStatisticClient()->increment($user, 'delete_request', 1);
    
            // @analytics stats
            $analytics = new Base_Service_Counter_Analytics();
            $analytics->increment($user, 'delete_request');
    
            if (Support_Service_Zendesk::isUserRequest($user->getId())) {
                $analytics->increment($user, 'zd_delete_requests');
            }

            // если юзер снова удаляется, удаляем из трекинга
            Support_Dao_NotDeletedStats::deleteRequest(Support_Dao_NotDeletedStats::TABLE_C3, $user->getId());
        } catch (Exception $e) {
            return false;
        }

        Base_Service_Lemon2::set(__METHOD__, 'preDeleteSpamSettings', $userId, Base_Service_Spam::getSpamSettings($user), 30 * 24 * 60 * 60);
        Base_Service_Spam::unsubscribeAll($user);

        Return_Dao_Registry::deleteUserById($userId);
        
        // скроем юзера из поиска
        Search_Dao_Base::hideUserFromSearch($user->getId(), Search_Dao_Base::getUserSex($user), true);

        // удалим из фоторейтинга
        Rating_Service_Base::getInstance()->flushUser($user->getId());
        
        //Удаляем старый ключ (если он вообще был), новый поднимется сам, когда будет нужен
        Base_Service_Memcache::delete(self::MC_USER_DELETE_REQUEST_TIME . $userId);
        return true;
    }

    public static function cancelDeleteRequest($userId)
    {
        $db = Base_Context::getInstance()->getDbConnection();
        Base_Service_Memcache::delete(self::MC_USER_DELETE_REQUEST_TIME . $userId);
        $res = $db->delete('user_delete_request', $db->qq('user_id = ?', $userId), __METHOD__);

        $user = Base_Dao_User::getUserById($userId);

        $oldSpamSettings = Base_Service_Lemon2::get(__METHOD__, 'preDeleteSpamSettings', $userId);
        if ($oldSpamSettings !== false) {
            Base_Service_Spam::setSpamSettings($user, $oldSpamSettings);
            $user = Base_Dao_User::getUserById($userId); //TODO: костыль чтобы обновить юзера, потому что предыдущий метод его изменяет
        }
        Base_Service_Lemon2::delete(__METHOD__, 'preDeleteSpamSettings', $userId);

        Return_Dao_Registry::addUser($user);
        
        // вернем юзера в поиск
        Base_Service_UserSearch::updateUserInSearch($user->getId());

        // ставим на трекинг
        $notifySent = Base_Service_Lemon2::get(__METHOD__, Userinfo_Controller_Room::USER_REMOVE_FRIENDS_NOTIFY_KEY, $user->getId());
        if ($notifySent) {
            Support_Dao_NotDeletedStats::addRequest(Support_Dao_NotDeletedStats::TABLE_C3, $user->getId());
            Base_Service_Lemon2::delete(__METHOD__, Userinfo_Controller_Room::USER_REMOVE_FRIENDS_NOTIFY_KEY, $user->getId());
        }

        /**
         * @var $newsfeedInterface Newsfeed_Interface_Base
         */
        $newsfeedInterface = Base_Interface_Factory::get('Newsfeed');
        $newsfeedInterface->deleteEvent($user->getId(), Newsfeed_Dao_Base::EVENT_FRIEND_DELETED, $user->getId());

        $stats = new Base_Service_Counter_Main();
        $stats->increment($user, 'delete_request_break');

        return $res;
    }

    public static function getDeleteRequestTime($userId)
    {
        $deletionTime = Base_Service_Memcache::get(self::MC_USER_DELETE_REQUEST_TIME . $userId, __METHOD__);
        $deletionTimeCacheTtl = 86400;

        if ($deletionTime === false) {
            $db = Base_Context::getInstance()->getDbConnection();
            $select = $db->select()
                ->from('user_delete_request', array('UNIX_TIMESTAMP(request_time)'))
                ->where('user_id = ?', $userId);
            $deletionTime = $db->fetchOne($select, __METHOD__); 
            Base_Service_Memcache::set(self::MC_USER_DELETE_REQUEST_TIME . $userId, $deletionTime, $deletionTimeCacheTtl);
        }
        return $deletionTime;
    }

    public static function getOnlineUserIds($userIds = array())
    {
        if (!is_array($userIds)) {
            $userIds = array($userIds);
        }

        if (empty($userIds)) {
            return array();
        }
        $db = Base_Context::getInstance()->getDbConnection();
        $query = $db->select()->from('auto_online', 'user_id')->where('user_id IN(?)', $userIds);
        return $db->fetchCol($query, __METHOD__);
    }

    /**
     * Обновляет данные в записи в таблице user/user_new и в мемкеше. Обновляет юзера в Base_Context,
     * если userId соответсвует залогиненному юзеру.
     *
     * @static
     * @param $userId
     * @param array $update
     * @return boolean
     */
    public static function updateUser($userId, array $update /* field => value */)
    {
        if (array_key_exists('user_email_approved', $update) && !$update['user_email_approved']) {
            Base_Service_Log::log('unapprove', array($userId, "\n".implode("\n", Base_Service_Log::getTrace(10))));
        }
        
        if (array_key_exists('user_name', $update) && Utf::stripos($update['user_name'], ' ',1)!==false) {
            Base_Service_Log::log('name_surname', array($userId, "\n".implode("\n", Base_Service_Log::getTrace(10))));
        }

        if (isset($update['user_lastname']) && $update['user_lastname'] && Utf::isUtf($update['user_lastname'])) {
            Base_Service_Log::log('utflastname', array($userId, "\n".implode("\n", Base_Service_Log::getTrace(10))));
        }
        
        $db = Base_Context::getInstance()->getDbConnection();
        $dbUpdate = $update;
        foreach ($dbUpdate as $key => $val) {
            if ($val === null) {
                $dbUpdate[$key] = new Zend_Db_Expr('null');
            }
            // костыль для предотвращения записи "0" вместо "NULL" в БД
            else if ($key === 'user_is_hidden' && $val == 0) {
                $dbUpdate[$key] = new Zend_Db_Expr('null');
            }
        }
        $rows = $db->update(Db_User::getTb($userId), $dbUpdate, 'user_id = ' . (int)$userId,  __METHOD__);
        Db_User::updateMemcachedUser($userId, $update);

        // если это текущий юзер — обновляем модель
        $currentUser = Base_Context::getInstance()->getUser();

        if ($currentUser && $currentUser->getId() == $userId) {
            $currentUser->update($update);
        }

        // обновим информацию о пользователе в поиске
        if (Search_Dao_Base::hasDaldConfig() && Search_Dao_Base::hasUpdatebleFields(array_keys($update))) {
            Base_Service_UserSearch::updateUserInSearch($userId);
        }

        //обновим информацию о пользователе в свите
        if (isset($update['user_is_hidden'])) {
            Team_Service_Base::updateBanStatus($userId, $update['user_is_hidden']);
        }

        return $rows == 1;
    }

    public static function isPhoneRegistered($phoneNumber, $approvedOnly = true)
    {
        $db = Base_Context::getInstance()->getDbConnection();
        foreach (array('user', 'user_new') as $table) {
            $where = $approvedOnly ? 'user_phone = ? AND user_phone_approved = 1' : 'user_phone = ?';
            $select = $db->select()->from($table, 'user_id')->where($where, $phoneNumber);
            $res = $db->fetchOne($select, __METHOD__);
            if (!empty($res)) {
                return true;
            }
        }
        return false;
    }

    public static function approveUserPhone(Base_Model_User $user, $phoneNumber, $updateUserLocation = 1)
    {
        $reputation = Base_Interface_Factory::get('Reputation'); /* @var $reputation Reputation_Interface_Base */
        // Очистим репутацию если телефон не был подтвержден и юзер - гражданин
        if (!$user->isPhoneApproved() && $user->getUserClass() == Db_User::USER_CLASS_CITIZEN) {
            $reputation->deleteReputation($user->getId());
        }

        $oldClass = $user->getUserClass();

        Base_Dao_User::updateUser($user->getId(), array('user_phone' => $phoneNumber, 'user_phone_approved' => 1));
        $reputation->reputationEvent($user->getId(), Reputation_Dao_Base::EVENT_APPROVE_PHONE);

        if ($updateUserLocation) {
            // Разрешаем доступ с текущего местоположения
            $ip = Base_Service_Common::getRealIp();
            Antifraud_Service_Location::enableLocationForUser($user->getId(), $ip);
        }

        // Повышаем класс до гражданина
        Userclass_Service::updateUserClass($user, $oldClass);

        $db = Base_Context::getInstance()->getDbConnection();
        $db->insert('user_phone_approve_log', array('user_id' => $user['user_id'], 'phone' => $phoneNumber,
            'inserted' => new Zend_Db_Expr('NOW()')), __METHOD__);
        
        $user->getNativeProject()->getStatisticClient()->increment($user, 'phone_approve');
    }

    /**
     * Конвертит массив юзеров-массивов в масси юзеров-объектов
     * @param array $users
     */
    public static function makeUserModelsFromArray(array $users)
    {
        $userModels = array();
        foreach ($users as $key => $rawUser) {
            if (!isset($rawUser['user_id'])) {
                continue;
          }
            $userModels[$key] = new Base_Model_User($rawUser);
        }

        return $userModels;
    }

    /**
     * Установка ключа в мемкеш о том, что сегодня телефон менялся
     */
    public static function setPhoneChangeLimit($idUser)
    {
        Base_Service_Memcache::set('user_change_phone:' . $idUser, 1, 86400); // ключ на сутки
    }

    /**
     * Проверка, может ли юзер менять телефон (лимит - 1 раз в день)
     */
    public static function isCanChangePhone($idUser)
    {
        return !PRODUCTION || !(Base_Service_Memcache::get('user_change_phone:' . $idUser, __METHOD__) == 1);
    }

    /**
     * Возвращает время предыдущего логина.
     * @NOTE Доступно только во время пользовательской сессии! (или 12 часов с момента логина)
     * @param int $userId
     * @return int
     */
    public static function getPreviousTimeIn($userId)
    {
        $userId = (int) $userId;
        $return = Base_Service_Lemon2::get(__METHOD__, self::LKEY_PREVIOUS_TIME_IN, $userId);
        return $return ? (int) $return : 0;
    }

    /**
     * Устанавливает/удаляет время предыдущего логина.
     * @param int $userId
     * @param int $prevLoginTime
     * @return bool
     */
    public static function setPreviousTimeIn($userId, $prevLoginTime)
    {
        $userId = (int) $userId;
        $prevLoginTime = (int) $prevLoginTime;

        if (!$prevLoginTime) {
            return Base_Service_Lemon2::delete(__METHOD__, self::LKEY_PREVIOUS_TIME_IN, $userId);
        }
        return Base_Service_Lemon2::set(__METHOD__, self::LKEY_PREVIOUS_TIME_IN, $userId, $prevLoginTime, 43200 /* 12 hours */);
    }

    /**
     * добавляет строчку в delete_history
     *
     * @param string $comment - "другая причина"
     */
    public static function addUserDeleteHistory($userId, $reason, $comment = '')
    {   
        $userId = intval($userId);
        $reason = intval($reason);
        if (!$userId || !$reason) {
            return false;
        }
        $table = 'user_delete_history';

        $db = Base_Context::getInstance()->getDbConnection();

        //IGNORE на всякий случай
        $db->writeQuery($table, "INSERT IGNORE INTO `" . $table . "` SET `user_id` = '" . $userId . "', `reason` = '" . $reason . "', `date` = NOW(), `comment` = '" . mysql_escape_string($comment) . "' ", __METHOD__);
    }

    public static function getAllProjectsTitles()
    {
        return array(
            PROJECT_TYPE_VK => 'Петы ВК',
            PROJECT_TYPE_OK => 'Петы OК',
            PROJECT_TYPE_MAILRU => 'Петы майл',
            PROJECT_TYPE_FB => 'Петы FB',
            PROJECT_TYPE_DATINGAPP_VK => 'Дейтинг ВК',
            PROJECT_TYPE_DATINGAPP_MAILRU => 'Дейтинг майл',
            PROJECT_TYPE_DATINGAPP_FB => 'Дейтинг FB',
        );
    }

    public static function getAllProjectsList()
    {
        return array(
            1 => PROJECT_TYPE_VK,
            2 => PROJECT_TYPE_MAILRU,
            4 => PROJECT_TYPE_OK,
            8 => PROJECT_TYPE_FB,
            16 => PROJECT_TYPE_DATINGAPP_VK,
            32 => PROJECT_TYPE_DATINGAPP_MAILRU,
            64 => PROJECT_TYPE_DATINGAPP_FB,
        );
    }

    public static function getUsingProjectsList($value)
    {
        $res = array();
        $project = self::getAllProjectsList();

        foreach ($project as $bitmask => $projectType) {
            if ($value & $bitmask) {
                $res[] = $projectType;
            }
        }

        return $res;
    }

    public static function addProjectsToValue($value, $projects)
    {
        $all = self::getAllProjectsList();
        $all = array_flip($all);

        foreach ($projects as $projectType) {
            $bitmask = isset($all[$projectType]) ? $all[$projectType] : 0;
            $value = $value | $bitmask;
        }

        return $value;
    }

    public static function deleteProjectsFromValue($value, $projects)
    {
        $all = self::getAllProjectsList();
        $all = array_flip($all);

        foreach ($projects as $projectType) {
            $bitmask = isset($all[$projectType]) ? $all[$projectType] : 0;
            $value = $value & (~$bitmask);
        }

        return $value;
    }

    public static function getExtIdFieldNameByProjectType($projectType)
    {
        $map = array(
            PROJECT_TYPE_VK               => 'user_vk_id',
            PROJECT_TYPE_DATINGAPP_VK     => 'user_vk_id',
            PROJECT_TYPE_MAILRU           => 'user_mailru_id',
            PROJECT_TYPE_DATINGAPP_MAILRU => 'user_mailru_id',
            PROJECT_TYPE_OK               => 'user_ok_id',
            PROJECT_TYPE_FB               => 'user_fb_id',
            PROJECT_TYPE_DATINGAPP_FB     => 'user_fb_id'
        );

        return isset($map[$projectType]) ? $map[$projectType] : false;
    }

    public static function getExtIdFieldNameByLoginType($loginType)
    {
        $map = array(
            Base_Service_Login_Factory::TYPE_VKONTAKTE     => 'user_vk_id',
            Base_Service_Login_Factory::TYPE_MAILRU        => 'user_mailru_id',
            Base_Service_Login_Factory::TYPE_ODNOKLASSNIKI => 'user_ok_id',
            Base_Service_Login_Factory::TYPE_FACEBOOK      => 'user_fb_id',
        );

        return isset($map[$loginType]) ? $map[$loginType] : false;
    }

    /**
     * Вернуть настройки уведомлений от приложений
     *
     * @param int $userId
     * @return array
     */
    public function getAppNotifySettings($userId) 
    {
        $bubbleNotifyMask = Base_Interface_Store::EXTERNAL_MASK_BUBBLE_NOTIFY;

        $user = Base_Dao_User::getUserById($userId);
        if ($user === null) {
            return array();
        }

        $skipApps = array(
            'Friends', 'Usercontact', 'Favorites', 'Walls', 'communityapp', 'fotoindex', 'supportNotifier'
        );
        $apps = array();
        if (Base_Service_Pacman::isEnabled()) {
            $skipApps = array_flip(Base_Interface_Store::getUid($skipApps));
            $notifies = Base_Service_Pacman::prepareNotifies(Base_Service_Pacman::getNotifies($userId));
            $iApp = new App_Interface_Base();
            foreach ($notifies as $appUid => $appModel) {
                if (isset($skipApps[$appUid])
                    || (isset($appModel[Base_Interface_Store::STORE_TESTING_LEVEL]) && $appModel[Base_Interface_Store::STORE_TESTING_LEVEL] != Base_Interface_Store::TESTING_LEVEL_ALL)
                ) {
                    continue;
                }
                $appName = Utf::strtolower($appModel['app_name']);
                // НЕ устранновленные не надо
                $access = $iApp->getAccess($appName, $userId);
                if (empty($access) || (isset($access['installed']) && !$access['installed'])) {
                    continue;
                }

                // Продолжаем набирать
                $apps[$appName] = array(
                    'name'    =>  $appModel['description'],
                    'notify'  =>  true,
                    'inPacman'=>  true, // Индикатор того, что приложение есть в пакмане
                    'bubbleNotify'  =>  !((bool) ((int) $access[0] & $bubbleNotifyMask)),
                );
                if (isset($appModel[Base_Interface_Store::STORE_ICO_CLASS])) {
                    $apps[$appName]['ico'] = $appModel[Base_Interface_Store::STORE_ICO_CLASS];
                }

                if(isset($appModel['mailing_enabled'])) { // вещает ли приложение по мылу?
                    $apps[$appName]['mailing_enabled'] = $appModel['mailing_enabled'];
                }
            }
            unset($iApp);
        } else {
            // Еще получим приложения, нотификации которых уже есть
            $appNotify = new App_Interface_News();
            $apps = Base_Util_Array::extract($appNotify->getAppNewsListForUser($userId, false), 'notify_uniq');
            $apps = empty($apps) ? array() : $apps;

            $appNotify = Base_Util_Array::extract(Base_Service_Notify::getNotifyByType($userId, Base_Service_Notify::NOTIFY_APP_USERNOTIFY), 'notify_uniq');
            $apps = empty($appNotify) ? $apps : array_merge($apps, $appNotify);

            $appNotify = Base_Util_Array::extract(Base_Service_Notify::getNotifyByType($userId, Base_Service_Notify::NOTIFY_APPINVITE), 'notify_uniq');
            $apps = empty($appNotify) ? $apps : array_merge($apps, $appNotify);

            $appNotify = array();
            foreach(Base_Util_Array::extract(Usercontact_Service_Base::getGameEventsNotifications($user), 'app') as $appItem) {
                if (isset($appItem['app_id'])) {
                    $appNotify[] = $appItem['app_id'];
                }
            }
            foreach(Base_Util_Array::extract(Usercontact_Service_Base::getGameRequestsNotifications($user), 'app') as $appItem) {
                if (isset($appItem['app_id'])) {
                    $appNotify[] = $appItem['app_id'];
                }
            }
            $appIds = empty($appNotify) ? $apps : array_merge($apps, $appNotify);

            $appIds = Base_Interface_Store::getAppsByUids(Base_Util_Array::intvalArray(array_unique($appIds)));

            $apps = array();
            foreach($appIds as $app) {
                $apps[$app['app_name']] = array(
                    'name'    =>  $app['description'],
                    'notify'  =>  true,
                    'inPacman'=>  true, // Индикатор того, что приложение есть в пакмане
                );
                if(isset($app['mailing_enabled'])) { // вещает ли приложение по мылу?
                    $apps[$app['app_name']]['mailing_enabled'] = $app['mailing_enabled'];
                }
            }
        }

        /* @var $appInterface App_Interface_Base */
        $appInterface = Base_Interface_Factory::get('App');
        $notifyMask = Base_Interface_Store::EXTERNAL_MASK_USERNOTIFY;

        
        $userApps = $appInterface->getUserApps($userId, true, true);
        $access = $appInterface->getAccessManyApps($userId, array_keys($userApps), false);

        foreach ($userApps as $appId => $app) {
            if (!isset($app['app_name']) || isset($skipApps[$appId])) {
                continue;
            }
            $appName = Utf::strtolower($app['app_name']);
            // Если есть имя приложения и уровень доступа для всех, или нету поля testing_level (для уже инсталированных приложений)
            if (!isset($apps[$appName]) && (!isset($app['testing_level']) || $app['testing_level'] == Base_Interface_Store::TESTING_LEVEL_ALL)) {
                $apps[$appName] = array(
                    'name'    =>  $app['description'],
                    'inPacman'=>  false, // Индикатор того, что приложение есть в пакмане
                );

                if (isset($access[$appId][0])) {
                    $apps[$appName] += array(
                        'notify'  =>  ((int) $access[$appId][0] & $notifyMask) > 0,
                        'bubbleNotify'  =>  !((bool) ((int) $access[$appId][0] & $bubbleNotifyMask)),
                    );
                } else {
                    $apps[$appName] += array(
                        'notify'  =>  true,
                        'bubbleNotify'  =>  true,
                    );
                }

                if (Base_Service_Pacman::isEnabled() && isset($app[Base_Interface_Store::STORE_ICO_CLASS])) {
                    $apps[$appName]['ico'] = $app[Base_Interface_Store::STORE_ICO_CLASS];
                }

                if(isset($app['mailing_enabled'])) { // вещает ли приложение по мылу?
                    $apps[$appName]['mailing_enabled'] = $app['mailing_enabled'];
                }
            }
        }

        return $apps;

    }

    /**
     * Установить настройки уведомлений приложений
     *
     * @param int $userId
     * @param string $appName
     * @param bool $access
     * @return bool
     */
    public static function setAppNotifySettings($userId, $appName, $open = true, $deleteAllNotifys = true)
    {
        // New pacman unnotify
        if (Base_Service_Pacman::isEnabled()) {
            /*if (!$open && $deleteAllNotifys) {
                Base_Service_Pacman::removeNotifies($userId, $appName);
            }*/
            $result = Base_Service_Pacman::userNotifyStatus($userId, $appName, $open);

            // Count statistic
            $statsFlag = $open ? 'on' : 'off';
            $user = Base_Dao_User::getUserById($userId);

            $client = new Base_Service_Counter_Main();
            $client->increment($user, 'pacman_notify_settings_' . $appName . '_' . $statsFlag);
            $client->increment($user, 'pacman_notify_settings_total_apps_' . $statsFlag);

            return $result;
        }


        $notifyMask = Base_Interface_Store::EXTERNAL_MASK_USERNOTIFY;
        /** @var $appInterface App_Interface_Base */
        $appInterface = Base_Interface_Factory::get('App');
        $access = $appInterface->getAccess($appName, $userId);
        $appNewsInterface = new App_Interface_News();
        $appUid = Base_Interface_Store::getAppNumericId($appName); // Получаем айди приложения
        $newAcc = null;
        if ($open) {
            
            if (!empty($access[0])) {
                $newAcc = $access[0] | $notifyMask;
            } else {
                $newAcc = Base_Interface_Store::EXTERNAL_MASK_DEFAULT;
                foreach (Base_Interface_Store::$appMasks as $mask) {
        	        if ($mask == Base_Interface_Store::EXTERNAL_MASK_SILENT_BILLING && !Base_Interface_Store::isSilentBillingAllowed($appName)) {
                        continue;
                    }
                    $newAcc |= $mask;
                }
            }

            // Подписываемся на новости
            $appNewsInterface->markNotifyNewsApp($userId, $appUid);

        } else {

            $newAcc = Base_Interface_Store::EXTERNAL_MASK_DEFAULT;
            foreach (Base_Interface_Store::$appMasks as $mask) {

                if ((empty($access[0]) and $mask != $notifyMask) or (isset($access[0]) and ($access[0] & $mask) and $mask != $notifyMask)) {
                    $newAcc |= $mask;
                }

            }

            // Отписываем от новостей
            $appNewsInterface->markUnnotifyNewsApp($userId, $appUid);

            // Обнуляем счетчики для меню знакомств
            Base_Service_Notify::subNotify($userId, Base_Service_Notify::NOTIFY_APP_COUNTER, $appUid);

            
            if ($deleteAllNotifys) { // Удаляем все нотификации от приложений

                foreach (Base_Service_Notify::getNotifyByType($userId, Base_Service_Notify::NOTIFY_GAME_EVENTS) as $notify) { // Получаем все нотификации

                    if ($notify['udata'][Base_Service_Notify::UDATA_REF_ID] == $appName) {
                        Base_Service_Notify::subNotify($userId, Base_Service_Notify::NOTIFY_GAME_EVENTS, $notify['notify_uniq']);
                    }

                }

                foreach (Base_Service_Notify::getNotifyByType($userId, Base_Service_Notify::NOTIFY_APP_USERNOTIFY) as $notify) { // нотификации от приложений с текстом

                    if ($notify['notify_uniq'] == $appUid) {
                        Base_Service_Notify::remove($userId, Base_Service_Notify::NOTIFY_APP_USERNOTIFY, $appUid);
                    }

                }

                foreach (Base_Service_Notify::getNotifyByType($userId, Base_Service_Notify::NOTIFY_APPINVITE) as $notify) { // приглашения в приложения

                    if ($notify['notify_uniq'] == $appUid) {

                        Base_Service_Notify::subNotify($userId, Base_Service_Notify::NOTIFY_APPINVITE, $appUid);

                        // удалим вип инвайт
                        if (App_Service_Vip::isVipApp(Base_Interface_Store::getAppByUid($appUid))) {
                            App_Service_Vip::deleteInvite($userId, $appUid);
                        }
                    }

                }

                // Получаем все реквесты
                foreach (Base_Service_Notify::getNotifyByType($userId, Base_Service_Notify::NOTIFY_GAME_REQUESTS) as $notify) {
                    // Удаляем реквесты для данного приложения
                    if ($notify['udata'][Base_Service_Notify::UDATA_REF_ID] == $appName) {
                        Base_Service_Notify::subNotify($userId, Base_Service_Notify::NOTIFY_GAME_REQUESTS, $notify['notify_uniq']);
                    }
                }
            }

        }


        $user = Base_Dao_User::getUserById($userId);

        $client = new Base_Service_Counter_Main();
        $client->increment($user, 'pacman_notify_settings_'.$appName.'_'.($open?'on':'off'));

        $client->increment($user, 'pacman_notify_settings_total_apps_'.($open?'on':'off'));
        return $appInterface->setAccess($appName, $userId, $newAcc, false);

    }

    /**
     * Возвращает массив айдишников юзеров по переданному массиву тел. номеров.
     * Используется для поиска друзей на мобильном клиенте фотостраны.
     * 
     * @static
     * @param $phones массив номеров телефонов
     * @return array айдишники юзеров или пустой массив
     */
    public static function getUsersByPhones($phones)
    {
        if(empty($phones)) {
            return array();
        }

        // проверим корректность номеров
        $validPhones = array();
        foreach ($phones as $phone) {
            if (Utf::preg_match('/^[0-9]{7,15}$/', $phone)) {
                $validPhones[] = $phone;
            }
        }

        $users = array();
        $db = Base_Context::getInstance()->getDbConnection();

        foreach (array('user', 'user_new') as $table) {
            if(empty($validPhones)) {
                break;
            }
            $sql = $db->select()->from($table, array('user_phone', 'user_id'));
            $sql->where('user_phone IN (?)', $validPhones);
            if ($records = $db->fetchAll($sql, __METHOD__)) {
                $users = array_merge($users, $records);
                foreach ($records as $record) {
                    unset($validPhones[$record['user_phone']]);
                }
            }
        }
        
        return $users;
    }

    const FAILED_LOGINS_COUNT = 'failedLoginsCount:';
    const FAILED_LOGINS_MAX_COUNT = 3;

    public static function resetFailedLoginsCount($email)
    {
        Base_Service_Memcache::delete(self::FAILED_LOGINS_COUNT.crc32($email));
    }
    
    public static function getFailedLoginsCount($email)
    {
        return (int)Base_Service_Memcache::get(self::FAILED_LOGINS_COUNT.crc32($email), __METHOD__);
    }

    public static function incrFailedLoginsCount($email)
    {
        $count = self::getFailedLoginsCount($email);
        if (!$count) {
            Base_Service_Memcache::set(self::FAILED_LOGINS_COUNT.crc32($email), 1, 60*15);
        } else {
            Base_Service_Memcache::increment(self::FAILED_LOGINS_COUNT.crc32($email));
        }
        return ++$count;
    }
    
    private static function logGetRequest(array $request)
    {
    	$reqs = array();
    	foreach ($request as $req) {
    		if (is_array($req) && isset($req['user_id'])) {
    			$req = $req['user_id']; // WTF???
    		}
    		if ($req && !isset($reqs[$req])){
    			$reqs[$req] = true;
    		}
    	}
    	$reqs = array_keys($reqs);
    	sort($reqs);
    	reset($reqs);
    	$request = $reqs;
    	$hash = md5(implode("//", $request));
    	if (isset(self::$requestHashes[$hash])) {return;}
    	self::$requestHashes[$hash] = 1;
    	self::$requestLog[self::$requestId] = $request;
    	foreach ($request as $userId) {
    		if (!$userId) {
    			continue;
    		}
    		if (!isset(self::$userToRequest[$userId])) {
    			self::$userToRequest[$userId] = array();
    		}
    		self::$userToRequest[$userId][] = self::$requestId;
    	}
    	self::$requestLoaded[self::$requestId] = false;
    	self::$requestId++;
    }
    
    private static function preloadAllUsersWithUser($userId) {
    	$usersToRequest = array();
    	if (is_array($userId)) {
    		foreach ($userId as $id) {
    			foreach (self::$userToRequest[$id] as $req) {
    				if (!self::$requestLoaded[self::$requestId]) {
    					$usersToRequest += self::$requestLog[$req];
    					self::$requestLoaded[self::$requestId] = true;
    				}
    			}
    		}
    	} else {
    		foreach (self::$userToRequest[$userId] as $req) {
    			if (!self::$requestLoaded[$req]) {
    				$usersToRequest += self::$requestLog[$req];
    				self::$requestLoaded[self::$requestId] = true;
    			}
    		}
    	}
    	Base_Dao_User::getFullUsersByIds($usersToRequest);
    }

}
