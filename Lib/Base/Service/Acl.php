<?php
class Base_Service_Acl
{
    const ACTION_TLOG_VOTE = 2;
    const ACTION_TLOG_COMMENT = 3;
    const ACTION_IMAGE_CREATE = 10;
    const ACTION_IMAGE_COMMENT = 11;
    const ACTION_IMAGE_VOTE = 12;
    const ACTION_AVATAR_CREATE = 13;
    const ACTION_GUESS = 30;
    const ACTION_USERVOTE_FREE = 40;
    const ACTION_MESSENGER_USE = 50;
    const ACTION_MESSENGER_CONTACTS = 51;
    const ACTION_FRIENDLINK = 52;
    const ACTION_USERDOMAIN = 70;

    const ACTION_NEWS_COMMENT = 74;
    const ACTION_LIVINGROOM_COMMENT = 75;
    const ACTION_CONTEST_PARTICIPATE = 76;
    const ACTION_ADMIT = 77;
    const ACTION_PET_SPORT_PARTICIPATE = 78;
    const ACTION_BE_ON_MAIN_PAGE = 79;
    const ACTION_BE_IN_SEARCH = 80;
    const ACTION_GET_RESIDENT_CLASS = 81;
    const ACTION_BE_IN_ROTATION = 82;
    const ACTION_PLAY_GAMES = 83;
    const ACTION_ADD_ADVERT = 84;
    const ACTION_COMMENT_PET = 85;
    const ACTION_COMMENT_MARKET_ITEM = 86;

    const ACTION_GAME_REQUEST = 94;
    const ACTION_ADD_WALL_RECORD = 87;
    const ACTION_FORUM_COMMENT = 88;
    const ACTION_GUEST_VISIT = 89;
    const ACTION_MISSMISTER_COMMENT = 91;
    const ACTION_USERPIC_REQUEST = 95;
    const ACTION_MOREPHOTO_REQUEST = 96;
    const ACTION_CREATE_COMMUNITY = 97;
    const ACTION_PROFILEFILL_REQUEST = 98;

    const ACTION_SEND_GIFT = 101; // Подарить подарочек
    const ACTION_CONTEST_VOTE = 102; // Голосовать в конкурсе

    const ACTION_MEETING_CLICK = 103; // Действия во встречах

    const ACTION_GROUPONS_INVITE = 104; // Приглашение друзей в группонах

    const ACTION_CONTEST_FREE_VOTE = 105; // бесплатные голоса в мисс/мистер

    const ACTION_GUESS_RECIVEMSG = 106; // получение сообщения в угадайках
    const ACTION_GUESS_RECIVEMAIL = 107; // получение сообщения в угадайках
    const ACTION_GUESS_CLICK      = 108; // получение сообщения в угадайках

    const DENY_CODE_WRONG_PARAMS = 0;
    const DENY_CODE_USERCLASS_LIMIT = 1;
    const DENY_CODE_NOPHOTO_LIMIT = 2;
    const DENY_CODE_TOOFAST_LIMIT = 3;
    const DENY_CODE_EMAIL_APPROVE_LIMIT = 4;
    const DENY_CODE_PHONE_APPROVE_LIMIT = 5;

    const ADMIN_LEVEL_NONE = 0;
    const ADMIN_LEVEL_LOW = 1;
    const ADMIN_LEVEL_MEDIUM = 2;
    const ADMIN_LEVEL_TRUSTED = 3;
    const ADMIN_LEVEL_HIGH = 4;
    const ADMIN_LEVEL_EXTRA = 5;

    const MODER_BAN_USER_NONE = 0;
    const MODER_BAN_USER_UNPAYABLE = 1;
    const MODER_BAN_USER_PHOTO = 2;
    const MODER_BAN_USER_EVERYWHERE = 3;

    const MODER_BAN_PHOTO_GROUP = 108; //Production = 108, Dev = 108
    const MODER_BAN_PHOTO_SUBGROUP = 25; //Production = 25, Dev =18
    const MODER_BAN_UNPAYABLE_SUBGROUP = 28; //Production = 28

    const TIME_MESSENGER_CONTACTS_DECREASE = 30; // время в днях,в течение которого действует уменьшение макс. кол-ва собеседников при проверенном телефоне
    const TEMP_MESSENGER_CONTACTS_LIMIT = 100; // до какого значение временно уменьшается кол-во собеседников при проверенном телефоне

