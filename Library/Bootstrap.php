<?php
/**
 * @Author: hepeng
 * @Date: 2019/09/25
 */
date_default_timezone_set('Asia/Shanghai');
define('MICROTIME',microtime(true));
//定义路径
define('DS', DIRECTORY_SEPARATOR);
//定义路径常量
defined('ROOT_PATH') OR define('ROOT_PATH', dirname(__DIR__).DS);
define('APP_PATH', ROOT_PATH.'App'.DS);
define('LIB_PATH', ROOT_PATH.'Library'.DS);
define('ROC_PATH', LIB_PATH.'Roc'.DS);
define('VERDOR_PATH', ROOT_PATH.'vendor'.DS);
define('CONF_PATH', ROOT_PATH.'Conf'.DS);
define('OTHER_PATH', ROOT_PATH.'Other'.DS);
//默认日志级别
define('LEVEL_LOG',     1);
define('ALL_LOG',     9);//写所有日志，如果配置ALL还高，则不写日志
define('ERROR_LOG',     3);//只写错误日志
define('DEBUG_LOG',     1);
//错误码
define('ERR_USER',     9000);//用户操作错误
define('ERR_SERVER',     9800);//服务器错误
define('ERR_EXCEPTION',     9900);//异常
define('ERR_UNKNOW',     9999);//未知错误
// 自动加载
require LIB_PATH.'Loader.php';
//全局变量或函数
require LIB_PATH.'Global.php';
// 创建实例
Roc_Application::run();