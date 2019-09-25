<?php
require_once (dirname(__FILE__) . '/notification/android/AndroidBroadcast.php');
require_once (dirname(__FILE__) . '/notification/android/AndroidFilecast.php');
require_once (dirname(__FILE__) . '/notification/android/AndroidGroupcast.php');
require_once (dirname(__FILE__) . '/notification/android/AndroidUnicast.php');
require_once (dirname(__FILE__) . '/notification/android/AndroidCustomizedcast.php');
require_once (dirname(__FILE__) . '/notification/ios/IOSBroadcast.php');
require_once (dirname(__FILE__) . '/notification/ios/IOSFilecast.php');
require_once (dirname(__FILE__) . '/notification/ios/IOSGroupcast.php');
require_once (dirname(__FILE__) . '/notification/ios/IOSUnicast.php');
require_once (dirname(__FILE__) . '/notification/ios/IOSCustomizedcast.php');

class Umeng_UPush
{

    protected static $aConf = null;
    protected static $aCommonKey = array(
        'appkey',
        'timestamp',
        'type',
        'device_tokens',
        'alias',
        'alias_type',
        'file_id',
        'filter',
        'production_mode',
        'description',
        'thirdparty_id'
    );
    
    protected static $aAndroidKey = array(
        'display_type',
        'ticker',
        'title',
        'text',
        'icon',
        'largeIcon',
        'img',
        'sound',
        'builder_id',
        'play_vibrate',
        'play_lights',
        'play_sound',
        'after_open',
        'url',
        'activity',
        'custom'
    );
    protected static $aIosKey = array(
        'alert',
        'badge',
        'sound',
        'content-available',
        'category'
    );
    protected static $aPolicy = array(
        'start_time',
        'expire_time',
        'max_send_num',
        'out_biz_no'
    );

    /**
     * 发送广播(给所有人)
     * @param unknown $aParam
     */
    public static function sendAndroidMsg ($aParam)
    {
        try {
            if (isset($aParam['device_tokens'])) {
                $oPush = new AndroidUnicast();
            } elseif (isset($aParam['contents'])) {
                $oPush = new AndroidFilecast();
                $oPush->uploadContents($aParam['contents']);
            } elseif (isset($aParam['filter'])) {
                $oPush = new AndroidGroupcast();
            } elseif (isset($aParam['alias_type'])) {
                $oPush = new AndroidCustomizedcast();
            } else {
                $oPush = new AndroidBroadcast();
            }
            self::buildAndroidParam($oPush, $aParam);
            print("Sending broadcast notification, please wait...\r\n");
            $oPush->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    /**
     * 发送广播(给所有人)
     * @param unknown $aParam
     */
    public static function sendIosMsg ($aParam)
    {
        try {
            if (isset($aParam['device_tokens'])) {
                $oPush = new IOSUnicast();
            } elseif (isset($aParam['contents'])) {
                $oPush = new IOSFilecast();
                $oPush->uploadContents($aParam['contents']);
            } elseif (isset($aParam['filter'])) {
                $oPush = new IOSGroupcast();
            } elseif (isset($aParam['alias_type'])) {
                $oPush = new IOSCustomizedcast();
            } else {
                $oPush = new IOSBroadcast();
            }
            self::buildIosParam($oPush, $aParam);
            print("Sending broadcast notification, please wait...\r\n");
            $oPush->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }
    
    /**
     * 构建参数
     * @param unknown $brocast
     * @param unknown $aParam
     */
    private static function buildAndroidParam($brocast, $aParam)
    {
        self::$aConf = Util_Common::getConf('umeng');
        $brocast->setAppMasterSecret(self::$aConf['msecret_android']);
        $brocast->setPredefinedKeyValue("appkey", self::$aConf['appkey_android']);
        $brocast->setPredefinedKeyValue("timestamp", strval(time()));
        $brocast->setPredefinedKeyValue("after_open", "go_app");
        // Set 'production_mode' to 'false' if it's a test device.
        // For how to register a test device, please see the developer doc.
        $brocast->setPredefinedKeyValue("production_mode", "true");
        
        foreach (self::$aCommonKey as $k) {
            if (isset($aParam[$k])) {
                $brocast->setPredefinedKeyValue($k, $aParam[$k]);
            }
        }
        
        foreach (self::$aAndroidKey as $k) {
            if (isset($aParam[$k])) {
                $brocast->setPredefinedKeyValue($k, $aParam[$k]);
            }
        }
        
        // 设置自定义数据
        if (isset($aParam['aExtra'])) {
            foreach ($aParam['aExtra'] as $k => $v) {
                $brocast->setExtraField($k, $v);
            }
        }
        
        // 设置策略
        if (isset($aParam['aPolicy'])) {
            foreach (self::$aPolicy as $k => $v) {
                if (isset($aParam[$k])) {
                    $brocast->setPredefinedKeyValue($k, $aParam[$k]);
                }
            }
        }
    }
    
    /**
     * 构建参数
     * @param unknown $brocast
     * @param unknown $aParam
     */
    private static function buildIosParam($brocast, $aParam)
    {
        self::$aConf = Util_Common::getConf('umeng');
        $brocast->setAppMasterSecret(self::$aConf['msecret_ios']);
        $brocast->setPredefinedKeyValue("appkey", self::$aConf['appkey_ios']);
        $brocast->setPredefinedKeyValue("timestamp", strval(time()));
        // Set 'production_mode' to 'false' if it's a test device.
        // For how to register a test device, please see the developer doc.
        $brocast->setPredefinedKeyValue("production_mode", "true");
    
        foreach (self::$aCommonKey as $k) {
            if (isset($aParam[$k])) {
                $brocast->setPredefinedKeyValue($k, $aParam[$k]);
            }
        }
        
        foreach (self::$aIosKey as $k) {
            if (isset($aParam[$k])) {
                $brocast->setPredefinedKeyValue($k, $aParam[$k]);
            }
        }
    
        // 设置自定义数据
        if (isset($aParam['aExtra'])) {
            foreach ($aParam['aExtra'] as $k => $v) {
                $brocast->setCustomizedField($k, $v);
            }
        }
    
        // 设置策略
        if (isset($aParam['aPolicy'])) {
            foreach (self::$aPolicy as $k => $v) {
                if (isset($aParam[$k])) {
                    $brocast->setPredefinedKeyValue($k, $aParam[$k]);
                }
            }
        }
    }
}