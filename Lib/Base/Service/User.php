<?php

class Base_Service_User
{
    const BAN_COOKIE_NAME = 'f_ubx';
    const COOKIE_CHECKSUM = 27;

    const USER_GENDER_FEMALE  = 0;
    const USER_GENDER_MALE    = 1;
    const USER_GENDER_UNKNOWN = 2;

    const SALT_TYPE_COOKIE   = 'cookie';
    const SALT_TYPE_COOKIE_OLD   = 'cookie_old';
    const SALT_TYPE_EMAIL    = 'email';
    const SALT_TYPE_PASSWORD = 'password';

    /* Bitwise online/offline statuses */
    const SOURCE_OFFLINE = 0;

    // Online on site
    const SOURCE_FS_ONLINE = 1;
    const SOURCE_FS_AWAY   = 2;

    // Online in messenger
    const SOURCE_FSM_ONLINE = 4;
    const SOURCE_FSM_AWAY   = 8;

    // Online from mibile version of FS
    const SOURCE_MOBILE_ONLINE = 16;
    const SOURCE_MOBILE_AWAY   = 32;

    // Online from iPhone
    const SOURCE_IPHONE_ONLINE = 64;
    const SOURCE_IPHONE_AWAY   = 128;

    // Тут внимание! Добвлять новые сурсы можно но мы ограничены 16 битами (8 из которых уже заняты)
    const ONLINE_BIT_SHIFT = 16; // x bit shift
    const ONLINE_MAX_ONLINE_TIME = 86400; //24 * 60 * 60 - максимальное время онлайна
    const ONLINE_TIMEOUT_UPDATE  = 180; // 3 * 60 - тамймаут, когда записывать юзера в up

    /*Sources*/
    const ONLINE_SOURCE_FS  = 1;
    const ONLINE_SOURCE_FSM = 2;
    const ONLINE_SOURCE_MOBILE = 3;
    const ONLINE_SOURCE_IPHONE = 4;

    /*statuses*/
    const ONLINE_STATUS_ONLINE  = 1;
    const ONLINE_STATUS_UPDATE  = 2;
    const ONLINE_STATUS_OFFLINE = 3;

    const ACTIVITY_GROUP_ERROR     = 5;
    const ACTIVITY_GROUP_NEWUSER   = 1;
    const ACTIVITY_GROUP_ACTIVE    = 2;
    const ACTIVITY_GROUP_INACTIVE  = 3;
    const ACTIVITY_GROUP_LOST      = 4;

    const M_GROUP_AGE_KID    = 1;
    const M_GROUP_AGE_ADULT  = 4;
    const M_GROUP_AGE_NO_AGE = 6;

//    const M_GROUP_AGE_TEEN   = 2;
//    const M_GROUP_AGE_YOUNG  = 3;
//    const M_GROUP_AGE_AGED   = 5;

    const M_GROUP_MALE      = 1;
    const M_GROUP_FEMALE    = 2;

    const F_ACT_GROUP = 1;
    const F_AGE_GROUP = 2;
    const F_SEX_GROUP = 3;

    const ZODIAC_ARIES = 1; // Овен
    const ZODIAC_TAURUS = 2; // Телец
    const ZODIAC_GEMINI = 3; // Близнецы
    const ZODIAC_CANCER = 4; // Рак
    const ZODIAC_LEO = 5; // Лев
    const ZODIAC_VIRGO = 6; // Дева
    const ZODIAC_LIBRA = 7; // Весы
    const ZODIAC_SCORPIO = 8; // Скорпион
    const ZODIAC_SAGITTARIUS = 9; // Стрелец
    const ZODIAC_CAPRICORN = 10; // Козерог
    const ZODIAC_AQUARIUS = 11; // Водолей
    const ZODIAC_PISCES = 12; // Рыбы

    public static $zodiacs = array(
        self::ZODIAC_ARIES => array(
            'name' => 'Овен',
            'range' => array(321, 420),
            'suitable_signs' => array(self::ZODIAC_GEMINI, self::ZODIAC_LEO, self::ZODIAC_CAPRICORN, self::ZODIAC_AQUARIUS, self::ZODIAC_PISCES),
        ),
        self::ZODIAC_TAURUS => array(
            'name' => 'Телец',
            'range' => array(421, 520),
            'suitable_signs' => array(self::ZODIAC_CANCER, self::ZODIAC_LEO, self::ZODIAC_VIRGO, self::ZODIAC_LIBRA, self::ZODIAC_CAPRICORN, self::ZODIAC_PISCES),
        ),
        self::ZODIAC_GEMINI => array(
            'name' => 'Близнецы',
            'range' => array(521, 621),
            'suitable_signs' => array(self::ZODIAC_ARIES, self::ZODIAC_LEO, self::ZODIAC_LIBRA, self::ZODIAC_AQUARIUS, self::ZODIAC_PISCES),
        ),
        self::ZODIAC_CANCER => array(
            'name' => 'Рак',
            'range' => array(622, 722),
            'suitable_signs' => array(self::ZODIAC_ARIES, self::ZODIAC_LEO, self::ZODIAC_LIBRA, self::ZODIAC_AQUARIUS, self::ZODIAC_PISCES),
        ),
        self::ZODIAC_LEO => array(
            'name' => 'Лев',
            'range' => array(723, 823),
            'suitable_signs' => array(self::ZODIAC_ARIES, self::ZODIAC_TAURUS, self::ZODIAC_GEMINI, self::ZODIAC_CANCER, self::ZODIAC_LEO, self::ZODIAC_LIBRA, self::ZODIAC_SAGITTARIUS),
        ),
        self::ZODIAC_VIRGO => array(
            'name' => 'Дева',
            'range' => array(824, 923),
            'suitable_signs' => array(self::ZODIAC_TAURUS, self::ZODIAC_CANCER, self::ZODIAC_VIRGO, self::ZODIAC_SCORPIO, self::ZODIAC_CAPRICORN, self::ZODIAC_AQUARIUS),
        ),
        self::ZODIAC_LIBRA => array(
            'name' => 'Весы',
            'range' => array(924, 1022),
            'suitable_signs' => array(self::ZODIAC_TAURUS, self::ZODIAC_GEMINI, self::ZODIAC_LEO, self::ZODIAC_LIBRA, self::ZODIAC_SAGITTARIUS, self::ZODIAC_AQUARIUS),
        ),
        self::ZODIAC_SCORPIO => array(
            'name' => 'Скорпион',
            'range' => array(1023, 1122),
            'suitable_signs' => array(self::ZODIAC_CANCER, self::ZODIAC_VIRGO, self::ZODIAC_CAPRICORN, self::ZODIAC_PISCES),
        ),
        self::ZODIAC_SAGITTARIUS => array(
            'name' => 'Стрелец',
            'range' => array(1123, 1221),
            'suitable_signs' => array(self::ZODIAC_LEO, self::ZODIAC_LIBRA, self::ZODIAC_AQUARIUS),
        ),
        self::ZODIAC_CAPRICORN => array(
            'name' => 'Козерог',
            'range' => array(1222, 120),
            'suitable_signs' => array(self::ZODIAC_ARIES, self::ZODIAC_TAURUS, self::ZODIAC_VIRGO, self::ZODIAC_SCORPIO, self::ZODIAC_CAPRICORN, self::ZODIAC_PISCES),
        ),
        self::ZODIAC_AQUARIUS => array(
            'name' => 'Водолей',
            'range' => array(121, 219),
            'suitable_signs' => array(self::ZODIAC_ARIES, self::ZODIAC_GEMINI, self::ZODIAC_VIRGO, self::ZODIAC_LIBRA, self::ZODIAC_SAGITTARIUS, self::ZODIAC_AQUARIUS),
        ),
        self::ZODIAC_PISCES => array(
            'name' => 'Рыбы',
            'range' => array(220, 320),
            'suitable_signs' => array(self::ZODIAC_ARIES, self::ZODIAC_TAURUS, self::ZODIAC_GEMINI, self::ZODIAC_CANCER, self::ZODIAC_SCORPIO, self::ZODIAC_CAPRICORN),
        ),
    );

    /* Time outs by source */
    private static $onlineAwayTimeout = array(
        // online type => timeout in seconds
        self::SOURCE_FS_ONLINE  => 900, // 60 * 15
        self::SOURCE_FSM_ONLINE => 900, // 60 * 15
        self::SOURCE_MOBILE_ONLINE => 900, // 60 * 15
        self::SOURCE_IPHONE_ONLINE => 900, // 60 * 15
    );

    private static $_systemUsersIds = array(
        1,          // Фотострана
        2,          // Служба поддержки
        3,          // Служба модерации
        57267704,   // Служба Поддержки ФотоЧата

        63760510,   // Тематические новости в ньюсфиде
        63760585,   // Тематические новости в ньюсфиде
        63760651,   // Тематические новости в ньюсфиде
        63760698,   // Тематические новости в ньюсфиде
        63760742,   // Тематические новости в ньюсфиде
        63760791,   // Тематические новости в ньюсфиде
        63760836,   // Тематические новости в ньюсфиде
        63760932,   // Тематические новости в ньюсфиде

        65396826,   // Социальные таргетинговые объявления

        70388414, // Бот Новости Фотостраны
    );

    private static function getSalt($type)
    {
        $saltConfig = Base_Application::getInstance()->config['passwd']['salt'];
        return !empty($saltConfig[$type]) ? $saltConfig[$type] : '';
    }

    public static function getUserProfileUrl($user, $native = false)
    {
        if ($native) {
            $project = $user->getNativeProject();
        } else {
            $project = Base_Project_Manager::getProject();
        }
        $domain = $project->getDomain();

        if (Base_Service_Common::isStage(false)) {
            $domain = 'stage.' . $domain;
        }

        $isRambler = Base_Project_Manager::getProject()->isRamblerWL() || $project->isRamblerWl();

        //  костыль для letitbit и pet.rambler.ru
        if (!Base_Project_Manager::isOurDomainId($project->getDomainModel()->getId()) ||
            $isRambler) {
            return 'http://' . $domain . '/user/' . $user['user_id'] . '/';
        }

        if (!isset($user['user_id']) || !$user['user_id']) {
            return 'http://' . $domain . '/user/deleteduser/';
        }

        if (PetApp_Base_Service_Project::isPetAppProject($project->getType())) {
            return $user['user_pet_id'] ? 'http://' . $domain . '/pet/' . $user['user_pet_id'] . '/' :
                    'http://' . $domain . '/user/'. $user['user_id'] .'/';
        }

        if (isset($user['user_pagename']) && $user['user_pagename']) {
            if (Base_Project_Fotostrana::fotostrana2Enabled()) {
                //  для определения ссылок на пользователей в фс 2.0 добавляем /u/. для аякс-переходов
                // @todo найти нормальное решение, не требующее изменения урлов.
                return 'http://' . $domain . '/u/' . $user['user_pagename'] . '/';
            }
            return 'http://' . $domain . '/' . $user['user_pagename'] . '/';
        } else {
            return 'http://' . $domain . '/user/' . $user['user_id'] . '/';
        }
    }

    public static function getInterestPeople($user)
    {
        $userId = $user->getId();
        $cachedId = Base_Service_Memcache::get(Base_Dao_User::MC_USER_INTERESTPEOPLE.$userId, __METHOD__);

        if($cachedId!==false) {
            $result =  Base_Dao_User::getUsersByIds($cachedId);
        } else {
            if(!($user instanceof Base_Model_User) || !$user) {
                return array();
            }

            $periods = array(Base_Service_Interest_Tracker::USERS_PERIOD_DAY, //0
                             Base_Service_Interest_Tracker::USERS_PERIOD_WEEK, //1
                             Base_Service_Interest_Tracker::USERS_PERIOD_MONTH); //2

            $result = array();

            $userFriendsIds = Friends_Service_New::getFriends($userId, 50, 0, true);

            $userFavIds  = Usercontact_Dao_Favorite::getRight($userId, 10);
            $t = Base_Dao_User::getUsersByIds(array_unique($userFavIds[2]));
            $userFavs = array();
            foreach($t as $u){
                if ($u->isPetAppUser()) {
                    Base_Service_Counter_Social::getInstance()->increment($u, 'test_stat_pet_only_in_favorite_left', 1);
                    Base_Service_Counter_Social::getInstance()->increment($user, 'test_stat_pet_only_in_favorite_right', 1);
                }
                if($u->isOnline()) {
                    $userFavs[] = $u;
                }
            }

            if(count($userFavs)==0 || count($userFriendsIds)==0) {
                $interests = array(15,10,5);
            } else {
                $interests = array(15,5,5);
            }

            $userInterestIds = array();
            foreach($periods as $p) {
                $t = Base_Service_Interest_Tracker::getUserContactsPopular($userId, $p, $interests[$p]);
                if(is_array($t)) {
                    foreach($t as $u=>$c) {
                        if($c>2 && $u!=0) {
                            $userInterestIds[] = $u;
                        }
                    }
                }
            }
            $userInterest = Base_Dao_User::getUsersByIds(array_unique($userInterestIds));
            $userFriendsIds = array_diff($userFriendsIds, $userInterestIds, $userFavIds);
            $userFriends  = Base_Dao_User::getUsersByIds($userFriendsIds);

            $users = array_merge($userInterest, $userFavs, $userFriends);
            //$usersIds = array_merge($userInterestIds, $userFavIds[2], $userFriendsIds);

            $resIds = array();
            foreach($users as $u) {
                if($u->hasMainPhoto() && $u->getId()>1 && !in_array($u, $result)) {
                    $result[] = $u;
                    $resIds[] = $u->getId();
                }

                if(count($result)>=20) break;
            }

            Base_Service_Memcache::set(Base_Dao_User::MC_USER_INTERESTPEOPLE . $userId, $resIds);
        }

        return $result;
    }

    public static function generateUserEmailHash($userOrEmail)
    {
        $salt = self::getSalt('email');
        if ($userOrEmail instanceof Base_Model_User) {
            return md5($userOrEmail->getEmail() . $salt);
        } else {
            return md5($userOrEmail . $salt);
        }
    }

    /**
     * convert user birthday to age
     *
     * @param      $userBirthday
     * @param bool $asString
     * @param bool $baseTime
     *
     * @return bool|int|string
     */
    public static function getUserAge($userBirthday, $asString = false, $baseTime = false)
    {
        if (!$userBirthday || $userBirthday == '0000-00-00') {
            return false;
        }
        $baseTime = ($baseTime === false) ? TIME : $baseTime;

        list($year, $month, $day) = explode('-', $userBirthday);
        $yearDiff  = date('Y', ($baseTime)) - $year;
        $monthDiff = date('m', ($baseTime)) - $month;
        $dayDiff   = date('d', ($baseTime)) - $day;

        if ($monthDiff < 0) {
            $yearDiff--;
        } elseif (($monthDiff == 0) && ($dayDiff < 0)) {
            $yearDiff--;
        }

        return $asString ? _f('{plural|%d год|%d года|%d лет|%d лет}', $yearDiff) : $yearDiff;
    }

    /**
     * Получть хеш пароля из пароля. Он хранится в бд
     */
    public static function getPasswordHash($password)
    {
        return md5($password . self::getSalt(self::SALT_TYPE_PASSWORD));
    }

    public static function getPasswordCrypt($password)
    {
        $secretKey = Base_Application::getInstance()->config['passwd']['user']['crypt_key'];
        return Base_Service_Crypt::crypt($password, true, $secretKey);
    }

    public static function getPasswordDecrypt($password)
    {
        if(Base_Mailer_Service_Config::isPreviewMode()) {
            return 'password_hidden';
        }
        $secretKey = Base_Application::getInstance()->config['passwd']['user']['crypt_key'];
        return Base_Service_Crypt::decrypt($password, true, $secretKey);
    }

    /**
     * Получить хеш для авторизации
     *
     * @param      $userId           ID пользователя
     * @param      $userEmail        e-mail пользователя
     * @param      $userPasswordHash хеш пароля пользователя
     * @param bool $includeASCode    включать ли в хеш провайдера
     * @param bool $oldSalt          использовать ли старую соль (для переходного периода)
     *
     * @return string
     */
    public static function getAuthHash($userId, $userEmail, $userPasswordHash, $includeASCode = false, $oldSalt = false)
    {
        $salt = self::getSalt($oldSalt ? self::SALT_TYPE_COOKIE_OLD : self::SALT_TYPE_COOKIE);

        $provider = '';
        if ($includeASCode) {
            $ip = Base_Service_Common::getRealIp();
            $location = Base_Dao_Geo::getLocationByIp($ip);
            if (!empty($location['provider'])) {
                $provider = crc32(Utf::trim($location['provider']));
            }
        }

        return md5($userEmail . $userPasswordHash . $provider . $salt) . $userId % self::COOKIE_CHECKSUM;
    }

    /**
     * @deprecated use Base_Service_User::getAuthHash
     */
    public static function getUserPasswordSalt($userId, $userEmail, $userPassword, $newEmail = '')
    {
        return md5($userEmail . $userPassword . $newEmail . self::getSalt(self::SALT_TYPE_COOKIE_OLD)) . $userId % self::COOKIE_CHECKSUM;
    }

    /**
     * Упрощенная авторизация. Используется там, где не нужна повышенная безопасность
     *
     * @param $userId
     * @param $cookieSecretWord
     *
     * @return bool
     */
    public static function checkPasswordHash($userId, $cookieSecretWord)
    {
        $uidHash = $userId % Base_Service_User::COOKIE_CHECKSUM;
        return ($uidHash == Utf::substr($cookieSecretWord, -Utf::strlen($uidHash), Utf::strlen($uidHash)));
    }

    /**
     * Авторизует пользователя на сайте
     *
     * @param Base_Model_User $user
     *
     * @return bool
     */
    public static function logIn($user)
    {
        if (!$user) {
            return false;
        }

        Service_Base::setCookie(
            'uid',
            $user->getId(),
            100, '/', true, 2
        );
        Service_Base::setCookie(
            'hw',
            Base_Service_User::getAuthHash($user->getId(), $user->getEmail(), $user->getPasswordHash(), false),
            100, '/', true, 2
        );

        Antispam_Service_Token::setToken();
        $trace = Base_Service_Log::getTrace(3);
        Pet_Dao_Trace::log($user->getId(), 'lgn: ' . implode(',', $trace));

        return true;
    }

    public static function logout()
    {
    	Service_Base::setCookie('hw', '', -1, '/', true, 2);
    	Service_Base::setCookie('uid', '', -1, '/', true, 2);
    }

    /**
     * @param Base_Model_User $user
     *
     * @return boolean
     */
    public static function checkUserNeedsActivation($user)
    {
    	if (!$user) {
    		return false;
    	}

    	if (!$user->getRefId() && !$user->isEmailApproved() && $user->isFsUser()) {
    		return true;
    	}
        return false;
    }

