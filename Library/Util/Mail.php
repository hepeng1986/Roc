<?php
/**
 * 邮件服务
 *  
 *  User: pancke@qq.com
 *  Date: 2015-11-12
 *  Time: 上午11:40:25
 */

class Util_Mail
{ 
    /**
     * 发送邮件
     * @param mixed $mTo    发给谁，单个或数组
     * @param string $sSubject 邮件标题
     * @param string $sBody 邮件内容，如果此选项为文件路径时，则为模板文件
     * @param array $aParam 参数
     * @param 附件 $sFile
     * @return boolean
     */
    public static function send($mTo, $sSubject, $sBody, $aParam = array(), $sFile = '')
    {
        if (is_file(APP_PATH . $sBody)) {
            $sBody = file_get_contents(APP_PATH . $sBody);
        }
        foreach ($aParam as $k => $v) {
            $sSubject = str_replace('{' . $k .'}', $v, $sSubject);
            $sBody = str_replace('{' . $k .'}', $v, $sBody);
        }
    
        try {
            $conf = Util_Common::getConf('mailer');
            $mail = new Util_PHPMailer(true);
            $mail->IsSMTP();
            $mail->SMTPAuth   = true;
            $mail->Port       = $conf['smtp_port'];
            $mail->Host       = $conf['smtp_host'];
            $mail->Username   = $conf['smtp_user'];
            $mail->Password   = $conf['smtp_pass'];
            $mail->From       = $conf['from_email'];
            //发送邮件附件
            if(is_file($sFile)){
                $mail->AddAttachment($sFile);
            }
            $mail->FromName   = $conf['from_name'];
            $mail->SMTPSecure = $conf['smtp_secure'];
            $mail->CharSet    = "utf-8";
            $mail->IsHTML( true );
            $mail->Subject  = $sSubject;
            $mail->MsgHTML($sBody);
            $mail->ClearAddresses();
            
            // 发多个人的情况
            if (!is_array($mTo)) {
                $mTo = array($mTo);
            }
            foreach ($mTo as $sTo) {
                $mail->AddAddress($sTo);
            }
            
            $mail->Send();
            $mail = null;
    
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}