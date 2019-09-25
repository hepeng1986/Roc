<?php
//通用数据库
$config['database']['db_decision_global']['master'] = array(
    'dsn' => 'mysql:host=m3306.db.fjdp.eju.local:3306;dbname=db_decision_global',
    'user' => 'dms_user',
    'pass' => '$amvrr1qC@IHhw@M',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

// 楼盘同步原始数据
$config['database']['dms_loupan_sync']['master'] = array(
    'dsn' => 'mysql:host=10.0.16.21;dbname=loupan_sync_db',
    'user' => 'sync_user',
    'pass' => 'cbNVAHvu7cQ1z6jX',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//山东版数据库
$config['database']['dms_project_sd']['master'] = array(
    'dsn' => 'mysql:host=m3306.db.fjdp.eju.local:3306;dbname=dms_project_sd',
    'user' => 'dms_user',
    'pass' => '$amvrr1qC@IHhw@M',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//福建版本数据库
$config['database']['dms_project_fj']['master'] = array(
    'dsn' => 'mysql:host=m3306.db.fjdp.eju.local:3306;dbname=dms_project_fj',
    'user' => 'dms_user',
    'pass' => '$amvrr1qC@IHhw@M',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//河南版数据库
$config['database']['dms_project_hn']['master'] = array(
    'dsn' => 'mysql:host=m3306.db.fjdp.eju.local:3306;dbname=dms_project_hn',
    'user' => 'dms_user',
    'pass' => '$amvrr1qC@IHhw@M',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//四川版数据库
$config['database']['dms_project_sc']['master'] = array(
    'dsn' => 'mysql:host=m3306.db.fjdp.eju.local:3306;dbname=dms_project_sc',
    'user' => 'dms_user',
    'pass' => '$amvrr1qC@IHhw@M',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

//南京版本数据库
$config['database']['dms_project_nj']['master'] = array(
    'dsn' => 'mysql:host=m3306.db.fjdp.eju.local:3306;dbname=dms_project_nj',
    'user' => 'dms_user',
    'pass' => '$amvrr1qC@IHhw@M',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);
//苏州版本数据库
$config['database']['dms_project_sz']['master'] = array(
    'dsn' => 'mysql:host=m3306.db.fjdp.eju.local:3306;dbname=dms_project_sz',
    'user' => 'dms_user',
    'pass' => '$amvrr1qC@IHhw@M',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);
//万科定制版本数据库
$config['database']['dms_project_wkdz']['master'] = array(
    'dsn' => 'mysql:host=m3306.db.fjdp.eju.local:3306;dbname=dms_project_wkdz',
    'user' => 'dms_user',
    'pass' => '$amvrr1qC@IHhw@M',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);
$config['database']['default']['master'] = array(
    'dsn' => 'mysql:host=;dbname=',
    'user' => '',
    'pass' => '',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

$config['es']['bll'] = array(
    [
        'host' => 'es.fjdp.eju.local',
        'port' => '9200',
        'scheme' => 'http'
    ]
);

//Redis配制
$config['redis']['bll'] = array(
    'host' => 'master.rds.fjdp.eju.local',
    'port' =>  6380,
    'db' => 9,
    'pass' => 'RAITCDV$wXib3*Hn',
);

$config['cache']['bll'] = array(
    array(
        'host' => '127.0.0.1',
        'port' => 11211
    )
);

$config['CCP'] = array(
    'host' => 'app.cloopen.com',
    'port' => '8883',
    'version' => '2013-12-26',
    'sid' => 'aaf98f89544cd9d901545ba6854f11a9',
    'token' => '54b41a9cebce4abab3cc15c42cf4f7c0',
    'appid' => '8aaf070868983fcb0168efcd15ac0a84'
);

$config['mongodb']['dms_frontend'] = array(
    'dsn' => 'mongodb://appraisal_user:DYzVY8jCtL4!aYOT@mongo1.fjdp.eju.local:27017,mongo2.fjdp.eju.local:27017,mongo3.fjdp.eju.local:27017/',
    'dbname'=> 'dms_frontend',
    'options' => ['replicaSet' => 'fjdp'],
);

$config['sPythonBin'] = 'python';
