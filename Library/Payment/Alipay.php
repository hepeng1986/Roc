<?php

/**
 * 支付宝支付模块
 * @author len
 *
 */
class Payment_Alipay
{
    /**
     * 生成支付表单
     * @param unknown $orderid
     * @param unknown $subject
     * @param unknown $body
     * @param unknown $total_fee
     * @return Ambigous <表单提交HTML信息, 提交表单HTML文本, string>
     */
    public static function makePay($orderid, $subject, $body, $total_fee)
    {
        if (ENV_SCENE == 'dev') {
            $total_fee = 0.01;
        }
        
        require_once LIB_PATH . '/Payment/Alipay/alipay_service.class.php';
        
        $alipay_config = Yaf_G::getConf('aliapy_config', null, 'alipay');
        
        $t_paymethod = '';
        $t_defaultbank = '';
        
        // 订单ID
        //$orderid = $this->getParam('id');
        // 请与贵网站订单系统中的唯一订单号匹配
        //$out_trade_no = $orderid; // $_POST['orderno'];
        // 订单名称，显示在支付宝收银台里的“商品名称”里，显示在支付宝的交易管理的“商品名称”的列表里。
        //$subject = "51wom"; // $_POST['subject'];
        // 订单描述、订单详细、订单备注，显示在支付宝收银台里的“商品描述”里
        //$body = "在线支付"; // @$_POST['body'];
        // 订单总金额，显示在支付宝收银台里的“应付总额”里
        //$total_fee = 0.01; // $_POST['total_fee'];
        
        // 默认支付方式，取值见“即时到帐接口”技术文档中的请求参数列表
        $paymethod = $t_paymethod;
        // 默认网银代号，代号列表见“即时到帐接口”技术文档“附录”→“银行列表”
        $defaultbank = $t_defaultbank;
        
        // 防钓鱼时间戳
        $anti_phishing_key = '';
        // 获取客户端的IP地址，建议：编写获取客户端IP地址的程序
        $exter_invoke_ip = '';
        // 商品展示地址，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
        $show_url = ''; // PageHelper::book2_url();
        // 自定义参数，可存放任何内容（除=、&等特殊字符外），不会显示在页面上
        $extra_common_param = '';
        
        // 扩展功能参数——分润(若要使用，请按照注释要求的格式赋值)
        $royalty_type = ""; // 提成类型，该值为固定值：10，不需要修改
        $royalty_parameters = "";
        
        // 构造要请求的参数数组
        $parameter = array(
            "service" => "create_direct_pay_by_user",
            "payment_type" => "1",
        
            "partner" => trim($alipay_config['partner']),
            "_input_charset" => trim(strtolower($alipay_config['input_charset'])),
            "seller_email" => trim($alipay_config['seller_email']),
            "return_url" => $alipay_config['return_url'],
            "notify_url" => $alipay_config['notify_url'], // trim($alipay_config['notify_url']),
        
            "out_trade_no" => $orderid,
            "subject" => $subject,
            "body" => $body,
            "total_fee" => $total_fee,
        
            "paymethod" => $paymethod,
            "defaultbank" => $defaultbank,
        
            "anti_phishing_key" => $anti_phishing_key,
            "exter_invoke_ip" => $exter_invoke_ip,
        
            "show_url" => $show_url,
            "extra_common_param" => $extra_common_param,
        
            "royalty_type" => $royalty_type,
            "royalty_parameters" => $royalty_parameters
        );
        
        // 构造即时到帐接口
        $alipayService = new AlipayService($alipay_config);
        return $alipayService->create_direct_pay_by_user($parameter);
    }
    
    /**
     * 支付宝回调
     * @param unknown $aParams
     * @return Ambigous <验证结果, boolean>
     */
    public static function callback($aParams) 
    {
        require_once LIB_PATH . '/Payment/Alipay/alipay_service.class.php';
        require_once LIB_PATH . '/Payment/Alipay/alipay_notify.class.php';
        
        $alipay_config = Yaf_G::getConf('aliapy_config', null, 'alipay');
        
        $alipayNotify = new AlipayNotify($alipay_config);
        return $alipayNotify->verifyNotify($aParams);
    } 
}