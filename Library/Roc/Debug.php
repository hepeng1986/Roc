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