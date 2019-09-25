<?php
if ($argc < 2) {
    echo "命令格式错误，请按下面格式运行：\n";
    echo "{$_SERVER['_']} {$argv[0]} url [env]\n";
    exit();
}

date_default_timezone_set('Asia/Shanghai');
$aUrl = parse_url($argv[1]);
$sHost = $aUrl['host'];
$sRouteUri = isset($aUrl['path']) ? $aUrl['path'] : '/';
$sRouteUri .= isset($aUrl['query']) ? '?' . $aUrl['query'] : '';
$sIndexPath = __DIR__;
define('ENV_CMD_HOST', $sHost);
define('ENV_CMD_MAIN', realpath(__FILE__));

$aHost = explode('.', $sHost);
define('ENV_CHANNEL', array_shift($aHost));
define('HTTP_PROTOCOL', isset($_SERVER['HTTPS']) ? 'https://' : 'http://');
$sProjectPath = $sIndexPath.'/..';
if (count($aHost) == 4) {
    define('ENV_CLOUDNAME', $aHost[0]);
    define('ENV_SCENE', $aHost[1]);
    define('EVN_PROJECT', ENV_CHANNEL);
    define("ROOT_PATH", $sProjectPath.'/application' );
    define("APP_PATH", $sProjectPath. '/application/app');
    define("APP_EXTEND", $sProjectPath . '/application/extend');
    define("LIB_PATH", $sProjectPath. '/application/library');
    define("CONF_PATH", $sProjectPath . '/application/conf');
} else {
    define('ENV_CLOUDNAME', '');

    define('ENV_SCENE', isset($argv[2]) && $argv[2] == 'beta' ? $argv[2] : 'ga');

    define('VERSION', '');

    define("ROOT_PATH", $sProjectPath);
    define("APP_PATH",  $sProjectPath. '/application/app');
    define("APP_EXTEND", $sProjectPath . '/application/extend');
    define("LIB_PATH",  $sProjectPath . '/application/library');
    define("CONF_PATH", $sProjectPath . '/application/conf/');

}

define('ENV_DOMAIN', join('.', $aHost));
define('STATIC_VERSION', '');
try {
    require_once LIB_PATH . '/loader.php';
    if (isset(Yaf_G::$YAF_CONTROLLER_DIRECTORY_NAME)){
        Yaf_G::$YAF_CONTROLLER_DIRECTORY_NAME = 'Controller';
    }
    $app = new Yaf_Application();
    $app->bootstrap()->run();
} catch (Exception $e) {
    echo $e->getMessage();
}
