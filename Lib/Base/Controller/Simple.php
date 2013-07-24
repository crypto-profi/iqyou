<?php
class Base_Controller_Simple
{
    const DEFAULT_HEADER_TPL = 1;
    const SMALL_HEADER_TPL = 2;
    const TINY_HEADER_TPL = 3;
    const LANDING_HEADER_TPL = 4; // Нужно только для новых лэндингов на основе нового Signup
    const LANDING_NO_HEADER_TPL = 5; // Нужно только для новых лэндингов на основе нового Signup
    const LANDING_HEADER_NO_LINK_TPL = 6; // Нужно только для новых лэндингов на основе нового Signup
    const LANDING_HEADER_USER_PROFILE_TPL = 7; // Нужно только для новых лэндингов на основе нового Signup

    const DEFAULT_FOOTER_TPL = 1;
    const EMPTY_FOOTER_TPL = 2;
    const LANDING_FOOTER_COPYRIGHT_TPL = 3;
    const LANDING_FOOTER_FULL = 4;

    const TEMPLATE_TYPE_SMARTY = 1;
    const TEMPLATE_TYPE_ZEND   = 3;

    const ERROR_CODE_UNKNOWN = 0;
    const ERROR_CODE_FAIL = 1;
    const ERROR_CODE_SHOW_LOGIN_POPUP = 2;
    const ERROR_CODE_SHOW_UPLOAD_MAIN_PHOTO_POPUP = 3;
    const ERROR_CODE_SHOW_APPROVE_EMAIL_POPUP = 4;
    const ERROR_CODE_SHOW_APPROVE_PHONE_POPUP = 5;
    const ERROR_CODE_SHOW_UPLOAD_MORE_PHOTOS = 6;

    const CALLBACK_ERROR_NOTIFY = 'unknownError';
    const UNKNOWN_ERROR_TEXT = 'Ошибка на стороне сервера.';

    const AJAX_STATUS_FAIL = 0;
    const AJAX_STATUS_SUCCESS = 1;

    const ERROR_DATA = 'data';
    const ERROR_CALLBACK = 'callback';
    const ERROR_DATA_TEXT = 'text';
    const ERROR_DATA_FIELD = 'field';

    const FONT_SIZE_NORMAL = 0;
    const FONT_SIZE_LARGE = 1;
    protected $_fontSize = self::FONT_SIZE_NORMAL;

    protected static $_systemError = array(
        self::ERROR_CODE_UNKNOWN => array(
            'Ой! Что-то сломалось! Попробуйте обновить страницу - и всё получится!'
        ),
        self::ERROR_CODE_SHOW_LOGIN_POPUP => array(
            array('message' => 'Чтобы совершить это действие, необходимо зарегистрироваться или авторизоваться на сайте.'),
            'showUserLogin'
        ),
        self::ERROR_CODE_SHOW_UPLOAD_MAIN_PHOTO_POPUP => array(
            array('message' => 'Чтобы совершить это действие, необходимо загрузить аватарку.'),
            'showMainPhotoUpload'
        ),
        self::ERROR_CODE_SHOW_APPROVE_EMAIL_POPUP => array(
            array('message' => 'Чтобы совершить это действие, необходимо подтвердить почту.'),
            'showApproveEmail'
        ),
        self::ERROR_CODE_SHOW_APPROVE_PHONE_POPUP => array(
            array('message' => 'Чтобы совершить это действие, необходимо подтвердить телефон.'),
            'showApprovePhone'
        ),
    );

    protected static $_htmlCharset = null;

    /**
     * @var Base_Request
     */
    protected $request;
    /**
     * @var Base_Context
     */
    protected $context;
    /**
     * @var Base_View
     */
    public $view;
    public $_ajaxData = array();
    public $_ajaxExtData = array();
    public $_ajaxErrorsData = array();
    public $_ajaxRedirect = '';

    /**
     * @var Base_Model_User
     */
    public $USER = null;
    /**
     * @var Base_Model_User
     */
    public $viewUser = null;

    public $renderHeader = false;
    public $renderFooter = true;
    /**
     * @var boolean
     * опция для лайф-сайкла - переводить ли ajaxData в ютф рекурсивно (true), или считать что уже все переведено (false)
     */
    public $ajaxDataToUtf = true;
    public $ajaxErrorDataToUtf = true;
    public $ajaxExtDataToUtf = true;
    public $isProfilePage = false;
    public $serviceAppId = false; // сюда контроллер сервиса будет писать свой аппайди, дабы в футере можно было повесить ссылку на попап с настройками

    public $templatesPath = null;
    public $templatesType = self::TEMPLATE_TYPE_ZEND;

