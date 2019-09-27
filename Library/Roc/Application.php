<?php

/**
 * Yaf Application
 */
class Roc_Application
{

    /**
     *
     * @var Roc_Application
     */
    protected static $_app = null;

    /**
     *
     * @var Roc_Dispatcher
     */
    protected $_dispatcher = null;

    private $_running = false;

    public static function run ()
    {
        if (self::$_running == true) {
            echo "An application instance already run";die;
        }
        self::$_running = true;

        //注册异常，加载配置
        Roc_G::init();
        
        // request initialization
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $request = new Roc_Request_Http();
            $reponse = new Roc_Response_Http();
        } else {
            $request = new Roc_Request_Cli();
            $reponse = new Roc_Response_Cli();
        }
        if ($request == null) {
            throw new Roc_Exception('Initialization of request failed');
        }
        
        // dispatcher
        self::$_dispatcher = Roc_Dispatcher::getInstance();
        self::$_dispatcher->setRequest($request);
        self::$_dispatcher->setResponse($reponse);
        //run
        self::$_dispatcher->dispatch();
    }
}