    /**
     * Check User for ban
     *
     * @param Base_Model_User $user
     *
     * @return bool
     */
    public static function checkUserBanCookie($user)
    {
        return;
//        if (!$user->isBanned() && isset($_COOKIE[self::BAN_COOKIE_NAME])) {
//            Service_Base::setCookie(self::BAN_COOKIE_NAME, '');
//        } elseif ($user->isBanned() && $user->isBanned() != Db_Moders::BAN_REASON_NEED_APPROVE_EMAIL && !isset($_COOKIE[self::BAN_COOKIE_NAME])) {
//            Service_Base::setCookie(self::BAN_COOKIE_NAME, 1, 1);
//        }
    }

    /**
     * Проверям, может ли юзер стать претендентом в эксперты
     *
     * @param Base_Model_User $user   пользователь
     * @param                 $type   (expert, moder, petgid, guide)
     * @param                 $reason Возвращает причину отказа (в виде кода)
     *
     * @return bool
     */
    public static function canBeExpert($user, $type, &$reason = '')
    {
        $reason = '';

        assert(in_array($type, array('expert', 'moder', 'petgid', 'guide')));

        if (!($user instanceof Base_Model_User)) {
            trigger_error("Please pass Base_Model_User, not an array", E_USER_WARNING);
            $user = Base_Dao_User::getUserById($user['user_id']);
        }

        /*if(!$user['user_identity_approved']) {
        	if($type != 'petgid') {
                $reason = 'need_identity_approved';
                return false;
            }
        }*/

        if ($type == 'petgid' && $user->hasProfession()) {
            $reason = 'has_profession';
            return false;
        }

        // Требуем наличия подтвержденного телефона
        if ($user->getUserClass() !== Db_User::USER_CLASS_CITIZEN) {
            $reason = 'must_be_citizen';
            return false;
        }

        if(self::getUserAge($user['user_birthday'])<18 && $type!='petgid'){
            $reason = 'must_be_18';
            return false;
        }

        if ($type=='petgid') {
            if (self::getUserAge($user['user_birthday'])<16) {
                $reason = 'must_be_16';
                return false;
            }

            $pet = $user->getPet();

            if (!$pet) {
                $reason = 'no_pet';
                return false;
            }

            if ($pet->getDaltLevel() < 10) {
                $reason = 'pet_dalt_10';
                return false;
            }
        }

        $dbModers = new Admin_Dao_User();
        $bans = $dbModers->getUsersViolations(array($user['user_id']),'adminban',true);

        if(!empty($bans)) {
            $reason = 'has_bans';
        	return false;
        }

        if(Support_Dao_Base::getExpertRequestByUserId($user['user_id'],$type)) {
            $reason = 'already_applied';
            return false;
        }
        return true;
    }


    /**
     * Возвращает HTML-текст с сообщением об ошибке для данного кода ошибки.
     */
    public static function getErrorText($errorCode)
    {
        $simpleCodes = array(
            'need_identity_approved'    =>  _g('Чтобы получить профессию, надо сначала подтвердить свою личность.'),
            'need_more_reputation'      =>  _g('У тебя пока недостаточно репутации, чтобы получить профессию.'), // deprecated
            'must_be_18'                =>  _g('Тебе должно быть не меньше 18 лет!'),
            'must_be_16'                =>  _g('Тебе должно быть не меньше 16 лет!'),
            'has_profession'            =>  _g('У тебя уже есть профессия на сайте!'),
            'no_pet'                    =>  _g('У тебя нет питомца.'),
            'pet_dalt_10'               =>  _g('У твоего питомца недостаточно способностей.'),
            'has_bans'                  =>  _g('Ты был наказан за нарушения.'),
            'already_applied'           =>  _g('Ты уже подавал заявку на получение профессии. Дождись ее рассмотрения.'),
            'must_be_citizen'           =>  _g('Тебе необходимо подтвердить свой номер телефона.')
        );

        if ($errorCode == '') {
            // No error
            return '';
        }

        if (isset($simpleCodes[$errorCode])) {
            return $simpleCodes[$errorCode];
        }

        throw new Base_Exception("Invalid error code: '$errorCode'");
    }



    public static function canBeWriter($user)
    {
        return false; // профессия выключена для всех

        if (Event_Service_Editor::userInBan($user['user_id'])) {
            return false;
        }
        return
                !$user['user_is_volunteer']
                && $user['user_class'] == Db_User::USER_CLASS_CITIZEN;
    }

    public static function getDeletedUserInto(&$user)
    {
        $user['is_deleted'] = true;
        $user['user_name'] = _g('Удаленный житель');
        $user['user_pagename'] = null;
        $user['user_phone'] = null;
        $user['user_phone_approved'] = '0';
        $user['user_sex'] = 'm';
        $user['user_inserted'] = '2009-01-01 00:00:00';
        $user['user_updated'] = '2009-01-01 00:00:00';
        $user['user_email'] = 'deleted@fotostrana.ru';
        $user['password_hash'] = '';
        $user['user_email_approved'] = null;
        $user['user_ip'] = null;
        $user['user_id_from'] = null;
        $user['user_about'] = null;
        $user['user_city'] = null;
        $user['user_last_mail'] = '2009-01-01 00:00:00';
        $user['user_is_hidden'] = null;
        $user['user_is_volunteer'] = null;
        $user['user_source'] = null;
        $user['user_source_result'] = null;
        $user['user_not_activated'] = null;
        $user['user_country_id'] = 0;
        $user['user_city_id'] = 0;
        $user['user_region_id'] = 0;
        $user['user_birthday'] = null;
        $user['user_ref_id'] = null;
        $user['user_ref_pa'] = '0';
        $user['user_deviz'] = null;
        $user['user_why'] = null;
        $user['user_galleries'] = null;
        $user['user_status'] = null;
        $user['user_bans'] = null;
        $user['user_warn'] = null;
        $user['user_cash'] = '0.00';
        $user['user_hold'] = '0.00';
        $user['user_class'] = '0';
        $user['terms_agree'] = '1';
        $user['time_in'] = 1;
        $user['time_out'] = 1;
        $user['user_pet_id'] = 0;
        $user['user_photo_id'] = -1;
        return $user;
    }

    public static function getDeletedUser($userId)
    {
        $user = array();
        $user['user_id'] = $userId;
        self::getDeletedUserInto($user);
        return new Base_Model_User($user);
    }

    public static function validateEmail($email)
    {
        return Utf::preg_match('/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/', $email);
    }

    /**
     * исправить ошибки в написании пароля:
     * убирает пробелы на краях и заменяет русские символы транслитом
     * (если забыл сменить язык)
     */
    public static function correctPassword($pswd)
    {
        $pswd = trim($pswd);

        // strtr получается в 4е раза быстрее чем str_replace
        return strtr($pswd, 'ёйцукенгшщзфывапролдячсмитьЁЙЦУКЕНГШЩЗФЫВАПРОЛДЯЧСМИТЬ', '`qwertyuiopasdfghjklzxcvbnm~QWERTYUIOPASDFGHJKLZXCVBNM');
    }

    /**
     * Валидатор для имени пользователя
     *
     * @param  $name
     * @param string $error
     * @return bool
     */
    public static function validateUsername($name, &$error = '', &$debug = '')
    {
        // если нужно заблочить смешанное имя (кириллица + латиница),
        // можно добавлять в любой массив
        // разделение массивов сделано для увеличения производительности в
        // ф-циях preg_match

        if (Base_Service_Common::isOurIp()) {
            return true;
        }

        $name = str_replace(chr(173), '', $name);

        // stop words in Antifraud_Service_Badwords
        $cyrStopWords = Antifraud_Service_Badwords::getWords(Antifraud_Service_Badwords::CYR_NAMES);
        $latStopWords = Antifraud_Service_Badwords::getWords(Antifraud_Service_Badwords::LAT_NAMES);

        array_walk($cyrStopWords, function (&$value, $key) {
            $value = preg_quote($value);
        });

        array_walk($latStopWords, function (&$value, $key) {
            $value = preg_quote($value);
        });

        $error = '';

        $toCyrName = strtr($name, 'aA6cCeETmHoOpPkKxXBMbryYUunl3gt', 'аАбсСеЕТтНоОрРкКхХВМьгуУИип1Здт'); // переводим имя в кириллицу
        $toLatName = strtr($name, 'аАбсСеЕТтНоОрРкКхХВМьгуУИип1Здт', 'aA6cCeETmHoOpPkKxXBMbryYUunl3gt'); // переводим имя в латиницу

        $name = Utf::trim(mb_strtolower($name));
        $toCyrName = Utf::trim(mb_strtolower($toCyrName));
        $toLatName = Utf::trim(mb_strtolower($toLatName));

        // первый этап проверки, если юзер меняет на что-то вроде админ или admin (т.е. не использует замены символов на похожие)
        if (!$error && Utf::preg_match('/(' . implode('|', array_merge($cyrStopWords, $latStopWords)) . ')/', $name)) {
            $error = 'stop_word';
            $debug = 'lat_or_cyr_stop_word';
        }

        // второй этап, если юзер ввел имя, используя замену кириллических символов на похожие латинские
        if (!$error && Utf::preg_match('/(' . implode('|', $cyrStopWords) . ')/', $toCyrName)) {
            $error = 'stop_word';
            $debug = 'cyr_to_lat_change';
        }

        // третий этап, если юзер ввел имя, используя замену латинских символов на похожие кириллические
        if (!$error && Utf::preg_match('/(' . implode('|', $latStopWords) . ')/', $toLatName)) {
            $error = 'stop_word';
            $debug = 'lat_to_cyr_change';
        }

        // normalize urls
        $toLatName = str_replace(' ', '', $toLatName);
        $toLatName = preg_replace('#[.]+#', '.', $toLatName);

        if (!$error && Utf::preg_match(Base_Util_String::getUrlPreg(), $toLatName) > 0) {
            $error = 'stop_word';
            $debug = 'url_preg';
        }

        $statsClient = new Base_Service_Counter_Main();
        $defaultUser = Base_Dao_User::getUserById(1);
        $statsClient->increment($defaultUser, 'signup_time_name_check', 1);

        if ($error) {
            $statsClient->increment($defaultUser, 'signup_time_name_block', 1);
            return false;
        }

        return true;
    }

    public static function getRemainingDeleteRequestTime($userId)
    {
        $requestTime = Base_Dao_User::getDeleteRequestTime($userId);
        if (!$requestTime) {
            return false;
        }
        $requestTimeEnd = $requestTime + 60*60*24;
        $timeRemaining = $requestTimeEnd - time();
        // Пользователь за 5 дней не подтвердил желание удалиться, удаляем заявку
        $user = Base_Dao_User::getUserById($userId);
        if($timeRemaining < -60*60*24*5 && $user['user_is_hidden'] != Db_Moders::DELAYED_DELETED){
            Base_Dao_User::cancelDeleteRequest($userId);
            return false;
        }
        if ($timeRemaining < 0) {
            return -1;
        }
        $hours = (int)($timeRemaining / (60*60));
        $minutes = (int)($timeRemaining % (60*60) /60);

        $hoursStr = _f('{plural|%d час|%d часа|%d часов}', $hours);
        $minutesStr = _f('{plural|%d минуту|%d минуты|%d минут}', $minutes);

        return $hoursStr . ' ' . $minutesStr;
    }

    public static function getRemainingDeleteTime($userId)
    {
        $requestTime = Base_Dao_User::getDeleteRequestTime($userId);
        if (!$requestTime) {
            return false;
        }
        $deleteTime = $requestTime + 60*60*24*30;
        $timeRemaining = $deleteTime - time();
        if ($timeRemaining < 0) {
            return _g('еще одного дня');
        }
        $days = (int)($timeRemaining / (60*60*24));
        if ($days == 0) {
            $days = 1;
        }
        return _f('{plural|%d дня|%d дней|%d дней}', $days);
    }

    public static function getDeleteConfirmHash($user, $oldSalt = false)
    {
        $salt = self::getSalt($oldSalt ? self::SALT_TYPE_COOKIE_OLD : self::SALT_TYPE_COOKIE);
        return md5($user['user_inserted'] . $salt);
    }

    public static function processDelayedDelete()
    {
        $db = Base_Context::getInstance()->getDbConnection();
        $dbUser = new Db_User();
        $query = '
                SELECT user_id, reason
                FROM
                    user_delete_request
                WHERE
                    DATE_SUB(NOW(), INTERVAL 30 DAY) > request_time
                LIMIT 200';
        $usersData = $db->fetchAssoc($query, __METHOD__);
        if ($usersData) {
            $userIds = Base_Util_Array::extract($usersData, 'user_id');
            $usersToDelete = Base_Dao_User::getUsersByIds($userIds);
            foreach ($usersToDelete as $userToDelete) {
            	if ($userToDelete->isHidden() != Db_Moders::DELAYED_DELETED) {
            		continue;
            	}
                $reason = isset($usersData[$userToDelete->getId()]) ? $usersData[$userToDelete->getId()]['reason'] : '';
                $dbUser->deleteUser($userToDelete, $reason);
                $userToDelete->getNativeProject()->getStatisticClient()->increment($userToDelete, 'delete_user', 1);

                // @analytics stats
                $analytics = new Base_Service_Counter_Analytics();
                $analytics->increment($userToDelete, 'delete_user', 1);

                sleep(1);
            }
            $db->delete('user_delete_request', $db->qq('user_id IN ('.join(',',$userIds).')'), __METHOD__);
        }

        // предупреждаем тех, кому осталось 3 дня до удаления
        $query = '
                SELECT user_id
                FROM
                    user_delete_request
                WHERE
                    DATE_SUB(NOW(), INTERVAL 60*24*27-10 MINUTE) > request_time
                    AND DATE_SUB(NOW(), INTERVAL 60*24*27 MINUTE) <= request_time';
        $usersToNotifyIds = $db->fetchCol($query, __METHOD__);
        if ($usersToNotifyIds) {
            $usersToNotify = Base_Dao_User::getUsersByIds($usersToNotifyIds);
            foreach ($usersToNotify as $userToNotify) {
                if ($userToNotify->isHidden() != Db_Moders::DELAYED_DELETED || !$userToNotify->isEmailSet() ) {
            		continue;
            	}

                /* @var $userToNotify Base_Model_User */
                Base_Mailer_Base::addImmediateMail($userToNotify, Base_Mailer_NewTypes::TYPE_DELETE_NOTIFY, __METHOD__, array('subject' => _f('Удаление с {string}', $userToNotify->getNativeProject()->getTitle(2))));
            }
        }
    }

    /**
     * @param Base_Model_User $user
     * @param string          $wLetter
     * @param string          $mLetter
     *
     * @return string
     */
    public static function getSexLetter($user, $wLetter = 'а', $mLetter = '')
    {
        return $user->isFemale() ? $wLetter : $mLetter;
    }

    /**
     * Проверяет, кто из списка айдишников онлайн
     *
     * @param array $ids         массив айдишников пользователй для проверки
     * @param bool  $keepOffline
     *
     * @return array|bool массив с ключами $ids и значениями 1|0 (онлайн/офлайн)
     */
    public static function getAutoOnlineUsers(array $ids, $keepOffline = true)
    {
        if(!is_array($ids)){
            return false;
        }
        $db = Base_Context::getInstance()->getDbConnection();
        $select = $db->select()->from('auto_online','user_id')->where('user_id IN (?)', $ids);
        $result = $db->fetchCol($select, __METHOD__);
        $online = $keepOffline ? array_fill_keys($ids, 0) : array();

        foreach($result as $id){
            $online[$id]=1;
        }
        return $online;
    }

