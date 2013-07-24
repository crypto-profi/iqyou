<?php
/**
 * Класс реализует атомарный семафор (блокировку) при помощи команды MEMCACHE ADD.
 *
 * Атомарный - значит успешно работающий для 2х параллельных процессов. Грубо говоря, если
 * 2 запроса к рнр пришли "абсолютно одновременно", и оба пытаются поставить блокировку, то
 * успешно она установится только для 1го из них, пришедшего "истинно раньше".
 *
 * Показан к применению там, где нельзя дать выполнить какое-то действия 2 раза одновременно.
 *
 * Прим. этим коренным образом класс отличается от Base_Semaphore, кот-й ставит неатомарный семафор используя MEMCACHE GET для проверки.
 *
 * @author Разумовский Дмитрий (darazum)
 * @date 14.11.2001
 * @source http://code.google.com/p/memcached/wiki/FAQ#Emulating_locking_with_the_add_command
 *
 * Using:
 *
  class My_Super_Controller extends Base_Controller_App
  {
      function mySuperAction()
      {
          if(!$this->USER) { return false; }

          $lock = new Base_Lock(__METHOD__ . ':' . $this->USER->getId()); // созали объект для блокировки этого экшена для данного юзера
          if (!$lock->lock(5)) { // пытаемся установить блокировку на 5 секунд
               // если не удалось установить блокировку - значит она уже установлена
               echo 'action_locked'; return;
          }

          // что-то делаем
          // ...

          $lock->unLock(); // снимаем блокировку
      }
  }
 *
 */

class Base_Lock
{
    /**
     * @var string
     * Memcache-ключ в котором устанавливается lock
     */
    private $mKey;

    /**
     * Статический конструктор лока
     * 
     * Статическая функция для того чтобы вызывать лок в одну строчку и не заводить лишних переменных
     * если лок и анлок нужно делать в разных методах
     * 
     * @param string $mKey часть Memcache-ключа в котором устанавливается lock
     * 
     * @return Base_Lock 
     */
    static function getCopy($mKey){
        return new Base_Lock($mKey);
    }
    
    /**
     * Проверяет есть ли лок и устанавливает если нет. 
     * 
     * @param string $mKey часть Memcache-ключа в котором устанавливается lock
     * @param int $seconds на сколько секунд установить. 0 будет означать блокировку навечно (точнее пока не потрется ключик) http://www.php.net/manual/en/memcached.expiration.php
     * 
     * @return bool false если уже установлен и true если не был установлен и установился
     */
    static function isAvailableAndSet($mKey,$seconds = 3){
        return Base_Lock::getCopy($mKey)->lock($seconds);
    }
    
    /**
     * Снять lock статически 
     * 
     * @return bool|void
     */
    static function staticUnlock($mKey){
        return Base_Lock::getCopy($mKey)->unLock();
    }
    
    /**
     * @param  $mKey часть Memcache-ключа в котором устанавливается lock
     * Конструктор объекта
     */
    function __construct($mKey)
    {
        $mKey = Utf::strlen($mKey) > 64 ? crc32($mKey) : $mKey;
        $this->mKey = 'baseLock.' . $mKey . ':';
    }

    /**
     * @param int $seconds на сколько секунд установить. 0 будет означать блокировку навечно (точнее пока не потрется ключик) http://www.php.net/manual/en/memcached.expiration.php
     * @return bool false если уже установлен и true если не был установлен и установился
     *
     * Установка lock-а, она же проверка установлен ли lock.
     */
    function lock($seconds = 3)
    {
        return Base_Service_Memcache::add($this->mKey, 1, $seconds);
    }

    /**
     * @return bool|void
     * Снять lock
     */
    function unLock()
    {
        return Base_Service_Memcache::delete($this->mKey);
    }
}