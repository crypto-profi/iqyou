<?php

class Base_Service_UserSource
{
    const TYPE_PET = 1;
    const TYPE_DATING = 2;
    const TYPE_GAME = 3;
    const TYPE_SOCIAL = 4;

    const TYPE_PET_FROM_DATING = 5;     // Пришли на знакомства, а ушли с петом

    const TYPE_QIPED_BONUS = 6;

    const TYPE_PAYMENT_SYSTEM = 7;
    const TYPE_INVITED = 8;

    /*
     * user_source_result с 11 до 15 — юзеры, пришедншие из рассылки
     */
    const TYPE_COMEBACK_2 = 12;
    const TYPE_COMEBACK_4 = 14;
    const TYPE_COMEBACK_5 = 15;

    // Software types
    const TYPE_SOFT_FSM = 20;
    const TYPE_SOFT_ANDROID = 21;
    const TYPE_SOFT_IOS = 22;

    const SOURCE_LANDING_1_QUEST = 601;
    const SOURCE_LANDING_2_IFRAME_W = 602;
    const SOURCE_LANDING_3_BONUS = 603;
    const SOURCE_LANDING_3_OPEN_SITE = 604;
    const SOURCE_LANDING_VKLOGIN = 605;
    const SOURCE_LANDING_LET_SIGNUP = 606;
    const SOURCE_LANDING_NO_MAIL = 607;
    const SOURCE_LANDING_NO_MAIL_QUEST = 608;
    const SOURCE_LANDING_GIFT = 609;
    const SOURCE_LANDING_GIFT_DEP = 610;
    const SOURCE_LANDING_QUEST_3 = 611;
    const SOURCE_LANDING_QUEST_3_FINISHED = 613;
    const SOURCE_LANDING_PUZZLE_GIFT = 614;
    const SOURCE_LANDING_QUEST_4 = 615;
    const SOURCE_LANDING_QUEST_4_1 = 616;

    const SOURCE_LANDING_V_VIDEO = 617;
    const SOURCE_LANDING_V_LIKE = 618;
    const SOURCE_LANDING_V_LIKE_2 = 619;
    const SOURCE_LANDING_V_HOT_OR_NOT = 620;
    const SOURCE_LANDING_V_TEST_1 = 621;
    const SOURCE_LANDING_V_TEST_2 = 622;

    const SOURCE_LANDING_HOTNOT = 623;
    const SOURCE_LANDING_HOTNOT2 = 624;
    const SOURCE_LANDING_HOTNOT_SIMPLE = 625;
    const SOURCE_ODNOKLASSNIKI = 666;
    const SOURCE_LIKE_NEW = 627;

    const SOURCE_LANDING_LEADER_QUEST = 626;
    const SOURCE_LANDING_LEADER_QUEST_VIP = 627;
    const SOURCE_LANDING_SEARCH = 629;
    const SOURCE_PAY_SIGNUP = 630;

    const SOURCE_YANDEX_LANDING_1 = 70;
    const SOURCE_GOOGLE_LANDING_1 = 71;
    const SOURCE_LETITBIT_LANDING_1 = 631;
    const SOURCE_REGISTR_COMMON = 632;
    const SOURCE_VKONTAKTE_APP = 633;
    const SOURCE_LOVE_LETITBIT = 634;
    const SOURCE_VK_LANDING = 635;
    const SOURCE_CONTEXT_ADS = 636;
    const SOURCE_FREETOPAY = 637;

    const SOURCE_LANDING_HELLGARD = 638;
    const SOURCE_LANDING_POKER = 639;

    const SOURCE_AUTO_MSG_ROBOTS = 971;
    const SOURCE_AUTO_MSG_USERS  = 972;
    const SOURCE_AUTO_MSG_NONE   = 973;

    const SOURCE_REAPPROVE_EMAIL   = 974;
    /*
     * From Pet_Service_Base
     */

    // transfered from Start_Service_Pet
    const SOURCE_START_PET_ID        = 101;
    const SOURCE_ENDED_PET_ID        = 102;
    // transfered from Start_Service_Second
    const SOURCE_START_SECOND_PET_ID = 103;
    const SOURCE_ENDED_SECOND_PET_ID = 104;
    // transfered from Start_Service_Questpet
    const SOURCE_QUESTPET_START      = 170;
    const SOURCE_QUESTPET_END        = 171;
    const SOURCE_QUESTPET_MAILRU_NEW = 172;
    const SOURCE_QUESTPET_START_NEW  = 182;
    const SOURCE_QUESTPET_START_NEW_2 = 184;
    const SOURCE_QUESTPET_START_NEW_3 = 185;
    const SOURCE_QUESTPET_START_NEW_4 = 186;
    const SOURCE_QUESTPET_START_NEW_5 = 187;
    
