<?php
class Base_Error extends Exception 
{
	const TYPE_BASE = 0;
	const TYPE_NOTFOUND = 1;// ������ �� �������
	const TYPE_PARAM = 2;	// �������� ��������� ������
	const TYPE_DB = 3;		// ������ � ���� ������
	const TYPE_ACCESS = 4;	// ������ �������, ������������ �������
    const TYPE_NOCASH = 5;	// �� ������� ����������
    const TYPE_USERCLASS_LIMIT = 6;  // ��������� ��� �������� ������
    const TYPE_IGNORED = 7;  // ����������
	const TYPE_OTHER = 254;	// ������
	
	public static $errorMessage = array(
	    //self::TYPE_BASE => '��������� ����������� ������.',     
	    self::TYPE_NOTFOUND => '������ ������� �� �������� �� �������.',
	    self::TYPE_PARAM => '�������� �������� ���������.',
	    self::TYPE_DB => '��������� ������ ��� ������ � ����� ������.',	    
        self::TYPE_ACCESS => '� ���� �� ������� ���� �������, ����� ��������� ��� ��������.',
        self::TYPE_NOCASH => '� ���� �� ������� ��������, ����� ��������� ��� ��������.',
        self::TYPE_USERCLASS_LIMIT => '���� ������ �� ����� �� ��������� ��������� ��� ��������.',
        self::TYPE_IGNORED => '���� ����������.',
        self::TYPE_OTHER => '��������� ����������� ������.',        
    );
	
	protected $type = self::TYPE_BASE;
	
	public function setType($type){
	    $this->type = $type;
	}
	
	public function getType(){
		return $this->type;
	}
}
