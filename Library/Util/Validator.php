<?php

/**
 * @author: xuchuyuan
 * @date: 20190109 22:00
 * @deprecated:验证类
 */
use WebGeeker\Validation\Validation;

class Util_Validator
{

    /**
     * validator捕获参数异常
     * @param $params
     * @param $validations
     * @return string
     */
    public static function validator ($params, $validations)
    {
        try {
            Validation::validate($params, $validations);
            return '';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    /*
     * 校验参数,默认校验方法
     */
    public static function defaultCheck (&$params, $conf)
    {
        $sMsg = "success";
        if(!is_array($conf) || !is_array($params)){
            return "fail";
        }
        try {
            //循环处理参数
            foreach ($conf as $ck=>$cv){
                $field = $cv["param"];
                //如果没传或传了空值
                if(!isset($params[$field]) || $params[$field]==""){
                    //如果有默认值，则可不填
                    if($cv["default"]!==""){
                        //如果默认值是函数
                        if(is_string($cv["default"]) && $cv["default"][0] == "="){
                            $funStr = str_replace("=","",$cv["default"]);
                            $params[$field] = eval("return ".$funStr.";");
                        }else{
                            $params[$field] = $cv["default"];
                        }
                    }elseif(!empty($cv["fun"]) || !empty($cv["validations"]) ){
                        //如果需要校验，因为没填值，所以肯定错误
                        $err = !empty($cv["errMsg"])?$cv["errMsg"]:($field."为空且无默认值");
                        throw new Exception($err);
                    }
                }else{
                    //自定义
                    if(!empty($cv["fun"])){
                        $err = !empty($cv["errMsg"])?$cv["errMsg"]:($field."校验错误");
                        $bFlag = call_user_func($cv["fun"],$params[$field]);
                        if(!$bFlag){//校验失败
                            throw new Exception($err);
                        }
                    }else{
                        //WebGeeker
                        if(!empty($cv["validations"])){
                            $aTemp = [];
                            if(empty($cv["errMsg"])){
                                $aTemp[$field] = $cv["validations"];
                            }else{
                                $aTemp[$field] = $cv["validations"]."|>>>:{$cv["errMsg"]}";
                            }
                            Validation::validate($params, $aTemp);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $sMsg;
    }
    /**
     * validator捕获参数异常
     * @param $params
     * @param $validations
     * @return string
     */
    public static function validatorExt (&$params, $aConf)
    {
        //添加两个类别，一个Default,一个自定义函数Fun
        $aValid = [];
        try {
            foreach ($aConf as $ack=>$acv){
                $sRule = "";
                $aLastRule = [];
                $aRule = explode("|",$acv);
                foreach ($aRule as $rk=>$rv){
                    $rv = trim($rv);
                    if(empty($rv)){
                        continue;
                    }
                    $aSub = explode(":",$rv);
                    if(strtolower($aSub[0]) == "default"){
                        if(!isset($aSub[1]) || $aSub[1]===""){
                            throw  new Exception("{$ack}:默认值没有填写");
                        }
                        if(!isset($params[$ack])){
                            $params[$ack] = eval("return {$aSub[1]};");
                            break;//没有传值且有默认值则无需校验
                        }
                        continue;
                    }elseif(strtolower($aSub[0]) == "fun") {//fun要写在default后面
                        if (!isset($aSub[1])) {
                            throw  new Exception("{$ack}:自定义函数没有填写");
                        }
                        if (!isset($params[$ack])) {
                            throw new Exception("{$ack}:不能为空");
                        }
                        $bFlag = call_user_func("Util_Validate::{$aSub[1]}", $params[$ack]);
                        if (!$bFlag) {//校验失败
                            throw new Exception("{$ack}:校验错误");
                        }
                        break;//校验成功，不走WebGeeker,结束
                    }elseif(strtolower($aSub[0]) == "required"){//bug
                        if(!isset($params[$ack])){
                            throw new Exception("必须提供参数{$ack}");
                        }
                    }
                    $aLastRule[] = $rv;
                }
                //重新生成新的规则
                $sRule = implode("|",$aLastRule);
                $sRule? $aValid[$ack] = $sRule : "";
            }
            Validation::validate($params, $aValid);
            return '';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}