<?php

/**
 * 运通讯SDK
 * @author len
 *
 */
require_once LIB_PATH . '/Sms/CCP/SDK/CCPRestSDK.php';

class Sms_CCP
{
    public static $aError = array(
    	112300 => '【短信】接收短信的手机号码为空',
    	112301 => '【短信】短信正文为空',
    	112302 => '【短信】群发短信已暂停',
    	112303 => '【短信】应用未开通短信功能',
    	112304 => '【短信】短信内容的编码转换有误',
    	112305 => '【短信】应用未上线，短信接收号码外呼受限',
    	112306 => '【短信】接收模板短信的手机号码为空',
    	112307 => '【短信】模板短信模板ID为空',
    	112308 => '【短信】模板短信模板data参数为空',
    	112309 => '【短信】模板短信内容的编码转换有误',
    	112310 => '【短信】应用未上线，模板短信接收号码外呼受限',
    	112311 => '【短信】短信模板不存在',
    	112312 => '【短信】应用未配置此短信扩展码',
    	112313 => '【短信】未查到符合条件的短信模板',
    	112314 => '【短信】短信下发超过当日发送限制',
    	112315 => '【短信】短信模板ID长度超长',
    	112316 => '【短信】接收短信的多个号码格式错误',
    	112317 => '【短信】号码在黑名单中',
    	112318 => '【短信】短信模板ID要求为数字',
    	112319 => '【短信】接收短信的手机号码超长',
    	112320 => '【短信】用户未配置接收上行短信及回执的回调地址',
    	112321 => '【短信】用户未勾选接收上行短信及回执',
    	112323 => '【短信】短信模板不存在或审核未通过',
    	112324 => '【短信】日期不能为空',
    	112325 => '【短信】msgId不能为空',
    	112326 => '【短信】发送短信失败',
    	112327 => '【短信】日期参数含有非法字符',
    	112328 => '【短信】电话号码参数含有非法字符',
    	112329 => '【短信】获取短信状态报告count参数不正确',
    	112330 => '【短信】获取短信状态报告count参数超过最大限制',
    	112341 => '【短信】短信模板产品类型不能为空',
    	112342 => '【短信】rest接口生成短信模板时产品类型仅支持已发布应用和网页',
    	112343 => '【短信】短信模板标题不能为空',
    	112344 => '【短信】短信模板签名不能为空',
    	112345 => '【短信】短信模板内容不能为空',
    	112346 => '【短信】短信模板签名必须大于3个字小于8个字',
    	112347 => '【短信】短信模板签名必须带有中文',
    	112348 => '【短信】短信模板产品地址不能为空',
    	112349 => '【短信】账户必须企业认证',
    	112350 => '【短信】账户必须累计充值500元以上',
    	112351 => '【短信】短信状态不存在',
    	112352 => '【短信】短信状态已过期',
    	112353 => '【短信】应用不属于该主账号',
    	112354 => '【短信】查询超过并发次数',
    	112356 => '【短信】短信已发出但未收到回执请稍后查询',
    	113302 => '【短信】您正在使用云通讯测试模板且短信接收者不是注册的测试号码',
    	160001 => '【短信】短信通道繁忙',
    	160002 => '【短信】短信通道繁忙',
    	160003 => '【短信】短信通道繁忙',
    	160004 => '【短信】短信通道繁忙',
    	160005 => '【短信】短信通道繁忙',
    	160006 => '【短信】短信通道繁忙',
    	160007 => '【短信】短信通道繁忙',
    	160008 => '【短信】短信通道繁忙',
    	160009 => '【短信】短信通道繁忙',
    	160010 => '【短信】短信通道繁忙',
    	160011 => '【短信】存在屏蔽词的词汇，目前模板短信都没有校验屏蔽词。',
    	160012 => '【短信】短信通道认证出错。',
    	160013 => '【短信】号码格式不对，或者该号码不支持',
    	160014 => '【短信】短信通道余额不足',
    	160015 => '【短信】限制对单号码的发送次数',
    	160016 => '【短信】通道限制发送速度',
    	160017 => '【短信】单次提交号码过多。天天限制是100个',
    	160018 => '【短信】短信通道返回有屏蔽词',
    	160019 => '【短信】签名无效',
    	160020 => '【短信】短信内容过长。天天限制是250个字符。',
    	160021 => '【短信】相同的内容发给同一手机一天中只能发一次',
    	160022 => '【短信】对同一个手机一天发送的短信超过限制次数',
    	160031 => '【短信】参数解析失败',
    	160032 => '【短信】短信模板无效',
    	160033 => '【短信】短信存在黑词',
    	160034 => '【短信】号码黑名单',
    	160035 => '【短信】短信下发内容为空',
    	160036 => '【短信】短信模板类型未知',
    	160037 => '【短信】短信内容长度限制',
    	160038 => '【短信】短信验证码发送过频繁',
    	160039 => '【短信】发送数量超出同模板同号天发送次数上限',
    	160040 => '【短信】验证码发送频繁，为了您的账户安全，请明天再试',
    	160041 => '【短信】通知超出同模板同号码天发送上限',
    	160042 => '【短信】号码格式有误',
    	160043 => '【短信】应用与模板id不匹配',
    	160050 => '【短信】短信发送失败'
    );
    
