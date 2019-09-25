<?php

/**
 * 敏感词类
 * @author xiejinci
 *
 */
class Util_Badword
{

    /**
     * 检测是否有违禁词
     *
     * @param unknown $sData            
     * @param unknown $aBadword            
     */
    public static function check ($sStr, $aBadword)
    {
        return preg_match('/' . join('|', $aBadword) . '/i', $sStr);
    }

    /**
     * 替换词禁词
     *
     * @param unknown $sData            
     * @param unknown $aBadword            
     * @param string $sReplace            
     */
    public static function replace ($sStr, $aBadword, $sReplace = '*')
    {
        $aBadword = array_combine($aBadword, array_fill(0, count($aBadword), $sReplace));
        return strtr($sStr, $aBadword);
    }
}