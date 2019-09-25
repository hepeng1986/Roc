<?php

/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/12/24
 * Time: 14:10
 * 全局公共函数
 */
class Common_Fun
{
    /* 列出两个时间段的月份
     * @param  $startMonth  开始月份
     * @param  $endMonth  结束月份
     * @param  $endMonth  返回格式
     * @retrun   array 月份数组
    */
    public static function calculationMonth($startMonth, $endMonth, $format = 'Y-m')
    {
        $monarr = [];
        if (!preg_match('/^\d{4}[\-](0?[1-9]|1[012])$/', $startMonth) || !preg_match('/^\d{4}[\-](0?[1-9]|1[012])$/', $endMonth)) {
            return $monarr;
        }
        $time1 = strtotime($startMonth);
        $time2 = strtotime($endMonth);
        if (empty($time1) || empty($time2) || $time1 > $time2) { //时间有误
            return $monarr;
        }

//        $time1 = strtotime(date("Y-m-01", $time1));
//        $time2 = strtotime(date("Y-m-01", $time2));

        while ($time1 <= $time2) {
            $monarr[] = date($format, $time1); // 取得递增月;
            $time1 = strtotime('+1 month', $time1);
        }
        return $monarr;
    }

    /**
     * @param $from
     * @param $to
     * @param string $format
     * @param int $type 1-按月, 2-按年
     * @return array
     */
    public static function calcTimeRange($from, $to, $type = 1, $format = 'Y-m')
    {
        $rangeArr = [];
        $time1 = $type == 2 ? intval($from) : strtotime($from);
        $time2 = $type == 2 ? intval($to) : strtotime($to);
        if (empty($time1) || empty($time2) || $time1 > $time2) {
            return $rangeArr;
        }

        while ($time1 <= $time2) {
            switch ($type) {
                case 2: // 年:  [2018, 2019]
                    $rangeArr[] = $time1 . '';
                    $time1++;
                    break;
                case 3: //季度 2018-3,2018-6
                    $rangeArr[] = date($format, $time1);
                    $time1 = strtotime('+3 month', $time1);
                    break;
                case 1: // 月
                default:
                    $rangeArr[] = date($format, $time1);
                    $time1 = strtotime('+1 month', $time1);
                    break;
            }
        }
        return $rangeArr;
    }

    /**
     * 获取一颗树结构的数组
     * @param $aTree 树结构的数据
     * @param $iLevel 第几级子节点 从1开始索引
     * @param $iIndex 初始节点 一般不填
     * @return array
     */
    public static function getLeafNode($aTree, $iLevel, $iIndex = 1)
    {
        $ret = [];
        if (empty($aTree)) {
            return $ret;
        }
        //搜索子节点
        foreach ($aTree as $tk => $tv) {
            //如果还没达到层级，继续往下
            if ($iIndex < $iLevel) {
                $temp = self::getLeafNode($tv['children'], $iLevel, $iIndex + 1);
                $ret = array_merge($ret, $temp);
            } else {
                $ret[] = $tv['value'];
            }

        }
        return $ret;
    }

