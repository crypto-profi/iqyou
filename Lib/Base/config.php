<?php
if (file_exists('Lib/Base/config.override.php')) {
    include('Base/config.override.php');
}

@define('PRODUCTION', true);

@define('TIME', time());
@define('TIME_TODAY', strtotime('today'));

@define('PROJECT_DIR', dirname(__FILE__) . '/..');
@define('PROJECT_ENCODING', 'UTF8');