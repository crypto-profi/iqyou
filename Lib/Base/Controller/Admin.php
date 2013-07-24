<?php
/**
 * Abstract Admin controller
 *
 * @author vasmik
 */
abstract class Base_Controller_Admin extends Base_Controller_App
{
    public $_sanitizeUserInput = false;
    
    /**
     * Дергает phpDoс у метода и смотрит там значение @access
     */
    private function getActionAccessLevel($actionName)
    {
        $reflectionClass = new ReflectionClass($this);
        $method = $reflectionClass->getMethod($actionName);
        if (!$method) {
            return Base_Service_Acl::ADMIN_LEVEL_NONE;
        }
        $comment = $method->getDocComment();
        $matches = array();
        
        Utf::preg_match('/@accessAllow\s+(.+)\s+/', $comment, $matches);
        $allowedUsers = isset($matches[1]) ? $matches[1] : '';
        $allowedUsers = explode(',', Utf::trim($allowedUsers));
        $currentUser = Base_Context::getInstance()->getUser();
        if ($currentUser && in_array($currentUser->getId(), $allowedUsers)) {
            return Base_Service_Acl::ADMIN_LEVEL_NONE;
        }
        
        Utf::preg_match('/@access\s+(none|low|medium|trusted|high|extra)\s+/', $comment, $matches);
        $access = isset($matches[1]) ? $matches[1] : '';
        //TODO: это костыль, убрать, когда заработает нормально Reflection
        if ($actionName == 'ouremailAction') {
            return Base_Service_Acl::ADMIN_LEVEL_NONE;
        }
        $accessMap = array(
            'none' => Base_Service_Acl::ADMIN_LEVEL_NONE,
            'low' => Base_Service_Acl::ADMIN_LEVEL_LOW,
            'medium' => Base_Service_Acl::ADMIN_LEVEL_MEDIUM,
            'trusted' => Base_Service_Acl::ADMIN_LEVEL_TRUSTED,
            'high' => Base_Service_Acl::ADMIN_LEVEL_HIGH,
            'extra' => Base_Service_Acl::ADMIN_LEVEL_EXTRA,
        );  
        return isset($accessMap[$access]) ? $accessMap[$access] : Base_Service_Acl::ADMIN_LEVEL_LOW;
    }
    
    
    /**
     * по phpDoc смотрит наличие @accessNoLoginRequest
     */
    private function needLoginRequest($actionName)
    {
        $reflectionClass = new ReflectionClass($this);
        $method = $reflectionClass->getMethod($actionName);
        if (!$method) {
            return Base_Service_Acl::ADMIN_LEVEL_NONE;
        }
        $comment = $method->getDocComment();
        return Utf::preg_match('/@accessNoLoginRequest/', $comment) ? false : true;
    }
    
    private final function isUserAllowedToView($userAdminLevel, $actionName) 
    {
        $accessLevel = $this->getActionAccessLevel($actionName);
        return $userAdminLevel >= $accessLevel;    
    }
    
    public static function generateAdminHash($url = null)
    {
        if ($url === null) {
            $url = $_SERVER['REQUEST_URI'];
        }
        $salt = !empty(Base_Application::getInstance()->config['passwd']['admin_page']['admin_hash_salt'])
                ? Base_Application::getInstance()->config['passwd']['admin_page']['admin_hash_salt']
                : '';
        return md5($url . $salt . 'f7O)dq_3#');
    }
    
    private static function checkAdminHash()
    {
        return isset($_POST['admin_hash']) && $_POST['admin_hash'] == self::generateAdminHash();
    }

    public function preDispatch()
    {
        parent::preDispatch();

        if (PRODUCTION && !$this->delayRightsCheck) {
            if (!$this->USER) {
                throw new Base_Exception_Error401();
            }
            // Костыль для девочек финансового сапорта
            if( !in_array($this->getRequest()->getActionName(), array('transactionAction', 'getfieldsAction')) || !in_array($this->USER->getId(), array(17312838, 3783404,24496690,4981185, 51570269,61000990, /*для ios и android*/9542992, 45866252, 45973220/*для ios и android*/)) ) {
                if (!self::checkAdminHash()) {
                    $actionLevel = $this->getActionAccessLevel($this->request->getActionName());

                    if ($actionLevel != Base_Service_Acl::ADMIN_LEVEL_NONE) {
                        if (!$this->USER) {
                            throw new Base_Exception_Error401();
                        }

                        $userAdminLevel = Base_Service_Acl::getUserAdminLevel($this->USER->getId());
                        if ($userAdminLevel <= Base_Service_Acl::ADMIN_LEVEL_NONE || !$this->isUserAllowedToView($userAdminLevel, $this->request->getActionName())) {
                            $this->redirect('/error/?code=1', true);
                        }
                    }
                }

                if (in_array($this->getRequest()->getActionName(), array('transactionAction', 'getfieldsAction')) && in_array($this->USER->getId(), array(3783404,24496690,61000990))) {
                    Service_AdminLogin::login(Service_AdminLogin::ADMIN_FINANCE);
                } else {
                    // очень нехороший костыль для нитро
                    if ($this->needLoginRequest($this->request->getActionName()) && $this->getRequest()->getActionName() != 'appstatsAction' && $this->USER && !in_array($this->USER->getId(), array(1578, 1347601))) {
                        Service_AdminLogin::login();
                    }
                }
            }
        }
        
        ini_set('display_errors', 'on');
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
    }
}
