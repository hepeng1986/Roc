<?php
/**
 * Created by PhpStorm.
 * User: yaobiqing
 * Date: 16/5/26
 * Time: 上午10:15
 */

/**
 * Class Admin_Model_Youzan
 * 有赞操作类
 */
class Admin_Model_Youzan
{
    /**
     * 添加商品
     */
    public static function addGood($sTitle, $fPrice, $fPostFee = 0.00, $iIsVirtual = 1, $iQuantity = 1, $sDesc = '', $sImage = '', $sOutID='')
    {
        $oYouzanClient = Util_Common::getYouzanClient();
        $method = 'kdt.item.add';
        $params = [];
        $params['title'] = $sTitle;
        if ($sImage) {
            $params['image_ids'] = $sImage;
        }
        $params['outer_id'] = $sOutID;
        $params['desc'] = $sDesc;
        $params['price'] = $fPrice;
        $params['post_fee'] = $fPostFee;
        $params['is_virtual'] = $iIsVirtual;
        $params['quantity'] = $iQuantity;
        return $oYouzanClient->post($method, $params, []);
    }

    /**
     * 下架商品
     */
    public static function offGood($num_iid)
    {
        $oYouzanClient = Util_Common::getYouzanClient();
        $method = 'kdt.item.update.delisting';
        $params = [];
        $params['num_iid'] = $num_iid;
        return $oYouzanClient->post($method, $params, []);

    }

    /**
     * 获取订单
     */
    public static function getOrder()
    {

    }




}