<?php

/**
 * Class Roc_Log
 * Date: 15-1-5
 * Time: 下午4:
 */
class Roc_Log
{
    const DEBUG = 1;

    const WARN = 2;

    const ERROR = 3;

    const SYS = 4;

    /**
     * 配置信息
     *
     * @var array
     */
    private static $_aConfig;

    /**
     * 日志级别
     */
    private static $_iLevel = 0;

    /**
     * 是否初始化
     *
     * @var boolean
     */
    private static $_bInit = false;

    /**
     * 构造函数
     */
    private static function init ()
    {
        self::$_bInit = true;
        self::$_aConfig = Roc_G::getConf(null, 'logger');

        $sBaseDir = self::$_aConfig['sBaseDir'];
        unset(self::$_aConfig['sBaseDir']);

        foreach (self::$_aConfig as $sKey => $mConfig) {
            $sDir = isset(self::$_aConfig[$sKey]['sDir']) ? self::$_aConfig[$sKey]['sDir'] : $sKey;
            self::$_aConfig[$sKey]['sPath'] = $sBaseDir . DIRECTORY_SEPARATOR . $sDir . DIRECTORY_SEPARATOR;
            if (! is_dir(self::$_aConfig[$sKey]['sPath'])) {
                umask(0000);
                if (false === mkdir(self::$_aConfig[$sKey]['sPath'], 0755, true)) {
                    throw new Exception(__CLASS__ . ': can not create path(' . self::$_aConfig[$sKey]['sPath'] . ').');
                    return false;
                }
            }
        }
    }

    public static function getLogLevelByName ($p_sLevelName)
    {
        $p_sLevelName = strtoupper($p_sLevelName);

        $iLevel = isset(self::$aLevelNames[$p_sLevelName]) ? self::$aLevelNames[$p_sLevelName] : - 1;

        return $iLevel;
    }
    /**
     * 设置日志级别
     */
    public static function setLevel($iLevel)
    {
        $iLevel = intval($iLevel);
        self::$_iLevel = $iLevel;
    }
    /**
     * 获取级别
     */
    public static function getLevel()
    {
        if(!empty(self::$_iLevel)){
            return self::$_iLevel;
        }
        //conf
        $iLevel = Roc_G::getConf("loglevel","Config");
        if(empty($iLevel)){
            $iLevel = self::DEBUG;
        }
        return $iLevel;
    }
    //写日志
    public static function debug ($mMsg)
    {
        self::log(self::DEBUG, $mMsg);
    }
    public static function warn ($mMsg)
    {
        self::log(self::WARN, $mMsg);
    }
    public static function error ($mMsg)
    {
        self::log(self::ERROR, $mMsg);
    }
    public static function sys ($mMsg)
    {
        self::log(self::SYS, $mMsg);
    }

    private static function log ($iLevel, $mMsg)
    {
        if (isset(self::$_aConfig[$p_sConf]['iLevel']) && $p_iLevel < self::$_aConfig[$p_sConf]['iLevel']) {
            return;
        }

        if (! self::$_bInit) {
            self::init();
        }

        $sLogLevelName = array_keys(self::$aLevelNames)[$p_iLevel];
        $sLogFile = self::$_aConfig[$p_sConf]['sPath'] . ($p_sType ? $p_sType . '-' : '') . $sLogLevelName . '-' . date(self::$_aConfig[$p_sConf]['sSplit']) . '.log';
        $sContent = date('Y-m-d H:i:s') . ' ' . self::convertToStr($mMsg) . PHP_EOL;
        //写日志
        file_put_contents($sLogFile, $sContent, FILE_APPEND);
    }

    protected static function convertToStr ($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string) $data;
        }

        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return @json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return str_replace('\\/', '/', @json_encode($data));
    }
}