    private static $limits;

    /**
     * Checks user limits (depending on his class)
     *
     * @param integer   $action Action
     * @param Base_Model_User $USER User
     * @return bool
     */
    public static function isUserAllowed($action, $USER, $log = false, &$denyCode = null)
    {

        if (!$action || !$USER) {
            $denyCode = self::DENY_CODE_WRONG_PARAMS;
            return false;
        }
        // temporary check
        if (!($USER instanceof Base_Model_User)) {
            $USER = Base_Dao_User::getUserById($USER['user_id']);
        }
        if (!$USER) {
            return false;
        }

        if (!$USER->hasMainPhoto()) {
            $noPhotoLimits = self::getNoMainPhotoLimits($USER);
            if (in_array($action, $noPhotoLimits)) {
                $denyCode = self::DENY_CODE_NOPHOTO_LIMIT;
                return false;
            }
        }


        $recentLimits = self::getUserLimits(Base_Service_Counter::PERIOD_10MINUTES);

        $userClass = $USER->getUserClass();
        $defaultDenyStatus = self::DENY_CODE_USERCLASS_LIMIT;
        $limits = self::getUserLimits();

        $regged = strtotime($USER->getDateInserted());
        $reggedPlusMonth = $regged + self::TIME_MESSENGER_CONTACTS_DECREASE * 60 * 60 * 24; // используется тут и ниже, при логировании ошибки
        if ($USER->isPhoneApproved() && $reggedPlusMonth > time()) { // проверка, прошел ли месяц с регистрации
            $limits[$userClass][self::ACTION_MESSENGER_CONTACTS]['limit'] = self::TEMP_MESSENGER_CONTACTS_LIMIT; // 100 сообщений, если не прошел месяц с момента регистрации
        }

        if ($USER->getDaysRegistered() < 7) {
            $limits[$userClass][self::ACTION_MESSENGER_CONTACTS]['limit'] = 20; // 20 сообщений в день если не прошло недели с момента регистрации
        }

        if (isset($recentLimits[$userClass][$action]['limit'])) {
            $limit = $recentLimits[$userClass][$action]['limit'];
            $counterId = !empty($recentLimits[$userClass][$action]['counterId']) ? $recentLimits[$userClass][$action]['counterId'] : null;
            $count = 0;
            if ($counterId !== null) {
                $count = Base_Service_Counter::getUserActionCounts($USER['user_id'], $counterId, Base_Service_Counter::PERIOD_10MINUTES);
            }
            if ($count >= $limit) {
                if ($log) {
                    self::logDenyAction($USER, $action);
                }
                $denyCode = self::DENY_CODE_TOOFAST_LIMIT;
                file_put_contents('var/log/acl.deny_toofast.log', date('Y-m-d H:i:s') . "\t" . $USER['user_id'] . "\t" . $action . "\n", FILE_APPEND);

                return false;
            }
        }

        if (!isset($limits[$userClass][$action])) {
            return true;
        }

        $limit = $limits[$userClass][$action]['limit'];
        if ($limit == 0) {
            if ($log) {
                self::logDenyAction($USER, $action);
            }
            $denyCode = $defaultDenyStatus;
            return false;
        } elseif ($limit > 0) {
            $counterId = !empty($limits[$userClass][$action]['counterId']) ? $limits[$userClass][$action]['counterId'] : null;
            $dayCount = 0;
            if ($counterId !== null) {
                $dayCount = Base_Service_Counter::getUserActionCounts($USER->getId(), $counterId);
            }

            if ($action == Base_Service_Acl::ACTION_USERVOTE_FREE && !Vip_Service_Base::isVip($USER)) {
                $limit = Vip_Service_Base::FREE_VOTE_LIMIT;
            }


            if ($action == Base_Service_Acl::ACTION_MESSENGER_CONTACTS && $USER->isPhoneApproved() && $reggedPlusMonth > time()) {
                if ($dayCount >= 150) {
                    file_put_contents('var/tmp/acldenymessenger150.log', "id: ".$USER->getId()."\t count: ".$dayCount."\t <a href='http://fotostrana.ru/".$USER->getId()."' target='_blank'>user link</a> \n", FILE_APPEND);
                } elseif ($dayCount >= 100) {
                    file_put_contents('var/tmp/acldenymessenger100.log', "id: ".$USER->getId()."\t count: ".$dayCount."\t <a href='http://fotostrana.ru/".$USER->getId()."' target='_blank'>user link</a> \n", FILE_APPEND);
                } elseif ($dayCount >= 50) {
                    file_put_contents('var/tmp/acldenymessenger50.log', "id: ".$USER->getId()."\t count: ".$dayCount."\t <a href='http://fotostrana.ru/".$USER->getId()."' target='_blank'>user link</a> \n", FILE_APPEND);
                }
            }

            if ($dayCount >= $limit) {
                if ($log) {
                    self::logDenyAction($USER, $action);
                }
                $denyCode = $defaultDenyStatus;

                if ($action == self::ACTION_MESSENGER_CONTACTS && $USER->isPhoneApproved() && $reggedPlusMonth > time()) { // сохраняем  в лог, если не получилось добаить собеседника из-за уменьшения макс. колва на первый месяц
                    if (!Antifraud_Dao_Message::isInWhitelist($USER->getId())) {
                        //пока остановим
                        //Antifraud_Dao_Message::addUser($USER->getId(), $dayCount);
                    }
                    file_put_contents('var/log/acl.deny_new_messenger_contact.log', date('Y-m-d H:i:s') . "\t" . $USER['user_id'] . "\t" . $dayCount . "\n", FILE_APPEND);
                }

                return false;
            }
            return true;
        }
        return true;
    }

