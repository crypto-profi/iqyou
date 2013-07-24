<?php

class Base_Service_Upload {

	private static $nginx = array();
	private static $upload = array();
    private static $_uploadedByNginx = array();

    /**
     * @var Base_Service_Upload
     */
    static private $_instance = null;

	static public function init()
	{
        if (!PRODUCTION) {
            // Do not try to understand it
            self::$_instance = null;
        }
		self::$_instance = new Base_Service_Upload;
		$_FILES = self::$_instance->getFiles();
	}

    /**
	 * Перемещает загруженный файл
	 *
	 * @param Array|string $file
	 * @param String $destination
	 * @return Bool
	 */
	static public function move($file, $destination, $leaveUploadedFile = null)
	{
		$res = false;

        $fielTmpName = (is_array($file)) ? $file["tmp_name"] : $file;

        // $leaveUploadedFile - флаг, который передаётся когда не нужно удалять загруженный файл
        if(($leaveUploadedFile == true) || array_key_exists($fielTmpName, self::$_uploadedByNginx)){
            $res = copy($fielTmpName, $destination);
        }else{
            $res = move_uploaded_file($fielTmpName, $destination);
        }

        return $res;
	}

	public function __construct()
	{
		foreach($_POST as $key => $post) {
			if(Utf::preg_match('/^nginx_upload_([a-zA-Z0-9\-_]+)_([a-zA-Z]+)$/', $key, $pockets)) {
				self::$nginx[$pockets[1]][$pockets[2]] = $post;
			}
		}

		foreach(self::$nginx as $key => $file) {
			self::$upload[$key] = Array(
				"name" => $file["name"],
				"tmp_name" => $file["path"],
				"type" => $file["contentType"],
				"size" => $file["size"],
				"error" => UPLOAD_ERR_OK,
				"nginx_upload" => true,
			);
            self::$_uploadedByNginx[$file["path"]] = true;
		}

		foreach($_FILES as $name => $files) {
            // костыль для кривого массива с фотками фотоконкурса =( Надо разобраться и убрать
            if (!isset($files['error']) && isset($files[0]['error'])) {
                $files = reset($files);
            }

            if (is_array($files['error'])) {
                $c = 0;
                for ($i = 0; $i < count($files['error']); $i++) {
                    if($files['error'][$i] == UPLOAD_ERR_OK) {
                        foreach($files as $key => $value) {
                            self::$upload[$name][$c][$key] = $value[$i];
                        }
                        $c++;
                    }
                }
            } else {
                if($files['error'] == UPLOAD_ERR_OK) {
                    self::$upload[$name] = $files;
                }
            }
		}
	}

	public function __destruct()
	{
		foreach(self::$nginx as $file) {
			unlink($file["path"]);
		}
	}

	/**
     * Получить массив с файлами для загрузки
     * @param string $key Позволяет получить только определенный файл по ключю
     * @return array
     */
    public static function getFiles($key = null)
	{
		if ($key !== null) {
            return (isset(self::$upload[$key])) ? self::$upload[$key] : null;
        }

        return self::$upload;
	}
}