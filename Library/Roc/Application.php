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
        } else {
            $request = new Roc_Request_Cli();
        }
        if ($request == null) {
            throw new Roc_Exception('Initialization of request failed');
        }
        
        // dispatcher
        $this->_dispatcher = Roc_Dispatcher::getInstance();
        if ($this->_dispatcher == null || ! ($this->_dispatcher instanceof Roc_Dispatcher)) {
            throw new Roc_Exception('Instantiation of dispatcher failed');
        }
        $this->_dispatcher->setRequest($request);
        
        self::$_app = $this;
    }

    /**
     * Start Roc_Application
     */
    public function run ()
    {
        if ($this->_running == true) {
            throw new Roc_Exception('An application instance already run');
        } else {
            $this->_running = true;
            
            return $this->_dispatcher->dispatch();
        }
    }

    /**
     * Get Roc_Dispatcher instance
     *
     * @return Roc_Dispatcher
     */
    public function getDispatcher ()
    {
        return $this->_dispatcher;
    }
}