    /**
     * @var Driver_Db
     */
    public $db;
    public $tpl = false;
    public $_headTitle = '';
    public $_skipHeadLine = false;
    public $_sanitizeUserInput = true;

    protected $checkIncomingUserId = true; // чтобы отключить проверку входного параметра userId. переназвать свой параметр мы уже не можем, к сожалению, поэтому привет костыль.

    /**
     * @var BaseLayout_Model_Base
     */
    protected $_layout;


    public function __construct()
    {
        $this->context = Base_Context::getInstance();
        $this->request = $this->context->getRequest();
        if (!$this->templatesPath) {
            $moduleLocation = $this->request->getModuleLocation();
            $this->templatesPath = './App/' . ($moduleLocation ? $moduleLocation . '/' : '') . $this->request->getModuleName() . '/Templates/';
        }        
    }

    public function preDispatch()
    {
        $this->db = $this->context->getDbConnection();

        $this->USER = $this->context->getUser();

        $this->view->USER = $this->context->getUser();

        if (!empty($this->USER)) {
            Base_Service_User::checkUserBanCookie($this->USER);
        }
    }

    protected function setLayoutModel(BaseLayout_Model_Base $layout)
    {
        $this->_layout = $layout;
    }

    public function getLayoutModel()
    {
        return $this->_layout;
    }

    public function getFullPageHtml()
    {
        $layout = $this->getLayoutModel();
        $contentHtml = $this->tpl ? $this->view->render($this->tpl) : '';
        return $layout->renderPage($contentHtml, $this->getRequest(), $this);
    }

    public function redirect($url = '/', $internal = false, $status = null, $ajaxNav = true)
    {
        if ($internal) {
            throw new Base_Exception_InternalRedirect($url);
        } else {
            throw new Base_Exception_Redirect($url, $status);
        }
    }

    /**
     * Enter description here...
     *
     * @return Base_Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Enter description here...
     *
     * @return Base_Context
     */
    public function getContext()
    {
        return $this->context;
    }

    protected function norender()
    {
        $this->tpl = null;
    }

    public function p($key, $default = null, $type = Base_Request::PARAM_TEXT)
    {
        return $this->getRequest()->getParamWithType($key, $default, $type);
    }
    
    /**
     * Вызывается ПОСЛЕ рендеринга страницы (уж так сожилось :((
     */
    public function postDispatch()
    {

    }

    /**
     * Вызывается после preDispatch, но до Action
     */
    public function preProcess()
    {

    }

    /**
     * Вызывается ДО рендеринга страницы, но после Action
     */
    public function postProcess()
    {

    }

    /**
    * Метод для отправки PHP-массива при помощи json. Преобразует данные в нужную кодировку,
    * если передан ajax = 1, добавляет заголовок Content-Type
    */
    protected function ajax(array $data)
    {
        $this->norender();
    
        array_walk_recursive($data,
            create_function('&$value', 'if (is_string($value)) { $value = @iconv("'.Utf::charset().'", "utf-8", $value); }')
        );

        if ($this->p('ajax')) {
            header("Content-Type: application/json");
        }
        
        echo Zend_Json::encode($data);
    }

    protected function ajaxError($message = null, array $data = array())
    {
        if ($message !== null) {
            $data['message'] = $message;
        }

        $this->ajax($data + array('ret'  =>  0));
        return false;
    }

    protected function ajaxSuccess(array $data = array())
    {
        $this->ajax($data + array('ret'  =>  1));
        return true;
    }

    /**
     * Выводит ответ AJXA'а в формате JSON.
     * @param array $data данные ответа
     */
    protected function _ajax(array $data)
    {
        $this->norender();

        header("Content-Type: application/json");

        echo Zend_Json::encode($data);
    }


    /**
     * Ответ сервера в случае удачного завершения ajax.
     * @param array $data возвращаемые данные
     * @param array $extData доп. данные ответа (см. Base_Controller_Simple::AJAX_)
     *
     * @return bool
     */
    protected function _ajaxSuccess(array $data = array(), array $extData = array())
    {
        $this->norender();

        $this->_ajaxData = $data;
        $this->_ajaxExtData = $extData;

        return self::AJAX_STATUS_SUCCESS;
    }