    /**
     * По прошествию 4-х дней переносятся все пользователи кроме:
     * 1) забанен и не имеет фин активности
     * 2) не имеет подтвержденного е-мела
     * в таблицу user. Остальные пользователи удаляются.
     */
    public static function moveNewUsers()
    {
        $date4Day = date('Y-m-d H:i:s', time() - 86400 * 4);

        /**
         * Если мы в окружении для тестирования, необходимо
         * скопировать пользователей которые были добавлены через modelFactory
         */
        if (defined('TESTING') && constant('TESTING') === true) {
//            $whereNotActive = "user_email = '".Db_User::DEFAULT_EMAIL."'";
//            $whereActive = "user_email != '".Db_User::DEFAULT_EMAIL."'";
            $whereActive = "1=1";
        } else {
//            $whereNotActive = "user_inserted < '$date4Day' AND user_email = '".Db_User::DEFAULT_EMAIL."'";
//            $whereActive = "user_inserted < '$date4Day' AND user_email != '".Db_User::DEFAULT_EMAIL."'";
            $whereActive = "user_inserted < '$date4Day'";
        }

        /* ---------------------------------------------------------------------------------------------------------- */

        $db = Base_Context::getInstance()->getDbConnection();

        /* $countNew = (int) $db->fetchOne("SELECT COUNT(*) FROM `user_new` WHERE $whereActive", __METHOD__);
        $countBefore = (int) $db->fetchOne("SELECT COUNT(*) FROM `user`", __METHOD__);
        $query = "INSERT INTO `user` (SELECT * FROM `user_new` WHERE $whereActive) ON DUPLICATE KEY UPDATE user.user_email = user_new.user_email, user.user_is_hidden = user_new.user_is_hidden, user.user_not_activated = user_new.user_not_activated";
        $db->writequery('user', $query, __METHOD__);

        $countAfter = $db->fetchOneMaster("SELECT COUNT(*) FROM `user`", __METHOD__);
        if ($countAfter >= $countBefore + $countNew) {
            $queryDelete = "DELETE FROM `user_new` WHERE $whereActive";
            Driver_Db::writequery('user_new', $queryDelete, __METHOD__);
        }*/

        // получим активных пользователей, для переноса
        $activeIds = $db->fetchCol("SELECT `user_id` FROM `user_new` WHERE $whereActive", __METHOD__);
        $activeTotal = count($activeIds);

        foreach (array_chunk($activeIds, 5000) as $idsPack) {

            $activeIdsImploded = implode(', ', $idsPack);

            // перенесем их в `users` табличку
            $query = "
                INSERT INTO `user` (SELECT * FROM `user_new` WHERE `user_id` IN($activeIdsImploded))
                ON DUPLICATE KEY UPDATE
                    `user`.`user_email` = `user_new`.`user_email`,
                    `user`.`user_is_hidden` = `user_new`.`user_is_hidden`,
                    `user`.`user_not_activated` = `user_new`.`user_not_activated`
            ";
            $db->writequery('user', $query, __METHOD__);

            // уберем перенесенных из `user_new`
            $db->writequery('user_new', "DELETE FROM `user_new` WHERE  `user_id` IN($activeIdsImploded)", __METHOD__);
        }
        unset($activeIds, $activeIdsImploded);


        /* ---------------------------------------------------------------------------------------------------------- */

        // полюбому удаляем, тех, кто были незабанены :)
        $dbUser = new Db_User();
        $dbUser->getTb(1, true); // обновляем кеш по ид на данном сервере.
        $usersToDelete = array(); // $db->fetchAll($db->select()->from('user_new')->where($whereNotActive), __METHOD__);
        $phonesToBlacklist = array();
        
//        foreach ($usersToDelete as $user) {
//            // забанен, не платил, номер телефона активирован
////            if($user['user_is_hidden'] >= 1  && $user['user_cash'] === null && $user['user_phone_approved'] == 1 && $user['user_phone']){
////                $phonesToBlacklist[] = $user['user_phone'];
////            }
//
//            /**
//             * Слишком долго выполняется в тестах
//             */
//            if (!defined('TESTING')) {
//                $dbUser->deleteUser($user, "not-active-4day");
//            }
//        }
//
//        // добавляем телефоны в blacklist. Отключено по просьбе Германова Евгения.
////        $blacklistDao = Antifraud_Dao_Blacklist::getInstance();
////        foreach($phonesToBlacklist as $phone){
////            $blacklistDao->addToBlackList(Antifraud_Dao_Blacklist::TYPE_PHONE, $phone, 'not-active-4day-banned');
////        }
//
//        $dbUser->getTb(1, true);

        return array(
            'transferred'       => $activeTotal, //$countNew,
            'deleted'           => count($usersToDelete),
            'phonesToBlacklist' => count($phonesToBlacklist)
        );
    }
    /**
     * @depracated - оставлю для истории. Сейчас этот крон работает в демоне. Код портировали с небольшими измениями.
     *
     * Обновления онлайн. Запускается раз в 1 минуту.
     *
     * Как эта хренота работает:
     *  - забираем юзеров из SQ, туда записываются из разных мест массивчики вида
         array(
            'user_id' => id юзера, который произвёл действие,
            'time'    => время, когда это действие было совершено,
            'r_id'    => реферрер - откуда пришёл юзер,
            'r_data'  => что-то (не вкурсе, присутствовало в старом кроне),
            'src_id'  => сурс - с какого типа ФС юзер вышел онлайн (FS, FSm, Mobile version, iPhone app, ...)
         );
     *  - группируем эти действия по user_id и src_id
     *  - берём время последнего действия для каждого типа
     *  - достаём все записи из auto_online
     *  - путём нехитрых манипуляций вычисляем по time_update юзеров, которые проэкспайрились
     *  - исходя из данных из SQ определяем юзеров которые только что стали онлайн (тоесть отсутствуют в auto_online)
     *    и тех, кто уже был онлайн на момент запуска крона (присутствуют в auto_online)
     *  - и затем апдейтим группы этих юзеров
     *    ... В общем коллективный разум нам подсказал сложную и интересную реализацию онлайнов.
     *    В табличках user и user_new у нас есть (появилось) поле online_mask, которое содержит в себе побитовую маску
     *      сурсов, в которых юзер онлайн/оффлайн. Битики лежат в константах этого класса (с префиксом SOURCE_*).
     *      Масочка может принимать значение 0 - когда юзер вообще оффлайн или иметь установленные битики онлайн/эвэй для каждого их сурсов.
     *      Под каждый сурс занято 2 бита - бит online и бит away (это когда юзер ещё где-то онлайн, но по данному сурсу уже оффлайн),
     *      выглядит это как-то так (на момент запуска - 16 бит, определяется константой ONLINE_BIT_SHIFT):
     *      |0|0|0|0|0|0|0|0|
     *      |   |   |   | FS: away, online
     *      |   |   | FSm: away, online
     *      |   | Mobile: away, online
     *      | iPhone: away, online
     *      Если юзер не имеет ни одного установленного бита online - ему вставляется 0.
     *      Апдейтится эта штука так: создаётся маска, имеющая 32 бит:
     *          16 младших бит - это те биты, которые мы хотим сбросить
     *          16 старших бит - это те биты, которые мы хотим поставить
     *      Весь апдейт происходит сбрасыванием полной маски, и установкой со сдвигом маски на 16 бит вправо
     *      Собственно вот :)
     *  - и соответственно апдейтим мемкэш для этих юзеров
     *  - удаляем юзеров из auto_online
     *  Тут ещё есть немного кода, который был перенесён из старого крона, и коммент соответсвенно для него:
     *    в) статистика логинов для Gameleads
     *
     */
    public static function updateAutoOnline($qnum)
    {
        $timeNow = time();
        $currDayTS = strtotime(date('Y-m-d 00:00:00'));
        $prevDayTS = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));

        // забираем все из очереди
        if (PRODUCTION) {
            $queueNum = ($qnum == 1) ? Base_Service_SharedQueue::INDEX_ONLINE : Base_Service_SharedQueue::INDEX_ONLINE_EVEN;
            $sqData = Base_Service_SharedQueue::popAll($queueNum);
        } else {
            if ($qnum != 1) {
                return true;
            }
            $sqData = array_merge(
                Base_Service_SharedQueue::popAll(Base_Service_SharedQueue::INDEX_ONLINE),
                Base_Service_SharedQueue::popAll(Base_Service_SharedQueue::INDEX_ONLINE_EVEN)
            );
        }

        // берём время последнего действия с группировкой по юзеру и сурсу
        $byUserSrc = array();
        $usersLocation = array();
        /*$loggedOutUsers = array();*/

        foreach ($sqData as $k => $item) {
            if (!empty($item['from_logout'])) {
                /*$loggedOutUsers[$item['user_id']] = 1;*/
                continue;
            }
            // временная затычка, на время перехода со старого крона на новый
            if (!isset($item['src_id'])) {
                $item['src_id'] = self::SOURCE_FS_ONLINE;
            }

            /* Example
            $item = array(
                'user_id' => $userId,
                'time'    => $timeNow,
                'r_id'    => $referrerId,
                'r_data'  => $returnData,
                'src_id'  => $sourceId
            );
            */

            // отсеиваем события старше таймаута для данного сурса (ибо нахх не нужны)
            if (($timeNow - $item['time']) > Base_Service_User::$onlineAwayTimeout[$item['src_id']]) {
                continue;
            }

            //Сохраним ip и время для обновления счетчиков логинов
            if (isset($item['long_ip'])) {
                $usersLocation[$item['user_id']] = $item['long_ip'];
            }

            if (!isset($byUserSrc[$item['user_id']])) {
                $byUserSrc[$item['user_id']] = array();
            } elseif (!isset($byUserSrc[$item['user_id']][$item['src_id']])) {
                $byUserSrc[$item['user_id']][$item['src_id']] = array();
            }

            // сделаем ссылочку, для более удобной работы с элементом массива
            $link = &$byUserSrc[$item['user_id']][$item['src_id']];

            // обновляем время последнего действия
            if (!isset($link['time']) || $item['time'] > $link['time']) {
                $link['time'] = $item['time'];
            }

            // Записываем только первый refererId для юзера
            if (!empty($item['r_id']) && !isset($link['r_id'])) {
                $link['r_id'] = $item['r_id'];
            }

            if (!empty($item['r_data']) && empty($link['r_data'])) {
                $link['r_data'] = $item['r_data'];
            }
            unset($link);
        }
        unset($sqData);

        $_new = $_up = $_off = array();

        // выбираем пользователей которые были онлайн
        $autoOnlineByUser = array();
        $db = Base_Context::getInstance()->getDbConnection();
        $autoOnlineData = $db->selectAll('auto_online', "SELECT user_id, source_id, time_update, time_in FROM auto_online", __METHOD__);
        foreach ($autoOnlineData as $k => $row) {
            // фильтруем чётные / нечётные
            if (PRODUCTION && (($qnum == 1 && $row['user_id'] % 2 == 0) || ($qnum == 2 && $row['user_id'] % 2 != 0))) {
                continue;
            }
            $timeOut = Base_Service_User::$onlineAwayTimeout[$row['source_id']];
            if (!isset($autoOnlineByUser[$row['user_id']])) {
                $autoOnlineByUser[$row['user_id']] = array();
            }
            $autoOnlineByUser[$row['user_id']][$row['source_id']] = array(
                'time_update' => $row['time_update'],
                'time_in'     => $row['time_in']
            );

            /* Вынес условия в переменные, иначе код нечитаемый */

            // если юзер проэкспайрился по табличке
            $dbTimeout = ($timeNow - $row['time_update']) > $timeOut;
            // отсутствует в SQ или имеет в SQ проэкспайреное время
            $sqTimeout = (!isset($byUserSrc[$row['user_id']][$row['source_id']]) || ($timeNow - $byUserSrc[$row['user_id']][$row['source_id']]['time']) > self::ONLINE_TIMEOUT_UPDATE);
            // если юзер олайн больше чем максимальное время онлайна
            $maxTimeout = ($timeNow - $row['time_in']) > self::ONLINE_MAX_ONLINE_TIME;

            if (($dbTimeout && $sqTimeout) || $maxTimeout/* || isset($loggedOutUsers[$row['user_id']])*/) {
                if (!isset($_off[$row['user_id']])) {
                    $_off[$row['user_id']] = array();
                }
                /*$_off[$row['user_id']][$row['source_id']] = array();*/
                $_off[$row['user_id']][$row['source_id']] = (int) $row['user_id'];

                // удалим из массива, дабы не занести эту пару (юзер-тип) в другую группу (up, new)
                unset($byUserSrc[$row['user_id']][$row['source_id']]);
                if (empty($byUserSrc[$row['user_id']])) {
                    unset($byUserSrc[$row['user_id']]);
                }
            }
        }
        unset($autoOnlineData);

        // Ид источника, с которого пришел юзер
        $refererIds = $returnData = array();

        // выбираем юзеров, которые сейчас онлайн
        foreach ($byUserSrc as $userId => $sources) {
            foreach ($sources as $srcId => $data) {
                // юзеры, которые есть в табличке - up'ы
                if (isset($autoOnlineByUser[$userId][$srcId])) {
                    // если обновление было больше чем 3 минуты назад
                    /* Не трогать этот IF, блеать! */
                    if (($timeNow - $autoOnlineByUser[$userId][$srcId]['time_update']) < self::ONLINE_TIMEOUT_UPDATE) {
                        continue;
                    }
                    if (!isset($_up[$userId])) {
                        $_up[$userId] = array();
                    }
                    /*$_up[$userId][$srcId] = array();*/
                    $_up[$userId][$srcId] = $userId;
                // юзеры, которых нет в табличке - new
                } else {
                    if (!isset($_new[$userId])) {
                        $_new[$userId] = array();
                    }
                    /*$_new[$userId][$srcId] = array();*/
                    $_new[$userId][$srcId] = $userId;
                }

                // Взято со старого крона, для совместимости
                if ($srcId == self::SOURCE_FS_ONLINE) {
                    // Записываем только первый refererId для юзера
                    if (isset($data['r_id'])) {
                        $refererIds[$userId] = $data['r_id'];
                    }

                    if (isset($data['r_data'])) {
                        $returnData[$userId] = $data['r_data'];
                    }
                }
            }
        }
        unset($byUserSrc);

        // Переназначаем переменные для совместимости с пересчётом старых статистик и прочего и прочего и прочего...
        // при этом учитываем только онлайны с сайта (SOURCE_FS_ONLINE)
        $new = self::extractOnlinersBySrc($_new, self::SOURCE_FS_ONLINE);
        $off = self::extractOnlinersBySrc($_off, self::SOURCE_FS_ONLINE);
        $up  = self::extractOnlinersBySrc($_up, self::SOURCE_FS_ONLINE);

        $newMobileUsers = self::extractOnlinersBySrc($_new, self::SOURCE_MOBILE_ONLINE);
        $offMobileUsers = self::extractOnlinersBySrc($_off, self::SOURCE_MOBILE_ONLINE);
        //$upMobileUsers  = self::extractOnlinersBySrc($_up, self::SOURCE_MOBILE_ONLINE);

        // достаём модельки юзеров для каждого типа
        $newUsers = empty($new) ? array() : Base_Dao_User::getUsersByIds($new);
        $offUsers = empty($off) ? array() : Base_Dao_User::getUsersByIds($off);
        $upUsers  = empty($up)  ? array() : Base_Dao_User::getUsersByIds($up);
        //$upMoibleUsers  = empty($upMobileUsers)  ? array() : Base_Dao_User::getUsersByIds($upMobileUsers);

        // Create memory table
        $oldOnline = false;

        $createTmpTable = !empty($_off) || !empty($_new);

        if ($createTmpTable) {
            self::createTmpTableForMasks();
        }

        $mcMaskOffUpdate = array();
        // обработка полученных массивов
        if (!empty($_off)) {
            self::makeUsersOffline($_off, $mcMaskOffUpdate/*passed by link*/);
        }
        if (!empty($_up)) {
            self::updateOnlineUsers($_up);
        }
        if (!empty($_new)) {
            $oldOnline = self::makeUsersOnline($_new, $mcMaskOffUpdate/*passed by link*/);
        }

        if (!empty($mcMaskOffUpdate)) {
            // Апдейтим мемкэш для офлайнов
            Db_User::updateMemcacheOnlineMask($mcMaskOffUpdate/*, array('time_out' => $timeNow)*/);
            $toTmpInsert = array();
            foreach ($mcMaskOffUpdate as $userId => $bitMask) {
                $toTmpInsert[] = '(' . (int) $userId . ', ' . $bitMask . ')';
            }
            if (!empty($toTmpInsert)) {
                $db->writequery('tmp_online_masks', 'INSERT INTO `tmp_online_masks` (`user_id`, `new_mask`) VALUES ' . implode(', ', $toTmpInsert), __METHOD__);
            }
        }

        // if we've some data in tmp table
        if ($createTmpTable) {
            $fullOnlineMask = self::getAllOnlineMask();
            $bitmaskUpdate  = '(`u`.`online_mask` & ~`tom`.`new_mask` | `tom`.`new_mask` >> ' . self::ONLINE_BIT_SHIFT . ')';

            // Апдейтим user
            $query = 'UPDATE `user` AS `u`, `tmp_online_masks` AS `tom` SET
                          `u`.`online_mask` = IF(' . $bitmaskUpdate . ' & ' . $fullOnlineMask . ' > 0,
                                                 ' . $bitmaskUpdate . ',
                                                 0
                                              ),
                          `u`.`time_in` =     IF(`u`.`time_out` > 0,
                                                 ' .  $timeNow . ',
                                                 `u`.`time_in`
                                              ),
                          `u`.`time_out` =    IF(' . $bitmaskUpdate . ' & ' . $fullOnlineMask . ' > 0,
                                                 0,
                                                 ' .  $timeNow . '
                                              )
                      WHERE `u`.`user_id` = `tom`.`user_id`';
            $db->writequery('user', $query, __METHOD__);

            // Апдейтим user_new
            $query = 'UPDATE `user_new` AS `u`, `tmp_online_masks` AS `tom` SET
                          `u`.`online_mask` = IF(' . $bitmaskUpdate . ' & ' . $fullOnlineMask . ' > 0,
                                                 ' . $bitmaskUpdate . ',
                                                 0
                                              ),
                          `u`.`time_in` =     IF(`u`.`time_out` > 0,
                                                 ' .  $timeNow . ',
                                                 `u`.`time_in`
                                              ),
                          `u`.`time_out` =    IF(' . $bitmaskUpdate . ' & ' . $fullOnlineMask . ' > 0,
                                                 0,
                                                 ' .  $timeNow . '
                                              )
                      WHERE `u`.`user_id` = `tom`.`user_id`';
            $db->writequery('user_new', $query, __METHOD__);
        }

        $online = self::extractOnlinersBySrc($autoOnlineByUser, self::SOURCE_FS_ONLINE, true);
        foreach ($online as &$data) {
            $data = array_values($data);
        }
        unset($data, $autoOnlineByUser);
        /* Ниже идёт код, перенесённый из старого крона */

        self::fillOnlineHistory($online, $up, 300, self::ONLINE_TIMEOUT_UPDATE);

        // обновляем онлайн во всех базах
        self::OnlineReplica(array_keys($_new), array_keys($_off), 86400, array('database_heap','fotodb3','database'));

        // Чистимся слегка
        unset($_new, $_off, $_up);

        //для добавлений акции пользователям
        if ($new && Invites_Service_Vip::isAvailable()) {
            Invites_Service_Vip::pushOnlineUsersForCron($newUsers);
        }

        //вайпаем лишние локейшены
        $_tmpLocations = array();

        foreach($new as $userId){
            if(isset($usersLocation[$userId])) {
                $_tmpLocations[$userId] = $usersLocation[$userId];
            }
        }

        $usersLocation = $_tmpLocations;
        unset($_tmpLocations);

        // обработчики онлайн от пользователя
        self::onOnline($new, $oldOnline, $returnData, $usersLocation);
        self::onOffline($off, $offUsers, $online);

        $fsmessengerPush = new Fsmessenger_Service_DaemonPush;
        $fsmessengerPush->pushOnlineChanges($new, $off);
        $fsmessengerPush->pushOnlineChanges($newMobileUsers, $offMobileUsers, 'mob_');

        Partner_Service_Gameleads::traceAuth($newUsers);

        $analytics = Base_Service_Counter_Analytics::getInstance();
        $involvement = Base_Service_Counter_Involvement::getInstance();

        // new users process
        foreach ($newUsers as $value) { /** @var $value Base_Model_User */

            $userLastVisit = $value->getLastVisit();
            $refererId = isset($refererIds[$value->getId()]) ? $refererIds[$value->getId()] : null;
            Base_Service_Counter_Stats::incrementLogin($value['time_out'], $value, $refererId);

            // Считаем тех, кто был вчера и впервые зашёл сегодня (то есть сколько сегодня зашло тех, кто был на сайте вчера)
            //if ($value->isPetAppUser() && strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))) <= $value->getLastVisit() && $value->getLastVisit() < strtotime(date('Y-m-d 00:00:00'))) {
            if ($value->isPetAppUser() && $prevDayTS <= $userLastVisit && $userLastVisit < $currDayTS) {
                $stats = $value->getNativeProject()->getStatisticClient();
                $stats->increment($value, 'login_return');
            }

            // @analytics stats
            $analytics->increment($value, 'sessions_count', 1);
            Base_Service_Counter_Analytics::incrementAnalytics($value, 'new_sessions_count');

            if ($value->hasPet()){
                $analytics->increment($value, 'pet_session_count', 1);
            }

            if ($value->getCash() > 0 && $userLastVisit < $currDayTS) {
                $analytics->increment($value, 'cash_rest', $value->getCash() * 100);
                //инкремент статсы биллинга остатки на счетах
                $billingStats = Base_Service_Counter_Billing::getInstance(); /** @var Base_Service_Counter_Billing $billingStats */
                $billingStats->incrementCashRest($value, $billingStats::SERVICE_ID_ALL, $value->getCash() * 100, null, Base_Service_Counter_Billing::BILLING_ID_ALL);
            }

            $activityDay = Base_Service_Counter_Analytics::isNewbie($value);
            if ($activityDay) { /** @var $value Base_Model_User */
                // был вчера и вернулся сегодня
                if ($userLastVisit >= $prevDayTS && $userLastVisit < $currDayTS) {
                    Base_Service_Counter_Analytics::incrementAnalytics($value, 'new_retention_1day');
                }
                if ($activityDay > 1 && $userLastVisit < $currDayTS) {
                    Base_Service_Counter_Analytics::incrementAnalytics($value, 'new_return_' . $activityDay . 'day');
                }
            }
        }

        // up users process
        foreach ($upUsers as $value) {
            if (!isset($online[$value->getId()][0])) {
                continue;
            }

            // temporary log
            if ($value->getCash() < 0) {
                Base_Service_Log::log('wrong_user_balance', array('old', $value->getId(), $value->getCash()));
            }

            $refererId = isset($refererIds[$value->getId()]) ? $refererIds[$value->getId()] : null;
            Base_Service_Counter_Stats::incrementLogin($online[$value['user_id']][0], $value, $refererId);
        }

        // up mobile users process
        //$hourNow = strtotime(date('Y-m-d H:00:00'));
        //foreach ($upMoibleUsers as $value) {
        //    if (isset($online[$value['user_id']][0])
        //    &&  ($online[$value['user_id']][0] < $hourNow)
        //    ) {
        //        Base_Service_Counter_Main::getInstance()->increment($value, 'traffic_m_login_hour');
        //    }
        //}

        // off users process
        foreach ($offUsers as $oUser) { /** @var $oUser Base_Model_User */

            if (empty($online[$oUser->getId()])) {
                continue;
            }

            // общее время сессии
            $minutes = round(($online[$oUser->getId()][0] - $online[$oUser->getId()][1]) / 60);

            if ($minutes) {
                // тест - посчитаем разницу времении сессии, если ботов не отсеивать
                $analytics->increment($oUser, 'sessions_time_botfriendly', $minutes);
            }
            if ($minutes && $minutes < 3 * 3600) {
                $analytics->increment($oUser, 'sessions_time', $minutes);
                Base_Service_Counter_Analytics::incrementAnalytics($oUser, 'new_sessions_time', $minutes);

                // определять, была ли эта сессия первой для человека
                if (!$oUser->get('time_out')) {
                    $analytics->increment($oUser, 'first_session_time', $minutes);
                }
                if (Base_Service_User::isFirstSession($oUser)) {
                    $analytics->increment($oUser, 'socialnetwork_1session_time', $minutes);
                }
                if ($oUser->hasPet()){ // статистика сессий петов
                    $analytics->increment($oUser, 'pet_session_time', $minutes);
                }

                $involvement->incrementStats($oUser, Base_Service_Counter_Involvement::FIELD_SESSION_TIME, $minutes);

                // Статистикапо трафику партнёров
                Base_Service_Counter_Stats::incrementPartnerTrafficStats($oUser, 'sess_time', $minutes);
            }

            // считаем первую сессию для приглешенных пользователей
            if (Base_Service_User::isFirstSession($oUser)) {
                Invites_Service_Stats::countUserFirstSession($oUser, $minutes);
            }

            // заполняем очередь для проверки возвращаемости
            $dateInsertedTS = strtotime($oUser->getDateInserted());
            if ($oUser->isEmailApproved() && $timeNow-86400*7 <= $dateInsertedTS && $timeNow-86400*1 >= $dateInsertedTS) {
                Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_USERRETURN_CHECK, $oUser->getId());
            }
        }

        Usercontact_Dao_Talk2::saveOnlineOffline($new, $off);

        return 'online=' . count($new) . '; refresh=' . count($up) . '; offline=' . count($off);
    }

    /**
     * Обновление статистики по крону онлайн. Выполняется раз в минуту.
     * Вся логика вынесена в демон, подсчёт статсы работает так же как в updateAutoOnline
     * @static
     * @return string
     */
    public static function updateAutoOnlineDaemon()
    {
        $timeNow = time();
        $currDayTS = strtotime(date('Y-m-d 00:00:00'));
        $prevDayTS = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));

        if (PRODUCTION) {
            $sqData = Base_Service_SharedQueue::popAll(Base_Service_SharedQueue::INDEX_CRON_ONLINE_STATISTIC);
        } else {
            $sqData = array ( 0 => array ( 2048 => array ( 1 => array ( 'prev_time_out' => 1348073242, 'r_data' => '', 'r_id' => 0, 'status' => 1, 'time_in' => 0, 'time_update' => 0, ), ), ), 1 => array ( 60713389 => array ( 1 => array ( 'prev_time_out' => 0, 'r_data' => '', 'r_id' => 0, 'status' => 2, 'time_in' => 1348048112, 'time_update' => 1348076160, ), ), ), );
        }

        if (empty($sqData)) {
            return 'No status changes from daemon received';
        }

        $analytics   = Base_Service_Counter_Analytics::getInstance();
        $involvement = Base_Service_Counter_Involvement::getInstance();

        $counters = array(
            self::ONLINE_STATUS_ONLINE  => 0,
            self::ONLINE_STATUS_UPDATE  => 0,
            self::ONLINE_STATUS_OFFLINE => 0
        );

        foreach ($sqData as $k => $item) {
            if (empty($item)) {
                continue;
            }
            /*
            'userId1' => array(
                ONLINE_SOURCE_FS => array(
                    'status' => ONLINE_STATUS_ONLINE,
                    'r_id'   => '...',
                    'r_data' => '...',
                    'time_in' => 0,
                    'time_update' => 0,
                    'prev_time_out' => 0,
                ),
                ...
            ),
            */
            $userModels = Base_Dao_User::getUsersByIds(array_keys($item));
            foreach ($item as $userId => $onlineInfo) {
                if (!isset($userModels[$userId])) {
                    continue;
                }
                /* @var $userModel Base_Model_User */
                $userModel = $userModels[$userId];

                foreach ($onlineInfo as $source => $sourceInfo) {
                    // Предыдущее время выхода, у нас получается из демона
                    $userModel->set('time_out', $sourceInfo['prev_time_out']);
                    ++$counters[$sourceInfo['status']];
                    switch ($source) {
                        case self::ONLINE_SOURCE_FS:
                            switch ($sourceInfo['status']) {
                                // increment online statuses
                                case self::ONLINE_STATUS_ONLINE:
                                    $userLastVisit = $userModel->getLastVisit();
                                    $refererId = empty($sourceInfo['r_id']) ? null : $sourceInfo['r_id'];
                                    Base_Service_Counter_Stats::incrementLogin($userModel->get('time_out'), $userModel, $refererId);

                                    // Считаем тех, кто был вчера и впервые зашёл сегодня (то есть сколько сегодня зашло тех, кто был на сайте вчера)
                                    //if ($value->isPetAppUser() && strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))) <= $value->getLastVisit() && $value->getLastVisit() < strtotime(date('Y-m-d 00:00:00'))) {
                                    if ($userModel->isPetAppUser() && $prevDayTS <= $userLastVisit && $userLastVisit < $currDayTS) {
                                        $stats = $userModel->getNativeProject()->getStatisticClient();
                                        $stats->increment($userModel, 'login_return');
                                    }

                                    // Возвраты виральных пользователей в первую неделю (но не в день регистрации)
                                    if(Base_Service_UserSource::isMailInvitedUser($userModel)
                                        && $userModel->getDaysRegistered() <= 7
                                        && date('Y-m-d', strtotime($userModel->getDateInserted())) != date('Y-m-d')) {
                                        $client = Base_Service_Counter_Invites::getInstance();
                                        $client->increment($userModel, 'first_week_return');
                                    }

                                    // @analytics stats
                                    $analytics->increment($userModel, 'sessions_count', 1);
                                    Base_Service_Counter_Analytics::incrementAnalytics($userModel, 'new_sessions_count');

                                    if ($userModel->hasPet()){
                                        $analytics->increment($userModel, 'pet_session_count', 1);
                                    }

                                    if ($userModel->getCash() > 0 && $userLastVisit < $currDayTS) {
                                        $analytics->increment($userModel, 'cash_rest', $userModel->getCash() * 100);
                                        //инкремент статсы биллинга остатки на счетах
                                        $billingStats = Base_Service_Counter_Billing::getInstance(); /** @var Base_Service_Counter_Billing $billingStats */
                                        $billingStats->incrementCashRest($userModel, $billingStats::SERVICE_ID_ALL, $userModel->getCash() * 100, null, Base_Service_Counter_Billing::BILLING_ID_ALL);
                                    }

                                    $activityDay = Base_Service_Counter_Analytics::isNewbie($userModel);
                                    if ($activityDay) {
                                        // был вчера и вернулся сегодня
                                        if ($userLastVisit >= $prevDayTS && $userLastVisit < $currDayTS) {
                                            Base_Service_Counter_Analytics::incrementAnalytics($userModel, 'new_retention_1day');
                                        }
                                        if ($activityDay > 1 && $userLastVisit < $currDayTS) {
                                            Base_Service_Counter_Analytics::incrementAnalytics($userModel, 'new_return_' . $activityDay . 'day');
                                        }
                                    }
                                break;
                                // increment status updates
                                case self::ONLINE_STATUS_UPDATE:
                                    if (empty($sourceInfo['time_update'])) {
                                        continue;
                                    }
                                    $refererId = empty($sourceInfo['r_id']) ? null : $sourceInfo['r_id'];
                                    Base_Service_Counter_Stats::incrementLogin($sourceInfo['time_update'], $userModel, $refererId);
                                break;
                                // increment offline statuses
                                case self::ONLINE_STATUS_OFFLINE:
                                    // общее время сессии
                                    $minutes = round(($sourceInfo['time_update'] - $sourceInfo['time_in']) / 60);

                                    if ($minutes) {
                                        // тест - посчитаем разницу времении сессии, если ботов не отсеивать
                                        $analytics->increment($userModel, 'sessions_time_botfriendly', $minutes);
                                    }
                                    if ($minutes && $minutes < 3 * 3600) {
                                        $analytics->increment($userModel, 'sessions_time', $minutes);
                                        Base_Service_Counter_Analytics::incrementAnalytics($userModel, 'new_sessions_time', $minutes);

                                        // определять, была ли эта сессия первой для человека
                                        if (!$userModel->get('time_out')) {
                                            $analytics->increment($userModel, 'first_session_time', $minutes);
                                        }
                                        if (Base_Service_User::isFirstSession($userModel)) {
                                            $analytics->increment($userModel, 'socialnetwork_1session_time', $minutes);
                                        }
                                        if ($userModel->hasPet()){ // статистика сессий петов
                                            $analytics->increment($userModel, 'pet_session_time', $minutes);
                                        }

                                        $involvement->incrementStats($userModel, Base_Service_Counter_Involvement::FIELD_SESSION_TIME, $minutes);

                                        // Статистикапо трафику партнёров
                                        Base_Service_Counter_Stats::incrementPartnerTrafficStats($userModel, 'sess_time', $minutes);
                                    }

                                    // заполняем очередь для проверки возвращаемости
                                    $dateInsertedTS = strtotime($userModel->getDateInserted());
                                    if ($userModel->isEmailApproved() && $timeNow-86400*7 <= $dateInsertedTS && $timeNow-86400*1 >= $dateInsertedTS) {
                                        Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_USERRETURN_CHECK, $userModel->getId());
                                    }
                                break;
                                default:
                                    trigger_error('Unknown online status: ' . $sourceInfo['status'], E_WARNING);
                            }
                        break;
                        case self::ONLINE_SOURCE_IPHONE:
                        case self::ONLINE_SOURCE_MOBILE:
                        case self::ONLINE_SOURCE_FSM:
                            // skip other sources
                            continue;
                        break;
                        default:
                            trigger_error('Unknown online source: ' . $source, E_WARNING);
                    }
                }
            }
        }

        return 'online=' . $counters[self::ONLINE_STATUS_ONLINE]
             . '; refresh=' . $counters[self::ONLINE_STATUS_UPDATE]
             . '; offline=' . $counters[self::ONLINE_STATUS_OFFLINE];
    }


    private static function extractOnlinersBySrc($onliners, $src, $associative = false)
    {
        $return = array();
        foreach ($onliners as $userId => $sources) {
            foreach ($sources as $srcId => $data) {
                if ($srcId == $src) {
                    if ($associative) {
                        $return[$userId] = $data;
                    } else {
                        $return[] = $data;
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Returns away flag associated with online flag
     * @static
     * @param $onlineFlag
     * @return int
     */
    private static function getOppositeOnlineBit($bit)
    {
        switch ($bit) {
            // FS
            case self::SOURCE_FS_ONLINE:  return self::SOURCE_FS_AWAY;
            case self::SOURCE_FS_AWAY:    return self::SOURCE_FS_ONLINE;
            // FSm
            case self::SOURCE_FSM_ONLINE: return self::SOURCE_FSM_AWAY;
            case self::SOURCE_FSM_AWAY:   return self::SOURCE_FSM_ONLINE;
            // Mobile
            case self::SOURCE_MOBILE_ONLINE: return self::SOURCE_MOBILE_AWAY;
            case self::SOURCE_MOBILE_AWAY:   return self::SOURCE_MOBILE_ONLINE;
            // iPhone
            case self::SOURCE_IPHONE_ONLINE: return self::SOURCE_IPHONE_AWAY;
            case self::SOURCE_IPHONE_AWAY:   return self::SOURCE_IPHONE_ONLINE;
            // --
            default: return 0;
        }
    }

    private static function getFullOnlineMask($set)
    {
        return $set << self::ONLINE_BIT_SHIFT | $set | self::getOppositeOnlineBit($set);
    }

    public static function updateFullOnlineMask($mask, $resetMask)
    {
//        $resetMask = self::getFullOnlineMask($set);
        // Эта же штука выполняется в запросе
        return $mask & ~$resetMask | $resetMask >> self::ONLINE_BIT_SHIFT;
    }


    /**
     * New offline method
     * @static
     * @param $users
     * @return mixed
     */
    private static function makeUsersOffline($off, &$memcacheBitMask)
    {
        $db = Base_Context::getInstance()->getDbConnection();

        $toDelete = /*$toTmpInsert = */array();
        foreach ($off as $userId => $sources) {
            $bitMask = 0;
            foreach ($sources as $srcId => $data) {
                $toDelete[] = '(`user_id` = ' . (int) $userId . ' AND `source_id` = ' . (int) $srcId . ')';
                $bitMask ^= self::getFullOnlineMask(self::getOppositeOnlineBit($srcId)); // Prepare reset mask
            }

//            $toTmpInsert[] = '(' . (int) $userId . ', ' . $bitMask . ')';

            if (!isset($memcacheBitMask[$userId])) {
                $memcacheBitMask[$userId] = 0;
            }
            $memcacheBitMask[$userId] ^= $bitMask;

            unset($off[$userId]);
        }

        if (!empty($toDelete)) {
            // удаляем из auto_online
            $sql = 'DELETE FROM auto_online WHERE ' . implode(' OR ', $toDelete);
            $db->writequery('auto_online', $sql, __METHOD__);
        }

//        if (!empty($toTmpInsert)) {
//            $db->writequery('tmp_online_masks', 'INSERT INTO `tmp_online_masks` (`user_id`, `new_mask`) VALUES ' . implode(', ', $toTmpInsert), __METHOD__);
//        }

        return $memcacheBitMask;
    }

    private static function updateOnlineUsers($up)
    {
        $db = Base_Context::getInstance()->getDbConnection();

        $toUpdate = array();
        foreach ($up as $userId => $sources) {
            foreach ($sources as $srcId => $data) {
                $toUpdate[] = '(' . (int) $userId . ', ' . (int) $srcId . ', ' . TIME . ')';
            }
            unset($up[$userId]);
        }

        // Апдейтим auto_online
        $query = 'INSERT INTO `auto_online` (`user_id`, `source_id`, `time_update`)
                  VALUES ' . implode(', ', $toUpdate) . '
                  ON DUPLICATE KEY UPDATE `time_update` = ' . TIME;
        $db->writequery('auto_online', $query, __METHOD__);
    }

    private static function makeUsersOnline($on, &$memcacheBitMask)
    {
        $db = Base_Context::getInstance()->getDbConnection();
        $toInsert = $oldOnline = array();
//        $toTmpInsert = array();
        foreach ($on as $userId => $sources) {
            $bitMask = 0;
            foreach ($sources as $srcId => $data) {
                $toInsert[] = '(' . (int) $userId . ', ' . (int) $srcId . ', ' . TIME . ', ' . TIME . ')';
                $bitMask ^= self::getFullOnlineMask($srcId); // Prepare reset mask
            }

//            $toTmpInsert[] = '(' . (int) $userId . ', ' . $bitMask . ')';

            if (!isset($memcacheBitMask[$userId])) {
                $memcacheBitMask[$userId] = 0;
            }
            $memcacheBitMask[$userId] ^= $bitMask;

//            unset($on[$userId]);
        }

        if (!empty($toInsert)) {
            // Добавляем записи по юзерам в auto_online
            $sql = 'INSERT INTO `auto_online` (`user_id`, `source_id`, `time_in`, `time_update`)
                    VALUES ' . implode(', ', $toInsert) . '
                    ON DUPLICATE KEY UPDATE `time_update`= ' . TIME;
            $db->writequery('auto_online', $sql, __METHOD__);
        }

//        if (!empty($toTmpInsert)) {
//            $db->writequery('tmp_online_masks', 'INSERT INTO `tmp_online_masks` (`user_id`, `new_mask`) VALUES ' . implode(', ', $toTmpInsert), __METHOD__);
//        }

        $oldUsers = Db_User::getMemcachedUser(array_keys($on));
        foreach ($oldUsers as $userId => $userData) {
            if (isset($userData['time_in'])) {
                $oldOnline[$userId] = $userData['time_in'];
            }
        }
        unset($oldUsers);

        return $oldOnline;
    }

    public static function getAllOnlineMask()
    {
        $return = 0;
        foreach (self::$onlineAwayTimeout as $onlineBit => $time) {
            $return |= $onlineBit;
        }
        return $return;
    }

    public static function createTmpTableForMasks()
    {
        $db = Base_Context::getInstance()->getDbConnection();
        return $db->writequery('tmp_online_masks', '
                    DROP TABLE IF EXISTS `tmp_online_masks`;
                    CREATE TEMPORARY TABLE `tmp_online_masks` (
                      `user_id` INT UNSIGNED NOT NULL ,
                      `new_mask` INT UNSIGNED NOT NULL ,
                      PRIMARY KEY (`user_id`)
                    ) ENGINE = MEMORY;', __METHOD__);
    }

    public static function dropTmpTableForMasks()
    {
        $db = Base_Context::getInstance()->getDbConnection();
        return $db->writequery('tmp_online_masks', 'DROP TABLE `tmp_online_masks`', __METHOD__);
    }

    /**
     * Крон обнуления time_out и побитовых масок онлайна
     * Запускается каждый день в 2 часа ночи
     * Обнуляет time_out и маску тем юзерам, у которых time_out == 0 и time_in > time() + 60 * 24
     */
    public static function resetOnlineStatus()
    {
        $db = Base_Context::getInstance()->getDbConnection();
        // достать юзеров и таймин
        $rows = $db->selectAll('user', "SELECT `user_id`, `time_in` FROM `user` WHERE `user_is_hidden` IS NULL AND `time_out` = 0", __METHOD__);
        $toReset = array();
        foreach ($rows as $row) {
            if ((TIME - $row['time_in']) > self::ONLINE_MAX_ONLINE_TIME) {
                $toReset[] = (int) $row['user_id'];
            }
        }
        unset($rows);

        if (!empty($toReset)) {
            // обновить поля в юзерс
            $db->writequery('user', 'UPDATE `user` SET `time_out` = ' . TIME . ', `online_mask` = 0 WHERE `user_id` IN(' . implode(', ', $toReset) . ')', __METHOD__);
            // удалить из auto_online
            $db->writequery('auto_online', 'DELETE FROM `auto_online` WHERE `user_id` IN(' . implode(', ', $toReset) . ')', __METHOD__);

            Db_User::resetOnlineStatusMemcache($toReset);
        }
        return 'reseted=' . count($toReset);
    }

    public static function fireActiveUsersEvents()
    {
    	$alreadyFoundUsersKey = 'activeUserFlag';

    	$users = Base_Service_SharedQueue::popAll(Base_Service_SharedQueue::INDEX_USERRETURN_CHECK);
    	$users = array_unique($users);
    	$activeUsersCount = 0;
    	if ($users) {
    		$usersToIncrementIds = array();

    		$i = 0; $ch = 250;
    		while($usersPart=array_slice($users,$i,$ch)) {
    			$alreadyFoundUsersKeys = array();
	    		foreach($usersPart as $userId) {
	    			$alreadyFoundUsersKeys[] = array($alreadyFoundUsersKey, $userId);
	    		}
	    		$alreadyFoundUsers = Base_Service_Lemon::mget(__METHOD__, $alreadyFoundUsersKeys);
	    		if(!is_array($alreadyFoundUsers)) {
	    			// не получили ответа от Lemon
	    			return $activeUsersCount;
	    		}
		    	foreach($usersPart as $userId) {
					if (!isset($alreadyFoundUsers[$alreadyFoundUsersKey.':'.$userId])) {
						$usersToIncrementIds[] = $userId;
						Base_Service_Lemon::set(__METHOD__, $alreadyFoundUsersKey, $userId, 1, 86400*7);
					}
				}
				$i += $ch;
    		}

    		$usersToIncrement = Base_Dao_User::getUsersByIds($usersToIncrementIds);
    		if ($usersToIncrement) {
    			$statistics = new Base_Service_Counter_Main();
    			$statistics->increments($usersToIncrement, array('week_returned_user' => 1));

                // @analytics stats
                $analitycs = new Base_Service_Counter_Analytics();
                $analitycs->increments($usersToIncrement, array('new_week_returned_user' => 1));

    			foreach($usersToIncrement as $userToIncrement) {
    				if (!$userToIncrement) {
    					continue;
    				}
//	                $eventData = array(
//						'ref_id' => $userToIncrement->getRefId(),
//						'time' => strtotime($userToIncrement->getDateInserted()),
//						'reg' => Base_Service_Counter_Stats::definePeriodId($userToIncrement->getDateInserted()),
//						'track' => Userinfo_Dao_Base::getUserSubId(),
//	                	'ignore_track' => 0,
//	                    'source' => Base_Service_Counter_Stats::castPartnerEventSource($userToIncrement->getSource(), $userToIncrement->getDateInserted()),
//						'user_id' => $userToIncrement->getId(),
//						'data' => array(
//							'act_returned_user' => 1
//						),
//					);
//					Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_PARTNER_EVENT, $eventData);
                    Base_Service_Counter_Stats::incrementPartner(
                        $userToIncrement,
                        'new_week_returned_user',
                        1,
                        $dummy,
                        strtotime($userToIncrement->getDateInserted())
                    );
    			}
    			$activeUsersCount = count($usersToIncrement);
    		}
    	}
    	return $activeUsersCount;
    }

    /**
     * @static
     * @param array $userIds
     * @param array $oldOnline     - время последнего онлайна
     * @param array $returnData
     * @param array $usersLocation - массив вида: userId => longIp
     */
    private static function onOnline($userIds,$oldOnline,$returnData = array(), $usersLocation = array())
    {
        //обновляем статус в таблицах новых юзеров для гидов
        Support_Dao_Base::updateNewUsersOnlineStatus($userIds, 1);

        if ($userIds) {
            Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_ON_ONLINE_ACTION, array($userIds, $oldOnline, $returnData, $usersLocation), __METHOD__); // для всех, кто хочет что-то делать на onOnline, обрабатывается раз в минуту в self::cronProcessOnOnlineUsers
            // временно deprecated. временно!
            Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_ON_ONLINE_ACTION2, array($userIds, $oldOnline, $returnData, $usersLocation), __METHOD__); // второй крон на он-онлайн, только Новый Год
        }
    }

    private static function onOffline($userIds, $users, $online = array())
    {
        //обновляем статус в таблицах новых юзеров для гидов
        Support_Dao_Base::updateNewUsersOnlineStatus($userIds, 0);

        Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_ON_OFFLINE_ACTION, array($userIds, $online), __METHOD__); // для всех, кто хочет что-то делать на onOnline, обрабатывается раз в минуту в self::cronProcessOnOnlineUsers

        return true;
    }

    /**
    * тут обработка пользователей на onOnline. в self::onOnline они пихаются в очередь, разбираются тут раз в 1 минуту
    *
    */
    public static function cronProcessOnOnlineUsers()
    {
        $queueData = Base_Service_SharedQueue::popAll(Base_Service_SharedQueue::INDEX_ON_ONLINE_ACTION, __METHOD__);

        $userIds = $oldOnline = $returnData = $usersLocation = array();
        foreach ($queueData as $record) {
            $userIds = array_merge($userIds, $record[0]);
            if (is_array($record[1])) {
                $oldOnline = $oldOnline + $record[1];
            }
            if (is_array($record[2])) {
                $returnData = $returnData + $record[2];
            }
            //isset для старых очередей
            if(isset($record[3]) && is_array($record[3])){
                $usersLocation = $usersLocation + $record[3];
            }
        }
        $userIds = array_unique($userIds);

        // цепляемся сюда, чтобы добавить логины в стату пытавшихся удалиться
        Support_Dao_NotDeletedStats::addLogins($userIds);

        $users = Base_Dao_User::getUsersByIds($userIds);
        unset($queueData);

        // считаем посещения пользователей за последние 2 недели

        Base_Service_UserActivity::updateVisitsInformation($users, $userIds, $oldOnline);
        self::updateComeBackUsers($oldOnline);

        self::updateUserLocations($userIds, $usersLocation);
        unset($usersLocation);

        // BEGIN: стата возватов
        $returnStats = new Base_Service_Counter_Returns();
        $involvement = new Base_Service_Counter_Involvement();
        $emailReturnStats = new Base_Service_Counter_EmailReturn();
        $trackingCampaigns = Base_Service_Counter_Returns::getTrackingCampaigns();

        $messenger = new Messenger_Interface_Base();
        foreach ($users as $value) { /** @var $value Base_Model_User */
            $returnDataRec = isset($returnData[$value->getId()]) ? $returnData[$value->getId()] : null;
            $oldTimeIn = isset($oldOnline[$value->getId()]) ? $oldOnline[$value->getId()] : 0;

            $value['old_time_in'] = $oldTimeIn;
            $lastVisitInt = Base_Service_Counter_Returns::getLastvisit($value);
            if ($oldTimeIn > 0) {
                // не удаляйте, эта штука активно юзается во встречах
                Base_Dao_User::setPreviousTimeIn($value->getId(), $oldTimeIn);
            }

            Profile_Service_Change::cancelDeleteRequest($value->getId());

            $involvement->incrementStats($value, Base_Service_Counter_Involvement::FIELD_LOGINS, 1);

            // был вчера или ранее и вернулся сегодня, или зарегился сегодня и это не первая сессия
            if ($value->getLastVisit() < strtotime(date('Y-m-d 00:00:00')) || (strtotime(date('Y-m-d 00:00:00')) == strtotime(date('Y-m-d 00:00:00', strtotime($value->getDateInserted()))) && !Base_Service_User::isFirstSession($value))) {
                // @involvement stats
                $involvement->incrementStats($value, Base_Service_Counter_Involvement::FIELD_RETURNS, 1);
            }

            $involvement->incrementStats($value, Base_Service_Counter_Involvement::FIELD_RETURNS_WEEK, 1);

            if (substr($returnDataRec, 0, 17) != 'email_unsubscribe') {
                if ($oldTimeIn) { //&& $oldTimeIn <= TIME - 1080
                    $returnStats->increment($value, 'all_all_return');
                    if ($lastVisitInt > 0) {
                        $returnStats->increment($value, 'all_return_period_' . $lastVisitInt);
                    }
                    if ($oldTimeIn >= TIME - 7 * 24 * 60 * 60) {
                        $returnStats->increment($value, 'all_return_active');
                    } else {
                        $returnStats->increment($value, 'all_return_nonactive');
                    }

                    $regDaysAgo = (int)floor((time() - strtotime($value->getDateInserted())) / 60 / 60 / 24);
                    $returnStats->increment($value, ((0 <= $regDaysAgo && $regDaysAgo <= Base_Service_Counter_Returns::BY_DAYS_MAX_DAY) ? ('return_day_' . $regDaysAgo) : 'return_day_after'));

                    $returnDataRec = strpos($returnDataRec, '|') ? explode('|', $returnDataRec) : false;
                    $returnDataRec = isset($returnDataRec[0]) ? $returnDataRec : false;

                    if ($returnDataRec) {
                        $returnDataRec = array('utm_campaign' => $returnDataRec[0], 'utm_source' => isset($returnDataRec[1]) ? $returnDataRec[1] : '', 'utm_medium' => isset($returnDataRec[2]) ? $returnDataRec[2] : '', 'utm_content' => isset($returnDataRec[3]) ? $returnDataRec[3] : '');
                        $returnDataRec['old_time_in'] = $oldTimeIn; // this old_time_in key nowhere used on the face of it

                        $campaing = $returnDataRec['utm_campaign'];

                        Base_Service_Lemon2::set(__METHOD__, 'userReturnData', $value->getId(), $returnDataRec, 12 * 60 * 60);

                        if ($campaing == 'email_notify' || $campaing == 'email_generic' || $campaing == 'email_immediate' || $campaing == 'email_confirm' || $campaing == 'email_genericnew') {
                            $returnStats->increment($value, 'return_fromemail');
                            $returnStats->increment($value, $oldTimeIn >= TIME - 7 * 24 * 60 * 60 ? 'return_active_fromemail' : 'return_nonactive_fromemail');
                            if ($oldTimeIn >= TIME - 7 * 24 * 60 * 60) {
                                $returnStats->increment($value, strtotime($value->getDateInserted()) >= TIME - 7 * 24 * 60 * 60 ? 'return_active_fromemail_newbie' : 'return_active_fromemail_oldusers');
                            } else {
                                $month1ago = TIME - 31 * 24 * 60 * 60;
                                $month6ago = TIME - 6 * 31 * 24 * 60 * 60;

                                if (TIME - $month1ago <= $oldTimeIn) {
                                    $returnStats->increment($value, 'return_nonactive_fromemail_month');
                                } else if (TIME - $month6ago <= $oldTimeIn && $oldTimeIn <= TIME - $month1ago) {
                                    $returnStats->increment($value, 'return_nonactive_fromemail_severalmonths');
                                } else {
                                    $returnStats->increment($value, 'return_nonactive_fromemail_sixmonths');
                                }
                            }
                        }

                        if (isset($trackingCampaigns[$campaing])) {
                            $returnStats->increment($value, 'all_return');
                            $returnStats->increment($value, $oldTimeIn >= TIME - 7 * 24 * 60 * 60 ? 'all_return_track_active' : 'all_return_track_nonactive');

                            $returnStats->increment($value, 'return_' . $campaing);
                            if (!empty($returnDataRec['utm_source'])) {
                                $returnStats->increment($value, 'return_' . $campaing . '_' . $returnDataRec['utm_source']);
                            }

                            if ($campaing == 'email_notify') {
                                $eventId = (int)substr($returnDataRec['utm_source'], 6);
                                $typeId = (int)substr($returnDataRec['utm_medium'], 6);
                                Base_Notification_Base::returnCounter($value, $typeId, $eventId);

                                // Костыль для подсчета возвратов с конкретных email типов
                                $returnStats->increment($value, ('return_' . $campaing . '_') . 'email_' . $typeId);

                                // Костыль для подсчета возвратов с конкретных ссылок в email типе
                                $linkId = isset($returnDataRec['utm_content']) ? $returnDataRec['utm_content'] : false;
                                $blockLinkId = $linkId && strpos($returnDataRec['utm_content'], 'block_') === 0 ? (int)substr($returnDataRec['utm_content'], 6) : false;
                                if ($blockLinkId) {
                                    $blockTypeId = Base_Mailer_NewTypes::getFakeTypeId($typeId, $blockLinkId);
                                    $returnStats->increment($value, ('return_' . $campaing . '_') . 'email_' . $blockTypeId);
                                }
                            } else if ($campaing == 'email_generic') {
                                $typeId = Base_Mailer_NewTypes::getMassMailTypeId((int)substr($returnDataRec['utm_medium'], 5));
                                $returnStats->increment($value, ('return_email_notify_') . 'email_' . $typeId);
                            }
                        } else {
                            $returnStats->increment($value, 'all_return_notrackcampaign');
                        }
                    } else {
                        $returnStats->increment($value, 'all_return_nosource');
                    }
                }

                // Вернулся давно не заходивший пользователь, надо проверить не через систему ли возвратов старичков он вернулся
                if ($returnDataRec && $oldTimeIn && $oldTimeIn <= time() - Return_Dao_Registry::$oldTimeInterval) {
                    $lastFilter = Return_Dao_Registry::getLastUserFilter($value->getId());
                    if ($lastFilter) {
                        Return_Dao_Registry::oldUserReturned($value, $lastFilter, $emailReturnStats);
                    }
                }

                //Рассылка приглашений в элитарную отднодневку
                Contest_Service_OneDay::inviteUser($value, $message);
            }

            //временная статса
            Admin_Controller_Emaillinks::addEvent($value, Admin_Controller_Emaillinks::ONLINE, $returnDataRec);
        }
        // END: стата возватов

        Base_Notification_Daemon::clearUsers($userIds, Base_Notification_Common::MEDIA_EMAIL);
        Base_Mailer_Service_Cron::onUsersOnline($users);

        // Новые нотификации (новые?)
        $notificationInterface = Base_Interface_Factory::get('Notifications');
        /** @var $notificationInterface Notifications_Interface_Common */
        $notificationInterface->updateOnOnlineCommunityNotifications($userIds);

        $chunked = array_chunk($userIds, 500);
        foreach ($chunked as $chunkedIds) {
            Return_Dao_Registry::updateUserByIds($chunkedIds, array('email_sent_count' => 0, 'email_view_count' => 0, 'email_click_count' => 0));
            Contest_Service_Cron::onOnline($chunkedIds);
        }

        // аналитика остатоков магий на счетах петов
        Pet_Service_KarmaTop::saveMagicRest($users, $oldOnline);

        // выставляем таргетинг автоимпорта ньюсфида
        $res = '';
        try{
            $res = Advsocial_Service_Base::updateTargetingNewsfeedAutoimport($userIds);
        } catch(exception $e){}

        /*foreach ($users as $user) {
            if (Abtest_Dao_Base::getUserGroup(Abtest_Dao_Base::TEST_NOTIFY_ALGORITHM, $user) == 2 || Abtest_Dao_Base::getUserGroup(Abtest_Dao_Base::TEST_NOTIFY_ALGORITHM_OLDUSERS, $user) == 2) {
                Base_Service_Lemon2::delete(__METHOD__, 'userHadImmediateNotify', $user->getId());
            }
        }*/

        /*if (!PRODUCTION) {
            Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_ON_ONLINE_ACTION, array($userIds, $oldOnline, $returnData), __METHOD__);
        }*/

        //биллинг раздача бонусов за посещение
        $dividendService = new Billing_Service_Dividend();
        $dividendService->addUserDayLoginBonus($users);

        //добавляние айди юзеров в очередь для напоминаний о др друзей
        Fs2Friends_Service_Birthdayadvsoc::addUsersIdsToQueue($users, $oldOnline);

        return $res;
    }

    /**
    * тут обработка пользователей на onOffline. в self::onOffline они пихаются в очередь, разбираются тут раз в 1 минуту
    *
    */
    public static function cronProcessOnOfflineUsers()
    {
        $queueData = Base_Service_SharedQueue::popAll(Base_Service_SharedQueue::INDEX_ON_OFFLINE_ACTION, __METHOD__);

        $userIds = $online = array();
        foreach ($queueData as $record) {
            $userIds = array_merge($userIds, $record[0]);
            if (is_array($record[1])) {
                $online = $online + $record[1];
            }
        }
        $userIds = array_unique($userIds);
        $users = Base_Dao_User::getUsersByIds($userIds);
        unset($queueData);

        if (Admin_Service_CoreServices::isSeparatedCronEnabled()) {
            Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_CORE_SERVICES_STATS, $userIds, __METHOD__); // обрабатывается раз в минуту в Admin_Service_CoreServices::statsCountCron
        }

        // Стата по популярности
        Popularity_Dao_Base::popularityStats($userIds, $users);

        // BEGIN: стата возватов
        $returnStats = new Base_Service_Counter_Returns();
        $mailStats = new Base_Service_Counter_NewMail();
        $trackingCampaigns = Base_Service_Counter_Returns::getTrackingCampaigns();

        $involvement = new Base_Service_Counter_Involvement();

        foreach ($users as $oUser) { /** @var $oUser Base_Model_User */
            if (!empty($online[$oUser->getId()])) {

                // общее время сессии
                $minutes = round(($online[$oUser->getId()][0] - $online[$oUser->getId()][1]) / 60);

                // Трекаем среднее время сессии
                $sessionTime = null;
                if ($minutes < 10 * 60) {
                    $sessionTime = (int)round($minutes * 60);
                }

                Base_Service_Counter_FriendConvert::incrementStat($oUser, Base_Service_Counter_FriendConvert::FIELD_SESSION_COUNT);
                Base_Service_Counter_FriendConvert::incrementStat($oUser, Base_Service_Counter_FriendConvert::FIELD_SESSION_TIME, $sessionTime);


//                if ($minutes && $minutes < 3 * 3600) {
//                    //$involvement->incrementStats($oUser, Base_Service_Counter_Involvement::FIELD_SESSION_TIME, $minutes);
//                }

                $returnDataRec = Base_Service_Lemon2::get(__METHOD__, 'userReturnData', $oUser->getId());

                $returnDataRec = isset($returnDataRec['utm_campaign']) ? $returnDataRec : false;


                // Стата по активным возвратам (> 5 минут)
                if ($minutes >= 5) {
                    if ($returnDataRec) {
                        $campaing = $returnDataRec['utm_campaign'];

                        if (isset($trackingCampaigns[$campaing])) {
                            $returnStats->increment($oUser, 'all_confirmed_return');
                            $returnStats->increment($oUser, 'return_confirmed_' . $returnDataRec['utm_campaign']);
                            if (!empty($returnDataRec['utm_source'])) {
                                $returnStats->increment($oUser, 'return_confirmed_' . $returnDataRec['utm_campaign'] . '_' . $returnDataRec['utm_source']);
                            }

                            if ($returnDataRec['utm_campaign'] == 'email_notify') {
                                $eventId = (int)substr($returnDataRec['utm_source'], 6);
                                $typeId = (int)substr($returnDataRec['utm_medium'], 6);
                                $tTime = Service_Base::getCookie(crc32('mtiti_'.$typeId));
                                $time = ($tTime) ? $tTime : 0;

                                Base_Notification_Base::returnCounter($oUser, $typeId, $eventId, '6min');

                                // Костыль для подсчета возвратов с конкретных email типов
                                $returnStats->increment($oUser, ('return_confirmed_' . $campaing . '_') . 'email_' . $typeId);
                                /**var $mailStats Base_Service_Counter_NewMail*/
                                $mailStats->increment($oUser, 'return_confirmed_' . $typeId);//todel
//                                Base_Mailer_Service_Stats::increment($oUser, $typeId, Base_Mailer_Service_Stats::MAIL_RETURN_COUNT_CONFIRMED);//METKA


                                // Костыль для подсчета возвратов с конкретных ссылок в email типе
                                $linkId = isset($returnDataRec['utm_content']) ? $returnDataRec['utm_content'] : false;
                                $blockLinkId = $linkId && strpos($returnDataRec['utm_content'], 'block_') === 0 ? (int)substr($returnDataRec['utm_content'], 6) : false;
                                if ($blockLinkId) {
                                    $blockTypeId = Base_Mailer_NewTypes::getFakeTypeId($typeId, $blockLinkId);
                                    $returnStats->increment($oUser, ('return_confirmed_' . $campaing . '_') . 'email_' . $blockTypeId);
                                }
                            } else if ($campaing == 'email_generic') {
                                $typeId = Base_Mailer_NewTypes::getMassMailTypeId((int)substr($returnDataRec['utm_medium'], 5));
                                $returnStats->increment($oUser, ('return_confirmed_email_notify_') . 'email_' . $typeId);
                            }
                        } else {
                            $returnStats->increment($oUser, 'all_return_notrackcampaign_confirmed');
                        }
                    } else {
                        $returnStats->increment($oUser, 'all_return_nosource_confirmed_return');
                    }

                    $returnStats->increment($oUser, 'all_all_return_confirmed');
                }

                // Стата по среднему времени сессии
                $returnStats->incrementSession($oUser, 'all_all_return', $sessionTime);

                if ($returnDataRec) {
                    $campaing = $returnDataRec['utm_campaign'];

                    if (isset($trackingCampaigns[$campaing])) {
                        $returnStats->incrementSession($oUser, 'all_return', $sessionTime);
                        $returnStats->incrementSession($oUser, 'return_' . $campaing , $sessionTime);
                        if (!empty($returnDataRec['utm_source'])) {
                            $returnStats->incrementSession($oUser, 'return_' . $campaing . '_' . $returnDataRec['utm_source'], $sessionTime);
                        }

                        if ($campaing == 'email_notify') {

                            // Костыль для подсчета возвратов с конкретных email типов
                            $typeId = (int)substr($returnDataRec['utm_medium'], 6);
                            $tTime = Service_Base::getCookie(crc32('mtiti_'.$typeId));


                            $returnStats->incrementSession($oUser, ('return_' . $campaing . '_') . 'email_' . $typeId, $sessionTime);//todel
                            /**var $mailStats Base_Service_Counter_NewMail*/
                            Base_Mailer_Service_Stats::increment($oUser, $typeId, Base_Mailer_Service_Stats::MAIL_SESSION_TIME, $sessionTime);//METKA
                            $mailStats->increment($oUser, 'session_time_' . $typeId, $sessionTime);//todel

                            if ($sessionTime <= 60) {
                                /**var $mailStats Base_Service_Counter_NewMail*/
                                $mailStats->increment($oUser, 'return_reject_' . $typeId);
//                                Base_Mailer_Service_Stats::increment($oUser, $typeId, Base_Mailer_Service_Stats::MAIL_RETURN_COUNT_REJECT);//METKA
                            }

                            if ($sessionTime <= 120) {
                                $returnStats->increment($oUser, ('return_reject_' . $campaing . '_') . 'email_' . $typeId);
                            }

                            // Костыль для подсчета возвратов с конкретных ссылок в email типе
                            $linkId = isset($returnDataRec['utm_content']) ? $returnDataRec['utm_content'] : false;
                            $blockLinkId = $linkId && strpos($returnDataRec['utm_content'], 'block_') === 0 ? (int)substr($returnDataRec['utm_content'], 6) : false;
                            if ($blockLinkId) {
                                $blockTypeId = Base_Mailer_NewTypes::getFakeTypeId($typeId, $blockLinkId);
                                $returnStats->incrementSession($oUser, ('return_' . $campaing . '_') . 'email_' . $blockTypeId, $sessionTime);

                                if ($sessionTime <= 120) {
                                    $returnStats->increment($oUser, ('return_reject_' . $campaing . '_') . 'email_' . $blockTypeId);
                                }
                            }
                        } else if ($campaing == 'email_generic') {
                            $typeId = Base_Mailer_NewTypes::getMassMailTypeId((int)substr($returnDataRec['utm_medium'], 5));
                            $returnStats->incrementSession($oUser, ('return_email_notify_') . 'email_' . $typeId, $sessionTime);
                            if ($sessionTime <= 120) {
                                $returnStats->increment($oUser, ('return_reject_email_notify_') . 'email_' . $typeId);
                            }
                        }
                    } else {
                        $returnStats->incrementSession($oUser, 'all_return_notrackcampaign', $sessionTime);
                    }
                } else {
                    $returnStats->incrementSession($oUser, 'all_return_nosource', $sessionTime);
                }

                // Подсчет времени сессии по периодам
                $oUser['old_time_in'] = $oUser->getPreviousTimeIn(); // костыль для корректной отработки метода ниже
                $lastVisitInt = Base_Service_Counter_Returns::getLastvisit($oUser);
                if ($lastVisitInt > 0) {
                    $returnStats->incrementSession($oUser, 'all_return_period_' . $lastVisitInt, $sessionTime);

                    $returnStats->incrementSession($oUser, $oUser['old_time_in'] >= TIME - 7 * 24 * 60 * 60 ? 'all_return_active_session' : 'all_return_nonactive_session', $sessionTime);
                }

                Base_Service_Lemon2::delete(__METHOD__, 'userReturnData', $oUser->getId());
                Base_Dao_User::setPreviousTimeIn($oUser->getId(), 0);
            }
            //временная статса
            Admin_Controller_Emaillinks::addEvent($oUser, Admin_Controller_Emaillinks::OFFLINE);
        }
        // END: стата возватов

        Return_Dao_Registry::updateOffline($userIds);

        Base_Mailer_Service_Cron::onUsersOffine($users);

        // работа с сессиями VIP АБтестов
        Vip_Service_Base::onOffline($users);

        if (!Admin_Service_CoreServices::isSeparatedCronEnabled()) {
            /** Подсчёт статы для KPI сервисов >> **/
            $userApps     = Base_Interface_Access::mGetUserApps($userIds);
            $stanUserApps = array();
            $statServices = Service_StatsFuncToId::getServicesInfo();
            $totalServicesInterest = 0;
            foreach (array_chunk($userIds, 500) as $usersChunk) {
                $userInterests = Bi_Dao_InterestServices::calcMulti($usersChunk, Bi_Dao_AbTest_Base::DEFAULT_AB_TEST_ID, __METHOD__);
                if (empty($userInterests[0])) {
                    continue;
                }
                foreach ($userInterests[0] as $uId => $appsData) {
                    // crutch for resolve daemon "feature" (it returns '0' if no data for user found)
                    if (empty($appsData)) {
                        continue;
                    }
                    foreach ($appsData as $appData) {
                        list($sType, $sId, $sWeight, $sLastVisit) = $appData;
                        if (round($sWeight, 0) == 0) {
                            continue;
                        }
                        switch ($sType) {
                            case Bi_Dao_Base::SERVICE_TYPE_INTERNAL:
                                if (isset($statServices[$sId]['storeAppUid'])) {
                                    $stanUserApps[$uId][] = $statServices[$sId]['storeAppUid'];
                                    $totalServicesInterest += $sWeight;
                                }
                                break;
                            case Bi_Dao_Base::SERVICE_TYPE_EXTERNAL:
                                $stanUserApps[$uId][] = $sId;
                                $totalServicesInterest += $sWeight;
                                break;
                        }
                    }
                }
            }

            $usersCount = $allAppsCount = $interestAppsCount = 0;
            foreach ($userApps as $uId => $uApps) {
                $aC = count($uApps);
                if ($aC > 0) {
                    ++$usersCount;
                    $allAppsCount += $aC;
                    if (isset($stanUserApps[$uId])) {
                        $interestAppsCount += count(array_intersect($stanUserApps[$uId], $uApps));
                    }
                }
            }

            /*
            $usersCount   - кол-во обработанных юзеров
            $allAppsCount - общее кол-во приложений по всем юзерам
            $interestAppsCount - общее кол-во интересных сервисов по всем юзерам
            $totalServicesInterest - суммарный интерес всех пользователей к сервисам

            Среднее число установленных сервисов на человека (кол-во людей)
                    $allAppsCount / $usersCount
            среднее число сервисов, в которых пользователь активен
                    $interestAppsCount / $usersCount
            Общий суммарный интерес пользователей к сервисам на ФС по Базе Интересов
                    $totalServicesInterest / $usersCount
            */

            $defaultUser = Base_Dao_User::getUserById(Messenger_Service::SYSTEM_MESSENGER_ID);
            $analytics = new Base_Service_Counter_Analytics();
            if ($usersCount > 0) {
                $analytics->increment($defaultUser, 'services_user_count', $usersCount);
            }
            if ($allAppsCount > 0) {
                $analytics->increment($defaultUser, 'services_all_apps', $allAppsCount);
            }
            if ($interestAppsCount > 0) {
                $analytics->increment($defaultUser, 'services_interest_apps', $interestAppsCount);
            }
            if ($totalServicesInterest > 0) {
                $analytics->increment($defaultUser, 'services_total_interest', $totalServicesInterest);
            }
            /** << Подсчёт статы для KPI сервисов **/
        }

        /*if (!PRODUCTION) {
            Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_ON_OFFLINE_ACTION, array($userIds, $online), __METHOD__);
        }*/
    }

    /**
    * тут еще одна обработка пользователей на onOnline. в self::onOnline они пихаются в очередь, разбираются тут раз в 1 минуту
    * основной крон -- self::cronProcessOnOnlineUsers
    * используется для спама из сервисов знакомств
    */
    public static function cronProcessOnOnlineUsers2()
    {
        $queueData = Base_Service_SharedQueue::popAll(Base_Service_SharedQueue::INDEX_ON_ONLINE_ACTION2, __METHOD__);

        $userIds = $oldOnline = $returnData = $usersLocation = array();
        foreach ($queueData as $record) {
            $userIds = array_merge($userIds, $record[0]);
            if (is_array($record[1])) {
                $oldOnline = $oldOnline + $record[1];
            }
            if (is_array($record[2])) {
                $returnData = $returnData + $record[2];
            }
            //isset для старых очередей
            if(isset($record[3]) && is_array($record[3])){
                $usersLocation = $usersLocation + $record[3];
            }
        }
        $userIds = array_unique($userIds);

        // create static cache
        $users = Base_Dao_User::getUsersByIds($userIds);
        unset($queueData);

        $text = '';

        // Отправка нотификаций "бывалым юзерам"
        $checkMeetingUsers = array();
        /** @var $userModel Base_Model_User */
        foreach ($users as $userModel) {
            if ($userModel->getRealDaysRegistered() > 7) {
                $checkMeetingUsers[] = $userModel->getId();
            }
        }
        list($in, $out) = Meeting_Service_Base::sendFreeShowsNotify($checkMeetingUsers);
        $text .= 'free_20_shows: ' . $out . ' (' . $in . '); ';

        //Отправляем нотификации для свиты
        $count = Team_Service_Base::sendBonusNotify($userIds);
        $text .= 'n_team_bonus: ' . $count . '; ';

        return $text;
    }

    private static function OnlineReplica($new, $off, $timeoff, $dbHosts){

    	$timeNow = time();
    	$timeOff = $timeNow - $timeoff;

        // апдейтим auto_online
        if(!empty($new)){
            $usersNew = implode(",$timeNow),(", $new).",$timeNow";
            $sqlNew = "INSERT INTO auto_online_replica (`user_id`,`time_in`) VALUES (".$usersNew.") ON DUPLICATE KEY UPDATE `time_in`=NOW()";
        }

        if(!empty($off)){
            $usersOff = implode("','", $off);
            $sqlOff = "DELETE FROM auto_online_replica WHERE user_id IN ('".$usersOff."')";
        }

        $sqlOld = "DELETE FROM auto_online_replica WHERE time_in < $timeOff";

        foreach($dbHosts as $host){
        	if(!empty($new)) Driver_DBQuery::queryWrite($sqlNew, $host, __METHOD__);
            if(!empty($off)) Driver_DBQuery::queryWrite($sqlOff, $host, __METHOD__);
        	Driver_DBQuery::queryWrite($sqlOld, $host, __METHOD__);
        }
    }

    /**
     * Add users' IDs to user_online_history table.
     * Must be called before any changes in auto_online table.
     */
    private static function fillOnlineHistory($online, $up, $onlineDuration = 300, $timeOnLineRefresh = 180)
    {

        $db = Base_Context::getInstance()->getDbConnection();
        $updated = 0;
        $now = time();

        if (date('H:i') == '23:55') { // Fixed bug table lock conflict
            return $updated;
        }

        $dayNumber = intval(floor(($now / 86400))); // Days passed from unixtime started
        switch ($dayNumber % 3) {
            case 0: $dayBitMask = 1; break; // 1st day
            case 1: $dayBitMask = 2; break; // 2nd day
            case 2: $dayBitMask = 4; break; // 3rd day
            default: $dayBitMask = 0; break;
        }
        $result = array();
        foreach($online as $userId => $value){
        	if((($value[0] - $value[1]) > $onlineDuration) && in_array($userId, $up) )
        	{
        		$result[] = "($userId, $dayBitMask)";
        	}
        }

        if(!empty($result)){
	        $insert = "INSERT INTO user_online_history (user_id, bitmask)"."\n"
			  . " VALUES " . implode(',', $result)."\n"
			  . " ON DUPLICATE KEY UPDATE bitmask = ($dayBitMask | bitmask), updated=NOW()";
	        $updated += $db->writequery('user_online_history', $insert, __METHOD__);
        }
//        $select = $db->select()
//            ->from('auto_online', array('user_id'))
//            ->where('(time_in + ?) <= time_update', $onlineDuration);
//        $result = $db->fetchCol($select, __METHOD__);
//
//        $dayNumber = intval(floor(($now / 86400))); // Days passed from unixtime started
//        switch ($dayNumber % 3) {
//            case 0: $dayBitMask = 1; break; // 1st day
//            case 1: $dayBitMask = 2; break; // 2nd day
//            case 2: $dayBitMask = 4; break; // 3rd day
//            default: $dayBitMask = 0; break;
//        }
//        if (!empty($result)) {
//            foreach ($result as $key => $value) {
//                $result[$key] = "($value, $dayBitMask)";
//            }
//            $insert = "INSERT INTO user_online_history (user_id, bitmask)"."\n"
//            . " VALUES " . implode(',', $result)."\n"
//            . " ON DUPLICATE KEY UPDATE bitmask = ($dayBitMask | bitmask), updated=NOW()";
//            if (date('H:i') == '23:55') { // Fixed bug table lock conflict
//                return $updated;
//            } else {
//                $updated += $db->writequery('user_online_history', $insert, __METHOD__);
//            }
//        }
        return $updated;
    }

    public static function getUserIdByText($text)
    {
        if ((int)$text > 0) {
            return (int)$text;
        }

        Utf::preg_match_all('/user\/(\d+)+/i', $text, $matches);
        if (!empty($matches[1][0])) {
            return (int)$matches[1][0];
        }

        $text = str_replace(array('http://', 'www.', ' '), array('', '', ''), $text);

        $begin = Utf::strpos($text, '.fotostrana.ru');
        $userDomain = Utf::substr($text, 0, $begin);

        if ($userDomain) {
            $user = Base_Dao_User::getUserByPageName($userDomain);
            return $user ? $user['user_id'] : false;
        }

        return false;
    }

    public static function generatePassword($length = 6, $strength = 0, $generateWordPassword = false)
    {
        if ($generateWordPassword) {
            $aFreqBase = array('through', 'around', 'something', 'little', 'before', 'another', 'behind', 'toward', 'nothing', 'because', 'moment', 'himself', 'across', 'really', 'should', 'anything', 'against', 'happen', 'window', 'street', 'enough', 'minute', 'inside', 'follow', 'second', 'suddenly', 'almost', 'without', 'better', 'course', 'continue', 'always', 'believe', 'office', 'answer', 'remember', 'friend', 'though', 'mother', 'listen', 'understand', 'between', 'father', 'morning', 'please', 'everything', 'slowly', 'someone', 'shoulder', 'question', 'hundred', 'outside', 'change', 'finger', 'scream', 'ground', 'already', 'forward', 'figure', 'become', 'glance', 'matter', 'number', 'police', 'twenty', 'together', 'return', 'appear', 'control', 'probably', 'finally', 'wonder', 'suppose', 'corner', 'quickly', 'shadow', 'myself', 'perhaps', 'business', 'notice', 'pretty', 'everyone', 'several', 'silence', 'problem', 'thousand', 'approach', 'realize', 'either', 'interest', 'surprise', 'forget', 'finish', 'picture', 'chance', 'couple', 'doctor', 'officer', 'anyone', 'pocket', 'family', 'breath', 'reason', 'machine', 'beside', 'expect', 'careful', 'school', 'whisper', 'herself', 'yourself', 'trouble', 'thirty', 'different', 'coffee', 'anyway', 'station', 'brother', 'kitchen', 'decide', 'straight', 'remain', 'whatever', 'actually', 'dollar', 'children', 'afraid', 'except', 'exactly', 'computer', 'explain', 'centre', 'corridor', 'report', 'living', 'middle', 'bottle', 'somebody', 'driver', 'rather', 'freeze', 'bedroom', 'beautiful', 'darkness', 'person', 'nobody', 'within', 'cigarette', 'attention', 'direction', 'search', 'company', 'weapon', 'apartment', 'reveal', 'charge', 'record', 'clothes', 'beyond', 'tonight', 'position', 'arrive', 'animal', 'somewhere', 'soldier', 'colour', 'strike', 'security', 'tomorrow', 'strange', 'disappear', 'distance', 'possible', 'beneath', 'bright', 'country', 'handle', 'afternoon', 'telephone', 'evening', 'certain', 'quietly', 'island', 'bathroom', 'mirror', 'throat', 'important', 'sometimes', 'engine', 'itself', 'letter', 'everybody', 'silent', 'strong', 'camera', 'consider', 'million', 'remove', 'struggle', 'promise', 'instead', 'among', 'slightly', 'hospital', 'during', 'information', 'system', 'surface', 'service', 'bridge', 'attack', 'lawyer', 'motion', 'elevator', 'button', 'general', 'flight', 'doorway', 'jacket', 'circle', 'desert', 'certainly', 'single', 'bottom', 'secret', 'immediately', 'concern', 'husband', 'escape', 'simply', 'message', 'detective', 'manage', 'creature', 'themselves', 'dinner', 'gesture', 'switch', 'expression', 'yellow', 'sergeant', 'however', 'present', 'settle', 'daughter', 'anybody', 'completely', 'nearly', 'gather', 'sister', 'special', 'hallway', 'mountain', 'gently', 'spread', 'bullet', 'stretch', 'master', 'counter', 'private', 'entrance', 'entire', 'perfect', 'command', 'screen', 'conversation', 'whether', 'ceiling', 'narrow', 'imagine', 'simple', 'colonel', 'excuse', 'tunnel', 'planet', 'double', 'recognize', 'sudden', 'monitor', 'square', 'mention', 'uniform', 'travel', 'fifteen', 'memory', 'serious', 'softly', 'contact', 'action', 'prepare', 'thought');
            $iBaseIndex = rand(0, count($aFreqBase)-1);
            $iBasePostfix = rand(100, 999);
            return $aFreqBase[$iBaseIndex] . $iBasePostfix;
        }

        $alphabet = '0123456789';
        if ($strength >= 1) {
            $alphabet .= 'abcdefghijklmnopqrstuvwxyz';
        }
        if ($strength >= 2) {
            $alphabet .= 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        }
        if ($strength >= 3) {
            $alphabet .= '!@#$^_()';
        }

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= Utf::getChar($alphabet, (rand() % Utf::strlen($alphabet)));
        }

        return $password;
    }

    public static function getUserName($userId)
    {
    	$user = Base_Dao_User::getUserById($userId);
    	return $user['user_name'];
    }
    /**
     * Сгенерить новый пароль для юзера и поставить ему
     */
    public static function resetPassword($userId)
    {
        $user = Base_Dao_User::getUserById($userId);
        if (!$user) {
            return false;
        }
        $newPassword = Base_Service_User::generatePassword(6, 1);
        Db_User::getInstance()->saveProfile($user->getId(), array('password_hash' => Base_Service_User::getPasswordHash($newPassword)));
        return $newPassword;
    }

    /**
     * @param Base_Model_User $user
     */
    public static function getAutologinHash($user, $oldSalt = false)
    {
        if (!($user instanceof Base_Model_User )) {
            $user = new Base_Model_User($user);
        }
        if ($oldSalt) {
            return Utf::substr(self::getUserPasswordSalt($user->getId(), $user->getEmail(), $user->getPasswordHash()), 0, 15);
        }
        return Utf::substr(self::getAuthHash($user->getId(), $user->getEmail(), $user->getPasswordHash()), 0, 15);
    }

    /**
     * @param Base_Model_User $user
     */
    public static function getAutologinLink($user, $redirectUrl = '', array $options = array(), $params = array())
    {
        if (!($user instanceof Base_Model_User )) {
            $user = new Base_Model_User($user);
        }
        $projectDomain = $user->getNativeProject()->getDomain();

        // Код для статистики - устарел, не надо его использовать
        $tail = (!empty($options['code'])) ? '&c='.$options['code'] : '';

        if (!empty($options['code']) || !empty($options['from_sysmail'])) {
            $tail .= '&from=sysmail';
        }
        if (!empty($params)) {
            foreach ($params as $title => $value) {
                $tail .= '&' . $title . '=' . urlencode($value);
            }
        }

        // Код для статистики возвратов из e-mail
        if (!empty($options['mail_type_id'])) {
            $tail .= '&t='.$options['mail_type_id'];
        }
        // Код для статистики возвратов из e-mail по событиям
        if (!empty($options['event_id'])) {
            $tail .= '&e=' . $options['event_id'];
        }
        // Код для статистики возвратов из e-mail по событиям
        if (!empty($options['mail_type_ver'])) {
            $tail .= '&v=' . $options['mail_type_ver'];
            $tail .= '&time=' . time();
        }
        if (!empty($options['mail_data'])) {
            $tail .= '&md='.$options['mail_data'];
        }

        $projectDomain = !empty($options['domain']) ? $options['domain'] : $projectDomain;

        return 'http://' . $projectDomain . '/user/autologin/?u=' . $user->getId() . '&h=' . self::getAutologinHash($user) . (!$user->isEmailApproved() && !empty($options['add_confirm_hash']) ? '&c=' . Base_Service_User::getEmailConfirmHash($user) : '') .
                $tail . ($redirectUrl ? '&to_url=' . urlencode($redirectUrl) : '');
    }

    public static function getPasswordChangeLink($user, $redirectUrl = '')
    {
        if (!($user instanceof Base_Model_User )) {
            $user = new Base_Model_User($user);
        }
        $projectDomain = Base_Project_Manager::getProject()->getDomain();
        return 'http://' . $projectDomain . '/user/changepassword/?u=' . $user->getId() . '&h=' . self::getAutologinHash($user) . ($redirectUrl ? '&to_url=' . urlencode($redirectUrl) : '');
    }


    /**
     * @param Base_Model_User $user
     * @param sting $newEmail Новый пароль(если есть)
     */
    public static function getEmailConfirmHash($user, $newEmail = '')
    {
        if (!($user instanceof Base_Model_User )) {
            $user = new Base_Model_User($user);
        }

        return Utf::substr(md5($user->getId() . $user->getEmail() . $user->getPasswordHash() . $newEmail . self::getSalt(self::SALT_TYPE_EMAIL)), 0, 10);
    }

    /**
     * @deprecated Use self::getZodiacV2 or $user->getZodiacSign()
     *
     * @param $date
     * @return int|string
     */
    public static function getZodiacSign($date)
    {
        $signs = array(
            _('Овен') => array(
                            'from' => array('d' => 21, 'm' => 3),
                            'to'   => array('d' => 20, 'm' => 4),
                            ),
            _('Телец') => array(
                            'from' => array('d' => 21, 'm' => 4),
                            'to'   => array('d' => 21, 'm' => 5),
                            ),
            _('Близнецы') => array(
                            'from' => array('d' => 22, 'm' => 5),
                            'to'   => array('d' => 21, 'm' => 6),
                            ),
            _('Рак') => array(
                            'from' => array('d' => 22, 'm' => 6),
                            'to'   => array('d' => 22, 'm' => 7),
                            ),
            _('Лев') => array(
                            'from' => array('d' => 23, 'm' => 7),
                            'to'   => array('d' => 23, 'm' => 8),
                            ),
            _('Дева') => array(
                            'from' => array('d' => 24, 'm' => 8),
                            'to'   => array('d' => 23, 'm' => 9),
                            ),
            _('Весы') => array(
                            'from' => array('d' => 24, 'm' => 9),
                            'to'   => array('d' => 23, 'm' => 10),
                            ),
            _('Скорпион') => array(
                            'from' => array('d' => 24, 'm' => 10),
                            'to'   => array('d' => 22, 'm' => 11),
                            ),
            _('Стрелец') => array(
                            'from' => array('d' => 23, 'm' => 11),
                            'to'   => array('d' => 21, 'm' => 12),
                            ),
            _('Козерог') => array(
                            'from' => array('d' => 22, 'm' => 12),
                            'to'   => array('d' => 20, 'm' => 1),
                            ),
            _('Водолей') => array(
                            'from' => array('d' => 21, 'm' => 1),
                            'to'   => array('d' => 19, 'm' => 2),
                            ),
            _('Рыбы') => array(
                            'from' => array('d' => 20, 'm' => 2),
                            'to'   => array('d' => 20, 'm' => 3),
                            ),
        );

        $day = (int) (date('d', is_int($date) ? $date : strtotime($date)));
        $month = (int) (date('m', is_int($date) ? $date : strtotime($date)));

        foreach ($signs as $sign => $data) {
            if ($month == $data['from']['m'] && $day >= $data['from']['d'] || $month == $data['to']['m'] && $day <= $data['to']['d']) {
                return $sign;
            }
        }
    }

    /**
     * Определение знака зодиака по дате
     *
     * @param int|string $date timestamp или дата в виде строки
     * @param bool $asNum вернуть числовой идентификатор
     *
     * @return int|string название либо числовой идентификатор
     */
    public static function getZodiacV2($date, $asNum = false)
    {
        $date = (int) date('md', is_int($date) ? $date : strtotime($date));
        $zodiacId = 0;
        $name = '';
        foreach (self::$zodiacs as $zodiacId => $zodiacConf) {
            $from = $zodiacConf['range'][0];
            $to = $zodiacConf['range'][1];
            if ($from < $to && $date >= $from && $date <= $to || $from > $to && ($date >= $from || $date <= $to)) {
                $name = $zodiacConf['name'];
                break;
            }
        }
        return $asNum ? $zodiacId : _($name);
    }

    /**
     *
     * Проверяет совместимость 2х юзеров по знакам зодиака без учета пола
     * Таблица: https://docs.google.com/a/playform.com/spreadsheet/ccc?key=0AgxrrF4XHBjzdHpPcHRIcVZVbGpGTmZVNGxxalluWnc#gid=0
     * Автор таблицы: Лена Лакки, автор метода: darazum
     *
     * @param Base_Model_User $userA
     * @param Base_Model_User $userB
     */
    public static function checkZodiacSuitable(Base_Model_User $userA, Base_Model_User $userB)
    {
        if(!isset(self::$zodiacs[$userA->getZodiacSign(true)]) || !isset(self::$zodiacs[$userB->getZodiacSign(true)])) { return false; }

        return in_array($userA->getZodiacSign(true), self::$zodiacs[$userB->getZodiacSign(true)]['suitable_signs'])
                || in_array($userB->getZodiacSign(true), self::$zodiacs[$userA->getZodiacSign(true)]['suitable_signs']);
    }

    /**
     * @static
     * @param string $family
     * @return string
     */
    public static function prepareUserLastName($family)
    {
        if (empty($family)) {
            return $family;
        }

        $family = Utf::preg_replace('/[0-9\!\?\#\&\_\.\/]/', '', $family);
        $family = Utf::trim($family, '-/._&?!');
        $family = mb_strtolower($family, Utf::charset());

        $nameParts = explode('-', $family);
        foreach ($nameParts as &$part) {
            $part = mb_convert_case(Utf::trim($part), MB_CASE_TITLE, Utf::charset());
        }
        $family = implode('-', $nameParts);

        return $family;
    }

    /**
     *
     * @param Base_Model_User $user
     */
    public static function checkNeedToModerateUserpic($user)
    {
        if (!($user instanceof Base_Model_User)) {
            return false;
        }

        //return $user->getAge() && $user->getAge() >= 16;
        return true;
    }

    /**
     * Обновление даты последнего визита пользователя
     * @param int $userId
     * @param $sourceId - откуда пришёл юзер (с сайта/мобильной версии/айфона/итп)
     * @param int|null $referrerId
     * @param int|null $returnData
     * @return void
     */
    public static function updateLastVisit($userId, $sourceId, $referrerId = null, $returnData = null)
    {
        $timeNow = time();
        $queueNum = ($userId % 2 == 0)
                  ? Base_Service_SharedQueue::INDEX_ONLINE_EVEN
                  : Base_Service_SharedQueue::INDEX_ONLINE;
        Base_Service_SharedQueue::push($queueNum, array(
            'user_id' => $userId,
            'time'    => $timeNow,
            'r_id'    => $referrerId,
            'r_data'  => $returnData,
            'src_id'  => $sourceId,
            'long_ip' => ip2long(Base_Service_Common::getRealIp()),
        ), __METHOD__);
    }

    public static function isPresetEmail($email)
    {
        return $email && strpos($email, '.playform.com') !== false;
    }

    public static function isNonApprovableEmail($email)
    {
        return (empty($email) || $email==Db_User::DEFAULT_EMAIL || Base_Service_User::isPresetEmail($email));
    }

    public static function isNewProfileEnabled($user)
    {
        return true;
    }

    public static function isNewFullProfileEnabled($user)
    {
        if (!self::isNewProfileEnabled($user)) {
            return false;
        }

        if (!empty($_COOKIE['newprofile_newuser'])) {
            return false;
        }

        return $user && (strtotime($user->getDateInserted()) + 7 * 24 * 60 * 60) <= time();
    }

    public static function isNewHeaderEnabled($user)
    {
        return self::isNewProfileEnabled($user);
    }

    public static function confirmUserEmail($user, $chash, $newEmail = '', $params = array(), $type = null)
    {
        if (!$user) {
            return array('res' => false, 'error' => 'nouser');
        }

        foreach(
            array(
                'force_hash',
                'gift',
                'contest',
                'from_confirm_hash',
                'utm_campaign',
                'utm_medium'
            ) as $param) {
            if (!isset($params[$param])) {
                $params[$param] = '';
            }
        }

        if ($user) {
            if ( Antifraud_Service_Blacklist::checkIsInBlacklist(Antifraud_Dao_Blacklist::TYPE_EMAIL, $user['user_email'])
                || ($newEmail && Antifraud_Service_Blacklist::checkIsInBlacklist(Antifraud_Dao_Blacklist::TYPE_EMAIL, $newEmail))
                || Antifraud_Service_Blacklist::checkIsInBlacklist(Antifraud_Dao_Blacklist::TYPE_IP, $user['user_ip'])
                || Antifraud_Service_Blacklist::checkIsInBlacklist(Antifraud_Dao_Blacklist::TYPE_REFER, $user['user_ref_id'])
                || Antifraud_Service_Location::checkMultiLogin(Base_Service_Common::getRealIp(), $user->getId())) {
                    return array('res' => false, 'error' => 'blacklist');
            }
        }

        $dbUser = Db_User::getInstance();
        if ($params['force_hash']
            || ($chash && $chash == Base_Service_User::getEmailConfirmHash($user, $newEmail))
        ) {
            $oldEmail = $user->getEmail();
            if ($newEmail) {
                $req = array('user_email' => $newEmail);
                $dbUser->saveProfile($user->getId(), $req);
                $user->set('user_email', $newEmail);
            }

            //notify to old user email if email changed
            if (isset($oldEmail) && !empty($newEmail) && $user->isEmailApproved()) {
                $data = array(
                    'oldEmail' => $oldEmail,
                    'newEmail' => $newEmail,
                    'gift'     => false,
                );
                Base_Mailer_Base::addImmediateMail($user, Base_Mailer_NewTypes::TYPE_BASE_NEWEMAIL_NOTIFY, __METHOD__, $data);
            }
            if (!$user->isEmailApproved()) {
                $statsClient = Base_Service_Counter_Main::getInstance();

                if ($params['from_confirm_hash'] && $params['utm_campaign'] == 'email_notify') {
                    $statsClient->increment($user, 'confirm_from_regular_email');
                    if (
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_2 ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_3 ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_V2 ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_V2_1 ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_V2_2 ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_V2_3 ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_V2_4 ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_V2_5 ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_V2_6 ||
                        $params['utm_medium'] == 'email_' . Base_Mailer_NewTypes::TYPE_PLEASE_CONFIRM_EMAIL_V3
                    ) {
                        Base_Dao_User::updateUser($user->getId(), array('user_source' => Base_Service_UserSource::SOURCE_REAPPROVE_EMAIL));
                        Start_Service_UserScheduleAction::addInitialActivityToUser($user);
                        Start_Service_UserScheduleAction::addAction($user, Start_Service_UserScheduleAction::TYPE_SEND_INVOLVE_OLDUSERS_REAPPROVE, '+'. mt_rand(18, 26) .' hour');
                    }
                }

                Signup_Service_Confirm::setEmailApproved($user, true, $type);

                $user = Base_Dao_User::getUserById($user->getId());

                if ($user->isEmailApproved()) { // @TODO temporary
                    $statsClient->increment($user, 'traffic_test_email_confirm_ok');
                }

                //-------------------landing tracker-----------
                $landingTrackerService = new Start_Service_LandingTracker();
                if ($landingTrackerService->isLandingTrackerUser()) {
                    $landingTrackerService->trackEvent(Start_Service_LandingTracker::EVENT_CONFIRMATION);
                }
                unset($landingTrackerService);
                //-------------------end landing tracker-----------

                if ($params['gift']) {
                    Db_UserGood::getInstance()->sendGift(DatingApp_Base_Service_Base::EMAIL_APPROVE_FREE_GIFT_ID, $user->getId());
                    $messengerInterface = Base_Interface_Factory::get('Messenger'); /** @var Messenger_Interface_Base $messengerInterface */
                    $text = "Ты получил замечательный подарок от ". Base_Project_Manager::getProject()->getTitle(1) ."! Ищи его на своей странице.";
                    $messengerInterface->sendMessageToUser(Messenger_Service::SYSTEM_MESSENGER_ID, $user->getId(), $text, false, false);
                }

                if ($user->hasMainPhoto()) {
                    /* @var $iface Meeting_Interface_Base */
                    $iface = Base_Interface_Factory::get(Meeting_Service_Base::APP_ID);
                    $iface->upUserShows($user->getId(), Meeting_Dao_Base::EVENT_TYPE_ADDSHOWS_CUSTOM, 10);
                }
            } else {
                $ids = array(
                    Spider_Service_SocialLinks::TYPE_ID_FS => $user->getId(),
                    Spider_Service_SocialLinks::TYPE_ID_EMAIL => array($user->getEmail()),
                );
                Spider_Service_SocialLinks::getInstance()->addUser($ids);
                Base_Service_Counter_Main::getInstance()->increment($user, 'traffic_test_email_confirm_already');
            }

            if ($user->isHidden() == Db_Moders::BAN_REASON_NEED_APPROVE_EMAIL) {
                $dbUser->show($user->getId(), true);
            }

            if ($inviter = $user->getInviterId()) {
                $inviter = Base_Dao_User::getUserById($inviter);
            }

            // Для приглашения от участников соревнований
            if ($params['contest']) {
                Service_Base::setCookie('contestInvite', $params['contest'], 7);
            }

            if ($inviter && Base_Service_Invite_Email::isInviteCanBeApproved($inviter->getId(), $user->getId())) {
                // Начисление баллов в двух местах, тут и в каптче
                Contest_Service_Invite::tryToApproveIvite($user);
            }

            $emailConfirmationPopup = array(
                'user_to' => $user->getId(),
                'feed' => Base_Service_NotifyWS::CHANNEL_MAIN,
                'message' => array(
                    'type' => Base_Service_NotifyWS::NOTIFY_POPUP_EMAIL_CONFIRMATION,
                    'feed' => Base_Service_NotifyWS::CHANNEL_MAIN,
                    'data' => array(
                        'confirmed' => '1'
                    )
                ),
            );
            Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_SOCKET_PROXY, $emailConfirmationPopup);

            // Update object
            return array('res' => true, 'user' => Base_Dao_User::getUserById($user->getId()));
        }

        Base_Service_Counter_Main::getInstance()->increment($user, 'traffic_test_email_confirm_hash_error');
        if ($user->isEmailApproved()) {
            Base_Service_Counter_Main::getInstance()->increment($user, 'traffic_test_email_confirm_hash_error_already');
        }

        return array('res' => false, 'error' => 'badhash');
    }

    public static function approveEmailFromEmailTrackingImg($userId, $hash, $time)
    {
        if (!$userId || !$hash) {
            return false;
        }

        $user = Base_Dao_User::getUserById($userId);

        if ($user->isEmailApproved()) {
            return false;
        }

        if ($time) {
            $daysAgo = round((time() - $time) / 60 / 60 / 24, 2);
            $minutesAgo = round((time() - $time) / 60, 1);
        } else {
            $daysAgo = 'und';
            $minutesAgo = 'und';
        }

        /*if ($hash == Base_Service_User::getEmailConfirmHash($user)) {
            if ($user->getIp() == Base_Service_Common::getRealIp()) {
                Base_Service_Log::log('email_approve', array($user->getId(), $user->getEmail(), $user->getIp(), Base_Service_Common::getRealIp(), $minutesAgo . ' min', $daysAgo . ' days', $time, time()));
            } else {
                Base_Service_Log::log('email_approve_badip', array($user->getId(), $user->getEmail(), $user->getIp(), Base_Service_Common::getRealIp(), $minutesAgo . ' min', $daysAgo . ' days', $time, time()));
            }
        } else {
            Base_Service_Log::log('email_approve_badhash', array($user->getId(), $user->getEmail(), $user->getIp(), Base_Service_Common::getRealIp(), $minutesAgo . ' min', $daysAgo . ' days', $time, time()));
        }*/

        if ($hash == Base_Service_User::getEmailConfirmHash($user)) {
            Base_Service_Log::log('email_img_approve_ok', array($user->getId(), $user->getEmail(), $user->getIp(), Base_Service_Common::getRealIp(), $minutesAgo . ' min', $daysAgo . ' days', $time, time()));
            $stats = new Base_Service_Counter_NewMail();
            $stats->increment($user, 'email_img_approve');
        } else {
            Base_Service_Log::log('email_img_approve_badhash', array($user->getId(), $user->getEmail(), $user->getIp(), Base_Service_Common::getRealIp(), $minutesAgo . ' min', $daysAgo . ' days', $time, time()));
        }

        $res = self::confirmUserEmail($user, $hash, '', array('from_confirm_hash' => 1, 'utm_campaign' => 'email_notify', 'utm_medium' => 'email_' . @$_GET['t']));
        if ($res['res']) {
            self::logIn($res['user']);
            Base_Service_User::updateLastVisit($user->getId(), Base_Service_User::SOURCE_FS_ONLINE, Base_Service_UserSource::getCurrentReferrerId(), null); // КОСТЫЛЬ!!! обновим что юзер был онлайн при аппруве мыла по картинке
        }


        return $res['res'];
    }

    /**
     * Проверяет, первая ли это сессия юзера.
     *
     * @param Base_Model_User $user
     * @return boolean
     */
    public static function isFirstSession($user)
    {
        if (!$user) {
            return false;
        }

        $regDate = strtotime($user->getDateInserted()); // дата реги
        $timeIn  = (int) $user->get('time_in');         // дата последнего входа
        $timeOut = $user->get('time_out');              // пользователь не онлайн

        // если пользователь онлайн, дата входа больше или равна дате реги
        // и меньше чем дата реги + 1 минута, то это первая сессия пользователя
        if (!$timeOut && $timeIn >= $regDate && $timeIn <= $regDate + 60) {
            return true;
        }

        return false;
    }



    public static function getActivityGroupForUsers($users)
    {
        if(empty($users)) {
            return array();
        }
        if(!is_object(reset($users))) {
            $userIds = $users;
            $users = Base_Dao_User::getUsersByIds($users);
        }
        $return = array();
        if($users) {
            foreach ($users as $id => $user) {
                $return[$id] = $user->getActivity();
            }
        }

        return $return;
    }

    /**
     * Получение активности по email пользователя
     *
     * Не будь уверен что он работает корректно - он был написан, но так и не использовался
     *
     * @param array $userEmails
     * @return array
     */
    public static function getActivityGroupForUsersByEmails($userEmails)
    {
        $users = Base_Dao_User::getUsersByEmails($userEmails);
        $users = empty($users) ? array() : $users;

        $result = self::getActivityGroupForUsers($users);
        $newResult = $resultRecivedEmails =  array();
        foreach ($result as $id => $group ) {
            $newResult[$users[$id]->getEmail()] = $group;
            $resultRecivedEmails[] = $users[$id]->getEmail();
        }

       $noUserEmails = array_diff($userEmails,$resultRecivedEmails);

        foreach($noUserEmails as $email) {
            $newResult[$email] = self::ACTIVITY_GROUP_ERROR;
        }

        return $newResult;
    }

    public static function getActivityGroups()
    {
        return array(
            Base_Service_User::ACTIVITY_GROUP_NEWUSER => 'Новички',
            Base_Service_User::ACTIVITY_GROUP_ACTIVE => 'Активные',
            Base_Service_User::ACTIVITY_GROUP_INACTIVE => 'Неактивные',
            Base_Service_User::ACTIVITY_GROUP_LOST => 'Потерянные',
            Base_Service_User::ACTIVITY_GROUP_ERROR => 'Не определено',
        );
	}

    public static function getActivityGroupsWithoutError()
    {
        $gr = self::getActivityGroups();
        unset($gr[Base_Service_User::ACTIVITY_GROUP_ERROR]);

        return $gr;
	}

     /* END */

    public static function getAgeGroups()
    {
         return array(
            self::M_GROUP_AGE_KID   => 'Дети ( < 16 )',
            self::M_GROUP_AGE_ADULT => 'Взрослые (>= 16)',
            self::M_GROUP_AGE_NO_AGE=> 'Без возроста',
        );
	}

    /**
    *
    * @param Base_Model_User $user
    */
    public static function getSexGroupForUser($user)
    {
        return $user->isFemale() ? self::M_GROUP_FEMALE : self::M_GROUP_MALE;
	}

    public static function getSexGroups()
    {
        return array(
            self::M_GROUP_MALE => 'Мужчины',
            self::M_GROUP_FEMALE => 'Женщины',
        );
	}

    /**
     *
     * @param array $users
     */
    public static function getAgeGroupForUsers($users = array())
    {
        $res = array();
        foreach ($users as $user) {
            if(is_object($user)) {
                $res[$user->getId()] = self::getAgeGroupForUser($user);
            }
        }

        return $res;
	}

    /**
     *
     * @param Base_Model_User $user
     */
    public static function getAgeGroupForUser($user)
    {
        $age = $user->getAge();
        if(!$age) {
            return self::M_GROUP_AGE_NO_AGE;
        }

        if ($age < 16) {
            return self::M_GROUP_AGE_KID;
        } else {
            return self::M_GROUP_AGE_ADULT;
        }
	}

    /**
     * Возвращает либо crc, либо конкат  $activityGroup.'-'.$sexGroup.'-'.$ageGroup
     * @param type $user
     * @param type $getCrc
     * @return boolean
     */
    public static function getMainGroupForUser($user, $getCrc = false)
    {
        if(!is_object($user)) {
            return false;
        }
        $ageGroup = self::getAgeGroupForUser($user);
        $sexGroup = self::getSexGroupForUser($user);
        $activityGroup = self::getActivityGroupForUsers(array($user));

        $concat = reset($activityGroup).'-'.$sexGroup.'-'.$ageGroup;

        return $getCrc ? crc32($concat) : $concat;
	}


    private static function simplifyName($name)
    {
        $name = mb_strtolower(Utf::trim($name));
        $name = explode(' ', $name);
        $nameStr = '';
        foreach ($name as $val) {
            if ($val && (strlen($val) >= 3 || count($name) <= 1)) {
                $nameStr = $val;
                break;
            }
        }
        $name = $nameStr;
        $converter = array(
            'а' => 'a',   'б' => 'b', 'в' => 'v', 'г' => 'g',   'д' => 'd',   'е' => 'e',
            'ж' => 'zh',  'з' => 'z', 'и' => 'i', 'й' => 'y',   'к' => 'k',
            'л' => 'l',   'м' => 'm', 'н' => 'n', 'о' => 'o',   'п' => 'p',   'р' => 'r',
            'с' => 's',   'т' => 't',   'у' => 'u', 'ф' => 'f',   'х' => 'h',   'ц' => 'c',
            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch', 'ь' => '\'',  'ъ' => '\'', 'ю' => 'yu',  'я' => 'ya',
            'А' => 'A',   'Б' => 'B',   'В' => 'V', 'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z', 'И' => 'I',   'Й' => 'Y',   'К' => 'K',
            'Л' => 'L',   'М' => 'M',   'Н' => 'N', 'О' => 'O',   'П' => 'P',   'Р' => 'R',
            'С' => 'S',   'Т' => 'T',   'У' => 'U', 'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch', 'Ь' => '\'',  'Ъ' => '\'', 'Ю' => 'Yu',  'Я' => 'Ya',
        );
        $converter = array_flip($converter);
        $name = strtr($name, $converter);
        $name = Utf::preg_replace('/[^a-zA-Zа-яА-ЯЁё ]/', '', $name);
        $name = str_replace(array('ё', 'Ё', 'Ч', 'Я'), array('е', 'Е', 'ч', 'я'), $name);
        return $name;
    }

    /**
     * Определить пол пользователей по именам
     * @param array $names
     */
	public static function getGenderByNames($names, $asString = false)
	{
	    if (!is_array($names)) {
	        $names = (array) $names;
	    }
	    $map = array(
           self::USER_GENDER_FEMALE  => 'w',
           self::USER_GENDER_MALE    => 'm',
           self::USER_GENDER_UNKNOWN => null,
        );
	    $result = array();
	    $namesCrc = array();
	    $simple = array();
	    foreach ($names as $name) {
	        $simple[$name] = self::simplifyName($name);
    	    if (!$simple[$name]) {
    	        $result[$name] = $asString ? $map[self::USER_GENDER_UNKNOWN] : self::USER_GENDER_UNKNOWN;
    	        unset($simple[$name]);
    	    } else {
    	        $namesCrc[] = crc32($simple[$name]);
    	    }
	    }

	    if (!empty($simple)) {
	        $db = Base_Context::getInstance()->getDbConnection();
            $res = $db->fetchAssoc($db->select()->from('user_names', array('user_name', 'gender'))->where('user_name_crc IN (' . implode(', ' , $namesCrc) . ')'), __METHOD__);

            foreach ($simple as $name => $simple) {
                $gender = isset($res[$simple]['gender']) && in_array($res[$simple]['gender'], array(self::USER_GENDER_FEMALE, self::USER_GENDER_MALE))
                          ? (int) $res[$simple]['gender']
                          : self::USER_GENDER_UNKNOWN;
                $result[$name] = $asString ? $map[$gender] : $gender;
            }
	    }

	    return $result;
	}

    public static function getGenderByName($name, $asString = false)
    {
        $result = self::getGenderByNames($name, $asString);
        return $result[$name];
    }

    /**
     * Метод обновления таблицы user_location_short, вызывается в ShareQueue
     *
     * @static
     * @param array $userIds       - array($userId, $userId2, ...)
     * @param array $usersLocation - array($userId => $userLongIp, ...)
     */
    private static function updateUserLocations(array $userIds, array $usersLocation)
    {
        $data = array();

        foreach($userIds as $userId){
            if(!isset($usersLocation[$userId])){
                continue;
            }

            $data[] = array(
                'userId'   => $userId,
                'longIp'   => $usersLocation[$userId]
            );

            Base_Service_SharedQueue::push(Base_Service_SharedQueue::INDEX_ANTIFRAUD_LOCATION, array('user_id' => $userId, 'user_ip' => long2ip($usersLocation[$userId])));

        }

        $parts = array_chunk($data, 1000);

        foreach($parts as $part){
            Antifraud_Dao_LocationShort::updateCounters($part);
        }
    }

    /**
     * Проверяем подтвержден ли e-mail у юзера и загружена ли аватарка
     *
     * @param Base_Model_User $user
     *
     * @return bool
     */
    public static function isApprovedPhotoAndMail(Base_Model_User $user)
    {
        return ($user->hasApprovedMainPhoto() && $user->isEmailApproved()) ? true : false;
    }

    /**
     * Вернет ссылку "Моя работа"
     *
     * @param Base_Model_User $user
     *
     * @return string
     */
    public static function getUserWorkUrl($user)
    {

        $workCommunity = 0;
        /* @var Ticket_Interface $iTs */
        $iTs = Base_Interface_Factory::get('TicketSystem');
        $wcs = $iTs->getWorkCommunities($user->getId());
        if (sizeof($wcs) > 0) {
            $workCommunity = array_pop($wcs);
        }

        $avamoderator = false;
        $supportAcl = new Support_Acl($user->getId());
        if (in_array($user->getId(), $supportAcl->getAllSupportUsers()) && $supportAcl->isAllowed(Support_Acl::PERM_AVAMODERATOR_ACCESS)
        ) {
            $avamoderator = true;
        }

        $workHref = "";
        if ($user->isExpert()) {
            $workHref = "/ticket/work/?pc=1";
        } elseif ($user->getProfessionId() == Base_Model_User::PETGUIDE) {
            $workHref = "/pet/petgid";
        } elseif ($user->isFakeModer()) {
            $workHref = "/support/fake/?pc=1";
        } elseif ($avamoderator) {
            $workHref = "/moderation/?communityId=108&typeId=1671";
        } elseif ($user->isVolunteer()) {
            $workHref = "/support/volunteer/?pc=1";
        } elseif ($user->isGuide()) {
            $workHref = "/support/index/guide/?pc=1";
        } elseif ($user->isWriter()) {
            $workHref = "/event/editor/?pc=1";
        } elseif ($workCommunity) {
            $workHref = "/ticket/work/inWork?c=".$workCommunity."&pc=1";
        }

        return $workHref;

    }

    /**
     * Возвращает список id системных пользователей
     * @return array
     */
    public static function getSystemUsersIds()
    {
        return self::$_systemUsersIds;
    }

    /**
     * Проверяет, является ли айдишник айдишником системного пользователя
     * @param int $userId
     * @return bool
     */
    public static function isSystemUserId($userId)
    {
        return in_array($userId, self::getSystemUsersIds());
    }

    /**
     *  Вычишает несуществующих юзеров
     *      и добавляет к каждому элементу массива поле с именем $field,
     *      в котором находится соответствующая модель юзера
     *
     * @param array $usersData массив, ключи которого - id пользователей
     * @param string $field название параметра для модели юзера
     * @param callback $callback колбек-функция для удаления отсутствующих юзеров,
     *      должна принимать список id единственным параметром
     * @return array
     */
    public static function addUsersAndRemoveDeleted($usersData, $field='user', $callback=null)
    {
        $existingUsers = Base_Dao_User::getUsersByIds(array_keys($usersData));
        $nonexistingUserIds = array();
        foreach($usersData as $userId => $data) {
            if (!isset($existingUsers[$userId])) {
                unset($usersData[$userId]);
                $nonexistingUserIds[] = $userId;
            } else {
                $usersData[$userId][$field] = $existingUsers[$userId];
            }
        }
        $nonexistingUserIds = array_unique($nonexistingUserIds);
        if ($nonexistingUserIds && is_callable($callback)) {
            $callback($nonexistingUserIds);
        }

        return $usersData;
    }

    /**
     *  Вычишает записи с несуществующими юзерами из массива,
     *     в котором id юзера содержится в поле $userIdField
     *
     * @param array $records массив с записями
     * @param string $userIdField название поля с id юзера
     * @param callback $callback колбек-функция для удаления отсутствующих юзеров,
     *      должна принимать список id единственным параметром
     * @return array
     */
    public static function removeDeletedUsersRecords($records, $userIdField='user_id', $callback=null)
    {
        $userIds = array();
        foreach($records as $record) {
            $userIds[] = $record[$userIdField];
        }
        $existingUsers = Base_Dao_User::getUsersByIds($userIds);
        $nonexistingUserIds = array();
        foreach($records as $key => $record) {
            if (!isset($existingUsers[$record[$userIdField]])) {
                unset($records[$key]);
                $nonexistingUserIds[] = $record[$userIdField];
            }
        }
        $nonexistingUserIds = array_unique($nonexistingUserIds);
        if ($nonexistingUserIds && is_callable($callback)) {
            $callback($nonexistingUserIds);
        }

        return $records;
    }

    /**
     * Являются ли аватарки пользователей их реальной фоткой
     *
     * @param array $users
     *
     * @return array
     */
    public static function isMainPhotosReal($users)
    {
        $extPhotoIds = $return = array();
        foreach($users as $user) {
            /** @var Base_Model_User $user */
            $return[$user->getId()] = false;

            if ($user->getRealMainPhotoId()) {
                $extPhotoIds[$user->getId()] = $user->getRealMainPhotoId();
            }
        }

        $photoDao = Userphoto_Dao_PhotoUser::getInstance();
        $photos = $photoDao->getByIds($extPhotoIds);

        foreach($extPhotoIds as $userId => $extPhotoId) {
            if (!empty($photos[$extPhotoId]) && $photos[$extPhotoId]['is_photo']) {
                $return[$userId] = true;
            }
        }

        return $return;
    }

    /**
     * @return array of userIds
     */
    public static function getNovices()
    {
        $db = Base_Context::getInstance()->getDbConnection();
        $res = $db->fetchAll(
            $db->select()->from('user_new', array('user_id'))->order('user_id DESC')->limit(60), __METHOD__
        );
        return Base_Util_Array::extract($res, 'user_id');
    }


    private static function updateComeBackUsers($oldOnline)
    {
        $comeBackUserList = array();
        foreach($oldOnline as $userId => $lastOnlineTime) {
            if ($lastOnlineTime < (TIME - 24 * 60 * 3600)) {
                $comeBackUserList[] = $userId;
            }
        }
        Base_Service_Lemon::incrby(__METHOD__, 'come_back_users_count', 1, count($comeBackUserList), 0, false);
    }
}