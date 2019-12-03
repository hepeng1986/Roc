<?php

/**
 * Class Roc_Log
 * Date: 15-1-5
 * Time: 下午4:
 */
class Roc_Log
{
    const DEBUG = 1;
    const ERROR = 3;//只写错误日志
    const ALL = 9;//写所有日志，如果配置ALL还高，则不写日志

    //日志目录
    private static $_sDir = "";
    //级别字符串
    private static $aLevelNames = [1 => "DEBUG", 3 => "ERROR", 9 => "INFO"];

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
        $iLevel = Roc_G::getConf("loglevel", "Config");
        if (empty($iLevel) || !is_numeric($iLevel)) {
            $iLevel = self::DEBUG;
        }
        return $iLevel;
    }

    //写日志
    public static function debug($mMsg)
    {
        self::log(self::DEBUG, $mMsg);
    }
    public static function error($mMsg)
    {
        self::log(self::ERROR, $mMsg);
    }
    public static function all($mMsg)
    {
        self::log(self::ALL, $mMsg);
    }

    private static function log($iLevel, $mMsg)
    {
        if (isset(self::$_aConfig[$p_sConf]['iLevel']) && $p_iLevel < self::$_aConfig[$p_sConf]['iLevel']) {
            return;
        }

        if (!self::$_bInit) {
            self::init();
        }

        $sLogLevelName = array_keys(self::$aLevelNames)[$p_iLevel];
        $sLogFile = self::$_aConfig[$p_sConf]['sPath'] . ($p_sType ? $p_sType . '-' : '') . $sLogLevelName . '-' . date(self::$_aConfig[$p_sConf]['sSplit']) . '.log';
        $sContent = date('Y-m-d H:i:s') . ' ' . self::convertToStr($mMsg) . PHP_EOL;
        //写日志
        file_put_contents($sLogFile, $sContent, FILE_APPEND);
    }

    protected static function convertToStr($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string)$data;
        }

        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return @json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return str_replace('\\/', '/', @json_encode($data));
    }
}