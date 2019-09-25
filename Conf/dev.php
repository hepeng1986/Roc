<?php
//通用数据库
$config['database']['db_decision_global']['master'] = array(
    'dsn' => 'mysql:host=10.122.149.201;dbname=db_decision_global',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

// 楼盘同步原始数据
$config['database']['dms_loupan_sync']['master'] = array(
    'dsn' => 'mysql:host=10.122.149.201;dbname=loupan_sync_db',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//山东版数据库
$config['database']['dms_project_sd']['master'] = array(
    'dsn' => 'mysql:host=10.122.149.201;dbname=dms_project_sd',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//福建版本数据库
$config['database']['dms_project_fj']['master'] = array(
    'dsn' => 'mysql:host=10.122.149.201;dbname=dms_project_fj',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//河南版本数据库
$config['database']['dms_project_hn']['master'] = array(
    'dsn' => 'mysql:host=10.122.149.201;dbname=dms_project_hn',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//四川版本数据库
$config['database']['dms_project_sc']['master'] = array(
    'dsn' => 'mysql:host=10.122.149.201;dbname=dms_project_sc',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//南京版本数据库
$config['database']['dms_project_nj']['master'] = array(
    'dsn' => 'mysql:host=10.122.149.201;dbname=dms_project_nj',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);
//苏州版本数据库
$config['database']['dms_project_sz']['master'] = array(
    'dsn' => 'mysql:host=10.122.149.201;dbname=dms_project_sz',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);
//万科定制版本数据库
$config['database']['dms_project_wkdz']['master'] = array(
    'dsn' => 'mysql:host=10.122.149.201;dbname=dms_project_wkdz',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);
//Redis配制
$config['redis']['bll'] = array(
    'host' => '127.0.0.1',
    'port' => 6379,
    'db' => 9
);

$config['cache']['bll'] = array(
    array(
        'host' => '127.0.0.1',
        'port' => 11211
    )
);
////土地详情标书文件下载地址
//$config['InviteBookDomain'] = 'http://app.cric.com/';

$config['sPythonBin'] = 'python';
$config['CCP'] = array(
    'host' => 'app.cloopen.com',
    'port' => '8883',
    'version' => '2013-12-26',
    'sid' => 'aaf98f89544cd9d901545ba6854f11a9',
    'token' => '54b41a9cebce4abab3cc15c42cf4f7c0',
    'appid' => '8aaf070868983fcb0168efcd15ac0a84'
);

$config['mongodb']['dms_frontend'] = array(
    'dsn' => 'mongodb://172.28.21.242:27018',
    'dbname' => 'dms_frontend',
    'options'=>[],
    'query_safety' => null
);