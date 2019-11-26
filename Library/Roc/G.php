<?php

class Roc_G
{

    public static $Roc_CONTROLLER_DIRECTORY_NAME = 'Controller';

    const Roc_VIEW_DIRECTORY_NAME = 'View';

    const Roc_DEFAULT_VIEW_EXT = 'phtml';

    const Roc_ROUTER_DEFAULT_ACTION = 'index';

    const Roc_ROUTER_DEFAULT_CONTROLLER = 'Index';

    const Roc_ROUTER_DEFAULT_MODULE = 'Index';

    protected static $_modulePath = '';

    /**
     * 加载过的配制文件
     *
     * @var unknown
     */
    protected static $_configFiles = array();

    /**
     * 配制项
     *
     * @var unknown
     */
    protected static $_config = array();

    /**
     * 运行的开始时间
     *
     * @var unknown
     */
    protected static $_startTime = 0;

    /**
     * 初始化
     */
    public static function init ()
    {
        self::$_startTime = microtime(true);
        Roc_G::loadConfig('common');
        Roc_G::loadConfig(ENV_SCENE);
    }

    /**
     * 取得运行的时间
     *
     * @return number
     */
    public static function getRunTime ()
    {
        return round((microtime(true) - self::$_startTime) * 1000, 2);
    }

    /**
     * 取得当前频道
     *
     * @return string
     */
    public static function getChannel ()
    {
        return ENV_CHANNEL;
    }

    /**
     * 取得当前环境
     *
     * @return string
     */
    public static function getEnv ()
    {
        return ENV_SCENE;
    }

    /**
     * 是否为debug环境
     *
     * @return boolean
     */
    public static function isDebug ()
    {
        if (isset($_COOKIE['debug']) && $_COOKIE['debug'] == '2k9j38h#4') {
            return true;
        }
        if (self::getEnv() != 'ga') {
            return true;
        }
        
        $sParam = file_get_contents("php://input");
        if (! empty($sParam)) {
            $aParam = json_decode($sParam, true);
            if (isset($aParam['__debug']) && $aParam['__debug'] == '2k9j38h#4') {
                return  true;
            }
        }
        
        return false;
    }

    /**
     * 加载配制文件
     *
     * @param unknown $type            
     * @throws Roc_Exception
     */
    public static function loadConfig ($type)
    {
        if (file_exists(self::getConfPath() . '/' . $type . '.php')) {
            $sFile = self::getConfPath() . '/' . $type . '.php';
        } else {
            throw new Roc_Exception('Config file ' . $type . ' not find!');
        }
        
        $config = self::$_config;
        include $sFile;
        self::$_config = $config;
        self::$_configFiles[$type] = 1;
    }

    /**
     * 取得配制内容
     *
     * @param unknown $key
     * @param string $type
     * @param string $file
     * @return NULL multitype:
     */
    public static function setConf ($key, $type = null, $data = null)
    {
        if ($type == null) {
            self::$_config[$key] = $data;
        } else {
            self::$_config[$key][$type] = $data;
        }
    }
    
    /**
     * 取得配制内容
     *
     * @param unknown $key            
     * @param string $type            
     * @param string $file            
     * @return NULL multitype:
     */
    public static function getConf ($key, $type = null, $file = null)
    {
        if ($file != null && ! isset(self::$_configFiles[$file])) {
            self::loadConfig($file);
        }
        
        if ($type != null) {
            if (isset(self::$_config[$type][$key])) {
                return self::$_config[$type][$key];
            } else 
                if ($key == null && (isset(self::$_config[$type]))) {
                    return self::$_config[$type];
                }
            
            return null;
        }
        
        if (isset(self::$_config[$key])) {
            return self::$_config[$key];
        }
        
        return null;
    }