    /**
     * 中心坐标点和对角线的距离得到正方形的8个边界点
     * @param $lat 纬度
     * @param $lon 经度
     * @param $raidus 对角线距离 单位米
     * @return array
     */
    public static function getSquareAround($lat, $lon, $raidus)
    {
        $PI = 3.14159265;
        $latitude = $lat;
        $longitude = $lon;
        $degree = (24901 * 1609) / 360.0;
        $dpmLat = 1 / $degree;

        //1,得到对角线距离的四个边界坐标
        $raidusMile1 = $raidus;
        $radiusLat = $dpmLat * $raidusMile1;
        $minLat1 = $latitude - $radiusLat;
        $maxLat1 = $latitude + $radiusLat;

        $mpdLng = $degree * cos($latitude * ($PI / 180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng * $raidusMile1;
        $minLng1 = $longitude - $radiusLng;
        $maxLng1 = $longitude + $radiusLng;

        //2,求中心点到边的距离
        $raidusMile2 = sqrt(pow($raidus, 2));

        //3,得到边长距离的四个边界坐标
        $radiusLat = $dpmLat * $raidusMile2;
        $minLat2 = $latitude - $radiusLat;
        $maxLat2 = $latitude + $radiusLat;

        $mpdLng = $degree * cos($latitude * ($PI / 180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng * $raidusMile2;
        $minLng2 = $longitude - $radiusLng;
        $maxLng2 = $longitude + $radiusLng;

        //4,组合成边界
        $aa = ["116.53007, 35.421437;116.546742, 35.428966;116.642466, 35.430143;116.633267, 35.388015;116.627805, 35.389898;116.600784, 35.392959;116.588999, 35.383542;116.566002, 35.374596;116.561978, 35.394371;116.557091, 35.405904"];
        $sBoundary = $lon . ',' . $minLat1 . ';';
        $sBoundary .= $lon . ',' . $maxLat1 . ';';
        $sBoundary .= $lon . ',' . $minLat2 . ';';
        $sBoundary .= $lon . ',' . $maxLat2 . ';';
        $sBoundary .= $minLng1 . ',' . $lat . ';';
        $sBoundary .= $maxLng1 . ',' . $lat . ';';
        $sBoundary .= $minLng2 . ',' . $lat . ';';
        $sBoundary .= $maxLng2 . ',' . $lat;
        return '["' . $sBoundary . '"]';
    }

    /**
     * 获取中心坐标点某半径的距离
     * @param $lat 纬度
     * @param $lon 经度
     * @param $raidus 半径 单位米
     * @return array
     */
    public static function getAround($lat, $lon, $raidus)
    {
        $PI = 3.14159265;
        $latitude = $lat;
        $longitude = $lon;
        $degree = (24901 * 1609) / 360.0;
        $raidusMile = $raidus;
        $dpmLat = 1 / $degree;
        $radiusLat = $dpmLat * $raidusMile;
        $minLat = $latitude - $radiusLat;
        $maxLat = $latitude + $radiusLat;

        $mpdLng = $degree * cos($latitude * ($PI / 180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng * $raidusMile;
        $minLng = $longitude - $radiusLng;
        $maxLng = $longitude + $radiusLng;
        return array(
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'minLng' => $minLng,
            'maxLng' => $maxLng
        );
    }

    /**
     * 中断报错，返回json
     * @param $mMsg
     * @param $bRet
     * @param string $sRedirectUrl
     */
    public static function showError($mMsg, $bRet, $sRedirectUrl = '')
    {
        $aData = array(
            'data' => $mMsg,
            'status' => $bRet
        );
        $sDebug = Util_Common::getDebugData();
        if ($sDebug) {
            $aData['debug'] = $sDebug;
        }
        if (!empty($sRedirectUrl)) {
            $aData['url'] = $sRedirectUrl;
        }
        echo json_encode($aData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        die;
    }

    /**
     * 中断报错，返回javascript
     * @param $mMsg
     * @param string $sRedirectUrl
     */
    public static function showAlert($mMsg, $sRedirectUrl = '')
    {
        $sEchoContent = '<script type="text/javascript">';
        $sEchoContent .= 'alert("' . $mMsg . '");';
        if (!empty($sRedirectUrl)) {
            $sEchoContent .= 'window.location.href="' . $sRedirectUrl . '";';
        }
        $sEchoContent .= '</script>';
        echo $sEchoContent;
        die;
    }

    /**
     * 未知字段，未知条件的查询sql组装
     * @param $aFields 多维数组，格式：表=>字段名=
     * @param $aOptions 多维数组，格式：表=>字段名=>属性
     */
    public static function initSql($aFields, $aOptions, $iPage = 1, $iPageSize = 20, $sOrder = '')
    {
        if (self::arrayLevel($aFields) != 2) {
            self::showError('字段必须是二维数组', false);
        }
        if (self::arrayLevel($aOptions) != 4) {
            self::showError('字段必须是四维数组', false);
        }
        $aFieldsKeys = array_keys($aFields);
        $aOptionsKeys = array_keys($aOptions);
        if ($aFieldsKeys != $aOptionsKeys) {
            self::showError('字段和条件用到的表不一致', false);//条件包含字段表也不行，消耗资源
        }
        unset($aOptionsKeys);
        //1,组装所有的表
        $sJoinTables = self::_initTables($aFieldsKeys);

        //2,组装字段列表
        $sFields = self::_initFields($aFields);

        //3,组装条件列表s
        $sOptions = self::_initOptions($aOptions);

        //4,组装页数
        $sLimit = ($iPage - 1) . ',' . $iPageSize;

        //5,组装排序
        $sOrder = !empty($sOrder) ? ' ORDER BY ' . $sOrder : '';

        //4,组装成完成sql
        $sSql = 'SELECT ' . $sFields . ' FROM ' . $sJoinTables . ' WHERE ' . $sOptions . $sOrder . ' LIMIT ' . $sLimit;

        return $sSql;
    }

    /**
     * 组装所有的表
     * @param $aFieldsKeys
     * @return string
     */
    private static function _initTables($aFieldsKeys)
    {
        $sMasterTable = $aFieldsKeys[0];
        $sJoinTable = '';
        if (count($aFieldsKeys) > 1) {
            $aTablerelation = Yaf_G::getConf('tablerelation', 'search', 'search');
            if (!isset($aTablerelation[$sMasterTable])) {
                self::showError('该查询为多表查询,请先配置主表' . $sMasterTable . '的对应关系', false);
            }
            $aTmp = [];
            foreach ($aFieldsKeys as $key => $value) {
                if ($key == 0) {
                    continue;
                }
                if (!isset($aTablerelation[$sMasterTable][$value])) {
                    self::showError('请先配置主表' . $sMasterTable . '和从表' . $value . '的对应关系', false);
                }
                $aTmp = array_merge($aTmp, $aTablerelation[$sMasterTable][$value]);
            }
            $aJoinTable = array_values(array_unique($aTmp));
            $sJoinTable = implode(' ', $aJoinTable);
        }
        $sJoinTables = $sMasterTable . ' ' . $sJoinTable;
        return $sJoinTables;
    }

    /**
     * 组装所有的字段
     * @param $aFields
     * @return string
     */
    private static function _initFields($aFields)
    {
        $aTmp = [];
        $aFieldsName = [];
        foreach ($aFields as $key => $value) {
            foreach ($value as $k => $v) {
                //$aTmp[] = $key . '.' . $k . ' AS ' . '"' . $v . '"';
                $aTmp[] = $key . '.' . $k;
                $aFieldsName[] = $v;
            }
        }
        $sFields = trim(implode(',', $aTmp), ',');
        return $sFields;
    }

    /**
     * 组装所有的搜索选项
     * @param $aFields
     * @return string
     */
    private static function _initOptions($aOptions)
    {
        $aOpTmp = [];
        foreach ($aOptions as $m => $n) {
            foreach ($n as $key => $value) {
                if (empty($value['mValue'])) {
                    continue;
                }
                switch ($value['sInputType']) {
                    case 'text':
                        if ($value['sSearchType'] == 'or') {
                            $aTmp = [];
                            foreach ($value['sFieldList'] as $k => $val) {
                                $aTmp[] = $m . '.' . $val . ' LIKE "%' . self::quote($value['mValue']) . '%"';
                            }
                            $aOpTmp[] = '(' . trim(implode(' or ', $aTmp), 'or') . ')';
                        } else {
                            $aOpTmp[] = $m . '.' . $key . ' LIKE "%' . self::quote($value['mValue']) . '%"';
                        }
                        break;
                    case 'checkbox':
                    case 'select':
                        if (is_array($value['mValue'])) {
                            $aOpTmp[] = $m . '.' . $key . ' IN ("' . implode('","', self::quote($value['mValue'])) . '")';
                        } elseif (is_string($value['mValue'])) {
                            $aOpTmp[] = $m . '.' . $key . '=' . self::quote($value['mValue']);
                        } else {
                            $aOpTmp[] = $m . '.' . $key . 'LIKE ' . self::quote($value['mValue']);
                        }
                        break;
                    case 'date':
                        $aOpTmp[] = $m . '.' . $key . '="' . date('Y-m-d', strtotime(self::quote($value['mValue']))) . '"';
                        break;
                    case 'number':
                        $aOpTmp[] = $m . '.' . $key . '=' . self::quote($value['mValue']);
                        break;
                }
            }
        }
        if (!empty($aOpTmp)) {
            $sOption = trim(implode(' AND ', $aOpTmp), 'AND');
        } else {
            $sOption = 1;
        }
        return $sOption;
    }

    /**
     * 判断是数组的维度
     * @param $arr
     * @return mixed
     */
    public static function arrayLevel($arr)
    {
        $al = [];
        self::_aL($arr, $al);
        return max($al);
    }

    private static function _aL($arr, &$al, $level = 0)
    {
        if (is_array($arr)) {
            $level++;
            $al[] = $level;
            foreach ($arr as $v) {
                self::_aL($v, $al, $level);
            }
        }
    }

    /**
     * 数据过滤
     * @param $value 要过滤的值
     * @return string
     */
    public static function quote($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        if (is_array($value)) {
            foreach ($value as &$val) {
                if (is_array($val)) {
                    self::quote($val);
                } elseif (is_int($val)) {
                    $val = $val;
                } elseif (is_float($val)) {
                    $val = sprintf('%F', $val);
                } else {
                    $val = addcslashes($val, "\000\n\r\\'\"\032");
                }
            }
            return $value;
        }
        return addcslashes($value, "\000\n\r\\'\"\032");
    }

    /**
     * 转换分段信息（总价段单位为 '万元'）
     * @param array $aStep 传入数组如 [50,60,70]
     * @param int $times 倍数, 比如150万元, times=10000
     * @return array
     *    [
     *      0 => 100以下
     *      1 => 100-200
     *      2 => 200-300
     *      3 => 300以上
     *    ]
     * @throws Exception
     */
    public static function getSectionArray($aStep)
    {
        if (empty($aStep)) {
            throw new Exception('分段数组不能为空');
        }
        $cnt = count($aStep);
        if ($cnt <= 1) {
            throw new Exception('分段数应该大于1个');
        }
        sort($aStep);

        $_aStep = [];
        foreach ($aStep as $k => $item) {
            $_sCur = $item;
            if ($k == 0) {
                $_aStep["[0,{$_sCur}]"] = $_sCur . '以下';
            }

            if ($k == $cnt - 1) {
                $_aStep["[{$_sCur},0]"] = $_sCur . '以上';
            } else {
                $_sNext = $aStep[$k + 1];
                $_aStep["[{$_sCur},{$_sNext}]"] = $_sCur . '-' . $_sNext;
            }
        }

        return $_aStep;
    }

    /**
     * 转换分段信息（总价段单位为 '万元'）
     * @param array $aStep 传入数组如 [50,60,70]
     * @param string $sField
     * @param bool $withSort 是否需要用于排序, true-结果为 '2,80-100', false-结果为 '80-100'
     * @param string $equal 是否需要等于开始区间
     * @return string 返回结果如下：其中 '2,80-100' 中逗号及其前面的数字为了排序用, 取出结果集后自行处理去掉即可
     *   (CASE
     *       WHEN {$field} < 60 THEN '0,60以下'
     *       WHEN {$field} BETWEEN 60 AND 80 THEN '1,60-80'
     *       WHEN {$field} BETWEEN 80 AND 100 THEN '2,80-100'
     *       ELSE '3,100以上'
     *   END)
     * @throws Exception
     */
    public static function getSectionCase($aStep, $sField, $withSort = true,$equal='')
    {
        if (empty($aStep)) {
            throw new Exception('分段数组不能为空');
        }
        $cnt = count($aStep);
        if ($cnt <= 1) {
            throw new Exception('分段数应该大于1个');
        }
        sort($aStep);

        // 用于sql结果集排序, 取出结果后自行移除
        $_order = 0;
        $sStepCase = " (case ";
        foreach ($aStep as $k => $v) {
            $_cur = $v;
            if ($k == 0) {
                // WHEN {$field} < 60 THEN '0,60以下'
                if ($withSort) {
                    $sStepCase .= " WHEN {$sField} < {$_cur} THEN '{$_order},{$_cur}以下' ";
                } else {
                    $sStepCase .= " WHEN {$sField} <{$equal} {$_cur} THEN '{$_cur}以下' ";
                }
                $_order++;
            }
            if ($k < $cnt - 1) {
                $_next = $aStep[$k + 1];
                // WHEN {$field} BETWEEN 60 AND 80 THEN '1,60-80'
                if ($withSort) {
                    $sStepCase .= " WHEN {$sField} BETWEEN {$_cur} AND {$_next} THEN '{$_order},{$_cur}-{$_next}' ";
                } else {
                    $sStepCase .= " WHEN {$sField} BETWEEN {$_cur} AND {$_next} THEN '{$_cur}-{$_next}' ";
                }
                $_order++;
            } else {
                // ELSE '3,100以上'
                if ($withSort) {
                    $sStepCase .= " ELSE '{$_order},{$_cur}以上' ";
                } else {
                    $sStepCase .= " ELSE '{$_cur}以上' ";
                }
            }
        }
        $sStepCase .= " END) ";

        return $sStepCase;
    }

    //过滤特殊字符 删除空格与回车,去除特殊字符
    function reeChar($strParam)
    {
        $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\★\▼|\=|\\\|\|/";
        $qian = array(" ", "　", "\t", "\n", "\r");
        $cv = preg_replace($regex, "", $strParam);
        return str_replace($qian, '', $cv);
    }

    //替换微信名中的表情符号
    function filterEmoji($text, $replaceTo = 'x')
    {
        $clean_text = "";
        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, $replaceTo, $text);
        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, $replaceTo, $clean_text);
        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, $replaceTo, $clean_text);
        // Match Miscellaneous Symbols
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, $replaceTo, $clean_text);
        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text = preg_replace($regexDingbats, $replaceTo, $clean_text);
        // Match Dingbats
        //$regexDingbats = '/[^\x{4e00}-\x{9fa5}]/iu';
        return $clean_text;
    }

    //只需要中英文数字
    function filterSymbol($text, $replaceTo = 'x')
    {
        $clean_text = "";
        // Match Emoticons
        $regexEmoticons = '/[^\x{4e00}-\x{9fa5}a-zA-Z0-9]/iu';
        $clean_text = preg_replace($regexEmoticons, $replaceTo, $text);
        return $clean_text;
    }

    /**
     * http://get.file.dc.cric.com/{PicCode}_{Width}X{Height}_{water_pic}_{water_position}_{cut_type}.jpg
     * http://get.file.dc.cric.com/CRIC61223f4d9e8e429b8a77cc8385bc3d11_600X600_8_7_5.jpg
     * PicCode: 如 CRIC61223f4d9e8e429b8a77cc8385bc3d11 为图片文件ID
     * Width、Height：分别为图片显示的宽高, 宽高之间是大写字母X, 如果宽为1表示高固定宽自适应，如果高为1表示宽固定高自适应，
     *     如果宽高都为1表示原图尺寸，此时如果没有水印不给显示该图片
     * water_pic：水印图片, 即写的时候传入参数里的water_pic
     * water_position: 水印图片位置
     * cut_type: 裁剪方式
     *
     * @param string $sPicCode 如 CRIC61223f4d9e8e429b8a77cc8385bc3d11 为图片文件ID
     * @param int $width
     * @param int $height
     * @param int $water_pic 水印图片, 即写的时候传入参数里的water_pic
     * @param int $water_position 水印图片位置
     * @param int $cut_type 裁剪方式
     * @return string
     */

    public static function getImgUrlByPicCode($sPicCode, $width = 600, $height = 1, $water_pic = 8, $water_position = 5, $cut_type = 5)
    {
        if (!$sPicCode) {
            return '';
        }
        return "http://get.file.dc.cric.com/{$sPicCode}_{$width}X{$height}_{$water_pic}_{$water_position}_{$cut_type}.jpg";
    }


    /**
     * 使用指定下标对数据进行重建索引
     * @param $array
     * @param $key
     *
     * @return array
     */
    public static function mapByKey($array, $key)
    {
        $map = [];
        foreach ($array as $item) {
            if (!isset($item[$key]) || (!is_string($item[$key]) && !is_numeric($item[$key]))) {
                continue;
            }
            $map[$item[$key]] = $item;
        }

        return $map;
    }

    /**
     * 使用指定下标对数据进行重建分组索引
     * @param $array
     * @param $key
     *
     * @return array
     */
    public static function groupByKey($array, $key)
    {
        $map = [];
        foreach ($array as $item) {
            if (!isset($item[$key]) || (!is_string($item[$key]) && !is_numeric($item[$key]))) {
                continue;
            }
            $map[$item[$key]][] = $item;
        }

        return $map;
    }

    public static function getRoomUsage()
    {
        $aRet = [];
        $aConf = Yaf_G::getConf('roomUsage', "loupan", "enumeration");
        foreach ($aConf as $k => $item) {
            $aRet[] = str_replace("'", '', $item);
        }
        return $aRet;
    }

    /**
     * 格式化数字
     * @param        $number
     * @param        $unit
     * @param string $format
     * @param string $default
     *
     * @return string
     */
    public static function numberFormat($number, $unit, $format = 0, $default = '--')
    {
        if (floatval($number)) {
            return $format > 0 ? number_format($number, $format) . $unit : number_format($number) . $unit;
        } else {
            return $default;
        }
    }

    public static function dateFormat($date, $default = '--', $null = "0000-00-00")
    {
        return ($date == $null || $date == '1970-01-01') ? $default : $date;
    }

    /* 格式化时间 yyyy-mm-dd */
    public static function formatDate(&$sStartDate = "", &$sEndDate = "")
    {
        //开始时间
        if (!empty($sStartDate)) {
            //如果是年
            if (preg_match('/^([0-9]{4})$/', $sStartDate)) {
                $sStartDate = $sStartDate . "-01-01";
            } elseif (preg_match('/^([0-9]{4})-(0[0-9]|1[0-2])$/', $sStartDate)) {
                $sStartDate = $sStartDate . "-01";
            }
        }
        //结束时间
        if (!empty($sEndDate)) {
            //如果是年
            if (preg_match('/^([0-9]{4})$/', $sEndDate)) {
                $sEndDate = $sEndDate . "-12-31";
            } elseif (preg_match('/^([0-9]{4})-(0[0-9]|1[0-2])$/', $sEndDate)) {
                $sEndDate = date("Y-m-d", strtotime("+1 month -1 day ", strtotime($sEndDate . "-01")));
            }
        }
    }

    /*
     * 得到枚举类型 key
     */
    public static function getEnum($sKey, $aConf, $default = "")
    {
        return isset($aConf[$sKey]) ? $aConf[$sKey] : $default;
    }

    /*
     * 得到枚举类型反向 通过value找key
     */
    public static function getEnumEx($sValue, $aConf, $default = "")
    {
        $sKey = array_search($sValue, $aConf);
        return ($sKey === false) ? $default : $sKey;
    }

    //返回需要的枚举型销售状态格式
    public static function returnNeedSalesStatusMatch($sTableAs = '')
    {
        $aSalesStatusMatch = Yaf_G::getConf('aSalesStatusMatch', "loupan", "enumeration");
        $sRetrun = "case";
        foreach ($aSalesStatusMatch as $key => $value) {
            $sRetrun .= " when {$sTableAs}sSalesStatus='{$key}' then '{$value}'";
        }
        $sRetrun .= " else {$sTableAs}sSalesStatus end";
        return $sRetrun;
    }

    //两个日期相差多少月份
    public static function getMonthNum($date1, $date2)
    {
        $date1_stamp = strtotime($date1);
        $date2_stamp = strtotime($date2);
        list($date_1['y'], $date_1['m']) = explode("-", date('Y-m', $date1_stamp));
        list($date_2['y'], $date_2['m']) = explode("-", date('Y-m', $date2_stamp));
        return abs($date_1['y'] - $date_2['y']) * 12 + $date_2['m'] - $date_1['m'];
    }

    /**
     * 递归设置默认数据值(config中的所有text匹配aData中对应的字段)
     * @param        $config
     * @param        $aData
     * @param        $sMatchKey 需要匹配的key
     * @return array
     */
    public static function setConfigFormat($config, $aData, $sMatchKey = 'text')
    {
        foreach ($config as $key => &$val) {
            if (is_array($val)) {
                $val = self::setConfigFormat($val, $aData, $sMatchKey);
            } else if ($key == $sMatchKey) {
                $val = !empty($aData[$val]) ? $aData[$val] : '--';
            }
        }
        return $config;
    }

    /**
     * 获取带坐标的元素集过滤不在中心点正方形范围内的元素
     * @param $lat 中心点纬度
     * @param $lon 中心点经度
     * @param $raidus 对角线距离 单位米
     * @param $aList 带坐标的元素集
     * @return array
     */
    public static function getSquareList($lat, $lon, $raidus, &$aList)
    {
        //取得正方形的坐标集
        $sBoundary = Common_Functions::getSquareAround($lat, $lon, $raidus);
        //判断不在方形
        $aTmp = [];
        foreach ($aList as $key => $value) {
            if(isset($value['CenterX']) && isset($value['CenterY'])){
                if (Util_Polygon::polyContainsPoint($sBoundary, $value['CenterX'], $value['CenterY'])) {
                    $aTmp[] = $value;
                }
            } elseif (isset($value['sLng']) && isset($value['sLat'])){
                if (Util_Polygon::polyContainsPoint($sBoundary, $value['sLng'], $value['sLat'])) {
                    $aTmp[] = $value;
                }
            } else {
                $aTmp[] = $value;//返回原元素集
            }
        }
        $aList = $aTmp;
    }
    /*
     * 处理日期formatYmd
     */
    public static function formatYmd($sDate,$bStart = true){
        if($bStart){
            //如果是年
            if(preg_match('/^([0-9]{4})$/', $sDate)){
                $sDate = $sDate."-01-01";
            }elseif(preg_match('/^([0-9]{4})-(0[0-9]|1[0-2])$/', $sDate)){
                $sDate = $sDate."-01";
            }
        }else{
            //如果是年
            if(preg_match('/^([0-9]{4})$/', $sDate)){
                $sDate = $sDate."-12-31";
            }elseif(preg_match('/^([0-9]{4})-(0[0-9]|1[0-2])$/', $sDate)){
                $sDate = date("Y-m-d",strtotime("+1 month -1 day ",strtotime($sDate."-01")));
            }
        }
        return $sDate;
    }
    /*
     * 处理日期formatYmd
     */
    public static function formatYmonth($sDate,$bStart = true){
        if($bStart){
            //如果是年
            if(preg_match('/^([0-9]{4})$/', $sDate)){
                $sDate = $sDate."-01";
            }elseif (preg_match('/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[01])$/',$sDate)){
                $sDate = substr($sDate,0,7);
            }
        }else{
            //如果是年
            if(preg_match('/^([0-9]{4})$/', $sDate)){
                $sDate = $sDate."-12";
            }elseif (preg_match('/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[01])$/',$sDate)){
                $sDate = substr($sDate,0,7);
            }
        }
        return $sDate;
    }
    /* 自定义楼盘 */
    public static function dealArr($arr, $step = 5)
    {
        if (!is_array($arr)) {
            return [];
        }
        $arr = array_map('intval', $arr);
        $arr = array_filter($arr, function ($item) use ($step) {
            return $item && ($item % $step == 0);
        });
        sort($arr);
        $arr = array_slice($arr, 0, 5);
        return $arr;
    }
    /*
     * 处理城市
     */
    public static function dealCityParam($aParam,$sDefault)
    {
        if(empty($aParam["sCityID"]) && empty($aParam["sRegionID"]) && empty($aParam["sBlockID"])){
            $aParam["sCityID"] = $sDefault;
        }
        //处理省市区
        if(!empty($aParam['sBlockID'])){
            $aParam['sCityID'] = "";
            $aParam['sRegionID'] = "";
        }elseif (!empty($aParam['sRegionID'])){
            $aParam['sCityID'] = "";
            $aParam['sBlockID'] = "";
        }else{
            $aParam['sBlockID'] = "";
            $aParam['sRegionID'] = "";
        }
        return $aParam;
    }
    /*
     * 计算经纬度指点的距离
     */
    public static function calcDistance($aLng,$aLat,$bLng,$bLat){
        return  round(6378.138 * 2 * asin(sqrt(pow( sin( ( $aLat * pi( ) / 180- $bLat * pi( ) / 180 ) / 2 ), 2 ) + cos( $aLat * pi( ) / 180 ) * cos( $bLat * pi( ) / 180 ) * pow( sin( ( $aLng * pi( ) / 180- $bLng * pi( ) / 180 ) / 2 ), 2 ))) * 1000);
    }
}
