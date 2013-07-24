<?php

/**
 *
 * user_updated удалить после смены схемы с главной фоткой
 * user_is_hidden и user_not_activated тоже объединить
 * user_galleries вынести в user_profile
 * user_bans, user_warn - перенести куда-нибудь еще и удалить
 *
 */
class Db_User extends Db_Abstract
{
    const SAVEPROFILE_ERROR_BAD_NAME = 'bad_name';
    const SAVEPROFILE_ERROR_PHONE = 'error_phone';
    const SAVEPROFILE_ERROR_EMAIL = 'error_email';
    const SAVEPROFILE_ERROR_PASSWORD = 'error_password';

    const SAVEPROFILE_ERROR_PASSWORD_CHARS = 'error_password_chars';
    const SAVEPROFILE_ERROR_PASSWORD_MISMATCH = 'error_password_mismatch';
    const SAVEPROFILE_ERROR_OLD_PASSWORD = 'error_old_password_invalid';
    const SAVEPROFILE_ERROR_PASSWORD_PAGENAME = 'error_password_pagename';
    const SAVEPROFILE_ERROR_PASSWORD_EMPTY = 'error_passsword_empty';
    const SAVEPROFILE_ERROR_PASSWORD_SHORT = 'error_password_short';
    const SAVEPROFILE_ERROR_APPROVED_EMAIl = 'error_approved_email';


    const SAVEPROFILE_OK = 'ok';
    const DEFAULT_EMAIL = 'default@fotostrana.ru';

    const MKEY_USER_ID = 'userId:';
    const MKEY_USER_SHORT_ID = 'userShortId:';
    const CACHE_TIME = 86400;

    public $table = 'user';
    static public $userIdFirst = 0;

    private static $shortModelCache = array();
    private static $shortUserEnabled = false;

    public static function isShortUserEnabled() {
    	if (!is_null(self::$shortUserEnabled)) {
    		return self::$shortUserEnabled;
    	}
    	self::$shortUserEnabled = Base_Service_Common::isOurIp();
    	//((!PRODUCTION) || (PRODUCTION && Base_Service_Common::isOurIp()));
    	return self::$shortUserEnabled;
    }

    const USER_CLASS_GUEST = 0;   // Пользователь, не подтвердивший ни телефон, ни email
    const USER_CLASS_CANDIDATE_RESIDENT = 5; // не исп.
    const USER_CLASS_RESIDENT = 10; // Пользовтаель, подтвердивший email
    const USER_CLASS_CITIZEN = 20; // Пользователь, подтвердивший телефон

    private static $instance;

    /**
     * @return Db_User
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public function checkLogin($login)
    {
        $login = Utf::strtolower($login);
        if(!Utf::preg_match('/^[a-z]{1}[a-z0-9-]{1,28}[a-z0-9]{1}$/i', $login))
            return _('В адресе присутствуют некорректные символы');

        $result = $this->db->fetchAll($this->db->select()
          ->from('user', array('user_pagename'))
          ->where('user_pagename=?', $login)
          ->limit(1), __METHOD__);
        if (!empty($result)) {
            return _('Адрес уже используется');
        }
        $result = $this->db->fetchAll($this->db->select()
          ->from('user_new', array('user_pagename'))
          ->where('user_pagename=?', $login)
          ->limit(1), __METHOD__);
        if (!empty($result)) {
            return _('Адрес уже используется');
        }

        return null;
    }

    public function checkPassword($password)
    {
        // Убираем & (из-за htmlspecialchars)
        return Utf::preg_match('/^[a-zA-Z0-9!@#$%^*()_+-]{6,20}$/i', $password);
    }

    public function savePageName($userId, $pageName)
    {
        $pageName = Utf::strtolower(Utf::trim($pageName));
        $pageName2 = strtr($pageName, '015', 'olS');
        $rows = $this->db->update($this->getTb($userId), array('user_pagename' => $pageName), Driver_Db::qq("user_id = ?", $userId), __METHOD__);
        if ($rows > 0) {
            $this->updateMemcachedUser($userId, array('user_pagename' => $pageName));
            Base_Service_Memcache::set('userPage:'.$pageName, $userId, 86400);
        }
    }

    public function getById($userId)
    {
        return $this->getMemcachedUser($userId);
    }

    /**
     * @param Base_Model_User $user
     * @param null $dependecies
     */
    public function basicDeleteUser(Base_Model_User $user, $dependecies = null)
    {
        $this->db->delete($this->getTb($user->getId()), Driver_Db::qq('user_id = ?', $user->getId()), __METHOD__);
        $this->deleteMemcachedUser($user);
    }

