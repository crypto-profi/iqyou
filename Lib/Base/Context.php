<?php

class Base_Context
{
    private $application;
    private $user;
    private $dbConnection;
    private $controller;
    /**
     * @var Base_Request
     */
    private $request;
    /**
     * @var Base_Response;
     */
    private $response;
    /**
     * @var Base_Logger
     */
    private $logger;

    private $attributes = array();

    // Context singleton ------------------------------------------------------

    private static $instance = null;

    protected function __construct()
    {
    }

    /**
     * Creates context singleton.
     *
     * @return Base_Context
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Properties -------------------------------------------------------------

    /**
     * @return Base_Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Set application reference
     *
     * @param Base_Application $application
     */
    public function setApplication($application)
    {
        $this->application = $application;
    }

    /**
     * @return Base_Model_User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param array $user
     */
    public function setUser($user)
    {
        $this->user = $user;
        $this->attributes['_user'] = $user;
    }

    public function setDbConnection($db)
    {
        $this->dbConnection = $db;
    }

    /**
     * @return Driver_Db
     */
    public function getDbConnection()
    {
        return $this->dbConnection;
    }
    
    /**
     * Return request object instance
     *
     * @return Base_Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Base_Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * Return response object instance
     * @return Base_Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Base_Response $response
     */
    public function setResponse(Base_Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Base_Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }
    
    /**
     * @param Base_Logger $logger 
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * @param Base_Controller_Simple $controller 
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }
    
    /**
     * @return Base_Controller_Simple 
     */
    public function getController()
    {
        return $this->controller;
    }
     

    // Access ------------------------------------------------------------------

    public function get($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
    }

    public function set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function has($key)
    {
        return isset($this->attributes[$key]);
    }

    public function remove($key)
    {
        unset($this->attributes[$key]);
    }
}