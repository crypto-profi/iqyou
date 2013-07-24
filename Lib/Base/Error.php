<?php
class Base_Error extends Exception 
{
	const TYPE_BASE = 0;
	const TYPE_NOTFOUND = 1;// данные не найдены
	const TYPE_PARAM = 2;	// неверные параметры вызова
	const TYPE_DB = 3;		// ошибка в базе данных
	const TYPE_ACCESS = 4;	// ошибка доступа, недостаточно средств
    const TYPE_NOCASH = 5;	// не хватает наличности
    const TYPE_USERCLASS_LIMIT = 6;  // запрещено для текущего класса
    const TYPE_IGNORED = 7;  // игнорируют
	const TYPE_OTHER = 254;	// прочее
	
	public static $errorMessage = array(
	    //self::TYPE_BASE => 'Произошла неизвестная ошибка.',     
	    self::TYPE_NOTFOUND => 'Данные которые ты запросил не найдены.',
	    self::TYPE_PARAM => 'Переданы неверные параметры.',
	    self::TYPE_DB => 'Произошла ошибка при работе с базой данных.',	    
        self::TYPE_ACCESS => 'У тебя не хватает прав доступа, чтобы совершить это действие.',
        self::TYPE_NOCASH => 'У тебя не хватает ФотоМани, чтобы совершить это действие.',
        self::TYPE_USERCLASS_LIMIT => 'Твой статус на сайте не позволяет выполнять это действие.',
        self::TYPE_IGNORED => 'Тебя игнорируют.',
        self::TYPE_OTHER => 'Произошла неизвестная ошибка.',        
    );
	
	protected $type = self::TYPE_BASE;
	
	public function setType($type){
	    $this->type = $type;
	}
	
	public function getType(){
		return $this->type;
	}
}