    /**
     * добавил удаление из user_new
     * @TODO: тут надо уже знать куда пользователь относится.
     */
    public function deleteUser($USER, $deleteReason = '', $forceDeletedLog = false)
    {
        if ((!$USER['user_id']) || (Base_Service_Common::isOurId($USER['user_id']))) {
            return ;
        }

        // логгируем, кто удалил
        $whoDelete = Base_Context::getInstance()->getUser();
        if ($whoDelete) {
            Base_Service_Log::text('user: '.$whoDelete->getId().'; deleted: '.$USER['user_id'].'; reason: '.$deleteReason, 'user_deletion_log');
        }

        $userCash = Billing_Service_Base::getRealUserCash($USER['user_id']);
        if($userCash){
            // give user's money to cb
            Billing_Service_Base::saveUserCashInfo($USER, -$userCash, Billing_Service_Base::OP_TYPE_CB, Billing_Service_Base::SERVICE_TO_CENTRAL_BANK, 'в связи с удалением пользователя', false);

        }

        // заносим юзера в табличку с удаленными
        $userDbRecord = $this->db->fetchRow($this->db->select()->from($this->getTb($USER['user_id']))->where('user_id = ?', $USER['user_id']), __METHOD__);
        if ($userDbRecord) {
            if ($forceDeletedLog || (!empty($userDbRecord['user_email']) && $userDbRecord['user_email'] != Db_User::DEFAULT_EMAIL)) {
                $userDbRecord['date_deleted'] = date('Y-m-d H:i:s');
                $userDbRecord['delete_reason'] = Utf::trim($deleteReason);
                if ($forceDeletedLog) {
                    Base_Service_Log::log('user_transfer_deleted', $userDbRecord);
                }
                try {
                    if (isset($userDbRecord['spam_settings'])) {
                        // пока не добавили колонку в user_deleted - такой вот костылек
                        unset($userDbRecord['spam_settings']);
                    }
                    unset($userDbRecord['online_mask']); // в user_deleted эта колонка не нужна
                    unset($userDbRecord['visit_mask']); // в user_deleted эта колонка не нужна
                    unset($userDbRecord['visit_time']); // в user_deleted эта колонка не нужна

                    $this->db->insert('user_deleted', $userDbRecord, __METHOD__);

                    // INSERT INTO user(user_id,user_name,user_pagename,user_phone,user_phone_approved,user_sex,user_photo_id,user_inserted,user_updated,user_email,user_password,user_email_approved,user_ip,user_id_from,user_is_hidden,user_is_volunteer,user_source,user_source_result,user_not_activated,user_country_id,user_city_id,user_birthday,user_ref_id,user_galleries,user_bans,user_warn,user_cash,user_class,time_in,time_out,user_pet_id,password_hash,user_identity_approved,user_mail,user_region_id,user_native_domain,user_show_status,user_payable,user_lastname,user_lastname_show,vip_end,user_projects_usage,user_vk_id,user_mailru_id,user_ok_id,user_fb_id,user_foto_ext,spam_settings,confirm_date,has_mobile_app,spam_mask,last_online_src) SELECT user_id,user_name,user_pagename,user_phone,user_phone_approved,user_sex,user_photo_id,user_inserted,user_updated,user_email,user_password,user_email_approved,user_ip,user_id_from,user_is_hidden,user_is_volunteer,user_source,user_source_result,user_not_activated,user_country_id,user_city_id,user_birthday,user_ref_id,user_galleries,user_bans,user_warn,user_cash,user_class,time_in,time_out,user_pet_id,password_hash,user_identity_approved,user_mail,user_region_id,user_native_domain,user_show_status,user_payable,user_lastname,user_lastname_show,vip_end,user_projects_usage,user_vk_id,user_mailru_id,user_ok_id,user_fb_id,user_foto_ext,spam_settings,confirm_date,has_mobile_app,spam_mask,last_online_src FROM user_deleted WHERE user_id = 17666272;
                } catch (Exception $e) {
                    Base_Exception::logError($e->getMessage(), $e->getTraceAsString());
                }
            }
        }

        $this->db->delete($this->getTb($USER['user_id']), Driver_Db::qq('user_id = ?', $USER['user_id']), __METHOD__);

        // remove all market offers for user
        Marketplace_Dao_MarketActive::deleteUserOffers($USER["user_id"]);

        if (isset($USER["user_pet_id"]) && is_numeric($USER["user_pet_id"])) {
            Pet_Dao_Base::deletePet($USER["user_pet_id"], $USER["user_id"]);
        }

        Profile_Dao_Base::deleteProfileInfo($USER['user_id']);

        $this->deleteMemcachedUser($USER);

        Top_Dao_Base::deleteUserFromAllTops($USER['user_id']);

        Community_Dao_Member::deleteUser($USER['user_id']);

        $moderationApp = Base_Interface_Factory::get('UserphotoModerationApp'); /* @var $moderationApp Userphoto_Interface_ModerationApp */
        $moderationApp->afterProfilePhotoDeleted($USER['user_id']);

        // delete all newsfeed events
        // @newsfeedEventDelete
        try {
            $newsfeedInterface = Base_Interface_Factory::get('Newsfeed');
            /* @var $newsfeedInterface Newsfeed_Interface_Base */
            $newsfeedInterface->deleteAllUserEvents($USER['user_id']);
        } catch (Base_Exception $e) {}

        // delete user email
        Mail_Dao_Base::deleteUser($USER);

        // delete user from antifraud tables
        Antifraud_Dao_Location::getInstance()->deleteUser($USER['user_id']);

        //TODO delete all user's comments (gallery, tlog)
        //TODO delete all user's votes
        //TODO delete from queens, etc

        //delete all user messages
        //to task ::: Messenger_Service::deleteAllUserMessages($USER['user_id']);

        // @meeting
        $meetingIface = Base_Interface_Factory::get(Meeting_Service_Base::APP_ID); /** @var $meetingIface Meeting_Interface_Base */
        $meetingIface->removeUsersFromPool($USER['user_id']);

        //Удаляем из таблицы дней рождений
        User_Dao_Birthdays::removeUserBirthday($USER);

        //удаляем из угадайки
        Guess_Dao_Base::removeUsersFromPool(array($USER['user_id']));

        $teamFace = Base_Interface_Factory::get('Team'); /** @var $teamFace Team_Interface_Base */
        $teamFace->processUserDelete($USER['user_id']);

        Spider_Service_SocialLinks::getInstance()->removeIdFromUser(Spider_Service_SocialLinks::TYPE_ID_FS, $USER['user_id']);

        // чистим приглашенные контакты
        Base_Service_Invite_Email::deleteInviteContactsByUser($USER['user_id']);
        Base_Service_Invite_Email::deleteUserInviteEmails($USER['user_id']);

        // блокируем пользователя в спайдере на год
        Fs2Invites_Service_ContactManager::blockDeletedUser($USER['user_id']);

        // удаляем из поиска
        Search_Dao_Base::hideUserFromSearch($USER['user_id'], Search_Dao_Base::getUserSex($USER));

        Return_Dao_Registry::deleteUserById($USER['user_id']);

        Base_Service_Login_Factory::removeAllExtIdsFromUser($USER['user_id']);

        Rating_Service_Base::getInstance()->flushUser($USER['user_id']);
    }

