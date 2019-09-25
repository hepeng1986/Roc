<?php

/**
 * @author xuchuyuan
 * @date 20190109 22:00
 * @deprecated JsonWebToken token生成/验证类
 */
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;

class Util_JWT
{

    /**
     * 生成Token
     * @param array $params
     * @return string
     */
    public static function initToken ($params=array(), $sign_key='')
    {
        $build = new Builder();
        $oToken = $build->setExpiration(time() + 36000);

        foreach ($params as $k => $v) {
            $oToken->set($k, $v);
        }

        $token = $oToken->sign(new Sha256(), $sign_key)->getToken();

        return (string)$token;
    }
    /**
     * 生成Token 带失效时间
     * @param array $params
     * @return string
     */
    public static function initTokenExpire ($params=array(), $sign_key='',$expire = 36000)
    {
        $build = new Builder();
        if(!is_int($expire)){
            $expire = 36000;
        }
        $oToken = $build->setExpiration(time() + $expire);

        foreach ($params as $k => $v) {
            $oToken->set($k, $v);
        }

        $token = $oToken->sign(new Sha256(), $sign_key)->getToken();

        return (string)$token;
    }
    /**
     * 解析Token
     * 1.token过期
     * 2.sign非法
     * @param $token
     * @param $sign_key
     * @return object
     */
    public static function parseToken ($token, $sign_key='')
    {
        $token = (new Parser())->parse($token);
        try {
            if (!$token->validate(new ValidationData())) {
                return 'token已过期';
            }

            if (!$token->verify(new Sha256(), $sign_key)) {
                return '签名非法';
            }

            return $token;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}