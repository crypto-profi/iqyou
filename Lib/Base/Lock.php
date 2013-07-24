<?php
/**
 * ����� ��������� ��������� ������� (����������) ��� ������ ������� MEMCACHE ADD.
 *
 * ��������� - ������ ������� ���������� ��� 2� ������������ ���������. ����� ������, ����
 * 2 ������� � ��� ������ "��������� ������������", � ��� �������� ��������� ����������, ��
 * ������� ��� ����������� ������ ��� 1�� �� ���, ���������� "������� ������".
 *
 * ������� � ���������� ���, ��� ������ ���� ��������� �����-�� �������� 2 ���� ������������.
 *
 * ����. ���� �������� ������� ����� ���������� �� Base_Semaphore, ���-� ������ ����������� ������� ��������� MEMCACHE GET ��� ��������.
 *
 * @author ����������� ������� (darazum)
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

          $lock = new Base_Lock(__METHOD__ . ':' . $this->USER->getId()); // ������ ������ ��� ���������� ����� ������ ��� ������� �����
          if (!$lock->lock(5)) { // �������� ���������� ���������� �� 5 ������
               // ���� �� ������� ���������� ���������� - ������ ��� ��� �����������
               echo 'action_locked'; return;
          }

          // ���-�� ������
          // ...

          $lock->unLock(); // ������� ����������
      }
  }
 *
 */

class Base_Lock
{
    /**
     * @var string
     * Memcache-���� � ������� ��������������� lock
     */
    private $mKey;

    /**
     * ����������� ����������� ����
     * 
     * ����������� ������� ��� ���� ����� �������� ��� � ���� ������� � �� �������� ������ ����������
     * ���� ��� � ����� ����� ������ � ������ �������
     * 
     * @param string $mKey ����� Memcache-����� � ������� ��������������� lock
     * 
     * @return Base_Lock 
     */
    static function getCopy($mKey){
        return new Base_Lock($mKey);
    }
    
    /**
     * ��������� ���� �� ��� � ������������� ���� ���. 
     * 
     * @param string $mKey ����� Memcache-����� � ������� ��������������� lock
     * @param int $seconds �� ������� ������ ����������. 0 ����� �������� ���������� ������� (������ ���� �� �������� ������) http://www.php.net/manual/en/memcached.expiration.php
     * 
     * @return bool false ���� ��� ���������� � true ���� �� ��� ���������� � �����������
     */
    static function isAvailableAndSet($mKey,$seconds = 3){
        return Base_Lock::getCopy($mKey)->lock($seconds);
    }
    
    /**
     * ����� lock ���������� 
     * 
     * @return bool|void
     */
    static function staticUnlock($mKey){
        return Base_Lock::getCopy($mKey)->unLock();
    }
    
    /**
     * @param  $mKey ����� Memcache-����� � ������� ��������������� lock
     * ����������� �������
     */
    function __construct($mKey)
    {
        $mKey = Utf::strlen($mKey) > 64 ? crc32($mKey) : $mKey;
        $this->mKey = 'baseLock.' . $mKey . ':';
    }

    /**
     * @param int $seconds �� ������� ������ ����������. 0 ����� �������� ���������� ������� (������ ���� �� �������� ������) http://www.php.net/manual/en/memcached.expiration.php
     * @return bool false ���� ��� ���������� � true ���� �� ��� ���������� � �����������
     *
     * ��������� lock-�, ��� �� �������� ���������� �� lock.
     */
    function lock($seconds = 3)
    {
        return Base_Service_Memcache::add($this->mKey, 1, $seconds);
    }

    /**
     * @return bool|void
     * ����� lock
     */
    function unLock()
    {
        return Base_Service_Memcache::delete($this->mKey);
    }
}