    public static function getUserLimits($period = Base_Service_Counter::PERIOD_DAY)
    {
        if (!self::$limits) {
            $limits = self::getDayLimits();

            $p = Base_Service_Counter::PERIOD_10MINUTES;
            $userClasses = array(Db_User::USER_CLASS_GUEST, Db_User::USER_CLASS_RESIDENT, Db_User::USER_CLASS_CITIZEN);
            foreach ($userClasses as $class) {
                $limits[$p][$class][self::ACTION_TLOG_COMMENT]['limit'] = 60;
                $limits[$p][$class][self::ACTION_TLOG_COMMENT]['counterId'] = Base_Service_Counter::USER_COUNT_TLOG_COMMENTS;                
            }

            self::$limits = $limits;
        }

        return self::$limits[$period];
    }

    public static function getDayLimits()
    {
        $g = Db_User::USER_CLASS_GUEST;
        $r = Db_User::USER_CLASS_RESIDENT;
        $c = Db_User::USER_CLASS_CITIZEN;

        $limits = Array(
            self::ACTION_TLOG_COMMENT => Array(
                $g => 10,
                $r => 50,
                $c => 2000,
                'counter' => Base_Service_Counter::USER_COUNT_TLOG_COMMENTS
            ),
            self::ACTION_TLOG_VOTE => Array(
                $g => 0
            ),
            self::ACTION_IMAGE_VOTE => Array(
                $g => 0
            ),
            self::ACTION_AVATAR_CREATE => Array(
                $g => 0
            ),
            self::ACTION_USERDOMAIN => Array(
                $g => 0,
                $r => 0
            ),
            self::ACTION_IMAGE_COMMENT => Array(
                $g => 10,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_IMAGE_COMMENTS
            ),
            self::ACTION_USERVOTE_FREE => Array(
                $g => 5,
                $r => 50, // было 10
                $c => 50, // было 15
                'counter' => Base_Service_Counter::USER_COUNT_NICE_VOTE
            ),
            self::ACTION_MESSENGER_CONTACTS => Array(
                $g => 0,
                $r => 20,
                $c => 170,
                'counter' => Base_Service_Counter::USER_COUNT_MESSENGER_CONTACTS
            ),
            self::ACTION_MESSENGER_USE => Array(
                $g => 0,
                $r => 1,
                $c => 1,
            ),
            self::ACTION_FRIENDLINK => Array(
                $g => 6,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_FRIENDLINK
            ),
            self::ACTION_ADD_WALL_RECORD => Array(
                $g => 10,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_WALL_RECORDS
            ),
            self::ACTION_LIVINGROOM_COMMENT => Array(
                $g => 10,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_LIVINGROOM_COMMENTS
            ),
            self::ACTION_COMMENT_PET => Array(
                $g => 50,
                $r => 100,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_PET_COMMENTS
            ),
            self::ACTION_NEWS_COMMENT => Array(
                $g => 0,
                $r => 5,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_NEWS_COMMENT
            ),
            self::ACTION_FORUM_COMMENT => Array(
                $g => 10,
                $r => 50,
                $c => 1000,
                'counter' => Base_Service_Counter::USER_COUNT_FORUM_COMMENTS
            ),
            self::ACTION_GUEST_VISIT => Array(
                $g => 50,
                $r => 200,
                $c => 5000,
                'counter' => Base_Service_Counter::USER_COUNT_GUEST_VISITS
            ),
            self::ACTION_COMMENT_MARKET_ITEM => Array(
                $g => 0,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_MARKET_COMMENT
            ),
            self::ACTION_GUESS => Array(
                $g => 10,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_GUESS
            ),
            self::ACTION_GAME_REQUEST => Array(
                $g => 20,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_GAME_REQUESTS
            ),
            self::ACTION_MISSMISTER_COMMENT => Array(
                $g => 10,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_MISSMISTER_COMMENT
            ),
            self::ACTION_USERPIC_REQUEST => Array(
                $g => 10,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_USERPIC_REQUEST
            ),
            self::ACTION_MOREPHOTO_REQUEST => Array(
                $g => 10,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_MOREPHOTO_REQUEST
            ),
            self::ACTION_CREATE_COMMUNITY => Array(
                $g => 0,
                $r => 0,
                $c => 1,
                'counter' => Base_Service_Counter::USER_COUNT_COMMUNITY_CREATION
            ),
            self::ACTION_MEETING_CLICK => Array(
                $g => 1000,
                $r => 1000,
                $c => 1000,
                'counter' => Base_Service_Counter::USER_COUNT_MEETING_CLICKS
            ),
            self::ACTION_GROUPONS_INVITE => Array(
                $g => 100,
                $r => 100,
                $c => 100,
                'counter' => Base_Service_Counter::USER_COUNT_GROUPON_INVITES
            ),
            self::ACTION_PROFILEFILL_REQUEST => Array(
                $g => 10,
                $r => 50,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_MOREPHOTO_REQUEST
            ),
            self::ACTION_CONTEST_FREE_VOTE => Array(
                $g => 500,
                $r => 500,
                $c => 500,
                'counter' => Base_Service_Counter::USER_COUNT_FREE_VOTES
            ),
            self::ACTION_GUESS_RECIVEMSG => Array(
                $g => 10,
                $r => 10,
                $c => 10,
                'counter' => Base_Service_Counter::USER_COUNT_GUESS_RECIVED_MSG
            ),
            self::ACTION_GUESS_RECIVEMAIL => Array(
                $g => 1,
                $r => 1,
                $c => 1,
                'counter' => Base_Service_Counter::USER_COUNT_GUESS_RECIVED_MAIL
            ),
            self::ACTION_GUESS_CLICK => Array(
                $g => 50,
                $r => 50,
                $c => 2000,
                'counter' => Base_Service_Counter::USER_COUNT_GUESS_CLICKS
            )
        );

        $limitsOldFormat = Array();

        $p = Base_Service_Counter::PERIOD_DAY;

        foreach ($limits as $action => $limit) {
            if (isset($limit[Db_User::USER_CLASS_GUEST])) {
                $limitsOldFormat[$p][Db_User::USER_CLASS_GUEST][$action]['limit'] = $limit[Db_User::USER_CLASS_GUEST];

                if (isset($limit['counter']))
                    $limitsOldFormat[$p][Db_User::USER_CLASS_GUEST][$action]['counterId'] = $limit['counter'];
            }
            if (isset($limit[Db_User::USER_CLASS_RESIDENT])) {
                $limitsOldFormat[$p][Db_User::USER_CLASS_RESIDENT][$action]['limit'] = $limit[Db_User::USER_CLASS_RESIDENT];

                if (isset($limit['counter']))
                    $limitsOldFormat[$p][Db_User::USER_CLASS_RESIDENT][$action]['counterId'] = $limit['counter'];
            }
            if (isset($limit[Db_User::USER_CLASS_CITIZEN])) {
                $limitsOldFormat[$p][Db_User::USER_CLASS_CITIZEN][$action]['limit'] = $limit[Db_User::USER_CLASS_CITIZEN];

                if (isset($limit['counter']))
                    $limitsOldFormat[$p][Db_User::USER_CLASS_CITIZEN][$action]['counterId'] = $limit['counter'];
            }
        }

        return $limitsOldFormat;
    }

