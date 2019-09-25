<?php
/**
 * Created by PhpStorm.
 * User: zhangelexiong
 * Date: 15/3/30
 * Time: 上午12:24
 */

class Admin_Model_WxUserApi extends Admin_Model_WxApiBase
{
    protected $_sFollowUserListUrl = "https://api.weixin.qq.com/cgi-bin/user/get";//获取当前关注着列表
    protected $_sUserInfoUrl= "https://api.weixin.qq.com/cgi-bin/user/info";//获取用户信息


    protected $_sAuthUserInfoUrl = "https://api.weixin.qq.com/sns/userinfo";//网页授权获取用户信息
    protected $_sAuthUrl = "https://open.weixin.qq.com/connect/oauth2/authorize";//用户授权获取code
    protected $_sAuthAccessTokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token';//通过code获取用户信息
    protected $_sAuthCheckUrl = 'https://api.weixin.qq.com/sns/auth';//检验授权凭证（access_token）是否有效
    protected $_sRefreshAccessTokenUrl = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';//刷新access_token

    protected $_sBatchGetUserUrl = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget'; //批量获取微信帐号信息

    /**
     * @var string
     * 授权方式 静默授权
     */
    protected $_sScopeBase = 'snsapi_base';

    /**
     * @var string
     * 用户手动授权
     */
    protected $_sScopeInfo = 'snsapi_userinfo';

    protected $_sResponseType= 'code';
    protected $_sWechatRedirect = 'wechat_redirect';
    protected $_sRefreshToken = 'refresh_token';

    protected $_sGrantType = 'authorization_code';

    protected $sOuthRedirect_uri = '';//自定义


    /**
     * @param $aUserOpenIDList
     * @param $sToken
     * @return mixed
     * 批量获取用户信息
     * doc url http://mp.weixin.qq.com/wiki/1/8a5ce6257f1d3b2afb20f83e72b72ce9.html
     */
    public function batchGetUser($aUserOpenIDList, $sToken)
    {

        $param['access_token'] = $sToken;
        $sApiUrl = $this->_sBatchGetUserUrl."?" . http_build_query($param);

        $sReturn = $this->curl($sApiUrl, true, $aUserOpenIDList);
        return json_decode($sReturn, true);
    }


    /**
     * 关注者列表
     * @param $sToken
     * @return mixed
     */
    public function getWxUserList($sToken)
    {
        $param['access_token'] = $sToken;
        $sApiUrl = $this->_sFollowUserListUrl."?" . http_build_query($param);
        $sReturn = $this->curl($sApiUrl);
        return json_decode($sReturn, true);
    }

    /**
     * 获取微信用户信息
     * access_token    调用接口凭证
     * openid    用户的唯一标识
     * lang    返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
     * return 没有关注返回openid和是否关注，关注才返回用户信息
     */
    public function getWxUserInfo($sToken, $sOpenID, $lang = "zh_CN")
    {

        $param['access_token'] = $sToken;
        $param['openid'] = $sOpenID;
        $param['lang'] = $lang;
        $sApiUrl = $this->_sUserInfoUrl."?" . http_build_query($param);
        $sReturn = $this->curl($sApiUrl);
        return json_decode($sReturn, true);
    }

    /**
     * 通过授权获取微信用户信息（需scope为 snsapi_userinfo）
     * access_token    网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
     * openid    用户的唯一标识
     * lang    返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
     */
    public function getOauthWxUserInfo($sToken, $sOpenID, $lang = "zh_CN")
    {
        $param['access_token'] = $sToken;
        $param['openid'] = $sOpenID;
        $param['lang'] = $lang;
        $sApiUrl = $this->_sAuthUserInfoUrl."?" . http_build_query($param);
        $sReturn = $this->curl($sApiUrl);
        return json_decode($sReturn, true);

    }

    /**
     * 用户授权(参数顺序要固定)
     * appid     是     公众号的唯一标识
     * redirect_uri     是     授权后重定向的回调链接地址，请使用urlencode对链接进行处理
     * response_type     是     返回类型，请填写code
     * scope     是     应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
     * state     否     重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
     * #wechat_redirect     是     无论直接打开还是做页面302重定向时候，必须带此参数
     */
    public function oauth($sRedirectUrl, $scope = 0, $isret = 0, $state = '1111')
    {

        $sApiUrl = $this->_sAuthUrl . '?';
        $aParam['appid'] = $this->_sAppID;
        $aParam['redirect_uri'] = $sRedirectUrl;
        $aParam['response_type'] = $this->_sResponseType;
        $aParam['scope'] = $scope == 1 ? $this->_sScopeInfo : $this->_sScopeBase;
        $aParam['state'] = $state;
        $sApiUrl .= http_build_query($aParam);
        $sApiUrl .= '#' . $this->_sWechatRedirect;
        if($isret){
            return $sApiUrl;
        }
        else{
            header("location:" . $sApiUrl);
        }
    }

    /**
     * 通过code获取用户信息
     * appid     是     公众号的唯一标识
     * secret     是     公众号的appsecret
     * code     是     填写第一步获取的code参数
     * grant_type     是     填写为authorization_code
     * @param $aParam
     */
    public function getAccessTokenByCode($code)
    {
        $sApiUrl = $this->_sAuthAccessTokenUrl . '?';
        $param['grant_type'] = $this->_sGrantType;
        $param['appid'] = $this->_sAppID;
        $param['secret'] = $this->_sAppSecret;
        $param['code'] = $code;
        $sApiUrl .= http_build_query($param);
        $sReturn = self::curl($sApiUrl);
        return json_decode($sReturn, true);
    }

    /**
     * 判断access_token是否有效
     * access_token     网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
     * openid     用户的唯一标识
     */
    public function checkIsValid($sToken, $sOpenID)
    {

        $param['access_token'] = $sToken;
        $param['openid'] = $sOpenID;

        $sApiUrl = $this->_sAuthCheckUrl."?" . http_build_query($param);
        $sReturn = $this->curl($sApiUrl);
        return json_decode($sReturn, true);
    }

    /**
     * 刷新access_token
     * appid     是     公众号的唯一标识
     * grant_type     是     填写为refresh_token
     * refresh_token     是     填写通过access_token获取到的refresh_token参数
     * @param $aParam
     */
    public function refreshToken($sRefreshToken)
    {
        $sApiUrl = $this->_sRefreshAccessTokenUrl . '?';
        $param['grant_type'] = $this->_sRefreshToken;
        $param['refresh_token'] = $sRefreshToken;
        $param['appid'] = $this->_sAppID;
        $sApiUrl .= http_build_query($param);
        $sReturn = $this->curl($sApiUrl);
        return json_decode($sReturn, true);
    }

}