    // default pet source
    const SOURCE_PET_DEFAULT         = 174;
    // transfered from Start_Service_Getpet
    const SOURCE_START               = 150;
    const SOURCE_END                 = 151;
    const SOURCE_CONFIRM             = 152;
    const SOURCE_QUEST_SPORT         = 153;
    const SOURCE_END_SPORT_Q         = 154;
    const SOURCE_KINTORT             = 160;
    const SOURCE_SNOWRIDER           = 161;
    const SOURCE_YAMMY               = 162;
    const SOURCE_SURFRIDER           = 163;

    const SOURCE_DATING_APP          = 633;
    const SOURCE_MAILRU              = 644;

    /**
    * @todo надо вычистить отсюда старые sources, которые были для тестов
    */
    const SOURCE_SEARCH_LANDING      = 701;
    const SOURCE_BADOO               = 702;
    const SOURCE_BADOO_1             = 703; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_BADOO_2             = 704; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_BADOO_BOTS          = 705; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_BADOO_MAILS         = 706; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_OLD_USERS           = 707;
    const SOURCE_BADOO_COMMON        = 720; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_BADOO_MSG           = 721; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_BADOO_SIMPLE        = 741; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_BADOO_WELCOME       = 742; //поменять всех пользователей на SOURCE_BADOO

    const SOURCE_BADOO_STYLE_OLD     = 743; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_BADOO_STYLE_NEW1    = 744; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_BADOO_STYLE_NEW2    = 745; //поменять всех пользователей на SOURCE_BADOO

    const SOURCE_BADOO_SIMPLE_O      = 724; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_BADOO_WELCOME_O     = 725; //поменять всех пользователей на SOURCE_BADOO

    const SOURCE_BADOO_T_OLD         = 738;
    const SOURCE_BADOO_T_NEW         = 739;

    const SOURCE_BADOO_TEST_CTX_MSG  = 750;
    const SOURCE_BADOO_TEST_CTX_INV  = 751;

    const SOURCE_INV_TEST_OLD        = 722; //поменять всех пользователей на SOURCE_BADOO
    const SOURCE_INV_TEST_NEW        = 723; //поменять всех пользователей на SOURCE_BADOO

    const SOURCE_INV_MOBILE          = 840; //поменять всех пользователей на SOURCE_BADOO

    const SOURCE_INVITE_LAND_DATING  = 841;
    const SOURCE_INVITE_LAND_PETS    = 842;
    const SOURCE_INVITE_LAND_FRIENDS = 843;
    const SOURCE_INVITE_LAND_KIDS    = 844;

    //АБ тест на новые приглашения
    const SOURCE_INVITE_USER         = 729;
    const SOURCE_INVITE_USER_OLD     = 730;
    const SOURCE_FEED_LANDING        = 726;
    const SOURCE_INVITE_ME_CONTACT_LANDING  = 727;
    const SOURCE_INVITE_ME_CONTACT_OLD  = 728;

    const SOURCE_INVITE_OLD_MAIN     = 710;
    const SOURCE_INVITE_NEW_MAIN     = 711;
    const SOURCE_LAND_SOC            = 712;
    const SOURCE_RATELANDING_ANKETA  = 713;
    const SOURCE_RATELANDING_MAILRU  = 714;

    const SOURCE_LAND_SOC_PET        = 716;
    const SOURCE_LAND_SOC_ROLL       = 717;
    const SOURCE_SKYMONK_PET         = 718;
    const SOURCE_SKYMONK_DATING      = 719;

    const SOURCE_LAND_SOC_TEST_AUTOMSG = 733;
    const SOURCE_LAND_SOC_TEST_BOT     = 734;

    const SOURCE_ADWORDS_JOOP          = 735;
    const SOURCE_ADWORDS_JOOP_DIRECT   = 736;
    const SOURCE_HELLGARD_ADMITAD      = 737;