    public static function getLimitsDescription()
    {
        $descriptions = array();
        $descriptions[self::ACTION_TLOG_COMMENT] = 'Комментировать чужие тлоги';
        $descriptions[self::ACTION_TLOG_VOTE] = 'Голосовать за тлоги';
        $descriptions[self::ACTION_IMAGE_COMMENT] = 'Комментировать чужие фотки';
        $descriptions[self::ACTION_IMAGE_VOTE] = 'Голосовать за фотки';
        $descriptions[self::ACTION_AVATAR_CREATE] = 'Создавать аватарку';
        $descriptions[self::ACTION_GUESS] = 'Угадывать других людей';
        $descriptions[self::ACTION_USERVOTE_FREE] = 'Голосовать за других людей';
        $descriptions[self::ACTION_MESSENGER_USE] = 'Писать сообщения';
        $descriptions[self::ACTION_MESSENGER_CONTACTS] = 'Лимит по собеседникам';
        $descriptions[self::ACTION_FRIENDLINK] = 'Дружить';
        $descriptions[self::ACTION_USERDOMAIN] = 'Иметь собственный домен';
        $descriptions[self::ACTION_NEWS_COMMENT] = 'Комментировать новости';
        $descriptions[self::ACTION_LIVINGROOM_COMMENT] = 'Комментировать комнаты';
        $descriptions[self::ACTION_CONTEST_PARTICIPATE] = 'Участвовать в конкурсе мисс/мистер';
        $descriptions[self::ACTION_ADMIT] = 'Признаваться';
        $descriptions[self::ACTION_PET_SPORT_PARTICIPATE] = 'Участвовать в соревнованиях питомцев';
        $descriptions[self::ACTION_BE_ON_MAIN_PAGE] = 'Появляться на главной странице';
        $descriptions[self::ACTION_BE_IN_SEARCH] = 'Появляться в поиске';
        $descriptions[self::ACTION_GET_RESIDENT_CLASS] = 'Подтвердить адрес почты';
        $descriptions[self::ACTION_BE_IN_ROTATION] = 'Появляться в ротаторе';
        $descriptions[self::ACTION_PLAY_GAMES] = 'Играть в игры';
        $descriptions[self::ACTION_ADD_ADVERT] = 'подавать объявление с фото';
        $descriptions[self::ACTION_COMMENT_PET] = 'Коментировать питомца';
        $descriptions[self::ACTION_COMMENT_MARKET_ITEM] = 'Комментировать товраы на рынке';
        $descriptions[self::ACTION_ADD_WALL_RECORD] = 'Оставлять записи на стене';
        $descriptions[self::ACTION_FORUM_COMMENT] = 'Оставлять комментарии в форуме';
        $descriptions[self::ACTION_GUEST_VISIT] = 'Быть гостем';
        $descriptions[self::ACTION_MISSMISTER_COMMENT] = 'Комментировать страницу участника мисс/мистер';
        $descriptions[self::ACTION_SEND_GIFT] = 'Дарить подарки';
        $descriptions[self::ACTION_CONTEST_VOTE] = 'Голосовать в конкурсе мисс/мистер';
        $descriptions[self::ACTION_MEETING_CLICK] = 'Принятие решения во Встречах';
        $descriptions[self::ACTION_CONTEST_FREE_VOTE] = 'Бесплатные голоса в голосовании мисс/мистер';

        return $descriptions;
    }