    public function deleteUserBan($userId)
    {
        if (!$userId) {
            return false;
        }
        Base_Dao_User::updateUser($userId, array('user_is_hidden' => null));

        return true;
    }

    public function updateUserSource($userId, $sourceId)
    {
        return Base_Dao_User::updateUser($userId, array('user_source' => $sourceId));
    }

    public function create($row)
    {
        if ($this->db->insert('user_new', $row, __METHOD__)) {
            if ($ret = $this->db->lastInsertId('user_new')) {
                $this->_flushCache($ret);
                return $ret;
            }
        }
        return false;
    }

    /**
     * Если передан параметр $projectId, проверяет только среди пользователей.
     */
    public static function isEmailExists($email, $projectId = null)
    {
        $db = Base_Context::getInstance()->getDbConnection();
        $select1 = $db->select()->from('user', array('user_id'))->where('user_email = ?', $email)->limit(1);

        if ($projectId !== null) {
            $select1->where('user_native_domain = ?', $projectId);
        }

        $isExists = $db->fetchOne($select1, __METHOD__);

        if (!$isExists) {
            $select2 = $db->select()->from('user_new', array('user_id'))->where('user_email = ?', $email)->limit(1);

            if ($projectId !== null) {
                $select2->where('user_native_domain = ?', $projectId);
            }
            $isExists = $db->fetchOne($select2, __METHOD__);
        }

        return empty($isExists) ? false : true;
    }    

    private static function getUsersDB($userIds, $cols = '*')
    {
        $db = Base_Context::getInstance()->getDbConnection();

        if (!$db) {
            Base_Service_Log::log('dbfetch_error', Base_Service_Log::getTrace());
        }

        if (is_array($userIds)) {
            $result = array();

            // находим пользователей в одной базе
            $users = $db->fetchAll($db->select()->from('user', $cols)->where('user_id IN('.implode(', ', $userIds).')'), __METHOD__);
            $delkey = array();
            foreach($users as $value){
                if(isset($value['user_id'])){
                    $userId = $value['user_id'];
                    $result[$userId] = $value;
                    array_push($delkey, $userId);
                }
            }

            // удаляем ключи найденные в мемкешe
            $userIds = array_diff($userIds, $delkey);
            if (!empty($userIds)) {
                // находим пользователей в другой базе
                $users = $db->fetchAll($db->select()->from('user_new', $cols)->where('user_id IN('.implode(', ', $userIds).')'), __METHOD__);
                $delkey = array();
                foreach($users as $value){
                    if(isset($value['user_id'])){
                        $userId = $value['user_id'];
                        $result[$userId] = $value;
                        array_push($delkey, $userId);
                    }
                }
                // удаляем ключи найденные в мемкешe
                $userIds = array_diff($userIds, $delkey);
            }

            return $result;

        } else {
            // сначала берем из новых, это должно в основе попадаться, старые - это ошибки мемкеша по факту
            $user = $db->fetchRow($db->select()->from('user', $cols)->where('user_id = ?', $userIds), __METHOD__);
            if (!$user) {
                $user = $db->fetchRow($db->select()->from('user_new', $cols)->where('user_id = ?', $userIds), __METHOD__);
            }
            if (!$user) {
                $user = array('user_id' => 0);
            }
            return (@$user['user_id'] ? $user : array());
        }
    }

