<?php

class Base_Lifecycle
{
    /**
     * @var Base_Controller_Simple
     */
    protected $controller;

    /**
     * @var Base_Request
     */
    protected $request;

    /**
     * @var Base_Response
     */
    protected $response;

    public function __construct()
    {
        $this->request = Base_Context::getInstance()->getRequest();
        $this->response = Base_Context::getInstance()->getResponse();
    }

    public function process()
    {
        $this->initCalledController();

        $this->init();
        $this->preDispatch();

        $actionName = $this->request->getActionName();


        if (!method_exists($this->controller, $actionName)) {
            throw new Base_Exception_Error404();
        }

        $actionResult = 0;
        try {
        	$useProfiler = Base_Service_Profiler_Log::$enabled;
        	if ($useProfiler) {
 		   		Base_Service_Profiler_Log::logGeneral('Started executing of '. get_class($this->controller) . "::preDispatch");
			}
            $this->controller->preDispatch();
        	if ($useProfiler) {
                Base_Service_Profiler_Log::logGeneral('Finished executing of '. get_class($this->controller) . "::preDispatch");
                Base_Service_Profiler_Log::logGeneral('Started executing of '. get_class($this->controller) . "::preProcess");
            }
            $this->controller->preProcess();
            if ($useProfiler) {
                Base_Service_Profiler_Log::logGeneral('Finished executing of '. get_class($this->controller) . "::preProcess");
				Base_Service_Profiler_Log::logGeneral('Started executing of '. get_class($this->controller) . "::" . $actionName);
			}
            $actionResult = $this->controller->$actionName();
        	if ($useProfiler) {
 		   		Base_Service_Profiler_Log::logGeneral('Finished executing of '. get_class($this->controller) . "::" . $actionName);
                Base_Service_Profiler_Log::logGeneral('Started executing of '. get_class($this->controller) . "::postProcess");
			}
            $this->controller->postProcess();
            if ($useProfiler) {
                Base_Service_Profiler_Log::logGeneral('Finished executing of '. get_class($this->controller) . "::postProcess");
            }
        } catch (Exception $exception) {
            if (method_exists($this->controller, 'errorHandler')){
                $this->controller->errorHandler($exception);
            } else {
                throw $exception;
            }
        }

        if (!empty($this->controller->tpl)) {
            if ($this->p('ajax') || !$this->controller->renderHeader) {
                $this->renderAjaxResponse();
            } else {
                $this->renderFullHtmlResponse();
            }
        }

    	if (class_exists('Base_Service_Profiler_Log')) {
 			Base_Service_Profiler_Log::logGeneral('Started executing of '. get_class($this->controller) . "::postDispatch");
    	}
    	$this->controller->postDispatch();
    	if (class_exists('Base_Service_Profiler_Log')) {
 			Base_Service_Profiler_Log::logGeneral('Finished executing of '. get_class($this->controller) . "::postDispatch");
    	}

        return;        
    }

    /**
     * Инициализирует текущий контроллер.
     *
     * @throws Base_Exception_Error404
     * @throws Base_Exception_InternalRedirect
     */
    private function initCalledController()
    {
        $moduleLocation = $this->request->getModuleLocation();
        $subModule = $this->request->getSubModuleName();
        $controllerFileName = 'App/' . ($moduleLocation ? $moduleLocation . '/' : '') . $this->request->getModuleName() . '/Controller/' . ($subModule ? $subModule . '/' : '') . $this->request->getControllerName() . '.php';
        
        if (file_exists($controllerFileName)) {
            require_once($controllerFileName);
        } else {
            $module = strtolower($this->request->getModuleName());

            if ($this->request->getControllerName() == 'Index' && $this->request->getActionName() == 'indexAction') {
                if ((string) (int) $module === $module) {
                    throw new Base_Exception_InternalRedirect('/user/' . $module . '/');
                }
                $user = Base_Dao_User::getUserByPageName($module);
                if ($user) {
                    throw new Base_Exception_InternalRedirect('/user/' . $user['user_id'] . '/');
                }
            } elseif ($module && !preg_match('/[^a-z0-9-\.]/i', $module)) {
                $user = ((string) (int) $module === $module) ? Base_Dao_User::getUserById($module) : Base_Dao_User::getUserByPageName($module);
                if ($user) {
                    $requestUri = str_ireplace('/' . $module . '/', '', $this->request->getRequestUri());
                    throw new Base_Exception_InternalRedirect('/user/' . $user['user_id'] . '/' . $requestUri);
                }
            }
            throw new Base_Exception_Error404();
        }
        $controllerName = ($moduleLocation ? $moduleLocation . '_' : '') . $this->request->getModuleName() . '_Controller_' . ($subModule ? $subModule . '_' : '') . $this->request->getControllerName();

        $this->controller = new $controllerName($this->request, new Zend_Controller_Response_Http());

        $actionName = $this->request->getActionName();
        if ($this->controller->tpl === false) {
            $this->controller->tpl = strtolower(substr($actionName, 0, strpos($actionName, 'Action'))) . '.phtml';
        }

        Base_Context::getInstance()->setController($this->controller);
    }