    // transfered from Start_Service_Userpet
    const SOURCE_USERPET_START       = 231; //неделя
    // TODO: перенести все константы, в Base_Service_User, например
    const SOURCE_DATING              = 175; // дейтинг
    const SOURCE_DATING_MAINPAGE     = 176; // дейтинг, главная страница
    //с виджетов регистрации
    const SOURCE_DATING_PROFILE      = 177; // дейтинг, профиль
    const SOURCE_DATING_SEARCH       = 178; // дейтинг, поиск
    const SOURCE_DATING_MEETING      = 179; // дейтинг, встречи
    const SOURCE_DATING_MEETINGMAIN  = 180; // дейтинг, главная
    const SOURCE_DATING_VIDEO        = 181; // видео

    const SOURCE_NEWSIGNUP_MAINPAGE  = 183; // тесты новой главной регистрации

    //промо-кодовые юзеры с закладок
    const SOURCE_BOOKMARK_DATING  = 715;
    const SOURCE_BOOKMARK_PET  = 173;
    /*
     * From Base_Dao_User
     */
    // const USER_SOURCE_DATING = 633;
    const SOURCE_LETITBIT = 803;
    const SOURCE_LETITBIT_MESSAGE = 802;
    const SOURCE_TMP_VK = 753;
    // const USER_SOURCE_MAILRU = 644;

    const SOURCE_GAMELEADS = 804;

    const SOURCE_PETAPP_VK = 805;
    const SOURCE_DATINGAPP_VK = 806;
    const SOURCE_PETAPP_MAILRU = 807;
    const SOURCE_DATINGAPP_MAILRU = 808;

    const SOURCE_DATINGAPP_VK_FROM_AD = 809;
    const SOURCE_DATINGAPP_VK_FROM_USER_FROM_AD = 810;
    const SOURCE_DATINGAPP_FB = 811;
    const SOURCE_DATINGAPP_VK_NEW = 812;
    const SOURCE_DATINGAPP_VK_SINKED = 813;
    const SOURCE_DATINGAPP_VK_FROM_AD_NEW = 814;

    const SOURCE_NEW_LANDING = 821;
    const SOURCE_LANDINGEND = 822;
    const SOURCE_RATELANDING = 823;
    const SOURCE_CONTESTLANDING = 824;
    const SOURCE_GUESSLANDING = 825;


    const SOURCE_PHOTOAPP_VK = 830;
    const SOURCE_PHOTOAPP_VK_FROM_AD = 831;
    const SOURCE_PHOTOAPP_VK_FROM_USER_FROM_AD = 832;

    const SOURCE_PHOTOAPP_VK_SINKED = 839;

    const SOURCE_MAILRU_2        = 833;
    const SOURCE_ODNOKLASSNIKI_2 = 834;

    const SOURCE_MOBILEAPP = 835;
    const SOURCE_MOBILEAPP_FB = 836;
    const SOURCE_MOBILEAPP_VK = 837;
    const SOURCE_MOBILEAPP_MAILRU = 838;

    /**
     *  Те, кто пришел на знакомства, но получил пета из попапа обмена трафиком
     */
    const SOURCE_PET_FROM_DATING = 991;

    const SOURCE_QIPED_BONUS = 1000;
    const SOURCE_GUESSNEW = 1001;

    // Сорсы для контактовских офферов
    const SOURCE_OFFER_DATING_10LIKES_START   = 1101;
    const SOURCE_OFFER_DATING_10LIKES_END     = 1102;

    const SOURCE_MAILRU_AUTOREG = 1103;
    const SOURCE_MAILRU_AUTOREG_PET = 1104;
    const SOURCE_MAILRU_AUTOREG_OUR = 1105;

    const SOURCE_TEST_WITH_GUIDE = 1114;
    const SOURCE_TEST_WITHOUT_GUIDE = 1115;

    const SOURCE_QIWI = 1116;

    const SOURCE_FOTOROULETTE_DOMAIN = 1117;
    const SOURCE_FOTOROULETTE_CORPMAIL = 1118;

    const SOURCE_ADF_CU = 1119;
    const SOURCE_ADF_CU_RU = 1120;

    const SOURCE_YANDEX_MONEY = 1121;
    const SOURCE_YANDEX_MONEY_BANNER = 1122;

    const SOURCE_ADLABS_HELLGARD  = 1123;
    const SOURCE_ADLABS_DATING  = 1124;

    const SOURCE_LEMONPAY = 1300;

    const SOURCE_MOBILE_FS = 1400;
    const SOURCE_MOBILE_TRAFFIC = 1401;

    const SOURCE_SOFT_ANDROID = 1420;
    const SOURCE_SOFT_IOS = 1421;

    const SOURCE_PETGIFT = 1125;