    public static function getMemcachedShortUser($userId, $skipDb = false)
    {
    	if (!self::isShortUserEnabled()) {
    		return self::getMemcachedUser($userId, $skipDb);
    	}
    	if (!$userId) {
    		return array();
    	}

    	$return = $notFound = $notFoundFull = array();
    	$userIds = (array) $userId;
    	foreach ($userIds as $_userId) {
    		// Проверяем, является ли userId натуральным числом
    		if ((string) (int) $_userId === (string) $_userId && $_userId > 0) {
    			if (isset(self::$shortModelCache[$_userId])) {
    				$return[$_userId] = self::$shortModelCache[$_userId];
    			} else {
    				$notFound[self::MKEY_USER_SHORT_ID . $_userId] = $_userId;
    				$notFoundFull[self::MKEY_USER_ID . $_userId] = $_userId;
    			}
    		}
    	}

    	if (!empty($notFound)) {

    		$inCacheUsers = Base_Service_Memcache::getCache(array_keys($notFoundFull));
    		if (count($inCacheUsers)) {
    			$inCacheUsers = self::buildShortUserArray($inCacheUsers);
    			foreach ($inCacheUsers as $mcKey => $shortUserData) {
    				$return[$shortUserData['user_id']] = $shortUserData;
    				self::$shortModelCache[$shortUserData['user_id']] = $shortUserData;
    				unset($notFound[self::MKEY_USER_SHORT_ID . $shortUserData['user_id']]);
    			}
    		}
    		// Забираем данные из кэша
    		$cachedUsers = Base_Service_Memcache::get(array_keys($notFound), __METHOD__);
    		foreach ($cachedUsers as $mKey => $userData) {
    			// если в кэше есть данные по юзеру
    			$userData = is_array($userData) ? $userData : Base_Util_Serialize::unserializeFromMemcache('userId_short', $userData);
    			if (isset($userData['user_id'])) {
    				$rUserId = $notFound[$mKey];

    				// Если запрашиваемый userId не сходится с тем, что есть в модельке, это ошибка!
    				if ($rUserId != $userData['user_id']) {
    					file_put_contents('var/log/error.memcached_user.txt', date('Y-m-d H:i:s')."\t{$rUserId}\t{$userData['user_id']}\n", FILE_APPEND);
    					continue;
    				}
    				// Костылёк.. потому что сишный крон апдейта онлайна пишет длинные флоаты в мемкэш при апдейте модельки
    				if (isset($userData['user_cash'])) {
    					$userData['user_cash'] = round($userData['user_cash'], 2);
    				}
    				self::$shortModelCache[$rUserId] = $userData;
    				$return[$rUserId] = $userData;
    			}
    			unset($notFound[$mKey]);
    		}

    		// Если что, запрашиваем из базы, и кэшируем
    		if (!empty($notFound) && !$skipDb) {
    			$dbUsers = self::getMemcachedUser($notFound, $skipDb);
    			if (!empty($dbUsers)) {
    				foreach($dbUsers as $_userId => $userData) {
    					$return[$_userId] = self::buildShortUser($userData);
    					$mKey = self::MKEY_USER_SHORT_ID . $_userId;
    					self::$shortModelCache[$_userId] = $return[$_userId];
    					Base_Service_Memcache::set($mKey, self::shortMcUser($return[$_userId], false), Db_User::CACHE_TIME);
    					unset($notFound[$mKey]);
    				}
    			}

    			// Кэшируем пустых юзеров, если их модельки не найдены в базе
    			if (!empty($notFound)) {
    				foreach ($notFound as $mKey => $_userId) {
    					Base_Service_Memcache::set($mKey, array(), Db_User::CACHE_TIME);
    				}
    			}
    		}
    	}

    	return is_array($userId)
    	? $return
    	: (isset($return[$userId]) ? $return[$userId] : array());
    }

