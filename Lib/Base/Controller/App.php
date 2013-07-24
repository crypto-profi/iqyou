<?php

class Base_Controller_App extends Base_Controller_Simple
{
    public function preDispatch()
    {
        parent::preDispatch();
    }

    /**
     * Возвращает номер текущей страницы и присваивает его переменной шаблона page
     * @param string $param параметр GET, содержащий номер страницы
     * @return int
     */
    public function getCurrentPageNumber($param='page')
    {
        $this->view->page = (int)$this->p($param);
        if ($this->view->page < 1) {
            $this->view->page = 1;
        }
        return $this->view->page;
    }
}