    const SOURCE_SKYMONK_DATING_2 = 1126;
    const SOURCE_SKYMONK_DATING_NEW_2 = 1134;
    const SOURCE_SKYMONK_DATING_NEW_NO_BIRTHDAY_2 = 1135;
    const SOURCE_SKYMONK_WANNATALK_NEW_2 = 1136;
    const SOURCE_SKYMONK_FOTOROULETTE = 1132;

    const SOURCE_PET_YANDEX_DIRECT = 1127;
    const SOURCE_PET_VK_PUBLIC = 1128;
    const SOURCE_GAMELANDING_PET = 1129;
    const SOURCE_PET_SINKER = 1130;

    const SOURCE_ADFORCE_CIS = 1131;
    const SOURCE_SKYMONK_FOTOROULETTE_2 = 1133;

    const SOURCE_PET_CRANE = 1137;
    const SOURCE_PET_KIDS = 1139;
    const SOURCE_SKYMONK_PET_NEW_2 = 1138;
    const SOURCE_WEBMONEY = 1140;
    const SOURCE_MAILMONEY = 1141;
    
    const SOURCE_EXTPET_PET = 1142;
    
    const SOURCE_ADWORDS_NEW = 1143;
    
    const SOURCE_ADWORDS_NEW_SEARCH = 1144;

// 1)  adwords UA - display
// 2)  adwords KZ - display
// 3)  adwords UA - search
// 4)  adwords KZ - search
    const SOURCE_ADWORDS_NEW_UA = 1145;
    const SOURCE_ADWORDS_NEW_KZ = 1146;
    const SOURCE_ADWORDS_NEW_UA_SEARCH = 1147;
    const SOURCE_ADWORDS_NEW_KZ_SEARCH = 1148;

    const SOURCE_MEETING_NEW_LANDINGS = 1149;
    const SOURCE_MEETING_NEW_LANDINGS_2 = 1150;
    const SOURCE_MEETING_NEW_LANDINGS_3 = 1151;
    const SOURCE_MEETING_NEW_LANDINGS_4 = 1152;
    const SOURCE_MEETING_NEW_LANDINGS_5 = 1162;
    const SOURCE_MEETING_NEW_LANDINGS_6 = 1156;
    const SOURCE_MEETING_NEW_LANDINGS_7 = 1158;
    const SOURCE_MEETING_NEW_LANDINGS_8 = 1159;
    const SOURCE_MEETING_NEW_LANDINGS_9 = 1160;

    const SOURCE_PICK_NEW_LANDINGS = 1153;
    const SOURCE_YANDEX_DIRECT_ADLIME = 1154;
    const SOURCE_YANDEX_DIRECT_ADLIME_PETS = 1164;
    const SOURCE_FACEBOOK_TRAFFIC = 1155;
    const SOURCE_FACEBOOK_TRAFFIC_PET = 1157;

    const SOURCE_AUDITORIUS_TRAFFIC = 1161;

    const SOURCE_ADWORDS_NEW_TEST = 1163;

    const SOURCE_NEW_TEST_TRAFF = 1165;

    const SOURCE_BEGUN_DATING_TRAFF = 1166;
    const SOURCE_BEGUN_PET_TRAFF = 1167;

    const SOURCE_KOROVIN_YANDEX_TRAFF = 1168;
    const SOURCE_KOROVIN_YANDEX_TRAFF_PET = 1169;    

    const SOURCE_VOTE_LANDING = 1170;
    