    /**
     * Получение данных пользователя из мемкэша
     * - если стоит флаг $skipDb, метод не полезет за юзером в базу
     * @param $userId
     * @param bool $skipDb
     * @return array
     */
    public static function getMemcachedUser($userId, $skipDb = false)
    {
        if (!$userId) {
            return array();
        }

        $return = $notFound = array();
        $userIds = (array) $userId;

        foreach ($userIds as $_userId) {
            // Проверяем, является ли userId натуральным числом
            if ((string) (int) $_userId === (string) $_userId && $_userId > 0) {
                $notFound[self::MKEY_USER_ID . $_userId] = $_userId;
            }
        }

        if (!empty($notFound)) {
            // Забираем данные из кэша
            $cachedUsers = Base_Service_Memcache::get(array_keys($notFound), __METHOD__);
            foreach ($cachedUsers as $mKey => $userData) {
                // если в кэше есть данные по юзеру
                if (isset($userData['user_id'])) {
                    $rUserId = $notFound[$mKey];

                    // Если запрашиваемый userId не сходится с тем, что есть в модельке, это ошибка!
                    if ($rUserId != $userData['user_id']) {
                        file_put_contents('var/log/error.memcached_user.txt', date('Y-m-d H:i:s')."\t{$rUserId}\t{$userData['user_id']}\n", FILE_APPEND);
                        continue;
                    }
                    // Костылёк.. потому что сишный крон апдейта онлайна пишет длинные флоаты в мемкэш при апдейте модельки
                    if (isset($userData['user_cash'])) {
                        $userData['user_cash'] = round($userData['user_cash'], 2);
                    }
                    $return[$rUserId] = $userData;
                }
                unset($notFound[$mKey]);
            }

            // Если что, запрашиваем из базы, и кэшируем
            if (!empty($notFound) && !$skipDb) {
            	$dbUsers = self::getUsersDB($notFound);
                if (!empty($dbUsers)) {
                    foreach($dbUsers as $_userId => $userData) {
                        $return[$_userId] = $userData;
                        $mKey = self::MKEY_USER_ID . $_userId;
                        Base_Service_Memcache::set($mKey, $userData, Db_User::CACHE_TIME);
                        unset($notFound[$mKey]);
                    }
                }

                // Кэшируем пустых юзеров, если их модельки не найдены в базе
                if (!empty($notFound)) {
                    foreach ($notFound as $mKey => $_userId) {
                        Base_Service_Memcache::set($mKey, array(), Db_User::CACHE_TIME);
                    }
                }
            }
        }

        return is_array($userId)
            ? $return
            : (isset($return[$userId]) ? $return[$userId] : array());
    }

    private function deleteMemcachedUser($user)
    {
        if (!empty($user['user_id'])) {
            $this->_flushCache($user['user_id']);
        }
    }

    /**
     * Flush memcache data by userId
     * @param  int $userId
     * @return void
     */
    private function _flushCache($userId)
    {
        Base_Service_Memcache::delete(self::MKEY_USER_ID.$userId);
        if (self::isShortUserEnabled()) {
            Base_Service_Memcache::delete(self::MKEY_USER_SHORT_ID.$userId);
        }
    }

    public function getLastUserNewId($noCache = false)
    {
        $fn = 'var/userNew.firstUserId.txt';
        self::$userIdFirst = (@filemtime($fn)+10*60 > TIME ? @file_get_contents($fn) : 0);
        if (!self::$userIdFirst || $noCache) {
            self::$userIdFirst = $this->db->fetchOne($this->db->select()->from('user_new', array('MIN(user_id)')), __METHOD__);
            if (!self::$userIdFirst) self::$userIdFirst = -1;
            @file_put_contents($fn, self::$userIdFirst);
        }
        return self::$userIdFirst;
    }

    public static function getTb($userId, $noCache = false)
    {
        if (!self::$userIdFirst || $noCache) {
            $db = Base_Context::getInstance()->getDbConnection();
            $fn = 'var/user_new.firstUserId.txt';
            self::$userIdFirst = (@filemtime($fn)+10*60 > TIME ? @file_get_contents($fn) : 0);
            if (!self::$userIdFirst || $noCache) {
                self::$userIdFirst = $db->fetchOne($db->select()->from('user_new', array('MIN(user_id)')), __METHOD__);
                if (!self::$userIdFirst) {
                    self::$userIdFirst = 999999999;
                }
                @file_put_contents($fn, self::$userIdFirst);
            }
        }
        return (self::$userIdFirst && $userId >= self::$userIdFirst ? 'user_new' : 'user');
    }

    public static function updateMemcachedUser($userId, $params=array())
    {
        if (!$userId) {
            return false;
        }

        $user = Db_User::getMemcachedUser($userId);

        foreach ($params as $key => $value) {
            $user[$key] = $value;
        }

        Base_Service_Memcache::set(self::MKEY_USER_ID.$userId, $user, Db_User::CACHE_TIME);
        if (self::isShortUserEnabled()) {
        	Base_Service_Memcache::set(self::MKEY_USER_SHORT_ID.$userId, self::shortMcUser($user), Db_User::CACHE_TIME);
        }

        return $user;
    }

