<?php

class Base_Dispatcher
{
    protected $_url = '';
    /**
     * @var Base_Request
     */
    protected $_request;

    protected $nonApp = array('user');

    public function __construct()
    {
        $this->_request = Base_Context::getInstance()->getRequest();
        $this->_url = rtrim($this->_request->getPathInfo(), '/');
    }

    protected function match($pattern, $routeInfo, $fullPattern = true)
    {
        $matches = array();
        if (!preg_match("~^{$pattern}" . ($fullPattern ? '$' : '') . '~', $this->_url, $matches)) {
            return false;
        }

        if (!isset($routeInfo['_module'])) {
            return true;
        }

        $count = count($matches);
        foreach ($routeInfo as $key => $param) {
            for ($i = 0; $i < $count; ++$i) {
                $param = str_replace('$' . $i, $matches[$i], $param);
            }
            if ($key === '_module') {
                $processParams = $param;
            } else {
                $this->_request->setParam($key, $param);
            }
        }

        return $processParams;
    }

    public function dispatch()
    {
        $activeSubrouteInfo = null;
        $routes = array(
            '/user/' => array(
                '/user/(\d+)' => array('_module' => 'Profile.Index.indexAction', 'userId' => '$1'),                
            ),
        );
        foreach ($routes as $routePattern => $routeInfo) {
            $processInfo = $this->match($routePattern, $routeInfo, isset($routeInfo['_module']));

            $activeSubrouteInfo = $routeInfo;
            // если это группа маршрутов - ищем внутри группы
            if ($processInfo === true) {
                 foreach ($routeInfo as $subroutePattern => $subrouteInfo) {
                    $processInfo = $this->match($subroutePattern, $subrouteInfo);
                    if ($processInfo) {
                        $activeSubrouteInfo = $subrouteInfo;
                        break;
                    }
                 }
                 if ($processInfo === true) {
                    $processInfo = false;
                 }
            }

            if ($processInfo !== false && isset($activeSubrouteInfo['_location'])) {
                $this->_request->setModuleLocation($activeSubrouteInfo['_location']);
            }

            if ($processInfo) {
                $processInfo = explode('.', $processInfo);
                $this->_request->setModuleName($processInfo[0]);
                $this->_request->setControllerName($processInfo[1]);
                $this->_request->setActionName($processInfo[2]);
            
                return true;
            }
        }

        if ($url = ltrim($this->_url, '/')) { // remove leading slash from $this->_url, which is actually a path
            $urlParts = explode('/', $url);
            $urlPartsCount = count($urlParts);
            if ($urlPartsCount > 3) {
                throw new Base_Exception_Error404;
            }

            $module = $subModule = $controller = $action = null;
            $module = array_shift($urlParts); // since $url is not empty we always have module, shitfting 0
            // define $controller and maybe $subModule
            $module = ucfirst($module);
            
            $controller = array_shift($urlParts) // we are not sure about having controller in url parts, shifting 1 or 2 if submodule
                or $controller = 'index';
        
            // we definitely have $module and $controller so far + maybe $subModule
            $action = array_shift($urlParts) // we are not sure about having action in url parts
                or $action = 'index';
            // last preparations for setters
            $controller = ucfirst($controller);
            $action = $action . 'Action';
            // actually setting retrieved $module, $subModule, $controller and $action
            $this->_request->setModuleName($module);
            if ($subModule) {
                $this->_request->setSubModuleName($subModule);
            }

            $this->_request->setControllerName($controller);
            $this->_request->setActionName($action);
        } else { // if default page - setting default controller
            $this->setDefaultPageController(Base_Context::getInstance()->getUser(), $this->_request);
        }

        return true;
    }


    /**
     * @param Base_Model_User $user
     * @param Base_Request $request
     * TODO перенести!
     */
    public function setDefaultPageController($user, Base_Request $request)
    {
        if ($user) {
            $request->setModuleName('Index');
            $request->setControllerName('Welcome');
            $request->setActionName('indexAction');
            return;
        }
        $request->setModuleName('Index');
        $request->setControllerName('Index');
        $request->setActionName('indexAction');
    }
}