    /**
     * Вывод ajax-ответа, запрошенного без статик-менеджера
     */
    private function renderAjaxResponse()
    {
        $this->render($this->controller->view->render($this->controller->tpl));
    }

    /**
     * Вывод обычного html-ответа
     */
    private function renderFullHtmlResponse()
    {
        // @todo check for highload
        $includesControllerData = Base_Project_Manager::getProject()->getIncludesControllerData($this->controller, $this->request);
        $iController    = $includesControllerData['controller']; /** @var $iController Base_Interface_IncludesController */
        $templateTop    = $includesControllerData['templateTop'];
        $templateBottom = $includesControllerData['templateBottom'];

        // @todo перенести это куда-то
        // перетягиваем в шапку/футер переменные из вызываемого контроллера
        $iController->view->renderFooter       = $this->controller->renderFooter;
        $iController->view->renderHeader       = $this->controller->renderHeader;
        $iController->view->disablePageCounter = $this->controller->disablePageCounter;
        $iController->view->profilePage        = $this->controller->isProfilePage;
        $iController->view->appPage            = $this->controller->appPage;
        $iController->preDispatch();

        $content = $this->controller->view->render($this->controller->tpl);
        $this->render($this->getHtmlHeader($iController, $templateTop));
        $this->render($content);
        if ($this->controller->renderFooter !== false) {
            $this->render($this->getHtmlFooter($iController, $templateBottom));
        }
    }

    protected function render($data)
    {
        if ($this->controller instanceof Base_Controller_Admin) {
           echo $data;
           return;
        }
        
        $res = preg_replace("/[\t ]+/", " ", $data);
        echo $res;
    }

    protected function init()
    {
        $this->response->setHeader('Expires: Thu, 01 Jan 1970 00:00:01 GMT');
        $this->response->setHeader('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        // всегда модифицируется
        $this->response->setHeader('Cache-Control: no-store, no-cache, must-revalidate');// HTTP/1.1
        $this->response->setHeader('Cache-Control: post-check=0, pre-check=0', false);
        $this->response->setHeader('Pragma: no-cache');// HTTP/1.0

        $this->controller->view = new Base_View(array('helperPath' => 'Lib/Helpers/', 'encoding' => PROJECT_ENCODING));
        $this->controller->view->setScriptPath($this->controller->templatesPath);        
    }


    protected function preDispatch()
    {
        $user = Base_Context::getInstance()->getUser();
        
        $this->requestAjaxUtf();

        if ($this->controller->_sanitizeUserInput == true) {
            $this->escapeAllParams();
        }
    }

    protected function escapeAllParams()
    {
        $params = $this->request->getParams();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (!is_array($v)) {
                        $value[$k] = Utf::htmlspecialchars(($v), ENT_QUOTES);
                    }
                }
            } elseif (!is_object($value)) {
                $value = Utf::htmlspecialchars(($value), ENT_QUOTES);
            }
            $params[$key] = $value;
        }
        $this->request->setParams($params);
    }


    protected function requestAjaxUtf()
    {
        if (!$this->p('ajax') && !$this->p('_ajax')) {
            return ;
        }
        foreach ($this->p('*') as $key => $value) {
            $this->request->setParam($key, $value);        
        }
        Base_Context::getInstance()->getResponse()->setHeader('Content-type: text/html; charset=' . Utf::charset());
    }

    public function p($key)
    {
        if ($key == '*') {
            return $this->request->getParams();
        }
        return $this->request->getParam($key);
    }

    /**
     * Get htmldata for header
     *
     * @param IncludesController $controller
     * @param string $template
     * @return string
     */
    protected function getHtmlHeader($controller, $template)
    {
        $controller->view->USER = Base_Context::getInstance()->getUser();
        $controller->USER = Base_Context::getInstance()->getUser();
        $controller->topAction();
        $controller->view->headTitle = $this->controller->_headTitle;
        $controller->view->skipHeadLine = $this->controller->_skipHeadLine;
        return $controller->view->render($template);
    }

    /**
     * Get htmldata for footer
     *
     * @param IncludesController $controller
     * @param string $template
     * @return string
     */
    protected function getHtmlFooter($controller,$template)
    {
        $controller->bottomAction();
        return $controller->view->render($template);
    }
}