    private static function getNoMainPhotoLimits(Base_Model_User $user)
    {
        $res = array(
            self::ACTION_FRIENDLINK,
            self::ACTION_IMAGE_COMMENT,
            self::ACTION_TLOG_COMMENT,
            self::ACTION_NEWS_COMMENT,
            self::ACTION_LIVINGROOM_COMMENT,
            self::ACTION_CONTEST_PARTICIPATE,
            self::ACTION_ADMIT,
            self::ACTION_GUESS,
            self::ACTION_PET_SPORT_PARTICIPATE,
            self::ACTION_BE_ON_MAIN_PAGE,
            self::ACTION_BE_IN_SEARCH,
            self::ACTION_GET_RESIDENT_CLASS,
            self::ACTION_BE_IN_ROTATION,
            self::ACTION_PLAY_GAMES,
            self::ACTION_ADD_ADVERT,
            self::ACTION_COMMENT_PET,
            self::ACTION_COMMENT_MARKET_ITEM,
        );

        if (!$user->isDatingUser()) {
            $res[] = self::ACTION_MESSENGER_USE;
        }
        if ($user->isPetAppUser()){
            $res[] = self::ACTION_COMMENT_PET;
        }
        return $res;
    }

    private static function logDenyAction($user, $object)
    {
        if ($user) {
            $text = date('Y-m-d H:i:s') . "\t";
            $text .= "deny action\t";
            $text .= "user: {$user['user_id']}\t";
            $class = Userclass_Service::getCurrentClass($user);
            $text .= "user_class: {$class}\t";
            $descriptions = self::getLimitsDescription();
            $object = @$descriptions[$object];
            $text .= "action: {$object}\n";
            file_put_contents('var/log/acl.deny_action.txt', $text, FILE_APPEND);

            //@ log
            if ($object === self::ACTION_MESSENGER_CONTACTS && $user->isPhoneApproved()) {
                file_put_contents('var/tmp/acl.deny_action.log', $text, FILE_APPEND);
            }

            return true;
        }
        return false;
    }

