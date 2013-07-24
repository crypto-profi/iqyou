<?php
chdir('../');
set_time_limit(5);

$GLOBALS['time_start'] = microtime(true);

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . 'Lib' . PATH_SEPARATOR  . 'App'. PATH_SEPARATOR . '../');

require_once 'Base/Autoloader.php';
$autoloader = Base_Autoloader::getInstance();

require('Base/config.php');

$application = new Base_Application();
$application->run();

$pageTime = microtime(true) - $GLOBALS['time_start'];
if (!PRODUCTION) {
	echo $pageTime;
}