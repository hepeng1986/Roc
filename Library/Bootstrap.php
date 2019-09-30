<?php
/**
 * @Author: hepeng
 * @Date: 2019/09/25
 */
date_default_timezone_set('Asia/Shanghai');
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
define('LOG_LEVEL',     2);
define('LOG_SYS',     1);
define('LOG_ERROR',     2);
define('LOG_DEBUG',     4);

// 自动加载
require LIB_PATH.'Loader.php';
// 创建实例
Roc_Application::run();