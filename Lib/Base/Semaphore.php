<?php
/**
 * @deprecated см. Base_Lock
 */
class Base_Semaphore
{
    private $name;
    private $mkey;

    /**
     * @deprecated см. Base_Lock
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->mkey = 'semaphore:'.$this->name;
    }

    /**
     * @deprecated см. Base_Lock
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Acquire semaphore
     *
     * @deprecated см. Base_Lock
     *
     * @param string $name
     * @param int $timelimit
     * @return boolean
     */
    public function acquire($timelimit = 3600)
    {
        if (!$this->hasPermit()) {
            return false;
        }
        Base_Service_Memcache::set($this->mkey, '1', $timelimit);
        return true;
    }

    /**
     * Release semaphore
     *
     * @deprecated см. Base_Lock
     *
     * @return boolean
     */
    public function release()
    {
        Base_Service_Memcache::delete($this->mkey);
        return true;
    }

    /**
     * @return bool
     * @deprecated см. Base_Lock
     */
    public function hasPermit()
    {
        $value = Base_Service_Memcache::get($this->mkey);
        if ($value === false) {
            return true;
        }
        return false;
    }
}