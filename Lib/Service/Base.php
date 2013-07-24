<?php

class Service_Base
{
    protected static $sentP3P = false;

    /**
     * @deprecated
     */
    public static function sex($user, $man, $woman)
    {
        $sex = isset($user['user_sex']) ? @$user['user_sex'] : 'm';
        return ($sex == 'w' ? $woman : $man);
    }

    public static function useCookieUrldecode()
    {
        return !Base_Service_Common::isOurIp();
    }

    public static function getCookie($key, $default = '')
    {
        if (self::useCookieUrldecode()) {
            return isset($_COOKIE[$key]) ? urldecode($_COOKIE[$key]) : $default;
        } else {
            return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
        }
    }

    /**
     * Cookie manage
     * @param string  $key
     * @param string  $value
     * @param integer|null $days    Count of expiration days or timestamp, if null - set to session
     * @param string  $path         Base path
     * @param boolean $isDays       Param $days is count of days or timestamp
     * @param bool|int $onMainHost  Set cookie to main host of project if possible
     *                                0|false  - do not try to set on main host
     *                                1|true   - try to set on main host
     *                                2        - try to set only main host if possible otherwise behaves like false
     */
    public static function setCookie($key, $value, $days = 100, $path = '/', $isDays = true, $onMainHost = false)
    {
        $_COOKIE[$key] = $value;

        if (defined('TESTING')) {
            return;
        }

        if (!self::$sentP3P) {
            if (empty($_SERVER['HTTP_USER_AGENT']) || Utf::stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE ') !== false) {
                header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
                self::$sentP3P = true;
            }
        }

        $host = Base_Project_Manager::getProject()->getDomain();
        if (Utf::strpos($host, 'www.') === 0) {
            $host = Utf::substr($host, 4);
        }
        if (($port = Utf::strpos($host, ':')) !== false) {
            $host = Utf::substr($host, 0, $port);
        }


        if ($isDays) {
            $days = round((int) $days * 86400);
        }

        $expire = ($days === null) ? 0 : (TIME + $days);

        if ($onMainHost = (int) $onMainHost) {
            $canSetMainHost = ($host !== PROJECT_DOMAIN
                && strpos($host, PROJECT_DOMAIN) !== false
            );
        }

        if($onMainHost===1 && $canSetMainHost) {
            setcookie($key, $value, $expire, $path, '.' . PROJECT_DOMAIN);
        } elseif($onMainHost===2 && $canSetMainHost) {
            $host = PROJECT_DOMAIN;
        }
        setcookie($key, $value, $expire, $path, '.' . $host);
    }

    /**
     * @deprecated
     *
     * @todo delete later
     */
    public static function galleryUrl($img, $type, $size)
    {
        return Base_Service_Photo::getGalleryImageUrl($img, $size);
    }

    /**
     * @deprecated
     *
     * @todo delete later
     * @param Base_Model_User $user
     * @param String $size
     */
    public static function userImgUrl($user, $size)
    {
        return Base_Service_Photo::getUserImageUrl($user, $size);
    }

    /**
     * @deprecated
     *
     * @todo delete later
     */
    public static function imageUrl($type, $size, $id)
    {
        return Base_Service_Photo::getImageUrl($type, $size, $id);
    }

    /**
     * @deprecated
     *
     * גûחמגû ג Db_User
     *
     * @param $array
     * @param $key
     */
    public static function arrayKey($array, $key)
    {
        if(!is_array($array))
            return array();
        $result = array();
        foreach ($array as $rec) {
            if(is_object($rec)) {
                $result[] = @$rec->$key;
            }
            else {
                $result[] = @$rec[$key];
            }
        }
        return $result;
    }
}