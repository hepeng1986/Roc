<?php
define('OSS_ACCESS_ID', 'fgajpwUlk5EtX5WY');
define('OSS_ACCESS_KEY', 'SS1InKsh7FrVYf3ouUkDdTBcIaBHMb');

// 青岛节点外网地址： oss-cn-qingdao.aliyuncs.com
// 青岛节点内网地址： oss-cn-qingdao-internal.aliyuncs.com
// 杭州节点外网地址： oss-cn-hangzhou.aliyuncs.com
// 杭州节点内网地址:  oss-cn-hangzhou-internal.aliyuncs.com
// 原地址oss.aliyuncs.com 默认指向杭州节点外网地址。
// 原内网地址oss-internal.aliyuncs.com 默认指向杭州节点内网地址
define('OSS_ENDPOINT', 'oss-cn-hangzhou-internal.aliyuncs.com');
define('OSS_TEST_BUCKET', 'test');

//是否记录日志
define('ALI_LOG', FALSE);

//自定义日志路径，如果没有设置，则使用系统默认路径，在./logs/
//define('ALI_LOG_PATH','');

//是否显示LOG输出
define('ALI_DISPLAY_LOG', FALSE);

//语言版本设置
define('ALI_LANG', 'zh');
