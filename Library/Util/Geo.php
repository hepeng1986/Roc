<?php
class Util_Geo {
    public static $pi = 3.1415926535897932384626;
    public static $x_pi = 52.35987755983; //3.14159265358979324 * 3000.0 / 180.0;
    public static $a = 6378245.0;
    public static $ee = 0.00669342162296594323;

    public static function transformLat($x, $y) {
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * self::$pi) + 20.0 * sin(2.0 * $x * self::$pi)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * self::$pi) + 40.0 * sin($y / 3.0 * self::$pi)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * self::$pi) + 320 * sin($y * self::$pi / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    public static function transformLon($x, $y) {
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * self::$pi) + 20.0 * sin(2.0 * $x * self::$pi)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * self::$pi) + 40.0 * sin($x / 3.0 * self::$pi)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * self::$pi) + 300.0 * sin($x / 30.0 * self::$pi)) * 2.0 / 3.0;
        return $ret;
    }
    
    public static function transform($lat, $lon) {
        if (self::outOfChina($lat, $lon)) {
            return [$lat,$lon];
        }
        $dLat = self::transformLat($lon - 105.0, $lat - 35.0);
        $dLon = self::transformLon($lon - 105.0, $lat - 35.0);
        $radLat = $lat / 180.0 * self::$pi;
        $magic = sin($radLat);
        $magic = 1 - self::$ee * $magic * $magic;
        $sqrtMagic = sqrt($magic);
        $dLat = (dLat * 180.0) / ((self::$a * (1 - self::$ee)) / ($magic * $sqrtMagic) * self::$pi);
        $dLon = (dLon * 180.0) / (self::$a / $sqrtMagic * cos($radLat) * self::$pi);
        $mgLat = $lat + $dLat;
        $mgLon = $lon + $dLon;
        return [$mgLat, $mgLon];
    }
    
    public static function outOfChina($lat, $lon) {
        if ($lon < 72.004 || $lon > 137.8347)
            return true;
        if ($lat < 0.8293 || $lat > 55.8271)
            return true;
        return false;
    }
    
    /**
     * 84 to 火星坐标系 (GCJ-02) World Geodetic System ==> Mars Geodetic System
     *
     * @param $lat
     * @param $lon
     * @return
     */
    public static function gps84_To_Gcj02($lat, $lon) {
        if (self::outOfChina($lat, $lon)) {
            return [$lat,$lon];
        }
        $dLat = self::transformLat($lon - 105.0, $lat - 35.0);
        $dLon = self::transformLon($lon - 105.0, $lat - 35.0);
        $radLat = $lat / 180.0 * self::$pi;
        $magic = sin($radLat);
        $magic = 1 - self::$ee * magic * magic;
        $sqrtMagic = sqrt($magic);
        $dLat = ($dLat * 180.0) / ((self::$a * (1 - self::$ee)) / ($magic * $sqrtMagic) * self::$pi);
        $dLon = ($dLon * 180.0) / (self::$a / $sqrtMagic * cos($radLat) * self::$pi);
        $mgLat = $lat + $dLat;
        $mgLon = $lon + $dLon;
        return [$mgLat, $mgLon];
    }

    /**
     * 火星坐标系 (GCJ-02) to 84 * * @param $lon * @param $lat * @return
     */
    public static function gcj02_To_Gps84($lat, $lon) {
        $gps = self::transform($lat, $lon);
        $lontitude = $lon * 2 - $gps[1];
        $latitude = $lat * 2 - $gps[0];
        return [$latitude, $lontitude];
    }
    
    /**
     * 火星坐标系 (GCJ-02) 与百度坐标系 (BD-09) 的转换算法 将 GCJ-02 坐标转换成 BD-09 坐标
     *
     * @param $lat
     * @param $lon
     */
    public static function gcj02_To_Bd09($lat, $lon) {
        $x = $lon;
        $y = $lat;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * self::$x_pi);
        $theta = atan2($y, $x) + 0.000003 * cos($x * self::$x_pi);
        $tempLon = $z * cos($theta) + 0.0065;
        $tempLat = $z * sin($theta) + 0.006;
        return [$tempLat, $tempLon];
    }

    /**
     * * 火星坐标系 (GCJ-02) 与百度坐标系 (BD-09) 的转换算法 * * 将 BD-09 坐标转换成GCJ-02 坐标 * * @param
     * bd_lat * @param bd_lon * @return
     */
    public static function bd09_To_Gcj02($lat, $lon) {
        $x = $lon - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * self::$x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * self::$x_pi);
        $tempLon = $z * cos($theta);
        $tempLat = $z * sin($theta);
        return [$tempLat, $tempLon];
    }

    /**
     * 将gps84转为bd09
     * @param $lat
     * @param $lon
     * @return
     */
    public static function gps84_To_bd09($lat,$lon){
        $gcj02 = self::gps84_To_Gcj02($lat,$lon);
        $bd09 = self::gcj02_To_Bd09($gcj02[0], $gcj02[1]);
        return $bd09;
    }
    
    public static function bd09_To_gps84($lat,$lon){
        $gcj02 = self::bd09_To_Gcj02($lat, $lon);
        $gps84 = self::gcj02_To_Gps84($gcj02[0], $gcj02[1]);
        //保留小数点后六位
        $gps84[0] = self::retain6($gps84[0]);
        $gps84[1] = self::retain6($gps84[1]);
        return $gps84;
    }

    /**保留小数点后六位
     * @param num
    * @return
    */
    private static function retain6($num){
        return round($num, 6);
    }

}