    /**
     * Ответ сервера в случае неудачного завершения ajax.
     * @param array|string|int $errors ошибки
     * @param array|string|int $data данные ответа
     * @param null|array|string|bool $extErrorData доп. параметры:
     * если null в параметре $errors передан код ошибки
     * если array параметры предустановленной ошибки
     * если string callback
     * если true ошибки отдаются в том формате котором переданы
     * @return bool
     */
    protected function _ajaxError($errors = self::UNKNOWN_ERROR_TEXT, $extErrorData = array(), array $data = array())
    {
        $this->norender();

        $this->_ajaxErrorsData = array();
        $this->_ajaxData = $data;

        if ($extErrorData === true) {
            $this->_ajaxErrorsData[] = $errors;
        } elseif (is_string($errors)) {
            $this->_ajaxErrorsData[] = array(
                self::ERROR_DATA => array(self::ERROR_DATA_TEXT => $errors),
                self::ERROR_CALLBACK => self::CALLBACK_ERROR_NOTIFY
            );
        } elseif (is_numeric($errors) && is_array($extErrorData)) {
            $error = $this->getCustomError($errors, self::$_systemError, $extErrorData);
            $this->_ajaxErrorsData[] = $this->showCustomError($error);
        } elseif (is_array($errors) && is_string($extErrorData)) {
            $this->_ajaxErrorsData[] = array(
                self::ERROR_DATA => $errors,
                self::ERROR_CALLBACK => $extErrorData
            );
        } elseif (is_array($errors) && !empty($errors)) {
            foreach ($errors as $error) {
                if (is_string($error)) {
                    $this->_ajaxErrorsData[] = array(
                        self::ERROR_DATA => array(self::ERROR_DATA_TEXT => $error),
                        self::ERROR_CALLBACK => self::CALLBACK_ERROR_NOTIFY
                    );
                } elseif (is_array($error)) {
                    if (isset($error[0])) {
                        if (is_numeric($error[0])) {
                            $error = $this->getCustomError($error[0], self::$_systemError, (isset($error[1]) && is_array($error[1]) ? $error[1] : array()));
                            $this->_ajaxErrorsData[] = $this->showCustomError($error);
                        } elseif (is_array($error[0]) && isset($error[1]) && is_string($error[1])) {
                            $this->_ajaxErrorsData[] = array(
                                self::ERROR_DATA => $error[0],
                                self::ERROR_CALLBACK => $error[1]
                            );
                        } else {
                            $this->_ajaxErrorsData[] = $error;
                        }
                    } else {
                        $this->_ajaxErrorsData[] = $error;
                    }
                } else {
                    $this->_ajaxErrorsData[] = $error;
                }
            }
        } else {
            $this->_ajaxErrorsData[] = $errors;
        }

        return self::AJAX_STATUS_FAIL;
    }

    public function getCustomError($errorCode = self::ERROR_CODE_FAIL, array $errors = array(), array $data = array())
    {
        if (array_key_exists($errorCode, $errors)) {
            $result = $errors[$errorCode];
            if (is_array($result[0])) {
                foreach($data as $key => $param) {
                    $result[0][$key] = $param;
                }
            }
        } else {
            $result = array();
        }
        return $result;
    }

    private function showCustomError(array $data = array()) {
        if ($data) {
            if (is_string($data[0])) {
                return array(
                    self::ERROR_DATA => array(self::ERROR_DATA_TEXT => $data[0]),
                    self::ERROR_CALLBACK => self::CALLBACK_ERROR_NOTIFY
                );
            } elseif (is_array($data[0]) && is_string($data[1])) {
                return array(
                    self::ERROR_DATA => $data[0],
                    self::ERROR_CALLBACK => $data[1]
                );
            } elseif (is_numeric($data[0])) {
                return $data[0];
            } else {
                return $data;
            }
        } else {
            return $data;
        }
    }

    /**
     * Установить кодировку Content-Type для html
     * @param string $charset
     * @return void
     */
    protected function setHtmlCharset($charset)
    {
        self::$_htmlCharset = (string) $charset;
    }

    /**
     * Получить кодировку Content-Type для html
     * @return string
     */
    protected function getHtmlCharset()
    {
        return self::$_htmlCharset == null ? Utf::charset() : self::$_htmlCharset;
    }

    /**
     * Защита от CSRF-атак
     * @param bool $token переданный в запросе токен, по умолчанию используется токен из заголовков (передается неявно)
     * @return bool true если запрос валидный, иначе false
     */
    protected function checkToken($token = false)
    {
        $token = $token
               ? $token
               : (isset($_SERVER['HTTP_X_SIMPLE_TOKEN']) ? $_SERVER['HTTP_X_SIMPLE_TOKEN'] : false);

        return Antispam_Service_Token::checkToken($token);
    }

    public function isAjax()
    {
        if ($this->p('_ajax')) {
            return 2;
        }
        if ($this->p('_ajax')) {
            return 1;
        }
        if ($this->getRequest()->isXmlHttpRequest()) {
            return 13;
        }

        return 0;
    }
}