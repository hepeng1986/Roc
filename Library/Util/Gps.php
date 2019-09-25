<?php

/**
 * 地图位置相关的使用
 * @author xiejinci
 *
 */

class Util_Gps
{
    /**
     * 取得高德GPS
     * @param string $sCity
     * @param string $sAddr
     * @return multitype:number
     */
    public static function getAmapGps ($sCity, $sAddr)
    {
        $opts = array(
            'http' => array(
                'method' => "GET",
                'timeout' => 10
            )
        );
        $context = stream_context_create($opts);
        $url = "http://restapi.amap.com/v3/place/text?keywords=" . urlencode($sAddr) . "&key=8325164e247e15eea68b59e89200988b&city=" . urlencode($sCity);
        $aRet = file_get_contents($url, false, $context);
        $aRet = json_decode($aRet, true);
        $sRet = isset($aRet['pois']['0']['location']) ? $aRet['pois']['0']['location'] : '0,0';
        $aTmp = explode(',', $sRet);
        return array(
            'lng' => $aTmp[0],
            'lat' => $aTmp[1]
        );
    }

    /**
     * 取得百度GPS
     * @param string $sCity
     * @param string $sAddr
     * @return multitype:number
     */
    public static function getBaiduGps ($sCity, $sAddr)
    {
        $opts = array(
            'http' => array(
                'method' => "GET",
                'timeout' => 10
            )
        );
        $context = stream_context_create($opts);
        $url = "http://api.map.baidu.com/geocoder?address=" . urlencode($sAddr) . "&output=json&key=37492c0ee6f924cb5e934fa08c6b1676&city=" . urlencode($sCity);
        $aRet = file_get_contents($url, false, $context);
        $aRet = json_decode($aRet, true);
        return isset($aRet['result']['location']) ? $aRet['result']['location'] : array(
            'lng' => 0,
            'lat' => 0
        );
    }
    
    public static function gcj2bd($gcjLat, $gcjLng)
    {
        $x = $gcjLat;
        $y = $gcjLng;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * M_PI);
        $theta = atan2($y, $x) + 0.000003 * cos($x * M_PI);
        $bdLat = $z * cos($theta) + 0.0065;
        $bdLng = $z * sin($theta) + 0.006;
        return array($bdLat, $bdLng);
    }
    
    public static function bd2gcj($bdLat, $bdLng)
    {
        $x = $bdLat - 0.0065;
        $y = $bdLng - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * M_PI);
        $theta = atan2($y, $x) - 0.000003 * cos($x * M_PI);
        $gcjLat = $z * cos($theta);
        $gcjLng = $z * sin($theta);
        return array($gcjLat, $gcjLng);
    }

    const DEF_PI = 3.14159265359; // PI
    const DEF_2PI= 6.28318530712; // 2*PI
    const DEF_PI180= 0.01745329252; // PI/180.0
    const DEF_R =6370693.5;

    /**
     * 百度地图两点之间距离
     */
    public static function getBDShortDistance($lon1, $lat1, $lon2, $lat2){
        // 角度转换为弧度
        $ew1 = $lon1 * self::DEF_PI180;
        $ns1 = $lat1 * self::DEF_PI180;
        $ew2 = $lon2 * self::DEF_PI180;
        $ns2 = $lat2 * self::DEF_PI180;
        // 经度差
        $dew = $ew1 - $ew2;
        // 若跨东经和西经180 度，进行调整
        if ($dew > self::DEF_PI)
            $dew = self::DEF_2PI - $dew;
        else if ($dew < -self::DEF_PI)
            $dew = self::DEF_2PI + $dew;
        $dx = self::DEF_R * cos($ns1) * $dew; // 东西方向长度(在纬度圈上的投影长度)
        $dy = self::DEF_R * ($ns1 - $ns2); // 南北方向长度(在经度圈上的投影长度)
        // 勾股定理求斜边长
        $distance = sqrt($dx * $dx + $dy * $dy);
        return $distance;
    }

    /**
     * 百度地图两点之间距离
     */
    public static function getBDLongDistance($lon1, $lat1, $lon2, $lat2){
        // 角度转换为弧度
        $ew1 = $lon1 * self::DEF_PI180;
        $ns1 = $lat1 * self::DEF_PI180;
        $ew2 = $lon2 * self::DEF_PI180;
        $ns2 = $lat2 * self::DEF_PI180;
        // 求大圆劣弧与球心所夹的角(弧度)
        $distance = sin($ns1) * sin($ns2) + cos($ns1) * cos($ns2) * cos($ew1 - $ew2);
        // 调整到[-1..1]范围内，避免溢出
        if ($distance > 1.0)
            $distance = 1.0;
        else if ($distance < -1.0)
            $distance = -1.0;
        // 求大圆劣弧长度
        $distance = self::DEF_R * acos($distance);
        return $distance;
    }
    
    /**
     * 判断点是否在多边形里
     * @param unknown $coordArray
     * @param unknown $point
     * @return boolean
     */
    public static function isPointInPolygon( $coordArray, $point)
    {
        if(!is_array($coordArray)||!is_array($point)) return false;
        $maxY = $maxX = 0;
        $minY = $minX = 9999;
        foreach ($coordArray as $item){
            if($item['lng']>$maxX) $maxX = $item['lng'];
            if($item['lng'] < $minX) $minX = $item['lng'];
            if($item['lat']>$maxY) $maxY = $item['lat'];
            if($item['lat'] < $minY) $minY = $item['lat'];
            $vertx[] = $item['lng'];
            $verty[] = $item['lat'];
        }
        if ($point['lng'] < $minX || $point['lng'] > $maxX || $point['lat'] < $minY || $point['lat'] > $maxY) {
            return false;
        }
    
        $c = false;
        $nvert=count($coordArray);
        $testx=$point['lng'];
        $testy=$point['lat'];
        for ($i = 0, $j = $nvert-1; $i < $nvert; $j = $i++) {
            if ( ( ($verty[$i]>$testy) != ($verty[$j]>$testy) )
            && ($testx < ($vertx[$j]-$vertx[$i]) * ($testy-$verty[$i]) / ($verty[$j]-$verty[$i]) + $vertx[$i]) )
                $c = !$c;
        }
        return $c;
    }
}