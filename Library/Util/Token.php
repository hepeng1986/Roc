<?php

class Util_Token
{
    // 获取key值
    public static function key ($key)
    {
        return Util_Common::getConf($key, 'token');
    }
    
    // 获取某个Cookie值
    public static function get ($key, $token)
    {
        $aRet = json_decode(Util_Crypt::decrypt($token, self::key($key)), true);
        if (empty($aRet) || $aRet['expire'] < time()) {
            return false;
        }
        
        return $aRet['value'];
    }
    
    // 设置某个Cookie值
    public static function make ($key, $value, $expire)
    {
        $data = [
            'value' => $value,
            'expire' => time() + $expire
        ];
        return Util_Crypt::encrypt(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), self::key($key));
    }
}