    /**
     * 解析Route
     *
     * @param unknown $uri            
     * @return multitype:string Ambigous <string, unknown>
     */
    public static function getRoute ($uri)
    {
        $module = self::Roc_ROUTER_DEFAULT_MODULE;
        $controller = self::Roc_ROUTER_DEFAULT_CONTROLLER;
        $action = self::Roc_ROUTER_DEFAULT_ACTION;
        
        // 去掉参数，在$_GET里能获取到
        $aUrl = parse_url($uri);
        $path = preg_replace('/[\.\?].+$/', '', $uri);
        $path = explode('/', trim($path, '/'));
        $root = APP_PATH;
        if (! empty($path[0]) && is_dir($root . '/' . ucfirst($path[0]))) {
            $module = ucfirst(array_shift($path));
        }
        if (! empty($path[0]) && ! empty($path[1]) && file_exists($root . '/' . $module . '/' . self::$Roc_CONTROLLER_DIRECTORY_NAME . '/' . ucfirst($path[0]) . '/' . ucfirst($path[1]) . '.php')) {
            $controller = ucfirst(array_shift($path)) . '_' . ucfirst(array_shift($path));
        } elseif (! empty($path[0]) && file_exists($root . '/' . $module . '/' . self::$Roc_CONTROLLER_DIRECTORY_NAME . '/' . ucfirst($path[0]) . '.php')) {
            $controller = ucfirst(array_shift($path));
        }
        if (! empty($path[0])) {
            $action = array_shift($path);
        }
        
        $rest = $path;
        if (count($rest) % 2 == 1) {
            $rest[] = '';
        }
        
        // 解析这种的Action: detail.id.184.html
        // TODO
        /*
         * $tmp = explode('.', trim(strstr($uri, '.'), '.')); if (count($tmp) > 1) { $rest = $tmp; $rest[count($tmp) - 1] = strstr($rest[count($tmp) - 1], '?', true); } if (count($rest) % 2 == 1) { $rest[] = ''; }
         */
        
        $query = trim(isset($aUrl['query']) ? $aUrl['query'] : '', '&');
        foreach ($rest as $k => $v) {
            if ($k % 2 == 0) {
                $query .= '&' . $v;
            } else {
                $query .= '=' . $v;
            }
        }
        
        /*
         * if (isset($aUrl['query'])) { parse_str($aUrl['query'], $aParam); foreach ($aParam as $k => $v) { $rest[] = $k; $rest[] = $v; } } if (count($rest) % 2 == 1) { $rest[] = ''; }
         */
        
        $aRoute = array(
            'path' => $root,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'query' => $query
        );
        
        return $aRoute;
    }

    /**
     * Module + Controller + Action => URL
     *
     * @param unknown $sMca
     * @return string
     */
    public static function routeToUrl ($sRoute, $bFullPath = false)
    {
        $sUrl = '/' . strtolower(str_replace(array(
            'Controller_',
            '_'
        ), array(
            '',
            '/'
        ), $sRoute));
        if ($bFullPath == false) {
            $sUrl = str_replace('/index', '', $sUrl);
        }
        return $sUrl;
    }

    /**
     * 取得URL地址
     *
     * @param string $uri            
     * @param string $params            
     * @param string $domain            
     * @param string $postfix            
     * @return string
     */
    public static function getUrl ($uri = null, $params = null, $bFullPath = false, $domain = null, $postfix = null)
    {
        $url = '';
        if ($domain != null) {
            $url .= 'http://' . self::getConf($domain, 'domain');
        }
        if ($uri == null) {
            $oRequest = Roc_Dispatcher::getInstance()->getRequest();
            $uri = $oRequest->getRequestUri();
        }
        $aRoute = self::getRoute($uri);
        $url = self::routeToUrl($aRoute['module'] . '_' . 'Controller_' . $aRoute['controller'] . '_' . $aRoute['action'], $bFullPath);
        if ($postfix == null) {
            $delimiter = '/';
        } else {
            $delimiter = '.';
        }
        if (! empty($params)) {
            $tmp = http_build_query($params);
            // $url .= '?' . $tmp;
            $url .= $delimiter . str_replace(array(
                '&',
                '='
            ), $delimiter, $tmp);
            /*
             * foreach ($params as $k => $v) { $url .= $delimiter . urlencode($k) . $delimiter . urlencode($v); }
             */
        }
        return $url . $postfix;
    }

    /**
     * 设置module路径
     */
    public static function setModulePath ($module)
    {
        self::$_modulePath = APP_PATH . DIRECTORY_SEPARATOR . $module;
    }

    /**
     * 取得module的路径
     *
     * @return string
     */
    public static function getModulePath ()
    {
        return self::$_modulePath;
    }

    /**
     * 取得Controller的路径
     *
     * @return string
     */
    public static function getControllerPath ()
    {
        return self::$_modulePath . DIRECTORY_SEPARATOR . self::$Roc_CONTROLLER_DIRECTORY_NAME;
    }

    /**
     * 取得View的路径
     *
     * @return string
     */
    public static function getViewPath ()
    {
        return self::$_modulePath . DIRECTORY_SEPARATOR . self::Roc_VIEW_DIRECTORY_NAME;
    }

    /**
     * 取得配制文件路径
     *
     * @return string
     */
    public static function getConfPath ()
    {
        return APP_PATH . '/../conf';
    }

