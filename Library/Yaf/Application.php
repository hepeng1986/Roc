<?php

/**
 * Yaf Application
 */
class Yaf_Application
{

    /**
     *
     * @var Yaf_Application
     */
    protected static $_app = null;

    /**
     *
     * @var Yaf_Dispatcher
     */
    protected $_dispatcher = null;

    protected $_running = false;

    public function __construct ()
    {
        $app = self::app();
        if (! is_null($app)) {
            throw new Yaf_Exception('Only one application can be initialized');
        }
        
        Yaf_G::init();
        
        // request initialization
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $request = new Yaf_Request_Http();
        } else {
            $request = new Yaf_Request_Cli();
        }
        if ($request == null) {
            throw new Yaf_Exception('Initialization of request failed');
        }
        
        // dispatcher
        $this->_dispatcher = Yaf_Dispatcher::getInstance();
        if ($this->_dispatcher == null || ! ($this->_dispatcher instanceof Yaf_Dispatcher)) {
            throw new Yaf_Exception('Instantiation of dispatcher failed');
        }
        $this->_dispatcher->setRequest($request);
        
        self::$_app = $this;
    }

    /**
     * Retrieve application instance
     *
     * @return Yaf_Application
     */
    public static function app ()
    {
        return self::$_app;
    }

    public function bootstrap ()
    {
        $bootstrap = new Bootstrap();
        if (! ($bootstrap instanceof Yaf_Bootstrap)) {
            throw new Yaf_Exception('Expect a Yaf_Bootstrap instance, ' . get_class($bootstrap) . ' give ');
        }
        if (version_compare(PHP_VERSION, '5.2.6') === - 1) {
            $class = new ReflectionObject($bootstrap);
            $classMethods = $class->getMethods();
            $methodNames = array();
            
            foreach ($classMethods as $method) {
                $methodNames[] = $method->getName();
            }
        } else {
            $methodNames = get_class_methods($bootstrap);
        }
        $initMethodLength = strlen(Yaf_Bootstrap::YAF_BOOTSTRAP_INITFUNC_PREFIX);
        foreach ($methodNames as $method) {
            if ($initMethodLength < strlen($method) && Yaf_Bootstrap::YAF_BOOTSTRAP_INITFUNC_PREFIX === substr($method, 0, $initMethodLength)) {
                $bootstrap->$method($this->_dispatcher);
            }
        }
        
        return $this;
    }

    /**
     * Start Yaf_Application
     */
    public function run ()
    {
        if ($this->_running == true) {
            throw new Yaf_Exception('An application instance already run');
        } else {
            $this->_running = true;
            
            return $this->_dispatcher->dispatch();
        }
    }

    /**
     * Get Yaf_Dispatcher instance
     *
     * @return Yaf_Dispatcher
     */
    public function getDispatcher ()
    {
        return $this->_dispatcher;
    }

    public function execute ($args)
    {
        $arguments = func_get_args();
        $callback = $arguments[0];
        if (! is_callable($callback)) {
            trigger_error('First argument is expected to be a valid callback', E_USER_WARNING);
        }
        array_shift($arguments);
        
        return call_user_func_array($callback, $arguments);
    }
}
