<?php

class Base_Controller_App extends Base_Controller_Simple
{
    public function preDispatch()
    {
        parent::preDispatch();
    }

    /**
     * ���������� ����� ������� �������� � ����������� ��� ���������� ������� page
     * @param string $param �������� GET, ���������� ����� ��������
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
