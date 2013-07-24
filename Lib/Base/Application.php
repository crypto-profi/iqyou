<?php

class Base_Application
{
    private static $instance = null;
    /**
     * @var Base_Lifecycle
     */
    protected $lifecycle;
    /**
     * @var array
     */
    public $config = null;
    /**
     * @var Base_Context
     */
    protected $context;

    /**
     * @param array $config
     */
    public function __construct($config = null)
    {
        $this->config = $config;

        self::$instance = $this;
    }

    /**
     * @return Base_Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param Base_Context $context Context instance
     */
    public function setContext($context)
    {
        $this->context = $context;        
    }

    /**
     * Creates application singleton.
     *
     * @return Base_Application
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        // init context first
        $context = Base_Context::getInstance();
        $context->setApplication($this);
        $context->setLogger(new Base_Logger('var/errors.txt'));

        $this->setContext($context);

        $db = new Driver_Db();
        $context->setDbConnection($db);

        // инициализируем FILES
        Base_Service_Upload::init();

        $context->setRequest(new Base_Request());
        $context->setResponse(new Base_Response());

        $user = $this->initUser();
        $this->dispatch();        
    }

    protected function dispatch()
    {
        $dispatcher = new Base_Dispatcher();
        $result = $dispatcher->dispatch();

        if (!$result) {
            throw new Base_Exception_Error404();
        }
    }

    protected function checkForRedirect()
    {
        // если не гет или аякс - редирект не нужен
        if (($_SERVER['REQUEST_METHOD'] !== 'GET')
            || !($request = $this->getContext()->getRequest())
            || $request->isXmlHttpRequest()
        ) {
            return false;
        }

        $path = $request->getRequestUri();
        if ($pos = strpos($path, '?')) {
            $path = substr($path, 0, $pos);
        }
        $newPath = $path = urldecode($path);

        $specSymbols = '\.!@#$%^&*\-+=() '; // непонятно
        $allowedSymbols = '0-9a-z';

        // удаляем последний символ, если он - один из запрещенных и идет после
        // разрешенного или слеша
        // добавляем на конце слеш, если это запрос не к файлу
        $newPath = preg_replace(
            array(
                '~([' . $allowedSymbols . '/])[' . $specSymbols . ']$~i',
                '~(/[' . $allowedSymbols . ']+)$~i',
            ),
            array(
                '$1',
                '$1/'
            ),
            $newPath
        );

        if (in_array($newPath, array('/index.php', '/index.php/'))) {
            $newPath = '/';
        }
        

        if ($path !== $newPath) {
            if (!empty($_SERVER['QUERY_STRING'])) {
                $newPath = $newPath . '?' . $_SERVER['QUERY_STRING'];
            }
            throw new Base_Exception_Redirect($newPath, 301);
        }
        return false;
    }

    protected function beforeRun()
    {
        $this->checkForRedirect();
        $this->lifecycle = new Base_Lifecycle();
    }

    protected function afterRun()
    {
        

    }

    protected function process()
    {
        $this->lifecycle->process();
    }

    public function run($skipInitialization = false)
    {
        $db = Base_Context::getInstance()->getDbConnection();
        if (!$db) {
            $db = new Driver_Db();
            Base_Context::getInstance()->setDbConnection($db);
        }

        try {
            if (!$skipInitialization) {
                // Внутри init уже есть $this->dispatch();
                $this->init();
            } else {
                // А это обычно при internalRedirect происходит
                $this->dispatch();
            }
            $this->beforeRun();
            $this->process();
            $this->afterRun();
            $user = Base_Context::getInstance()->getUser();            
        } catch (Base_Exception_InternalRedirect $exception) {
            $url = $exception->getUrl();
            $this->internalRedirect($url);
        } catch (Base_Exception_Redirect $exception) {
            $exception->handle();
        } catch (Base_Exception $exception) {
            
            $this->getContext()->getResponse()->setHeader('HTTP/1.0 500 Internal Server Error');

            $exception->handle();
        } catch (Base_Error_Access $exception) {
            
            $this->getContext()->getResponse()->setHeader('HTTP/1.0 403 Forbidden');

            $newException = new Base_Exception($exception->getMessage(), $exception->getCode());
            $newException->setType(Base_Exception::TYPE_ACCESS_DENY);
            $newException->handle(false);
        } catch (Base_Error $exception) {
            
            $this->getContext()->getResponse()->setHeader('HTTP/1.0 500 Internal Server Error');

            $newException = new Base_Exception($exception->getMessage(), $exception->getCode());
            $newException->setType(Base_Exception::TYPE_BASE);
            $newException->handle(false);
        } catch (Exception $exception) {
            //FIX this
            $this->getContext()->getResponse()->setHeader('HTTP/1.0 500 Internal Server Error');

            $newException = new Base_Exception($exception->getMessage(), $exception->getCode());
            $newException->setType(Base_Exception::TYPE_BASE);
            $newException->handle(false);
            Base_Exception::logError($exception->getMessage(), $exception->getTraceAsString());
        }        
    }

    protected function internalRedirect($url)
    {
        $request = $this->getContext()->getRequest();
        $currentUrl = $request->getPathInfo();

        if ($currentUrl == $url) {
            throw new Base_Exception('Internal redirect happened on the same url ' . $url);
        }

        $_SERVER['REQUEST_URI'] = $url;
        if (($tmp = parse_url($url)) && !empty($tmp['query'])) {
            $_SERVER['QUERY_STRING'] = $tmp['query'];
            parse_str($_SERVER['QUERY_STRING'], $tmp);
            $_GET = array_merge($_GET, $tmp);
        } else {
            $_SERVER['QUERY_STRING'] = '';
        }

        $context = Base_Context::getInstance();
        $newRequest = new Base_Request();
        $newRequest->setInternal();
        $context->setRequest($newRequest);

        $request->setPathInfo($url);
        $request->setRequestUri($url);

        $this->run(true);
    }

    protected function initUser()
    {
        $this->getContext()->setUser(null);
        
        if (empty($_COOKIE['uid']) || empty($_COOKIE['hw'])) {
            return false;
        }

        if (!($user = Base_Dao_User::getUserById((int) $_COOKIE['uid']))) {
            return false;
        }

        if ($user->isBanned() == Db_Moders::BAN_REASON_USER_IS_DELETED) {
            Base_Service_User::logout();
            return false;
        }

        $givenAuthHash = $_COOKIE['hw'];

        $authHashNew = Base_Service_User::getAuthHash($user->getId(), $user->getEmail(), $user->getPasswordHash(), false);
        if ($givenAuthHash != $authHashNew) {
            $authHash = Base_Service_User::getAuthHash($user->getId(), $user->getEmail(), $user->getPasswordHash(), true);
            if ($givenAuthHash != $authHash) {
                return false;
            }

            // меняем старую куку на новую
            Base_Service_User::logIn($user);            
        }

        $this->getContext()->setUser($user);

        return $user;
    }
}