    public static function getLimitMessage(Base_Model_User $user, $item, $purpose = Userclass_Service::PURPOSE_DEFAULT)
    {
        switch ($user->getUserClass()) {
            case Db_User::USER_CLASS_GUEST:
                return _f('Чтобы оставлять больше 10-ти {string} в день, надо {string}.', $item, Userclass_Service::getUpgradeToResidentLink($user, Userclass_Service::FORM_INFINITIVE, $purpose));
            case Db_User::USER_CLASS_RESIDENT:
                return _f('Чтобы оставлять больше 50-ти {string} в день, надо {string}.', $item, Userclass_Service::getUpgradeToCitizenLink($user, Userclass_Service::FORM_INFINITIVE, $purpose));
            case Db_User::USER_CLASS_CITIZEN:
                return _f('Пользователи не могут оставлять больше 2000 {string} в день.', $item);
        }
    }

    private static function getAdminLevels()
    {
        return array(
            self::ADMIN_LEVEL_LOW => array(
                
            ),
            self::ADMIN_LEVEL_MEDIUM => array(
                
            ),
            self::ADMIN_LEVEL_TRUSTED => array(
                
            ),
            self::ADMIN_LEVEL_HIGH => array(
                
            ),
            self::ADMIN_LEVEL_EXTRA => array(
                1,                
            ),
        );        
    }

    public static function getUserBanLevels()
    {
        return array(
            self::MODER_BAN_USER_EVERYWHERE => array(
                1                
            ),            
        );     
    }

    public static $usersCanBan = array(
        1
    );

    public static function getUserBanLevel($userId)
    {
        if (!PRODUCTION) {
            //return self::MODER_BAN_USER_EVERYWHERE;
        }

        $levels = self::getUserBanLevels();
        foreach ($levels as $level => $ids) {
            if (in_array($userId, $ids)) {
                return $level;
            }
        }        
        return self::MODER_BAN_USER_NONE;
    }

    public static function getUserAdminLevel($userId)
    {
        $levels = self::getAdminLevels();
        foreach ($levels as $level => $ids) {
            if (in_array($userId, $ids)) {
                return $level;
            }
        }
        return self::ADMIN_LEVEL_NONE;
    }

    public static function hasAdminRights($userId)
    {
        return self::getUserAdminLevel($userId) > self::ADMIN_LEVEL_NONE;
    }

    private static $adminIds = null;

    public static function getAdminIds()
    {
        if (self::$adminIds === null) {
            $levels = self::getAdminLevels();
            $ids = array();
            foreach ($levels as $adminIds) {
                $ids = array_merge($ids, $adminIds);
            }
            self::$adminIds = $ids;
        }
        return self::$adminIds;
    }
}