    public static function updateMemcacheOnlineMask($newMasks/*, $optFields = array()*/)
    {
        if (empty($newMasks)) {
            return false;
        }
        $time = time();
        $users = Db_User::getMemcachedUser(array_keys($newMasks), true);
        foreach ($users as $user) {
            if (isset($user['user_id'], $newMasks[$user['user_id']])) {
                if (!isset($user['online_mask']) || $user['online_mask'] > 256) {
                    $user['online_mask'] = 0;
                }
                // Just the same implementation in php as in DB-query for online
                $newMask = Base_Service_User::updateFullOnlineMask($user['online_mask'], $newMasks[$user['user_id']]);
                if ($newMask & Base_Service_User::getAllOnlineMask()) {
                // is user online anywhere
                    $user['online_mask'] = $newMask;
                    if ($user['time_out'] > 0) {
                        $user['time_in'] = $time;
                    }
                    $user['time_out'] = 0;
                } else {
                // user is offline everywhere
                    $user['online_mask'] = 0;
                    $user['time_out']    = $time;
                }
                Base_Service_Memcache::set(self::MKEY_USER_ID. $user['user_id'], $user, Db_User::CACHE_TIME);
                if (self::isShortUserEnabled()) {
                	Base_Service_Memcache::set(self::MKEY_USER_SHORT_ID . $user['user_id'], self::shortMcUser($user), Db_User::CACHE_TIME);
                }
            }
        }
    }

    public static function resetOnlineStatusMemcache($userIds)
    {
        $time = time();
        $users = Db_User::getMemcachedUser($userIds, true);
        foreach ($users as $user) {
            $user['time_out']    = $time;
            $user['online_mask'] = 0;
            Base_Service_Memcache::set(self::MKEY_USER_ID . $user['user_id'], $user, Db_User::CACHE_TIME);
        	if (self::isShortUserEnabled()) {
            	Base_Service_Memcache::set(self::MKEY_USER_SHORT_ID . $user['user_id'], self::shortMcUser($user), Db_User::CACHE_TIME);
            }
        }
    }


    /**
     * @deprecated
     */
    public function userList($page, $where=array(), $order='user_id', $desc = 0, $userTable = 'user')
    {
        $select = $this->db->select()->from($userTable)->order($order . ($desc ? ' DESC' : ''));
        if ($where) $select->where($where);
        $list1 = $this->fetchPaging($select, 50, $page, 'all', null, __METHOD__);
        return $list1;
    }

    public function userListByRef($refId)
    {
        $allIds = array();
        foreach (array('user', 'user_new') as $table) {
            $query = $this->db->select()->from($table, array('user_id'))->where('user_ref_id = ?', $refId);
            $ids = $this->db->fetchCol($query, __METHOD__);
            $allIds = array_merge($allIds, $ids);
        }
        return $allIds;
    }

