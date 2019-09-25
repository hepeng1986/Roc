<?php
class Util_Format
{
    public static function ch2num ($str)
    {
        // 单位数组用于循环遍历，单位顺序从大到小
        $c = [
            '亿' => 100000000,
            '万' => 10000
        ];
    
        // 中文替换数字规则，零没什么卵用；所以去掉
        $b = [
            '一' => 1,
            '二' => 2,
            '两' => 2,
            '三' => 3,
            '四' => 4,
            '五' => 5,
            '六' => 6,
            '七' => 7,
            '八' => 8,
            '九' => 9,
            '零' => ''
        ];
    
        // 替换数字
        $str = str_replace(array_keys($b), array_values($b), $str);
        // 如果字符串以十开头，前边加1
        if (mb_strpos($str, '十', 0, 'utf-8') === 0) {
            $str = '1' . $str;
        }
        // 初始化一个数组
        $arr[] = array(
            'str' => $str, // 字符串
            'unit' => 1 // 单位
        );
    
        // 将字符串按单位切分
        foreach ($c as $key => $value) {
            $brr = [];
            foreach ($arr as $item) {
                if (strpos($item['str'], $key)) {
                    $sun = explode($key, $item['str'], 2);
                    $brr[] = [
                    'str' => $sun[0],
                    'unit' => $value
                    ];
                    $brr[] = [
                    'str' => $sun[1],
                    'unit' => $item['unit']
                    ];
                } else {
                    $brr[] = $item;
                }
            }
            $arr = $brr;
        }
    
        // 遍历求和
        $sum = 0;
        foreach ($arr as $item) {
            $sum += self::getNum($item['str'], $item['unit']);
        }
        return $sum;
    }
    
    // 将分组后的字符串转化成数字，并乘以单位
    public static function getNum ($str, $st)
    {
        // 倍数
        $a = [
            '千' => 1000,
            '百' => 100,
            '十' => 10
        ];
        // 开始值
        $num = 0;
        // 当前值所在位数
        $step = 1;
        // 单位
        $un = 1;
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        while (count($arr)) {
            $m = array_pop($arr);
            // 如果是位数;更新倍数
            if (! empty($a[$m])) {
                $step = $a[$m];
            }
            if (is_numeric($m)) {
                $num += $m * $step;
            }
        }
        return $num * $st;
    }
    /*
     * 格式化返回值，使用千分位格式化
     */
    public static function formatRet($type,$value,$default="-",$unit="",$multiple=1,$decimal=0){
        //无值
        if(empty($value)){
            return $default;
        }
        if($type == "string"){
            return $value.$unit;
        }elseif ($type == "json"){
            //json数组格式，连起来返回
            $v = json_decode($value,true);
            if(!empty($v)){
                return implode(" ",$v);
            }else{
                return $default;
            }
        }elseif($type == "time"){
            return $value == "0000-00-00"?$default:$value;
        }elseif($type == "number" && is_numeric($value)){
            if(!floatval($value)) {
                return $default;
            }
            $decimal = !empty($decimal)?$decimal:0;
            $multiple = !empty($multiple)?$multiple:1;
            //无倍数，直接格式化
            if($multiple == 1){
                return number_format($value,$decimal).$unit;
            }else{
                //乘以倍数
                $num = bcmul($value,$multiple,$decimal+1);
                if(!floatval($num)) {
                    return $default;
                }
                return number_format($num,$decimal).$unit;
            }
        }
        return $value;
    }
    //新增三个格式化通用函数

    /**
     * @param $value
     * @param string $unit 末尾单位
     * @param int $decimal 保留几位小数点
     * @param int $multiple 乘以倍数
     * @param string $default 为空时默认值
     * @return string
     */
    public static function formatNumber($value, $unit="", $decimal=0, $multiple=1, $default="-" )
    {
        //非数字，及0.00
        if(!is_numeric($value) || !floatval($value)) {
            return $default;
        }
        $result = 0;
        $decimal = !empty($decimal)?$decimal:0;
        $multiple = !empty($multiple)?$multiple:1;
        //无倍数，直接格式化
        if($multiple == 1){
            $result =  number_format($value,$decimal);
        }else{
            //乘以倍数
            $num = bcmul($value,$multiple,$decimal+1);
            $result = number_format($num,$decimal);
        }
        //格式化后仍然为0.0,设为默认值
        $result = floatval($result)?$result.$unit:$default;
        return $result;
    }
    /** 格式化时如果为0，则保留0  20190917 计算溢价率
     * @param $value
     * @param string $unit 末尾单位
     * @param int $decimal 保留几位小数点
     * @param int $multiple 乘以倍数
     * @param string $default 为空时默认值
     * @return string
     */
    public static function formatNumberZero($value, $unit="", $decimal=0, $multiple=1, $default="-" )
    {
        if(!is_numeric($value)){
            return $default;
        }
        $result = 0;
        $decimal = !empty($decimal)?$decimal:0;
        $multiple = !empty($multiple)?$multiple:1;
        //无倍数，直接格式化
        if($multiple == 1){
            $result =  number_format($value,$decimal);
        }else{
            //乘以倍数
            $num = bcmul($value,$multiple,$decimal+1);
            $result = number_format($num,$decimal);
        }
        return $result.$unit;
    }
    /**
     * 格式化日期
     * @param $string
     * @return string
     */
    public static function formatDate($value,$default="-")
    {
        //非法值
        if(empty($value) || $value == "1970-01-01" || $value == "0000-00-00"){
            return $default;
        }
        return $value;
    }
    /**
     * 格式化日期
     * @param $string
     * @return string
     */
    public static function formatString($value,$unit="",$default="-")
    {
        return !empty($value)?$value.$unit:$default;
    }
}
