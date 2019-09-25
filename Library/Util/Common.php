<?php

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;

class Util_Common
{

    protected static $_aInstance;

    public static function getSolr($sType = 'bll', $aParam = array(), $limit = 10)
    {
        if (!isset(self::$_aInstance['solr'][$sType])) {
            $sHost = self::getConf($sType, 'solr');
            if (empty($sHost)) {
                throw new Exception('Solr配置【' . $sType . '】未找到!');
            }
            self::$_aInstance['solr'][$sType] = new Util_Solr($sHost, $aParam, $limit);
        }

        return self::$_aInstance['solr'][$sType];
    }

    public static function getRedis($sType = 'bll')
    {
        if (!isset(self::$_aInstance['redis'][$sType])) {
            $aConf = self::getConf($sType, 'redis');
            if (empty($aConf)) {
                throw new Exception('Redis配置【' . $sType . '】未找到!');
            }
            if (!isset($aConf['pass'])) {
                $aConf['pass'] = '';
            }

            self::$_aInstance['redis'][$sType] = new Util_Redis($aConf['host'], $aConf['port'], $aConf['db'], $aConf['pass']);
        }

        return self::$_aInstance['redis'][$sType];
    }

    public static function getCache($sType = 'bll')
    {
        if (!isset(self::$_aInstance['cache'][$sType])) {
            $aConf = self::getConf($sType, 'cache');
            if (empty($aConf)) {
                throw new Exception('Cache配置【' . $sType . '】未找到!');
            }
            self::$_aInstance['cache'][$sType] = new Util_Memcache($aConf);
        }

        return self::$_aInstance['cache'][$sType];
    }

    public static function getOrm($sDbName, $sTblName, $bWhereCache)
    {
        if (!isset(self::$_aInstance['orm'][$sDbName][$sTblName])) {
            self::$_aInstance['orm'][$sDbName][$sTblName] = new Db_Orm($sDbName, $sTblName);
            self::$_aInstance['orm'][$sDbName][$sTblName]->setWhereCache($bWhereCache);
        }

        return self::$_aInstance['orm'][$sDbName][$sTblName];
    }

    public static function getMongoDb($sType = 'bll')
    {
        if (!isset(self::$_aInstance['mongodb'][$sType])) {
            $aConf = self::getConf($sType, 'mongodb');

            if (empty($aConf)) {
                throw new Exception('MongoDb配置【' . $sType . '】未找到!');
            }
            self::$_aInstance['mongodb'][$sType] = new Db_Mongo($aConf['server'], $aConf['option']);
        }

        return self::$_aInstance['mongodb'][$sType];
    }

    public static function getMongo($sMongoDbName)
    {
        return Db_MongoDb::getInstance($sMongoDbName);
    }

    public static function getEsClient($sType = 'bll')
    {
        if (!isset(self::$_aInstance['es'][$sType])) {
            $aConf = self::getConf($sType, 'es');

            if (empty($aConf)) {
                throw new Exception('ES配置【' . $sType . '】未找到!');
            }
            self::$_aInstance['es'][$sType] = ClientBuilder::create()->setHosts($aConf)->build();
        }

        return self::$_aInstance['es'][$sType];
    }

    public static function getMsSQLDB($sDbName, $sType = 'master')
    {
        if (!isset(self::$_aInstance['mssqldb'][$sDbName])) {
            $aConf = self::getConf($sDbName, 'mssql');
            if (empty($aConf)) {
                throw new Exception('MSSQL DB配置【' . $sDbName . '】未找到!');
            }
            self::$_aInstance['mssqldb'][$sDbName] = new Db_MsSQL($aConf);
        }
        return self::$_aInstance['mssqldb'][$sDbName];
    }

    public static function getMySQLDB($sDbName = 'default', $sType = 'master')
    {
        if (is_array($sDbName)) {
            $aConn = $sDbName;
            if (Yaf_G::getEnv() == 'ga') {
                $aConn['host'] = str_replace([
                    '139.196.122.245',
                    '106.15.207.75'
                ], [
                    '192.168.1.165',
                    '192.168.1.185'
                ], $aConn['host']);
            }
            $aConf = [
                'sDsn' => "mysql:host={$aConn['host']};port={$aConn['port']};dbname={$aConn['db']}",
                'sUser' => $aConn['user'],
                'sPass' => $aConn['pass']
            ];
            $sDbName = md5(serialize($aConf));
            Bi_Model_Datasource::setMysqlConf($sDbName, $aConf);
        }

        Db_MySQL::setDebug(Util_Common::getDebug());
        if (!isset(self::$_aInstance['mysql'][$sDbName])) {
            self::$_aInstance['mysql'][$sDbName] = new Db_MySQL($sDbName);
        }
        return self::$_aInstance['mysql'][$sDbName];
    }