    /**
     * Метод подтверждения email пользователя. Использовать только его, т. к. он считает все статсы.
     * @param Base_Model_User $user
     * @param boolean $approved
     */
    public static function setEmailApproved(Base_Model_User $user, $approved)
    {
        $analytics = Base_Service_Counter_Analytics::getInstance();
        $updatedRefId = $user->getDaysRegistered() > 15 ? (isset($_COOKIE['ref_id']) ? (int) $_COOKIE['ref_id'] : 0) : $user->getRefId(); // костыль для обновления ref_id юзера
        $emailConfirmDate = $user->getEmailConfirmDate();

        $oldClass = $user->getUserClass();

        $data['user_email_approved'] = $approved ? 1 : null;

        // если мыло не заапрувлено и мы хотим его заапрувить
        if (!$user->isEmailApproved() && $approved) {
            $data['confirm_date'] = date('Y-m-d H:i:s'); // сохраняем только дату первого подтверждения
        }

        // костыль для обновления ref_id юзера без повторного updateUser
        if ($updatedRefId !== null) {
            $data['user_ref_id'] = $updatedRefId;
        }

        Base_Dao_User::updateUser($user->getId(), $data);

        // Получаем обновленную модель юзера
        $user = Base_Dao_User::getUserById($user->getId());

        // Повышаем до резидента
        Userclass_Service::updateUserClass($user, $oldClass);

        $addWeekMailsForUser = false;
        $firstApprove = false;

        if (empty($emailConfirmDate) && $approved) {
            // временный костыль, предотвращающий повторный «первый» конфирм имейла одним и тем-же юзером
            $bt = Base_Service_Memcache::get('emailapproveuniquetmp:'.$user->getId());

            if (!$bt) {
                Base_Service_Memcache::set('emailapproveuniquetmp:'.$user->getId(), "\n".implode("\n", Base_Service_Log::getTrace(10))."\n".date('Y-m-d H:i:s')."\n", 60*60*2);

                $firstApprove = true;
                $user->getNativeProject()->getStatisticClient()->increment($user, 'signup_approved', 1);

                // @tmp log
                $statsClient = $user->getNativeProject()->getStatisticClient();

                if (!($statsClient instanceof Base_Service_Counter_Main)) {
//                    $statsClient = Base_Service_Counter_Main();
//                    $statsClient->increment($user, 'signup_approved', 1);

                    Base_Service_Log::log('nomaincounter', array(get_class($user->getNativeProject()), get_class($statsClient), implode("\n", Base_Service_Log::getTrace(10))));
                }

                // @analytics stats
                $analytics->increment($user, 'signup_approved', 1);

                $signupStats = Base_Service_Counter_Signup::getInstance();
                $signupStats->increment($user, 'signup_approved_all');
                if (strtotime(date('Y-m-d', strtotime($user->getDateInserted()))) == mktime(0, 0, 0)) {
                    $signupStats->increment($user, 'signup_approved');
                }

                // @involvement stats
                Base_Service_Counter_Involvement::getInstance()->increment($user, 'involvement_signup_all', 1);
                //$involvement->incrementStats($user, Base_Service_Counter_Involvement::FIELD_SIGNUP, 1);

                // @antifraud signups
                $suspeiciousUsers = array_keys(Antifraud_Dao_Signup::getAllUsers());
                if (in_array($user->getId(), $suspeiciousUsers)) {
                    Base_Service_Counter_Main::getInstance()->increment($user, 'signup_time_approved', 1);
                }

                // fotocash
                Base_Service_Counter_Stats::incrementPartnerConfirm($user);

                // считаем виральные регистрации
                Base_Service_UserSignup::countViralRegStatsAndClear($user);

                // платные регистрации: те, что идут на партнерку + несколько дополнительных
                $fcPartnersCountedInMainStats = array_diff(
                    array_keys(Base_Service_Counter_Stats::$partners),
                    Base_Service_Counter_Stats::$partnersExclude
                );
                if (
                    Base_Service_Counter_Stats::definePartnerId($user->getRefId()) == Base_Service_Counter_Stats::PARTNER_ID_ALL_OTHER
                    || in_array($user->getRefId(), $fcPartnersCountedInMainStats)
                ) {
                    if (!in_array($user->getRefId(), Base_Service_Counter_Stats::$unpaidRegsRefIds)) {
                        $user->getNativeProject()->getStatisticClient()->increment($user, 'signup_approved_paid', 1);
                    }
                }

                if (Base_Project_Manager::isFsWhitelabelMod()) {
                    $domainSource = Base_Project_Manager::getProject()->getDomainModel()->getUserSource();
                    if ($domainSource) {
                        $land = isset($_COOKIE['land']) && $_COOKIE['land'] == 'dating' ? 'dating' : 'pet';
                        Base_Service_Counter_Main::getInstance()->increment($user, 'whitelabel_' . $land . '_signup', 1);
                    }
                }

                if(Traffic_Service_Tds::getCookie('ref_id') == $user->getRefId()){
                    Traffic_Service_Tds::increment(
                        Traffic_Service_Tds::getCookie('promo'),
                        Traffic_Service_Tds::getCookie('landing'),
                        Traffic_Service_Tds::getCookie('theme'),
                        'signup_approved'
                    );
                }

                if(Start_Service_UserScheduleAction::isAddBotActivity($user)) {
                    Start_Service_UserScheduleAction::addDatingSignMailActivity($user);
                }

                if (strtotime($user->getDateInserted()) >= strtotime('-3 days')) {
                    Start_Service_UserScheduleAction::addAction($user, Start_Service_UserScheduleAction::TYPE_SEND_AREYOULOST_EMAIL, '+3 days');
                }

                if (strtotime($user->getDateInserted()) >= strtotime('-1 month')) {
                    Start_Service_UserScheduleAction::addAction($user, Start_Service_UserScheduleAction::TYPE_PEOPLE_NEWBIES_WELCOME, '7 day');
                    //Start_Service_UserScheduleAction::addAction($user, Start_Service_UserScheduleAction::TYPE_PEOPLE_HOW_TO_BE_COOL, '3 day');
                }

                // репутация
                $reputation = Base_Interface_Factory::get('Reputation'); /* @var $reputation Reputation_Interface_Base */
                $reputation->reputationEvent($user->getId(), Reputation_Dao_Base::EVENT_APPROVE_EMAIL);

                Base_Service_Counter_Stats::increment($user, 'mail_approve');
                $analytics->increment($user, 'mail_approve');

                Mail_Dao_Mailmonitor::incrementUserSignup($user, true);


                if ((int) $user->getRefId() === Base_Service_Counter_Stats::PARTNER_ID_FSM_INVITE) {
                    $statsClient = new Base_Service_Counter_Fsmessenger();
                    $statsClient->increment($user, 'invites_regs');
                }

                // Костылик для питомцев, есть проблема если юзер апрувит емейл после создания пета, не позволяет отрпавить ему письмицо
                if ($user->hasPet()) {
                    Pet_Service_Base::getAnalyticsClient()->increment($user, 'pet_approve_email_after_reg_pet');
                    Pet_Dao_TrafficExchange::insertUserEmailApproved($user->getId(), $data['confirm_date']);
                }

            } else {
                Base_Service_Log::log('emailapproveuniquetmp', array($user->getId(), $bt, "\n ========== \n", "\n".implode("\n", Base_Service_Log::getTrace(10))));
            }

            // добавляем имейл в очередь парсера контактов mail.ru
            Crawler_Service_Base::pushParserRequestToQueue($user->getEmail(), Crawler_Service_Base::PRIORITY_LOW, Crawler_Service_Base::CALLER_ID_FS_SIGNUP);

            $addWeekMailsForUser = true;
        }

        if ($approved) {
            $user['user_email_approved'] = true;
            Base_Service_Counter_Main::incrementLoginByDateInserted($user);

            $ids = array(
                Spider_Service_SocialLinks::TYPE_ID_FS => $user->getId(),
                Spider_Service_SocialLinks::TYPE_ID_EMAIL => array($user->getEmail()),
            );

            Spider_Service_SocialLinks::getInstance()->addUser($ids);

            Return_Dao_Registry::addUser($user);

            if($addWeekMailsForUser) {
                // создаем расписание рассылки писем на неделю для этого пользователя
                Start_Service_UserScheduleAction::addInviteTrafWeekMails($user);
            }

            $aclTrack = new Analytics_Service_AclTrack($user);
            $aclTrack->trackGoalAction($aclTrack::DENY_NO_EMAIL_APPROVE);
        } else {
            Return_Dao_Registry::deleteUserById($user->getId());
        }

        if ($firstApprove && $approved && $user->isEmailApproved()) {
            Base_Service_Counter_MailSystemSource::inc($user, Base_Service_Counter_MailSystemSource::FIELD_MAIL_APPROVED);
        }
    }

