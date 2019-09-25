<?php

class Util_Uri
{

    /**
     * 获取URL的Domain
     *
     * @param
     *            $p_sUrl
     */
    public static function getDomain ($p_sUrl)
    {
        if (empty($p_sUrl)) {
            $sDomain = '';
        } else {
            $sDomain = parse_url($p_sUrl, PHP_URL_HOST);
        }

        return $sDomain;
    }

    /**
     * 获取前当URL
     *
     * @return string
     */
    public static function getCurrUrl ()
    {
        return Yaf_G::getUrl();
    }

    /**
     * 生成新的搜索URL
     *
     * @param unknown $aParam
     * @param unknown $sReplaceKey
     * @param unknown $sNewValue
     */
    public static function makeSearchUrl ($aParam, $sReplaceKey, $sNewValue, $iSelect = 0, $sUri = null)
    {
        unset($aParam['page']);
        if (! is_array($sReplaceKey)) {
            $sReplaceKey = array($sReplaceKey);
            $sNewValue = array($sNewValue);
        }

        foreach ($sReplaceKey as $k => $v) {
            $aParam = self::makeSearchParam($aParam, $sReplaceKey[$k], $sNewValue[$k], $iSelect);
        }

        return Yaf_G::getUrl($sUri, $aParam) . '.html';
    }

    public static function makeSearchParam($aParam, $sReplaceKey, $sNewValue, $iSelect)
    {
        if ($sNewValue === null) {
            unset($aParam[$sReplaceKey]);
        } else {
            if (! empty($aParam[$sReplaceKey])) {
                if (is_array($aParam[$sReplaceKey]) && in_array($sNewValue, $aParam[$sReplaceKey])) {

                } elseif ($aParam[$sReplaceKey] == $sNewValue) {
                    unset($aParam[$sReplaceKey]);
                }
            }
            if ($iSelect == 0) { // 单选
                if ($aParam[$sReplaceKey] == $sNewValue) {
                    unset($aParam[$sReplaceKey]);
                } else {
                    $aParam[$sReplaceKey] = $sNewValue;
                }
            } else {
                if (empty($aParam[$sReplaceKey])) {
                    $aParam[$sReplaceKey] = array();
                }

                $bFind = false;
                foreach ($aParam[$sReplaceKey] as $k => $v) {
                    if ($v == $sNewValue) {
                        $bFind = true;
                        unset($aParam[$sReplaceKey][$k]);
                    }
                }

                if ($bFind == false) {
                    $aParam[$sReplaceKey][] = $sNewValue;
                }
            }
        }

        return $aParam;
    }

    /**
     * 获取文件服务路径
     *
     * @param string $p_sFileKey
     * @param string $p_sExtension
     * @param int $p_iWidth
     * @param int $p_iHeight
     * @param string $p_sOption
     * @return string
     */
    public static function getDFSDownURL ($p_sFileKey)
    {
        $sViewUrl = Yaf_G::getConf('dfsdown', 'url');
        if (Yaf_G::getEnv() == "dev") {
            $sViewUrl = preg_replace('/\.(\w+)\.dev\./i', '.test.dev.', $sViewUrl);
        }
        if (! $p_sFileKey) {
            return '';
        }
    
        list ($p_sKey, $p_sExt) = explode('.', $p_sFileKey);
        return $sViewUrl . '/' . $p_sKey . '.' . $p_sExt;
    }
    
    /**
     * 获取文件服务路径
     *
     * @param string $p_sFileKey
     * @param string $p_sExtension
     * @param int $p_iWidth
     * @param int $p_iHeight
     * @param string $p_sOption
     * @return string
     */
    public static function getDFSViewURL ($p_sFileKey, $p_iWidth = 900, $p_iHeight = 675, $p_sOption = '', $sBiz = '')
    {
        $sViewUrl = Yaf_G::getConf('dfsview', 'url');
        if (Yaf_G::getEnv() == "dev") {
            $sViewUrl = preg_replace('/\.(\w+)\.dev\./i', '.test.dev.', $sViewUrl);
        }
        if (! $p_sFileKey) {
            return '';
        }
        if ('banner' == $sBiz) {
            $sViewUrl .= '/fjbanner';
        }

        list ($p_sKey, $p_sExt) = explode('.', $p_sFileKey);
        if (0 == $p_iWidth && 0 == $p_iHeight) {
            return $sViewUrl . '/' . $p_sKey . '.' . $p_sExt;
        } else {
            if ('' == $p_sOption) {
                return $sViewUrl . '/' . $p_sKey . '/' . $p_iWidth . 'x' . $p_iHeight . '.' . $p_sExt;
            } else {
                return $sViewUrl . '/' . $p_sKey . '/' . $p_iWidth . 'x' . $p_iHeight . '_' . $p_sOption . '.' . $p_sExt;
            }
        }
    }
    public static function getCricViewURL ($p_sFileKey, $p_iWidth = 0, $p_iHeight = 0, $p_iWaterPiC = 0, $p_iWaterPos = 0, $p_iCutType = 0, $p_Ext = '')
    {
//        $sOption = '';
//
//        if ($p_iCutType == 1) {
//            $sOption = 'c';
//        }
//        if (strpos($p_sFileKey, '.') !== false) {
//            return self::_getDFSViewURLByWH($p_sFileKey, $p_iWidth, $p_iHeight, $p_Ext, $sOption);
//        }

//        $oRedis = Util_Common::getRedis();
//        $sImgKey = $oRedis->hGet('t_img_map', $p_sFileKey);
//        if (! empty($sImgKey)) {
//            return self::_getDFSViewURLByWH($sImgKey, $p_iWidth, $p_iHeight, $p_Ext, $sOption);
//        }
//
//        // 检测图片是否已经同步
//        $dbh = Util_Common::getMySQLDB('dfs_db');
//        $sImgKey = $dbh->getOne('SELECT CONCAT(sImgKey,".",sImgExt) FROM t_img_map WHERE sCricImgKey="' . $p_sFileKey . '"');
//        if (! empty($sImgKey)) {
//            $oRedis->hSet('t_img_map', $p_sFileKey, $sImgKey);
//            return self::_getDFSViewURLByWH($sImgKey, $p_iWidth, $p_iHeight, $p_Ext, $sOption);
//        }
//
//        // 插入到同步队列中
//        $tmpResult =$oRedis->hGet('t_img_queue', $p_sFileKey);
//        if (empty($tmpResult)) {
//            $iNowTime = time();
//            $dbh->query("REPLACE INTO t_img_queue(sCricImgKey,sCricImgExt,iStatus,iFailNum,iCreateTime,iUpdateTime) VALUES('$p_sFileKey', '',1,0,$iNowTime,$iNowTime)");
//            $oRedis->hSet('t_img_queue', $p_sFileKey, 1);
//        }

        $iWhich = hexdec(substr($p_sFileKey,-1));
        $sServers = array('',2,3,4,5,6,7,8,9,10,11,12,13,14,15,16);
        return 'http://get.file' . $sServers[$iWhich] . '.dc.cric.com/'
        . $p_sFileKey . '_' . $p_iWidth . 'X' . $p_iHeight . '_'
        . $p_iWaterPiC . '_' . $p_iWaterPos . '_' . $p_iCutType
        . '.jpg';
    }
}