    public static function getPdoDb($sDbName = 'default', $sType = 'master')
    {
        if (!isset(self::$_aInstance['pdo'][$sDbName])) {
            $aConf = self::getConf($sDbName, 'database');
            if (empty($aConf)) {
                throw new Exception('DB配置【' . $sDbName . '】未找到!');
            }
            $aConf = isset($aConf[$sType]) ? $aConf[$sType] : $aConf['master'];
            if (isset($aConf['option'])) {
                $aOption = $aConf['option'];
            } else {
                $aOption = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    PDO::ATTR_STRINGIFY_FETCHES => false
                );
            }
            self::$_aInstance['pdo'][$sDbName] = new PDO($aConf['dsn'], $aConf['user'], $aConf['pass'], $aOption);
            if (isset($aConf['init'])) {
                foreach ($aConf['init'] as $sql) {
                    self::$_aInstance['pdo'][$sDbName]->exec($sql);
                }
            }
        }

        return self::$_aInstance['pdo'][$sDbName];
    }

    public static function getDebug()
    {
        if (self::isDebug()) {
            if (!isset(self::$_aInstance['debug'])) {
                self::$_aInstance['debug'] = new Util_Debug();
            }
            return self::$_aInstance['debug'];
        }
        return null;
    }

    //add by zfx don`t need isDebug
    public static function addDebug($type, $msg)
    {
        if (!isset(self::$_aInstance['debug'])) {
            self::$_aInstance['debug'] = new Util_Debug();
        }
        $_oDebug = self::$_aInstance['debug'];
        $_oDebug->add($type, $msg);
    }

    public static function getDebugData()
    {
        if (self::isDebug()) {
            $aDebug[] = '共次总耗时：' . Yaf_G::getRunTime() . '毫秒';
            if (isset(self::$_aInstance['mysql'])) {
                $aDebug[] = Db_MySQL::getDebugStat();
            }
            if (isset(self::$_aInstance['orm'])) {
                $aDebug[] = Db_Orm::getDebugStat();
            }
            if (isset(self::$_aInstance['cache'])) {
                $aDebug[] = Util_Memcache::getDebugStat();
            }
            if (isset(self::$_aInstance['redis'])) {
                $aDebug[] = Util_Redis::getDebugStat();
            }

            $sDebug = join(' ', $aDebug);
            $aDebug = self::getDebug()->getAll();
            array_unshift($aDebug, $sDebug);
            return $aDebug;
        }
        return null;
    }

    public static function isDebug()
    {
        return Yaf_G::isDebug();
    }

    public static function getConf($sKey, $sType = null, $sFile = null)
    {
        return Yaf_G::getConf($sKey, $sType, $sFile);
    }

    public static function getUrl($sAction = null, $aParam = null, $bFullPath = false, $sDomain = null, $sPostfix = null)
    {
        return Yaf_G::getUrl($sAction, $aParam, $bFullPath, $sDomain, $sPostfix);
    }

    public static function getSolrInstance($sType = 'bll')
    {
        if (!isset(self::$_aInstance['solr'])) {
            $aConf = self::getConf($sType);
            if (empty($aConf)) {
                throw new Exception('solr配置【' . $sType . '】未找到!');
            }
            self::$_aInstance['solr'][$sType] = new Solarium\Client($aConf);
        }

        return self::$_aInstance['solr'][$sType];
    }

    public static function getYouzanClient()
    {
        if (!isset(self::$_aInstance['youzan'])) {
            $aConf = self::getConf('youzan');
            if (empty($aConf)) {
                throw new Exception('有赞配置未找到!');
            }
            require_once LIB_PATH . '/Youzan/KdtApiClient.php';
            self::$_aInstance['youzan'] = new KdtApiClient($aConf['appID'], $aConf['appKey']);
        }

        return self::$_aInstance['youzan'];
    }
    /*
     * 得到模块的配置文件
     * @param $sKey  配置文件的key
     * @param $sFile 配置文件
     * @param $sDir 模块下的目录 默认为Conf
     * @param $sModule 模块名 默认为本模块,也支持传模块名
     */
    public static function getModuleConf($sKey, $sFile, $sDir = "Conf",$sModule = null){
        return Yaf_G::getModuleConf($sKey, $sFile, $sDir,$sModule);
    }
}