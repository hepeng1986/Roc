<?php

/**
 * Class Roc_Logger
 * User: felixtang
 * Date: 15-1-5
 * Time: 下午4:
 *
 * 日志配置文件格式
 * $config['logger']['sBaseDir'] = '/data/log/php'; // 日志目录
 * // 默认日志
 * $config['logger']['common'] = array(
 * 'sSplit' => 'Ymd',   // day, week, month, persistent
 * 'sDir' => 'common',     // 目录为可以选的，不配置时，默认为key值 /data/log/php/sDir or /data/log/php/sDir/
 * );
 *
 */
class Roc_Logger
{

    /**
     * 所有都打印
     */
    const ALL = 0;

    /**
     * Detailed debug information
     */
    const DEBUG = 1;

    /**
     * Interesting events
     */
    const INFO = 2;

    /**
     * Uncommon event
     */

    /**
     * Exceptional occurrences that are not errors
     */
    const WARN = 3;

    /**
     * Runtime errors
     */
    const ERROR = 4;

    /**
     * fatal conditions
     */
    const FATAL = 5;

    /**
     * Action must be taken immediately
     */
    const OFF = 6;

    /**
     * Urgent alert
     */
    static $aLevelNames = array(
        'ALL' => 0,
        'DEBUG' => 1,
        'INFO' => 2,
        'WARN' => 3,
        'ERROR' => 4,
        'FATAL' => 5,
        'OFF' => 6
    );

    /**
     * 系统日志实例
     *
     * @var object
     */
    private static $_oInstance = null;

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

    public static function debug ($p_sMsg, $p_sType = '', $p_sConf = 'common')
    {
        self::log(self::DEBUG, $p_sMsg, $p_sType, $p_sConf);
    }

    public static function info ($p_sMsg, $p_sType = '', $p_sConf = 'common')
    {
        self::log(self::INFO, $p_sMsg, $p_sType, $p_sConf);
    }

    public static function warn ($p_sMsg, $p_sType = '', $p_sConf = 'common')
    {
        self::log(self::WARN, $p_sMsg, $p_sType, $p_sConf);
    }

    public static function error ($p_sMsg, $p_sType = '', $p_sConf = 'common')
    {
        self::log(self::ERROR, $p_sMsg, $p_sType, $p_sConf);
    }

    public static function fatal ($p_mMsg, $p_sType = '', $p_sConf = 'common')
    {
        self::log(self::FATAL, $p_mMsg, $p_sType, $p_sConf);
    }

    private static function log ($p_iLevel, $p_mMsg, $p_sType, $p_sConf = 'common')
    {
        if (isset(self::$_aConfig[$p_sConf]['iLevel']) && $p_iLevel < self::$_aConfig[$p_sConf]['iLevel']) {
            return;
        }

        if (! self::$_bInit) {
            self::init();
        }

        $sLogLevelName = array_keys(self::$aLevelNames)[$p_iLevel];
        $sLogFile = self::$_aConfig[$p_sConf]['sPath'] . ($p_sType ? $p_sType . '-' : '') . $sLogLevelName . '-' . date(self::$_aConfig[$p_sConf]['sSplit']) . '.log';
        $sContent = date('Y-m-d H:i:s') . ' ' . self::convertToStr($p_mMsg) . PHP_EOL;

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