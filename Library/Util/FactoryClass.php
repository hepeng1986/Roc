<?php
/**
 * Created by PhpStorm.
 * User: xuchuyuan
 * Date: 2019/1/26
 * Time: 4:53 PM
 */

class Util_FactoryClass
{

    protected static $instance = null;

    protected static $aInstance = null;

    public static function build($className = null)
    {
        $className = ucfirst($className);

        if(self::$instance == null) {
            if ($className && class_exists($className)) {
                self::$instance = new $className();
            } else {
                return null;
            }
        }

        return self::$instance;
    }

    public static function classBuilder($className = null)
    {
        $className = ucfirst($className);

        if (!class_exists($className)) {
            $className = call_user_func(function ($class) {
                $aClass = explode('_', $class);
                if (count($aClass) == 3) {
                    return "{$aClass[0]}_Base_{$aClass[2]}";
                }
            }, $className);
        }

        if(self::$aInstance[$className] == null) {
            if ($className && class_exists($className)) {
                self::$aInstance[$className] = new $className();
            } else {
                return null;
            }
        }

        return self::$aInstance[$className];
    }

    /**
     * 读取特殊控制器
     * @param $className
     * @param $request
     * @param $response
     * @param $view
     *
     * @return null
     */
    public static function controllerBuilder($className,$request,$response,$view)
    {
        $className = ucfirst($className);

        if (!class_exists($className)) {
            return null;
        }

        if(self::$aInstance[$className] == null) {
            self::$aInstance[$className] = new $className($request, $response, $view);
        }
        return self::$aInstance[$className];
    }
}