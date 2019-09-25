<?php

include_once LIB_PATH.'/../vendor/phayes/geophp/geoPHP.inc';

class Util_Polygon {


    static $POLYGON = null;

    /**
     * 生成多边形
     * @param $aGeo
     * @throws exception
     */
    public static function loadPoly($sGeo)
    {
        $aGeo = json_decode($sGeo, true);
        $geos = str_replace(',', ' ', $aGeo[0]);
        $geos = str_replace('  ', ' ', $geos);
        $geos = str_replace(';' , ',', $geos);
        if ($geos) {
            $aGeos = explode(',', $geos);
            $geos = trim($geos,',');//add by zfx
            $geos .= ', ' . $aGeos[0];
        }
        self::$POLYGON = geoPHP::load('POLYGON(('.$geos.'))');
    }

    /**
     * 判断点是否在多边形内
     * @param $aGeo
     * @param $x
     * @param $y
     * @return mixed
     * @throws exception
     */
    public static function polyContainsPoint($aGeo, $x, $y)
    {
        self::loadPoly($aGeo);
        $center_xy = $x . ' ' . $y;
        $point = geoPHP::load('POINT('.$center_xy.')');
        return self::$POLYGON -> contains($point);
    }

}