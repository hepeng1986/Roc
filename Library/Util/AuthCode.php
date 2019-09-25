<?php

/**
 * 发送短信
 * @author xuchuyuan
 *
 */
class Util_AuthCode
{

    const PRE_WRONG = 'wrong_';
    const PRE_REQUEST = 'request_';

    /**
     * 设置当前请求key的时间戳
     * @param  $key
     * @param  $time
     * @param  $ttl
     * @return NULL
     * @throws
     */
    public static function setRequestTime($key, $time='', $ttl=600)
    {
        $time = ($time && $time < time()) ? $time : time();
        Util_Common::getRedis()->set (self::PRE_REQUEST . $key, $time, $ttl);
    }


    /**
     * 检测是否频繁请求验证码
     * @param type $key
     * @param type $ttl
     * @return boolean
     * @throws
     */
    public static function checkIsOftenRequest($key, $ttl=60)
    {
        $time = Util_Common::getRedis()->get (self::PRE_REQUEST . $key);
        if (!$time) {
            return false;
        }

        $diff_time = time() - $time;

        return $diff_time < $ttl;
    }

    /**
     * 输入错误 错误计数加1
     * @param type $key
     * @return boolean
     * @throws
     */
    public static function incrWrongTimes($key)
    {
        Util_Common::getRedis()->incr (self::PRE_WRONG . $key);
    }

    /**
     * 检测是否超出每日限制
     * @param type $key
     * @return boolean
     * @throws
     */
    public static function checkRequestWrongTimes($key)
    {
        $wrong_times = Util_Common::getRedis()->get (self::PRE_WRONG . $key);
        if ($wrong_times > 5) {
            return true;
        }

        return false;
    }

    /**
     * 设置当前key的val
     * @param  $key
     * @param  $code
     * @param  $ttl
     * @return NULL
     * @throws
     */
    public static function setExpireTime($key, $code='', $ttl=300)
    {
        return Util_Common::getRedis()->set ($key, $code, $ttl);
    }
}