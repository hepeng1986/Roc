<?php

/**
 * 发送短信
 * @author xiejinci
 *
 */
class Util_Sms
{
    /**
     * 发送带模板的短信
     * @param unknown $sMobile
     * @param unknown $iTempID
     */
    public static function sendTemplateSms($sMobile, $aData, $iTempID)
    {
        return Sms_CCP::sendTemplateSMS($sMobile, $aData, $iTempID);
    }
}