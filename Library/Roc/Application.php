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

    protected $_running = false;

    public function __construct ()
    {
        $app = self::app();
        if (! is_null($app)) {
            throw new Roc_Exception('Only one application can be initialized');
        }
        
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
     * Retrieve application instance
     *
     * @return Roc_Application
     */
    public static function app ()
    {
        return self::$_app;
    }

    public function bootstrap ()
    {
        $bootstrap = new Bootstrap();
        if (! ($bootstrap instanceof Roc_Bootstrap)) {
            throw new Roc_Exception('Expect a Roc_Bootstrap instance, ' . get_class($bootstrap) . ' give ');
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
        $initMethodLength = strlen(Roc_Bootstrap::Roc_BOOTSTRAP_INITFUNC_PREFIX);
        foreach ($methodNames as $method) {
            if ($initMethodLength < strlen($method) && Roc_Bootstrap::Roc_BOOTSTRAP_INITFUNC_PREFIX === substr($method, 0, $initMethodLength)) {
                $bootstrap->$method($this->_dispatcher);
            }
        }
        
        return $this;
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
