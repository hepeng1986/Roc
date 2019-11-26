<?php

class Roc_Debug
{
    /**
     * debug信息
     * @var unknown
     */
    private static $_logs = [];
    /*
     * log个数
     */
    private static $_iLimit = [];
    /*
     * 函数
     */
    private static $_cbs = [];
    /*
     * 注册
     */
    public static function register($fun)
    {
        if (! in_array($fun, self::$_cbs)) {
            self::$_cbs[] = $fun;
        }
    }
    /**
     * 追加debug信息
     * @param string $type
     * @param mixed $msg
     */
    public static function add($msg, $pre = "")
    {
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        if (count(self::$_logs) > self::$_iLimit) {
            return;
        }
        self::$_logs[] = $pre.$msg;
    }

    /**
     * 取得所有debug信息
     */
    public static function getAll()
    {
        $aStat = [];
        foreach (self::$_cbs as $fun) {
            $aStat[] = call_user_func($fun);
        }
        $iRunTime = Roc_G::getRunTime();
        array_unshift($aStat, "总耗时：{$iRunTime}ms");
        array_unshift(self::$_logs, implode("\r\n", $aStat));
        return self::$_logs;
    }

    /**
     * 清空debug信息
     */
    public static function clear()
    {
        self::$_logs = [];
    }
}