    /**
     * 初始化REST SDK
     * @return Ambigous <NULL, unknown>
     */
    public static function getRest()
    {
        $aConf = Util_Common::getConf('CCP');
        global $accountSid, $accountToken, $appId, $serverIP, $serverPort, $softVersion;
        $rest = new REST($aConf['host'], $aConf['port'], $aConf['version']);
        $rest->setAccount($aConf['sid'], $aConf['token']);
        $rest->setAppId($aConf['appid']);
        
        return $rest;
    }
    
    /**
     * 发送模板短信
     *
     * @param
     *            to 手机号码集合,用英文逗号分开
     * @param
     *            datas 内容数据 格式为数组 例如：array('Marry','Alon')，如不需替换请填 null
     * @param $tempId 模板Id            
     */
    public static function sendTemplateSMS ($to, $datas, $tempId)
    {
        $aRet = array();
        $rest = self::getRest();

        // 发送模板短信
        // echo "Sending TemplateSMS to $to <br/>";
        $result = $rest->sendTemplateSMS($to, $datas, $tempId);
        if ($result == NULL) {
            $aRet['data'] = "未知错误";
            $aRet['status'] = false;
        } else {
            if ($result->statusCode != 0) {
                $code = intval($result->statusCode);
                $aRet['data'] = isset(self::$aError[$code]) ? self::$aError[$code] : '未知错误';
                $aRet['status'] = false;
            } else {
                // echo "Sendind TemplateSMS success!<br/>";
                // 获取返回信息
//                 $smsmessage = $result->TemplateSMS;
//                 $aRet['data'] = "dateCreated:" . $smsmessage->dateCreated . "<br/>";
//                 $aRet['data'] .= "smsMessageSid:" . $smsmessage->smsMessageSid . "<br/>";
                $aRet['data'] = '发送成功';
                $aRet['status'] = true;
            }
        }
        
        return $aRet;
    }

    /**
     * 语音验证码
     *
     * @param
     *            verifyCode 验证码内容，为数字和英文字母，不区分大小写，长度4-8位
     * @param
     *            playTimes 播放次数，1－3次
     * @param
     *            to 接收号码
     * @param
     *            displayNum 显示的主叫号码
     * @param
     *            respUrl 语音验证码状态通知回调地址，云通讯平台将向该Url地址发送呼叫结果通知
     * @param
     *            lang 语言类型。取值en（英文）、zh（中文），默认值zh。
     * @param
     *            userData 第三方私有数据
     */
    public static function voiceVerify ($verifyCode, $to, $playTimes = 2, $displayNum = null, $respUrl = null, $lang ='zh', $userData = null)
    {
        $aRet = array();
        $rest = self::getRest();
        
        // 调用语音验证码接口
        //echo "Try to make a voiceverify,called is $to <br/>";
        $result = $rest->voiceVerify($verifyCode, $playTimes, $to, $displayNum, $respUrl, $lang, $userData);
        if ($result == NULL) {
            $aRet['data'] = "result error!";
            $aRet['status'] = false;
        } else {
            if ($result->statusCode != 0) {
                $aRet['data'] =  "error code :" . $result->statusCode . "<br>";
                $aRet['data'] .=  "error msg :" . $result->statusMsg . "<br>";
                $aRet['status'] = false;
            } else {
                //echo "voiceverify success!<br>";
                // 获取返回信息
                $voiceVerify = $result->VoiceVerify;
                $aRet['data'] = "callSid:" . $voiceVerify->callSid . "<br/>";
                $aRet['data'] .= "dateCreated:" . $voiceVerify->dateCreated . "<br/>";
                $aRet['status'] = true;
            }
        }
        
        return $aRet;
    }
}