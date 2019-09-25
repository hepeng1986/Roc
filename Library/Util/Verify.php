<?php

/**
 * 验证码
 * @author len
 *
 */
class Util_Verify
{
    const TYPE_AD_IMAGE = 1;
    const TYPE_MEDIA_IMAGE = 2;
    const TYPE_FORGET_IMAGE = 3;
    const TYPE_REGISTER_IMAGE = 4;//注册验证码
    const TYPE_LOGIN_IMAGE = 5;//账号登陆验证码
    const TYPE_CARD_LOGIN_IMAGE = 6;//卡号登陆验证码

    const TYPE_SMS_FORGET = 11;//忘记密码
    const TYPE_SYS_REGISTER = 10;//注册手机验证码
    const TYPE_SYS_LOGIN = 8;//登陆手机验证
    const SEND_SYS_LIMIT = 60;//发送间隔

    const TYPE_SMS_ADVICE = 13;//深度咨询短信验证码
    const TYPE_ADVICE_IMAGE = 14;//深度咨询图片验证码

    /**
     * 获取图片验证码
     * @param unknown $iType
     * @return string
     */
    public static function makeImageCode($iType)
    {
        $sKey = self::getImageKey($iType);
        $sRand = Util_Tools::passwdGen(4);
        Util_Cookie::set($sKey, $sRand, 1800);
        return Util_Image::createIdentifyCodeImage(120, 50, $sRand);
    }

    /**
     * 取得验证码Cookie名
     *
     * @param int $iType
     * @return string
     */
    protected static function getImageKey($iType)
    {
        $sGuid = Util_Cookie::get('guid');
        return $sGuid . '_' . $iType;
    }

    /**
     * 检测验证码是否正确
     * @param unknown $iType
     * @param unknown $sCode
     * @return bool
     */
    public static function checkImageCode($iType, $sCode)
    {
        $sKey = self::getImageKey($iType);
        $sSaveCode = Util_Cookie::get($sKey);
        Util_Cookie::delete($sKey);
        return strtoupper($sCode) == strtoupper($sSaveCode);
    }

    /**
     * 取得验证码Cookie名
     *
     * @param int $iType
     * @return string
     */
    protected static function getSmsKey($iType)
    {
        $sGuid = Util_Cookie::get('guid');
        return $sGuid . 's_' . $iType;
    }

    /**
     * 取得短信验证码时间间隔Cookie名
     *
     * @param int $iType
     * @return string
     */
    protected static function getSmsLimitKey($iType)
    {
        $sGuid = Util_Cookie::get('guid');
        return $sGuid . 's_l_' . $iType;
    }

    /**
     * 发送手机验证码
     * @param unknown $sMobile
     * @param unknown $iType
     */
    public static function makeSMSCode($sMobile, $iType)
    {
        $sLimitKey = self::getSmsLimitKey($iType);
        if (!Util_Cookie::get($sLimitKey)) {
            $sKey = self::getSmsKey($iType);
            $sRand = Util_Tools::passwdGen(4, Util_Tools::FLAG_NUMERIC);
            $iTempID = Util_Common::getConf($iType, 'aSmsTempID');
            $aRet = Sms_CCP::sendTemplateSMS($sMobile, array($sRand, 5), $iTempID);
            if ($aRet['status']) {
                Admin_Model_VerifyCode::addCode($sMobile, $iType, $sRand);
            }
            return $aRet;
        } else {
            $aRet['status'] = 1;
            return $aRet;
        }
    }

    /**
     * 检验验证码
     * @param unknown $sMobile
     * @param unknown $iType
     * @param unknown $sCode
     */
    public static function checkSMSCode($sMobile, $iType, $sCode)
    {
        $aCode = Admin_Model_VerifyCode::getCode($sMobile, $iType);
        if (time() - $aCode['iCreateTime'] > 300) {
            return false;
        }
        if (strtoupper($sCode) == strtoupper($aCode['sCode'])) {
            return true;
        } else {
            return false;
        }
    }
}