    private static $petSources = null;
    private static $petSourcesList = array(
        self::SOURCE_PET_DEFAULT,
        self::SOURCE_START_PET_ID,
        self::SOURCE_ENDED_PET_ID,
        self::SOURCE_START_SECOND_PET_ID,
        self::SOURCE_ENDED_SECOND_PET_ID,
        self::SOURCE_QUESTPET_START,
        self::SOURCE_QUESTPET_END,
        self::SOURCE_QUESTPET_MAILRU_NEW,
        self::SOURCE_START,
        self::SOURCE_END,
        self::SOURCE_CONFIRM,
        self::SOURCE_QUEST_SPORT,
        self::SOURCE_END_SPORT_Q,
        self::SOURCE_KINTORT,
        self::SOURCE_USERPET_START,
        self::SOURCE_LETITBIT,
        self::SOURCE_GAMELEADS,
        self::SOURCE_PETAPP_VK,
        self::SOURCE_PETAPP_MAILRU,
        self::SOURCE_BOOKMARK_PET,
        self::SOURCE_MAILRU_AUTOREG_PET,
        self::SOURCE_LAND_SOC_PET,
        self::SOURCE_SKYMONK_PET,
        self::SOURCE_PETGIFT,
        self::SOURCE_PET_YANDEX_DIRECT,
        self::SOURCE_PET_VK_PUBLIC,
        self::SOURCE_GAMELANDING_PET,
        self::SOURCE_PET_SINKER,
        self::SOURCE_PET_CRANE,
        self::SOURCE_SKYMONK_PET_NEW_2,
        self::SOURCE_EXTPET_PET,
        self::SOURCE_SNOWRIDER,
        self::SOURCE_YAMMY,
        self::SOURCE_SURFRIDER,

        self::SOURCE_QUESTPET_START_NEW,
        self::SOURCE_QUESTPET_START_NEW_2,
        self::SOURCE_QUESTPET_START_NEW_3,
        self::SOURCE_QUESTPET_START_NEW_4,
        self::SOURCE_QUESTPET_START_NEW_5,
    
        self::SOURCE_YANDEX_DIRECT_ADLIME_PETS,
        self::SOURCE_BEGUN_PET_TRAFF,
        self::SOURCE_KOROVIN_YANDEX_TRAFF_PET,
    );

    private static $mailInviteSourceList = array(
        self::SOURCE_BADOO,
        self::SOURCE_BADOO_1,
        self::SOURCE_BADOO_2,
        self::SOURCE_BADOO_BOTS,
        self::SOURCE_BADOO_MAILS,
        self::SOURCE_BADOO_COMMON,
        self::SOURCE_BADOO_MSG,
        self::SOURCE_BADOO_SIMPLE,
        self::SOURCE_BADOO_WELCOME,
        self::SOURCE_BADOO_STYLE_OLD,
        self::SOURCE_BADOO_STYLE_NEW1,
        self::SOURCE_BADOO_STYLE_NEW2,
        self::SOURCE_BADOO_SIMPLE_O,
        self::SOURCE_BADOO_WELCOME_O,
        self::SOURCE_BADOO_T_OLD,
        self::SOURCE_BADOO_T_NEW,
        self::SOURCE_INV_TEST_OLD,
        self::SOURCE_INV_TEST_NEW,
        self::SOURCE_INVITE_USER,
        self::SOURCE_INVITE_USER_OLD,
        self::SOURCE_FEED_LANDING,
        self::SOURCE_INVITE_ME_CONTACT_LANDING,
        self::SOURCE_INVITE_ME_CONTACT_OLD,
        self::SOURCE_INVITE_OLD_MAIN,
        self::SOURCE_INVITE_NEW_MAIN,
        self::SOURCE_BADOO_TEST_CTX_MSG,
        self::SOURCE_BADOO_TEST_CTX_INV,
        self::SOURCE_INV_MOBILE,
        self::SOURCE_INVITE_LAND_DATING,
        self::SOURCE_INVITE_LAND_FRIENDS,
        self::SOURCE_INVITE_LAND_PETS,
        self::SOURCE_INVITE_LAND_KIDS,
    );

    protected static $gameSourcesList = array(
        self::SOURCE_LANDING_HELLGARD,
        self::SOURCE_LANDING_POKER,
    );

    protected static $paymentSystemSourcesList = array(
        self::SOURCE_QIWI,
        self::SOURCE_YANDEX_MONEY,
        self::SOURCE_YANDEX_MONEY_BANNER
    );

    // refererIds
    const REFERER_EMPTY = 1;            // Нет или пустой
    const REFERER_SELF = 2;             // Переход в пределах сайта
    const REFERER_LINK = 3;             // Внешняя ссылка
    const REFERER_MAILING = 4;          // Рассылка
    const REFERER_NOTIFICATION = 5;     //  Письмо с уведомлением

    const REFERER_APP      = 6; // с приложений (дейтинг + петы)
    const REFERER_TOOLBAR       = 7; // с тулбаров (опера + хром)

    private static $refererFields = array(
        self::REFERER_EMPTY         =>  'referer_empty',
        self::REFERER_SELF          =>  'referer_self',
        self::REFERER_LINK          =>  'referer_link',
        self::REFERER_MAILING       =>  'referer_mailing',
        self::REFERER_NOTIFICATION  =>  'referer_notification',

        self::REFERER_APP          =>  'referer_app',
        self::REFERER_TOOLBAR      =>  'referer_toolbar',
    );

