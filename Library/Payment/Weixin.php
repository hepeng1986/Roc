<?php

/**
 * 微信支付模块
 * @author len
 *
 */
require_once LIB_PATH . "/Payment/Weixin/lib/WxPay.Api.php";
require_once LIB_PATH . "/Payment/Weixin/lib/WxPay.Notify.php";
require_once LIB_PATH . "/Payment/Weixin/lib/WxPay.NativePay.php";

class Payment_Weixin
{

    /**
     * 生成微信支付二维码
     */
    public static function qrcode ($product_id, $body, $total_fee, $attach = '',  $detail = '', $goods_tag = '')
    {
        // 处理金额
        if (ENV_SCENE == 'dev') {
            $total_fee = 0.01;
        }
        $total_fee *= 100;
        
        $notify = new NativePay();
        /**
         * 流程：
         * 1、调用统一下单，取得code_url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、支付完成之后，微信服务器会通知支付成功
         * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */
        $input = new WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach($product_id);
        
        if (! empty($detail)) {
            $input->SetDetail($detail);
        }
        $input->SetOut_trade_no(WxPayConfig::$APPID.substr($product_id,1));
        $input->SetTotal_fee($total_fee);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        if (! empty($goods_tag)) {
            $input->SetGoods_tag($goods_tag);
        }
        $input->SetNotify_url(WxPayConfig::$NOTIFY_URL);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($product_id);
        $result = $notify->GetPayUrl($input);
        return $result["code_url"];
    }

    /**
     * 微信异步调用
     */
    public static function notify ($callback)
    {
        self::debug("begin notify");
        $notify = new PayNotifyCallBack();
        $notify->callback = $callback;
        $notify->Handle(false);
    }

    public static function debug ($msg)
    {
        Yaf_Logger::debug($msg . "\n", 'wxpay');
    }
}

class PayNotifyCallBack extends WxPayNotify
{
    public $callback;
    
    // 查询订单
    public function Queryorder ($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        Payment_Weixin::debug("Queryorder:" . json_encode($result));
        if (array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS") {
            return true;
        }
        return false;
    }
    
    // 重写回调处理函数
    public function NotifyProcess ($data, &$msg)
    {
        Payment_Weixin::debug("NotifyProcess:" . json_encode($data));
        $notfiyOutput = array();
        
        if (! array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确";
            return false;
        }
        
        // 查询订单，判断订单真实性
        if (! $this->Queryorder($data["transaction_id"])) {
            $msg = "订单查询失败";
            return false;
        }
        
        call_user_func($this->callback, $data);
        
        return true;
    }
}