    /**
     * 是否为绝对路径
     *
     * @param unknown $path            
     * @return boolean
     */
    public static function isAbsolutePath ($path)
    {
        if (substr($path, 0, 1) == "/" || ((strpos($path, ":") !== false) && (strpos(PHP_OS, "WIN") !== false))) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 返回请求的参数
     */
    public static function getParams()
    {
        global $sRouteUri;
        if (isset($sRouteUri)) {
            return $sRouteUri;
        } else {
            return $_REQUEST;
        }
    }

    /*
     * 得到模块的配置文件
     * @param $sKey  配置文件的key
     * @param $sFile 配置文件
     * @param $sDir 模块下的目录 默认为Conf
     * @param $sModule 模块名 默认为本模块
     */
    public static function getModuleConf($sKey, $sFile, $sDir = "Conf",$sModule = null){
        //缓存一下
        static $_tempCache = [];
        if(empty($sModule)){
            $sModule = self::getModulePath();
        }else{
            $sModule = APP_PATH . DIRECTORY_SEPARATOR.$sModule;
        }
        if(empty($sDir)){
            $sDir = "Conf";
        }
        //配置文件名
        $sFileName = $sModule .DIRECTORY_SEPARATOR . $sDir .DIRECTORY_SEPARATOR.$sFile. '.php';
        if(!file_exists($sFileName)){
            return null;
        }
        //无缓存，加载
        $cacheName = basename($sModule)."_".$sDir."_".$sFile;
        if(!isset($_tempCache[$cacheName])){
            $_tempCache[$cacheName] = include $sFileName;
            $_tempCache[$cacheName] = array_change_key_case($_tempCache[$cacheName]);
        }
//        //如果$sKey填null，返回整个文件配置
//        if($sKey == null && isset($_tempCache[$cacheName])){
//            return $_tempCache[$cacheName];
//        }
        //存在则返回
        if($sKey != null && isset($_tempCache[$cacheName][strtolower($sKey)])){
            return $_tempCache[$cacheName][strtolower($sKey)];
        }
        //没找到
        return null;
    }
    /*
     * empty 排除0
     */
    public static function emptyZero($value){
        if($value === 0 || $value === "0"){
            return false;
        }
        return empty($value);
    }
    /*
     * 抛出异常
     */
    public static function throwException($sMsg,$iCode = ERR_EXCEPTION){
        throw new Roc_Exception($sMsg,$iCode);
    }
    /**
     * 提取出异常错误里的详细信息
     *
     * @param object $oExp
     * @param string $sImp
     */
    public static function parseException ($oExp, $sImp = "\n")
    {
        $aMsg = array();
        $aMsg[] = '# 错误时间 => ' . date('Y-m-d H:i:s');
        $aMsg[] = '# 请求URL=> ' . Roc_G::getUrl();
        $aMsg[] = '# 请求参数 => ' . json_encode(self::getParams(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($oExp->getCode() > 0) {
            $aMsg[] = '# 错误代码 => ' . $oExp->getCode();
        }
        $aMsg[] = '# 错误消息 => ' . $oExp->getMessage();
        $aMsg[] = '# 错误位置 => ' . $oExp->getFile() . '(' . $oExp->getLine() . '行)';
        $aEtrac = $oExp->getTrace();
        $iTotalEno = count($aEtrac) - 1;
        $iEno = 0;
        foreach ($aEtrac as $iEno => $aTrace) {
            $aMsg[] = '================================================================' . $sImp;
            $tmp = '第' . ($iTotalEno - $iEno) . '步 ';
            if (isset($aTrace['file'])) {
                $tmp .= '文件:' . $aTrace['file'] . ' (' . $aTrace['line'] . '行)';
            }

            $tmp .= $sImp . '函数名：';
            if (isset($aTrace['class'])) {
                $tmp .= $aTrace['class'] . '->';
            }
            $tmp .= $aTrace['function'] . '()';
            if (isset($aTrace['args']) && ! empty($aTrace['args'])) {
                $aTmpArg = array();
                foreach ($aTrace['args'] as $ano => $aArg) {
                    $atmp = $sImp . '@参数_' . $ano . '( ' . gettype($aArg) . ' ) = ';
                    if (is_numeric($aArg) || is_string($aArg)) {
                        $atmp .= $aArg;
                    } elseif (is_object($aArg)) {
                        $atmp .= get_class($aArg);
                    } else {
                        $atmp .= json_encode($aArg);
                    }
                    $aTmpArg[] = $atmp;
                }
                $tmp .= implode('', $aTmpArg);
            }
            $aMsg[] = $tmp;
        }
        return join($sImp, $aMsg);
    }
}
