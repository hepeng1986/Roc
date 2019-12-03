<?php

/**
 * Class Roc_Log
 * Date: 15-1-5
 * Time: 下午4:
 */
class Roc_Log
{
    //级别字符串
    private static $aLevelNames = [DEBUG_LOG => "DEBUG", ERROR_LOG => "ERROR", ALL_LOG => "INFO"];

    /**
     * 获取级别
     */
    public static function getLevel()
    {
        $iLevel = Roc_G::getConf("loglevel", "Config");
        if (empty($iLevel) || !is_numeric($iLevel)) {
            $iLevel = LEVEL_LOG;
        }
        return $iLevel;
    }

    //写日志
    public static function debug($mMsg)
    {
        self::log($mMsg,DEBUG_LOG);
    }
    public static function error($mMsg)
    {
        self::log($mMsg,ERROR_LOG);
    }
    public static function all($mMsg)
    {
        self::log($mMsg,ALL_LOG);
    }

    public static function log($mMsg,$iLevel = 9,$sDir = "")
    {
        //得到系统配置的日志级别
        $iConfLevel = self::getLevel();
        if($iConfLevel > $iLevel){
            return;
        }
        if(empty($sDir)){
            $sDir = OTHER_PATH."Logs".DS.date("Ym").DS;
        }
        if(file_exists($sDir) && is_dir($sDir)){
            $sLogFile = $sDir .date("Ymd")."log";
        }elseif(is_file($sDir)){
            $sLogFile = $sDir;
        }else{
            if(false === mkdir($sDir,0755,true)){
                Roc_G::throwException(__CLASS__ . ": can not mkdir '{$sDir}'");
            }
            $sLogFile = $sDir .date("Ymd")."log";
        }
        $sLogLevelName = self::$aLevelNames[$iLevel];
        $sContent = date('Y-m-d H:i:s') . " [{$sLogLevelName}] " . self::convertToStr($mMsg) . PHP_EOL;
        //写日志
        @file_put_contents($sLogFile, $sContent, FILE_APPEND);
    }

    private static function convertToStr($data)
    {
        if (is_null($data) || is_bool($data) || is_array($data)) {
            return var_export($data, true);
        }
        return $data;
    }
}