    // при добавлении петовской приемки в Start нужно добавлять контроллер в этот список
    private static $_petSourceControllers = array(
        'Questpet',
        'Getpet',
        'Crane',
        'Petgift',
        'Gamelanding',
        'Petland',
        'Second',
        'Sport',
        'Extpet'
    );

    public static function getAllPetSources()
    {
        if (!self::$petSources) {
            // Для поиска по индексу
            self::$petSources = array_combine(self::$petSourcesList, self::$petSourcesList);
        }

        return self::$petSources;
    }

    public static function getMailInviteSources()
    {
        return self::$mailInviteSourceList;
    }

    public static function isMailInvitedUser($user)
    {
        if($user instanceof Base_Model_User){
            /* @var Base_Model_User $user */
            return in_array($user->getSource(), self::$mailInviteSourceList);
        }

        return false;
    }

    public static function getSourceType($source)
    {
        if ($source==self::SOURCE_SOFT_ANDROID) {
            return self::TYPE_SOFT_ANDROID;
        }
        if ($source==self::SOURCE_SOFT_IOS) {
			return self::TYPE_SOFT_IOS;
		}
        $petTypes = self::getAllPetSources();
        if (isset($petTypes[$source])) {
            return self::TYPE_PET;
        }
        return self::TYPE_DATING;
    }

    public static function getRealSourceType($source)
    {
        $pet  = array_combine(self::$petSourcesList,  self::$petSourcesList);
        $game = array_combine(self::$gameSourcesList, self::$gameSourcesList);
        $invited = array_combine(self::$mailInviteSourceList, self::$mailInviteSourceList);
        $paymentSystems = array_combine(self::$paymentSystemSourcesList, self::$paymentSystemSourcesList);

        if (isset($pet[$source])) {
            return self::TYPE_PET;
        } elseif (isset($game[$source])) {
            return self::TYPE_GAME;
        } elseif (isset($paymentSystems[$source])) {
            return self::TYPE_PAYMENT_SYSTEM;
        } elseif (isset($invited[$source])) {
            return self::TYPE_INVITED;
        } else {
            return self::TYPE_DATING;
        }
    }

    public static function getRefererStatField($refererId, $postfix = '_day')
    {
        return isset(self::$refererFields[$refererId]) ?
            self::$refererFields[$refererId] . $postfix :
            self::$refererFields[self::REFERER_SELF] . $postfix ;
    }

    public static function getCurrentReferrerId()
    {
        // Сначала проверим, не с письма ли мы пришли
        $url = $_SERVER['REQUEST_URI'];

        if (Utf::preg_match('!\\A/user/autologin!', $url)) {
            // Check if it is a mailing or notification email
            if (!empty($_GET['t']) && is_numeric($_GET['t'])) {
                return self::REFERER_NOTIFICATION;
            } else {
                return self::REFERER_MAILING;
            }
        }

        //теперь опеределим тулбар или приложения
        if (isset($_GET['utm_campaign'])) {
            switch ($_GET['utm_campaign']) {
                case 'agent_opera':
                case 'agent_chrome':
                case 'toolbar':
                    return self::REFERER_TOOLBAR;
                    break;
                case 'datingapp':
                case 'petapp':
                    return self::REFERER_APP;
                    break;
            }
        }

        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if (empty($referer)) {
            return self::REFERER_EMPTY;
        }

        // Is it one of FS domains?
        $components = @parse_url($referer);
        if (!empty($components['host'])) {

            if (Base_Project_Manager::getDomainModel($components['host'])) {
                // Is our or partner project

                return self::REFERER_SELF;
            }
        }

        return self::REFERER_LINK;
    }

    // сбрасывает сорцы пользователя, если надо - сама определяет source
    public static function resetUserSources($detectSource = false)
    {
        // дропаем l_source
		Service_Base::setCookie('l_source', '', -1);
        // определяем source
        if ($detectSource) {
            if (isset($_GET['source']) && ($t = trim($_GET['source']))) {
                $source = $t;
            } else {
                $request = Base_Context::getInstance()->getRequest();
                if (('Start' === $request->getModuleName())
                    && in_array($request->getControllerName(),
                                self::$_petSourceControllers)
                ) {
                    $source = self::SOURCE_QUESTPET_START;
                } else {
                    $source = self::SOURCE_DATING;
                }
            }

            Service_Base::setCookie('source', $source, 7);
        } else {
            Service_Base::setCookie('source', '', -1);
        }
    }
}