    /**
     * @param base_model_user $user
     */
    private static function countApproveBySource($user)
    {
        $prefixes = array(
            Base_Service_UserSource::SOURCE_DATING_MAINPAGE => 'mainpage_signup',
        );

        $source = $user->getSource();
        if (isset($prefixes[$source])) {
            $field = $prefixes[$source] . '_approved';
            $stats = Base_Service_Counter_Main::getInstance();
            $stats->increment($user, $field);
        }
    }

    private static function buildShortUser($data) {
    	//return $data;
    	$shortUser = array(
    		'online_mask' => isset($data['online_mask']) ? (int)$data['online_mask'] : null,
    		'time_in' => isset($data['time_in']) ? (int)$data['time_in'] : null,
    		'time_out' => isset($data['time_out']) ? (int)$data['time_out'] : null,
    		'vip_end' => isset($data['vip_end']) ? $data['vip_end'] : null,
    		'user_is_hidden' => isset($data['user_is_hidden']) ? (int)$data['user_is_hidden'] : null,
    		'user_photo_id' => isset($data['user_photo_id']) ? (int)$data['user_photo_id'] : null,
    		'user_updated' => isset($data['user_updated']) ? $data['user_updated'] : null,
    		'user_native_domain' => isset($data['user_native_domain']) ? (int)$data['user_native_domain'] : null,
    		'user_id' => isset($data['user_id']) ? (int)$data['user_id'] : null,
    		'user_pet_id' => isset($data['user_pet_id']) ? (int)$data['user_pet_id'] : null,
    		'user_pagename' => isset($data['user_pagename']) ? $data['user_pagename'] : null,
    		'user_name' => isset($data['user_name']) ? $data['user_name'] : null,
    		'user_lastname' => isset($data['user_lastname']) ? $data['user_lastname'] : null,
			'user_lastname_show' => isset($data['user_lastname_show']) ? (int)$data['user_lastname_show'] : null,
			'user_birthday' => isset($data['user_birthday']) ? $data['user_birthday'] : null,
			'user_city_id' => isset($data['user_city_id']) ? (int)$data['user_city_id'] : null,
			'user_city' => isset($data['user_city']) ? $data['user_city'] : null,
			'user_sex' => isset($data['user_sex']) ? $data['user_sex'] : null,
    		'user_foto_ext' => isset($data['user_foto_ext']) ? (int)$data['user_foto_ext'] : null,
    		'user_payable' => isset($data['user_payable']) ? (int)$data['user_payable'] : null,
    		'user_source' => isset($data['user_source']) ? $data['user_source'] : null,
			'user_source_result' => isset($data['user_source_result']) ? $data['user_source_result'] : null,
			'user_inserted' => isset($data['user_inserted']) ? $data['user_inserted'] : null,
			'user_region_id' => isset($data['user_region_id']) ? (int)$data['user_region_id'] : null,
    		'user_email_approved' => isset($data['user_email_approved']) ? (bool)$data['user_email_approved'] : false,
    	);
    	return $shortUser;
    }

    private static function shortMcUser($user, $forceBuild = true) {
    	// Make serialization here
    	if ($forceBuild) {
    		$user = self::buildShortUser($user);
    	}
    	return Base_Util_Serialize::serializeForMemcache('userId_short', $user);
    }


	private static function buildShortUserArray($users, $fixKeys = true) {
		foreach ($users as $key => $user) {
			$model = self::buildShortUser($user);
			$users[($fixKeys ? self::MKEY_USER_ID . $user['user_id'] : $key)] = $model;
			self::$shortModelCache[$user['user_id']] = $model;
		}
    	return $users;
    }
}
